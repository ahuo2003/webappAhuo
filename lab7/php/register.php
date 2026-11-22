<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/account-form.php';
startSecureSession();
$error = '';
if (isFormPost()) {
    try {
        registerUser(openDatabase(), $_POST);
        flash('Your account is active. Log in to continue.');
        redirectTo('login.php');
    } catch (DomainException $e) {
        http_response_code(422);
        $error = $e->getMessage();
    }
}
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/nav.php';
?>
<main class="container py-4">
    <h1>Create account</h1>
    <?php showFormError($error); ?>
    <form method="post">
        <?= csrfField() ?>
        <?php accountInput('full_name', 'Full name', 'text', inputString($_POST, 'full_name'), 'name'); ?>
        <?php accountInput('email', 'Email', 'email', inputString($_POST, 'email'), 'email'); ?>
        <label class="form-label" for="role">Account type</label>
        <select id="role" name="role" class="form-select mb-3" required>
            <option value="">Choose a role</option>
            <?php foreach (['Student', 'Tutor'] as $role): ?>
                <option <?= inputString($_POST, 'role') === $role ? 'selected' : '' ?>><?= escapeHtml($role) ?></option>
            <?php endforeach; ?>
        </select>
        <p>Use a password between 12 and 72 bytes.</p>
        <?php accountInput('password', 'Password', 'password', '', 'new-password'); ?>
        <?php accountInput('confirmation', 'Confirm password', 'password', '', 'new-password'); ?>
        <button class="btn btn-primary">Create account</button>
    </form>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>
