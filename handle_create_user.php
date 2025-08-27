<?php
require_once __DIR__ . '/core/session.php';
require_once __DIR__ . '/core/database.php';

// --- Authorization & Request Method Check ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id']) || $_SESSION['role_name'] !== 'admin') {
    // Redirect to login or an error page if unauthorized or accessed directly
    header('Location: login.php');
    exit;
}

// --- Input Validation ---
$username = trim($_POST['username'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$role_id = filter_var($_POST['role_id'], FILTER_VALIDATE_INT);
$plan_id = filter_var($_POST['plan_id'], FILTER_VALIDATE_INT);
$initial_credits = filter_var($_POST['initial_credits'], FILTER_VALIDATE_INT);

if (empty($username) || empty($email) || empty($password) || $role_id === false || $plan_id === false || $initial_credits === false) {
    $_SESSION['user_creation_error'] = 'All fields are required and must be valid.';
    header('Location: admin_dashboard.php');
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['user_creation_error'] = 'Invalid email format.';
    header('Location: admin_dashboard.php');
    exit;
}

// --- Check for existing user ---
try {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $email]);
    if ($stmt->fetch()) {
        $_SESSION['user_creation_error'] = 'Username or email already exists.';
        header('Location: admin_dashboard.php');
        exit;
    }
} catch (PDOException $e) {
    $_SESSION['user_creation_error'] = 'Database error checking for existing user.';
    // error_log($e->getMessage());
    header('Location: admin_dashboard.php');
    exit;
}

// --- Database Transaction for creating the user ---
try {
    $pdo->beginTransaction();

    // 1. Hash password
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    // 2. Insert into `users` table
    $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, role_id) VALUES (?, ?, ?, ?)");
    $stmt->execute([$username, $email, $password_hash, $role_id]);
    $user_id = $pdo->lastInsertId();

    // 3. Insert into `subscriptions` table (e.g., for 1 month)
    $start_date = date('Y-m-d H:i:s');
    $end_date = date('Y-m-d H:i:s', strtotime('+1 month'));
    $stmt = $pdo->prepare("INSERT INTO subscriptions (user_id, plan_id, start_date, end_date, is_active) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$user_id, $plan_id, $start_date, $end_date, true]);

    // 4. Insert into `wallets` table
    $stmt = $pdo->prepare("INSERT INTO wallets (user_id, balance, last_refill_date) VALUES (?, ?, ?)");
    $stmt->execute([$user_id, $initial_credits, $start_date]);

    // If all queries were successful, commit the transaction
    $pdo->commit();

    $_SESSION['user_creation_success'] = 'User created successfully!';
    header('Location: admin_dashboard.php');
    exit;

} catch (PDOException $e) {
    // If any query fails, roll back the transaction
    $pdo->rollBack();
    $_SESSION['user_creation_error'] = 'Failed to create user. A database error occurred.';
    // In a real app, you must log this error for debugging
    // error_log('User creation failed: ' . $e->getMessage());
    header('Location: admin_dashboard.php');
    exit;
}
?>
