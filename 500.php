<?php
http_response_code(500);
require_once __DIR__ . '/core/session.php';
require_once __DIR__ . '/templates/header.php';
?>

<div style="text-align: center; padding: 50px;">
    <h1>500</h1>
    <h2><?= trans('error_server'); ?></h2>
    <p><?= trans('error_server_desc'); ?></p>
    <br>
    <a href="<?= site_url(); ?>" class="button-link"><?= trans('go_homepage'); ?></a>
</div>

<?php
require_once __DIR__ . '/templates/footer.php';
?>
