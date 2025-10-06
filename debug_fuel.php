<?php
require 'includes/db.php';

echo "Checking and fixing fuel data directly...\n";
echo "========================================\n";

$db = db();

// Pobierz produkty bez fuel
$stmt = $db->query("SELECT id, name, fuel FROM products WHERE status = 'active' AND (fuel IS NULL OR fuel = '' OR TRIM(fuel) = '')");
$emptyFuel = $stmt->fetchAll();

echo "Products without fuel data: " . count($emptyFuel) . "\n";

foreach($emptyFuel as $product) {
    echo "ID: {$product['id']} | {$product['name']} | fuel: '{$product['fuel']}'\n";
    
    // Uzupełnij fuel dla każdego produktu indywidualnie
    $stmt = $db->prepare("UPDATE products SET fuel = 'Hybrid' WHERE id = ?");
    $result = $stmt->execute([$product['id']]);
    
    if($result) {
        echo "  ✓ Updated fuel to 'Hybrid'\n";
    }
}

echo "\nVerification after update:\n";
echo "=========================\n";

$stmt = $db->query("SELECT id, name, fuel FROM products WHERE status = 'active' ORDER BY id");
while($row = $stmt->fetch()) {
    $fuel = trim($row['fuel'] ?? '');
    $status = empty($fuel) ? "❌ EMPTY" : "✓ " . $fuel;
    echo sprintf("ID: %d | %-20s | %s\n", 
        $row['id'], 
        substr($row['name'], 0, 20),
        $status
    );
}
?>