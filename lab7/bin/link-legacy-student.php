<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../repositories/studentPDO.php';

$options = ['options' => ['min_range' => 1]];
$studentId = filter_var($argv[1] ?? null, FILTER_VALIDATE_INT, $options);
$userId = filter_var($argv[2] ?? null, FILTER_VALIDATE_INT, $options);
if ($argc !== 3 || !$studentId || !$userId) {
    fwrite(STDERR, "Usage: php bin/link-legacy-student.php STUDENT_ID USER_ID\n");
    exit(1);
}
try {
    if (!linkLegacyStudent(openDatabase(), $studentId, $userId)) {
        throw new DomainException('Choose an unlinked legacy record and an unlinked active Student account.');
    }
    echo "Legacy student and tutoring records linked.\n";
} catch (Throwable $e) {
    fwrite(STDERR, $e->getMessage() . PHP_EOL);
    exit(1);
}
