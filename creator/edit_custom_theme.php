<?php
require_once __DIR__ . '/templates/header.php';

// Auth check
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$theme_id = $_GET['id'] ?? null;
?>

<h1>Edit Custom Theme #<?= htmlspecialchars($theme_id) ?></h1>
<p>The interface for editing an existing custom theme will be here.</p>
<br>
<a href="theme_manager.php">Back to Theme Manager</a>

<?php
require_once __DIR__ . '/templates/footer.php';
?>
