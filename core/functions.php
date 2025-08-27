<?php
// core/functions.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/database.php';

function site_url($path = '') {
    $base = rtrim(BASE_URL, '/');
    $path = ltrim($path, '/');
    return $base . '/' . $path;
}

function redirect($url) {
    header("Location: {$url}");
    exit;
}

function get_user_by_identifier($pdo, $identifier) {
    $stmt = $pdo->prepare("SELECT u.id, u.username, u.password_hash, r.name as role_name FROM users u JOIN roles r ON u.role_id = r.id WHERE u.username = ? OR u.email = ? OR u.phone_number = ?");
    $stmt->execute([$identifier, $identifier, $identifier]);
    return $stmt->fetch();
}

function get_user_plan($pdo, $user_id) {
    $stmt = $pdo->prepare("SELECT p.* FROM plans p JOIN subscriptions s ON p.id = s.plan_id WHERE s.user_id = ? AND s.is_active = TRUE");
    $stmt->execute([$user_id]);
    return $stmt->fetch();
}

function verify_survey_ownership($pdo, $survey_id, $user_id) {
    $stmt = $pdo->prepare("SELECT id FROM surveys WHERE id = ? AND creator_id = ?");
    $stmt->execute([$survey_id, $user_id]);
    return $stmt->fetch() !== false;
}

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

