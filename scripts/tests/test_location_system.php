<?php
require_once 'includes/db.php';

echo "SPRAWDZANIE STRUKTURY TABELI VEHICLES - KOLUMNY LOCATION:\n";
echo "════════════════════════════════════════════════════════════\n\n";

try {
    $stmt = db()->query('DESCRIBE vehicles');
    $locationColumns = [];

    while ($row = $stmt->fetch()) {
        if (strpos($row['Field'], 'location') !== false) {
            $locationColumns[] = $row;
            echo "✓ {$row['Field']} - {$row['Type']}\n";
        }
    }

    if (empty($locationColumns)) {
        echo "❌ Brak kolumn location w tabeli vehicles\n";
    }

    echo "\n" . str_repeat("═", 60) . "\n";
    echo "SPRAWDZANIE TABELI LOCATIONS:\n\n";

    $stmt = db()->query("SHOW TABLES LIKE 'locations'");
    if ($stmt->rowCount() > 0) {
        echo "✓ Tabela locations istnieje\n";

        $stmt = db()->query('DESCRIBE locations');
        while ($row = $stmt->fetch()) {
            echo "  - {$row['Field']} ({$row['Type']})\n";
        }

        echo "\nLOKALIZACJE W SYSTEMIE:\n";
        $stmt = db()->query('SELECT id, name, city, is_active FROM locations ORDER BY name');
        while ($row = $stmt->fetch()) {
            $status = $row['is_active'] ? "✓" : "❌";
            echo "  {$status} [{$row['id']}] {$row['name']} - {$row['city']}\n";
        }
    } else {
        echo "❌ Tabela locations nie istnieje\n";
    }

    echo "\n" . str_repeat("═", 60) . "\n";
    echo "SPRAWDZANIE TABELI VEHICLE_LOCATION_HISTORY:\n\n";

    $stmt = db()->query("SHOW TABLES LIKE 'vehicle_location_history'");
    if ($stmt->rowCount() > 0) {
        echo "✓ Tabela vehicle_location_history istnieje\n";

        $stmt = db()->query('SELECT COUNT(*) as cnt FROM vehicle_location_history');
        $count = $stmt->fetch()['cnt'];
        echo "  Liczba wpisów historii: {$count}\n";
    } else {
        echo "❌ Tabela vehicle_location_history nie istnieje\n";
    }
} catch (Exception $e) {
    echo "❌ Błąd: " . $e->getMessage() . "\n";
}
