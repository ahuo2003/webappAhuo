<?php
require_once __DIR__ . '/../includes/auth.php';
$pdo = openDatabase();
$user = requireLogin($pdo);
requireRole($user, ['Student', 'Administrator']);
$ownerId = $user['role'] === 'Student' ? (int)$user['user_id'] : null;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    stopRequest(405, 'POST required.');
}
verifyCsrfToken();
$id = filter_input(INPUT_POST, 'request_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if (!$id) {
    stopRequest(400, 'Invalid request ID.');
}
if (!findRequest($pdo, $id, $ownerId)) {
    stopRequest(404, 'Request not found.');
}
try {
    if (!reviseRequest($pdo, $id, $_POST, $ownerId)) {
        stopRequest(409, 'Only Open requests may be edited.');
    }
    redirectTo('request-details.php?request_id=' . $id);
} catch (InvalidArgumentException $e) {
    stopRequest(422, $e->getMessage());
} catch (Throwable $e) {
    error_log($e->getMessage());
    stopRequest(500, 'Unable to update the request.');
}
