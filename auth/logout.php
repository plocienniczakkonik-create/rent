<?php
require_once __DIR__ . '/auth.php';
$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $p["path"], $p["domain"], $p["secure"], $p["httponly"]);
}
session_destroy();

// było: header('Location: pages/login.php?out=1');
header('Location: ' . BASE_URL . '/index.php?out=1'); // na stronę główną z info "wylogowano"
exit;
