<?php
require_once __DIR__ . '/includes/config.php';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "<h1>Synchronizacja lokalizacji pojazdów</h1>\n";

    // Mapowanie lokalizacji tekstowych na ID Fleet Management
    $location_mapping = [
        'Gdańsk - Lotnisko' => 3,     // Gdańsk Port (Gdańsk)
        'Kraków - Balice' => 2,       // Kraków Główny (Kraków)  
        'Poznań - Dębiec' => 5,       // Poznań Plaza (Poznań)
        'Warszawa - Oddział A' => 1,  // Warszawa Centrum (Warszawa)
        'Wrocław - Centrum' => 4      // Wrocław Rynek (Wrocław)
    ];

    echo "<h2>Mapowanie lokalizacji:</h2>\n";
    echo "<table border='1'>\n";
    echo "<tr style='background: #f0f0f0'><th>Tekstowa lokalizacja</th><th>Fleet Management ID</th><th>Fleet Management Nazwa</th></tr>\n";

    foreach ($location_mapping as $text_location => $fleet_id) {
        // Pobierz nazwę z Fleet Management
        $stmt = $pdo->prepare("SELECT name, city FROM locations WHERE id = ?");
        $stmt->execute([$fleet_id]);
        $fleet_location = $stmt->fetch(PDO::FETCH_ASSOC);

        echo "<tr>";
        echo "<td>" . htmlspecialchars($text_location) . "</td>";
        echo "<td><strong>{$fleet_id}</strong></td>";
        echo "<td>" . htmlspecialchars($fleet_location['name'] . ' (' . $fleet_location['city'] . ')') . "</td>";
        echo "</tr>\n";
    }
    echo "</table>\n";

    echo "<h2>Aktualizacja danych:</h2>\n";

    $pdo->beginTransaction();

    foreach ($location_mapping as $text_location => $fleet_id) {
        // Sprawdź ile pojazdów ma tę lokalizację
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM vehicles WHERE location = ?");
        $stmt->execute([$text_location]);
        $count = $stmt->fetchColumn();

        echo "<p>Lokalizacja: <strong>{$text_location}</strong> → Fleet ID: {$fleet_id} (pojazdy: {$count})</p>\n";

        if ($count > 0) {
            // Zaktualizuj current_location_id
            $stmt = $pdo->prepare("UPDATE vehicles SET current_location_id = ? WHERE location = ?");
            $result = $stmt->execute([$fleet_id, $text_location]);

            if ($result) {
                echo "<p style='color: green'>✓ Zaktualizowano {$count} pojazdów</p>\n";
            } else {
                echo "<p style='color: red'>✗ Błąd aktualizacji</p>\n";
                $pdo->rollBack();
                throw new Exception("Błąd aktualizacji dla lokalizacji: {$text_location}");
            }
        }
    }

    $pdo->commit();
    echo "<p style='color: green; font-weight: bold'>✓ Wszystkie lokalizacje zostały zsynchronizowane!</p>\n";

    echo "<h2>Weryfikacja rezultatów:</h2>\n";

    // Sprawdź rezultaty
    $stmt = $pdo->query("
        SELECT v.id, v.registration_number, v.location, v.current_location_id, v.status,
               l.name as fleet_name, l.city as fleet_city
        FROM vehicles v
        LEFT JOIN locations l ON v.current_location_id = l.id
        ORDER BY v.current_location_id, v.id
    ");

    echo "<table border='1'>\n";
    echo "<tr style='background: #f0f0f0'><th>Vehicle ID</th><th>Registration</th><th>Old Location</th><th>Fleet ID</th><th>Fleet Location</th><th>Status</th></tr>\n";

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $status_color = '';
        switch ($row['status']) {
            case 'available':
                $status_color = 'color: green; font-weight: bold';
                break;
            case 'booked':
                $status_color = 'color: orange; font-weight: bold';
                break;
            case 'maintenance':
                $status_color = 'color: red; font-weight: bold';
                break;
            case 'unavailable':
                $status_color = 'color: gray; font-weight: bold';
                break;
        }

        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['registration_number']}</td>";
        echo "<td>" . htmlspecialchars($row['location']) . "</td>";
        echo "<td><strong>{$row['current_location_id']}</strong></td>";
        echo "<td>" . htmlspecialchars($row['fleet_name'] . ' (' . $row['fleet_city'] . ')') . "</td>";
        echo "<td style='{$status_color}'>{$row['status']}</td>";
        echo "</tr>\n";
    }
    echo "</table>\n";

    echo "<h2>Podsumowanie dostępności w lokalizacjach Fleet Management:</h2>\n";

    $stmt = $pdo->query("
        SELECT l.id, l.name, l.city,
               COUNT(v.id) as total_vehicles,
               SUM(CASE WHEN v.status = 'available' THEN 1 ELSE 0 END) as available_vehicles,
               SUM(CASE WHEN v.status = 'booked' THEN 1 ELSE 0 END) as booked_vehicles,
               SUM(CASE WHEN v.status = 'maintenance' THEN 1 ELSE 0 END) as maintenance_vehicles,
               SUM(CASE WHEN v.status = 'unavailable' THEN 1 ELSE 0 END) as unavailable_vehicles
        FROM locations l
        LEFT JOIN vehicles v ON l.id = v.current_location_id
        GROUP BY l.id, l.name, l.city
        ORDER BY l.city, l.name
    ");

    echo "<table border='1'>\n";
    echo "<tr style='background: #f0f0f0'><th>Fleet Location</th><th>Total</th><th style='color: green'>Available</th><th style='color: orange'>Booked</th><th style='color: red'>Maintenance</th><th style='color: gray'>Unavailable</th></tr>\n";

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td><strong>" . htmlspecialchars($row['name'] . ' (' . $row['city'] . ')') . "</strong></td>";
        echo "<td><strong>{$row['total_vehicles']}</strong></td>";
        echo "<td style='color: green; font-weight: bold'>{$row['available_vehicles']}</td>";
        echo "<td style='color: orange; font-weight: bold'>{$row['booked_vehicles']}</td>";
        echo "<td style='color: red; font-weight: bold'>{$row['maintenance_vehicles']}</td>";
        echo "<td style='color: gray; font-weight: bold'>{$row['unavailable_vehicles']}</td>";
        echo "</tr>\n";
    }
    echo "</table>\n";
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "<p><strong style='color: red'>Błąd:</strong> " . htmlspecialchars($e->getMessage()) . "</p>\n";
}
