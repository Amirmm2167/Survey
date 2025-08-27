<?php
require_once __DIR__ . '/templates/header.php';

// Auth check
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
?>

<h1>Create a New Survey</h1>

<form action="handle_create_survey.php" method="POST" id="create-survey-form">
    <!-- Part 1: Basic Survey Details -->
    <fieldset style="border: 1px solid #ccc; padding: 15px; border-radius: 5px;">
        <legend>Survey Details</legend>
        <div>
            <label for="title">Survey Title:</label><br>
            <input type="text" id="title" name="title" required style="width: 100%;">
        </div>
        <br>
        <div>
            <label for="description">Description (Optional):</label><br>
            <textarea id="description" name="description" rows="4" style="width: 100%;"></textarea>
        </div>
        <br>
        <div>
            <label for="access_level">Access Level:</label><br>
            <select id="access_level" name="access_level">
                <option value="public">Public (anyone with a link)</option>
                <option value="private_code">Private (requires a code)</option>
            </select>
        </div>
        <div id="access_code_container" style="display: none;">
            <br>
            <label for="access_code">Survey Access Code:</label><br>
            <input type="text" id="access_code" name="access_code">
        </div>
    </fieldset>

    <br>

    <!-- Part 2: Dynamic Question Builder -->
    <fieldset style="border: 1px solid #ccc; padding: 15px; border-radius: 5px;">
        <legend>Questions</legend>
        <div id="questions-container">
            <!-- Questions will be dynamically added here by JavaScript -->
        </div>
        <br>
        <button type="button" id="add-question-btn">+ Add Question</button>
    </fieldset>

    <br>

    <button type="submit">Save Survey</button>
    <a href="creator_dashboard.php" style="margin-left: 10px;">Cancel</a>
</form>

<!-- Include the JavaScript for the dynamic form -->
<script src="public/js/survey-builder.js"></script>

<script>
    // Simple script to show/hide the access code field
    document.getElementById('access_level').addEventListener('change', function() {
        const accessCodeContainer = document.getElementById('access_code_container');
        if (this.value === 'private_code') {
            accessCodeContainer.style.display = 'block';
            document.getElementById('access_code').required = true;
        } else {
            accessCodeContainer.style.display = 'none';
            document.getElementById('access_code').required = false;
        }
    });
</script>

<?php
require_once __DIR__ . '/templates/footer.php';
?>
