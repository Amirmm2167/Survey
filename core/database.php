<?php
// core/database.php

// Include the configuration file
require_once __DIR__ . '/../config/config.php';

$pdo = null;

try {
    // Create a new PDO instance
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    $pdo = new PDO($dsn, DB_USERNAME, DB_PASSWORD, $options);
} catch (PDOException $e) {
    // If connection fails, stop the script and show an error
    // In a production environment, you would log this error and show a generic message.
    die("Database connection failed: " . $e->getMessage());
}

// The $pdo object can now be used by any script that includes this file.
?>
