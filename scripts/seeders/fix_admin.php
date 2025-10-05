<?php
require_once 'includes/db.php';

echo "=== Struktura kolumny role ===\n";
$result = db()->query('SHOW COLUMNS FROM users LIKE "role"');
$column = $result->fetch();
echo "Type: " . $column['Type'] . "\n";

echo "\n=== Próba aktualizacji użytkownika ===\n";
try {
    // Najpierw zmień strukturę kolumny
    $db = db();
    $db->exec("ALTER TABLE users MODIFY COLUMN role ENUM('client','staff','admin') DEFAULT 'client'");
    echo "✓ Dodano rolę 'admin' do ENUM\n";

    // Ustaw użytkownika jako admin
    $stmt = $db->prepare("UPDATE users SET role = 'admin', first_name = 'Admin', last_name = 'Administrator' WHERE email = 'plocienniczak.konik@gmail.com'");
    $stmt->execute();
    echo "✓ Ustawiono użytkownika jako admin\n";

    // Sprawdź rezultat
    $stmt = $db->prepare('SELECT * FROM users WHERE email = ?');
    $stmt->execute(['plocienniczak.konik@gmail.com']);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    echo "\n=== Po aktualizacji ===\n";
    echo "Rola: '" . $user['role'] . "'\n";
    echo "Imię: '" . $user['first_name'] . "'\n";
    echo "Nazwisko: '" . $user['last_name'] . "'\n";
} catch (Exception $e) {
    echo "Błąd: " . $e->getMessage() . "\n";
}
