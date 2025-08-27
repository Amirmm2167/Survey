<?php
require_once __DIR__ . '/../core/session.php';
require_once __DIR__ . '/../core/functions.php';

// Get DB connection
$pdo = get_db_connection();

// --- Authorization Check ---
if (!isset($_SESSION['user_id'])) {
    redirect(site_url());
}
$user_id = $_SESSION['user_id'];

// --- Fetch Creator-Specific Data ---
$plan = get_user_plan($pdo, $user_id);
$surveys = [];
try {
    $stmt = $pdo->prepare(
       "SELECT s.id, s.unique_id, s.title, s.status, s.access_level, COUNT(DISTINCT r.id) as response_count
        FROM surveys s
        LEFT JOIN respondents r ON s.id = r.survey_id
        WHERE s.creator_id = ?
        GROUP BY s.id
        ORDER BY s.created_at DESC"
    );
    $stmt->execute([$user_id]);
    $surveys = $stmt->fetchAll();
} catch (PDOException $e) { die("Error fetching surveys."); }

require_once __DIR__ . '/../templates/header.php';
?>

<h1><?= trans('creator_dashboard'); ?></h1>
<p>Welcome, <?= htmlspecialchars($_SESSION['username']); ?>! <a href="<?= site_url('logout.php'); ?>" id="logout-link"><?= trans('logout'); ?></a></p>

<div class="account-status">
    <h3><?= trans('account_status'); ?></h3>
    <?php if ($plan): ?>
        <p><strong><?= trans('plan'); ?>:</strong> <?= htmlspecialchars($plan['name']); ?></p>
        <?php if ($plan['custom_theme_limit'] > 0): ?>
            <p><a href="<?= site_url('creator/theme_manager.php'); ?>"><?= trans('manage_themes'); ?></a></p>
        <?php endif; ?>
    <?php endif; ?>
</div>
<hr>
<div class="survey-management">
    <h2><?= trans('my_surveys'); ?></h2>
    <a href="<?= site_url('creator/create_survey.php'); ?>" class="button-link">+ <?= trans('create_new_survey'); ?></a>
    <br><br>
    <table>
        <thead>
            <tr>
                <th><?= trans('survey_title'); ?></th>
                <th><?= trans('status'); ?></th>
                <th><?= trans('responses'); ?></th>
                <th><?= trans('actions'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($surveys)): ?>
                <tr><td colspan="4"><?= trans('no_surveys_found'); ?></td></tr>
            <?php else: ?>
                <?php foreach ($surveys as $survey): ?>
                    <tr>
                        <td><?= htmlspecialchars($survey['title']); ?></td>
                        <td><?= trans('status_' . strtolower($survey['status'])); ?></td>
                        <td><?= $survey['response_count']; ?></td>
                        <td>
                            <a href="<?= site_url($survey['unique_id']); ?>" target="_blank"><?= trans('view'); ?></a> |
                            <a href="<?= site_url('creator/view_results.php?id=' . $survey['id']); ?>"><?= trans('results'); ?></a> |
                            <a href="<?= site_url('creator/edit_survey.php?id=' . $survey['id']); ?>"><?= trans('edit'); ?></a> |
                            <button class="delete-survey-btn" data-survey-id="<?= $survey['id']; ?>"><?= trans('delete'); ?></button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.delete-survey-btn').forEach(button => {
        button.addEventListener('click', function() {
            if (confirm('<?= trans('confirm_delete'); ?>')) {
                const surveyId = this.dataset.surveyId;
                const formData = new FormData();
                formData.append('action', 'delete_survey');
                formData.append('survey_id', surveyId);
                fetch('<?= site_url('api.php'); ?>', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(result => {
                    if (result.status === 'success') {
                        location.reload();
                    } else {
                        alert('Error: ' + result.message);
                    }
                });
            }
        });
    });
});
</script>

<?php
require_once __DIR__ . '/../templates/footer.php';
?>
