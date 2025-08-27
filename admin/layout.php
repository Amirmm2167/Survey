<?php
// Core dependencies must be included first
require_once __DIR__ . '/../core/session.php';
require_once __DIR__ . '/../core/localization.php';
require_once __DIR__ . '/../core/functions.php';
?>
<!DOCTYPE html>
<html lang="<?= $current_lang; ?>" dir="<?= $page_direction; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= trans('admin_dashboard'); ?> - <?= trans('site_title'); ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="<?= site_url('public/css/admin.css'); ?>">
</head>
<body>
    <div class="admin-layout">
        <aside class="admin-sidebar">
            <div>
                <h2><span class="nav-text"><?= trans('site_title'); ?></span></h2>
                <nav>
                    <ul>
                        <li><a href="<?= site_url('admin/'); ?>" class="active">
                            <svg class="nav-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/></svg>
                            <span class="nav-text"><?= trans('dashboard'); ?></span>
                        </a></li>
                        <li><a href="<?= site_url('admin/user_management.php'); ?>">
                            <svg class="nav-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>
                            <span class="nav-text"><?= trans('user_management'); ?></span>
                        </a></li>
                    </ul>
                </nav>
            </div>
            <div>
                <a href="<?= site_url('logout.php'); ?>"><span class="nav-text"><?= trans('logout'); ?></span></a>
                <button id="sidebar-toggle">&lt; &gt;</button>
            </div>
        </aside>

        <main class="admin-main-content">
            <?php
            if (isset($page_content_file) && file_exists($page_content_file)) {
                include $page_content_file;
            } else {
                echo "<p>" . trans('error_content_not_found') . "</p>";
            }
            ?>
        </main>
    </div>
    <script src="<?= site_url('public/js/main.js'); ?>"></script>
    <script src="<?= site_url('public/js/admin.js'); ?>"></script>
</body>
</html>
