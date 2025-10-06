<?php
// Skrypt przypisujący vehicle_id do rezerwacji
require_once '../includes/db.php';

$db = db();
$db->exec("SET NAMES utf8mb4 COLLATE utf8mb4_polish_ci");

echo "Przypisywanie pojazdów do rezerwacji...\n\n";

try {
    // Pobierz wszystkie rezerwacje bez vehicle_id
    $stmt = $db->prepare("SELECT id, product_name FROM reservations WHERE vehicle_id IS NULL ORDER BY pickup_at");
    $stmt->execute();
    $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Znaleziono " . count($reservations) . " rezerwacji bez przypisanego pojazdu\n";

    $updated = 0;
    $vehicleUsage = []; // Zliczamy ile razy każdy pojazd był użyty

    foreach ($reservations as $reservation) {
        // Znajdź dostępne pojazdy dla tego produktu
        $stmt = $db->prepare("
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
                $updateStmt = $db->prepare("UPDATE reservations SET vehicle_id = ? WHERE id = ?");
                $updateStmt->execute([$selectedVehicle['id'], $reservation['id']]);

                // Zwiększ licznik użycia pojazdu
                $vehicleUsage[$selectedVehicle['id']] = ($vehicleUsage[$selectedVehicle['id']] ?? 0) + 1;

                $updated++;
                echo "Rezerwacja #{$reservation['id']} ({$reservation['product_name']}) -> Pojazd {$selectedVehicle['registration_number']}\n";
            } else {
                echo "❌ Brak dostępnego pojazdu dla: {$reservation['product_name']}\n";
            }
        } else {
            echo "❌ Nie znaleziono pojazdów dla produktu: {$reservation['product_name']}\n";
        }
    }

    echo "\n✅ ZAKOŃCZONO!\n";
    echo "Zaktualizowano $updated rezerwacji\n";

    // Pokaż statystyki użycia pojazdów
    echo "\nStatystyki przypisania pojazdów:\n";
    foreach ($vehicleUsage as $vehicleId => $count) {
        $stmt = $db->prepare("SELECT registration_number FROM vehicles WHERE id = ?");
        $stmt->execute([$vehicleId]);
        $regNumber = $stmt->fetch(PDO::FETCH_ASSOC)['registration_number'];
        echo "Pojazd $regNumber: $count rezerwacji\n";
    }
} catch (Exception $e) {
    echo "❌ BŁĄD: " . $e->getMessage() . "\n";
}
