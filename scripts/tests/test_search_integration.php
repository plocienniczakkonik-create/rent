<?php
// Test integracji wyszukiwania z Fleet Management
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';

// Inicializacja PDO
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Autoloader dla klas Fleet Management
function autoload_fleet_classes($className)
{
    $classFile = __DIR__ . '/classes/' . $className . '.php';
    if (file_exists($classFile)) {
        require_once $classFile;
    }
}
spl_autoload_register('autoload_fleet_classes');

echo "<h1>Test Integracji Wyszukiwania z Fleet Management</h1>\n";

try {
    $fleetManager = new FleetManager($pdo);

    echo "<h2>1. Test lokalizacji w Fleet Management</h2>\n";
    $locations = $fleetManager->getActiveLocations();
    echo "<p>Dostępne lokalizacje (" . count($locations) . "):</p>\n";
    echo "<ul>\n";
    foreach ($locations as $location) {
        $displayName = $location['name'] . ' (' . $location['city'] . ')';
        echo "<li>ID: {$location['id']}, Display: '{$displayName}', Name: '{$location['name']}', City: '{$location['city']}'</li>\n";
    }
    echo "</ul>\n";

    echo "<h2>2. Test dostępności pojazdów w lokalizacjach</h2>\n";

    // Pobierz wszystkie pojazdy
    $stmt = $pdo->query("SELECT id, name FROM products WHERE category = 'vehicle' LIMIT 5");
    $vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($vehicles as $vehicle) {
        echo "<h3>Pojazd: {$vehicle['name']} (ID: {$vehicle['id']})</h3>\n";
        echo "<ul>\n";

        foreach ($locations as $location) {
            $isAvailable = $fleetManager->isProductAvailableInLocation($vehicle['id'], $location['id']);
            $status = $isAvailable ? "DOSTĘPNY" : "NIEDOSTĘPNY";
            $displayName = $location['name'] . ' (' . $location['city'] . ')';
            echo "<li>{$displayName}: <strong>{$status}</strong></li>\n";
        }
        echo "</ul>\n";
    }

    echo "<h2>3. Test funkcji filtrowania</h2>\n";

    // Symulacja danych wyszukiwania
    $sample_products = [];
    foreach ($vehicles as $vehicle) {
        $sample_products[] = [
            'id' => $vehicle['id'],
            'name' => $vehicle['name'],
            'category' => 'vehicle'
        ];
    }

    // Test filtrowania dla pierwszej lokalizacji
    if (!empty($locations)) {
        $test_location = $locations[0];
        $pickup_location = $test_location['name'] . ' (' . $test_location['city'] . ')';

        echo "<h3>Test filtrowania dla lokalizacji: {$pickup_location}</h3>\n";

        // Załaduj funkcję filtrowania
        ob_start();
        include __DIR__ . '/pages/includes/search.php';
        ob_end_clean();

        if (function_exists('filter_products_by_location')) {
            $filtered_products = filter_products_by_location($sample_products, $pickup_location);

            echo "<p>Pojazdy przed filtrowaniem: " . count($sample_products) . "</p>\n";
            echo "<p>Pojazdy po filtrowaniu: " . count($filtered_products) . "</p>\n";

            echo "<h4>Dostępne pojazdy w lokalizacji {$pickup_location}:</h4>\n";
            if (!empty($filtered_products)) {
                echo "<ul>\n";
                foreach ($filtered_products as $product) {
                    echo "<li>{$product['name']} (ID: {$product['id']})</li>\n";
                }
                echo "</ul>\n";
            } else {
                echo "<p>Brak dostępnych pojazdów w tej lokalizacji.</p>\n";
            }
        } else {
            echo "<p><strong>BŁĄD:</strong> Funkcja filter_products_by_location nie została załadowana.</p>\n";
        }
    }

    echo "<h2>4. Test różnych formatów nazw lokalizacji</h2>\n";

    if (!empty($locations)) {
        $test_location = $locations[0];
        $test_formats = [
            $test_location['name'] . ' (' . $test_location['city'] . ')',  // Pełny format
            $test_location['name'],                                        // Tylko nazwa
            $test_location['city'],                                        // Tylko miasto
            'Nieistniejąca lokalizacja'                                   // Test błędnej lokalizacji
        ];

        foreach ($test_formats as $format) {
            echo "<h4>Test format: '{$format}'</h4>\n";

            // Znajdź ID lokalizacji
            $locationId = null;
            foreach ($locations as $location) {
                $displayName = $location['name'] . ' (' . $location['city'] . ')';
                if (
                    $displayName === $format ||
                    $location['name'] === $format ||
                    $location['city'] === $format
                ) {
                    $locationId = $location['id'];
                    break;
                }
            }

            if ($locationId) {
                echo "<p>✓ Znaleziono lokalizację ID: {$locationId}</p>\n";

                // Test dostępności pierwszego pojazdu
                if (!empty($vehicles)) {
                    $available = $fleetManager->isProductAvailableInLocation($vehicles[0]['id'], $locationId);
                    $status = $available ? "DOSTĘPNY" : "NIEDOSTĘPNY";
                    echo "<p>Pojazd {$vehicles[0]['name']}: {$status}</p>\n";
                }
            } else {
                echo "<p>✗ Nie znaleziono lokalizacji</p>\n";
            }
        }
    }
} catch (Exception $e) {
    echo "<p><strong>BŁĄD:</strong> " . htmlspecialchars($e->getMessage()) . "</p>\n";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>\n";
}

echo "<h2>5. Test konfiguracji Fleet Management</h2>\n";
$stmt = $pdo->query("SELECT config_key, config_value FROM shop_general WHERE config_key LIKE 'fleet_%'");
$configs = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!empty($configs)) {
    echo "<ul>\n";
    foreach ($configs as $config) {
        echo "<li>{$config['config_key']}: {$config['config_value']}</li>\n";
    }
    echo "</ul>\n";
} else {
    echo "<p>Brak konfiguracji Fleet Management w bazie danych.</p>\n";
}
