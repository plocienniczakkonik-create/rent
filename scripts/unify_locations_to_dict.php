<?php
require_once '../includes/db.php';

$pdo = db();

echo "=== UNIFIKACJA LOKALIZACJI DO DICT_TERMS ===\n\n";

// Pobierz nazwy lokalizacji z dict_terms (źródło prawdy)
$stmt = $pdo->prepare("
    SELECT t.name 
    FROM dict_terms t 
    JOIN dict_types dt ON dt.id = t.dict_type_id 
    WHERE dt.slug = 'location' AND t.status = 'active' 
    ORDER BY t.name
");
$stmt->execute();
$dictLocations = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo "Lokalizacje w dict_terms (źródło prawdy):\n";
foreach ($dictLocations as $loc) {
    echo "- $loc\n";
}
echo "\n";

// Sprawdź obecne nazwy w tabeli locations
$stmt = $pdo->query("SELECT id, name FROM locations WHERE is_active = 1 ORDER BY name");
$currentLocations = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Obecne nazwy w tabeli locations:\n";
foreach ($currentLocations as $loc) {
    echo "- ID {$loc['id']}: {$loc['name']}\n";
}
echo "\n";

// Mapowanie starych nazw locations na dict_terms
$locationMapping = [
    'Gdańsk Port' => 'Kraków', // Gdańska nie ma w dict, przypisz do Krakowa
    'Kraków Główny' => 'Kraków',
    'Poznań Plaza' => 'Poznań',
    'Warszawa Centrum' => 'Warszawa',
    'Wrocław Rynek' => 'Wrocław'
];

echo "Mapowanie locations -> dict_terms:\n";
foreach ($locationMapping as $oldName => $newName) {
    echo "- '$oldName' -> '$newName'\n";
}
echo "\n";

// Zaktualizuj tabele locations
echo "1. Aktualizacja tabeli locations...\n";
$pdo->beginTransaction();

try {
    foreach ($locationMapping as $oldName => $newName) {
        $stmt = $pdo->prepare("UPDATE locations SET name = ? WHERE name = ? AND is_active = 1");
        $result = $stmt->execute([$newName, $oldName]);

        if ($result && $stmt->rowCount() > 0) {
            echo "   Zaktualizowano: '$oldName' -> '$newName'\n";
        }
    }

    // Dodaj brakujące lokalizacje z dict_terms
    $stmt = $pdo->query("SELECT name FROM locations WHERE is_active = 1");
    $existingLocations = $stmt->fetchAll(PDO::FETCH_COLUMN);

    foreach ($dictLocations as $dictLoc) {
        if (!in_array($dictLoc, $existingLocations)) {
            $stmt = $pdo->prepare("
                INSERT INTO locations (name, city, address, is_active, created_at) 
                VALUES (?, ?, NULL, 1, NOW())
            ");
            $stmt->execute([$dictLoc, $dictLoc]);
            echo "   Dodano brakującą lokalizację: '$dictLoc'\n";
        }
    }

    $pdo->commit();
    echo "   ✅ Tabela locations zaktualizowana\n\n";
} catch (Exception $e) {
    $pdo->rollBack();
    echo "   ❌ Błąd aktualizacji locations: " . $e->getMessage() . "\n";
    exit(1);
}

// Zaktualizuj rezerwacje
echo "2. Aktualizacja pickup_location w rezerwacjach...\n";
$pdo->beginTransaction();

try {
    // Mapowanie pickup_location w rezerwacjach
    $reservationMapping = [
        'Gdańsk Port' => 'Kraków',
        'Kraków Główny' => 'Kraków',
        'Poznań Plaza' => 'Poznań',
        'Warszawa Centrum' => 'Warszawa',
        'Wrocław Rynek' => 'Wrocław'
    ];

    foreach ($reservationMapping as $oldLoc => $newLoc) {
        $stmt = $pdo->prepare("UPDATE reservations SET pickup_location = ? WHERE pickup_location = ?");
        $result = $stmt->execute([$newLoc, $oldLoc]);

        if ($result && $stmt->rowCount() > 0) {
            echo "   Zaktualizowano {$stmt->rowCount()} rezerwacji: '$oldLoc' -> '$newLoc'\n";
        }
    }

    // Aktualizuj też dropoff_location
    foreach ($reservationMapping as $oldLoc => $newLoc) {
        $stmt = $pdo->prepare("UPDATE reservations SET dropoff_location = ? WHERE dropoff_location = ?");
        $result = $stmt->execute([$newLoc, $oldLoc]);

        if ($result && $stmt->rowCount() > 0) {
            echo "   Zaktualizowano {$stmt->rowCount()} dropoff_location: '$oldLoc' -> '$newLoc'\n";
        }
    }

    $pdo->commit();
    echo "   ✅ Rezerwacje zaktualizowane\n\n";
} catch (Exception $e) {
    $pdo->rollBack();
    echo "   ❌ Błąd aktualizacji rezerwacji: " . $e->getMessage() . "\n";
    exit(1);
}

// Sprawdź wynik
echo "=== WYNIK UNIFIKACJI ===\n";

echo "Lokalizacje w tabeli locations:\n";
$stmt = $pdo->query("SELECT name, COUNT(*) as count FROM locations WHERE is_active = 1 GROUP BY name ORDER BY name");
$finalLocations = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($finalLocations as $loc) {
    echo "- {$loc['name']}\n";
}

echo "\nLokalizacje w rezerwacjach:\n";
$stmt = $pdo->query("SELECT pickup_location, COUNT(*) as count FROM reservations WHERE pickup_location IS NOT NULL GROUP BY pickup_location ORDER BY pickup_location");
$finalReservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($finalReservations as $res) {
    echo "- {$res['pickup_location']} ({$res['count']} rezerwacji)\n";
}

echo "\n✅ Unifikacja zakończona! Wszystkie lokalizacje używają nazw z dict_terms.\n";
