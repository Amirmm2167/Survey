<?php
// This file is included by admin/index.php and is wrapped by admin/layout.php
// The $stats variable is available from the parent script (admin/index.php).
?>
<h1><?= trans('admin_dashboard'); ?></h1>
<p>Welcome, <?= htmlspecialchars($_SESSION['username']); ?>!</p>
<hr>

<div class="stats-grid">
    <div class="stat-card">
        <h3>Total Users</h3>
        <p class="stat-value"><?= $stats['total_users']; ?></p>
    </div>
    <div class="stat-card">
        <h3>Total Surveys</h3>
        <p class="stat-value"><?= $stats['total_surveys']; ?></p>
    </div>
    <div class="stat-card">
        <h3>Credits Used</h3>
        <p class="stat-value"><?= number_format($stats['total_credits_used']); ?></p>
    </div>
    <div class="stat-card">
        <h3>Credits Available</h3>
        <p class="stat-value"><?= number_format($stats['total_credits_available']); ?></p>
    </div>
    <div class="stat-card">
        <h3>Total Credits Added</h3>
        <p class="stat-value"><?= number_format($stats['total_credits_bought']); ?></p>
    </div>
    <div class="stat-card">
        <h3>Users by Tier</h3>
        <ul class="stat-list">
            <?php foreach($stats['users_by_tier'] as $tier): ?>
                <li><span><?= htmlspecialchars($tier['name']); ?>:</span> <strong><?= $tier['user_count']; ?></strong></li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <h3>Top Users by Credits Used</h3>
        <ul class="stat-list">
            <?php foreach($stats['top_users_by_credit'] as $user): ?>
                <li><span><?= htmlspecialchars($user['username']); ?>:</span> <strong><?= number_format($user['total_used']); ?></strong></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <div class="stat-card">
        <h3>Top Surveys by Usage</h3>
        <ul class="stat-list">
            <?php foreach($stats['top_surveys_by_usage'] as $survey): ?>
                 <li><span><?= htmlspecialchars($survey['title']); ?>:</span> <strong><?= number_format($survey['total_usage']); ?></strong></li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>
