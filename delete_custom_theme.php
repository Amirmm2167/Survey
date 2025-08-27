<?php
require_once __DIR__ . '/templates/header.php';

// Auth check
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$theme_id = $_GET['id'] ?? null;

// In a real implementation, you would have logic here to:
// 1. Verify the theme belongs to the logged-in user.
// 2. Delete the theme from the database.
// 3. Redirect back to the theme manager with a success message.
?>

<h1>Delete Custom Theme</h1>
<p>Logic to delete custom theme #<?= htmlspecialchars($theme_id); ?> would be here.</p>
<br>
<a href="theme_manager.php">Back to Theme Manager</a>

<?php
require_once __DIR__ . '/templates/footer.php';
?>
