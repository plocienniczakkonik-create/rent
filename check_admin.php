<?php
require_once 'includes/db.php';

echo "=== Sprawdzenie użytkownika plocienniczak.konik@gmail.com ===\n";
$stmt = db()->prepare('SELECT * FROM users WHERE email = ?');
$stmt->execute(['plocienniczak.konik@gmail.com']);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user) {
    echo "ID: " . $user['id'] . "\n";
    echo "Email: " . $user['email'] . "\n";
    echo "Rola: '" . $user['role'] . "'\n";
    echo "Aktywny: " . $user['is_active'] . "\n";
    echo "Imię: '" . $user['first_name'] . "'\n";
    echo "Nazwisko: '" . $user['last_name'] . "'\n";
} else {
    echo "Użytkownik nie znaleziony\n";
}
?>