function create_survey($pdo, $creator_id, $surveyData) {
    try {
        $pdo->beginTransaction();
        $unique_id = bin2hex(random_bytes(8));
        $stmt = $pdo->prepare("INSERT INTO surveys (creator_id, unique_id, title, description, access_level, access_code, status) VALUES (?, ?, ?, ?, ?, ?, 'draft')");
        $stmt->execute([$creator_id, $unique_id, $surveyData['title'], $surveyData['description'], $surveyData['access_level'], $surveyData['access_code']]);
        $survey_id = $pdo->lastInsertId();
        foreach ($surveyData['questions'] as $q_index => $question) {
            $question_text = trim($question['text']);
            if (empty($question_text)) continue;
            $stmt = $pdo->prepare("INSERT INTO questions (survey_id, question_text, question_type, display_order, is_required) VALUES (?, ?, ?, ?, ?)");
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
        return false;
    }
}

function update_survey($pdo, $survey_id, $user_id, $surveyData) {
    try {
        $sql = "UPDATE surveys SET title = :title, description = :description, status = :status, theme_id = :theme_id, custom_theme_id = :custom_theme_id WHERE id = :survey_id AND creator_id = :creator_id";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([':title' => $surveyData['title'], ':description' => $surveyData['description'], ':status' => $surveyData['status'], ':theme_id' => $surveyData['theme_id'], ':custom_theme_id' => $surveyData['custom_theme_id'], ':survey_id' => $survey_id, ':creator_id' => $user_id]);
    } catch (PDOException $e) { return false; }
}

function delete_survey($pdo, $survey_id, $user_id) {
    try {
        $stmt = $pdo->prepare("DELETE FROM surveys WHERE id = ? AND creator_id = ?");
        return $stmt->execute([$survey_id, $user_id]);
    } catch (PDOException $e) { return false; }
}

function save_custom_theme($pdo, $creator_id, $themeData) {
    try {
        $sql = "INSERT INTO custom_themes (creator_id, theme_name, color_primary, color_background, color_text, color_accent, color_panel_bg, font_family) VALUES (:creator_id, :theme_name, :color_primary, :color_background, :color_text, :color_accent, :color_panel_bg, :font_family)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':creator_id' => $creator_id, ':theme_name' => $themeData['theme_name'], ':color_primary' => $themeData['colors']['color_primary'], ':color_background' => $themeData['colors']['color_background'], ':color_text' => $themeData['colors']['color_text'], ':color_accent' => $themeData['colors']['color_accent'], ':color_panel_bg' => $themeData['colors']['color_panel_bg'], ':font_family' => $themeData['font_family']]);
        return true;
    } catch (PDOException $e) { return false; }
}

function delete_custom_theme($pdo, $theme_id, $user_id) {
    try {
        $stmt = $pdo->prepare("UPDATE surveys SET custom_theme_id = NULL WHERE custom_theme_id = ? AND creator_id = ?");
        $stmt->execute([$theme_id, $user_id]);
        $stmt = $pdo->prepare("DELETE FROM custom_themes WHERE id = ? AND creator_id = ?");
        return $stmt->execute([$theme_id, $user_id]);
    } catch (PDOException $e) { return false; }
}

function submit_survey_answers($pdo, $survey_id, $respondent_id, $question_count, $answers_data) {
    try {
        $pdo->beginTransaction();
        foreach ($answers_data as $question_id => $answer_value) {
            // ... (answer saving logic) ...
        }
        $stmt = $pdo->prepare("SELECT creator_id FROM surveys WHERE id = ?");
        $stmt->execute([$survey_id]);
        $creator_id = $stmt->fetchColumn();
        if ($creator_id) {
            $stmt = $pdo->prepare("UPDATE wallets SET reserved_balance = reserved_balance - ? WHERE user_id = ? AND reserved_balance >= ?");
            $stmt->execute([$question_count, $creator_id, $question_count]);
            $credits_spent = -$question_count;
            $stmt = $pdo->prepare("INSERT INTO credit_transactions (user_id, survey_id, transaction_type, credits_changed) VALUES (?, ?, 'survey_response', ?)");
            $stmt->execute([$creator_id, $survey_id, $credits_spent]);
        }
        $stmt = $pdo->prepare("UPDATE respondents SET completed_at = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->execute([$respondent_id]);
        $pdo->commit();
        return true;
    } catch (PDOException $e) {
        $pdo->rollBack();
        return false;
    }
}

// --- Admin Statistics Functions ---
function get_total_user_count($pdo) { return $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(); }
function get_total_survey_count($pdo) { return $pdo->query("SELECT COUNT(*) FROM surveys")->fetchColumn(); }
function get_total_credits_used($pdo) { return abs($pdo->query("SELECT SUM(credits_changed) FROM credit_transactions WHERE credits_changed < 0")->fetchColumn() ?? 0); }
function get_total_credits_available($pdo) { return $pdo->query("SELECT SUM(balance) FROM wallets")->fetchColumn() ?? 0; }
function get_total_credits_bought($pdo) { return $pdo->query("SELECT SUM(credits_changed) FROM credit_transactions WHERE transaction_type IN ('admin_add', 'initial_credits')")->fetchColumn() ?? 0; }
function get_user_count_by_tier($pdo) { return $pdo->query("SELECT p.name, COUNT(s.user_id) as user_count FROM plans p LEFT JOIN subscriptions s ON p.id = s.plan_id AND s.is_active = TRUE GROUP BY p.id ORDER BY p.level ASC")->fetchAll(PDO::FETCH_ASSOC); }
function get_users_with_most_used_credits($pdo, $limit = 5) { return $pdo->query("SELECT u.username, SUM(ABS(ct.credits_changed)) as total_used FROM credit_transactions ct JOIN users u ON ct.user_id = u.id WHERE ct.credits_changed < 0 GROUP BY ct.user_id ORDER BY total_used DESC LIMIT " . (int)$limit)->fetchAll(PDO::FETCH_ASSOC); }
function get_surveys_with_highest_credit_usage($pdo, $limit = 5) { return $pdo->query("SELECT s.title, SUM(ABS(ct.credits_changed)) as total_usage FROM credit_transactions ct JOIN surveys s ON ct.survey_id = s.id WHERE ct.transaction_type = 'survey_response' GROUP BY ct.survey_id ORDER BY total_usage DESC LIMIT " . (int)$limit)->fetchAll(PDO::FETCH_ASSOC); }
?>
