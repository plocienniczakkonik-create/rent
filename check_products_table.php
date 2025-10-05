<?php
require_once 'includes/db.php';

$pdo = db();

echo "=== SPRAWDZENIE STRUKTURY TABELI PRODUCTS ===\n\n";

try {
    $result = $pdo->query('DESCRIBE products');
    echo "Istniejące kolumny:\n";
    while ($row = $result->fetch()) {
        echo "- {$row['Field']} ({$row['Type']}) - {$row['Null']} - {$row['Default']}\n";
    }

    echo "\n=== SPRAWDZENIE CZY ISTNIEJĄ KOLUMNY KAUCJI ===\n";

    $depositColumns = ['deposit_enabled', 'deposit_type', 'deposit_amount'];
    foreach ($depositColumns as $col) {
        $stmt = $pdo->query("SHOW COLUMNS FROM products LIKE '$col'");
        if ($stmt->rowCount() > 0) {
            echo "✅ Kolumna $col istnieje\n";
        } else {
            echo "❌ Kolumna $col NIE istnieje\n";
        }
    }
} catch (Exception $e) {
    echo "Błąd: " . $e->getMessage() . "\n";
}
