<?php
require_once __DIR__ . '/../core/session.php';
require_once __DIR__ . '/../core/database.php';

// --- Authorization Check ---
if (!isset($_SESSION['user_id']) || $_SESSION['role_name'] !== 'admin') {
    header('Location: ../index.php'); // Redirect to main landing page
    exit;
}

// The header must be included AFTER the auth check and potential redirect
require_once __DIR__ . '/../templates/header.php';

// --- Fetch data for display ---
// Fetch all users with their roles and subscription plan
$users = [];
try {
    $stmt = $pdo->query(
        "SELECT u.id, u.username, u.email, u.phone_number, u.created_at, r.name as role_name, p.name as plan_name
         FROM users u
         JOIN roles r ON u.role_id = r.id
         LEFT JOIN subscriptions s ON u.id = s.user_id
         LEFT JOIN plans p ON s.plan_id = p.id
         ORDER BY u.created_at DESC"
    );
    $users = $stmt->fetchAll();
} catch (PDOException $e) {
    // In a real app, log this error
    echo "Error fetching users: " . $e->getMessage();
}

// Fetch all plans for the creation form dropdown
$plans = [];
try {
    $plans = $pdo->query("SELECT id, name, level FROM plans ORDER BY level ASC")->fetchAll();
} catch (PDOException $e) {
    echo "Error fetching plans: " . $e->getMessage();
}

// Fetch the creator role ID
$creator_role_id = null;
try {
    $stmt = $pdo->prepare("SELECT id FROM roles WHERE name = 'creator'");
    $stmt->execute();
    $creator_role_id = $stmt->fetchColumn();
} catch (PDOException $e) {
    echo "Error fetching role id: " . $e->getMessage();
}

?>

<h1><?= trans('admin_dashboard'); ?></h1>
<p>Welcome, <?= htmlspecialchars($_SESSION['username']); ?>! <a href="../logout.php"><?= trans('logout'); ?></a></p>

<hr>

<h2>User Management</h2>

<!-- Section to Create New User -->
<h3>Create New Creator User</h3>
<?php
if (isset($_SESSION['user_creation_success'])) {
    echo '<p style="color: green;">' . $_SESSION['user_creation_success'] . '</p>';
    unset($_SESSION['user_creation_success']);
}
if (isset($_SESSION['user_creation_error'])) {
    echo '<p class="error">' . $_SESSION['user_creation_error'] . '</p>';
    unset($_SESSION['user_creation_error']);
}
?>
<form action="../api.php" method="POST" id="create-user-form">
    <input type="hidden" name="action" value="create_user">
    <input type="hidden" name="role_id" value="<?= $creator_role_id; ?>">
    <div class="form-error" style="display: none; color: red; margin-bottom: 10px;"></div>
    <div>
        <label for="username">Username:</label>
        <input type="text" id="username" name="username" required>
    </div>
    <div>
        <label for="email">Email:</label>
        <input type="email" id="email" name="email">
    </div>
    <div>
        <label for="phone_number">Phone Number:</label>
        <input type="text" id="phone_number" name="phone_number">
    </div>
    <div>
        <label for="password">Password:</label>
        <input type="password" id="password" name="password" required>
    </div>
    <div>
        <label for="plan_id">Subscription Plan:</label>
        <select id="plan_id" name="plan_id" required>
            <?php foreach ($plans as $plan): ?>
                <option value="<?= $plan['id']; ?>"><?= htmlspecialchars($plan['name']); ?> (Level <?= $plan['level']; ?>)</option>
            <?php endforeach; ?>
        </select>
    </div>
    <div>
        <label for="initial_credits">Initial Credits:</label>
        <input type="number" id="initial_credits" name="initial_credits" value="0" required>
    </div>
    <br>
    <button type="submit">Create User</button>
</form>

<hr>

<!-- Section to View All Users -->
<h3>All Users</h3>
<table border="1" cellpadding="5" cellspacing="0" style="width: 100%;">
    <thead>
        <tr>
            <th>ID</th>
            <th>Username</th>
            <th>Email</th>
            <th>Phone Number</th>
            <th>Role</th>
            <th>Plan</th>
            <th>Created At</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($users)): ?>
            <tr>
                <td colspan="7">No users found.</td>
            </tr>
        <?php else: ?>
            <?php foreach ($users as $user): ?>
                <tr>
                    <td><?= $user['id']; ?></td>
                    <td><?= htmlspecialchars($user['username']); ?></td>
                    <td><?= htmlspecialchars($user['email'] ?? 'N/A'); ?></td>
                    <td><?= htmlspecialchars($user['phone_number'] ?? 'N/A'); ?></td>
                    <td><?= htmlspecialchars($user['role_name']); ?></td>
                    <td><?= htmlspecialchars($user['plan_name'] ?? 'N/A'); ?></td>
                    <td><?= $user['created_at']; ?></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const createUserForm = document.getElementById('create-user-form');
    if (createUserForm) {
        createUserForm.addEventListener('submit', function(e) {
            e.preventDefault();
            handleFormSubmit(this);
        });
    }
});
</script>

<?php
require_once __DIR__ . '/../templates/footer.php';
?>
