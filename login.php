<?php
// login.php
// This is the main login page for the user.

// We need to start the session to access session variables
require_once __DIR__ . '/core/session.php';

// If the user is already logged in, redirect them away from the login page
if (isset($_SESSION['user_id'])) {
    // Redirect to a dashboard page if it exists, otherwise to a generic home page
    // For now, let's assume we have a creator_dashboard.php
    header('Location: creator_dashboard.php');
    exit;
}

// Include the header template
require_once __DIR__ . '/templates/header.php';
?>

<h1><?= trans('login_welcome'); ?></h1>

<?php
// Display error message if it exists
if (isset($_SESSION['login_error'])) {
    echo '<p class="error">' . $_SESSION['login_error'] . '</p>';
    // Unset the error message so it doesn't show again
    unset($_SESSION['login_error']);
}
?>

<form action="handle_login.php" method="POST">
    <div>
        <label for="username"><?= trans('username'); ?></label><br>
        <input type="text" id="username" name="username" required>
    </div>
    <br>
    <div>
        <label for="password"><?= trans('password'); ?></label><br>
        <input type="password" id="password" name="password" required>
    </div>
    <br>
    <div>
        <button type="submit"><?= trans('login_button'); ?></button>
    </div>
</form>

<p>
    <a href="?lang=en">English</a> | <a href="?lang=fa">فارسی</a>
</p>

<?php
// Include the footer template
require_once __DIR__ . '/templates/footer.php';
?>
