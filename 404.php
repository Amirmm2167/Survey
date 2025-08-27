<?php
http_response_code(404);
require_once __DIR__ . '/core/session.php';
require_once __DIR__ . '/templates/header.php';
?>

<div style="text-align: center; padding: 50px;">
    <h1>404</h1>
    <h2><?= trans('error_page_not_found'); ?></h2>
    <p><?= trans('error_page_not_found_desc'); ?></p>
    <br>
    <a href="<?= site_url(); ?>" class="button-link"><?= trans('go_homepage'); ?></a>
</div>

<?php
require_once __DIR__ . '/templates/footer.php';
?>
