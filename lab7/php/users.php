<?php
require_once __DIR__ . '/../includes/auth.php';
$pdo = openDatabase();
$user = requireLogin($pdo);
requireRole($user, ['Administrator']);
$query = trim(inputString($_GET, 'q'));
$role = inputString($_GET, 'role');
$status = inputString($_GET, 'status');
if (($role !== '' && !in_array($role, USER_ROLES, true)) ||
    ($status !== '' && !in_array($status, USER_STATUSES, true))) {
    stopRequest(400, 'Invalid account filter.');
}
$accounts = searchUsers($pdo, $query, $role, $status);
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/nav.php';
?>
<main class="container py-4">
    <h1>Manage users</h1>
    <?php showFlash(); ?>
    <form method="get" class="row g-3 mb-3">
        <div class="col-md">
            <label class="form-label" for="q">Name or email</label>
            <input class="form-control" id="q" name="q" type="search" value="<?= escapeHtml($query) ?>">
        </div>
        <div class="col-md">
            <label class="form-label" for="role">Role</label>
            <select class="form-select" id="role" name="role">
                <option value="">All roles</option>
                <?php foreach (USER_ROLES as $option): ?>
                    <option <?= $role === $option ? 'selected' : '' ?>><?= escapeHtml($option) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md">
            <label class="form-label" for="status">Status</label>
            <select class="form-select" id="status" name="status">
                <option value="">All statuses</option>
                <?php foreach (USER_STATUSES as $option): ?>
                    <option <?= $status === $option ? 'selected' : '' ?>><?= escapeHtml($option) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-auto align-self-end"><button class="btn btn-primary">Search</button></div>
    </form>
    <?php if (!$accounts): ?>
        <p>No accounts matched your search.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table">
                <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Account</th></tr></thead>
                <tbody>
                <?php foreach ($accounts as $account): ?>
                    <tr>
                        <td><?= escapeHtml($account['full_name']) ?></td>
                        <td><?= escapeHtml($account['email']) ?></td>
                        <td><?= escapeHtml($account['role']) ?></td>
                        <td><?= escapeHtml($account['status']) ?></td>
                        <td><a href="manage-user.php?user_id=<?= (int)$account['user_id'] ?>">Manage</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>
