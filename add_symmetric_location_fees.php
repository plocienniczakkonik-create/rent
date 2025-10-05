<?php
require_once __DIR__ . '/includes/config.php';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "<h1>Dodawanie symetrycznych opłat lokalizacyjnych</h1>\n";

    // Pobierz lokalizacje
    $stmt = $pdo->query("SELECT id, name, city FROM locations ORDER BY city, name");
    $locations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<h2>Dostępne lokalizacje:</h2>\n";
    echo "<ul>\n";
    foreach ($locations as $location) {
        echo "<li>ID: {$location['id']} - {$location['name']} ({$location['city']})</li>\n";
    }
    echo "</ul>\n";

    // Definicja opłat - tylko w jednym kierunku (system automatycznie obsłuży kierunek odwrotny)
    $fees = [
        // Warszawa → inne miasta
        [1, 2, 150.00, 'Warszawa → Kraków'],          // Warszawa → Kraków
        [1, 3, 200.00, 'Warszawa → Gdańsk'],          // Warszawa → Gdańsk  
        [1, 4, 120.00, 'Warszawa → Wrocław'],         // Warszawa → Wrocław
        [1, 5, 180.00, 'Warszawa → Poznań'],          // Warszawa → Poznań

        // Kraków → inne miasta (bez Warszawy - już jest odwrotnie)
        [2, 3, 250.00, 'Kraków → Gdańsk'],            // Kraków → Gdańsk
        [2, 4, 100.00, 'Kraków → Wrocław'],           // Kraków → Wrocław
        [2, 5, 200.00, 'Kraków → Poznań'],            // Kraków → Poznań

        // Gdańsk → inne miasta (bez już istniejących)
        [3, 4, 300.00, 'Gdańsk → Wrocław'],           // Gdańsk → Wrocław
        [3, 5, 220.00, 'Gdańsk → Poznań'],            // Gdańsk → Poznań

        // Wrocław → Poznań
        [4, 5, 160.00, 'Wrocław → Poznań'],           // Wrocław → Poznań
    ];

    echo "<h2>Dodawanie opłat (tylko w jednym kierunku):</h2>\n";

    // Włącz opłaty lokalizacyjne
    $stmt = $pdo->prepare("INSERT INTO shop_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
    $stmt->execute(['location_fees_enabled', '1']);
    $stmt->execute(['location_fees_auto_calculate', '1']);
    echo "<p style='color: green'>✓ Włączono opłaty lokalizacyjne</p>\n";

    foreach ($fees as $fee) {
        [$pickup_id, $return_id, $amount, $description] = $fee;

        // Sprawdź czy opłata już istnieje (w obu kierunkach)
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM location_fees 
            WHERE (pickup_location_id = ? AND return_location_id = ?) 
            OR (pickup_location_id = ? AND return_location_id = ?)
        ");
        $stmt->execute([$pickup_id, $return_id, $return_id, $pickup_id]);
        $exists = $stmt->fetchColumn() > 0;

        if ($exists) {
            echo "<p style='color: orange'>⚠️ Opłata {$description} już istnieje</p>\n";
        } else {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO location_fees 
                    (pickup_location_id, return_location_id, fee_amount, fee_type, is_active, created_at) 
                    VALUES (?, ?, ?, 'fixed', 1, NOW())
                ");
                $stmt->execute([$pickup_id, $return_id, $amount]);
                echo "<p style='color: green'>✓ Dodano: {$description} - {$amount} PLN</p>\n";
            } catch (Exception $e) {
                echo "<p style='color: red'>✗ Błąd dla {$description}: " . htmlspecialchars($e->getMessage()) . "</p>\n";
            }
        }
    }

    echo "<h2>Test symetrycznych opłat:</h2>\n";

    // Autoloader
    function autoload_fleet_classes($className)
    {
        $classFile = __DIR__ . '/classes/' . $className . '.php';
        if (file_exists($classFile)) {
            require_once $classFile;
        }
    }
    spl_autoload_register('autoload_fleet_classes');

    $locationFeeManager = new LocationFeeManager($pdo);

    // Test kilku tras w obu kierunkach
    $test_routes = [
        [1, 2, 'Warszawa → Kraków'],
        [2, 1, 'Kraków → Warszawa'],  // Kierunek odwrotny
        [3, 5, 'Gdańsk → Poznań'],
        [5, 3, 'Poznań → Gdańsk'],    // Kierunek odwrotny
    ];

    echo "<table border='1'>\n";
    echo "<tr style='background: #f0f0f0'><th>Trasa</th><th>Opłata</th><th>Typ</th><th>Opis</th></tr>\n";

    foreach ($test_routes as $route) {
        [$pickup_id, $return_id, $route_name] = $route;

        $result = $locationFeeManager->calculateLocationFee($pickup_id, $return_id);

        $color = $result['enabled'] ? 'color: green' : 'color: red';
        echo "<tr>";
        echo "<td><strong>{$route_name}</strong></td>";
        echo "<td style='{$color}'>" . number_format($result['amount'], 2) . " PLN</td>";
        echo "<td>" . ($result['type'] ?? 'N/A') . "</td>";
        echo "<td>" . htmlspecialchars($result['description']) . "</td>";
        echo "</tr>\n";
    }
    echo "</table>\n";

    echo "<h2>Podsumowanie:</h2>\n";

    $stmt = $pdo->query("SELECT COUNT(*) FROM location_fees WHERE is_active = 1");
    $total_fees = $stmt->fetchColumn();

    echo "<ul>\n";
    echo "<li>✅ Dodano opłaty tylko w jednym kierunku</li>\n";
    echo "<li>✅ System automatycznie obsługuje kierunek odwrotny</li>\n";
    echo "<li>✅ Łącznie aktywnych opłat: {$total_fees}</li>\n";
    echo "<li>✅ Efektywnie obsługiwanych tras: " . ($total_fees * 2) . " (w obu kierunkach)</li>\n";
    echo "</ul>\n";
} catch (Exception $e) {
    echo "<p><strong style='color: red'>Błąd:</strong> " . htmlspecialchars($e->getMessage()) . "</p>\n";
}
