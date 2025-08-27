<?php
require_once __DIR__ . '/templates/header.php';

// Auth check
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
?>

<h1>Edit Survey</h1>
<p>The interface for editing an existing survey will be here.</p>
<p><em>(This will be implemented in a future step.)</em></p>
<br>
<a href="creator_dashboard.php">Back to Dashboard</a>

<?php
require_once __DIR__ . '/templates/footer.php';
?>
