<?php
// Set the response code
http_response_code(403);

require_once __DIR__ . '/core/functions.php';
require_once __DIR__ . '/templates/header.php';
?>

<div style="text-align: center; padding: 50px;">
    <h1>403</h1>
    <h2>Forbidden</h2>
    <p>Sorry, you do not have permission to access this page.</p>
    <br>
    <a href="<?= site_url(); ?>" class="button-link">Go to Homepage</a>
</div>

<?php
require_once __DIR__ . '/templates/footer.php';
?>
