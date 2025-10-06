<?php
// Test API dla BMW X3
require_once '../includes/db.php';

header('Content-Type: text/plain; charset=utf-8');

$dateFrom = '2025-08-01';
$dateTo = '2025-10-31';
$product = 'BMW X3';

echo "=== TEST API dla BMW X3 ===\n\n";

// Test zapytania vehicles
echo "1. Test głównego zapytania vehicles:\n";
$stmt = db()->prepare("
    SELECT 
        r.vehicle_id,
        v.vin,
        v.registration_number,
        p.name as model,
        COUNT(*) as reservations,
        SUM(r.total_gross) as revenue,
        AVG(r.rental_days) as avg_days
    FROM reservations r
    LEFT JOIN vehicles v ON r.vehicle_id = v.id
    LEFT JOIN products p ON v.product_id = p.id
    WHERE r.pickup_at BETWEEN ? AND ?
    AND r.vehicle_id IS NOT NULL
    AND (r.product_name LIKE ? OR p.name LIKE ?)
    GROUP BY r.vehicle_id, v.vin, v.registration_number, p.name
");

$params = [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59', '%' . $product . '%', '%' . $product . '%'];
$stmt->execute($params);
$withReservations = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Pojazdy z rezerwacjami: " . count($withReservations) . "\n";
foreach ($withReservations as $v) {
    echo "- {$v['registration_number']} ({$v['model']}): {$v['reservations']} rez, {$v['revenue']} PLN\n";
}

echo "\n2. Test pojazdów bez rezerwacji:\n";
$stmt = db()->prepare("
    SELECT 
        v.id as vehicle_id,
        v.vin,
        v.registration_number,
        p.name as model,
        0 as reservations,
        0 as revenue,
        0 as avg_days
    FROM vehicles v
    LEFT JOIN products p ON v.product_id = p.id
    WHERE p.name LIKE ?
    AND v.id NOT IN (
        SELECT DISTINCT r.vehicle_id 
        FROM reservations r 
        WHERE r.vehicle_id IS NOT NULL 
        AND r.pickup_at BETWEEN ? AND ?
        AND (r.product_name LIKE ? OR EXISTS(
            SELECT 1 FROM vehicles v2 
            LEFT JOIN products p2 ON v2.product_id = p2.id 
            WHERE v2.id = r.vehicle_id AND p2.name LIKE ?
        ))
    )
");

$params2 = ['%' . $product . '%', $dateFrom . ' 00:00:00', $dateTo . ' 23:59:59', '%' . $product . '%', '%' . $product . '%'];
$stmt->execute($params2);
$withoutReservations = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Pojazdy bez rezerwacji: " . count($withoutReservations) . "\n";
foreach ($withoutReservations as $v) {
    echo "- {$v['registration_number']} ({$v['model']}): brak rezerwacji\n";
}

echo "\n3. Wszystkie pojazdy BMW X3 w systemie:\n";
$stmt = db()->prepare("
    SELECT v.id, v.registration_number, p.name as model
    FROM vehicles v
    LEFT JOIN products p ON v.product_id = p.id
    WHERE p.name LIKE ?
");
$stmt->execute(['%BMW X3%']);
$allBmw = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Łączna liczba BMW X3: " . count($allBmw) . "\n";
foreach ($allBmw as $v) {
    echo "- ID {$v['id']}: {$v['registration_number']} ({$v['model']})\n";
}

echo "\n4. Rezerwacje BMW X3 w okresie:\n";
$stmt = db()->prepare("
    SELECT r.id, r.vehicle_id, r.product_name, r.total_gross, r.pickup_at
    FROM reservations r
    WHERE r.pickup_at BETWEEN ? AND ?
    AND (r.product_name LIKE ? OR r.vehicle_id IN (
        SELECT v.id FROM vehicles v 
        LEFT JOIN products p ON v.product_id = p.id 
        WHERE p.name LIKE ?
    ))
");
$stmt->execute([$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59', '%BMW X3%', '%BMW X3%']);
$reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Rezerwacje BMW X3: " . count($reservations) . "\n";
foreach ($reservations as $r) {
    echo "- Rez #{$r['id']}: {$r['product_name']}, pojazd {$r['vehicle_id']}, {$r['total_gross']} PLN\n";
}
