<?php
require_once __DIR__ . '/core/functions.php';

// Get DB connection
$pdo = get_db_connection();

// --- Get Survey UID and Validate ---
$survey_uid = filter_input(INPUT_GET, 'uid', FILTER_SANITIZE_STRING);
if (!$survey_uid || !preg_match('/^[a-f0-9]{16}$/', $survey_uid)) {
    redirect(site_url('404.php'));
}

// --- Fetch Survey and Creator Info ---
$survey = null;
try {
    $stmt = $pdo->prepare("SELECT * FROM surveys WHERE unique_id = ? AND status = 'published'");
    $stmt->execute([$survey_uid]);
    $survey = $stmt->fetch();
    if (!$survey) {
        redirect(site_url('404.php'));
    }
    $question_count = $pdo->query("SELECT COUNT(*) FROM questions WHERE survey_id = {$survey['id']}")->fetchColumn();
} catch (PDOException $e) {
    redirect(site_url('500.php'));
}

// --- Theme Selection Logic ---
// ... (theme logic as before)
$theme_file = "public/css/themes/default.css"; // Placeholder

// --- NOW we can include the header ---
require_once __DIR__ . '/templates/header.php';

// --- Credit Check and Reservation (if survey is not free) ---
// ...

?>

<h1><?= htmlspecialchars($survey['title']); ?></h1>
<p><?= nl2br(htmlspecialchars($survey['description'])); ?></p>
<hr>
<form action="<?= site_url('api.php'); ?>" method="POST" id="survey-form">
    <input type="hidden" name="action" value="submit_survey">
    <input type="hidden" name="survey_id" value="<?= $survey['id']; ?>">

    <!-- Questions Loop -->

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
