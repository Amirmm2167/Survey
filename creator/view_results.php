<?php
require_once __DIR__ . '/../templates/header.php';
require_once __DIR__ . '/../core/database.php';
require_once __DIR__ . '/../core/session.php';

// --- Authorization Check ---
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}
$user_id = $_SESSION['user_id'];
$survey_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$survey_id) {
    echo "<p>No survey specified.</p>";
    require_once __DIR__ . '/templates/footer.php';
    exit;
}

// --- Fetch Survey and Verify Ownership ---
$survey = null;
try {
    $stmt = $pdo->prepare("SELECT * FROM surveys WHERE id = ? AND creator_id = ?");
    $stmt->execute([$survey_id, $user_id]);
    $survey = $stmt->fetch();

    if (!$survey) {
        echo "<p>Survey not found or you do not have permission to view it.</p>";
        require_once __DIR__ . '/templates/footer.php';
        exit;
    }
} catch (PDOException $e) {
    // error_log($e->getMessage());
    echo "<p>An error occurred while fetching the survey.</p>";
    require_once __DIR__ . '/templates/footer.php';
    exit;
}

// --- Fetch Aggregated Results ---
$results = [];
$questions = [];
$total_responses = 0;
try {
    // Get all questions for the header
    $stmt = $pdo->prepare("SELECT id, question_text FROM questions WHERE survey_id = ? ORDER BY display_order ASC");
    $stmt->execute([$survey_id]);
    $questions = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    // Get all responses
    $stmt = $pdo->prepare(
        "SELECT r.id as respondent_id, a.question_id, a.answer_text, GROUP_CONCAT(qo.option_text SEPARATOR ', ') as selected_options
         FROM respondents r
         JOIN answers a ON r.id = a.respondent_id
         LEFT JOIN answer_selections ans ON a.id = ans.answer_id
         LEFT JOIN question_options qo ON ans.selected_option_id = qo.id OR a.selected_option_id = qo.id
         WHERE r.survey_id = ? AND r.completed_at IS NOT NULL
         GROUP BY r.id, a.question_id
         ORDER BY r.id, a.question_id"
    );
    $stmt->execute([$survey_id]);
    $raw_results = $stmt->fetchAll();

    // Process the raw results into a respondent-centric array
    foreach ($raw_results as $row) {
        $respondent_id = $row['respondent_id'];
        $question_id = $row['question_id'];
        $results[$respondent_id][$question_id] = $row['answer_text'] ?? $row['selected_options'];
    }
    $total_responses = count($results);

} catch (PDOException $e) {
    // error_log($e->getMessage());
    echo "<p>An error occurred while fetching results.</p>";
}

?>

<h1>Results for "<?= htmlspecialchars($survey['title']); ?>"</h1>
<a href="index.php">Back to Dashboard</a>

<hr>

<h3>Summary</h3>
<p><strong>Total Responses:</strong> <?= $total_responses; ?></p>
<a href="../export_csv.php?survey_id=<?= $survey_id; ?>" style="display:inline-block; padding:10px 15px; background-color: #007bff; color: white; text-decoration: none; border-radius: 5px;">
    Export All as CSV
</a>

<hr>

<!-- Placeholder for Charts -->
<div class="charts-section">
    <h3>Charts</h3>
    <div style="padding: 50px; text-align: center; background-color: #f8f9fa; border: 1px dashed #ccc;">
        <p><strong>Coming Soon!</strong></p>
        <p>Visual charts and graphs of your survey data will be available here.</p>
    </div>
</div>

<hr>

<h3>Raw Data</h3>
<div style="overflow-x:auto;">
    <table border="1" cellpadding="5" cellspacing="0" style="width: 100%;">
        <thead>
            <tr>
                <th>Respondent ID</th>
                <?php foreach ($questions as $q_text): ?>
                    <th><?= htmlspecialchars($q_text); ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($results)): ?>
                <tr>
                    <td colspan="<?= count($questions) + 1; ?>">No responses yet.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($results as $respondent_id => $answers): ?>
                    <tr>
                        <td><?= $respondent_id; ?></td>
                        <?php foreach ($questions as $q_id => $q_text): ?>
                            <td><?= htmlspecialchars($answers[$q_id] ?? ''); ?></td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>


<?php
require_once __DIR__ . '/templates/footer.php';
?>
