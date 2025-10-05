<?php
require_once 'includes/db.php';

echo "=== Wykonywanie migracji systemu Fleet Management ===\n";

$sql = file_get_contents('database_fleet_system.sql');

// Podziel na poszczególne komendy
$commands = explode(';', $sql);

$success = 0;
$errors = 0;

foreach ($commands as $command) {
    $command = trim($command);

    // Pomiń puste komendy i komentarze
    if (empty($command) || strpos($command, '--') === 0 || strpos($command, '/*') === 0) {
        continue;
    }

    try {
        db()->exec($command);
        $success++;
        echo "✓ Wykonano pomyślnie\n";
    } catch (Exception $e) {
        $errors++;
        echo "✗ Błąd: " . $e->getMessage() . "\n";
        echo "   Komenda: " . substr($command, 0, 100) . "...\n";
    }
}

echo "\n=== Podsumowanie ===\n";
echo "Pomyślne: $success\n";
echo "Błędy: $errors\n";

echo "\n=== Sprawdzenie nowych tabel ===\n";
try {
    $tables = ['locations', 'reservation_routes', 'shop_deposit_settings', 'reservation_deposits', 'location_fees_settings', 'location_fees', 'reservation_location_fees', 'vehicle_location_history'];

    foreach ($tables as $table) {
        try {
            $count = db()->query("SELECT COUNT(*) FROM $table")->fetchColumn();
            echo "Tabela $table: $count rekordów\n";
        } catch (Exception $e) {
            echo "Tabela $table: BŁĄD - " . $e->getMessage() . "\n";
        }
    }
} catch (Exception $e) {
    echo "Błąd sprawdzania tabel: " . $e->getMessage() . "\n";
}
