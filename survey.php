<?php
require_once __DIR__ . '/core/database.php';

// --- Get Survey ID and Validate ---
$survey_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$survey_id) {
    // Can't show a themed page if we don't know the survey.
    // Include a minimal header/footer.
    require_once __DIR__ . '/templates/header.php';
    echo "<p>Invalid survey link.</p>";
    require_once __DIR__ . '/templates/footer.php';
    exit;
}

// --- Fetch Survey and Creator Info ---
$survey = null;
$question_count = 0;
try {
    // Fetch survey details and its creator's ID
    $stmt = $pdo->prepare("SELECT * FROM surveys WHERE id = ? AND status = 'published'");
    $stmt->execute([$survey_id]);
    $survey = $stmt->fetch();

    if (!$survey) {
        require_once __DIR__ . '/templates/header.php';
        echo "<p>This survey is not available or cannot be found.</p>";
        require_once __DIR__ . '/templates/footer.php';
        exit;
    }

    // Get the number of questions in the survey
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM questions WHERE survey_id = ?");
    $stmt->execute([$survey_id]);
    $question_count = $stmt->fetchColumn();

} catch (PDOException $e) {
    // error_log($e->getMessage());
    require_once __DIR__ . '/templates/header.php';
    echo "<p>An error occurred while loading the survey.</p>";
    require_once __DIR__ . '/templates/footer.php';
    exit;
}

// --- Theme Selection Logic ---
// This logic runs *before* the header is included.
$predefined_themes = [
    1 => 'default',
    2 => 'cosmic',
    3 => 'dark',
    4 => 'green',
    5 => 'blue'
];
$theme_name = 'default'; // Fallback theme
if (isset($survey['theme_id']) && isset($predefined_themes[$survey['theme_id']])) {
    $theme_name = $predefined_themes[$survey['theme_id']];
}
// The $theme_file variable is used by header.php
$theme_file = "public/css/themes/{$theme_name}.css";


// --- NOW we can include the header, which will be properly themed ---
require_once __DIR__ . '/templates/header.php';


// --- Credit Check and Reservation Logic ---
try {
    $pdo->beginTransaction();

    // Get a lock on the wallet row to prevent race conditions
    $stmt = $pdo->prepare("SELECT * FROM wallets WHERE user_id = ? FOR UPDATE");
    $stmt->execute([$survey['creator_id']]);
    $wallet = $stmt->fetch();

    // Check if the creator has enough credits
    if (!$wallet || $wallet['balance'] < $question_count) {
        $pdo->rollBack(); // Release the lock
        echo "<p>This survey is currently not accepting new responses. Please try again later.</p>";
        require_once __DIR__ . '/templates/footer.php';
        exit;
    }

    // Reserve the credits
    $new_balance = $wallet['balance'] - $question_count;
    $new_reserved_balance = $wallet['reserved_balance'] + $question_count;
    $stmt = $pdo->prepare("UPDATE wallets SET balance = ?, reserved_balance = ? WHERE user_id = ?");
    $stmt->execute([$new_balance, $new_reserved_balance, $survey['creator_id']]);

    // Create a respondent record to track this session
    $respondent_session_id = session_id(); // Use PHP session ID as a unique identifier
    $stmt = $pdo->prepare("INSERT INTO respondents (survey_id, session_identifier) VALUES (?, ?)");
    $stmt->execute([$survey_id, $respondent_session_id]);
    $respondent_id = $pdo->lastInsertId();

    $pdo->commit();

} catch (PDOException $e) {
    $pdo->rollBack();
    // error_log($e->getMessage());
    echo "<p>A server error occurred. Please try again in a few moments.</p>";
    require_once __DIR__ . '/templates/footer.php';
    exit;
}

// --- If Credit Check Passes, Fetch and Display Questions ---
$questions = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM questions WHERE survey_id = ? ORDER BY display_order ASC");
    $stmt->execute([$survey_id]);
    $questions = $stmt->fetchAll();
} catch (PDOException $e) {
    // error_log($e->getMessage());
    echo "<p>An error occurred while loading survey questions.</p>";
    require_once __DIR__ . '/templates/footer.php';
    exit;
}
?>

<h1><?= htmlspecialchars($survey['title']); ?></h1>
<p><?= nl2br(htmlspecialchars($survey['description'])); ?></p>
<hr>

<form action="handle_submit_survey.php" method="POST">
    <input type="hidden" name="survey_id" value="<?= $survey_id; ?>">
    <input type="hidden" name="respondent_id" value="<?= $respondent_id; ?>">
    <input type="hidden" name="question_count" value="<?= $question_count; ?>">

    <?php foreach ($questions as $index => $question): ?>
        <div class="question-block" style="margin-bottom: 20px;">
            <p>
                <strong><?= ($index + 1); ?>. <?= htmlspecialchars($question['question_text']); ?></strong>
                <?= $question['is_required'] ? '<span style="color: red;">*</span>' : ''; ?>
            </p>

            <?php // Render input based on question type
            $input_name = "answers[{$question['id']}]";
            if ($question['question_type'] === 'text'): ?>
                <input type="text" name="<?= $input_name; ?>" <?= $question['is_required'] ? 'required' : ''; ?>>
            <?php elseif ($question['question_type'] === 'textarea'): ?>
                <textarea name="<?= $input_name; ?>" rows="4" style="width: 80%;" <?= $question['is_required'] ? 'required' : ''; ?>></textarea>
            <?php elseif (in_array($question['question_type'], ['radio', 'checkbox', 'dropdown'])):
                // Fetch options for this question
                $opt_stmt = $pdo->prepare("SELECT * FROM question_options WHERE question_id = ? ORDER BY display_order ASC");
                $opt_stmt->execute([$question['id']]);
                $options = $opt_stmt->fetchAll();

                if ($question['question_type'] === 'dropdown'): ?>
                    <select name="<?= $input_name; ?>" <?= $question['is_required'] ? 'required' : ''; ?>>
                        <option value="">Select an option...</option>
                        <?php foreach ($options as $option): ?>
                            <option value="<?= $option['id']; ?>"><?= htmlspecialchars($option['option_text']); ?></option>
                        <?php endforeach; ?>
                    </select>
                <?php else: // Radio and Checkbox
                    $input_type = $question['question_type'];
                    $name_attr = ($input_type === 'checkbox') ? "{$input_name}[]" : $input_name;
                    foreach ($options as $option): ?>
                        <div>
                            <label>
                                <input type="<?= $input_type; ?>" name="<?= $name_attr; ?>" value="<?= $option['id']; ?>" <?= $question['is_required'] ? 'required' : ''; ?>>
                                <?= htmlspecialchars($option['option_text']); ?>
                            </label>
                        </div>
                    <?php endforeach;
                endif;
            endif;
            ?>
        </div>
    <?php endforeach; ?>

    <br>
    <button type="submit">Submit Survey</button>
</form>

<?php
require_once __DIR__ . '/templates/footer.php';
?>
