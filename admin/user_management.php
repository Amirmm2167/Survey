<?php
require_once __DIR__ . '/../core/session.php';
require_once __DIR__ . '/../core/functions.php';
$pdo = get_db_connection();
if (!isset($_SESSION['user_id']) || $_SESSION['role_name'] !== 'admin') { redirect(site_url()); }
$users = $pdo->query("SELECT u.id, u.username, u.email, u.phone_number, u.created_at, r.name as role_name, p.name as plan_name FROM users u JOIN roles r ON u.role_id = r.id LEFT JOIN subscriptions s ON u.id = s.user_id LEFT JOIN plans p ON s.plan_id = p.id ORDER BY u.created_at DESC")->fetchAll();
$plans = $pdo->query("SELECT id, name, level FROM plans ORDER BY level ASC")->fetchAll();
$creator_role_id = $pdo->query("SELECT id FROM roles WHERE name = 'creator'")->fetchColumn();
$page_content_file = __DIR__ . '/user_management_content.php';
require_once __DIR__ . '/layout.php';
?>
