<?php
require_once '../includes/db.php';

$pdo = db();

echo "=== WALIDACJA ZGODNOŚCI LOKALIZACJI ===\n\n";

// Sprawdź czy wszystkie pickup_location w rezerwacjach mają odpowiednik w locations
$stmt = $pdo->query("
    SELECT DISTINCT r.pickup_location 
    FROM reservations r 
    WHERE r.pickup_location IS NOT NULL 
    AND r.pickup_location NOT IN (
        SELECT name FROM locations WHERE is_active = 1
    )
");
$invalidLocations = $stmt->fetchAll(PDO::FETCH_COLUMN);

if (empty($invalidLocations)) {
    echo "✅ Wszystkie pickup_location w rezerwacjach mają odpowiedniki w tabeli locations\n";
} else {
    echo "❌ Znaleziono nieprawidłowe lokalizacje w rezerwacjach:\n";
    foreach ($invalidLocations as $location) {
        echo "  - $location\n";
    }
}

// Sprawdź statystyki
$stmt = $pdo->query("SELECT COUNT(*) FROM reservations WHERE pickup_location IS NOT NULL");
$totalReservations = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(DISTINCT pickup_location) FROM reservations WHERE pickup_location IS NOT NULL");
$uniqueLocationsInReservations = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM locations WHERE is_active = 1");
$activeLocations = $stmt->fetchColumn();

echo "\nStatystyki:\n";
echo "- Aktywne lokalizacje w tabeli locations: $activeLocations\n";
echo "- Unikalne lokalizacje w rezerwacjach: $uniqueLocationsInReservations\n";
echo "- Łączna liczba rezerwacji z lokalizacją: $totalReservations\n";

// Pokaz mapowanie
echo "\nMapowanie lokalizacji:\n";
$stmt = $pdo->query("
    SELECT 
        l.name as location_name,
        COUNT(r.id) as reservation_count,
        COALESCE(SUM(r.total_gross), 0) as total_revenue
    FROM locations l
    LEFT JOIN reservations r ON l.name = r.pickup_location
    WHERE l.is_active = 1
    GROUP BY l.name
    ORDER BY reservation_count DESC
");
$mapping = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($mapping as $row) {
    echo sprintf(
        "- %-20s: %3d rezerwacji, %10.2f zł\n",
        $row['location_name'],
        $row['reservation_count'],
        $row['total_revenue']
    );
}

echo "\n=== WALIDACJA ZAKOŃCZONA ===\n";
