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
    <!-- I will add CSS files later in the styling step -->
    <style>
        body { font-family: sans-serif; direction: <?= $page_direction; ?>; }
        .container { max-width: 800px; margin: 20px auto; padding: 20px; border: 1px solid #ccc; border-radius: 8px; }
        .error { color: red; }
    </style>
</head>
<body>
    <div class="container">
