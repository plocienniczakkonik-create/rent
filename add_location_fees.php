<?php
require_once 'includes/db.php';

echo "Dodawanie opłat lokalizacyjnych...\n";

$stmt = db()->prepare('INSERT IGNORE INTO location_fees (pickup_location_id, return_location_id, fee_amount) VALUES (?, ?, ?)');

$fees = [
    [1, 2, 150.00], // Warszawa -> Kraków
    [1, 3, 200.00], // Warszawa -> Gdańsk  
    [1, 4, 120.00], // Warszawa -> Wrocław
    [1, 5, 100.00], // Warszawa -> Poznań
    [2, 1, 150.00], // Kraków -> Warszawa
    [2, 3, 250.00], // Kraków -> Gdańsk
    [2, 4, 180.00], // Kraków -> Wrocław
    [2, 5, 200.00], // Kraków -> Poznań
    [3, 1, 200.00], // Gdańsk -> Warszawa
    [3, 2, 250.00], // Gdańsk -> Kraków
    [3, 4, 300.00], // Gdańsk -> Wrocław
    [3, 5, 350.00], // Gdańsk -> Poznań
    [4, 1, 120.00], // Wrocław -> Warszawa
    [4, 2, 180.00], // Wrocław -> Kraków
    [4, 3, 300.00], // Wrocław -> Gdańsk
    [4, 5, 150.00], // Wrocław -> Poznań
    [5, 1, 100.00], // Poznań -> Warszawa
    [5, 2, 200.00], // Poznań -> Kraków
    [5, 3, 350.00], // Poznań -> Gdańsk
    [5, 4, 150.00]  // Poznań -> Wrocław
];

foreach ($fees as $fee) {
    $stmt->execute($fee);
}

$count = db()->query('SELECT COUNT(*) FROM location_fees')->fetchColumn();
echo "✓ Dodano opłaty lokalizacyjne\n";
echo "✓ Łącznie opłat w bazie: $count\n";
echo "✓ Baza Fleet Management jest gotowa!\n";
