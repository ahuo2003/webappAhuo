<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../repositories/schemaPDO.php';

try {
    $pdo = openDatabase();
    $pdo->exec(file_get_contents(__DIR__ . '/../database/setup-database.sql'));
    migrateUserSchema($pdo);
    echo "Lab 7 database ready.\n";
} catch (Throwable $e) {
    fwrite(STDERR, 'Setup failed: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
