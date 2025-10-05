<?php
require_once 'includes/config.php';
require_once 'includes/db.php';

$pdo = db();

echo "=== ROZSZERZENIE TABELI RESERVATIONS O POLA ADRESOWE ===\n\n";

try {
    // Dodaj kolumny adresowe
    $alterQueries = [
        "ALTER TABLE reservations ADD COLUMN billing_address VARCHAR(255) DEFAULT NULL AFTER customer_phone",
        "ALTER TABLE reservations ADD COLUMN billing_city VARCHAR(100) DEFAULT NULL AFTER billing_address",
        "ALTER TABLE reservations ADD COLUMN billing_postcode VARCHAR(20) DEFAULT NULL AFTER billing_city",
        "ALTER TABLE reservations ADD COLUMN billing_country VARCHAR(2) DEFAULT NULL AFTER billing_postcode"
    ];

    foreach ($alterQueries as $query) {
        try {
            $pdo->exec($query);
            echo "✅ " . explode(' ', $query)[5] . "\n";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
                echo "⚠️ Kolumna " . explode(' ', $query)[5] . " już istnieje\n";
            } else {
                echo "❌ Błąd dla " . explode(' ', $query)[5] . ": " . $e->getMessage() . "\n";
            }
        }
    }

    echo "\n2. Sprawdzenie struktury tabeli:\n";
    $stmt = $pdo->query("DESCRIBE reservations");
    while ($row = $stmt->fetch()) {
        if (strpos($row['Field'], 'billing_') === 0 || in_array($row['Field'], ['customer_phone', 'customer_email', 'customer_name'])) {
            echo "   - {$row['Field']}: {$row['Type']}\n";
        }
    }

    echo "\n✅ Tabela rozszerzona pomyślnie!\n";
} catch (Exception $e) {
    echo "❌ Błąd: " . $e->getMessage() . "\n";
}

echo "\n=== Zakończono ===\n";
