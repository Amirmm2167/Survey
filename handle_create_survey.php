<?php
require_once __DIR__ . '/core/session.php';
require_once __DIR__ . '/core/database.php';

// --- Authorization & Request Method Check ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// --- Input Validation ---
$creator_id = $_SESSION['user_id'];
$title = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$access_level = $_POST['access_level'] ?? 'public';
$access_code = ($access_level === 'private_code') ? trim($_POST['access_code'] ?? '') : null;
$questions = $_POST['questions'] ?? [];

// Basic validation
if (empty($title) || empty($questions)) {
    // In a real app, you'd set a session flash message with the error
    // $_SESSION['error'] = 'Title and at least one question are required.';
    header('Location: create_survey.php');
    exit;
}
if ($access_level === 'private_code' && empty($access_code)) {
    // $_SESSION['error'] = 'Access code is required for private surveys.';
    header('Location: create_survey.php');
    exit;
}


// --- Database Transaction ---
try {
    $pdo->beginTransaction();

    // 1. Insert into `surveys` table
    $stmt = $pdo->prepare(
        "INSERT INTO surveys (creator_id, title, description, access_level, access_code, status)
         VALUES (?, ?, ?, ?, ?, 'draft')"
    );
    $stmt->execute([$creator_id, $title, $description, $access_level, $access_code]);
    $survey_id = $pdo->lastInsertId();

    // 2. Loop through and insert questions and options
    foreach ($questions as $q_index => $question) {
        $question_text = trim($question['text']);
        $question_type = $question['type'];
        $is_required = isset($question['required']) ? 1 : 0;

        if (empty($question_text)) continue; // Skip empty questions

        $stmt = $pdo->prepare(
            "INSERT INTO questions (survey_id, question_text, question_type, display_order, is_required)
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->execute([$survey_id, $question_text, $question_type, $q_index, $is_required]);
        $question_id = $pdo->lastInsertId();

        // 3. If the question has options, insert them
        if (in_array($question_type, ['radio', 'checkbox', 'dropdown']) && !empty($question['options'])) {
            foreach ($question['options'] as $o_index => $option_text) {
                $option_text = trim($option_text);
                if (empty($option_text)) continue; // Skip empty options

                $stmt = $pdo->prepare(
                    "INSERT INTO question_options (question_id, option_text, display_order)
                     VALUES (?, ?, ?)"
                );
                $stmt->execute([$question_id, $option_text, $o_index]);
            }
        }
    }

    // If we got here without errors, commit the transaction
    $pdo->commit();

    // Set success message and redirect
    // $_SESSION['success'] = 'Survey created successfully!';
    header('Location: creator_dashboard.php');
    exit;

} catch (PDOException $e) {
    // If any part fails, roll back the entire transaction
    $pdo->rollBack();

    // In a real app, log the error and show a user-friendly message
    // error_log('Survey creation failed: ' . $e->getMessage());
    // $_SESSION['error'] = 'An error occurred while creating the survey. Please try again.';
    header('Location: create_survey.php');
    exit;
}
?>
