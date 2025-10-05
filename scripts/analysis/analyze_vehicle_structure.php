<?php
require_once __DIR__ . '/includes/config.php';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "<h1>Analiza struktury bazy danych - Pojazdy</h1>\n";

    // Sprawdź strukturę tabeli products
    echo "<h2>Struktura tabeli 'products':</h2>\n";
    $stmt = $pdo->query("DESCRIBE products");
    echo "<table border='1'><tr><th>Kolumna</th><th>Typ</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>\n";
    while ($row = $stmt->fetch()) {
        echo "<tr><td>{$row[0]}</td><td>{$row[1]}</td><td>{$row[2]}</td><td>{$row[3]}</td><td>{$row[4]}</td><td>{$row[5]}</td></tr>\n";
    }
    echo "</table>\n";

    // Sprawdź dane w tabeli products
    echo "<h2>Dane w tabeli 'products':</h2>\n";
    $stmt = $pdo->query("SELECT * FROM products LIMIT 10");
    if ($stmt->rowCount() > 0) {
        echo "<table border='1'>\n";
        $first = true;
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if ($first) {
                echo "<tr>";
                foreach (array_keys($row) as $key) {
                    echo "<th>{$key}</th>";
                }
                echo "</tr>\n";
                $first = false;
            }
            echo "<tr>";
            foreach ($row as $value) {
                echo "<td>" . htmlspecialchars($value) . "</td>";
            }
            echo "</tr>\n";
        }
        echo "</table>\n";
    } else {
        echo "<p>Brak danych w tabeli products</p>\n";
    }

    // Sprawdź strukturę tabeli vehicles
    echo "<h2>Struktura tabeli 'vehicles':</h2>\n";
    $stmt = $pdo->query("DESCRIBE vehicles");
    echo "<table border='1'><tr><th>Kolumna</th><th>Typ</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>\n";
    while ($row = $stmt->fetch()) {
        echo "<tr><td>{$row[0]}</td><td>{$row[1]}</td><td>{$row[2]}</td><td>{$row[3]}</td><td>{$row[4]}</td><td>{$row[5]}</td></tr>\n";
    }
    echo "</table>\n";

    // Sprawdź dane w tabeli vehicles
    echo "<h2>Dane w tabeli 'vehicles':</h2>\n";
    $stmt = $pdo->query("SELECT * FROM vehicles LIMIT 10");
    if ($stmt->rowCount() > 0) {
        echo "<table border='1'>\n";
        $first = true;
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if ($first) {
                echo "<tr>";
                foreach (array_keys($row) as $key) {
                    echo "<th>{$key}</th>";
                }
                echo "</tr>\n";
                $first = false;
            }
            echo "<tr>";
            foreach ($row as $value) {
                echo "<td>" . htmlspecialchars($value) . "</td>";
            }
            echo "</tr>\n";
        }
        echo "</table>\n";
    } else {
        echo "<p>Brak danych w tabeli vehicles</p>\n";
    }

    // Sprawdź relacje między products i vehicles
    echo "<h2>Relacja między products i vehicles:</h2>\n";
    $stmt = $pdo->query("
        SELECT p.id as product_id, p.name as product_name, p.category,
               v.id as vehicle_id, v.name as vehicle_name, v.license_plate, v.status
        FROM products p 
        LEFT JOIN vehicles v ON p.id = v.product_id 
        LIMIT 10
    ");

    if ($stmt->rowCount() > 0) {
        echo "<table border='1'>\n";
        echo "<tr><th>Product ID</th><th>Product Name</th><th>Category</th><th>Vehicle ID</th><th>Vehicle Name</th><th>License Plate</th><th>Status</th></tr>\n";
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>";
            echo "<td>{$row['product_id']}</td>";
            echo "<td>" . htmlspecialchars($row['product_name']) . "</td>";
            echo "<td>" . htmlspecialchars($row['category']) . "</td>";
            echo "<td>" . ($row['vehicle_id'] ?: 'NULL') . "</td>";
            echo "<td>" . htmlspecialchars($row['vehicle_name'] ?: 'NULL') . "</td>";
            echo "<td>" . htmlspecialchars($row['license_plate'] ?: 'NULL') . "</td>";
            echo "<td>" . htmlspecialchars($row['status'] ?: 'NULL') . "</td>";
            echo "</tr>\n";
        }
        echo "</table>\n";
    } else {
        echo "<p>Brak relacji między products i vehicles</p>\n";
    }

    // Sprawdź vehicle_location_history
    echo "<h2>Historia lokalizacji pojazdów (vehicle_location_history):</h2>\n";
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM vehicle_location_history");
    $count = $stmt->fetchColumn();
    echo "<p>Liczba rekordów w vehicle_location_history: {$count}</p>\n";

    if ($count > 0) {
        $stmt = $pdo->query("SELECT * FROM vehicle_location_history ORDER BY created_at DESC LIMIT 5");
        echo "<table border='1'>\n";
        $first = true;
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if ($first) {
                echo "<tr>";
                foreach (array_keys($row) as $key) {
                    echo "<th>{$key}</th>";
                }
                echo "</tr>\n";
                $first = false;
            }
            echo "<tr>";
            foreach ($row as $value) {
                echo "<td>" . htmlspecialchars($value) . "</td>";
            }
            echo "</tr>\n";
        }
        echo "</table>\n";
    }

    // Sprawdź reservations
    echo "<h2>Przykładowe rezerwacje:</h2>\n";
    $stmt = $pdo->query("
        SELECT id, product_id, vehicle_id, pickup_location, dropoff_location, 
               pickup_date, dropoff_date, status 
        FROM reservations 
        ORDER BY created_at DESC 
        LIMIT 5
    ");

    if ($stmt->rowCount() > 0) {
        echo "<table border='1'>\n";
        echo "<tr><th>ID</th><th>Product ID</th><th>Vehicle ID</th><th>Pickup Location</th><th>Dropoff Location</th><th>Pickup Date</th><th>Dropoff Date</th><th>Status</th></tr>\n";
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>";
            echo "<td>{$row['id']}</td>";
            echo "<td>{$row['product_id']}</td>";
            echo "<td>" . ($row['vehicle_id'] ?: 'NULL') . "</td>";
            echo "<td>" . htmlspecialchars($row['pickup_location']) . "</td>";
            echo "<td>" . htmlspecialchars($row['dropoff_location']) . "</td>";
            echo "<td>{$row['pickup_date']}</td>";
            echo "<td>{$row['dropoff_date']}</td>";
            echo "<td>{$row['status']}</td>";
            echo "</tr>\n";
        }
        echo "</table>\n";
    }
} catch (Exception $e) {
    echo "<p><strong>Błąd:</strong> " . htmlspecialchars($e->getMessage()) . "</p>\n";
}
