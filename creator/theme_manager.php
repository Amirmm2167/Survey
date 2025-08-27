<?php
require_once __DIR__ . '/../core/session.php';
require_once __DIR__ . '/../core/database.php';
require_once __DIR__ . '/../core/functions.php';

// --- Authorization Check ---
if (!isset($_SESSION['user_id'])) {
    redirect('../index.php');
}
$user_id = $_SESSION['user_id'];

// --- Fetch User's Plan and Theme Limit ---
$plan = get_user_plan($pdo, $user_id);

// If user's plan doesn't allow custom themes, deny access.
if (!$plan || $plan['custom_theme_limit'] <= 0) {
    echo "<p>Your current plan does not support custom themes.</p>";
    echo '<a href="index.php">Back to Dashboard</a>';
    require_once __DIR__ . '/../templates/footer.php';
    exit;
}

// --- Fetch User's Custom Themes ---
$custom_themes = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM custom_themes WHERE creator_id = ? ORDER BY created_at DESC");
    $stmt->execute([$user_id]);
    $custom_themes = $stmt->fetchAll();
} catch (PDOException $e) { die("Error fetching custom themes."); }

$theme_count = count($custom_themes);
$theme_limit = $plan['custom_theme_limit'];
$can_create_new = $theme_count < $theme_limit;

require_once __DIR__ . '/../templates/header.php';
?>

<h1>Theme Manager</h1>
<p>Create and manage your custom survey themes.</p>
<a href="<?= site_url('creator/'); ?>">Back to Dashboard</a>

<hr>

<!-- Create New Theme Section -->
<div>
    <h3>Create a New Theme</h3>
    <p>You have created <?= $theme_count; ?> out of <?= $theme_limit; ?> custom themes.</p>
    <?php if ($can_create_new): ?>
        <a href="<?= site_url('creator/create_custom_theme.php'); ?>" class="button-link"> + Create New Theme</a>
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
            <table>
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
                                <!-- Color swatches -->
                            </td>
                            <td>
                                <a href="<?= site_url('creator/edit_custom_theme.php?id=' . $theme['id']); ?>">Edit</a> |
                                <a href="#" class="delete-theme-btn" data-theme-id="<?= $theme['id']; ?>">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.delete-theme-btn').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            if (confirm('Are you sure you want to delete this theme?')) {
                const themeId = this.dataset.themeId;
                const formData = new FormData();
                formData.append('action', 'delete_theme');
                formData.append('theme_id', themeId);

                fetch('../api.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(result => {
                    if (result.status === 'success') {
                        location.reload();
                    } else {
                        alert('Error: ' + result.message);
                    }
                });
            }
        });
    });
});
</script>
<?php
require_once __DIR__ . '/../templates/footer.php';
?>
