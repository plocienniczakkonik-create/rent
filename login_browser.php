<?php
// login_browser.php - logowanie dla przeglądarki
session_start();

require_once 'includes/db.php';

$email = 'test2@example.com';
$password = 'test1234';

$stmt = db()->prepare('SELECT id, email, password_hash, role, first_name, last_name FROM users WHERE email = ? AND is_active = 1');
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user && password_verify($password, $user['password_hash'])) {
    $_SESSION['user_id'] = $user['id'];

    echo "<!DOCTYPE html>";
    echo "<html><head><title>Logowanie</title></head><body>";
    echo "<h1>Zalogowano pomyślnie!</h1>";
    echo "<p>Użytkownik: {$user['email']} (rola: {$user['role']})</p>";
    echo "<p>Session ID: " . session_id() . "</p>";
    echo "<p><a href='index.php?page=dashboard-staff'>Przejdź do panelu pracownika</a></p>";
    echo "<p><a href='test_dashboard_direct.php'>Przejdź do testu dashboard</a></p>";
    echo "</body></html>";
} else {
    echo "<!DOCTYPE html>";
    echo "<html><head><title>Błąd</title></head><body>";
    echo "<h1>Błąd logowania!</h1>";
    echo "<p>Nieprawidłowe dane logowania.</p>";
    echo "</body></html>";
}
