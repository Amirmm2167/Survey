<?php
require_once __DIR__ . '/templates/header.php';
require_once __DIR__ . '/core/database.php';

// --- Authorization Check ---
// Make sure the user is logged in. This page is for any logged-in user, but content will be creator-specific.
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$user_id = $_SESSION['user_id'];

// --- Fetch Creator-Specific Data ---
$wallet = null;
$plan = null;
$surveys = [];

try {
    // Fetch wallet balance
    $stmt = $pdo->prepare("SELECT balance, reserved_balance FROM wallets WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $wallet = $stmt->fetch();

    // Fetch subscription plan details
    $stmt = $pdo->prepare(
        "SELECT p.name, p.credits_allowance, s.end_date
         FROM subscriptions s
         JOIN plans p ON s.plan_id = p.id
         WHERE s.user_id = ? AND s.is_active = TRUE"
    );
    $stmt->execute([$user_id]);
    $plan = $stmt->fetch();

    // Fetch all surveys created by this user
    // We also get a count of responses for each survey.
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
    // In a real app, log this error and show a friendly message
    echo "Error fetching dashboard data: " . $e->getMessage();
}

?>

<h1><?= trans('creator_dashboard'); ?></h1>
<p>Welcome, <?= htmlspecialchars($_SESSION['username']); ?>! <a href="logout.php"><?= trans('logout'); ?></a></p>

<!-- Account Status Section -->
<div class="account-status">
    <h3>Account Status</h3>
    <?php if ($plan && $wallet): ?>
        <p><strong>Plan:</strong> <?= htmlspecialchars($plan['name']); ?></p>
        <p>
            <strong>Credits:</strong>
            <?= $wallet['balance']; ?> available
            (<?= $wallet['reserved_balance']; ?> reserved for active surveys)
            / <?= $plan['credits_allowance'] == -1 ? 'Unlimited' : $plan['credits_allowance']; ?>
        </p>
        <p>Your plan renews/expires on: <?= date('Y-m-d', strtotime($plan['end_date'])); ?></p>
    <?php else: ?>
        <p>You do not have an active subscription or wallet. Please contact an administrator.</p>
    <?php endif; ?>
</div>

<hr>

<!-- Survey Management Section -->
<div class="survey-management">
    <h2>My Surveys</h2>
    <a href="create_survey.php" style="display:inline-block; padding:10px 15px; background-color: #28a745; color: white; text-decoration: none; border-radius: 5px;">
        + Create New Survey
    </a>
    <br><br>
    <table border="1" cellpadding="5" cellspacing="0" style="width: 100%;">
        <thead>
            <tr>
                <th>Title</th>
                <th>Status</th>
                <th>Access</th>
                <th>Responses</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($surveys)): ?>
                <tr>
                    <td colspan="5">You haven't created any surveys yet.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($surveys as $survey): ?>
                    <tr>
                        <td><?= htmlspecialchars($survey['title']); ?></td>
                        <td><?= ucfirst(htmlspecialchars($survey['status'])); ?></td>
                        <td><?= ucfirst(htmlspecialchars($survey['access_level'])); ?></td>
                        <td><?= $survey['response_count']; ?></td>
                        <td>
                            <a href="view_results.php?id=<?= $survey['id']; ?>">Results</a> |
                            <a href="edit_survey.php?id=<?= $survey['id']; ?>">Edit</a> |
                            <a href="delete_survey.php?id=<?= $survey['id']; ?>" onclick="return confirm('Are you sure?');">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php
require_once __DIR__ . '/templates/footer.php';
?>
