<?php
require_once __DIR__ . '/core/session.php';
require_once __DIR__ . '/core/database.php';

// --- Authorization Check ---
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$user_id = $_SESSION['user_id'];
$survey_id = filter_input(INPUT_GET, 'survey_id', FILTER_VALIDATE_INT);

if (!$survey_id) {
    die("No survey specified.");
}

// --- Fetch Survey and Verify Ownership ---
try {
    $stmt = $pdo->prepare("SELECT title FROM surveys WHERE id = ? AND creator_id = ?");
    $stmt->execute([$survey_id, $user_id]);
    $survey = $stmt->fetch();
    if (!$survey) {
        die("Survey not found or you do not have permission to export it.");
    }
} catch (PDOException $e) {
    // error_log($e->getMessage());
    die("An error occurred while fetching the survey.");
}

// --- Set HTTP Headers for CSV Download ---
$filename = "survey_" . $survey_id . "_results_" . date('Y-m-d') . ".csv";
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// --- Fetch Data (similar to view_results.php) ---
$results = [];
$questions = [];
try {
    $stmt = $pdo->prepare("SELECT id, question_text FROM questions WHERE survey_id = ? ORDER BY display_order ASC");
    $stmt->execute([$survey_id]);
    $questions = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

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

    foreach ($raw_results as $row) {
        $results[$row['respondent_id']][$row['question_id']] = $row['answer_text'] ?? $row['selected_options'];
    }
} catch (PDOException $e) {
    // error_log($e->getMessage());
    die("An error occurred while fetching results for CSV export.");
}

// --- Write to CSV ---
// Open the output stream
$output = fopen('php://output', 'w');

// Write the header row
$header = array_merge(['Respondent ID'], array_values($questions));
fputcsv($output, $header);

// Write the data rows
foreach ($results as $respondent_id => $answers) {
    $row = [$respondent_id];
    foreach ($questions as $q_id => $q_text) {
        $row[] = $answers[$q_id] ?? '';
    }
    fputcsv($output, $row);
}

fclose($output);
exit;
?>
