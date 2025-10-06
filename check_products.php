<?php
require 'includes/db.php';

echo "Current products and their image paths:\n";
echo "=====================================\n";

$stmt = db()->query('SELECT id, name, image_path FROM products LIMIT 10');
while($row = $stmt->fetch()) {
    echo sprintf("ID: %d | Name: %s | Image: %s\n", 
        $row['id'], 
        $row['name'], 
        $row['image_path'] ?? 'NULL'
    );
}
?>