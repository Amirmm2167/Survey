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
$survey_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$survey_id || !verify_survey_ownership($pdo, $survey_id, $user_id)) {
    // Handle error, maybe redirect with a message
    redirect(site_url('creator/'));
}

$survey = $pdo->query("SELECT * FROM surveys WHERE id = $survey_id")->fetch();
$custom_themes = $pdo->query("SELECT id, theme_name FROM custom_themes WHERE creator_id = $user_id")->fetchAll();
$predefined_themes = [1 => 'Default', 2 => 'Cosmic', 3 => 'Dark', 4 => 'Green', 5 => 'Blue'];
?>

<h1><?= trans('edit_survey'); ?>: <?= htmlspecialchars($survey['title']); ?></h1>
<a href="<?= site_url('creator/'); ?>"><?= trans('back_to_dashboard'); ?></a>

<hr>

<form action="<?= site_url('api.php'); ?>" method="POST" id="edit-survey-form">
    <input type="hidden" name="action" value="update_survey">
    <input type="hidden" name="survey_id" value="<?= $survey_id; ?>">
    <div class="form-error" style="display: none; color: red; margin-bottom: 10px;"></div>

    <div>
        <label for="title"><?= trans('survey_title'); ?></label>
        <input type="text" id="title" name="title" value="<?= htmlspecialchars($survey['title']); ?>" required>
    </div>
    <div>
        <label for="description"><?= trans('description'); ?></label>
        <textarea id="description" name="description" rows="4"><?= htmlspecialchars($survey['description']); ?></textarea>
    </div>
    <div>
        <label for="status"><?= trans('status'); ?></label>
        <select id="status" name="status">
            <option value="draft" <?= $survey['status'] == 'draft' ? 'selected' : ''; ?>><?= trans('status_draft'); ?></option>
            <option value="published" <?= $survey['status'] == 'published' ? 'selected' : ''; ?>><?= trans('status_published'); ?></option>
            <option value="closed" <?= $survey['status'] == 'closed' ? 'selected' : ''; ?>><?= trans('status_closed'); ?></option>
        </select>
    </div>
    <hr>
    <h3><?= trans('theme_settings'); ?></h3>
    <div>
        <label for="theme_selection"><?= trans('survey_theme'); ?></label>
        <select id="theme_selection" name="theme_selection">
            <option value="none"><?= trans('theme_none'); ?></option>
            <optgroup label="<?= trans('predefined_themes'); ?>">
                <?php foreach ($predefined_themes as $id => $name): ?>
                    <option value="predefined-<?= $id; ?>" <?= $survey['theme_id'] == $id ? 'selected' : ''; ?>><?= $name; ?></option>
                <?php endforeach; ?>
            </optgroup>
            <?php if (!empty($custom_themes)): ?>
            <optgroup label="<?= trans('custom_themes'); ?>">
                 <?php foreach ($custom_themes as $theme): ?>
                    <option value="custom-<?= $theme['id']; ?>" <?= $survey['custom_theme_id'] == $theme['id'] ? 'selected' : ''; ?>><?= htmlspecialchars($theme['theme_name']); ?></option>
                <?php endforeach; ?>
            </optgroup>
            <?php endif; ?>
        </select>
    </div>
    <hr>
    <button type="submit"><?= trans('save_changes'); ?></button>
</form>

<script>
document.getElementById('edit-survey-form').addEventListener('submit', function(e) {
    e.preventDefault();
    handleFormSubmit(this);
});
</script>

<?php
require_once __DIR__ . '/../templates/footer.php';
?>
