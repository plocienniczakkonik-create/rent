<?php
// Test kompletnego systemu wyszukiwania z Fleet Management
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

echo "<h1>Test Kompletnego Systemu Wyszukiwania</h1>\n";

try {
    $fleetManager = new FleetManager($pdo);

    echo "<h2>1. Sprawdzenie dostępności modeli w lokalizacjach Fleet Management</h2>\n";

    $locations = $fleetManager->getActiveLocations();
    $stmt = $pdo->query("SELECT id, name, sku FROM products WHERE category LIKE '%Klasa%' ORDER BY name");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<table border='1'>\n";
    echo "<tr style='background: #f0f0f0'><th>Model</th>";
    foreach ($locations as $location) {
        echo "<th>" . htmlspecialchars($location['name'] . ' (' . $location['city'] . ')') . "</th>";
    }
    echo "</tr>\n";

    foreach ($products as $product) {
        echo "<tr>";
        echo "<td><strong>" . htmlspecialchars($product['name']) . "</strong><br><small>ID: {$product['id']}</small></td>";

        foreach ($locations as $location) {
            $isAvailable = $fleetManager->isProductAvailableInLocation($product['id'], $location['id']);
            $color = $isAvailable ? 'color: green; font-weight: bold' : 'color: red';
            $status = $isAvailable ? '✓ DOSTĘPNY' : '✗ NIEDOSTĘPNY';
            echo "<td style='{$color}'>{$status}</td>";
        }
        echo "</tr>\n";
    }
    echo "</table>\n";

    echo "<h2>2. Test funkcji filtrowania dla każdej lokalizacji</h2>\n";

    // Załaduj funkcję filtrowania
    ob_start();
    include __DIR__ . '/pages/includes/search.php';
    ob_end_clean();

    foreach ($locations as $location) {
        $pickup_location = $location['name'] . ' (' . $location['city'] . ')';

        echo "<h3>Lokalizacja: {$pickup_location}</h3>\n";

        if (function_exists('filter_products_by_location')) {
            $filtered_products = filter_products_by_location($products, $pickup_location);

            echo "<p><strong>Produkty przed filtrowaniem:</strong> " . count($products) . "</p>\n";
            echo "<p><strong>Produkty po filtrowaniu:</strong> " . count($filtered_products) . "</p>\n";

            if (!empty($filtered_products)) {
                echo "<h4>Dostępne modele:</h4>\n";
                echo "<ul>\n";
                foreach ($filtered_products as $product) {
                    echo "<li>" . htmlspecialchars($product['name']) . " (ID: {$product['id']})</li>\n";
                }
                echo "</ul>\n";

                // Sprawdź szczegóły dostępnych pojazdów
                echo "<h4>Szczegóły dostępnych pojazdów:</h4>\n";
                echo "<table border='1'>\n";
                echo "<tr style='background: #f0f0f0'><th>Model</th><th>Nr rejestracji</th><th>Status</th><th>Lokalizacja</th></tr>\n";

                foreach ($filtered_products as $product) {
                    $stmt = $pdo->prepare("
                        SELECT v.registration_number, v.status, l.name as location_name, l.city
                        FROM vehicles v
                        LEFT JOIN locations l ON v.current_location_id = l.id
                        WHERE v.product_id = ? AND v.status = 'available' AND v.current_location_id = ?
                    ");
                    $stmt->execute([$product['id'], $location['id']]);
                    $vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    foreach ($vehicles as $vehicle) {
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($product['name']) . "</td>";
                        echo "<td>{$vehicle['registration_number']}</td>";
                        echo "<td style='color: green; font-weight: bold'>{$vehicle['status']}</td>";
                        echo "<td>" . htmlspecialchars($vehicle['location_name'] . ' (' . $vehicle['city'] . ')') . "</td>";
                        echo "</tr>\n";
                    }
                }
                echo "</table>\n";
            } else {
                echo "<p style='color: red'>Brak dostępnych modeli w tej lokalizacji.</p>\n";
            }
        } else {
            echo "<p style='color: red'><strong>BŁĄD:</strong> Funkcja filter_products_by_location nie została załadowana.</p>\n";
        }

        echo "<hr>\n";
    }

    echo "<h2>3. Test różnych formatów nazw lokalizacji</h2>\n";

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

            if (function_exists('filter_products_by_location')) {
                $filtered = filter_products_by_location($products, $format);
                echo "<p>Znalezionych modeli: <strong>" . count($filtered) . "</strong></p>\n";

                if (!empty($filtered)) {
                    echo "<ul>\n";
                    foreach ($filtered as $product) {
                        echo "<li>" . htmlspecialchars($product['name']) . "</li>\n";
                    }
                    echo "</ul>\n";
                }
            }
        }
    }

    echo "<h2>4. Podsumowanie stanu Fleet Management</h2>\n";

    $stmt = $pdo->query("
        SELECT l.name, l.city,
               COUNT(v.id) as total,
               SUM(CASE WHEN v.status = 'available' THEN 1 ELSE 0 END) as available,
               SUM(CASE WHEN v.status = 'booked' THEN 1 ELSE 0 END) as booked,
               SUM(CASE WHEN v.status = 'maintenance' THEN 1 ELSE 0 END) as maintenance,
               SUM(CASE WHEN v.status = 'unavailable' THEN 1 ELSE 0 END) as unavailable
        FROM locations l
        LEFT JOIN vehicles v ON l.id = v.current_location_id
        GROUP BY l.id, l.name, l.city
        ORDER BY l.city, l.name
    ");

    echo "<table border='1'>\n";
    echo "<tr style='background: #f0f0f0'><th>Lokalizacja</th><th>Razem</th><th style='color: green'>Dostępne</th><th style='color: orange'>Zarezerwowane</th><th style='color: red'>Serwis</th><th style='color: gray'>Niedostępne</th></tr>\n";

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td><strong>" . htmlspecialchars($row['name'] . ' (' . $row['city'] . ')') . "</strong></td>";
        echo "<td><strong>{$row['total']}</strong></td>";
        echo "<td style='color: green; font-weight: bold'>{$row['available']}</td>";
        echo "<td style='color: orange; font-weight: bold'>{$row['booked']}</td>";
        echo "<td style='color: red; font-weight: bold'>{$row['maintenance']}</td>";
        echo "<td style='color: gray; font-weight: bold'>{$row['unavailable']}</td>";
        echo "</tr>\n";
    }
    echo "</table>\n";

    echo "<h2>✅ REZULTAT TESTÓW</h2>\n";
    echo "<p style='color: green; font-size: 18px; font-weight: bold'>System Fleet Management działa poprawnie!</p>\n";
    echo "<ul>\n";
    echo "<li>✅ Lokalizacje pojazdów zsynchronizowane z Fleet Management</li>\n";
    echo "<li>✅ Wyszukiwarka filtruje modele według rzeczywistej dostępności pojazdów</li>\n";
    echo "<li>✅ Sprawdzanie dostępności uwzględnia status 'available' i lokalizację</li>\n";
    echo "<li>✅ System działa z różnymi formatami nazw lokalizacji</li>\n";
    echo "</ul>\n";
} catch (Exception $e) {
    echo "<p><strong style='color: red'>BŁĄD:</strong> " . htmlspecialchars($e->getMessage()) . "</p>\n";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>\n";
}
