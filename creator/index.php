<?php
require_once __DIR__ . '/../core/session.php';
require_once __DIR__ . '/../core/database.php';
require_once __DIR__ . '/../core/functions.php';

// --- Authorization Check ---
if (!isset($_SESSION['user_id'])) {
    redirect('../index.php');
}
$user_id = $_SESSION['user_id'];

// --- Fetch Creator-Specific Data ---
$plan = get_user_plan($pdo, $user_id);
// Additional data fetching...
$surveys = [];
try {
    $stmt = $pdo->prepare(
       "SELECT s.id, s.title, s.status, s.access_level, COUNT(DISTINCT r.id) as response_count
        FROM surveys s
        LEFT JOIN respondents r ON s.id = r.survey_id
        WHERE s.creator_id = ?
        GROUP BY s.id
        ORDER BY s.created_at DESC"
    );
    $stmt->execute([$user_id]);
    $surveys = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Error fetching surveys.");
}

// This needs to come AFTER data fetching and auth checks
require_once __DIR__ . '/../templates/header.php';
?>

<h1><?= trans('creator_dashboard'); ?></h1>
<p>Welcome, <?= htmlspecialchars($_SESSION['username']); ?>! <a href="<?= site_url('logout.php'); ?>" id="logout-link">Logout</a></p>

<!-- Account Status Section -->
<div class="account-status">
    <h3>Account Status</h3>
    <?php if ($plan): ?>
        <p><strong>Plan:</strong> <?= htmlspecialchars($plan['name']); ?></p>
        <?php if ($plan['custom_theme_limit'] > 0): ?>
            <p><a href="<?= site_url('creator/theme_manager.php'); ?>">Manage Your Custom Themes</a></p>
        <?php endif; ?>
    <?php else: ?>
        <p>You do not have an active subscription. Please contact an administrator.</p>
    <?php endif; ?>
</div>

<hr>

<!-- Survey Management Section -->
<div class="survey-management">
    <h2>My Surveys</h2>
    <a href="<?= site_url('creator/create_survey.php'); ?>" class="button-link">+ Create New Survey</a>
    <br><br>
    <table>
        <thead>
            <tr>
                <th>Title</th>
                <th>Status</th>
                <th>Responses</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($surveys)): ?>
                <tr><td colspan="4">You haven't created any surveys yet.</td></tr>
            <?php else: ?>
                <?php foreach ($surveys as $survey): ?>
                    <tr>
                        <td><?= htmlspecialchars($survey['title']); ?></td>
                        <td><?= ucfirst(htmlspecialchars($survey['status'])); ?></td>
                        <td><?= $survey['response_count']; ?></td>
                        <td>
                            <a href="<?= site_url('survey.php?id=' . $survey['id']); ?>" target="_blank">View</a> |
                            <a href="<?= site_url('creator/view_results.php?id=' . $survey['id']); ?>">Results</a> |
                            <a href="<?= site_url('creator/edit_survey.php?id=' . $survey['id']); ?>">Edit</a> |
                            <button class="delete-survey-btn" data-survey-id="<?= $survey['id']; ?>">Delete</button>
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
            if (confirm('Are you sure you want to delete this survey? This action cannot be undone.')) {
                const surveyId = this.dataset.surveyId;
                const formData = new FormData();
                formData.append('action', 'delete_survey');
                formData.append('survey_id', surveyId);

                fetch('../api.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(result => {
                    if (result.status === 'success') {
                        // Reload the page to show the updated list
                        location.reload();
                    } else {
                        alert('Error: ' + result.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while deleting the survey.');
                });
            }
        });
    });
});
</script>

<?php
require_once __DIR__ . '/../templates/footer.php';
?>
