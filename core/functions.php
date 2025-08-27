<?php
// core/functions.php
// A central place for all reusable application logic.

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/database.php';

/**
 * Generates an absolute URL by prepending the BASE_URL.
 * @param string $path The path to append to the base URL.
 * @return string The full URL.
 */
function site_url($path = '') {
    return BASE_URL . '/' . ltrim($path, '/');
}

/**
 * A simple helper function for redirecting.
 * @param string $url The URL to redirect to.
 */
function redirect($url) {
    header("Location: {$url}");
    exit;
}

/**
 * Sets a session flash message.
 * @param string $key The key for the message (e.g., 'error', 'success').
 * @param string $message The message content.
 */
function set_flash_message($key, $message) {
    $_SESSION['flash_messages'][$key] = $message;
}

/**
 * Gets and clears a session flash message.
 * @param string $key The key for the message.
 * @return string|null The message, or null if not set.
 */
function get_flash_message($key) {
    if (isset($_SESSION['flash_messages'][$key])) {
        $message = $_SESSION['flash_messages'][$key];
        unset($_SESSION['flash_messages'][$key]);
        return $message;
    }
    return null;
}

/**
 * Fetches a user by their username.
 * @param PDO $pdo The database connection object.
 * @param string $identifier The username, email, or phone number to find.
 * @return array|false The user data, or false if not found.
 */
function get_user_by_identifier($pdo, $identifier) {
    $stmt = $pdo->prepare(
        "SELECT u.id, u.username, u.password_hash, r.name as role_name
         FROM users u
         JOIN roles r ON u.role_id = r.id
         WHERE u.username = :identifier OR u.email = :identifier OR u.phone_number = :identifier"
    );
    $stmt->execute([':identifier' => $identifier]);
    return $stmt->fetch();
}

/**
 * Fetches the active plan for a given user.
 * @param PDO $pdo The database connection object.
 * @param int $user_id The user's ID.
 * @return array|false The plan data, or false if not found.
 */
function get_user_plan($pdo, $user_id) {
    $stmt = $pdo->prepare(
        "SELECT p.*
         FROM plans p
         JOIN subscriptions s ON p.id = s.plan_id
         WHERE s.user_id = ? AND s.is_active = TRUE"
    );
    $stmt->execute([$user_id]);
    return $stmt->fetch();
}

/**
 * Checks if a user owns a specific survey.
 * @param PDO $pdo The database connection object.
 * @param int $survey_id The survey's ID.
 * @param int $user_id The user's ID.
 * @return bool True if the user owns the survey, false otherwise.
 */
function verify_survey_ownership($pdo, $survey_id, $user_id) {
    $stmt = $pdo->prepare("SELECT id FROM surveys WHERE id = ? AND creator_id = ?");
    $stmt->execute([$survey_id, $user_id]);
    return $stmt->fetch() !== false;
}

/**
 * Creates a new user with their subscription and wallet in a transaction.
 * @param PDO $pdo
 * @param array $userData Contains username, email, phone_number, password, role_id, plan_id, initial_credits
 * @return bool True on success, false on failure.
 */
