<?php
// This file is included by admin/user_management.php
?>
<h1><?= trans('user_management'); ?></h1>
<hr>
<h3><?= trans('create_new_creator'); ?></h3>
<form action="<?= site_url('api.php'); ?>" method="POST" id="create-user-form">
    <input type="hidden" name="action" value="create_user">
    <input type="hidden" name="role_id" value="<?= $creator_role_id; ?>">
    <div class="form-error" style="display: none;"></div>
    <div><label for="username"><?= trans('username'); ?></label><input type="text" id="username" name="username" required></div>
    <div><label for="email"><?= trans('email'); ?></label><input type="email" id="email" name="email"></div>
    <div><label for="phone_number"><?= trans('phone_number'); ?></label><input type="text" id="phone_number" name="phone_number"></div>
    <div><label for="password"><?= trans('password'); ?></label><input type="password" id="password" name="password" required></div>
    <div><label for="plan_id"><?= trans('subscription_plan'); ?></label><select id="plan_id" name="plan_id" required><?php foreach ($plans as $plan): ?><option value="<?= $plan['id']; ?>"><?= htmlspecialchars($plan['name']); ?></option><?php endforeach; ?></select></div>
    <div><label for="initial_credits"><?= trans('initial_credits'); ?></label><input type="number" id="initial_credits" name="initial_credits" value="0" required></div>
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
            <tr><td colspan="7"><?= trans('no_users_found'); ?></td></tr>
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
