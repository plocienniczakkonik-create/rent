<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';

// Autoloader
function autoload_fleet_classes($className)
{
    $classFile = __DIR__ . '/classes/' . $className . '.php';
    if (file_exists($classFile)) {
        require_once $classFile;
    }
}
spl_autoload_register('autoload_fleet_classes');

$pdo = db();
$fleetManager = new FleetManager($pdo);

echo "Fleet Management enabled: " . ($fleetManager->isEnabled() ? "YES" : "NO") . "\n";

if (!$fleetManager->isEnabled()) {
    echo "System Fleet Management jest wyłączony.\n";
    echo "Sprawdzamy ustawienia w bazie danych...\n";

    // Sprawdź czy istnieje tabela shop_settings
    try {
        $stmt = $pdo->query("SELECT * FROM shop_settings WHERE setting_key = 'fleet_enabled'");
        $setting = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($setting) {
            echo "Ustawienie fleet_enabled: " . $setting['setting_value'] . "\n";
        } else {
            echo "Brak ustawienia fleet_enabled w shop_settings\n";

            // Dodaj ustawienie
            $stmt = $pdo->prepare("INSERT INTO shop_settings (setting_key, setting_value) VALUES ('fleet_enabled', '1')");
            $stmt->execute();
            echo "Dodano ustawienie fleet_enabled = 1\n";
        }
    } catch (Exception $e) {
        echo "Błąd sprawdzania ustawień: " . $e->getMessage() . "\n";
    }
} else {
    echo "System Fleet Management jest włączony!\n";

    // Test lokalizacji
    $locations = $fleetManager->getActiveLocations();
    echo "Dostępne lokalizacje: " . count($locations) . "\n";

    foreach ($locations as $location) {
        echo "- {$location['name']} ({$location['city']})\n";
    }
}
