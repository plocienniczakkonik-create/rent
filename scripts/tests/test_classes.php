<?php

/**
 * Autoloader dla klas Fleet Management System
 */

spl_autoload_register(function ($className) {
    $classesDir = __DIR__ . '/classes/';
    $classFile = $classesDir . $className . '.php';

    if (file_exists($classFile)) {
        require_once $classFile;
    }
});

// Testowy skrypt sprawdzający działanie klas
if (basename($_SERVER['SCRIPT_NAME']) === 'test_classes.php') {
    require_once 'includes/db.php';

    echo "=== Test klas Fleet Management System ===\n\n";

    // Test FleetManager
    echo "1. Test FleetManager:\n";
    try {
        $fleetManager = new FleetManager();
        $isEnabled = $fleetManager->isEnabled();
        echo "   - System włączony: " . ($isEnabled ? 'TAK' : 'NIE') . "\n";

        $locations = $fleetManager->getActiveLocations();
        echo "   - Liczba lokalizacji: " . count($locations) . "\n";

        $stats = $fleetManager->getLocationStats();
        echo "   - Statystyki lokalizacji: " . count($stats) . " rekordów\n";

        echo "   ✓ FleetManager działa poprawnie\n";
    } catch (Exception $e) {
        echo "   ✗ Błąd FleetManager: " . $e->getMessage() . "\n";
    }

    echo "\n2. Test DepositManager:\n";
    try {
        $depositManager = new DepositManager();
        $isEnabled = $depositManager->isEnabled();
        echo "   - System kaucji włączony: " . ($isEnabled ? 'TAK' : 'NIE') . "\n";

        $settings = $depositManager->getSettings();
        echo "   - Liczba ustawień: " . count($settings) . "\n";

        $defaultSettings = $depositManager->getDefaultDepositSettings();
        echo "   - Domyślny typ: " . $defaultSettings['type'] . "\n";
        echo "   - Domyślna kwota: " . $defaultSettings['amount'] . " PLN\n";

        echo "   ✓ DepositManager działa poprawnie\n";
    } catch (Exception $e) {
        echo "   ✗ Błąd DepositManager: " . $e->getMessage() . "\n";
    }

    echo "\n3. Test LocationFeeManager:\n";
    try {
        $locationFeeManager = new LocationFeeManager();
        $isEnabled = $locationFeeManager->isEnabled();
        echo "   - System opłat włączony: " . ($isEnabled ? 'TAK' : 'NIE') . "\n";

        $fees = $locationFeeManager->getAllLocationFees();
        echo "   - Liczba opłat: " . count($fees) . "\n";

        $defaultFee = $locationFeeManager->getDefaultFeeAmount();
        echo "   - Domyślna opłata: " . $defaultFee . " PLN\n";

        // Test obliczania opłaty (Warszawa -> Kraków)
        if (count($fees) > 0) {
            $testFee = $locationFeeManager->calculateLocationFee(1, 2);
            echo "   - Test opłaty Warszawa->Kraków: " .
                ($testFee['enabled'] ? $testFee['amount'] . " PLN" : "brak") . "\n";
        }

        echo "   ✓ LocationFeeManager działa poprawnie\n";
    } catch (Exception $e) {
        echo "   ✗ Błąd LocationFeeManager: " . $e->getMessage() . "\n";
    }

    echo "\n=== Wszystkie klasy przetestowane ===\n";
}
