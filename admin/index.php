<?php
require_once __DIR__ . '/../core/session.php';
require_once __DIR__ . '/../core/functions.php';

$pdo = get_db_connection();

// --- Authorization Check ---
if (!isset($_SESSION['user_id']) || $_SESSION['role_name'] !== 'admin') {
    redirect(site_url());
}

// --- Fetch data for display ---
$stats = [
    'total_users' => get_total_user_count($pdo),
    'total_surveys' => get_total_survey_count($pdo),
    'total_credits_used' => get_total_credits_used($pdo),
    'total_credits_available' => get_total_credits_available($pdo),
    'total_credits_bought' => get_total_credits_bought($pdo),
    'users_by_tier' => get_user_count_by_tier($pdo),
    'top_users_by_credit' => get_users_with_most_used_credits($pdo),
    'top_surveys_by_usage' => get_surveys_with_highest_credit_usage($pdo)
];

// Define the content file to be included by the layout
$page_content_file = __DIR__ . '/dashboard_content.php';

// Include the main admin layout
require_once __DIR__ . '/layout.php';
?>
