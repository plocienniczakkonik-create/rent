<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'classes/LocationFeeManager.php';

$pdo = db();
$locationFeeManager = new LocationFeeManager($pdo);

echo "=== TEST WALIDACJI UNIKALNOŚCI TRAS ===\n\n";

echo "1. Test dodawania nowej trasy Wrocław→Poznań...\n";
$result1 = $locationFeeManager->setLocationFee(4, 5, 80.0); // Wrocław→Poznań
print_r($result1);

echo "\n2. Test duplikowania tej samej trasy Wrocław→Poznań...\n";
$result2 = $locationFeeManager->setLocationFee(4, 5, 90.0); // Duplikat
print_r($result2);

echo "\n3. Test dodawania trasy odwrotnej Poznań→Wrocław...\n";
$result3 = $locationFeeManager->setLocationFee(5, 4, 85.0); // Symetryczna
print_r($result3);

echo "\n4. Test tej samej lokalizacji...\n";
$result4 = $locationFeeManager->setLocationFee(4, 4, 100.0); // Ta sama lokalizacja
print_r($result4);

echo "\n5. Test sprawdzenia istniejącej trasy...\n";
$exists = $locationFeeManager->routeExists(4, 5);
echo "Wrocław→Poznań istnieje: " . ($exists['exists'] ? 'TAK' : 'NIE') . "\n";
if ($exists['direct']) echo "Bezpośrednia: {$exists['direct']['fee_amount']} PLN\n";
if ($exists['reverse']) echo "Odwrotna: {$exists['reverse']['fee_amount']} PLN\n";

echo "\n6. Test aktualizacji istniejącej trasy...\n";
$result5 = $locationFeeManager->updateLocationFee(4, 5, 75.0);
print_r($result5);

echo "\n=== Test zakończony ===\n";
