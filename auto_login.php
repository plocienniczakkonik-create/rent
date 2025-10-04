<?php
// auto_login.php - automatyczne logowanie do testów
session_start();

require_once 'includes/db.php';

$email = 'test2@example.com';
$password = 'test1234';

echo "=== Próba automatycznego logowania ===\n";
echo "Email: $email\n";

$stmt = db()->prepare('SELECT id, email, password_hash, role, first_name, last_name FROM users WHERE email = ? AND is_active = 1');
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo "BŁĄD: Nie znaleziono użytkownika\n";
    exit;
}

if (!password_verify($password, $user['password_hash'])) {
    echo "BŁĄD: Nieprawidłowe hasło\n";
    exit;
}

// Logowanie
$_SESSION['user_id'] = $user['id'];

echo "SUKCES: Zalogowano jako {$user['email']} (role: {$user['role']})\n";
echo "Session ID: " . session_id() . "\n";
echo "User ID w sesji: " . $_SESSION['user_id'] . "\n";

// Przekierowanie na dashboard
$redirect_url = 'http://localhost/rental/index.php?page=dashboard-staff';
echo "Przekierowanie na: $redirect_url\n";
echo "Możesz teraz otworzyć ten URL w przeglądarce.\n";

// Opcjonalnie: automatyczne przekierowanie (odkomentuj jeśli chcesz)
// header("Location: $redirect_url");
// exit;
