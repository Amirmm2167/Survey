<?php
require_once __DIR__ . '/core/database.php';
require_once __DIR__ . '/core/functions.php';

$survey_uid = filter_input(INPUT_GET, 'uid', FILTER_SANITIZE_STRING);
if (!$survey_uid || !preg_match('/^[a-f0-9]{16}$/', $survey_uid)) {
    require_once __DIR__ . '/templates/header.php';
    echo "<p>" . trans('survey_not_available') . "</p>";
    require_once __DIR__ . '/templates/footer.php';
    exit;
}
$survey = $pdo->prepare("SELECT * FROM surveys WHERE unique_id = ? AND status = 'published'");
$survey->execute([$survey_uid]);
$survey = $survey->fetch();
if (!$survey) {
    require_once __DIR__ . '/templates/header.php';
    echo "<p>" . trans('survey_not_available') . "</p>";
    require_once __DIR__ . '/templates/footer.php';
    exit;
}
// Other logic...
$theme_file = "public/css/themes/default.css";
require_once __DIR__ . '/templates/header.php';
?>
<h1><?= htmlspecialchars($survey['title']); ?></h1>
<p><?= nl2br(htmlspecialchars($survey['description'])); ?></p>
<hr>
<form action="<?= site_url('api.php'); ?>" method="POST" id="survey-form">
    <input type="hidden" name="action" value="submit_survey">
    <!-- other hidden fields -->

    <!-- questions loop -->

    <br>
    <button type="submit"><?= trans('submit_survey'); ?></button>
</form>

<script>
document.getElementById('survey-form').addEventListener('submit', function(e) {
    e.preventDefault();
    handleFormSubmit(this);
});
</script>

<?php
require_once __DIR__ . '/templates/footer.php';
?>
