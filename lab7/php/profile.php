<?php
require_once __DIR__ . '/../includes/auth.php';
$user = requireLogin(openDatabase());
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/nav.php';
?>
<main class="container py-4">
    <h1>My profile</h1>
    <?php showFlash(); ?>
    <dl>
        <dt>Name</dt><dd><?= escapeHtml($user['full_name']) ?></dd>
        <dt>Email</dt><dd><?= escapeHtml($user['email']) ?></dd>
        <dt>Role</dt><dd><?= escapeHtml($user['role']) ?></dd>
        <dt>Status</dt><dd><?= escapeHtml($user['status']) ?></dd>
    </dl>
    <a class="btn btn-primary" href="edit-profile.php">Edit profile</a>
    <a class="btn btn-outline-primary" href="change-password.php">Change password</a>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>
