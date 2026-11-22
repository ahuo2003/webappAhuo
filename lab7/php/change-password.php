<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/account-form.php';
$pdo = openDatabase();
$user = requireLogin($pdo);
$error = '';
if (isFormPost()) {
    try {
        changeOwnPassword($pdo, $user, $_POST);
        logoutSession();
        redirectTo('login.php?password_changed=1');
    } catch (DomainException $e) {
        http_response_code(422);
        $error = $e->getMessage();
    }
}
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/nav.php';
?>
<main class="container py-4">
    <h1>Change password</h1>
    <p>Use a new password between 12 and 72 bytes. Sign in again after saving.</p>
    <?php showFormError($error); ?>
    <form method="post">
        <?= csrfField() ?>
        <?php accountInput('current_password', 'Current password', 'password', '', 'current-password'); ?>
        <?php accountInput('password', 'New password', 'password', '', 'new-password'); ?>
        <?php accountInput('confirmation', 'Confirm new password', 'password', '', 'new-password'); ?>
        <button class="btn btn-primary">Update password</button>
        <a class="btn btn-secondary" href="profile.php">Cancel</a>
    </form>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>
