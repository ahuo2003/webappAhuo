<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/account-form.php';
startSecureSession();
$error = '';
if (isFormPost()) {
    $account = authenticateUser(openDatabase(), $_POST);
    if ($account) {
        startLogin($account);
        redirectTo('profile.php');
    }
    http_response_code(401);
    $error = 'Invalid email or password.';
}
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/nav.php';
?>
<main class="container py-4">
    <h1>Log in</h1>
    <?php showFlash(); ?>
    <?php if (isset($_GET["password_changed"])): ?><p>Password updated. Log in with your new password.</p><?php endif; ?>
    <?php if (isset($_GET['expired'])): ?><p>Your session ended. Please log in again.</p><?php endif; ?>
    <?php if (isset($_GET['logged_out'])): ?><p>You are logged out.</p><?php endif; ?>
    <?php showFormError($error); ?>
    <form method="post">
        <?= csrfField() ?>
        <?php accountInput('email', 'Email', 'email', inputString($_POST, 'email'), 'username'); ?>
        <?php accountInput('password', 'Password', 'password', '', 'current-password'); ?>
        <button class="btn btn-primary">Log in</button>
    </form>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>
