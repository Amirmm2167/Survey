<?php
// Core dependencies must be included first to make functions available.
require_once __DIR__ . '/../core/session.php';
require_once __DIR__ . '/../core/localization.php';
require_once __DIR__ . '/../core/functions.php';
?>
<!DOCTYPE html>
<html lang="<?= $current_lang; ?>" dir="<?= $page_direction; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= trans('site_title'); ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="<?= site_url('public/css/style.css'); ?>">
    <?php
    if (isset($theme_file) && file_exists(__DIR__ . '/../' . $theme_file)) {
        echo '<link rel="stylesheet" href="' . site_url($theme_file) . '">';
    }
    ?>
    <script> const BASE_URL = '<?= site_url(); ?>'; </script>
</head>
<body>
    <header class="site-header">
        <div class="header-inner">
            <div class="header-logo">
                <a href="<?= site_url(); ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm-1-12h2v2h-2zm0 4h2v6h-2z"/></svg>
                    <span><?= trans('site_title'); ?></span>
                </a>
            </div>
            <nav class="header-nav">
                <ul>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <?php if ($_SESSION['role_name'] === 'admin'): ?>
                            <li><a href="<?= site_url('admin/'); ?>"><?= trans('admin_dashboard'); ?></a></li>
                        <?php else: ?>
                            <li><a href="<?= site_url('creator/'); ?>"><?= trans('creator_dashboard'); ?></a></li>
                        <?php endif; ?>
                        <li><a href="<?= site_url('logout.php'); ?>"><?= trans('logout'); ?></a></li>
                    <?php else: ?>
                        <li><button id="login-modal-btn" class="button-link"><?= trans('login_button'); ?></button></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>
    <div class="container">
