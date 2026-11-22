<?php
const USER_FIELDS = 'user_id, full_name, email, role, status, created_at, session_version';

function findUserById(PDO $pdo, int $id)
{
    $stmt = $pdo->prepare('SELECT ' . USER_FIELDS . ' FROM users WHERE user_id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function findLoginUser(PDO $pdo, string $email)
{
    $stmt = $pdo->prepare('SELECT ' . USER_FIELDS . ', password_hash FROM users WHERE email = ?');
    $stmt->execute([$email]);
    return $stmt->fetch();
}

function insertUser(PDO $pdo, string $name, string $email, string $hash, string $role): int
{
    $stmt = $pdo->prepare('INSERT INTO users (full_name, email, password_hash, role) VALUES (?, ?, ?, ?)
        ON CONFLICT(email) DO NOTHING');
    $stmt->execute([$name, $email, $hash, $role]);
    if ($stmt->rowCount() !== 1) {
        throw new DomainException('That email address is already in use.');
    }
    return (int)$pdo->lastInsertId();
}

function searchUsers(PDO $pdo, string $query, string $role, string $status): array
{
    $stmt = $pdo->prepare('SELECT ' . USER_FIELDS . ' FROM users
        WHERE (instr(lower(full_name), lower(?)) > 0 OR instr(lower(email), lower(?)) > 0 OR ? = \'\')
        AND (? = \'\' OR role = ?) AND (? = \'\' OR status = ?) ORDER BY full_name COLLATE NOCASE, user_id');
    $stmt->execute([$query, $query, $query, $role, $role, $status, $status]);
    return $stmt->fetchAll();
}

function saveUserProfile(PDO $pdo, int $id, string $name, string $email): void
{
    $stmt = $pdo->prepare('UPDATE OR IGNORE users SET full_name = ?, email = ? WHERE user_id = ?');
    $stmt->execute([$name, $email, $id]);
    if ($stmt->rowCount() !== 1) {
        throw new DomainException('That email address is already in use.');
    }
}

function replacePasswordHash(PDO $pdo, int $id, string $oldHash, string $newHash, bool $invalidate): bool
{
    $stmt = $pdo->prepare('UPDATE users SET password_hash = ?, session_version = session_version + ?
        WHERE user_id = ? AND password_hash = ? AND status = \'Active\'');
    $stmt->execute([$newHash, (int)$invalidate, $id, $oldHash]);
    return $stmt->rowCount() === 1;
}

function activeAdminCount(PDO $pdo): int
{
    return (int)$pdo->query("SELECT count(*) FROM users WHERE role = 'Administrator' AND status = 'Active'")->fetchColumn();
}

function saveUserRole(PDO $pdo, int $id, string $role): void
{
    $pdo->prepare('UPDATE users SET role = ? WHERE user_id = ?')->execute([$role, $id]);
}

function saveUserStatus(PDO $pdo, int $id, string $status): void
{
    $pdo->prepare('UPDATE users SET status = ?, session_version = session_version + 1 WHERE user_id = ?')
        ->execute([$status, $id]);
}

function userHasRequests(PDO $pdo, int $id): bool
{
    $stmt = $pdo->prepare('SELECT 1 FROM tutoring_requests r JOIN students s ON r.student_id = s.student_id
        WHERE s.user_id = ? LIMIT 1');
    $stmt->execute([$id]);
    return (bool)$stmt->fetchColumn();
}

function deleteUser(PDO $pdo, int $id): void
{
    $pdo->prepare('DELETE FROM students WHERE user_id = ?')->execute([$id]);
    $pdo->prepare('DELETE FROM users WHERE user_id = ?')->execute([$id]);
}
