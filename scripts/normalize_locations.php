<?php
require_once '../includes/db.php';

$pdo = db();

// Mapowanie pickup_location na standardowe nazwy z tabeli locations
$locationMapping = [
    // Warszawa
    'Warszawa' => 'Warszawa Centrum',
    'Warszawa Centrum' => 'Warszawa Centrum',
    'Warszawa Dworzec Centralny' => 'Warszawa Centrum',

    // Kraków
    'Kraków' => 'Kraków Główny',
    'Kraków Centrum' => 'Kraków Główny',
    'Kraków Dworzec Główny' => 'Kraków Główny',
    'Kraków Lotnisko Balice' => 'Kraków Główny',

    // Gdańsk
    'Gdańsk' => 'Gdańsk Port',
    'Gdańsk Centrum' => 'Gdańsk Port',
    'Gdańsk Lotnisko' => 'Gdańsk Port',
    'Gdańsk Port' => 'Gdańsk Port',

    // Poznań
    'Poznań' => 'Poznań Plaza',
    'Poznań Centrum' => 'Poznań Plaza',
    'Poznań Lotnisko Ławica' => 'Poznań Plaza',

    // Wrocław
    'Wrocław' => 'Wrocław Rynek',
    'Wrocław Dworzec' => 'Wrocław Rynek',
    'Wrocław Lotnisko' => 'Wrocław Rynek',

    // Inne miasta - zostaną zmapowane na najbliższą dostępną lokalizację
    'Katowice' => 'Kraków Główny',
    'Katowice Centrum' => 'Kraków Główny',
    'Katowice Lotnisko Pyrzowice' => 'Kraków Główny',
    'Katowice Spodek' => 'Kraków Główny',

    'Lublin' => 'Warszawa Centrum',
    'Lublin Centrum' => 'Warszawa Centrum',

    'Rzeszów' => 'Kraków Główny',

    'Szczecin' => 'Poznań Plaza',
    'Szczecin Centrum' => 'Poznań Plaza',

    'Łódź' => 'Warszawa Centrum',
    'Łódź Manufaktura' => 'Warszawa Centrum',

    'Bydgoszcz' => 'Poznań Plaza'
];

echo "=== NORMALIZACJA LOKALIZACJI W REZERWACJACH ===\n\n";

// Sprawdź aktualną sytuację
$stmt = $pdo->query("SELECT pickup_location, COUNT(*) as count FROM reservations WHERE pickup_location IS NOT NULL GROUP BY pickup_location ORDER BY pickup_location");
$currentLocations = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Aktualne pickup_location w rezerwacjach:\n";
foreach ($currentLocations as $loc) {
    $newLocation = $locationMapping[$loc['pickup_location']] ?? 'BRAK MAPOWANIA';
    echo sprintf("%-30s -> %-20s (%d rezerwacji)\n", $loc['pickup_location'], $newLocation, $loc['count']);
}

echo "\n";

// Sprawdź czy wszystkie lokalizacje mają mapowanie
$unmappedLocations = [];
foreach ($currentLocations as $loc) {
    if (!isset($locationMapping[$loc['pickup_location']])) {
        $unmappedLocations[] = $loc['pickup_location'];
    }
}

if (!empty($unmappedLocations)) {
    echo "UWAGA! Następujące lokalizacje nie mają mapowania:\n";
    foreach ($unmappedLocations as $loc) {
        echo "- $loc\n";
    }
    echo "\nDodaj mapowanie dla tych lokalizacji przed kontynuowaniem.\n";
    exit(1);
}

echo "Wszystkie lokalizacje mają mapowanie. Rozpoczynam normalizację...\n\n";

// Wykonaj normalizację
$updatedCount = 0;
$pdo->beginTransaction();

try {
    foreach ($locationMapping as $oldLocation => $newLocation) {
        $stmt = $pdo->prepare("UPDATE reservations SET pickup_location = ? WHERE pickup_location = ?");
        $result = $stmt->execute([$newLocation, $oldLocation]);

        if ($result) {
            $affected = $stmt->rowCount();
            if ($affected > 0) {
                echo "Zaktualizowano $affected rezerwacji: '$oldLocation' -> '$newLocation'\n";
                $updatedCount += $affected;
            }
        }
    }

    $pdo->commit();
    echo "\n=== NORMALIZACJA ZAKOŃCZONA ===\n";
    echo "Łącznie zaktualizowano: $updatedCount rezerwacji\n";

    // Sprawdź wynik
    echo "\nNowe stany pickup_location:\n";
    $stmt = $pdo->query("SELECT pickup_location, COUNT(*) as count FROM reservations WHERE pickup_location IS NOT NULL GROUP BY pickup_location ORDER BY pickup_location");
    $newLocations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($newLocations as $loc) {
        echo sprintf("%-30s (%d rezerwacji)\n", $loc['pickup_location'], $loc['count']);
    }
} catch (Exception $e) {
    $pdo->rollBack();
    echo "BŁĄD podczas normalizacji: " . $e->getMessage() . "\n";
}
