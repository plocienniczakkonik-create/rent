<?php
require_once '../includes/db.php';

$pdo = db();

echo "=== NAPRAWA DROPOFF_LOCATION ===\n\n";

// Pobierz lokalizacje z dict_terms
$stmt = $pdo->prepare("
    SELECT t.name 
    FROM dict_terms t 
    JOIN dict_types dt ON dt.id = t.dict_type_id 
    WHERE dt.slug = 'location' AND t.status = 'active' 
    ORDER BY t.name
");
$stmt->execute();
$dictLocations = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Mapowanie wszystkich możliwych dropoff_location na dict_terms
$dropoffMapping = [
    // Warszawa
    'Warszawa' => 'Warszawa',
    'Warszawa Dworzec Centralny' => 'Warszawa',

    // Kraków  
    'Kraków' => 'Kraków',
    'Kraków Centrum' => 'Kraków',
    'Kraków Dworzec Główny' => 'Kraków',
    'Kraków Lotnisko Balice' => 'Kraków',

    // Poznań
    'Poznań' => 'Poznań',
    'Poznań Centrum' => 'Poznań',
    'Poznań Lotnisko Ławica' => 'Poznań',

    // Wrocław
    'Wrocław' => 'Wrocław',
    'Wrocław Dworzec' => 'Wrocław',
    'Wrocław Lotnisko' => 'Wrocław',

    // Katowice -> mapuj na Kraków (najbliższa dostępna lokalizacja)
    'Katowice' => 'Katowice',
    'Katowice Centrum' => 'Katowice',
    'Katowice Lotnisko Pyrzowice' => 'Katowice',
    'Katowice Spodek' => 'Katowice',

    // Inne miasta -> mapuj na najbliższe dostępne
    'Gdańsk' => 'Poznań',
    'Gdańsk Centrum' => 'Poznań',
    'Gdańsk Lotnisko' => 'Poznań',

    'Lublin' => 'Warszawa',
    'Lublin Centrum' => 'Warszawa',

    'Szczecin' => 'Poznań',
    'Szczecin Centrum' => 'Poznań',

    'Łódź' => 'Warszawa',
    'Łódź Manufaktura' => 'Warszawa',

    'Bydgoszcz' => 'Poznań'
];

echo "Mapowanie dropoff_location:\n";
foreach ($dropoffMapping as $old => $new) {
    echo "- '$old' -> '$new'\n";
}
echo "\n";

// Sprawdź obecne dropoff_location
$stmt = $pdo->query("SELECT dropoff_location, COUNT(*) as count FROM reservations WHERE dropoff_location IS NOT NULL GROUP BY dropoff_location ORDER BY dropoff_location");
$currentDropoffs = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Obecne dropoff_location:\n";
foreach ($currentDropoffs as $dropoff) {
    $newLoc = $dropoffMapping[$dropoff['dropoff_location']] ?? 'BRAK MAPOWANIA';
    echo sprintf("- %-30s (%d rez.) -> %s\n", $dropoff['dropoff_location'], $dropoff['count'], $newLoc);
}
echo "\n";

// Wykonaj aktualizację
$pdo->beginTransaction();

try {
    $updated = 0;

    foreach ($dropoffMapping as $oldLoc => $newLoc) {
        $stmt = $pdo->prepare("UPDATE reservations SET dropoff_location = ? WHERE dropoff_location = ?");
        $result = $stmt->execute([$newLoc, $oldLoc]);

        if ($result && $stmt->rowCount() > 0) {
            echo "Zaktualizowano {$stmt->rowCount()} dropoff_location: '$oldLoc' -> '$newLoc'\n";
            $updated += $stmt->rowCount();
        }
    }

    $pdo->commit();
    echo "\n✅ Zaktualizowano łącznie: $updated dropoff_location\n";

    // Sprawdź wynik
    echo "\nNowe dropoff_location:\n";
    $stmt = $pdo->query("SELECT dropoff_location, COUNT(*) as count FROM reservations WHERE dropoff_location IS NOT NULL GROUP BY dropoff_location ORDER BY dropoff_location");
    $finalDropoffs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($finalDropoffs as $dropoff) {
        $isValid = in_array($dropoff['dropoff_location'], $dictLocations) ? '✅' : '❌';
        echo sprintf("%s %-12s (%d rez.)\n", $isValid, $dropoff['dropoff_location'], $dropoff['count']);
    }
} catch (Exception $e) {
    $pdo->rollBack();
    echo "❌ Błąd aktualizacji: " . $e->getMessage() . "\n";
}

echo "\n=== NAPRAWA ZAKOŃCZONA ===\n";
