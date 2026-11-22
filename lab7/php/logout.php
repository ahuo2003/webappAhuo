<?php
require_once __DIR__ . '/../includes/auth.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    stopRequest(405, 'POST required.');
}
requireLogin(openDatabase());
verifyCsrfToken();
logoutSession();
redirectTo('login.php?logged_out=1');
