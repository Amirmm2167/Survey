<?php
require_once __DIR__ . '/core/session.php';
require_once __DIR__ . '/core/database.php';

// --- Authorization & Request Method Check ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$user_id = $_SESSION['user_id'];
$survey_id = filter_input(INPUT_POST, 'survey_id', FILTER_VALIDATE_INT);

if (!$survey_id) {
    // In a real app, use flash messages for errors
    header('Location: creator_dashboard.php');
    exit;
}

// --- Verify survey ownership before proceeding ---
try {
    $stmt = $pdo->prepare("SELECT id FROM surveys WHERE id = ? AND creator_id = ?");
    $stmt->execute([$survey_id, $user_id]);
    if (!$stmt->fetch()) {
        // User does not own this survey
        header('Location: creator_dashboard.php');
        exit;
    }
} catch (PDOException $e) {
    die("Error verifying survey ownership.");
}

// --- Input Validation ---
$title = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$status = $_POST['status'] ?? 'draft';
$theme_selection = $_POST['theme_selection'] ?? 'none';

if (empty($title) || !in_array($status, ['draft', 'published', 'closed'])) {
    // Redirect back with an error
    header("Location: edit_survey.php?id={$survey_id}");
    exit;
}

// --- Parse Theme Selection ---
$theme_id = null;
$custom_theme_id = null;

if (strpos($theme_selection, 'predefined-') === 0) {
    $theme_id = (int) str_replace('predefined-', '', $theme_selection);
} elseif (strpos($theme_selection, 'custom-') === 0) {
    $custom_theme_id = (int) str_replace('custom-', '', $theme_selection);
}

// --- Database Update ---
try {
    $sql = "UPDATE surveys SET
                title = :title,
                description = :description,
                status = :status,
                theme_id = :theme_id,
                custom_theme_id = :custom_theme_id
            WHERE id = :survey_id AND creator_id = :creator_id";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ':title' => $title,
        ':description' => $description,
        ':status' => $status,
        ':theme_id' => $theme_id,
        ':custom_theme_id' => $custom_theme_id,
        ':survey_id' => $survey_id,
        ':creator_id' => $user_id
    ]);

    // Set success message and redirect
    // $_SESSION['success'] = "Survey '{$title}' updated successfully!";
    header('Location: creator_dashboard.php');
    exit;

} catch (PDOException $e) {
    // In a real app, log the error and set a flash message
    // error_log("Survey update failed: " . $e->getMessage());
    header("Location: edit_survey.php?id={$survey_id}");
    exit;
}
?>
