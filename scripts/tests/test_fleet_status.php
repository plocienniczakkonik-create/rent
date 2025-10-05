<?php
require_once 'includes/config.php';
require_once 'includes/db.php';

$pdo = db();

echo "Sprawdzanie struktury tabeli vehicles:\n";
$stmt = $pdo->query("DESCRIBE vehicles");
while ($row = $stmt->fetch()) {
    echo "Kolumna: {$row['Field']}, Typ: {$row['Type']}\n";
}

echo "\nSprawdzanie pojazdów dostępnych do testów:\n\n";

// Znajdź pojazdy w różnych statusach
$stmt = $pdo->query("SELECT v.id, v.product_id, v.registration_number, v.status, v.current_location_id, p.sku 
                     FROM vehicles v 
                     LEFT JOIN products p ON v.product_id = p.id 
                     ORDER BY v.id LIMIT 10");
while ($row = $stmt->fetch()) {
    echo "ID: {$row['id']}, Product_ID: {$row['product_id']}, SKU: {$row['sku']}, Rejestracja: {$row['registration_number']}, Status: {$row['status']}, Lokalizacja: {$row['current_location_id']}\n";
}

echo "\nSprawdzanie lokalizacji:\n";
$stmt = $pdo->query("SELECT id, name FROM locations ORDER BY id LIMIT 5");
while ($row = $stmt->fetch()) {
    echo "ID: {$row['id']}, Nazwa: {$row['name']}\n";
}

echo "\nSprawdzanie struktury tabeli location_fees:\n";
$stmt = $pdo->query("DESCRIBE location_fees");
while ($row = $stmt->fetch()) {
    echo "Kolumna: {$row['Field']}, Typ: {$row['Type']}\n";
}

echo "\nSprawdzanie opłat za trasy:\n";
$stmt = $pdo->query("SELECT * FROM location_fees ORDER BY id LIMIT 10");
while ($row = $stmt->fetch()) {
    echo "ID: {$row['id']}, Z: {$row['pickup_location_id']}, Do: {$row['return_location_id']}, Opłata: {$row['fee_amount']} PLN, Typ: {$row['fee_type']}\n";
}

echo "\nSprawdzanie produktów z ustawieniami kaucji:\n";
$stmt = $pdo->query("SELECT sku, name, deposit_amount, deposit_type FROM products WHERE deposit_amount > 0 LIMIT 5");
while ($row = $stmt->fetch()) {
    echo "SKU: {$row['sku']}, Nazwa: {$row['name']}, Kaucja: {$row['deposit_amount']} ({$row['deposit_type']})\n";
}
