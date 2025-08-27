<?php
// Set the response code
http_response_code(500);

require_once __DIR__ . '/core/functions.php';
require_once __DIR__ . '/templates/header.php';
?>

<div style="text-align: center; padding: 50px;">
    <h1>500</h1>
    <h2>Internal Server Error</h2>
    <p>Sorry, something went wrong on our end. We are looking into it.</p>
    <br>
    <a href="<?= site_url(); ?>" class="button-link">Go to Homepage</a>
</div>

<?php
require_once __DIR__ . '/templates/footer.php';
?>
