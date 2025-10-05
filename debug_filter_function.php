<?php
// Debug funkcji filtrowania
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

// Załaduj funkcję filtrowania
ob_start();
include __DIR__ . '/pages/includes/search.php';
ob_end_clean();

$pdo = db();
$fleetManager = new FleetManager($pdo);

echo "<h1>Debug funkcji filtrowania</h1>\n";

// Test przypadki
$products = [
    ['id' => 1, 'name' => 'Toyota Corolla', 'sku' => 'CAR-COR-2022'],
    ['id' => 2, 'name' => 'VW Golf', 'sku' => 'CAR-GOL-2021'],
    ['id' => 3, 'name' => 'FIAT', 'sku' => 'F500'],
    ['id' => 4, 'name' => 'AUDI A5', 'sku' => 'aud-5'],
    ['id' => 5, 'name' => 'Fiat 800', 'sku' => '670']
];

$locations = $fleetManager->getActiveLocations();

echo "<h2>Fleet Management status:</h2>\n";
echo "<p>Enabled: " . ($fleetManager->isEnabled() ? "YES" : "NO") . "</p>\n";
echo "<p>Locations count: " . count($locations) . "</p>\n";

foreach ($locations as $location) {
    $pickup_location = $location['name'] . ' (' . $location['city'] . ')';

    echo "<h3>Test dla lokalizacji: {$pickup_location}</h3>\n";

    // Debug krok po kroku
    echo "<h4>1. Znajdowanie ID lokalizacji:</h4>\n";
    $locationId = null;
    foreach ($locations as $loc) {
        $displayName = $loc['name'] . ' (' . $loc['city'] . ')';
        echo "<p>Porównuję '{$pickup_location}' z '{$displayName}'</p>\n";
        if (
            $displayName === $pickup_location ||
            $loc['name'] === $pickup_location ||
            $loc['city'] === $pickup_location
        ) {
            $locationId = $loc['id'];
            echo "<p style='color: green'>✓ Znaleziono lokalizację ID: {$locationId}</p>\n";
            break;
        }
    }

    if (!$locationId) {
        echo "<p style='color: red'>✗ Nie znaleziono lokalizacji</p>\n";
        continue;
    }

    echo "<h4>2. Sprawdzanie dostępności każdego produktu:</h4>\n";
    $filteredProducts = [];

    foreach ($products as $product) {
        echo "<p><strong>Produkt: {$product['name']} (ID: {$product['id']})</strong></p>\n";

        try {
            $isAvailable = $fleetManager->isProductAvailableInLocation($product['id'], $locationId);
            echo "<p>&nbsp;&nbsp;Dostępny: " . ($isAvailable ? "TAK" : "NIE") . "</p>\n";

            if ($isAvailable) {
                $filteredProducts[] = $product;
                echo "<p>&nbsp;&nbsp;✓ Dodano do wyników</p>\n";
            } else {
                echo "<p>&nbsp;&nbsp;✗ Pominięto</p>\n";
            }
        } catch (Exception $e) {
            echo "<p>&nbsp;&nbsp;BŁĄD: " . $e->getMessage() . "</p>\n";
        }
    }

    echo "<h4>3. Wyniki filtrowania:</h4>\n";
    echo "<p>Produkty przed: " . count($products) . "</p>\n";
    echo "<p>Produkty po: " . count($filteredProducts) . "</p>\n";

    if (!empty($filteredProducts)) {
        echo "<ul>\n";
        foreach ($filteredProducts as $product) {
            echo "<li>{$product['name']} (ID: {$product['id']})</li>\n";
        }
        echo "</ul>\n";
    }

    echo "<h4>4. Test funkcji filter_products_by_location():</h4>\n";
    if (function_exists('filter_products_by_location')) {
        try {
            $function_result = filter_products_by_location($products, $pickup_location);
            echo "<p>Wynik funkcji: " . count($function_result) . " produktów</p>\n";

            if (count($function_result) !== count($filteredProducts)) {
                echo "<p style='color: red'>⚠️ PROBLEM: Funkcja zwraca inne wyniki niż manual test!</p>\n";
                echo "<p>Manual: " . count($filteredProducts) . ", Funkcja: " . count($function_result) . "</p>\n";
            } else {
                echo "<p style='color: green'>✓ Funkcja działa poprawnie</p>\n";
            }
        } catch (Exception $e) {
            echo "<p style='color: red'>BŁĄD funkcji: " . $e->getMessage() . "</p>\n";
        }
    } else {
        echo "<p style='color: red'>Funkcja filter_products_by_location nie istnieje!</p>\n";
    }

    echo "<hr>\n";
    break; // Test tylko pierwszą lokalizację
}
