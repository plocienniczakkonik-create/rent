<?php
require_once 'includes/db.php';
$db = db();

try {
    echo 'Testing database connection...' . PHP_EOL;
    $tables = ['vehicles', 'products', 'vehicle_services', 'vehicle_incidents', 'orders'];
    foreach ($tables as $table) {
        $result = $db->query('SHOW TABLES LIKE "' . $table . '"');
        if ($result->rowCount() > 0) {
            echo 'Table ' . $table . ' exists' . PHP_EOL;
        } else {
            echo 'Table ' . $table . ' NOT FOUND' . PHP_EOL;
        }
    }

    // Test basic vehicle query
    echo "\nTesting vehicle query..." . PHP_EOL;
    $stmt = $db->prepare("SELECT v.*, p.name AS product_name
                          FROM vehicles v
                          JOIN products p ON p.id = v.product_id
                          LIMIT 1");
    $stmt->execute();
    $vehicle = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($vehicle) {
        echo "Found vehicle: " . $vehicle['registration_number'] . " (ID: " . $vehicle['id'] . ")" . PHP_EOL;
    } else {
        echo "No vehicles found" . PHP_EOL;
    }
} catch (Exception $e) {
    echo 'Database error: ' . $e->getMessage() . PHP_EOL;
}
