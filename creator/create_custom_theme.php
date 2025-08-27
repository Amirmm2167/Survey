<?php
require_once __DIR__ . '/../core/database.php';
require_once __DIR__ . '/../core/session.php';
require_once __DIR__ . '/../templates/header.php';

// --- Authorization Check ---
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}
$user_id = $_SESSION['user_id'];

// Fetch User's Plan and Theme Limit
$plan = null;
$theme_count = 0;
try {
    $stmt = $pdo->prepare(
        "SELECT p.level, p.custom_theme_limit
         FROM plans p JOIN subscriptions s ON p.id = s.plan_id
         WHERE s.user_id = ? AND s.is_active = TRUE"
    );
    $stmt->execute([$user_id]);
    $plan = $stmt->fetch();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM custom_themes WHERE creator_id = ?");
    $stmt->execute([$user_id]);
    $theme_count = $stmt->fetchColumn();
} catch (PDOException $e) {
    die("Error fetching user data.");
}

// Deny access if user cannot create more themes
if (!$plan || $plan['custom_theme_limit'] <= 0 || $theme_count >= $plan['custom_theme_limit']) {
    echo "<p>You cannot create any more custom themes. Please upgrade your plan or delete an existing theme.</p>";
    echo '<a href="theme_manager.php">Back to Theme Manager</a>';
    require_once __DIR__ . '/templates/footer.php';
    exit;
}

$is_level_5 = $plan['level'] == 5;
$google_fonts = ['Roboto', 'Open Sans', 'Lato', 'Montserrat', 'Oswald', 'Source Sans Pro'];
?>

<h1>Create a New Custom Theme</h1>

<div style="display: flex; gap: 30px;">
    <!-- Form Side -->
    <div style="flex: 1;">
        <form action="../api.php" method="POST" id="create-theme-form">
            <input type="hidden" name="action" value="save_theme">
            <div class="form-error" style="display: none; color: red; margin-bottom: 10px;"></div>
            <div>
                <label for="theme_name">Theme Name:</label>
                <input type="text" id="theme_name" name="theme_name" required>
            </div>

            <h4>Colors</h4>
            <table style="width: 100%;">
                <tr><td><label for="color_primary">Primary (Buttons, Links):</label></td><td><input type="color" id="color_primary" name="color_primary" value="#77b9df" data-css-var="--color-primary"></td></tr>
                <tr><td><label for="color_background">Page Background:</label></td><td><input type="color" id="color_background" name="color_background" value="#ffffff" data-css-var="--color-background"></td></tr>
                <tr><td><label for="color_text">Text Color:</label></td><td><input type="color" id="color_text" name="color_text" value="#333333" data-css-var="--color-text"></td></tr>
                <tr><td><label for="color_accent">Accent / Highlight:</label></td><td><input type="color" id="color_accent" name="color_accent" value="#ff7452" data-css-var="--color-accent"></td></tr>
                <tr><td><label for="color_panel_bg">Panel Background:</label></td><td><input type="color" id="color_panel_bg" name="color_panel_bg" value="#f8f9fa" data-css-var="--color-panel-bg"></td></tr>
            </table>

            <?php if ($is_level_5): ?>
                <hr>
                <h4>Font</h4>
                <div>
                    <label for="font_family">Survey Font:</label>
                    <select id="font_family" name="font_family">
                        <option value="">Default</option>
                        <?php foreach($google_fonts as $font): ?>
                        <option value="<?= $font; ?>"><?= $font; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>

            <hr>
            <button type="submit">Save Theme</button>
            <a href="theme_manager.php" style="margin-left: 10px;">Cancel</a>
        </form>
    </div>

    <!-- Live Preview Side -->
    <div style="flex: 1;">
        <h4>Live Preview</h4>
        <div id="live-preview" style="border: 1px solid #ccc; padding: 20px; border-radius: 5px; transition: all 0.3s ease;">
            <h3 class="preview-text">Survey Title</h3>
            <p class="preview-text">This is some example question text to show how your theme will look.</p>
            <div class="preview-panel" style="padding: 15px; border-radius: 5px; margin: 10px 0;">
                <p class="preview-text">This is a panel.</p>
            </div>
            <button class="preview-button">Primary Button</button>
            <a href="#" class="preview-link">Accent Link</a>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const preview = document.getElementById('live-preview');
    const colorPickers = document.querySelectorAll('input[type="color"]');

    colorPickers.forEach(picker => {
        picker.addEventListener('input', function() {
            const cssVar = this.dataset.cssVar;
            preview.style.setProperty(cssVar, this.value);
        });
        // Set initial values
        preview.style.setProperty(picker.dataset.cssVar, picker.value);
    });

    // Also update preview elements directly for things not easily done with CSS variables on a single element
    const bgColorPicker = document.getElementById('color_background');
    const textColorPicker = document.getElementById('color_text');
    const primaryColorPicker = document.getElementById('color_primary');
    const accentColorPicker = document.getElementById('color_accent');
    const panelBgColorPicker = document.getElementById('color_panel_bg');

    bgColorPicker.addEventListener('input', () => preview.style.backgroundColor = bgColorPicker.value);
    textColorPicker.addEventListener('input', () => preview.querySelectorAll('.preview-text').forEach(el => el.style.color = textColorPicker.value));
    primaryColorPicker.addEventListener('input', () => preview.querySelector('.preview-button').style.backgroundColor = primaryColorPicker.value);
    accentColorPicker.addEventListener('input', () => preview.querySelector('.preview-link').style.color = accentColorPicker.value);
    panelBgColorPicker.addEventListener('input', () => preview.querySelector('.preview-panel').style.backgroundColor = panelBgColorPicker.value);

    // Set initial values
    preview.style.backgroundColor = bgColorPicker.value;
    preview.querySelectorAll('.preview-text').forEach(el => el.style.color = textColorPicker.value);
    preview.querySelector('.preview-button').style.backgroundColor = primaryColorPicker.value;
    preview.querySelector('.preview-link').style.color = accentColorPicker.value;
    preview.querySelector('.preview-panel').style.backgroundColor = panelBgColorPicker.value;
});

document.getElementById('create-theme-form').addEventListener('submit', function(e) {
    e.preventDefault();
    handleFormSubmit(this);
});
</script>


<?php
require_once __DIR__ . '/../templates/footer.php';
?>
