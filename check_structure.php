<?php
require_once 'includes/db.php';

echo "=== Struktura tabeli products ===\n";
try {
    $columns = db()->query('DESCRIBE products')->fetchAll();
    foreach ($columns as $col) {
        echo "Kolumna: {$col['Field']}, Typ: {$col['Type']}\n";
    }
} catch (Exception $e) {
    echo "Błąd: " . $e->getMessage() . "\n";
}

echo "\n=== Struktura tabeli vehicles ===\n";
try {
    $columns = db()->query('DESCRIBE vehicles')->fetchAll();
    foreach ($columns as $col) {
        echo "Kolumna: {$col['Field']}, Typ: {$col['Type']}\n";
    }
} catch (Exception $e) {
    echo "Błąd: " . $e->getMessage() . "\n";
}

echo "\n=== Struktura tabeli reservations ===\n";
try {
    $columns = db()->query('DESCRIBE reservations')->fetchAll();
    foreach ($columns as $col) {
        echo "Kolumna: {$col['Field']}, Typ: {$col['Type']}\n";
    }
} catch (Exception $e) {
    echo "Błąd: " . $e->getMessage() . "\n";
}

echo "\n=== Sprawdzenie istniejących tabel ===\n";
try {
    $tables = db()->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($tables as $table) {
        echo "Tabela: $table\n";
    }
} catch (Exception $e) {
    echo "Błąd: " . $e->getMessage() . "\n";
}
