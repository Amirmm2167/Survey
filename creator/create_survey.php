<?php
require_once __DIR__ . '/../core/session.php';
require_once __DIR__ . '/../core/functions.php';
if (!isset($_SESSION['user_id'])) { redirect(site_url()); }
require_once __DIR__ . '/../templates/header.php';
?>
<h1><?= trans('create_new_survey'); ?></h1>
<form action="<?= site_url('api.php'); ?>" method="POST" id="create-survey-form">
    <input type="hidden" name="action" value="create_survey">
    <div class="form-error" style="display: none;"></div>
    <fieldset>
        <legend><?= trans('survey_details'); ?></legend>
        <div><label for="title"><?= trans('survey_title'); ?></label><input type="text" id="title" name="title" required></div>
        <div><label for="description"><?= trans('description'); ?></label><textarea id="description" name="description" rows="4"></textarea></div>
        <div><label for="access_level"><?= trans('access_level'); ?></label><select id="access_level" name="access_level"><option value="public"><?= trans('access_public'); ?></option><option value="private_code"><?= trans('access_private'); ?></option></select></div>
    </fieldset>
    <br>
    <fieldset>
        <legend><?= trans('questions'); ?></legend>
        <div id="questions-container"></div>
        <br>
        <button type="button" id="add-question-btn">+ <?= trans('add_question'); ?></button>
    </fieldset>
    <br>
    <button type="submit"><?= trans('save_survey'); ?></button>
    <a href="<?= site_url('creator/'); ?>"><?= trans('cancel'); ?></a>
</form>
<script src="<?= site_url('public/js/survey-builder.js'); ?>"></script>
<script>
document.getElementById('create-survey-form').addEventListener('submit', function(e) {
    e.preventDefault();
    handleFormSubmit(this);
});
</script>
<?php require_once __DIR__ . '/../templates/footer.php'; ?>
