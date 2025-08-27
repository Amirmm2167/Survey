<?php
require_once __DIR__ . '/../core/session.php';
require_once __DIR__ . '/../core/database.php';
require_once __DIR__ . '/../templates/header.php';

// --- Authorization Check ---
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}
$user_id = $_SESSION['user_id'];
$survey_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$survey_id) {
    echo "<p>No survey specified.</p>";
    require_once __DIR__ . '/../templates/footer.php';
    exit;
}

// --- Fetch survey and verify ownership ---
$survey = null;
try {
    $stmt = $pdo->prepare("SELECT * FROM surveys WHERE id = ? AND creator_id = ?");
    $stmt->execute([$survey_id, $user_id]);
    $survey = $stmt->fetch();

    if (!$survey) {
        echo "<p>Survey not found or you do not have permission to edit it.</p>";
        require_once __DIR__ . '/../templates/footer.php';
        exit;
    }
} catch (PDOException $e) { die("Error fetching survey data."); }

// --- Fetch themes for the dropdown ---
$custom_themes = [];
try {
    $stmt = $pdo->prepare("SELECT id, theme_name FROM custom_themes WHERE creator_id = ?");
    $stmt->execute([$user_id]);
    $custom_themes = $stmt->fetchAll();
} catch (PDOException $e) { die("Error fetching custom themes."); }

$predefined_themes = [1 => 'Default', 2 => 'Cosmic', 3 => 'Dark', 4 => 'Green', 5 => 'Blue'];
?>

<h1>Edit Survey: <?= htmlspecialchars($survey['title']); ?></h1>
<a href="index.php">Back to Dashboard</a>

<hr>

<form action="../api.php" method="POST" id="edit-survey-form">
    <input type="hidden" name="action" value="update_survey">
    <input type="hidden" name="survey_id" value="<?= $survey_id; ?>">
    <div class="form-error" style="display: none; color: red; margin-bottom: 10px;"></div>

    <div>
        <label for="title">Survey Title:</label>
        <input type="text" id="title" name="title" value="<?= htmlspecialchars($survey['title']); ?>" required>
    </div>

    <div>
        <label for="description">Description:</label>
        <textarea id="description" name="description" rows="4"><?= htmlspecialchars($survey['description']); ?></textarea>
    </div>

    <div>
        <label for="status">Survey Status:</label>
        <select id="status" name="status">
            <option value="draft" <?= $survey['status'] == 'draft' ? 'selected' : ''; ?>>Draft</option>
            <option value="published" <?= $survey['status'] == 'published' ? 'selected' : ''; ?>>Published</option>
            <option value="closed" <?= $survey['status'] == 'closed' ? 'selected' : ''; ?>>Closed</option>
        </select>
    </div>

    <hr>

    <h3>Theme Settings</h3>
    <div>
        <label for="theme_selection">Survey Theme:</label>
        <select id="theme_selection" name="theme_selection">
            <option value="none">None (Use Site Default)</option>
            <optgroup label="Predefined Themes">
                <?php foreach ($predefined_themes as $id => $name): ?>
                    <option value="predefined-<?= $id; ?>" <?= $survey['theme_id'] == $id ? 'selected' : ''; ?>><?= $name; ?></option>
                <?php endforeach; ?>
            </optgroup>
            <?php if (!empty($custom_themes)): ?>
            <optgroup label="Your Custom Themes">
                 <?php foreach ($custom_themes as $theme): ?>
                    <option value="custom-<?= $theme['id']; ?>" <?= $survey['custom_theme_id'] == $theme['id'] ? 'selected' : ''; ?>><?= htmlspecialchars($theme['theme_name']); ?></option>
                <?php endforeach; ?>
            </optgroup>
            <?php endif; ?>
        </select>
    </div>

    <hr>

    <button type="submit">Save Changes</button>
</form>

<script>
document.getElementById('edit-survey-form').addEventListener('submit', function(e) {
    e.preventDefault();
    handleFormSubmit(this);
});
</script>

<?php
require_once __DIR__ . '/../templates/footer.php';
?>
