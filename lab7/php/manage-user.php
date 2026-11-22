<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/account-form.php';
$pdo = openDatabase();
$user = requireLogin($pdo);
requireRole($user, ['Administrator']);

$id = filter_input(INPUT_GET, 'user_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if (!$id) {
    stopRequest(400, 'Invalid account ID.');
}
$account = findUserById($pdo, $id);
if (!$account) {
    stopRequest(404, 'Account not found.');
}
$detailsUrl = 'manage-user.php?user_id=' . $id;
$action = inputString($_GET, 'action');
if (!in_array($action, ['', 'role', 'status', 'delete'], true)) {
    stopRequest(400, 'Invalid account action.');
}
$error = '';
if (isFormPost()) {
    try {
        manageUser($pdo, (int)$user['user_id'], $id, $action, $_POST);
        if ($id === (int)$user['user_id'] && in_array($action, ['status', 'delete'], true)) {
            logoutSession();
            redirectTo('login.php');
        }
        flash($action === 'delete' ? 'Account deleted.' : 'Account updated.');
        if ($id === (int)$user['user_id'] && $action === 'role') {
            redirectTo('profile.php');
        }
        redirectTo($action === 'delete' ? 'users.php' : $detailsUrl);
    } catch (DomainException $e) {
        http_response_code(422);
        $error = $e->getMessage();
    }
}
$newStatus = $account['status'] === 'Active' ? 'Inactive' : 'Active';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/nav.php';
?>
<main class="container py-4">
    <h1><?= escapeHtml($account['full_name']) ?></h1>
    <?php showFlash(); ?>
    <?php showFormError($error); ?>
    <dl>
        <dt>Email</dt><dd><?= escapeHtml($account['email']) ?></dd>
        <dt>Role</dt><dd><?= escapeHtml($account['role']) ?></dd>
        <dt>Status</dt><dd><?= escapeHtml($account['status']) ?></dd>
        <dt>Created</dt><dd><?= escapeHtml($account['created_at']) ?></dd>
    </dl>
    <?php if ($action === ''): ?>
        <a class="btn btn-primary" href="<?= escapeHtml($detailsUrl . '&action=role') ?>">Update role</a>
        <a class="btn btn-outline-primary" href="<?= escapeHtml($detailsUrl . '&action=status') ?>"><?= $newStatus === 'Active' ? 'Activate' : 'Deactivate' ?></a>
        <a class="btn btn-outline-danger" href="<?= escapeHtml($detailsUrl . '&action=delete') ?>">Delete account</a>
        <a class="btn btn-secondary" href="users.php">Back to users</a>
    <?php else: ?>
        <form method="post" action="<?= escapeHtml($detailsUrl . '&action=' . $action) ?>">
            <?= csrfField() ?>
            <?php if ($action === 'role'): ?>
                <label class="form-label" for="new-role">New role</label>
                <select class="form-select mb-3" id="new-role" name="value" required>
                    <option value="">Choose a role</option>
                    <?php foreach (array_diff(USER_ROLES, [$account['role']]) as $option): ?>
                        <option><?= escapeHtml($option) ?></option>
                    <?php endforeach; ?>
                </select>
                <button class="btn btn-primary">Save role</button>
            <?php elseif ($action === 'status'): ?>
                <p>Change account status to <?= escapeHtml($newStatus) ?>?</p>
                <input type="hidden" name="value" value="<?= escapeHtml($newStatus) ?>">
                <button class="btn btn-primary">Confirm status change</button>
            <?php else: ?>
                <p>Permanently delete this account? Related tutoring records block deletion.</p>
                <?php if (getenv('LAB7_ALLOW_ACCOUNT_DELETION') !== '1'): ?>
                    <p>Permanent deletion is disabled pending retention policy approval.</p>
                <?php endif; ?>
                <button class="btn btn-danger">Confirm delete</button>
            <?php endif; ?>
            <a class="btn btn-secondary" href="<?= escapeHtml($detailsUrl) ?>">Cancel</a>
        </form>
    <?php endif; ?>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>
