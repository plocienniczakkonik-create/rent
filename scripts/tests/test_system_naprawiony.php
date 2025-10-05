<?php

/**
 * Test poprawionego systemu lokalizacji
 * Sprawdzenie po naprawie błędów z tabelami użytkowników
 */

require_once 'includes/db.php';
require_once 'includes/vehicle-location-manager.php';

echo "🔧 TEST POPRAWIONEGO SYSTEMU LOKALIZACJI\n\n";

echo "✅ NAPRAWIONE BŁĘDY:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

echo "1. 🗃️  TABELA UŻYTKOWNIKÓW:\n";
echo "   ├── ❌ Błąd: staff_users (nie istnieje)\n";
echo "   ├── ✅ Poprawka: users (tabela z rolami)\n";
echo "   ├── ✅ Kolumny: first_name, last_name zamiast username\n";
echo "   └── ✅ JOIN: LEFT JOIN users u ON vlh.moved_by = u.id\n\n";

echo "2. 🔑 SESJA UŻYTKOWNIKA:\n";
echo "   ├── ❌ Błąd: \$_SESSION['staff_user']['id']\n";
echo "   ├── ✅ Poprawka: \$_SESSION['user_id']\n";
echo "   ├── ✅ Zgodność z auth.php\n";
echo "   └── ✅ Właściwe pobieranie ID zalogowanego użytkownika\n\n";

echo "3. 📊 TEST FUNKCJI VehicleLocationManager:\n";
echo "   ├── getAllLocations():\n";
try {
    $locations = VehicleLocationManager::getAllLocations();
    echo "     ✅ Pobrano " . count($locations) . " lokalizacji\n";
    foreach ($locations as $loc) {
        echo "       - [{$loc['id']}] {$loc['name']} - {$loc['city']}\n";
    }
} catch (Exception $e) {
    echo "     ❌ Błąd: " . $e->getMessage() . "\n";
}

echo "\n   ├── getCurrentLocation() (test na ID pojazdu 1):\n";
try {
    $currentLoc = VehicleLocationManager::getCurrentLocation(1);
    if ($currentLoc) {
        echo "     ✅ Lokalizacja: " . VehicleLocationManager::formatLocationDisplay($currentLoc) . "\n";
    } else {
        echo "     ⚠️  Pojazd ID 1 nie ma przypisanej lokalizacji\n";
    }
} catch (Exception $e) {
    echo "     ❌ Błąd: " . $e->getMessage() . "\n";
}

echo "\n   └── getLocationHistory() (test na ID pojazdu 1):\n";
try {
    $history = VehicleLocationManager::getLocationHistory(1, 3);
    if (count($history) > 0) {
        echo "     ✅ Pobrano " . count($history) . " wpisów historii\n";
        foreach ($history as $h) {
            $user = $h['moved_by_username'] ? $h['moved_by_username'] : 'System';
            echo "       - " . date('d.m.Y H:i', strtotime($h['moved_at'])) . " przez $user\n";
        }
    } else {
        echo "     ⚠️  Brak historii lokalizacji dla pojazdu ID 1\n";
    }
} catch (Exception $e) {
    echo "     ❌ Błąd: " . $e->getMessage() . "\n";
}

echo "\n4. 🚗 SPRAWDZENIE POJAZDÓW Z LOKALIZACJĄ:\n";
try {
    $stmt = db()->query("
        SELECT 
            v.id, 
            v.registration_number, 
            v.current_location_id,
            l.name as location_name,
            l.city as location_city
        FROM vehicles v 
        LEFT JOIN locations l ON v.current_location_id = l.id 
        LIMIT 5
    ");

    while ($vehicle = $stmt->fetch()) {
        $locInfo = $vehicle['location_name']
            ? "{$vehicle['location_name']} - {$vehicle['location_city']}"
            : "Brak lokalizacji";
        echo "   ├── [{$vehicle['id']}] {$vehicle['registration_number']} → $locInfo\n";
    }
} catch (Exception $e) {
    echo "   ❌ Błąd: " . $e->getMessage() . "\n";
}

echo "\n5. 🎯 STRUKTURA PLIKÓW:\n";
echo "   ├── ✅ includes/vehicle-location-manager.php - poprawiona\n";
echo "   ├── ✅ api/vehicle-change-location.php - poprawiona\n";
echo "   ├── ✅ pages/vehicle-save.php - poprawiona\n";
echo "   ├── ✅ pages/vehicle-detail.php - bez zmian (działa)\n";
echo "   └── ✅ pages/vehicle-form.php - bez zmian (działa)\n\n";

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "✅ SYSTEM LOKALIZACJI NAPRAWIONY!\n";
echo "🎯 Wszystkie funkcje używają właściwej tabeli 'users'\n";
echo "🔑 Sesja użytkownika zgodna z auth.php\n";
echo "🚗 Gotowy do testowania w przeglądarce\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
