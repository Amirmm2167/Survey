<?php
// Every page that needs session and language support should include this.
require_once __DIR__ . '/../core/localization.php';
?>
<!DOCTYPE html>
<html lang="<?= $current_lang; ?>" dir="<?= $page_direction; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= trans('site_title'); ?></title>

    <!-- Google Fonts for Persian -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;700&display=swap" rel="stylesheet">

    <!-- Main Stylesheet -->
    <link rel="stylesheet" href="public/css/style.css">

    <!-- Theme-specific stylesheet will be loaded here -->
    <?php
    // Define a default theme. Page-specific logic can override $theme_file before this header is included.
    if (!isset($theme_file)) {
        $theme_file = 'public/css/themes/default.css';
    }
    if (file_exists($theme_file)) {
        echo '<link rel="stylesheet" href="' . htmlspecialchars($theme_file) . '">';
    }
    ?>
</head>
<body>
    <div class="container">
