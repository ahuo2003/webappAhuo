<?php
function migrateUserSchema(PDO $pdo): void
{
    $pdo->exec('BEGIN IMMEDIATE');
    try {
        $pdo->exec(file_get_contents(__DIR__ . '/../database/user-management.sql'));
        $columns = $pdo->query('PRAGMA table_info(students)')->fetchAll();
        if (!in_array('user_id', array_column($columns, 'name'), true)) {
            $pdo->exec('ALTER TABLE students ADD COLUMN user_id INTEGER REFERENCES users(user_id)');
        }
        $pdo->exec('CREATE UNIQUE INDEX IF NOT EXISTS students_user_id_unique ON students(user_id)');
        $pdo->exec('COMMIT');
    } catch (Throwable $e) {
        $pdo->exec('ROLLBACK');
        throw $e;
    }
}
