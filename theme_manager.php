<?php
require_once __DIR__ . '/core/database.php';
require_once __DIR__ . '/templates/header.php';

// --- Authorization Check ---
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$user_id = $_SESSION['user_id'];

// --- Fetch User's Plan and Theme Limit ---
$plan = null;
try {
    $stmt = $pdo->prepare(
        "SELECT p.custom_theme_limit
         FROM plans p
         JOIN subscriptions s ON p.id = s.plan_id
         WHERE s.user_id = ? AND s.is_active = TRUE"
    );
    $stmt->execute([$user_id]);
    $plan = $stmt->fetch();
} catch (PDOException $e) {
    // error_log($e->getMessage());
    die("Error fetching user plan.");
}

// If user's plan doesn't allow custom themes, deny access.
if (!$plan || $plan['custom_theme_limit'] <= 0) {
    echo "<p>Your current plan does not support custom themes.</p>";
    echo '<a href="creator_dashboard.php">Back to Dashboard</a>';
    require_once __DIR__ . '/templates/footer.php';
    exit;
}

// --- Fetch User's Custom Themes ---
$custom_themes = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM custom_themes WHERE creator_id = ? ORDER BY created_at DESC");
    $stmt->execute([$user_id]);
    $custom_themes = $stmt->fetchAll();
} catch (PDOException $e) {
    // error_log($e->getMessage());
    die("Error fetching custom themes.");
}

$theme_count = count($custom_themes);
$theme_limit = $plan['custom_theme_limit'];
$can_create_new = $theme_count < $theme_limit;
?>

<h1>Theme Manager</h1>
<p>Create and manage your custom survey themes.</p>
<a href="creator_dashboard.php">Back to Dashboard</a>

<hr>

<!-- Create New Theme Section -->
<div>
    <h3>Create a New Theme</h3>
    <p>You have created <?= $theme_count; ?> out of <?= $theme_limit; ?> custom themes.</p>
    <?php if ($can_create_new): ?>
        <a href="create_custom_theme.php" class="button-link"> + Create New Theme</a>
    <?php else: ?>
        <p>You have reached your theme limit. Please upgrade your plan or delete an existing theme to create a new one.</p>
        <button disabled>+ Create New Theme</button>
    <?php endif; ?>
</div>

<hr>

<!-- List of Custom Themes -->
<div>
    <h3>Your Custom Themes</h3>
    <div class="themes-list">
        <?php if (empty($custom_themes)): ?>
            <p>You have not created any custom themes yet.</p>
        <?php else: ?>
            <table border="1" cellpadding="5" cellspacing="0" style="width:100%;">
                <thead>
                    <tr>
                        <th>Theme Name</th>
                        <th>Color Palette</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($custom_themes as $theme): ?>
                        <tr>
                            <td><?= htmlspecialchars($theme['theme_name']); ?></td>
                            <td style="display: flex; gap: 10px;">
                                <span style="background-color:<?= $theme['color_primary']; ?>; width:20px; height:20px; display:inline-block; border: 1px solid #ccc;" title="Primary: <?= $theme['color_primary']; ?>"></span>
                                <span style="background-color:<?= $theme['color_background']; ?>; width:20px; height:20px; display:inline-block; border: 1px solid #ccc;" title="Background: <?= $theme['color_background']; ?>"></span>
                                <span style="background-color:<?= $theme['color_text']; ?>; width:20px; height:20px; display:inline-block; border: 1px solid #ccc;" title="Text: <?= $theme['color_text']; ?>"></span>
                                <span style="background-color:<?= $theme['color_accent']; ?>; width:20px; height:20px; display:inline-block; border: 1px solid #ccc;" title="Accent: <?= $theme['color_accent']; ?>"></span>
                                <span style="background-color:<?= $theme['color_panel_bg']; ?>; width:20px; height:20px; display:inline-block; border: 1px solid #ccc;" title="Panel BG: <?= $theme['color_panel_bg']; ?>"></span>
                            </td>
                            <td>
                                <a href="edit_custom_theme.php?id=<?= $theme['id']; ?>">Edit</a> |
                                <a href="delete_custom_theme.php?id=<?= $theme['id']; ?>" onclick="return confirm('Are you sure?');">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<?php
require_once __DIR__ . '/templates/footer.php';
?>
