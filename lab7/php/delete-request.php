<?php
require_once __DIR__ . '/../includes/auth.php';
$pdo = openDatabase();
$user = requireLogin($pdo);
requireRole($user, ['Student', 'Administrator']);
$ownerId = $user['role'] === 'Student' ? (int)$user['user_id'] : null;

$post = isFormPost();
$id = filter_input($post ? INPUT_POST : INPUT_GET, 'request_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if (!$id) {
    stopRequest(400, 'Invalid request ID.');
}
$request = findRequest($pdo, $id, $ownerId);
if (!$request) {
    stopRequest(404, 'Request not found.');
}
if ($post) {
    try {
        if (!cancelOpenRequest($pdo, $id, $ownerId)) {
            stopRequest(409, 'Only Open requests may be cancelled.');
        }
        redirectTo('request-details.php?request_id=' . $id);
    } catch (Throwable $e) {
        error_log($e->getMessage());
        stopRequest(500, 'Unable to cancel the request.');
    }
}
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/nav.php';
?>
<main class="container py-4">
    <h1>Cancel tutoring request</h1>
    <p>Cancel request #<?= $id ?>? The record will be retained with status Cancelled.</p>
    <?php if ($request['status'] === 'Open'): ?>
        <form method="post">
            <?= csrfField() ?>
            <input type="hidden" name="request_id" value="<?= $id ?>">
            <button class="btn btn-danger">Yes, cancel request</button>
        </form>
    <?php else: ?>
        <p>Only Open requests can be cancelled.</p>
    <?php endif; ?>
    <a href="request-details.php?request_id=<?= $id ?>">Keep request</a>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>
