<?php
// This layout file assumes that the session and core functions are already included.
// It also assumes authorization has been checked.
?>
<!DOCTYPE html>
<html lang="<?= $current_lang; ?>" dir="<?= $page_direction; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= trans('admin_dashboard'); ?> - <?= trans('site_title'); ?></title>

    <!-- Google Fonts for Persian (Vazirmatn) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;700&display=swap" rel="stylesheet">

    <!-- Admin Panel Stylesheet -->
    <link rel="stylesheet" href="<?= site_url('public/css/admin.css'); ?>">
</head>
<body>
    <div class="admin-layout">
        <aside class="admin-sidebar">
            <h2><?= trans('site_title'); ?></h2>
            <nav>
                <ul>
                    <li><a href="<?= site_url('admin/'); ?>" class="active"><?= trans('dashboard'); ?></a></li>
                    <li><a href="<?= site_url('admin/user_management.php'); ?>"><?= trans('user_management'); ?></a></li>
                    <!-- Add more admin links here in the future -->
                </ul>
            </nav>
            <div style="margin-top: auto;">
                <a href="<?= site_url('logout.php'); ?>"><?= trans('logout'); ?></a>
            </div>
        </aside>

        <main class="admin-main-content">
            <?php
            // The specific page content (e.g., from index.php) will be included here.
            if (isset($page_content_file) && file_exists($page_content_file)) {
                include $page_content_file;
            } else {
                echo "<p>Error: Content file not found.</p>";
            }
            ?>
        </main>
    </div>
</body>
</html>
