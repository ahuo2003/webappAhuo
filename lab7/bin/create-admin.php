<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../services/userService.php';

if ($argc !== 3) {
    fwrite(STDERR, "Usage: php bin/create-admin.php 'Full Name' email@example.edu\n");
    exit(1);
}
try {
    [$name, $email] = accountIdentity(['full_name' => $argv[1], 'email' => $argv[2]]);
    $password = bin2hex(random_bytes(16));
    insertUser(openDatabase(), $name, $email, password_hash($password, PASSWORD_DEFAULT), 'Administrator');
    echo "Administrator created. Sign in and change this generated setup password:\n$password\n";
} catch (Throwable $e) {
    fwrite(STDERR, 'Administrator setup failed: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
