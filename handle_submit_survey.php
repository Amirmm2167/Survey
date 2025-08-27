<?php
require_once __DIR__ . '/core/session.php';
require_once __DIR__ . '/core/database.php';

// --- Authorization & Request Method Check ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php'); // Or some other appropriate default page
    exit;
}

// --- Input Validation ---
$survey_id = filter_input(INPUT_POST, 'survey_id', FILTER_VALIDATE_INT);
$respondent_id = filter_input(INPUT_POST, 'respondent_id', FILTER_VALIDATE_INT);
$question_count = filter_input(INPUT_POST, 'question_count', FILTER_VALIDATE_INT);
$answers_data = $_POST['answers'] ?? [];

if (!$survey_id || !$respondent_id || !$question_count) {
    // Basic check for required hidden fields
    die("Invalid submission data.");
}

// --- Database Transaction for submitting answers ---
try {
    $pdo->beginTransaction();

    // 1. Save answers to the database
    foreach ($answers_data as $question_id => $answer_value) {
        // Create the main answer record
        $stmt = $pdo->prepare("INSERT INTO answers (respondent_id, question_id, answer_text, selected_option_id) VALUES (?, ?, ?, ?)");

        // Handle different answer types
        if (is_array($answer_value)) { // Checkbox
            // For checkboxes, we create a primary answer record and then link selections
            $stmt->execute([$respondent_id, $question_id, null, null]);
            $answer_id = $pdo->lastInsertId();

            // Now insert each selected checkbox option into `answer_selections`
            foreach ($answer_value as $selected_option) {
                $sel_stmt = $pdo->prepare("INSERT INTO answer_selections (answer_id, selected_option_id) VALUES (?, ?)");
                $sel_stmt->execute([$answer_id, filter_var($selected_option, FILTER_VALIDATE_INT)]);
            }
        } elseif (is_numeric($answer_value)) { // Radio or Dropdown
             $stmt->execute([$respondent_id, $question_id, null, $answer_value]);
        } else { // Text or Textarea
            $stmt->execute([$respondent_id, $question_id, trim($answer_value), null]);
        }
    }

    // 2. Decrement the creator's reserved credit balance
    // First, get the creator's ID from the survey
    $stmt = $pdo->prepare("SELECT creator_id FROM surveys WHERE id = ?");
    $stmt->execute([$survey_id]);
    $creator_id = $stmt->fetchColumn();

    if ($creator_id) {
        $stmt = $pdo->prepare("UPDATE wallets SET reserved_balance = reserved_balance - ? WHERE user_id = ? AND reserved_balance >= ?");
        $stmt->execute([$question_count, $creator_id, $question_count]);
    }

    // 3. Mark the respondent record as complete
    $stmt = $pdo->prepare("UPDATE respondents SET completed_at = CURRENT_TIMESTAMP WHERE id = ?");
    $stmt->execute([$respondent_id]);

    // If all queries were successful, commit the transaction
    $pdo->commit();

    // Redirect to a thank you page
    header('Location: thank_you.php');
    exit;

} catch (PDOException $e) {
    $pdo->rollBack();
    // In a real app, log the error and show a user-friendly message
    // error_log('Survey submission failed: ' . $e->getMessage());
    die("An error occurred while submitting your survey. Please try again.");
}
?>
