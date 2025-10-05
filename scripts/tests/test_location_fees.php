<?php
// Test minimalny - sprawdzenie czy podstawowe rzeczy działają
echo "Test minimalnej strony location-fees:\n\n";

try {
    $rootDir = dirname(dirname(dirname(__DIR__)));
    echo "Root dir: $rootDir\n";

    echo "Test 1: Ładowanie config...\n";
    require_once $rootDir . '/includes/config.php';
    echo "✅ Config loaded\n";

    echo "Test 2: Ładowanie db...\n";
    require_once $rootDir . '/includes/db.php';
    $pdo = db();
    echo "✅ Database connected\n";

    echo "Test 3: Ładowanie klasy LocationFeeManager...\n";
    require_once $rootDir . '/classes/LocationFeeManager.php';
    $locationFeeManager = new LocationFeeManager($pdo);
    echo "✅ LocationFeeManager created\n";

    echo "Test 4: Pobieranie opłat...\n";
    $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM location_fees WHERE is_active = 1");
    $count = $stmt->fetchColumn();
    echo "✅ Found $count active location fees\n";

    echo "\n🎉 Wszystkie testy przeszły - strona powinna działać!\n";
} catch (Exception $e) {
    echo "❌ Błąd: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
}
