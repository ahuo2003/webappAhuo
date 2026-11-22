<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../services/userService.php';
require_once __DIR__ . '/../services/requestService.php';

function startSecureSession(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || getenv('LAB7_SECURE_COOKIE') === '1';
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_name('LAB7SESSION');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $https,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    if (!session_start()) {
        stopRequest(500, 'Unable to start your session.');
    }
}

function stopRequest(int $status, string $message): void
{
    http_response_code($status);
    header('Content-Type: text/plain; charset=UTF-8');
    exit($message);
}

function redirectTo(string $path): void
{
    header('Location: ' . $path, true, 303);
    exit;
}

function logoutSession(): void
{
    $_SESSION = [];
    $cookie = session_get_cookie_params();
    unset($cookie['lifetime']);
    $cookie['expires'] = time() - 3600;
    setcookie(session_name(), '', $cookie);
    session_destroy();
}

function startLogin(array $user): void
{
    if (!session_regenerate_id(true)) {
        stopRequest(500, 'Unable to establish your session.');
    }
    $_SESSION = [
        'user_id' => (int)$user['user_id'],
        'session_version' => (int)$user['session_version'],
        'last_activity' => time(),
    ];
    csrfToken();
}

function requireLogin(PDO $pdo): array
{
    startSecureSession();
    if (!isset($_SESSION['user_id'])) {
        redirectTo('login.php');
    }
    if (time() - ($_SESSION['last_activity'] ?? 0) >= 1200) {
        logoutSession();
        redirectTo('login.php?expired=1');
    }
    $user = findUserById($pdo, $_SESSION['user_id']);
    if (!$user || $user['status'] !== 'Active' ||
        (int)$user['session_version'] !== ($_SESSION['session_version'] ?? null)) {
        logoutSession();
        redirectTo('login.php?expired=1');
    }
    $_SESSION['last_activity'] = time();
    return $user;
}

function requireRole(array $user, array $roles): void
{
    if (!in_array($user['role'], $roles, true)) {
        stopRequest(403, 'You are not authorized to perform this action.');
    }
}

function csrfToken(): string
{
    return $_SESSION['csrf_token'] ??= bin2hex(random_bytes(32));
}

function csrfField(): string
{
    return '<input type="hidden" name="csrf_token" value="' . escapeHtml(csrfToken()) . '">';
}

function verifyCsrfToken(): void
{
    $expected = $_SESSION['csrf_token'] ?? '';
    if ($expected === '' || !hash_equals($expected, inputString($_POST, 'csrf_token'))) {
        stopRequest(403, 'Invalid form token. Reload the page and try again.');
    }
}

function isFormPost(): bool
{
    $method = $_SERVER['REQUEST_METHOD'];
    if ($method === 'POST') {
        verifyCsrfToken();
        return true;
    }
    if ($method !== 'GET') {
        stopRequest(405, 'Method not allowed.');
    }
    return false;
}

function flash(string $message): void
{
    $_SESSION['flash'] = $message;
}

function showFlash(): void
{
    if (isset($_SESSION['flash'])) {
        echo '<p class="alert alert-success" role="status">' . escapeHtml($_SESSION['flash']) . '</p>';
        unset($_SESSION['flash']);
    }
}
