<?php
require_once __DIR__ . '/../core/session.php';
require_once __DIR__ . '/../templates/header.php';

// Auth check
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}
?>

<h1>Create a New Survey</h1>

<form action="../api.php" method="POST" id="create-survey-form">
    <input type="hidden" name="action" value="create_survey">
    <div class="form-error" style="display: none; color: red; margin-bottom: 10px;"></div>

    <!-- Part 1: Basic Survey Details -->
    <fieldset>
        <legend>Survey Details</legend>
        <div>
            <label for="title">Survey Title:</label>
            <input type="text" id="title" name="title" required>
        </div>
        <div>
            <label for="description">Description (Optional):</label>
            <textarea id="description" name="description" rows="4"></textarea>
        </div>
        <div>
            <label for="access_level">Access Level:</label>
            <select id="access_level" name="access_level">
                <option value="public">Public (anyone with a link)</option>
                <option value="private_code">Private (requires a code)</option>
            </select>
        </div>
    </fieldset>

    <br>

    <!-- Part 2: Dynamic Question Builder -->
    <fieldset>
        <legend>Questions</legend>
        <div id="questions-container"></div>
        <br>
        <button type="button" id="add-question-btn">+ Add Question</button>
    </fieldset>

    <br>

    <button type="submit">Save Survey</button>
    <a href="index.php" style="margin-left: 10px;">Cancel</a>
</form>

<script src="../public/js/survey-builder.js"></script>
<script>
document.getElementById('create-survey-form').addEventListener('submit', function(e) {
    e.preventDefault();
    handleFormSubmit(this);
});
</script>

<?php
require_once __DIR__ . '/../templates/footer.php';
?>
