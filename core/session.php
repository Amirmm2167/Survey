<?php
// core/session.php

// Start a new session or resume the existing one
if (session_status() == PHP_SESSION_NONE) {
    // Set session cookie parameters for better security if needed
    // session_set_cookie_params(['lifetime' => 3600, 'httponly' => true]);
    session_start();
}
?>
