<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../services/userService.php';

if ($argc !== 4) {
    fwrite(STDERR, "Usage: php create-admin.php 'Full Name' email@example.com 'Password'\n");
    exit(1);
}

try {
    [$name, $email] = accountIdentity(['full_name' => $argv[1], 'email' => $argv[2]]);
    $password = newPassword(['password' => $argv[3], 'confirmation' => $argv[3]]);
    insertUser(openDatabase(), $name, $email, password_hash($password, PASSWORD_DEFAULT), 'Administrator');
    echo "Administrator account created for $name ($email).\n";
} catch (DomainException $e) {
    fwrite(STDERR, 'Administrator setup failed: ' . $e->getMessage() . PHP_EOL);
    exit(1);
} catch (PDOException $e) {
    fwrite(STDERR, "Could not write to the database. Run php ../bin/setup.php and check database permissions.\n");
    exit(1);
}
