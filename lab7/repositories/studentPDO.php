<?php
function findStudentByEmail($pdo, $email) {
    $stmt = $pdo->prepare('SELECT student_id, student_name, student_email FROM students WHERE student_email = ?');
    $stmt->execute([$email]); return $stmt->fetch();
}
function insertStudentIfAbsent($pdo, $name, $email) {
    $stmt = $pdo->prepare('INSERT OR IGNORE INTO students(student_name,student_email) VALUES(?,?)');
    $stmt->execute([$name,$email]);
}

function ensureAccountStudent(PDO $pdo, array $user): int
{
    $stmt = $pdo->prepare('INSERT INTO students (student_name, student_email, user_id) VALUES (?, ?, ?)
        ON CONFLICT DO NOTHING');
    $stmt->execute([$user['full_name'], $user['email'], $user['user_id']]);
    $stmt = $pdo->prepare('SELECT student_id FROM students WHERE user_id = ?');
    $stmt->execute([$user['user_id']]);
    $id = $stmt->fetchColumn();
    if ($id === false) {
        throw new InvalidArgumentException('This email is already attached to a tutoring record. Contact an administrator.');
    }
    return (int)$id;
}

function linkLegacyStudent(PDO $pdo, int $studentId, int $userId): bool
{
    $stmt = $pdo->prepare("UPDATE students SET user_id = ?
        WHERE student_id = ? AND user_id IS NULL
          AND EXISTS (SELECT 1 FROM users WHERE user_id = ? AND role = 'Student' AND status = 'Active')
          AND NOT EXISTS (SELECT 1 FROM students WHERE user_id = ?)");
    $stmt->execute([$userId, $studentId, $userId, $userId]);
    return $stmt->rowCount() === 1;
}
