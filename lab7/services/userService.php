<?php
require_once __DIR__ . '/../repositories/userPDO.php';

const USER_ROLES = ['Student', 'Tutor', 'Administrator'];
const USER_STATUSES = ['Active', 'Inactive'];

function inputString(array $input, string $key): string
{
    return is_string($input[$key] ?? null) ? $input[$key] : '';
}

function accountIdentity(array $input): array
{
    $name = trim(inputString($input, 'full_name'));
    $email = strtolower(trim(inputString($input, 'email')));
    if ($name === '' || strlen($name) > 120 || preg_match('/[\x00-\x1f\x7f]/', $name)) {
        throw new DomainException('Enter a full name of 1 to 120 bytes.');
    }
    if (strlen($email) > 254 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new DomainException('Enter a valid email address.');
    }
    return [$name, $email];
}

function validPasswordInput(string $password): bool
{
    return strlen($password) <= 72 && strpos($password, "\0") === false;
}

function newPassword(array $input): string
{
    $password = inputString($input, 'password');
    if (strlen($password) < 12 || !validPasswordInput($password)) {
        throw new DomainException('Use a password between 12 and 72 bytes.');
    }
    if ($password !== inputString($input, 'confirmation')) {
        throw new DomainException('The passwords must match.');
    }
    return $password;
}

function registerUser(PDO $pdo, array $input): int
{
    [$name, $email] = accountIdentity($input);
    $role = inputString($input, 'role');
    if (!in_array($role, ['Student', 'Tutor'], true)) {
        throw new DomainException('Choose Student or Tutor.');
    }
    return insertUser($pdo, $name, $email, password_hash(newPassword($input), PASSWORD_DEFAULT), $role);
}

function updateOwnProfile(PDO $pdo, int $id, array $input): void
{
    [$name, $email] = accountIdentity($input);
    saveUserProfile($pdo, $id, $name, $email);
}

function changeOwnPassword(PDO $pdo, array $user, array $input): void
{
    $record = findLoginUser($pdo, $user['email']);
    $current = inputString($input, 'current_password');
    if (!$record || (int)$record['user_id'] !== (int)$user['user_id'] ||
        !validPasswordInput($current) || !password_verify($current, $record['password_hash'])) {
        throw new DomainException('The current password is incorrect.');
    }
    $hash = password_hash(newPassword($input), PASSWORD_DEFAULT);
    if (!replacePasswordHash($pdo, (int)$user['user_id'], $record['password_hash'], $hash, true)) {
        throw new DomainException('Your account changed. Reload the page and try again.');
    }
}

function authenticateUser(PDO $pdo, array $input)
{
    $email = strtolower(trim(inputString($input, 'email')));
    $password = inputString($input, 'password');
    $user = findLoginUser($pdo, $email);
    if (!validPasswordInput($password)) {
        return false;
    }
    // Verify a fixed dummy hash for unknown accounts to perform comparable work.
    $hash = $user['password_hash'] ?? '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.';
    if (!password_verify($password, $hash) || !$user || $user['status'] !== 'Active') {
        return false;
    }
    if (password_needs_rehash($hash, PASSWORD_DEFAULT)) {
        replacePasswordHash($pdo, (int)$user['user_id'], $hash, password_hash($password, PASSWORD_DEFAULT), false);
    }
    unset($user['password_hash']);
    return $user;
}

// Serialize the administrator check, last-admin rule and write under one SQLite write lock.
function manageUser(PDO $pdo, int $actorId, int $id, string $action, array $input): void
{
    $pdo->exec('BEGIN IMMEDIATE');
    try {
        $actor = findUserById($pdo, $actorId);
        if (!$actor || $actor['role'] !== 'Administrator' || $actor['status'] !== 'Active') {
            throw new DomainException('Administrator access is required.');
        }
        $target = findUserById($pdo, $id);
        if (!$target) {
            throw new DomainException('Account not found.');
        }
        $actionValue = inputString($input, 'value');
        if ($action === 'role') {
            if (!in_array($actionValue, USER_ROLES, true) || $actionValue === $target['role']) {
                throw new DomainException('Choose a different valid role.');
            }
        } elseif ($action === 'status') {
            if (!in_array($actionValue, USER_STATUSES, true) || $actionValue === $target['status']) {
                throw new DomainException('Choose a valid status transition.');
            }
        } elseif ($action === 'delete') {
            if (userHasRequests($pdo, $id)) {
                throw new DomainException('This account has tutoring records and cannot be deleted.');
            }
            if (getenv('LAB7_ALLOW_ACCOUNT_DELETION') !== '1') {
                throw new DomainException('Permanent deletion is disabled pending retention policy approval.');
            }
        } else {
            throw new DomainException('Invalid account action.');
        }
        if ($target['role'] === 'Administrator' && $target['status'] === 'Active' && activeAdminCount($pdo) === 1) {
            throw new DomainException('The last active administrator must remain active and retain that role.');
        }
        if ($action === 'role') {
            saveUserRole($pdo, $id, $actionValue);
        } elseif ($action === 'status') {
            saveUserStatus($pdo, $id, $actionValue);
        } else {
            deleteUser($pdo, $id);
        }
        $pdo->exec('COMMIT');
    } catch (Throwable $e) {
        $pdo->exec('ROLLBACK');
        throw $e;
    }
}
