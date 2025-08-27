<?php
require_once __DIR__ . '/templates/header.php';
require_once __DIR__ . '/core/database.php';

// Auth check
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$survey_id = $_GET['id'] ?? null;

// In a real implementation, you would have logic here to:
// 1. Verify the survey belongs to the logged-in user.
// 2. Delete the survey and all its related questions and answers from the database.
// 3. Redirect back to the dashboard with a success message.

?>

<h1>Delete Survey</h1>
<p>Logic to delete survey #<?= htmlspecialchars($survey_id); ?> would be here.</p>
<p><em>(This will be implemented in a future step.)</em></p>
<br>
<a href="creator_dashboard.php">Back to Dashboard</a>

<?php
require_once __DIR__ . '/templates/footer.php';
?>
