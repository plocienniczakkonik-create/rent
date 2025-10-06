<?php
require 'includes/db.php';

echo "Filling missing product data with realistic values...\n";
echo "==================================================\n";

$db = db();

// Sprawdź co mamy dostępne w słownikach
echo "Checking available dictionary options:\n";

// Sprawdź dostępne opcje dla różnych pól
$gearboxOptions = [];
$fuelOptions = [];

// Pobierz produkty które potrzebują uzupełnienia
$stmt = $db->query("SELECT id, name, car_type, seats, doors, gearbox, fuel FROM products WHERE status = 'active'");
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Products to update: " . count($products) . "\n\n";

// Realistyczne mapowanie według typu samochodu
$carTypeMapping = [
    'Hatchback' => [
        'seats' => [4, 5],
        'doors' => [3, 5],
        'gearbox' => ['Manualna', 'Automatyczna'],
        'fuel' => ['Benzyna', 'Diesel', 'Hybrid']
    ],
    'Sedan' => [
        'seats' => [5, 7],
        'doors' => [4, 5],
        'gearbox' => ['Manualna', 'Automatyczna'],
        'fuel' => ['Benzyna', 'Diesel', 'Hybrid']
    ],
    'Kombi' => [
        'seats' => [5, 7, 9],
        'doors' => [5],
        'gearbox' => ['Manualna', 'Automatyczna'],
        'fuel' => ['Benzyna', 'Diesel']
    ],
    'Coupe' => [
        'seats' => [2, 4],
        'doors' => [2, 3],
        'gearbox' => ['Manualna', 'Automatyczna'],
        'fuel' => ['Benzyna', 'Hybrid']
    ]
];

// Domyślne wartości dla nieznanych typów
$defaultMapping = [
    'seats' => [5],
    'doors' => [4, 5],
    'gearbox' => ['Manualna', 'Automatyczna'],
    'fuel' => ['Benzyna', 'Diesel']
];

foreach($products as $product) {
    $updates = [];
    $params = ['id' => $product['id']];
    
    // Wybierz mapping na podstawie car_type
    $mapping = $carTypeMapping[$product['car_type']] ?? $defaultMapping;
    
    // Seats
    if(empty($product['seats']) || $product['seats'] == 0) {
        $seats = $mapping['seats'][array_rand($mapping['seats'])];
        $updates[] = 'seats = :seats';
        $params['seats'] = $seats;
    }
    
    // Doors
    if(empty($product['doors']) || $product['doors'] == 0) {
        $doors = $mapping['doors'][array_rand($mapping['doors'])];
        $updates[] = 'doors = :doors';
        $params['doors'] = $doors;
    }
    
    // Gearbox
    if(empty($product['gearbox'])) {
        $gearbox = $mapping['gearbox'][array_rand($mapping['gearbox'])];
        $updates[] = 'gearbox = :gearbox';
        $params['gearbox'] = $gearbox;
    }
    
    // Fuel
    if(empty($product['fuel'])) {
        $fuel = $mapping['fuel'][array_rand($mapping['fuel'])];
        $updates[] = 'fuel = :fuel';
        $params['fuel'] = $fuel;
    }
    
    // Jeśli są jakieś aktualizacje do zrobienia
    if(!empty($updates)) {
        $sql = "UPDATE products SET " . implode(', ', $updates) . " WHERE id = :id";
        
        try {
            $stmt = $db->prepare($sql);
            $result = $stmt->execute($params);
            
            if($result) {
                $updatedFields = [];
                if(isset($params['seats'])) $updatedFields[] = "seats: {$params['seats']}";
                if(isset($params['doors'])) $updatedFields[] = "doors: {$params['doors']}";
                if(isset($params['gearbox'])) $updatedFields[] = "gearbox: {$params['gearbox']}";
                if(isset($params['fuel'])) $updatedFields[] = "fuel: {$params['fuel']}";
                
                echo "✓ Updated '{$product['name']}' ({$product['car_type']}) - " . implode(', ', $updatedFields) . "\n";
            }
        } catch(PDOException $e) {
            echo "✗ Error updating product {$product['id']}: " . $e->getMessage() . "\n";
        }
    } else {
        echo "- '{$product['name']}' already has complete data\n";
    }
}

echo "\nFinal verification - all products:\n";
echo "==================================\n";

$stmt = $db->query("SELECT id, name, car_type, seats, doors, gearbox, fuel FROM products WHERE status = 'active' ORDER BY id LIMIT 12");
while($row = $stmt->fetch()) {
    echo sprintf("ID: %d | %s (%s) | %d seats, %d doors, %s, %s\n", 
        $row['id'], 
        $row['name'],
        $row['car_type'] ?? 'N/A',
        $row['seats'] ?? 0,
        $row['doors'] ?? 0,
        $row['gearbox'] ?? 'N/A',
        $row['fuel'] ?? 'N/A'
    );
}

echo "\nUpdate completed!\n";
?>