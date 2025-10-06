<?php
require 'includes/db.php';

echo "Fixing remaining empty fuel fields...\n";
echo "====================================\n";

$db = db();

// Uzupełnij puste pola fuel
$stmt = $db->prepare("UPDATE products SET fuel = 'Hybrid' WHERE (fuel IS NULL OR fuel = '') AND status = 'active'");
$result = $stmt->execute();

if($result) {
    echo "✓ Updated " . $stmt->rowCount() . " products with missing fuel data\n";
}

echo "\nFinal check - all products with complete data:\n";
echo "=============================================\n";

$stmt = $db->query("SELECT id, name, car_type, seats, doors, gearbox, fuel FROM products WHERE status = 'active' ORDER BY id");
while($row = $stmt->fetch()) {
    echo sprintf("ID: %d | %-20s | %-10s | %d seats | %d doors | %-12s | %s\n", 
        $row['id'], 
        substr($row['name'], 0, 20),
        $row['car_type'] ?? 'N/A',
        $row['seats'] ?? 0,
        $row['doors'] ?? 0,
        $row['gearbox'] ?? 'N/A',
        $row['fuel'] ?? 'N/A'
    );
}

echo "\nUpdate completed!\n";
?>