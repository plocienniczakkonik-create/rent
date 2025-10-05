<?php
require_once 'includes/config.php';
require_once 'includes/db.php';

$pdo = db();

echo "Sprawdzanie duplikatów tras:\n\n";

$stmt = $pdo->query("SELECT * FROM location_fees WHERE (pickup_location_id = 4 AND return_location_id = 5) OR (pickup_location_id = 5 AND return_location_id = 4)");
while ($row = $stmt->fetch()) {
    echo "ID: {$row['id']}, Z: {$row['pickup_location_id']}, Do: {$row['return_location_id']}, Opłata: {$row['fee_amount']}\n";
}

echo "\nCałe opłaty w bazie:\n";
$stmt = $pdo->query("SELECT lf.*, l1.name as pickup_name, l2.name as return_name FROM location_fees lf JOIN locations l1 ON lf.pickup_location_id = l1.id JOIN locations l2 ON lf.return_location_id = l2.id ORDER BY lf.id");
while ($row = $stmt->fetch()) {
    echo "ID: {$row['id']}, {$row['pickup_name']} → {$row['return_name']}: {$row['fee_amount']} PLN\n";
}
