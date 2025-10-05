<?php
require_once 'includes/db.php';

echo "=== Wykonywanie migracji Fleet Management - Część podstawowa ===\n";

// Część 1: Podstawowe tabele
$sql1 = file_get_contents('database_fleet_basic.sql');
$commands1 = array_filter(explode(';', $sql1), function ($cmd) {
    $cmd = trim($cmd);
    return !empty($cmd) && strpos($cmd, '--') !== 0 && strpos($cmd, '/*') !== 0;
});

echo "Część 1: Tworzenie tabel i kolumn...\n";
$success1 = 0;
$errors1 = 0;

foreach ($commands1 as $command) {
    $command = trim($command);
    if (empty($command)) continue;

    try {
        db()->exec($command);
        $success1++;
        echo "✓ ";
    } catch (Exception $e) {
        $errors1++;
        echo "✗ Błąd: " . $e->getMessage() . "\n";
    }
}

echo "\nCzęść 1 - Pomyślne: $success1, Błędy: $errors1\n\n";

// Część 2: Dane
echo "Część 2: Dodawanie danych...\n";
$sql2 = file_get_contents('database_fleet_data.sql');
$commands2 = array_filter(explode(';', $sql2), function ($cmd) {
    $cmd = trim($cmd);
    return !empty($cmd) && strpos($cmd, '--') !== 0 && strpos($cmd, '/*') !== 0;
});

$success2 = 0;
$errors2 = 0;

foreach ($commands2 as $command) {
    $command = trim($command);
    if (empty($command)) continue;

    try {
        db()->exec($command);
        $success2++;
        echo "✓ ";
    } catch (Exception $e) {
        $errors2++;
        echo "✗ Błąd: " . $e->getMessage() . "\n";
    }
}

echo "\nCzęść 2 - Pomyślne: $success2, Błędy: $errors2\n\n";

// Sprawdzenie wyniku
echo "=== Sprawdzenie nowych tabel ===\n";
$tables = [
    'locations' => 'Lokalizacje',
    'reservation_routes' => 'Trasy rezerwacji',
    'vehicle_location_history' => 'Historia lokalizacji',
    'shop_deposit_settings' => 'Ustawienia kaucji',
    'reservation_deposits' => 'Kaucje rezerwacji',
    'location_fees_settings' => 'Ustawienia opłat',
    'location_fees' => 'Opłaty lokalizacyjne',
    'reservation_location_fees' => 'Opłaty w rezerwacjach'
];

foreach ($tables as $table => $name) {
    try {
        $count = db()->query("SELECT COUNT(*) FROM $table")->fetchColumn();
        echo "✓ $name ($table): $count rekordów\n";
    } catch (Exception $e) {
        echo "✗ $name ($table): BŁĄD\n";
    }
}

echo "\n=== Sprawdzenie nowych kolumn w products ===\n";
try {
    $columns = db()->query("DESCRIBE products")->fetchAll();
    $newColumns = ['deposit_enabled', 'deposit_type', 'deposit_amount'];

    foreach ($columns as $col) {
        if (in_array($col['Field'], $newColumns)) {
            echo "✓ products.{$col['Field']}: {$col['Type']}\n";
        }
    }
} catch (Exception $e) {
    echo "✗ Błąd sprawdzania kolumn products\n";
}

echo "\n=== Sprawdzenie nowych kolumn w vehicles ===\n";
try {
    $columns = db()->query("DESCRIBE vehicles")->fetchAll();
    foreach ($columns as $col) {
        if ($col['Field'] === 'current_location_id') {
            echo "✓ vehicles.current_location_id: {$col['Type']}\n";
            break;
        }
    }
} catch (Exception $e) {
    echo "✗ Błąd sprawdzania kolumn vehicles\n";
}

echo "\n=== PODSUMOWANIE ===\n";
echo "Łącznie pomyślnych: " . ($success1 + $success2) . "\n";
echo "Łącznie błędów: " . ($errors1 + $errors2) . "\n";
echo "System Fleet Management został " . (($errors1 + $errors2) === 0 ? "POMYŚLNIE" : "CZĘŚCIOWO") . " zaimplementowany.\n";
