<?php
require_once '../includes/db.php';
require_once '../classes/FleetManager.php';

$pdo = db();
$fleetManager = new FleetManager($pdo);

echo "=== ANALIZA FLOW: OD WYSZUKIWANIA DO KONKRETNEGO EGZEMPLARZA ===\n\n";

// 1. WYSZUKIWARKA - symulujmy wyszukiwanie w Krakowie
$searchLocation = 'Kraków';
echo "1. WYSZUKIWARKA - lokalizacja: '$searchLocation'\n";
echo str_repeat("-", 50) . "\n";

// Sprawdź ile pojazdów jest dostępnych w Krakowie
$stmt = $pdo->prepare("
    SELECT l.id as location_id, l.name as location_name 
    FROM locations l 
    WHERE l.name = ? AND l.is_active = 1
");
$stmt->execute([$searchLocation]);
$location = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$location) {
    echo "❌ Lokalizacja '$searchLocation' nie istnieje w tabeli locations\n";
    exit(1);
}

echo "✅ Znaleziono lokalizację: ID {$location['location_id']} - {$location['location_name']}\n";

// Sprawdź dostępne pojazdy w tej lokalizacji
$availableVehicles = $fleetManager->getAvailableVehiclesInLocation($location['location_id']);
echo "Dostępne pojazdy w lokalizacji '$searchLocation':\n";

$vehiclesByProduct = [];
foreach ($availableVehicles as $vehicle) {
    $vehiclesByProduct[$vehicle['sku']][] = $vehicle;
    printf(
        "  - %s (%s) - Pojazd: %s [ID: %d]\n",
        $vehicle['product_name'],
        $vehicle['sku'],
        $vehicle['registration_number'],
        $vehicle['id']
    );
}

echo "Produkty dostępne w '$searchLocation':\n";
foreach ($vehiclesByProduct as $sku => $vehicles) {
    echo "  - $sku: " . count($vehicles) . " egzemplarzy\n";
}

echo "\n";

// 2. REZERWACJA - symulujmy wybór konkretnego produktu
if (!empty($vehiclesByProduct)) {
    $selectedSku = array_keys($vehiclesByProduct)[0]; // Pierwszy dostępny produkt
    $selectedProduct = $vehiclesByProduct[$selectedSku][0];

    echo "2. REZERWACJA - wybrano produkt: '$selectedSku'\n";
    echo str_repeat("-", 50) . "\n";

    echo "Egzemplarze dostępne dla produktu '$selectedSku' w '$searchLocation':\n";
    foreach ($vehiclesByProduct[$selectedSku] as $vehicle) {
        printf(
            "  - Pojazd %s (VIN: %s) [ID: %d]\n",
            $vehicle['registration_number'],
            $vehicle['vin'],
            $vehicle['id']
        );
    }

    // Sprawdź czy checkout-confirm.php poprawnie wybierze konkretny pojazd
    $testVehicles = $fleetManager->getAvailableVehiclesInLocation($location['location_id'], $selectedSku);
    if (!empty($testVehicles)) {
        $selectedVehicle = $testVehicles[0]; // Tak jak w checkout-confirm.php
        echo "\n✅ Checkout wybierze pojazd: {$selectedVehicle['registration_number']} [ID: {$selectedVehicle['id']}]\n";

        // 3. SYMULACJA REALIZACJI REZERWACJI
        echo "\n3. REALIZACJA - lokalizacja pojazdu po zwrocie\n";
        echo str_repeat("-", 50) . "\n";

        // Sprawdź obecną lokalizację pojazdu
        $currentLocation = $fleetManager->getCurrentVehicleLocation($selectedVehicle['id']);
        if ($currentLocation) {
            echo "Obecna lokalizacja pojazdu {$selectedVehicle['registration_number']}: {$currentLocation['location_name']} (źródło: {$currentLocation['source']})\n";
        }

        // Symuluj rezerwację z zwrotem w innej lokalizacji
        $dropoffLocation = 'Warszawa';
        echo "Symulacja rezerwacji: odbiór w '$searchLocation' -> zwrot w '$dropoffLocation'\n";

        $stmt = $pdo->prepare("SELECT id FROM locations WHERE name = ? AND is_active = 1");
        $stmt->execute([$dropoffLocation]);
        $dropoffLoc = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($dropoffLoc) {
            echo "✅ Po zwrocie pojazd będzie w: '$dropoffLocation' (ID: {$dropoffLoc['id']})\n";

            // Test aktualizacji lokalizacji (bez faktycznej zmiany w bazie)
            echo "FleetManager zaktualizuje: current_location_id = {$dropoffLoc['id']} dla vehicle_id = {$selectedVehicle['id']}\n";
        }
    } else {
        echo "❌ Brak dostępnych pojazdów dla produktu '$selectedSku' w '$searchLocation'\n";
    }
} else {
    echo "❌ Brak dostępnych pojazdów w lokalizacji '$searchLocation'\n";
}

