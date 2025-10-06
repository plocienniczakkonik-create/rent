<?php
require_once '../includes/db.php';

$pdo = db();

echo "=== WALIDACJA SPÓJNOŚCI LOKALIZACJI (DICT_TERMS) ===\n\n";

// Pobierz lokalizacje z dict_terms (źródło prawdy)
$stmt = $pdo->prepare("
    SELECT t.name 
    FROM dict_terms t 
    JOIN dict_types dt ON dt.id = t.dict_type_id 
    WHERE dt.slug = 'location' AND t.status = 'active' 
    ORDER BY t.name
");
$stmt->execute();
$dictLocations = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo "✅ Lokalizacje w dict_terms (źródło prawdy):\n";
foreach ($dictLocations as $loc) {
    echo "   - $loc\n";
}
echo "\n";

// Sprawdź tabele locations
$stmt = $pdo->query("SELECT name FROM locations WHERE is_active = 1 ORDER BY name");
$locationNames = $stmt->fetchAll(PDO::FETCH_COLUMN);

$locationsOK = true;
foreach ($locationNames as $loc) {
    if (!in_array($loc, $dictLocations)) {
        echo "❌ BŁĄD: '$loc' w locations nie istnieje w dict_terms\n";
        $locationsOK = false;
    }
}

if ($locationsOK) {
    echo "✅ Tabela locations: wszystkie nazwy zgodne z dict_terms\n";
}

// Sprawdź rezerwacje
$stmt = $pdo->query("SELECT DISTINCT pickup_location FROM reservations WHERE pickup_location IS NOT NULL");
$reservationLocations = $stmt->fetchAll(PDO::FETCH_COLUMN);

$reservationsOK = true;
foreach ($reservationLocations as $loc) {
    if (!in_array($loc, $dictLocations)) {
        echo "❌ BŁĄD: '$loc' w rezerwacjach nie istnieje w dict_terms\n";
        $reservationsOK = false;
    }
}

if ($reservationsOK) {
    echo "✅ Rezerwacje: wszystkie pickup_location zgodne z dict_terms\n";
}

// Sprawdź dropoff_location
$stmt = $pdo->query("SELECT DISTINCT dropoff_location FROM reservations WHERE dropoff_location IS NOT NULL");
$dropoffLocations = $stmt->fetchAll(PDO::FETCH_COLUMN);

$dropoffOK = true;
foreach ($dropoffLocations as $loc) {
    if (!in_array($loc, $dictLocations)) {
        echo "❌ BŁĄD: '$loc' w dropoff_location nie istnieje w dict_terms\n";
        $dropoffOK = false;
    }
}

if ($dropoffOK) {
    echo "✅ Rezerwacje: wszystkie dropoff_location zgodne z dict_terms\n";
}

echo "\n=== STATYSTYKI ===\n";

// Statystyki wykorzystania lokalizacji
$stmt = $pdo->query("
    SELECT 
        t.name as location,
        COUNT(r.id) as reservations,
        COALESCE(SUM(r.total_gross), 0) as revenue
    FROM dict_terms t
    JOIN dict_types dt ON dt.id = t.dict_type_id
    LEFT JOIN reservations r ON r.pickup_location = t.name
    WHERE dt.slug = 'location' AND t.status = 'active'
    GROUP BY t.name
    ORDER BY reservations DESC, t.name
");
$stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Wykorzystanie lokalizacji:\n";
foreach ($stats as $stat) {
    printf(
        "   %-12s: %3d rezerwacji, %10.2f zł\n",
        $stat['location'],
        $stat['reservations'],
        $stat['revenue']
    );
}

echo "\n";

if ($locationsOK && $reservationsOK && $dropoffOK) {
    echo "🎉 SYSTEM W PEŁNI SPÓJNY! Wszystkie komponenty używają nazw z dict_terms.\n";
} else {
    echo "⚠️  SYSTEM WYMAGA NAPRAWY! Wykryto niespójności.\n";
}

echo "\n=== WALIDACJA ZAKOŃCZONA ===\n";
