<?php
require_once __DIR__ . '/includes/config.php';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "<h1>Analiza struktury tabeli reservations</h1>\n";

    // Sprawdź strukturę tabeli reservations
    echo "<h2>Struktura tabeli 'reservations':</h2>\n";
    $stmt = $pdo->query("DESCRIBE reservations");
    echo "<table border='1'><tr><th>Kolumna</th><th>Typ</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>\n";
    while ($row = $stmt->fetch()) {
        echo "<tr><td>{$row[0]}</td><td>{$row[1]}</td><td>{$row[2]}</td><td>{$row[3]}</td><td>{$row[4]}</td><td>{$row[5]}</td></tr>\n";
    }
    echo "</table>\n";

    // Sprawdź czy są kolumny dla Fleet Management
    echo "<h2>Kolumny Fleet Management w reservations:</h2>\n";
    $fleet_columns = ['vehicle_id', 'deposit_amount', 'deposit_type', 'location_fee'];
    $stmt = $pdo->query("DESCRIBE reservations");
    $existing_columns = [];
    while ($row = $stmt->fetch()) {
        $existing_columns[] = $row[0];
    }

    echo "<ul>\n";
    foreach ($fleet_columns as $col) {
        $exists = in_array($col, $existing_columns);
        $status = $exists ? "✓ ISTNIEJE" : "✗ BRAK";
        $color = $exists ? "color: green" : "color: red";
        echo "<li style='{$color}'>{$col}: {$status}</li>\n";
    }
    echo "</ul>\n";

    // Sprawdź tabele Fleet Management
    echo "<h2>Tabele Fleet Management:</h2>\n";
    $fleet_tables = ['reservation_deposits', 'reservation_location_fees', 'reservation_routes'];
    $stmt = $pdo->query("SHOW TABLES");
    $existing_tables = [];
    while ($row = $stmt->fetch()) {
        $existing_tables[] = $row[0];
    }

    echo "<ul>\n";
    foreach ($fleet_tables as $table) {
        $exists = in_array($table, $existing_tables);
        $status = $exists ? "✓ ISTNIEJE" : "✗ BRAK";
        $color = $exists ? "color: green" : "color: red";
        echo "<li style='{$color}'>{$table}: {$status}</li>\n";

        if ($exists) {
            $stmt = $pdo->query("SELECT COUNT(*) FROM {$table}");
            $count = $stmt->fetchColumn();
            echo "<li>&nbsp;&nbsp;&nbsp;Rekordów: {$count}</li>\n";
        }
    }
    echo "</ul>\n";

    // Sprawdź przykładowe rezerwacje
    echo "<h2>Przykładowe rezerwacje (ostatnie 3):</h2>\n";
    $stmt = $pdo->query("SELECT id, sku, product_name, pickup_location, dropoff_location, total_gross, status, created_at FROM reservations ORDER BY created_at DESC LIMIT 3");
    $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($reservations)) {
        echo "<table border='1'>\n";
        echo "<tr style='background: #f0f0f0'><th>ID</th><th>SKU</th><th>Produkt</th><th>Odbiór</th><th>Zwrot</th><th>Suma</th><th>Status</th><th>Data</th></tr>\n";
        foreach ($reservations as $res) {
            echo "<tr>";
            echo "<td>{$res['id']}</td>";
            echo "<td>{$res['sku']}</td>";
            echo "<td>" . htmlspecialchars($res['product_name']) . "</td>";
            echo "<td>" . htmlspecialchars($res['pickup_location']) . "</td>";
            echo "<td>" . htmlspecialchars($res['dropoff_location']) . "</td>";
            echo "<td>{$res['total_gross']}</td>";
            echo "<td>{$res['status']}</td>";
            echo "<td>{$res['created_at']}</td>";
            echo "</tr>\n";
        }
        echo "</table>\n";
    } else {
        echo "<p>Brak rezerwacji w bazie danych.</p>\n";
    }
} catch (Exception $e) {
    echo "<p><strong>Błąd:</strong> " . htmlspecialchars($e->getMessage()) . "</p>\n";
}
