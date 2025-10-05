<?php
require_once 'includes/config.php';
require_once 'includes/db.php';

$pdo = db();

echo "=== USUWANIE DUPLIKATÓW TRAS ===\n\n";

// Znajdź wszystkie pary tras i usuń duplikaty
$stmt = $pdo->query("
    SELECT lf1.id as id1, lf1.pickup_location_id as p1, lf1.return_location_id as r1, lf1.fee_amount as fee1,
           lf2.id as id2, lf2.pickup_location_id as p2, lf2.return_location_id as r2, lf2.fee_amount as fee2
    FROM location_fees lf1
    JOIN location_fees lf2 ON lf1.pickup_location_id = lf2.return_location_id 
                          AND lf1.return_location_id = lf2.pickup_location_id
                          AND lf1.id < lf2.id
    ORDER BY lf1.id
");

$toDelete = [];
$duplicates = $stmt->fetchAll();

foreach ($duplicates as $dup) {
    echo "Znaleziono duplikat:\n";
    echo "  ID {$dup['id1']}: {$dup['p1']}→{$dup['r1']} = {$dup['fee1']} PLN\n";
    echo "  ID {$dup['id2']}: {$dup['p2']}→{$dup['r2']} = {$dup['fee2']} PLN\n";

    // Usuń trasę z wyższym ID (pozostaw starszą)
    $toDelete[] = $dup['id2'];
    echo "  → Usuwam ID {$dup['id2']}\n\n";
}

if (!empty($toDelete)) {
    $placeholders = str_repeat('?,', count($toDelete) - 1) . '?';
    $stmt = $pdo->prepare("DELETE FROM location_fees WHERE id IN ($placeholders)");
    $stmt->execute($toDelete);

    echo "✅ Usunięto " . count($toDelete) . " duplikatów\n\n";
} else {
    echo "ℹ️ Brak duplikatów do usunięcia\n\n";
}

echo "Stan po oczyszczeniu:\n";
$stmt = $pdo->query("SELECT lf.*, l1.name as pickup_name, l2.name as return_name FROM location_fees lf JOIN locations l1 ON lf.pickup_location_id = l1.id JOIN locations l2 ON lf.return_location_id = l2.id ORDER BY lf.id");
while ($row = $stmt->fetch()) {
    echo "ID: {$row['id']}, {$row['pickup_name']} → {$row['return_name']}: {$row['fee_amount']} PLN\n";
}

echo "\n=== Oczyszczanie zakończone ===\n";
