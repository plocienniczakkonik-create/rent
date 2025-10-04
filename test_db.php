<?php
require_once 'includes/db.php';

echo "=== Struktura tabeli users ===\n";
try {
    $columns = db()->query('DESCRIBE users')->fetchAll();
    foreach ($columns as $col) {
        echo "Kolumna: {$col['Field']}, Typ: {$col['Type']}\n";
    }
} catch (Exception $e) {
    echo "Błąd: " . $e->getMessage() . "\n";
}

echo "\n=== Sprawdzanie użytkowników ===\n";
try {
    $users = db()->query('SELECT id, email, role, first_name, last_name FROM users')->fetchAll();
    foreach ($users as $user) {
        echo "ID: {$user['id']}, Email: {$user['email']}, Role: {$user['role']}, Imię: {$user['first_name']}, Nazwisko: {$user['last_name']}\n";
    }
} catch (Exception $e) {
    echo "Błąd: " . $e->getMessage() . "\n";
}

echo "\n=== Sprawdzanie użytkownika test2@example.com ===\n";
try {
    $user = db()->prepare('SELECT id, email, role, first_name, last_name FROM users WHERE email = ?');
    $user->execute(['test2@example.com']);
    $userData = $user->fetch();
    if ($userData) {
        echo "Znaleziono użytkownika:\n";
        echo "ID: {$userData['id']}, Email: {$userData['email']}, Role: {$userData['role']}, Imię: {$userData['first_name']}, Nazwisko: {$userData['last_name']}\n";
    } else {
        echo "Nie znaleziono użytkownika z emailem test2@example.com\n";
    }
} catch (Exception $e) {
    echo "Błąd: " . $e->getMessage() . "\n";
}
