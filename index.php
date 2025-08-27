<?php
require_once __DIR__ . '/core/session.php';

// This is a public-facing page, so we load the header.
// It will use the default theme.
require_once __DIR__ . '/templates/header.php';
?>

<style>
    .hero { text-align: center; padding: 50px 20px; }
    .hero h1 { font-size: 2.5rem; }
    .hero p { font-size: 1.2rem; color: var(--color-muted); }
    .features { display: flex; justify-content: space-around; text-align: center; padding: 40px 0; }
    .feature { max-width: 30%; }
    .feature h3 { margin-bottom: 10px; }
</style>

<div class="hero">
    <h1>The Professional Survey Platform</h1>
    <p>Create, share, and analyze surveys with ease. Get the insights you need.</p>
    <br>
    <?php if (isset($_SESSION['user_id'])):
        $dashboard_url = ($_SESSION['role_name'] === 'admin') ? 'admin/' : 'creator/';
    ?>
        <a href="<?= $dashboard_url ?>" class="button-link">Go to Your Dashboard</a>
    <?php else: ?>
        <button id="login-modal-btn" class="button-link">Get Started / Login</button>
    <?php endif; ?>
</div>

<hr>

<div class="features">
    <div class="feature">
        <h3>Powerful Survey Builder</h3>
        <p>Create surveys with various question types, from simple text to multiple choice.</p>
    </div>
    <div class="feature">
        <h3>Custom Theming</h3>
        <p>Match your brand with custom themes, colors, and fonts for your surveys.</p>
    </div>
    <div class="feature">
        <h3>Actionable Insights</h3>
        <p>View your results in real-time and export your data for deep analysis.</p>
    </div>
</div>


<!-- The actual login form that will be placed inside the modal -->
<div id="login-form-content" style="display: none;">
    <form action="api.php" method="POST" id="login-form">
        <input type="hidden" name="action" value="login">
        <h2><?= trans('login_welcome'); ?></h2>
        <div class="form-error" style="display: none; color: red; margin-bottom: 10px;"></div>
        <div>
            <label for="login-username"><?= trans('username'); ?></label>
            <input type="text" id="login-username" name="username" required>
        </div>
        <div>
            <label for="login-password"><?= trans('password'); ?></label>
            <input type="password" id="login-password" name="password" required>
        </div>
        <br>
        <button type="submit"><?= trans('login_button'); ?></button>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const loginModalBtn = document.getElementById('login-modal-btn');
    const modalBody = document.getElementById('modal-body');
    const loginFormContent = document.getElementById('login-form-content');
    const modal = document.getElementById('generic-modal');
    const closeBtn = modal.querySelector('.close-btn');

    loginModalBtn.addEventListener('click', () => {
        modalBody.innerHTML = loginFormContent.innerHTML;
        openModal('generic-modal');

        const loginForm = document.getElementById('login-form');
        if(loginForm) {
            loginForm.addEventListener('submit', function(e) {
                e.preventDefault();
                handleFormSubmit(this);
            });
        }
    });

    closeBtn.addEventListener('click', () => closeModal('generic-modal'));
    window.addEventListener('click', (event) => {
        if (event.target == modal) {
            closeModal('generic-modal');
        }
    });
});
</script>

<?php
require_once __DIR__ . '/templates/footer.php';
?>
