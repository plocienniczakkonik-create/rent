<?php
require_once __DIR__ . '/includes/config.php';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "<h1>Analiza Modeli i Pojazdów</h1>\n";

    // Relacja między products i vehicles
    echo "<h2>Relacja MODELE → POJAZDY:</h2>\n";
    $stmt = $pdo->query("
        SELECT p.id as model_id, p.name as model_name, p.category,
               v.id as vehicle_id, v.registration_number, v.status, v.location, v.current_location_id
        FROM products p 
        LEFT JOIN vehicles v ON p.id = v.product_id 
        ORDER BY p.id, v.id
    ");

    echo "<table border='1'>\n";
    echo "<tr style='background: #f0f0f0'><th>Model ID</th><th>Model Name</th><th>Category</th><th>Vehicle ID</th><th>Registration</th><th>Status</th><th>Current Location</th><th>Location ID</th></tr>\n";

    $current_model = null;
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $bg = ($row['model_id'] != $current_model) ? "background: #fff3cd" : "background: white";
        $current_model = $row['model_id'];

        echo "<tr style='{$bg}'>";
        echo "<td><strong>{$row['model_id']}</strong></td>";
        echo "<td><strong>" . htmlspecialchars($row['model_name']) . "</strong></td>";
        echo "<td>" . htmlspecialchars($row['category']) . "</td>";
        echo "<td>" . ($row['vehicle_id'] ?: '-') . "</td>";
        echo "<td>" . htmlspecialchars($row['registration_number'] ?: '-') . "</td>";
        echo "<td>" . htmlspecialchars($row['status'] ?: '-') . "</td>";
        echo "<td>" . htmlspecialchars($row['location'] ?: '-') . "</td>";
        echo "<td>" . ($row['current_location_id'] ?: '-') . "</td>";
        echo "</tr>\n";
    }
    echo "</table>\n";

    // Statystyki pojazdów według lokalizacji
    echo "<h2>Dostępność pojazdów według lokalizacji:</h2>\n";
    $stmt = $pdo->query("
        SELECT p.name as model, v.location, v.status, COUNT(*) as count
        FROM products p 
        JOIN vehicles v ON p.id = v.product_id 
        GROUP BY p.name, v.location, v.status
        ORDER BY p.name, v.location, v.status
    ");

    echo "<table border='1'>\n";
    echo "<tr style='background: #f0f0f0'><th>Model</th><th>Lokalizacja</th><th>Status</th><th>Liczba pojazdów</th></tr>\n";
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
        }

        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['model']) . "</td>";
        echo "<td>" . htmlspecialchars($row['location']) . "</td>";
        echo "<td style='{$status_color}'>" . htmlspecialchars($row['status']) . "</td>";
        echo "<td><strong>{$row['count']}</strong></td>";
        echo "</tr>\n";
    }
    echo "</table>\n";

    // Podsumowanie dostępności
    echo "<h2>Podsumowanie dostępności modeli w lokalizacjach:</h2>\n";
    $stmt = $pdo->query("
        SELECT p.name as model, v.location, 
               SUM(CASE WHEN v.status = 'available' THEN 1 ELSE 0 END) as available,
               SUM(CASE WHEN v.status = 'booked' THEN 1 ELSE 0 END) as booked,
               SUM(CASE WHEN v.status = 'maintenance' THEN 1 ELSE 0 END) as maintenance,
               COUNT(*) as total
        FROM products p 
        JOIN vehicles v ON p.id = v.product_id 
        GROUP BY p.name, v.location
        ORDER BY p.name, v.location
    ");

    echo "<table border='1'>\n";
    echo "<tr style='background: #f0f0f0'><th>Model</th><th>Lokalizacja</th><th style='color: green'>Dostępne</th><th style='color: orange'>Zarezerwowane</th><th style='color: red'>Serwis</th><th>Razem</th></tr>\n";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td><strong>" . htmlspecialchars($row['model']) . "</strong></td>";
        echo "<td>" . htmlspecialchars($row['location']) . "</td>";
        echo "<td style='color: green; font-weight: bold'>{$row['available']}</td>";
        echo "<td style='color: orange; font-weight: bold'>{$row['booked']}</td>";
        echo "<td style='color: red; font-weight: bold'>{$row['maintenance']}</td>";
        echo "<td><strong>{$row['total']}</strong></td>";
        echo "</tr>\n";
    }
    echo "</table>\n";

    // Sprawdź lokalizacje Fleet Management vs rzeczywiste lokalizacje pojazdów
    echo "<h2>Porównanie Fleet Management vs rzeczywiste lokalizacje:</h2>\n";

    // Lokalizacje Fleet Management
    $stmt = $pdo->query("SELECT id, name, city FROM locations ORDER BY city, name");
    $fleet_locations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<h3>Lokalizacje Fleet Management:</h3>\n";
    echo "<ul>\n";
    foreach ($fleet_locations as $loc) {
        echo "<li>ID: {$loc['id']} - {$loc['name']} ({$loc['city']})</li>\n";
    }
    echo "</ul>\n";

    // Rzeczywiste lokalizacje z tabeli vehicles
    $stmt = $pdo->query("SELECT DISTINCT location FROM vehicles WHERE location IS NOT NULL ORDER BY location");
    $vehicle_locations = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo "<h3>Rzeczywiste lokalizacje pojazdów:</h3>\n";
    echo "<ul>\n";
    foreach ($vehicle_locations as $loc) {
        echo "<li>" . htmlspecialchars($loc) . "</li>\n";
    }
    echo "</ul>\n";

    echo "<h2>⚠️ PROBLEM: Niezgodność lokalizacji!</h2>\n";
    echo "<p><strong>Fleet Management używa ID lokalizacji, ale pojazdy mają tekstowe nazwy lokalizacji.</strong></p>\n";
    echo "<p>Trzeba zsynchronizować lokalizacje lub zaktualizować kolumnę current_location_id w tabeli vehicles.</p>\n";
} catch (Exception $e) {
    echo "<p><strong>Błąd:</strong> " . htmlspecialchars($e->getMessage()) . "</p>\n";
}
