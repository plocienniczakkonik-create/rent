<?php
require_once 'includes/db.php';

echo "Test lokalizacji:\n";
try {
    $pdo = db();

    // Sprawdź czy tabela istnieje
    $stmt = $pdo->query("SHOW TABLES LIKE 'locations'");
    if ($stmt->rowCount() == 0) {
        echo "Tabela 'locations' nie istnieje!\n";
        exit;
    }

    // Sprawdź liczbę lokalizacji
    $result = $pdo->query('SELECT COUNT(*) FROM locations');
    $count = $result->fetchColumn();
    echo "Liczba wszystkich lokalizacji: $count\n";

    // Sprawdź aktywne lokalizacje
    $result = $pdo->query('SELECT COUNT(*) FROM locations WHERE is_active = 1');
    $activeCount = $result->fetchColumn();
    echo "Liczba aktywnych lokalizacji: $activeCount\n";

    // Pokaż przykładowe lokalizacje
    $locations = $pdo->query('SELECT id, name, city, is_active FROM locations LIMIT 5')->fetchAll();
    echo "\nPrzykładowe lokalizacje:\n";
    foreach ($locations as $loc) {
        $status = $loc['is_active'] ? 'aktywna' : 'nieaktywna';
        echo "- {$loc['name']} ({$loc['city']}) - $status\n";
    }
} catch (Exception $e) {
    echo "Błąd: " . $e->getMessage() . "\n";
}
