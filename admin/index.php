<?php
require_once __DIR__ . '/../core/session.php';
require_once __DIR__ . '/../core/functions.php';

// Get DB connection
$pdo = get_db_connection();

// --- Authorization Check ---
if (!isset($_SESSION['user_id']) || $_SESSION['role_name'] !== 'admin') {
    redirect(site_url());
}

require_once __DIR__ . '/../templates/header.php';

// --- Fetch data for display ---
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
} catch (PDOException $e) { die("Error fetching users: " . $e->getMessage()); }

$plans = [];
try {
    $plans = $pdo->query("SELECT id, name, level FROM plans ORDER BY level ASC")->fetchAll();
} catch (PDOException $e) { die("Error fetching plans: " . $e->getMessage()); }

$creator_role_id = null;
try {
    $stmt = $pdo->prepare("SELECT id FROM roles WHERE name = 'creator'");
    $stmt->execute();
    $creator_role_id = $stmt->fetchColumn();
} catch (PDOException $e) { die("Error fetching role id: " . $e->getMessage()); }
?>

<h1><?= trans('admin_dashboard'); ?></h1>
<p>Welcome, <?= htmlspecialchars($_SESSION['username']); ?>! <a href="<?= site_url('logout.php'); ?>"><?= trans('logout'); ?></a></p>
<hr>
<h2><?= trans('user_management'); ?></h2>

<h3><?= trans('create_new_creator'); ?></h3>
<div class="form-message" style="display: none; margin-bottom: 10px;"></div>
<form action="<?= site_url('api.php'); ?>" method="POST" id="create-user-form">
    <input type="hidden" name="action" value="create_user">
    <input type="hidden" name="role_id" value="<?= $creator_role_id; ?>">
    <div class="form-error" style="display: none; color: red; margin-bottom: 10px;"></div>
    <div>
        <label for="username"><?= trans('username'); ?></label>
        <input type="text" id="username" name="username" required>
    </div>
    <div>
        <label for="email"><?= trans('email'); ?></label>
        <input type="email" id="email" name="email">
    </div>
    <div>
        <label for="phone_number"><?= trans('phone_number'); ?></label>
        <input type="text" id="phone_number" name="phone_number">
    </div>
    <div>
        <label for="password"><?= trans('password'); ?></label>
        <input type="password" id="password" name="password" required>
    </div>
    <div>
        <label for="plan_id"><?= trans('subscription_plan'); ?></label>
        <select id="plan_id" name="plan_id" required>
            <?php foreach ($plans as $plan): ?>
                <option value="<?= $plan['id']; ?>"><?= htmlspecialchars($plan['name']); ?> (Level <?= $plan['level']; ?>)</option>
            <?php endforeach; ?>
        </select>
    </div>
    <div>
        <label for="initial_credits"><?= trans('initial_credits'); ?></label>
        <input type="number" id="initial_credits" name="initial_credits" value="0" required>
    </div>
    <br>
    <button type="submit"><?= trans('create_user'); ?></button>
</form>
<hr>
<h3><?= trans('all_users'); ?></h3>
<table>
    <thead>
        <tr>
            <th><?= trans('user_id'); ?></th>
            <th><?= trans('username'); ?></th>
            <th><?= trans('email'); ?></th>
            <th><?= trans('phone_number'); ?></th>
            <th><?= trans('role'); ?></th>
            <th><?= trans('plan'); ?></th>
            <th><?= trans('created_at'); ?></th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($users)): ?>
            <tr>
                <td colspan="7"><?= trans('no_users_found'); ?></td>
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
