<?php
require_once __DIR__ . '/includes/config.php';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Products in database:\n";
    $stmt = $pdo->query("SELECT id, name, category FROM products");
    while ($row = $stmt->fetch()) {
        echo "ID: {$row['id']}, Name: {$row['name']}, Category: {$row['category']}\n";
    }

    echo "\nAdding test vehicles...\n";

    // Dodaj przykładowe pojazdy
    $vehicles = [
        ['Volkswagen Golf', 'vehicle', 'Kompaktowy samochód osobowy'],
        ['BMW X5', 'vehicle', 'Luksusowy SUV'],
        ['Mercedes Sprinter', 'vehicle', 'Van do transportu'],
        ['Audi A4', 'vehicle', 'Sedan klasy średniej'],
        ['Ford Transit', 'vehicle', 'Furgon dostawczy']
    ];

    foreach ($vehicles as $vehicle) {
        $stmt = $pdo->prepare("INSERT INTO products (name, category, description, price_per_day, status) VALUES (?, ?, ?, ?, 'active')");
        $stmt->execute([$vehicle[0], $vehicle[1], $vehicle[2], rand(50, 300)]);
        echo "Added: {$vehicle[0]}\n";
    }

    echo "\nVehicles after addition:\n";
    $stmt = $pdo->query("SELECT id, name, category FROM products WHERE category = 'vehicle'");
    while ($row = $stmt->fetch()) {
        echo "ID: {$row['id']}, Name: {$row['name']}, Category: {$row['category']}\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
