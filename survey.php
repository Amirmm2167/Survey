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
    if (!$survey) { redirect(site_url('404.php')); }
    $question_count = $pdo->query("SELECT COUNT(*) FROM questions WHERE survey_id = {$survey['id']}")->fetchColumn();
} catch (PDOException $e) { redirect(site_url('500.php')); }


// --- Credit Check and Reservation Logic ---
try {
    $pdo->beginTransaction();
    $stmt = $pdo->prepare("SELECT * FROM wallets WHERE user_id = ? FOR UPDATE");
    $stmt->execute([$survey['creator_id']]);
    $wallet = $stmt->fetch();

    if (!$wallet || $wallet['balance'] < $question_count) {
        $pdo->rollBack();
        require_once __DIR__ . '/templates/header.php';
        echo "<p>" . trans('survey_no_responses') . "</p>";
        require_once __DIR__ . '/templates/footer.php';
        exit;
    }

    $new_balance = $wallet['balance'] - $question_count;
    $new_reserved = $wallet['reserved_balance'] + $question_count;
    $stmt = $pdo->prepare("UPDATE wallets SET balance = ?, reserved_balance = ? WHERE user_id = ?");
    $stmt->execute([$new_balance, $new_reserved, $survey['creator_id']]);

    $respondent_session_id = session_id();
    $stmt = $pdo->prepare("INSERT INTO respondents (survey_id, session_identifier) VALUES (?, ?)");
    $stmt->execute([$survey['id'], $respondent_session_id]);
    $respondent_id = $pdo->lastInsertId();
    $pdo->commit();
} catch (PDOException $e) {
    $pdo->rollBack();
    redirect(site_url('500.php'));
}

// --- Theme Selection Logic ---
$theme_file = 'public/css/themes/default.css'; // Fallback
// ... more theme logic will go here ...

// --- Fetch Questions ---
$questions = $pdo->query("SELECT * FROM questions WHERE survey_id = {$survey['id']} ORDER BY display_order ASC")->fetchAll();

require_once __DIR__ . '/templates/header.php';
?>

<h1><?= htmlspecialchars($survey['title']); ?></h1>
<p><?= nl2br(htmlspecialchars($survey['description'])); ?></p>
<hr>
<form action="<?= site_url('api.php'); ?>" method="POST" id="survey-form">
    <input type="hidden" name="action" value="submit_survey">
    <input type="hidden" name="survey_id" value="<?= $survey['id']; ?>">
    <input type="hidden" name="respondent_id" value="<?= $respondent_id; ?>">
    <input type="hidden" name="question_count" value="<?= $question_count; ?>">

    <?php foreach ($questions as $index => $question): ?>
        <div class="question-block" style="margin-bottom: 20px;">
            <p><strong><?= ($index + 1); ?>. <?= htmlspecialchars($question['question_text']); ?></strong></p>
            <?php
            $input_name = "answers[{$question['id']}]";
            if ($question['question_type'] === 'text') {
                echo "<input type='text' name='{$input_name}'>";
            } elseif ($question['question_type'] === 'textarea') {
                echo "<textarea name='{$input_name}' rows='4'></textarea>";
            } elseif (in_array($question['question_type'], ['radio', 'checkbox', 'dropdown'])) {
                $options = $pdo->query("SELECT * FROM question_options WHERE question_id = {$question['id']} ORDER BY display_order ASC")->fetchAll();
                if ($question['question_type'] === 'dropdown') {
                    echo "<select name='{$input_name}'><option value=''>Select...</option>";
                    foreach ($options as $option) {
                        echo "<option value='{$option['id']}'>" . htmlspecialchars($option['option_text']) . "</option>";
                    }
                    echo "</select>";
                } else {
                    foreach ($options as $option) {
                        $type = $question['question_type'];
                        $name = ($type === 'checkbox') ? "{$input_name}[]" : $input_name;
                        echo "<div><label><input type='{$type}' name='{$name}' value='{$option['id']}'> " . htmlspecialchars($option['option_text']) . "</label></div>";
                    }
                }
            }
            ?>
        </div>
    <?php endforeach; ?>

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
