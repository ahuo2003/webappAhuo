<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/account-form.php';
$pdo = openDatabase();
$user = requireLogin($pdo);
$error = '';
$input = $user;
if (isFormPost()) {
    $input = $_POST;
    try {
        updateOwnProfile($pdo, (int)$user['user_id'], $input);
        flash('Your profile was updated.');
        redirectTo('profile.php');
    } catch (DomainException $e) {
        http_response_code(422);
        $error = $e->getMessage();
    }
}
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/nav.php';
?>
<main class="container py-4">
    <h1>Edit profile</h1>
    <?php showFormError($error); ?>
    <form method="post">
        <?= csrfField() ?>
        <?php accountInput('full_name', 'Full name', 'text', inputString($input, 'full_name'), 'name'); ?>
        <?php accountInput('email', 'Email', 'email', inputString($input, 'email'), 'email'); ?>
        <button class="btn btn-primary">Save changes</button>
        <a class="btn btn-secondary" href="profile.php">Cancel</a>
    </form>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>
