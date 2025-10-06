<?php
require 'includes/db.php';

echo "Updating remaining products without images...\n";
echo "===========================================\n";

// Dodatkowe mapowanie dla pozostałych produktów
$additionalMapping = [
    'VW Passat' => 'assets/img/skoda.jpg',       // Skoda dla VW
    'Peugeot 508' => 'assets/img/renault.jpg',   // Renault dla Peugeot
    'Renault Clio' => 'assets/img/renault.jpg'   // Już aktualizowane ale sprawdźmy
];

$db = db();

foreach ($additionalMapping as $productName => $imagePath) {
    try {
        $stmt = $db->prepare("UPDATE products SET image_path = ? WHERE name = ? AND (image_path IS NULL OR image_path = '')");
        $result = $stmt->execute([$imagePath, $productName]);

        if ($result && $stmt->rowCount() > 0) {
            echo "✓ Updated '$productName' -> '$imagePath'\n";
        } else {
            echo "- Product '$productName' already has image or not found\n";
        }
    } catch (PDOException $e) {
        echo "✗ Error updating '$productName': " . $e->getMessage() . "\n";
    }
}

// Sprawdźmy czy są jeszcze produkty bez zdjęć
echo "\nProducts still without images:\n";
$stmt = $db->query("SELECT id, name FROM products WHERE image_path IS NULL OR image_path = '' LIMIT 10");
$count = 0;
while ($row = $stmt->fetch()) {
    echo sprintf("ID: %d | Name: %s\n", $row['id'], $row['name']);
    $count++;
}

if ($count == 0) {
    echo "✓ All products now have images!\n";
} else {
    echo "Found $count products without images.\n";
}

echo "\nUpdate completed!\n";