function create_user($pdo, $userData) {
    try {
        $pdo->beginTransaction();

        // 1. Hash password
        $password_hash = password_hash($userData['password'], PASSWORD_DEFAULT);

        // 2. Insert into `users` table
        $stmt = $pdo->prepare("INSERT INTO users (username, email, phone_number, password_hash, role_id) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$userData['username'], $userData['email'], $userData['phone_number'], $password_hash, $userData['role_id']]);
        $user_id = $pdo->lastInsertId();

        // 3. Insert into `subscriptions` table (e.g., for 1 month)
        $start_date = date('Y-m-d H:i:s');
        $end_date = date('Y-m-d H:i:s', strtotime('+1 month'));
        $stmt = $pdo->prepare("INSERT INTO subscriptions (user_id, plan_id, start_date, end_date, is_active) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $userData['plan_id'], $start_date, $end_date, true]);

        // 4. Insert into `wallets` table
        $stmt = $pdo->prepare("INSERT INTO wallets (user_id, balance, last_refill_date) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $userData['initial_credits'], $start_date]);

        $pdo->commit();
        return true;

    } catch (PDOException $e) {
        $pdo->rollBack();
        // In a real app, you should log this error
        // error_log('User creation failed: ' . $e->getMessage());
        return false;
    }
}

/**
 * Creates a new survey with its questions and options in a transaction.
 * @param PDO $pdo
 * @param int $creator_id
 * @param array $surveyData
 * @return int|false The new survey ID on success, false on failure.
 */
function create_survey($pdo, $creator_id, $surveyData) {
    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare(
            "INSERT INTO surveys (creator_id, title, description, access_level, access_code, status)
             VALUES (?, ?, ?, ?, ?, 'draft')"
        );
        $stmt->execute([$creator_id, $surveyData['title'], $surveyData['description'], $surveyData['access_level'], $surveyData['access_code']]);
        $survey_id = $pdo->lastInsertId();

        foreach ($surveyData['questions'] as $q_index => $question) {
            $question_text = trim($question['text']);
            if (empty($question_text)) continue;

            $stmt = $pdo->prepare(
                "INSERT INTO questions (survey_id, question_text, question_type, display_order, is_required)
                 VALUES (?, ?, ?, ?, ?)"
            );
            $stmt->execute([$survey_id, $question_text, $question['type'], $q_index, isset($question['required']) ? 1 : 0]);
            $question_id = $pdo->lastInsertId();

            if (in_array($question['type'], ['radio', 'checkbox', 'dropdown']) && !empty($question['options'])) {
                foreach ($question['options'] as $o_index => $option_text) {
                    $option_text = trim($option_text);
                    if (empty($option_text)) continue;
                    $stmt = $pdo->prepare("INSERT INTO question_options (question_id, option_text, display_order) VALUES (?, ?, ?)");
                    $stmt->execute([$question_id, $option_text, $o_index]);
                }
            }
        }

        $pdo->commit();
        return $survey_id;

    } catch (PDOException $e) {
        $pdo->rollBack();
        // error_log('Survey creation failed: ' . $e->getMessage());
        return false;
    }
}

/**
 * Saves a new custom theme to the database.
 * @param PDO $pdo
 * @param int $creator_id
 * @param array $themeData
 * @return bool True on success, false on failure.
 */
function save_custom_theme($pdo, $creator_id, $themeData) {
    try {
        $sql = "INSERT INTO custom_themes (creator_id, theme_name, color_primary, color_background, color_text, color_accent, color_panel_bg, font_family)
                VALUES (:creator_id, :theme_name, :color_primary, :color_background, :color_text, :color_accent, :color_panel_bg, :font_family)";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            ':creator_id' => $creator_id,
            ':theme_name' => $themeData['theme_name'],
            ':color_primary' => $themeData['colors']['color_primary'],
            ':color_background' => $themeData['colors']['color_background'],
            ':color_text' => $themeData['colors']['color_text'],
            ':color_accent' => $themeData['colors']['color_accent'],
            ':color_panel_bg' => $themeData['colors']['color_panel_bg'],
            ':font_family' => $themeData['font_family']
        ]);
        return true;
    } catch (PDOException $e) {
        // error_log("Theme creation failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Updates an existing survey's details and theme.
 * @param PDO $pdo
 * @param int $survey_id
 * @param int $user_id
 * @param array $surveyData
 * @return bool True on success, false on failure.
 */
function update_survey($pdo, $survey_id, $user_id, $surveyData) {
    try {
        $sql = "UPDATE surveys SET
                    title = :title,
                    description = :description,
                    status = :status,
                    theme_id = :theme_id,
                    custom_theme_id = :custom_theme_id
                WHERE id = :survey_id AND creator_id = :creator_id";

        $stmt = $pdo->prepare($sql);

        return $stmt->execute([
            ':title' => $surveyData['title'],
            ':description' => $surveyData['description'],
            ':status' => $surveyData['status'],
            ':theme_id' => $surveyData['theme_id'],
            ':custom_theme_id' => $surveyData['custom_theme_id'],
            ':survey_id' => $survey_id,
            ':creator_id' => $user_id
        ]);
    } catch (PDOException $e) {
        // error_log("Survey update failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Submits survey answers and finalizes credit deduction.
 * @param PDO $pdo
 * @param int $survey_id
 * @param int $respondent_id
 * @param int $question_count
 * @param array $answers_data
 * @return bool True on success, false on failure.
 */
function submit_survey_answers($pdo, $survey_id, $respondent_id, $question_count, $answers_data) {
    try {
        $pdo->beginTransaction();

        foreach ($answers_data as $question_id => $answer_value) {
            $stmt = $pdo->prepare("INSERT INTO answers (respondent_id, question_id, answer_text, selected_option_id) VALUES (?, ?, ?, ?)");
            if (is_array($answer_value)) { // Checkbox
                $stmt->execute([$respondent_id, $question_id, null, null]);
                $answer_id = $pdo->lastInsertId();
                foreach ($answer_value as $selected_option) {
                    $sel_stmt = $pdo->prepare("INSERT INTO answer_selections (answer_id, selected_option_id) VALUES (?, ?)");
                    $sel_stmt->execute([$answer_id, filter_var($selected_option, FILTER_VALIDATE_INT)]);
                }
            } elseif (is_numeric($answer_value)) { // Radio or Dropdown
                 $stmt->execute([$respondent_id, $question_id, null, $answer_value]);
            } else { // Text or Textarea
                $stmt->execute([$respondent_id, $question_id, trim($answer_value), null]);
            }
        }

        $stmt = $pdo->prepare("SELECT creator_id FROM surveys WHERE id = ?");
        $stmt->execute([$survey_id]);
        $creator_id = $stmt->fetchColumn();

        if ($creator_id) {
            $stmt = $pdo->prepare("UPDATE wallets SET reserved_balance = reserved_balance - ? WHERE user_id = ? AND reserved_balance >= ?");
            $stmt->execute([$question_count, $creator_id, $question_count]);
        }

        $stmt = $pdo->prepare("UPDATE respondents SET completed_at = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->execute([$respondent_id]);

        $pdo->commit();
        return true;

    } catch (PDOException $e) {
        $pdo->rollBack();
        // error_log('Survey submission failed: ' . $e->getMessage());
        return false;
    }
}

/**
 * Deletes a survey, ensuring the user is the owner.
 * @param PDO $pdo
 * @param int $survey_id
 * @param int $user_id
 * @return bool True on success, false on failure.
 */
function delete_survey($pdo, $survey_id, $user_id) {
    // verify_survey_ownership is called in the API before this function
    try {
        $stmt = $pdo->prepare("DELETE FROM surveys WHERE id = ? AND creator_id = ?");
        return $stmt->execute([$survey_id, $user_id]);
    } catch (PDOException $e) {
        // error_log("Survey deletion failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Deletes a custom theme, ensuring the user is the owner.
 * @param PDO $pdo
 * @param int $theme_id
 * @param int $user_id
 * @return bool True on success, false on failure.
 */
function delete_custom_theme($pdo, $theme_id, $user_id) {
    try {
        // First, update any surveys using this theme to null
        $stmt = $pdo->prepare("UPDATE surveys SET custom_theme_id = NULL WHERE custom_theme_id = ? AND creator_id = ?");
        $stmt->execute([$theme_id, $user_id]);

        // Then, delete the theme
        $stmt = $pdo->prepare("DELETE FROM custom_themes WHERE id = ? AND creator_id = ?");
        return $stmt->execute([$theme_id, $user_id]);
    } catch (PDOException $e) {
        // error_log("Theme deletion failed: " . $e->getMessage());
        return false;
    }
}

// More functions will be added here as the refactoring progresses.
?>
