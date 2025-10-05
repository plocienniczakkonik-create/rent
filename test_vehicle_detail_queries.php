<?php
// Test specific vehicle detail page
require_once 'includes/db.php';
$db = db();

$id = 13; // from previous test
echo "Testing vehicle detail for ID: $id" . PHP_EOL;

try {
    // Main vehicle query from vehicle-detail.php
    $stmt = $db->prepare("SELECT v.*, p.name AS product_name
                          FROM vehicles v
                          JOIN products p ON p.id = v.product_id
                          WHERE v.id = :id");
    $stmt->execute([':id' => $id]);
    $veh = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$veh) {
        echo "ERROR: Vehicle not found!" . PHP_EOL;
        exit;
    }

    echo "Vehicle found: " . $veh['registration_number'] . PHP_EOL;
    echo "Product: " . $veh['product_name'] . PHP_EOL;
    echo "Status: " . $veh['status'] . PHP_EOL;

    // Test services query
    $q1 = $db->prepare("SELECT * FROM vehicle_services WHERE vehicle_id = ? ORDER BY service_date DESC, id DESC LIMIT 20");
    $q1->execute([(int)$veh['id']]);
    $services = $q1->fetchAll(PDO::FETCH_ASSOC);
    echo "Services found: " . count($services) . PHP_EOL;

    // Test incidents query
    $q2 = $db->prepare("SELECT * FROM vehicle_incidents WHERE vehicle_id = ? ORDER BY incident_date DESC, id DESC LIMIT 20");
    $q2->execute([(int)$veh['id']]);
    $incidents = $q2->fetchAll(PDO::FETCH_ASSOC);
    echo "Incidents found: " . count($incidents) . PHP_EOL;

    // Test orders query
    $q3 = $db->prepare("SELECT * FROM orders WHERE vehicle_id = ? ORDER BY start_date DESC, id DESC LIMIT 20");
    $q3->execute([(int)$veh['id']]);
    $orders = $q3->fetchAll(PDO::FETCH_ASSOC);
    echo "Orders found: " . count($orders) . PHP_EOL;

    echo "All queries successful!" . PHP_EOL;
} catch (Exception $e) {
    echo 'ERROR: ' . $e->getMessage() . PHP_EOL;
    echo 'Line: ' . $e->getLine() . PHP_EOL;
    echo 'File: ' . $e->getFile() . PHP_EOL;
}
