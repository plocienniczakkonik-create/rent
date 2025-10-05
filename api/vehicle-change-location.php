<?php

/**
 * API endpoint do zmiany lokalizacji pojazdu
 * Obsługuje AJAX request z formularza
 */

require_once dirname(__DIR__) . '/auth/auth.php';
require_staff();

require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/vehicle-location-manager.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Metoda nie dozwolona');
    }

    $vehicleId = isset($_POST['vehicle_id']) ? (int)$_POST['vehicle_id'] : 0;
    $newLocationId = isset($_POST['new_location_id']) ? (int)$_POST['new_location_id'] : 0;
    $reason = isset($_POST['reason']) ? $_POST['reason'] : 'manual';
    $notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';

    if (!$vehicleId) {
        throw new Exception('Nieprawidłowy ID pojazdu');
    }

    if (!$newLocationId) {
        throw new Exception('Nie wybrano nowej lokalizacji');
    }

    // Sprawdź czy pojazd istnieje
    $stmt = db()->prepare("SELECT id, registration_number FROM vehicles WHERE id = :id");
    $stmt->execute(['id' => $vehicleId]);
    $vehicle = $stmt->fetch();

    if (!$vehicle) {
        throw new Exception('Pojazd nie został znaleziony');
    }

    // Sprawdź czy lokalizacja istnieje i jest aktywna
    $stmt = db()->prepare("SELECT id, name FROM locations WHERE id = :id AND is_active = 1");
    $stmt->execute(['id' => $newLocationId]);
    $location = $stmt->fetch();

    if (!$location) {
        throw new Exception('Wybrana lokalizacja nie istnieje lub jest nieaktywna');
    }

    // Pobierz ID zalogowanego użytkownika
    $movedBy = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

    // Aktualizuj lokalizację
    $success = VehicleLocationManager::updateLocation(
        $vehicleId,
        $newLocationId,
        $reason,
        $notes,
        $movedBy
    );

    if (!$success) {
        throw new Exception('Nie udało się zaktualizować lokalizacji pojazdu');
    }

    // Dodaj flash message
    $_SESSION['flash_ok'] = "Lokalizacja pojazdu {$vehicle['registration_number']} została zmieniona na: {$location['name']}";

    echo json_encode([
        'success' => true,
        'message' => 'Lokalizacja została pomyślnie zmieniona',
        'vehicle_id' => $vehicleId,
        'new_location' => $location['name']
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
