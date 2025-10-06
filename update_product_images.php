<?php
require 'includes/db.php';

echo "Updating product image paths...\n";
echo "==============================\n";

// Mapowanie produktów do dostępnych zdjęć
$imageMapping = [
    'Toyota Corolla' => 'assets/img/nissan.jpg', // Używamy Nissan jako placeholder dla Toyoty
    'VW Golf' => 'assets/img/skoda.jpg',          // Skoda dla VW
    'FIAT' => 'assets/img/fiat.jpg',              // Pasujące
    'AUDI A5' => 'assets/img/audi.jpg',           // Pasujące
    'Fiat 800' => 'assets/img/fiat.jpg',          // Pasujące
    'BMW X3' => 'assets/img/mercedes.jpg',        // Mercedes dla BMW
    'Mercedes Sprinter' => 'assets/img/mercedes.jpg', // Pasujące
    'Audi A4' => 'assets/img/audi.jpg',           // Pasujące
    'Ford Focus' => 'assets/img/mustang.jpg',     // Ford Mustang dla Focus
    'Volvo XC60' => 'assets/img/volvo.jpg',       // Pasujące
    'Renault Clio' => 'assets/img/renault.jpg'   // Pasujące
];

$db = db();

foreach ($imageMapping as $productName => $imagePath) {
    try {
        $stmt = $db->prepare("UPDATE products SET image_path = ? WHERE name = ?");
        $result = $stmt->execute([$imagePath, $productName]);

        if ($result && $stmt->rowCount() > 0) {
            echo "✓ Updated '$productName' -> '$imagePath'\n";
        } else {
            echo "- No product found with name '$productName'\n";
        }
    } catch (PDOException $e) {
        echo "✗ Error updating '$productName': " . $e->getMessage() . "\n";
    }
}

echo "\nCurrent products after update:\n";
echo "==============================\n";

$stmt = $db->query('SELECT id, name, image_path FROM products ORDER BY id LIMIT 12');
while ($row = $stmt->fetch()) {
    echo sprintf(
        "ID: %d | Name: %s | Image: %s\n",
        $row['id'],
        $row['name'],
        $row['image_path'] ?? 'NULL'
    );
}

echo "\nUpdate completed!\n";
