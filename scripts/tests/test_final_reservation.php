<?php
require_once 'includes/config.php';
require_once 'includes/db.php';

$pdo = db();

echo "=== TEST FINALNEJ REZERWACJI PRZEZ checkout-confirm.php ===\n\n";

// Symuluj dane POST jak z checkout.php
$testData = [
    'sku' => 'CAR-COR-2022',
    'pickup_location' => 'Gdańsk Port (Gdańsk)',
    'dropoff_location' => 'Kraków Główny (Kraków)',
    'pickup_at' => '2024-01-20 10:00',
    'return_at' => '2024-01-25 18:00',
    'customer_name' => 'Jan Testowy',
    'customer_email' => 'jan.testowy@test.pl',
    'customer_phone' => '+48123456789',
    'billing_city' => 'Warszawa',
    'billing_address' => 'ul. Testowa 123',
    'billing_postcode' => '00-001',
    'billing_country' => 'Polska',
    'addons' => []
];

// Zapisz dane do sesji (symulacja)
session_start();
foreach ($testData as $key => $value) {
    $_SESSION[$key] = $value;
}

echo "1. Dane testowe zapisane do sesji\n";
echo "   - SKU: {$testData['sku']}\n";
echo "   - Trasa: {$testData['pickup_location']} → {$testData['dropoff_location']}\n";
echo "   - Terminy: {$testData['pickup_at']} - {$testData['return_at']}\n\n";

echo "2. Sprawdzenie czy wszystkie pliki istnieją:\n";
$files = [
    'pages/checkout.php',
    'pages/checkout-confirm.php',
    'classes/FleetManager.php',
    'classes/DepositManager.php',
    'classes/LocationFeeManager.php'
];

foreach ($files as $file) {
    echo ($file_exists = file_exists($file) ? "✅" : "❌") . " {$file}\n";
    if (!$file_exists) {
        echo "Błąd: Brak pliku {$file}\n";
        exit;
    }
}

echo "\n3. Test dostępności checkout-confirm.php przez URL:\n";

// Generuj URL do checkout-confirm.php
$checkoutUrl = "http://localhost/rental/index.php?page=checkout&sku=CAR-COR-2022&pickup_location=" . urlencode('Gdańsk Port (Gdańsk)') . "&dropoff_location=" . urlencode('Kraków Główny (Kraków)') . "&pickup_at=" . urlencode('2024-01-20 10:00') . "&return_at=" . urlencode('2024-01-25 18:00');

echo "URL checkout: {$checkoutUrl}\n\n";

echo "4. Test bezpośredniego include checkout-confirm.php (symulacja POST):\n";

// Symuluj POST request
$_POST = [
    'customer_name' => $testData['customer_name'],
    'customer_email' => $testData['customer_email'],
    'customer_phone' => $testData['customer_phone'],
    'billing_city' => $testData['billing_city'],
    'billing_address' => $testData['billing_address'],
    'billing_postcode' => $testData['billing_postcode'],
    'billing_country' => $testData['billing_country']
];

echo "   - POST data prepared\n";
echo "   - Próba include checkout-confirm.php...\n";

try {
    ob_start();
    include 'pages/checkout-confirm.php';
    $output = ob_get_clean();

    if (strpos($output, 'Rezerwacja złożona pomyślnie') !== false) {
        echo "✅ Rezerwacja została pomyślnie utworzona!\n";

        // Sprawdź ostatnią rezerwację w bazie
        $stmt = $pdo->query("SELECT * FROM reservations ORDER BY id DESC LIMIT 1");
        $lastReservation = $stmt->fetch();

        if ($lastReservation) {
            echo "\n5. Sprawdzenie zapisanej rezerwacji:\n";
            echo "   - ID rezerwacji: {$lastReservation['id']}\n";
            echo "   - Vehicle ID: " . ($lastReservation['vehicle_id'] ?? 'brak') . "\n";
            echo "   - Pickup location ID: " . ($lastReservation['pickup_location_id'] ?? 'brak') . "\n";
            echo "   - Dropoff location ID: " . ($lastReservation['dropoff_location_id'] ?? 'brak') . "\n";
            echo "   - Location fee: " . ($lastReservation['location_fee'] ?? 'brak') . "\n";
            echo "   - Deposit amount: " . ($lastReservation['deposit_amount'] ?? 'brak') . "\n";
            echo "   - Total with deposit: " . ($lastReservation['total_with_deposit'] ?? 'brak') . "\n";

            if ($lastReservation['vehicle_id'] && $lastReservation['pickup_location_id'] && $lastReservation['dropoff_location_id']) {
                echo "\n✅ Fleet Management data zapisane poprawnie!\n";
            } else {
                echo "\n❌ Brak danych Fleet Management w rezerwacji\n";
            }
        }
    } else if (strpos($output, 'błąd') !== false || strpos($output, 'error') !== false) {
        echo "❌ Błąd podczas tworzenia rezerwacji\n";
        echo "Output fragment: " . substr(strip_tags($output), 0, 200) . "...\n";
    } else {
        echo "⚠️ Nieoczekiwany output - sprawdź manually\n";
    }
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
}

echo "\n=== Test zakończony ===\n";
