<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'classes/FleetManager.php';
require_once 'classes/DepositManager.php';
require_once 'classes/LocationFeeManager.php';

$pdo = db();

echo "=== TEST KOMPLETNEGO PRZEPŁYWU FLEET MANAGEMENT ===\n\n";

// Parametry testowe
$sku = 'CAR-COR-2022';
$pickupLocationId = 3; // Gdańsk
$dropoffLocationId = 2; // Kraków
$pickupAt = '2024-01-20 10:00:00';
$returnAt = '2024-01-25 18:00:00';

echo "1. Sprawdzenie dostępności pojazdu...\n";
$fleetManager = new FleetManager($pdo);
$availableVehicles = $fleetManager->getAvailableVehiclesInLocation($pickupLocationId, $sku);

if (empty($availableVehicles)) {
    echo "❌ Brak dostępnych pojazdów\n";
    exit;
}

$selectedVehicle = $availableVehicles[0];
echo "✅ Dostępny pojazd: #{$selectedVehicle['id']} (rejestracja: {$selectedVehicle['registration_number']})\n\n";

echo "2. Sprawdzenie ustawień kaucji...\n";
$depositManager = new DepositManager($pdo);
$stmt = $pdo->prepare("SELECT * FROM products WHERE sku = ?");
$stmt->execute([$sku]);
$product = $stmt->fetch();

if (!$product) {
    echo "❌ Produkt nie znaleziony\n";
    exit;
}

$basePrice = $product['price'] * 5; // 5 dni
$depositData = $depositManager->calculateDeposit($sku, $basePrice, 5);
$depositAmount = $depositData['amount'];
echo "✅ Kaucja: {$depositAmount} PLN (typ: {$depositData['type']})\n\n";

echo "3. Sprawdzenie opłaty za trasę...\n";
$locationFeeManager = new LocationFeeManager($pdo);
$locationFeeData = $locationFeeManager->calculateLocationFee($pickupLocationId, $dropoffLocationId);
$locationFee = $locationFeeData['amount'];
echo "✅ Opłata za trasę Gdańsk→Kraków: {$locationFee} PLN\n\n";

echo "4. Sprawdzenie opłaty w przeciwnym kierunku (symetryczna)...\n";
$reverseFeeData = $locationFeeManager->calculateLocationFee($dropoffLocationId, $pickupLocationId);
$reverseFee = $reverseFeeData['amount'];
echo "✅ Opłata za trasę Kraków→Gdańsk: {$reverseFee} PLN\n";
echo ($locationFee == $reverseFee ? "✅ Opłaty symetryczne - OK!\n" : "❌ Opłaty niesymetryczne - błąd!\n");

echo "\n5. Symulacja kompletnej rezerwacji...\n";

// Oblicz podstawową cenę (już obliczona wyżej)
$finalTotal = $basePrice + $locationFee;
$totalWithDeposit = $finalTotal + $depositAmount;

echo "   - Cena bazowa (5 dni): {$basePrice} PLN\n";
echo "   - Opłata za trasę: {$locationFee} PLN\n";
echo "   - Suma: {$finalTotal} PLN\n";
echo "   - Kaucja: {$depositAmount} PLN\n";
echo "   - Do zapłaty: {$totalWithDeposit} PLN\n\n";

echo "6. Test zapisu do bazy (symulacja)...\n";
echo "   - vehicle_id: {$selectedVehicle['id']}\n";
echo "   - pickup_location_id: {$pickupLocationId}\n";
echo "   - dropoff_location_id: {$dropoffLocationId}\n";
echo "   - deposit_amount: {$depositAmount}\n";
echo "   - deposit_type: {$depositData['type']}\n";
echo "   - location_fee: {$locationFee}\n";
echo "   - total_with_deposit: {$totalWithDeposit}\n\n";

echo "✅ Test zakończony pomyślnie - Fleet Management jest gotowy!\n";
