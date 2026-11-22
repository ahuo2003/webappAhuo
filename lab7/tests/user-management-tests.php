<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../repositories/schemaPDO.php';
require_once __DIR__ . '/../services/userService.php';
require_once __DIR__ . '/../services/requestService.php';

$pdo = openDatabase(':memory:');
$pdo->exec(file_get_contents(__DIR__ . '/../database/setup-database.sql'));
$pdo->exec("INSERT INTO students (student_name, student_email) VALUES ('Legacy Student', 'legacy@example.edu')");
insertTutoringRequest($pdo, 1, [
    'course' => 'IS 6465', 'topic' => 'Original plain-text topic',
    'preferred_date' => '2027-01-01', 'preferred_time' => '14:00',
]);
migrateUserSchema($pdo);
migrateUserSchema($pdo);
$passed = $failed = 0;

function check(bool $condition, string $label): void
{
    global $passed, $failed;
    echo ($condition ? 'PASS ' : 'FAIL ') . $label . PHP_EOL;
    if ($condition) {
        $passed++;
    } else {
        $failed++;
    }
}

function denied(callable $action): bool
{
    try {
        $action();
        return false;
    } catch (DomainException $e) {
        return true;
    }
}

$password = 'lab7-test-passphrase';
$input = [
    'full_name' => 'Alex Student', 'email' => 'Alex@example.edu',
    'password' => $password, 'confirmation' => $password, 'role' => 'Student',
];
$student = registerUser($pdo, $input);
$tutor = registerUser($pdo, array_replace($input, ['role' => 'Tutor', 'email' => 'tutor@example.edu']));
$admin = insertUser($pdo, 'Jordan Admin', 'admin@example.edu', password_hash($password, PASSWORD_DEFAULT), 'Administrator');
$account = findUserById($pdo, $student);
check($account['status'] === 'Active' && $account['role'] === 'Student' &&
    findUserById($pdo, $tutor)['role'] === 'Tutor', 'UM-01 Student and Tutor activate on registration');
check(denied(fn() => registerUser($pdo, $input)) &&
    denied(fn() => registerUser($pdo, array_replace($input, ['role' => 'Administrator']))) &&
    denied(fn() => registerUser($pdo, array_replace($input, ['confirmation' => 'different']))) &&
    (int)$pdo->query('SELECT count(*) FROM users')->fetchColumn() === 3,
    'UM-01 rejects duplicate, privileged role and password mismatch');
check($account['email'] === 'alex@example.edu' && !isset($account['password_hash']), 'UM-02 safe account projection');
check(count(searchUsers($pdo, 'ALEX', 'Student', 'Active')) === 1 &&
    searchUsers($pdo, 'missing', '', '') === [], 'UM-03 combined search and empty result');
updateOwnProfile($pdo, $student, ['full_name' => 'Alex Updated', 'email' => 'updated@example.edu', 'role' => 'Administrator']);
$account = findUserById($pdo, $student);
check($account['full_name'] === 'Alex Updated' && $account['email'] === 'updated@example.edu' &&
    $account['role'] === 'Student' &&
    denied(fn() => updateOwnProfile($pdo, $student, ['full_name' => 'Invalid', 'email' => 'TUTOR@example.edu'])),
    'UM-04 updates name/email, blocks role modification and duplicate email');

$login = ['email' => $account['email'], 'password' => $password];
check(authenticateUser($pdo, $login)['user_id'] === $student &&
    authenticateUser($pdo, array_replace($login, ['password' => 'wrong'])) === false,
    'UM-09 checks credentials');
$change = ['current_password' => $password, 'password' => 'a-new-test-password', 'confirmation' => 'a-new-test-password'];
check(denied(fn() => changeOwnPassword($pdo, $account, array_replace($change, ['current_password' => 'wrong']))) &&
    denied(fn() => changeOwnPassword($pdo, $account, array_replace($change, ['confirmation' => 'wrong']))),
    'UM-05 rejects wrong current password and mismatched confirmation');
changeOwnPassword($pdo, $account, $change);
check(authenticateUser($pdo, $login) === false &&
    authenticateUser($pdo, array_replace($login, ['password' => $change['password']]))['user_id'] === $student &&
    (int)findUserById($pdo, $student)['session_version'] === 1,
    'UM-05 replaces the hash and invalidates prior sessions');

check(denied(fn() => manageUser($pdo, $student, $tutor, 'role', ['value' => 'Administrator'])) &&
    denied(fn() => manageUser($pdo, $admin, $admin, 'role', ['value' => 'Tutor'])),
    'UM-06 blocks non-admin and last-admin demotion');
manageUser($pdo, $admin, $tutor, 'role', ['value' => 'Student']);
check(findUserById($pdo, $tutor)['role'] === 'Student', 'UM-06 administrator changes role');
manageUser($pdo, $admin, $tutor, 'status', ['value' => 'Inactive']);
check(authenticateUser($pdo, ['email' => 'tutor@example.edu', 'password' => $password]) === false &&
    denied(fn() => manageUser($pdo, $admin, $admin, 'status', ['value' => 'Inactive'])) &&
    denied(fn() => manageUser($pdo, $admin, $tutor, 'status', ['value' => 'Inactive'])),
    'UM-07 inactive login, invalid transition and last-admin suspension denied');
manageUser($pdo, $admin, $tutor, 'status', ['value' => 'Active']);
check(findUserById($pdo, $tutor)['status'] === 'Active', 'UM-07 administrator reactivates account');

$legacy = findRequest($pdo, 1);
check(decodeTopic($legacy['topic'])['reason'] === 'Original plain-text topic' &&
    findRequest($pdo, 1, $student) === false, 'Migration preserves legacy records without assigning ownership');
$requestInput = [
    'course' => 'IS 6495', 'reason' => 'Please help with practice problems.', 'topics' => ['Practice problems'],
    'preferreddate' => (new DateTimeImmutable('+7 days'))->format('Y-m-d'), 'preferredtime' => 'Morning',
    'student_email' => 'legacy@example.edu',
];
$id = submitRequest($pdo, $requestInput, findUserById($pdo, $student));
check(findRequest($pdo, $id, $student)['student_email'] === 'updated@example.edu' &&
    listRequests($pdo, 'IS 6495', 'Open', $tutor) === [] &&
    !cancelOpenRequest($pdo, $id, $tutor),
    'UC01/UC02 ownership derives from the account and scopes reads and writes');

putenv('LAB7_ALLOW_ACCOUNT_DELETION=0');
check(denied(fn() => manageUser($pdo, $admin, $tutor, 'delete', [])) &&
    findUserById($pdo, $tutor) !== false, 'UM-08 deletion disabled by default');
putenv('LAB7_ALLOW_ACCOUNT_DELETION=1');
check(denied(fn() => manageUser($pdo, $admin, $student, 'delete', [])) &&
    denied(fn() => manageUser($pdo, $admin, $admin, 'delete', [])) &&
    findRequest($pdo, $id, $student) !== false, 'UM-08 retained requests and last administrator block deletion');
manageUser($pdo, $admin, $tutor, 'delete', []);
check(findUserById($pdo, $tutor) === false, 'UM-08 approved policy permits deletion of eligible account');

echo "Results: $passed passed, $failed failed\n";
exit($failed ? 1 : 0);
