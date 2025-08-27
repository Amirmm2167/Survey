<?php
// core/database.php

/**
 * Creates and returns a PDO database connection.
 * Uses a static variable to ensure only one connection is made per request (Singleton pattern).
 * @return PDO|null The PDO connection object, or null on failure.
 */
function get_db_connection() {
    // A static variable to hold the connection instance
    static $pdo = null;

    // If the connection hasn't been made yet, create it.
    if ($pdo === null) {
        // Include the configuration file
        require_once __DIR__ . '/../config/config.php';

        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            $pdo = new PDO($dsn, DB_USERNAME, DB_PASSWORD, $options);
        } catch (PDOException $e) {
            // In a production environment, you would log this error and show a generic message.
            // For now, we die to make debugging clear.
            // In the future, this could redirect to 500.php
            die("Database connection failed: " . $e->getMessage());
        }
    }

    return $pdo;
}
?>
