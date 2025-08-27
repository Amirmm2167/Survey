<?php
require_once __DIR__ . '/core/session.php';
require_once __DIR__ . '/core/database.php';

// --- Authorization & Request Method Check ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$user_id = $_SESSION['user_id'];

// --- Re-verify user's permission to create themes ---
$plan = null;
$theme_count = 0;
try {
    $stmt = $pdo->prepare(
        "SELECT p.level, p.custom_theme_limit
         FROM plans p JOIN subscriptions s ON p.id = s.plan_id
         WHERE s.user_id = ? AND s.is_active = TRUE"
    );
    $stmt->execute([$user_id]);
    $plan = $stmt->fetch();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM custom_themes WHERE creator_id = ?");
    $stmt->execute([$user_id]);
    $theme_count = $stmt->fetchColumn();
} catch (PDOException $e) {
    // In a real app, you'd set a session flash message and log the error
    die("Error fetching user data.");
}

if (!$plan || $plan['custom_theme_limit'] <= 0 || $theme_count >= $plan['custom_theme_limit']) {
    // Redirect with an error if they try to bypass the UI limitations
    // $_SESSION['error'] = "You do not have permission to create a new theme.";
    header('Location: theme_manager.php');
    exit;
}

// --- Input Validation & Sanitization ---
$theme_name = trim($_POST['theme_name'] ?? '');
if (empty($theme_name)) {
    // $_SESSION['error'] = "Theme name is required.";
    header('Location: create_custom_theme.php');
    exit;
}

// Validate colors are valid hex codes
$colors = [
    'color_primary'   => $_POST['color_primary'] ?? '#77b9df',
    'color_background'=> $_POST['color_background'] ?? '#ffffff',
    'color_text'      => $_POST['color_text'] ?? '#333333',
    'color_accent'    => $_POST['color_accent'] ?? '#ff7452',
    'color_panel_bg'  => $_POST['color_panel_bg'] ?? '#f8f9fa'
];
foreach ($colors as $key => $value) {
    if (!preg_match('/^#[a-f0-9]{6}$/i', $value)) {
        // $_SESSION['error'] = "Invalid color format for {$key}.";
        header('Location: create_custom_theme.php');
        exit;
    }
}

// Handle font for Level 5 users
$font_family = null;
if ($plan['level'] == 5 && !empty($_POST['font_family'])) {
    // A real app should validate this against a whitelist of fonts
    $font_family = trim($_POST['font_family']);
}

// --- Database Insertion ---
try {
    $sql = "INSERT INTO custom_themes (creator_id, theme_name, color_primary, color_background, color_text, color_accent, color_panel_bg, font_family)
            VALUES (:creator_id, :theme_name, :color_primary, :color_background, :color_text, :color_accent, :color_panel_bg, :font_family)";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ':creator_id' => $user_id,
        ':theme_name' => $theme_name,
        ':color_primary' => $colors['color_primary'],
        ':color_background' => $colors['color_background'],
        ':color_text' => $colors['color_text'],
        ':color_accent' => $colors['color_accent'],
        ':color_panel_bg' => $colors['color_panel_bg'],
        ':font_family' => $font_family
    ]);

    // Set success message and redirect
    // $_SESSION['success'] = "Theme '{$theme_name}' created successfully!";
    header('Location: theme_manager.php');
    exit;

} catch (PDOException $e) {
    // In a real app, log the error and set a flash message
    // error_log("Theme creation failed: " . $e->getMessage());
    // $_SESSION['error'] = "An error occurred while saving the theme.";
    header('Location: create_custom_theme.php');
    exit;
}
?>
