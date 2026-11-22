<?php
// Run from the Lab 7 root, or pass -t lab7 from the parent directory.
ini_set('display_errors', '0');
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';
$publicPages = [
    'login', 'logout', 'register', 'profile', 'edit-profile', 'change-password', 'users', 'manage-user',
    'request-form', 'process-request', 'request-details', 'requests', 'edit-request', 'update-request', 'delete-request',
];
if (in_array($path, ['/', '/index.php', '/css/styles.css'], true)) {
    return false;
}
foreach ($publicPages as $page) {
    if ($path === '/php/' . $page . '.php') {
        return false;
    }
}
http_response_code(404);
exit('Not found.');
