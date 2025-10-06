<?php
require_once '../includes/db.php';
require_once '../classes/FleetManager.php';

$pdo = db();
$fleetManager = new FleetManager($pdo);

echo "=== TEST FLEET MANAGER - AKTUALNE LOKALIZACJE POJAZDÓW ===\n\n";

// Sprawdź czy Fleet Management jest włączony
if (!$fleetManager->isEnabled()) {
    echo "⚠️  Fleet Management jest wyłączony. Włączam...\n";
    $stmt = $pdo->prepare("INSERT INTO shop_settings (setting_key, setting_value) VALUES ('fleet_management_enabled', '1') ON DUPLICATE KEY UPDATE setting_value = '1'");
    $stmt->execute();
    echo "✅ Fleet Management włączony\n\n";
}

// Pobierz wszystkie pojazdy
$stmt = $pdo->query("
    SELECT 
        v.id, 
        v.vin, 
        v.registration_number, 
        v.current_location_id,
        l.name as current_location_name,
        p.name as product_name
    FROM vehicles v
    LEFT JOIN locations l ON v.current_location_id = l.id  
    LEFT JOIN products p ON v.product_id = p.id
    WHERE v.status IN ('available', 'booked', 'maintenance')
    ORDER BY v.id
");
$vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Aktualny stan lokalizacji pojazdów:\n";
echo str_repeat("-", 80) . "\n";
printf("%-5s %-15s %-12s %-18s %-20s\n", "ID", "VIN", "Rejestr.", "Obecna lokalizacja", "Model");
echo str_repeat("-", 80) . "\n";

foreach ($vehicles as $vehicle) {
    printf(
        "%-5s %-15s %-12s %-18s %-20s\n",
        $vehicle['id'],
        substr($vehicle['vin'], 0, 15),
        $vehicle['registration_number'],
        $vehicle['current_location_name'] ?: 'BRAK',
        substr($vehicle['product_name'] ?: 'BRAK', 0, 20)
    );
}

echo "\n";

// Sprawdź lokalizacje na podstawie ostatnich rezerwacji
echo "Aktualizacja na podstawie ostatnich rezerwacji:\n";
echo str_repeat("-", 80) . "\n";

foreach ($vehicles as $vehicle) {
    $location = $fleetManager->getCurrentVehicleLocation($vehicle['id']);

    if ($location) {
        $sourceLabel = [
            'vehicle_table' => '✅ Aktualne',
            'last_reservation' => '🔄 Zaktualizowano',
            'auto_sync' => '🔄 Zsynchronizowano',
            'default_fallback' => '⚠️  Domyślne'
        ][$location['source']] ?? $location['source'];

        printf(
            "%-15s %-12s -> %-18s %s\n",
            substr($vehicle['vin'], 0, 15),
            $vehicle['registration_number'],
            $location['location_name'],
            $sourceLabel
        );
    } else {
        printf(
            "%-15s %-12s -> %-18s ❌ Błąd\n",
            substr($vehicle['vin'], 0, 15),
            $vehicle['registration_number'],
            'BRAK LOKALIZACJI'
        );
    }
}

echo "\n";

// Synchronizacja wszystkich pojazdów
echo "Przeprowadzam pełną synchronizację...\n";
$syncResults = $fleetManager->syncAllVehicleLocations();

echo "Wyniki synchronizacji:\n";
echo "- Zaktualizowano: {$syncResults['updated']} pojazdów\n";
echo "- Pominięto (już aktualne): {$syncResults['skipped']} pojazdów\n";

if (!empty($syncResults['errors'])) {
    echo "- Błędy:\n";
    foreach ($syncResults['errors'] as $error) {
        echo "  * $error\n";
    }
}

echo "\n";

// Sprawdź stan po synchronizacji
echo "=== STAN PO SYNCHRONIZACJI ===\n";

$stmt = $pdo->query("
    SELECT 
        COUNT(*) as total_vehicles,
        COUNT(v.current_location_id) as vehicles_with_location,
        COUNT(DISTINCT v.current_location_id) as unique_locations
    FROM vehicles v
    WHERE v.status IN ('available', 'booked', 'maintenance')
");
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

echo "Statystyki:\n";
echo "- Łączna liczba aktywnych pojazdów: {$stats['total_vehicles']}\n";
echo "- Pojazdy z przypisaną lokalizacją: {$stats['vehicles_with_location']}\n";
echo "- Unikalne lokalizacje: {$stats['unique_locations']}\n";

echo "\n";

// Pokaż rozkład pojazdów po lokalizacjach
echo "Rozkład pojazdów po lokalizacjach:\n";
$stmt = $pdo->query("
    SELECT 
        l.name as location_name,
        COUNT(v.id) as vehicle_count
    FROM locations l
    LEFT JOIN vehicles v ON v.current_location_id = l.id AND v.status IN ('available', 'booked', 'maintenance')
    WHERE l.is_active = 1
    GROUP BY l.id, l.name
    ORDER BY vehicle_count DESC, l.name
");
$locationStats = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($locationStats as $stat) {
    printf("- %-15s: %2d pojazdów\n", $stat['location_name'], $stat['vehicle_count']);
}

echo "\n✅ Test FleetManager zakończony!\n";
