<?php
// handle_login.php

// Core files are needed for database and session handling
require_once __DIR__ . '/core/session.php';
require_once __DIR__ . '/core/database.php';

// --- Redirect to login if not a POST request ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit;
}

// --- Get form data ---
$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

if (empty($username) || empty($password)) {
    $_SESSION['login_error'] = 'Username and password are required.';
    header('Location: login.php');
    exit;
}

// --- Find user in the database ---
try {
    // Prepare a query to get user and their role
    $stmt = $pdo->prepare(
        "SELECT u.id, u.username, u.password_hash, r.name as role_name
         FROM users u
         JOIN roles r ON u.role_id = r.id
         WHERE u.username = ?"
    );
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    // --- Verify password and log in ---
    if ($user && password_verify($password, $user['password_hash'])) {
        // Password is correct, authentication successful.
        // Regenerate session ID for security
        session_regenerate_id(true);

        // Store user info in the session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role_name'] = $user['role_name'];

        // Redirect based on role
        if ($user['role_name'] === 'admin') {
            header('Location: admin_dashboard.php');
        } else {
            header('Location: creator_dashboard.php');
        }
        exit;
    } else {
        // Authentication failed
        $_SESSION['login_error'] = 'Invalid username or password.';
        header('Location: login.php');
        exit;
    }

} catch (PDOException $e) {
    // In a real app, log this error. For now, show a generic error.
    $_SESSION['login_error'] = 'An error occurred. Please try again later.';
    // error_log('Login failed: ' . $e->getMessage());
    header('Location: login.php');
    exit;
}
?>
