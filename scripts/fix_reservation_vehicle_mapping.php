<?php
require_once '../includes/db.php';

$pdo = db();

echo "=== MAPOWANIE PRODUKTÓW W REZERWACJACH NA DOSTĘPNE POJAZDY ===\n\n";

// Mapowanie produktów bez pojazdów na dostępne produkty z pojazdami
$productMapping = [
    'BMW Seria 3' => 'BMW X3',
    'Toyota RAV4' => 'Toyota Corolla',
    'Volkswagen Tiguan' => 'VW Golf',
    'Ford Kuga' => 'Ford Focus',
    'Mercedes GLC' => 'Mercedes Sprinter',
    'Mercedes C-Class' => 'Mercedes Sprinter',
    'Mercedes S-Class' => 'Mercedes Sprinter',
    'Kia Rio' => 'Toyota Corolla',
    'Skoda Octavia' => 'VW Golf',
    'Mazda 3' => 'Toyota Corolla',
    'Nissan Qashqai' => 'Toyota Corolla',
    'Bentley Continental' => 'AUDI A5',
    'Ford Transit' => 'Mercedes Sprinter',
    'Volvo S60' => 'Audi A4',
    'Hyundai i20' => 'Toyota Corolla',
    'Porsche Panamera' => 'AUDI A5',
    'Fiat 500' => 'FIAT',
    'Audi A8' => 'Audi A4',
    'Tesla Model S' => 'BMW X3'
];

echo "Mapowanie produktów:\n";
foreach ($productMapping as $oldProduct => $newProduct) {
    echo "- '$oldProduct' -> '$newProduct'\n";
}
echo "\n";

// Sprawdź dostępność pojazdów dla docelowych produktów
echo "Sprawdzanie dostępności pojazdów:\n";
foreach (array_unique(array_values($productMapping)) as $targetProduct) {
    $stmt = $pdo->prepare("
        SELECT COUNT(v.id) as vehicle_count 
        FROM vehicles v 
        JOIN products p ON v.product_id = p.id 
        WHERE p.name = ?
    ");
    $stmt->execute([$targetProduct]);
    $count = $stmt->fetchColumn();
    echo "- $targetProduct: $count pojazdów\n";
}
echo "\n";

// Wykonaj mapowanie w rezerwacjach
$pdo->beginTransaction();

try {
    $totalUpdated = 0;

    foreach ($productMapping as $oldProduct => $newProduct) {
        $stmt = $pdo->prepare("UPDATE reservations SET product_name = ? WHERE product_name = ? AND vehicle_id IS NULL");
        $result = $stmt->execute([$newProduct, $oldProduct]);

        if ($result && $stmt->rowCount() > 0) {
            $count = $stmt->rowCount();
            echo "Zaktualizowano $count rezerwacji: '$oldProduct' -> '$newProduct'\n";
            $totalUpdated += $count;
        }
    }

    $pdo->commit();
    echo "\n✅ Łącznie zaktualizowano: $totalUpdated rezerwacji\n\n";
} catch (Exception $e) {
    $pdo->rollBack();
    echo "❌ Błąd mapowania: " . $e->getMessage() . "\n";
    exit(1);
}

// Teraz przypisz pojazdy do zaktualizowanych rezerwacji
echo "Przypisywanie pojazdów do zaktualizowanych rezerwacji...\n";

// Pobierz rezerwacje bez vehicle_id
$stmt = $pdo->prepare("SELECT id, product_name FROM reservations WHERE vehicle_id IS NULL ORDER BY pickup_at");
$stmt->execute();
$reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Znaleziono " . count($reservations) . " rezerwacji bez przypisanego pojazdu\n";

$updated = 0;
$vehicleUsage = []; // Zliczamy ile razy każdy pojazd był użyty

foreach ($reservations as $reservation) {
    // Znajdź dostępne pojazdy dla tego produktu
    $stmt = $pdo->prepare("
        SELECT v.id, v.registration_number 
        FROM vehicles v
        LEFT JOIN products p ON v.product_id = p.id
        WHERE p.name = ?
        ORDER BY v.id
    ");
    $stmt->execute([$reservation['product_name']]);
    $availableVehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($availableVehicles)) {
        // Wybierz pojazd który był najmniej używany
        $selectedVehicle = null;
        $minUsage = PHP_INT_MAX;

        foreach ($availableVehicles as $vehicle) {
            $usage = $vehicleUsage[$vehicle['id']] ?? 0;
            if ($usage < $minUsage) {
                $minUsage = $usage;
                $selectedVehicle = $vehicle;
            }
        }

        if ($selectedVehicle) {
            // Przypisz pojazd do rezerwacji
            $updateStmt = $pdo->prepare("UPDATE reservations SET vehicle_id = ? WHERE id = ?");
            $updateStmt->execute([$selectedVehicle['id'], $reservation['id']]);

            // Zwiększ licznik użycia pojazdu
            $vehicleUsage[$selectedVehicle['id']] = ($vehicleUsage[$selectedVehicle['id']] ?? 0) + 1;

            $updated++;
            echo "Rezerwacja #{$reservation['id']} ({$reservation['product_name']}) -> Pojazd {$selectedVehicle['registration_number']}\n";
        }
    } else {
        echo "❌ Brak dostępnego pojazdu dla: {$reservation['product_name']}\n";
    }
}

echo "\n✅ ZAKOŃCZONO!\n";
echo "Przypisano pojazdy do $updated rezerwacji\n";

// Sprawdź finalny stan
$stmt = $pdo->query("
    SELECT 
        COUNT(*) as total_reservations,
        COUNT(vehicle_id) as with_vehicle,
        (COUNT(*) - COUNT(vehicle_id)) as without_vehicle
    FROM reservations
");
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

echo "\nFinalny stan rezerwacji:\n";
echo "- Łączna liczba: {$stats['total_reservations']}\n";
echo "- Z przypisanym pojazdem: {$stats['with_vehicle']}\n";
echo "- Bez pojazdu: {$stats['without_vehicle']}\n";

if ($stats['without_vehicle'] == 0) {
    echo "\n🎉 Wszystkie rezerwacje mają przypisane konkretne pojazdy!\n";
}