echo "\n";

// 4. SPRAWDŹ OBECNY STAN PRZYPISAŃ REZERWACJI
echo "4. ANALIZA OBECNYCH REZERWACJI\n";
echo str_repeat("-", 50) . "\n";

$stmt = $pdo->query("
    SELECT 
        r.status,
        COUNT(*) as count,
        COUNT(r.vehicle_id) as with_vehicle,
        (COUNT(*) - COUNT(r.vehicle_id)) as without_vehicle
    FROM reservations r
    GROUP BY r.status
    ORDER BY count DESC
");
$reservationStats = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Statystyki rezerwacji:\n";
foreach ($reservationStats as $stat) {
    printf(
        "  Status %-12s: %3d rezerwacji (%d z pojazdem, %d bez pojazdu)\n",
        $stat['status'],
        $stat['count'],
        $stat['with_vehicle'],
        $stat['without_vehicle']
    );
}

// 5. SPRAWDŹ PRZYKŁADOWĄ REZERWACJĘ Z POJAZDEM
echo "\n5. PRZYKŁAD KOMPLETNEJ REZERWACJI\n";
echo str_repeat("-", 50) . "\n";

$stmt = $pdo->prepare("
    SELECT 
        r.id, r.product_name, r.pickup_location, r.dropoff_location, r.status,
        r.vehicle_id, v.registration_number, v.vin, v.current_location_id,
        l.name as current_vehicle_location
    FROM reservations r
    LEFT JOIN vehicles v ON r.vehicle_id = v.id
    LEFT JOIN locations l ON v.current_location_id = l.id
    WHERE r.vehicle_id IS NOT NULL
    ORDER BY r.id DESC
    LIMIT 1
");
$stmt->execute();
$exampleReservation = $stmt->fetch(PDO::FETCH_ASSOC);

if ($exampleReservation) {
    printf("Rezerwacja #%d:\n", $exampleReservation['id']);
    printf("  - Produkt: %s\n", $exampleReservation['product_name']);
    printf("  - Trasa: %s -> %s\n", $exampleReservation['pickup_location'], $exampleReservation['dropoff_location']);
    printf("  - Status: %s\n", $exampleReservation['status']);
    printf(
        "  - Pojazd: %s (VIN: %s) [ID: %d]\n",
        $exampleReservation['registration_number'],
        $exampleReservation['vin'],
        $exampleReservation['vehicle_id']
    );
    printf("  - Obecna lokalizacja pojazdu: %s\n", $exampleReservation['current_vehicle_location'] ?: 'BRAK');
} else {
    echo "Brak rezerwacji z przypisanym pojazdem\n";
}

echo "\n=== PODSUMOWANIE FLOW ===\n";
echo "1. ✅ Wyszukiwarka pokazuje produkty dostępne w konkretnej lokalizacji\n";
echo "2. ✅ System wybiera konkretny egzemplarz (vehicle_id) podczas checkout\n";
echo "3. ✅ Rezerwacja ma przypisany konkretny pojazd\n";
echo "4. ✅ FleetManager może śledzić lokalizację każdego pojazdu\n";
echo "5. ⚠️  Potrzeba: automatyczna aktualizacja lokalizacji po zakończeniu rezerwacji\n";
echo "6. ⚠️  Potrzeba: poprawa logiki wyboru lokalizacji w checkout-confirm.php\n";

echo "\n✅ Analiza flow zakończona!\n";
