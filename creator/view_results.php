<?php
require_once __DIR__ . '/../core/session.php';
require_once __DIR__ . '/../core/functions.php';

// Get DB connection
$pdo = get_db_connection();

// Auth & Data Fetching
if (!isset($_SESSION['user_id'])) { redirect(site_url()); }
$user_id = $_SESSION['user_id'];
$survey_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$survey_id || !verify_survey_ownership($pdo, $survey_id, $user_id)) {
    redirect(site_url('creator/'));
}
$survey = $pdo->query("SELECT * FROM surveys WHERE id = $survey_id")->fetch();

// Fetch results logic...
$results = [];
$questions = [];
$total_responses = 0;
try {
    $stmt = $pdo->prepare("SELECT id, question_text FROM questions WHERE survey_id = ? ORDER BY display_order ASC");
    $stmt->execute([$survey_id]);
    $questions = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    $stmt = $pdo->prepare(
        "SELECT r.id as respondent_id, a.question_id, a.answer_text, GROUP_CONCAT(qo.option_text SEPARATOR ', ') as selected_options
         FROM respondents r JOIN answers a ON r.id = a.respondent_id
         LEFT JOIN answer_selections ans ON a.id = ans.answer_id
         LEFT JOIN question_options qo ON ans.selected_option_id = qo.id OR a.selected_option_id = qo.id
         WHERE r.survey_id = ? AND r.completed_at IS NOT NULL
         GROUP BY r.id, a.question_id ORDER BY r.id, a.question_id"
    );
    $stmt->execute([$survey_id]);
    $raw_results = $stmt->fetchAll();
    foreach ($raw_results as $row) {
        $results[$row['respondent_id']][$row['question_id']] = $row['answer_text'] ?? $row['selected_options'];
    }
    $total_responses = count($results);
} catch (PDOException $e) { die("Error fetching results."); }

require_once __DIR__ . '/../templates/header.php';
?>

<h1><?= str_replace('{title}', htmlspecialchars($survey['title']), trans('results_for')); ?></h1>
<a href="<?= site_url('creator/'); ?>"><?= trans('back_to_dashboard'); ?></a>
<hr>
<h3><?= trans('summary'); ?></h3>
<p><strong><?= trans('total_responses'); ?>:</strong> <?= $total_responses; ?></p>
<a href="<?= site_url('export_csv.php?survey_id=' . $survey_id); ?>" class="button-link"><?= trans('export_csv'); ?></a>
<hr>
<div class="charts-section">
    <h3><?= trans('charts'); ?></h3>
    <div style="padding: 50px; text-align: center; background-color: #f8f9fa; border: 1px dashed #ccc;">
        <p><strong><?= trans('coming_soon'); ?></strong></p>
        <p><?= trans('charts_desc'); ?></p>
    </div>
</div>
<hr>
<h3><?= trans('raw_data'); ?></h3>
<div style="overflow-x:auto;">
    <table>
        <thead>
            <tr>
                <th><?= trans('respondent_id'); ?></th>
                <?php foreach ($questions as $q_text): ?>
                    <th><?= htmlspecialchars($q_text); ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($results)): ?>
                <tr>
                    <td colspan="<?= count($questions) + 1; ?>"><?= trans('no_responses_yet'); ?></td>
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
require_once __DIR__ . '/../templates/footer.php';
?>
