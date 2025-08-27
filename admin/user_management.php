<?php
require_once __DIR__ . '/../core/session.php';
require_once __DIR__ . '/../core/functions.php';

$pdo = get_db_connection();

// --- Authorization Check ---
if (!isset($_SESSION['user_id']) || $_SESSION['role_name'] !== 'admin') {
    redirect(site_url());
}

// This file now acts as a "controller" for user management.
// It will fetch all necessary data and then include the main layout file.

// --- Fetch all users for the list ---
$users = [];
try {
    $stmt = $pdo->query(
        "SELECT u.id, u.username, u.email, u.phone_number, u.created_at, r.name as role_name, p.name as plan_name
         FROM users u
         JOIN roles r ON u.role_id = r.id
         LEFT JOIN subscriptions s ON u.id = s.user_id
         LEFT JOIN plans p ON s.plan_id = p.id
         ORDER BY u.created_at DESC"
    );
    $users = $stmt->fetchAll();
} catch (PDOException $e) { die("Error fetching users: " . $e->getMessage()); }

// --- Data for the 'Create User' form ---
$plans = [];
try {
    $plans = $pdo->query("SELECT id, name, level FROM plans ORDER BY level ASC")->fetchAll();
} catch (PDOException $e) { die("Error fetching plans: " . $e->getMessage()); }

$creator_role_id = null;
try {
    $stmt = $pdo->prepare("SELECT id FROM roles WHERE name = 'creator'");
    $stmt->execute();
    $creator_role_id = $stmt->fetchColumn();
} catch (PDOException $e) { die("Error fetching role id: " . $e->getMessage()); }


// Define the content file to be included by the layout
$page_content_file = __DIR__ . '/user_management_content.php';

// Include the main admin layout
require_once __DIR__ . '/layout.php';
?>
