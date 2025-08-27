<?php
// core/session.php

// Start a new session or resume the existing one
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
