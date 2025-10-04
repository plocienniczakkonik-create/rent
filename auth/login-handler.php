<?php
// /auth/login-handler.php
require_once __DIR__ . '/auth.php';

$BASE = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';

// helper do budowy adresów
$to = function (string $path) use ($BASE): string {
    $path = ltrim($path, '/');
    return $BASE ? ($BASE . '/' . $path) : ('../' . $path); // jesteśmy w /auth
};

$email = trim($_POST['email'] ?? '');
$pass  = $_POST['password'] ?? '';

if ($email === '' || $pass === '') {
    header('Location: ' . $to('index.php?page=login&e=empty'));
    exit;
}

// pobierz też role, żeby po sukcesie wiedzieć, dokąd przekierować
$stmt = db()->prepare("SELECT id, password_hash, is_active, role FROM users WHERE email = ?");
$stmt->execute([$email]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

$ok = $row && (int)$row['is_active'] === 1 && password_verify($pass, $row['password_hash']);
if (!$ok) {
    header('Location: ' . $to('index.php?page=login&e=invalid'));
    exit;
}

// logujemy
$_SESSION['user_id'] = (int)$row['id'];

// ustal redirect: 1) jeśli przyszedł w POST → użyj go (wewnętrzny), 2) w przeciwnym razie wg roli
$redirect = trim($_POST['redirect'] ?? '');

// jeśli ktoś podał pełny URL z domeną – odetnij domenę
if ($redirect !== '') {
    $redirect = preg_replace('#^https?://[^/]+#i', '', $redirect);
    $redirect = ltrim($redirect, '/');
}

// akceptujemy tylko ścieżki typu "index.php?..." — inaczej domyślnie kierujemy wg roli
if ($redirect === '' || !preg_match('#^index\.php(\?.*)?$#i', $redirect)) {
    $userRole = $row['role'] ?? 'client';
    $redirect = 'index.php?page=' . (in_array($userRole, ['staff', 'admin']) ? 'dashboard-staff' : 'dashboard-client');
}

header('Location: ' . $to($redirect));
exit;
