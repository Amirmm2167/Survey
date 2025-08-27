<?php
require_once __DIR__ . '/../core/session.php';
require_once __DIR__ . '/../core/functions.php';
$pdo = get_db_connection();
if (!isset($_SESSION['user_id'])) { redirect(site_url()); }
$user_id = $_SESSION['user_id'];
$plan = get_user_plan($pdo, $user_id);
$stmt = $pdo->prepare("SELECT COUNT(*) FROM custom_themes WHERE creator_id = ?");
$stmt->execute([$user_id]);
$theme_count = $stmt->fetchColumn();
if (!$plan || $plan['custom_theme_limit'] <= 0 || $theme_count >= $plan['custom_theme_limit']) {
    redirect(site_url('creator/theme_manager.php'));
}
$is_level_5 = $plan['level'] == 5;
$google_fonts = ['Roboto', 'Open Sans', 'Lato', 'Montserrat', 'Oswald'];
require_once __DIR__ . '/../templates/header.php';
?>
<h1><?= trans('create_a_new_custom_theme'); ?></h1>
<form action="<?= site_url('api.php'); ?>" method="POST" id="create-theme-form">
    <input type="hidden" name="action" value="save_theme">
    <div class="form-error" style="display: none;"></div>
    <div><label for="theme_name"><?= trans('theme_name'); ?></label><input type="text" id="theme_name" name="theme_name" required></div>
    <h4><?= trans('colors'); ?></h4>
    <table>
        <tr><td><label for="color_primary"><?= trans('color_primary'); ?></label></td><td><input type="color" name="color_primary" value="#77b9df"></td></tr>
        <tr><td><label for="color_background"><?= trans('color_background'); ?></label></td><td><input type="color" name="color_background" value="#ffffff"></td></tr>
        <tr><td><label for="color_text"><?= trans('color_text'); ?></label></td><td><input type="color" name="color_text" value="#333333"></td></tr>
    </table>
    <?php if ($is_level_5): ?>
        <h4><?= trans('font'); ?></h4>
        <div><label for="font_family"><?= trans('font_family'); ?></label><select id="font_family" name="font_family"><option value=""><?= trans('font_default'); ?></option><?php foreach($google_fonts as $font): ?><option value="<?= $font; ?>"><?= $font; ?></option><?php endforeach; ?></select></div>
    <?php endif; ?>
    <hr>
    <button type="submit"><?= trans('save_theme'); ?></button>
    <a href="<?= site_url('creator/theme_manager.php'); ?>"><?= trans('cancel'); ?></a>
</form>
<script>
document.getElementById('create-theme-form').addEventListener('submit', function(e) {
    e.preventDefault();
    handleFormSubmit(this);
});
</script>
<?php require_once __DIR__ . '/../templates/footer.php'; ?>
