<?php
require_once __DIR__ . '/includes/config.php';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "<h1>Rozszerzenie tabeli reservations o Fleet Management</h1>\n";

    // Lista kolumn do dodania
    $new_columns = [
        "vehicle_id INT(11) NULL COMMENT 'ID konkretnego pojazdu z tabeli vehicles'",
        "pickup_location_id INT(11) NULL COMMENT 'ID lokalizacji odbioru z Fleet Management'",
        "dropoff_location_id INT(11) NULL COMMENT 'ID lokalizacji zwrotu z Fleet Management'",
        "deposit_amount DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Kwota kaucji'",
        "deposit_type ENUM('fixed', 'percentage') NULL COMMENT 'Typ kaucji'",
        "location_fee DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Opłata międzymiastowa'",
        "total_with_deposit DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Suma łączna z kaucją'"
    ];

    echo "<h2>Dodawanie nowych kolumn:</h2>\n";

    foreach ($new_columns as $column_def) {
        // Wyciągnij nazwę kolumny
        $column_name = explode(' ', $column_def)[0];

        echo "<h3>Kolumna: {$column_name}</h3>\n";

        // Sprawdź czy kolumna już istnieje
        $stmt = $pdo->prepare("SHOW COLUMNS FROM reservations LIKE ?");
        $stmt->execute([$column_name]);
        $exists = $stmt->fetch();

        if ($exists) {
            echo "<p style='color: orange'>⚠️ Kolumna już istnieje</p>\n";
        } else {
            try {
                $sql = "ALTER TABLE reservations ADD COLUMN {$column_def}";
                $pdo->exec($sql);
                echo "<p style='color: green'>✓ Dodano kolumnę: {$column_name}</p>\n";
            } catch (Exception $e) {
                echo "<p style='color: red'>✗ Błąd dodawania kolumny {$column_name}: " . htmlspecialchars($e->getMessage()) . "</p>\n";
            }
        }
    }

    echo "<h2>Weryfikacja struktury tabeli po zmianach:</h2>\n";

    $stmt = $pdo->query("DESCRIBE reservations");
    echo "<table border='1'>\n";
    echo "<tr style='background: #f0f0f0'><th>Kolumna</th><th>Typ</th><th>Null</th><th>Default</th><th>Comment</th></tr>\n";

    while ($row = $stmt->fetch()) {
        $is_new = in_array($row[0], ['vehicle_id', 'pickup_location_id', 'dropoff_location_id', 'deposit_amount', 'deposit_type', 'location_fee', 'total_with_deposit']);
        $bg_color = $is_new ? 'background: #d4edda' : 'background: white';

        echo "<tr style='{$bg_color}'>";
        echo "<td><strong>{$row[0]}</strong></td>";
        echo "<td>{$row[1]}</td>";
        echo "<td>{$row[2]}</td>";
        echo "<td>" . ($row[4] ?: '-') . "</td>";
        echo "<td>" . ($row[8] ?? '-') . "</td>";
        echo "</tr>\n";
    }
    echo "</table>\n";

    echo "<h2>Dodanie indeksów dla wydajności:</h2>\n";

    $indexes = [
        "ADD INDEX idx_vehicle_id (vehicle_id)",
        "ADD INDEX idx_pickup_location_id (pickup_location_id)",
        "ADD INDEX idx_dropoff_location_id (dropoff_location_id)"
    ];

    foreach ($indexes as $index_def) {
        try {
            $sql = "ALTER TABLE reservations {$index_def}";
            $pdo->exec($sql);
            echo "<p style='color: green'>✓ Dodano indeks: {$index_def}</p>\n";
        } catch (Exception $e) {
            // Indeks może już istnieć
            if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
                echo "<p style='color: orange'>⚠️ Indeks już istnieje: {$index_def}</p>\n";
            } else {
                echo "<p style='color: red'>✗ Błąd dodawania indeksu: " . htmlspecialchars($e->getMessage()) . "</p>\n";
            }
        }
    }

    echo "<h2>✅ Rozszerzenie tabeli reservations zakończone!</h2>\n";
    echo "<p>Tabela reservations jest teraz gotowa do integracji z Fleet Management.</p>\n";
} catch (Exception $e) {
    echo "<p><strong style='color: red'>Błąd:</strong> " . htmlspecialchars($e->getMessage()) . "</p>\n";
}
