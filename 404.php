<?php
// Set the response code
http_response_code(404);

require_once __DIR__ . '/core/functions.php';
require_once __DIR__ . '/templates/header.php';
?>

<div style="text-align: center; padding: 50px;">
    <h1>404</h1>
    <h2>Page Not Found</h2>
    <p>Sorry, the page you are looking for does not exist.</p>
    <br>
    <a href="<?= site_url(); ?>" class="button-link">Go to Homepage</a>
</div>

<?php
require_once __DIR__ . '/templates/footer.php';
?>
