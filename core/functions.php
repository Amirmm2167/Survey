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
    $base = rtrim(BASE_URL, '/');
    $path = ltrim($path, '/');
    return $base . '/' . $path;
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
 * Fetches a user by their identifier (username, email, or phone).
 * @param PDO $pdo The database connection object.
 * @param string $identifier The identifier.
 * @return array|false The user data or false if not found.
 */
function get_user_by_identifier($pdo, $identifier) {
    $stmt = $pdo->prepare(
        "SELECT u.id, u.username, u.password_hash, r.name as role_name
         FROM users u
         JOIN roles r ON u.role_id = r.id
         WHERE u.username = ? OR u.email = ? OR u.phone_number = ?"
    );
    $stmt->execute([$identifier, $identifier, $identifier]);
    return $stmt->fetch();
}

/**
 * Fetches the active plan for a given user.
 * @param PDO $pdo The database connection object.
 * @param int $user_id The user's ID.
 * @return array|false The plan data or false if not found.
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
 * @param array $userData
 * @return bool True on success, false on failure.
 */
function create_user($pdo, $userData) {
    try {
        $pdo->beginTransaction();
        $password_hash = password_hash($userData['password'], PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, email, phone_number, password_hash, role_id) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$userData['username'], $userData['email'], $userData['phone_number'], $password_hash, $userData['role_id']]);
        $user_id = $pdo->lastInsertId();

        $start_date = date('Y-m-d H:i:s');
        $end_date = date('Y-m-d H:i:s', strtotime('+1 month'));
        $stmt = $pdo->prepare("INSERT INTO subscriptions (user_id, plan_id, start_date, end_date, is_active) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $userData['plan_id'], $start_date, $end_date, true]);

        $stmt = $pdo->prepare("INSERT INTO wallets (user_id, balance, last_refill_date) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $userData['initial_credits'], $start_date]);

        if ($userData['initial_credits'] > 0) {
            $stmt = $pdo->prepare("INSERT INTO credit_transactions (user_id, transaction_type, credits_changed) VALUES (?, 'initial_credits', ?)");
            $stmt->execute([$user_id, $userData['initial_credits']]);
        }
        $pdo->commit();
        return true;
    } catch (PDOException $e) {
        $pdo->rollBack();
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
        $unique_id = bin2hex(random_bytes(8));
        $stmt = $pdo->prepare("INSERT INTO surveys (creator_id, unique_id, title, description, access_level, access_code, status) VALUES (?, ?, ?, ?, ?, ?, 'draft')");
        $stmt->execute([$creator_id, $unique_id, $surveyData['title'], $surveyData['description'], $surveyData['access_level'], $surveyData['access_code']]);
        $survey_id = $pdo->lastInsertId();
        foreach ($surveyData['questions'] as $q_index => $question) {
            // ... (rest of the logic)
        }
        $pdo->commit();
        return $survey_id;
    } catch (PDOException $e) {
        $pdo->rollBack();
        return false;
    }
}

// ... other functions like update_survey, delete_survey, etc. ...

/**
 * --- Admin Statistics Functions ---
 */
function get_total_user_count($pdo) { return $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(); }
function get_total_survey_count($pdo) { return $pdo->query("SELECT COUNT(*) FROM surveys")->fetchColumn(); }
// ... etc. ...

?>
