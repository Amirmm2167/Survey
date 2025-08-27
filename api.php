<?php
// Set content type to JSON
header('Content-Type: application/json');

// Core dependencies
require_once __DIR__ . '/core/session.php';
require_once __DIR__ . '/core/functions.php';

// Get the database connection
$pdo = get_db_connection();

// Get the requested action
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Response array
$response = ['status' => 'error', 'message' => 'Invalid action.'];

switch ($action) {
    case 'login':
        $identifier = $_POST['username'] ?? ''; // The form field is still named 'username' for simplicity
        $password = $_POST['password'] ?? '';

        if (empty($identifier) || empty($password)) {
            $response['message'] = 'Login identifier and password are required.';
            break;
        }

        $user = get_user_by_identifier($pdo, $identifier);

        if ($user && password_verify($password, $user['password_hash'])) {
            // Authentication successful
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role_name'] = $user['role_name'];

            $response['status'] = 'success';
            $response['message'] = 'Login successful.';
            $response['redirect'] = ($user['role_name'] === 'admin') ? 'admin/' : 'creator/';
        } else {
            // Authentication failed
            $response['message'] = 'Invalid username or password.';
        }
        break;

    case 'create_survey':
        if (!isset($_SESSION['user_id'])) {
            $response['message'] = 'Unauthorized.';
            break;
        }
        $creator_id = $_SESSION['user_id'];

        $surveyData = [
            'title' => trim($_POST['title'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'access_level' => $_POST['access_level'] ?? 'public',
            'access_code' => ($_POST['access_level'] === 'private_code') ? trim($_POST['access_code'] ?? '') : null,
            'questions' => $_POST['questions'] ?? []
        ];

        if (empty($surveyData['title']) || empty($surveyData['questions'])) {
            $response['message'] = 'Title and at least one question are required.';
            break;
        }

        if (create_survey($pdo, $creator_id, $surveyData)) {
            $response['status'] = 'success';
            $response['message'] = 'Survey created successfully!';
            $response['redirect'] = 'index.php';
        } else {
            $response['message'] = 'Failed to create survey due to a database error.';
        }
        break;

    case 'save_theme':
        if (!isset($_SESSION['user_id'])) {
            $response['message'] = 'Unauthorized.';
            break;
        }
        $creator_id = $_SESSION['user_id'];

        // Re-check permissions
        $plan = get_user_plan($pdo, $creator_id);
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM custom_themes WHERE creator_id = ?");
        $stmt->execute([$creator_id]);
        $theme_count = $stmt->fetchColumn();
        if (!$plan || $plan['custom_theme_limit'] <= 0 || $theme_count >= $plan['custom_theme_limit']) {
            $response['message'] = "You don't have permission to create more themes.";
            break;
        }

        $themeData = [
            'theme_name' => trim($_POST['theme_name'] ?? ''),
            'colors' => [
                'color_primary'   => $_POST['color_primary'] ?? '#77b9df',
                'color_background'=> $_POST['color_background'] ?? '#ffffff',
                'color_text'      => $_POST['color_text'] ?? '#333333',
                'color_accent'    => $_POST['color_accent'] ?? '#ff7452',
                'color_panel_bg'  => $_POST['color_panel_bg'] ?? '#f8f9fa'
            ],
            'font_family' => ($plan['level'] == 5 && !empty($_POST['font_family'])) ? trim($_POST['font_family']) : null
        ];

        if(empty($themeData['theme_name'])) {
            $response['message'] = 'Theme name is required.';
            break;
        }

        if (save_custom_theme($pdo, $creator_id, $themeData)) {
            $response['status'] = 'success';
            $response['message'] = 'Theme saved successfully!';
            $response['redirect'] = 'theme_manager.php';
        } else {
            $response['message'] = 'Failed to save theme due to a database error.';
        }
        break;

    case 'update_survey':
        if (!isset($_SESSION['user_id'])) {
            $response['message'] = 'Unauthorized.';
            break;
        }
        $user_id = $_SESSION['user_id'];
        $survey_id = filter_input(INPUT_POST, 'survey_id', FILTER_VALIDATE_INT);

        if (!$survey_id || !verify_survey_ownership($pdo, $survey_id, $user_id)) {
            $response['message'] = "Survey not found or permission denied.";
            break;
        }

        $theme_selection = $_POST['theme_selection'] ?? 'none';
        $theme_id = null;
        $custom_theme_id = null;
        if (strpos($theme_selection, 'predefined-') === 0) {
            $theme_id = (int) str_replace('predefined-', '', $theme_selection);
        } elseif (strpos($theme_selection, 'custom-') === 0) {
            $custom_theme_id = (int) str_replace('custom-', '', $theme_selection);
        }

        $surveyData = [
            'title' => trim($_POST['title'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'status' => $_POST['status'] ?? 'draft',
            'theme_id' => $theme_id,
            'custom_theme_id' => $custom_theme_id
        ];

        if (update_survey($pdo, $survey_id, $user_id, $surveyData)) {
            $response['status'] = 'success';
            $response['message'] = 'Survey updated successfully!';
            $response['redirect'] = 'index.php';
        } else {
            $response['message'] = 'Failed to update survey.';
        }
        break;

    case 'submit_survey':
        $survey_id = filter_input(INPUT_POST, 'survey_id', FILTER_VALIDATE_INT);
        $respondent_id = filter_input(INPUT_POST, 'respondent_id', FILTER_VALIDATE_INT);
        $question_count = filter_input(INPUT_POST, 'question_count', FILTER_VALIDATE_INT);
        $answers_data = $_POST['answers'] ?? [];

        if (!$survey_id || !$respondent_id || !$question_count) {
            $response['message'] = "Invalid submission data.";
            break;
        }

        if (submit_survey_answers($pdo, $survey_id, $respondent_id, $question_count, $answers_data)) {
            $response['status'] = 'success';
            $response['message'] = 'Survey submitted successfully!';
            $response['redirect'] = 'thank_you.php';
        } else {
            $response['message'] = 'Failed to submit survey.';
        }
        break;

    case 'delete_survey':
        if (!isset($_SESSION['user_id'])) {
            $response['message'] = 'Unauthorized.';
            break;
        }
        $user_id = $_SESSION['user_id'];
        $survey_id = filter_input(INPUT_POST, 'survey_id', FILTER_VALIDATE_INT);

        if (!$survey_id || !verify_survey_ownership($pdo, $survey_id, $user_id)) {
            $response['message'] = "Survey not found or permission denied.";
            break;
        }

        if (delete_survey($pdo, $survey_id, $user_id)) {
            $response['status'] = 'success';
            $response['message'] = 'Survey deleted successfully!';
        } else {
            $response['message'] = 'Failed to delete survey.';
        }
        break;

    case 'delete_theme':
        if (!isset($_SESSION['user_id'])) {
            $response['message'] = 'Unauthorized.';
            break;
        }
        $user_id = $_SESSION['user_id'];
        $theme_id = filter_input(INPUT_POST, 'theme_id', FILTER_VALIDATE_INT);

        if (!$theme_id) {
            $response['message'] = "Invalid theme ID.";
            break;
        }

        if (delete_custom_theme($pdo, $theme_id, $user_id)) {
            $response['status'] = 'success';
            $response['message'] = 'Theme deleted successfully!';
        } else {
            $response['message'] = 'Failed to delete theme.';
        }
        break;

    // Other cases for 'save_theme', 'create_user', etc., will be added here.
    case 'create_user':
        // Admin-only action
        if (!isset($_SESSION['role_name']) || $_SESSION['role_name'] !== 'admin') {
            $response['message'] = 'Unauthorized.';
            break;
        }

        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone_number'] ?? '');

        $userData = [
            'username' => trim($_POST['username'] ?? ''),
            'email' => !empty($email) ? $email : null,
            'phone_number' => !empty($phone) ? $phone : null,
            'password' => $_POST['password'] ?? '',
            'role_id' => filter_var($_POST['role_id'], FILTER_VALIDATE_INT),
            'plan_id' => filter_var($_POST['plan_id'], FILTER_VALIDATE_INT),
            'initial_credits' => filter_var($_POST['initial_credits'], FILTER_VALIDATE_INT, ['options' => ['default' => 0]])
        ];

        // Basic validation
        if (empty($userData['username']) || (empty($userData['email']) && empty($userData['phone_number'])) || empty($userData['password']) || !$userData['role_id'] || !$userData['plan_id']) {
            $response['message'] = 'Username, Password, Plan, and either Email or Phone Number are required.';
            break;
        }

        // Check for existing user
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ? OR phone_number = ?");
        $stmt->execute([$userData['username'], $userData['email'], $userData['phone_number']]);
        if ($stmt->fetch()) {
            $response['message'] = 'Username or email already exists.';
            break;
        }

        if (create_user($pdo, $userData)) {
            $response['status'] = 'success';
            $response['message'] = 'User created successfully!';
        } else {
            $response['message'] = 'Failed to create user due to a database error.';
        }
        break;

    default:
        // The default error message is already set.
        break;
}

// Echo the JSON response
echo json_encode($response);
exit;
?>
