<?php
require_once __DIR__ . '/../core/session.php';
require_once __DIR__ . '/../core/functions.php';

// Get DB connection
$pdo = get_db_connection();

// --- Authorization Check ---
if (!isset($_SESSION['user_id'])) {
    redirect(site_url());
}
$user_id = $_SESSION['user_id'];
$plan = get_user_plan($pdo, $user_id);

if (!$plan || $plan['custom_theme_limit'] <= 0) {
    redirect(site_url('creator/'));
}

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

<h1><?= trans('theme_manager'); ?></h1>
<p><?= trans('theme_manager_desc'); ?></p>
<a href="<?= site_url('creator/'); ?>"><?= trans('back_to_dashboard'); ?></a>
<hr>
<div>
    <h3><?= trans('create_new_theme'); ?></h3>
    <p><?= str_replace(['{count}', '{limit}'], [$theme_count, $theme_limit], trans('themes_created')); ?></p>
    <?php if ($can_create_new): ?>
        <a href="<?= site_url('creator/create_custom_theme.php'); ?>" class="button-link">+ <?= trans('create_new_theme'); ?></a>
    <?php else: ?>
        <p><?= trans('theme_limit_reached'); ?></p>
        <button disabled>+ <?= trans('create_new_theme'); ?></button>
    <?php endif; ?>
</div>
<hr>
<div>
    <h3><?= trans('your_custom_themes'); ?></h3>
    <div class="themes-list">
        <?php if (empty($custom_themes)): ?>
            <p><?= trans('no_themes_found'); ?></p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th><?= trans('theme_name'); ?></th>
                        <th><?= trans('color_palette'); ?></th>
                        <th><?= trans('actions'); ?></th>
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
                                <a href="<?= site_url('creator/edit_custom_theme.php?id=' . $theme['id']); ?>"><?= trans('edit'); ?></a> |
                                <a href="#" class="delete-theme-btn" data-theme-id="<?= $theme['id']; ?>"><?= trans('delete'); ?></a>
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
            if (confirm('<?= trans('confirm_delete'); ?>')) {
                const themeId = this.dataset.themeId;
                const formData = new FormData();
                formData.append('action', 'delete_theme');
                formData.append('theme_id', themeId);
                fetch('<?= site_url('api.php'); ?>', {
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
