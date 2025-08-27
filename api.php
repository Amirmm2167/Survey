<?php
header('Content-Type: application/json');
require_once __DIR__ . '/core/session.php';
require_once __DIR__ . '/core/functions.php';

$pdo = get_db_connection();
$action = $_POST['action'] ?? $_GET['action'] ?? '';
$response = ['status' => 'error', 'message' => 'Invalid action.'];

switch ($action) {
    case 'login':
        $identifier = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        if (empty($identifier) || empty($password)) {
            $response['message'] = 'Login identifier and password are required.';
            break;
        }
        $user = get_user_by_identifier($pdo, $identifier);
        if ($user && password_verify($password, $user['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role_name'] = $user['role_name'];
            $response = [
                'status' => 'success',
                'message' => 'Login successful.',
                'redirect' => ($user['role_name'] === 'admin') ? site_url('admin/') : site_url('creator/')
            ];
        } else {
            $response['message'] = 'Invalid username or password.';
        }
        break;

    case 'create_user':
        if (!isset($_SESSION['user_id']) || $_SESSION['role_name'] !== 'admin') {
            $response['message'] = 'Unauthorized.';
            break;
        }
        // ... (validation logic)
        $userData = [
            'username' => trim($_POST['username']),
            'email' => empty(trim($_POST['email'])) ? null : trim($_POST['email']),
            'phone_number' => empty(trim($_POST['phone_number'])) ? null : trim($_POST['phone_number']),
            'password' => $_POST['password'],
            'role_id' => $_POST['role_id'],
            'plan_id' => $_POST['plan_id'],
            'initial_credits' => $_POST['initial_credits']
        ];
        if (create_user($pdo, $userData)) {
            $response = ['status' => 'success', 'message' => 'User created successfully!'];
        } else {
            $response['message'] = 'Failed to create user.';
        }
        break;

    // ... other cases for create_survey, update_survey, delete_survey, save_theme, delete_theme ...

    default:
        break;
}

echo json_encode($response);
exit;
?>
