<?php
require_once dirname(__DIR__) . '/includes/_helpers.php';
require_once dirname(__DIR__) . '/auth/auth.php';
require_staff();
csrf_verify();
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/classes/FleetManager.php';

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$status = isset($_POST['status']) ? trim((string)$_POST['status']) : '';
$allowed = ['pending', 'confirmed', 'completed', 'cancelled'];
if ($id <= 0 || !in_array($status, $allowed, true)) {
    header('Location: ' . BASE_URL . '/index.php?page=dashboard-staff#pane-orders');
    exit;
}

$db = db();

// Pobierz stary status przed aktualizacją
$stmt = $db->prepare('SELECT status, vehicle_id, dropoff_location FROM reservations WHERE id = :id');
$stmt->execute([':id' => $id]);
$oldReservation = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$oldReservation) {
    header('Location: ' . BASE_URL . '/index.php?page=dashboard-staff#pane-orders');
    exit;
}

// Aktualizuj status
$stmt = $db->prepare('UPDATE reservations SET status = :st WHERE id = :id');
$stmt->execute([':st' => $status, ':id' => $id]);

// Jeśli zmieniono status na 'completed', aktualizuj lokalizację pojazdu
if ($status === 'completed' && $oldReservation['status'] !== 'completed' && $oldReservation['vehicle_id']) {
    try {
        $fleetManager = new FleetManager($db);
        if ($fleetManager->isEnabled()) {
            $updated = $fleetManager->updateVehicleLocationFromReservation($id);
            if ($updated) {
                // Log sukcesu (opcjonalnie)
                error_log("FleetManager: Zaktualizowano lokalizację pojazdu po zakończeniu rezerwacji #$id");
            }
        }
    } catch (Exception $e) {
        error_log("FleetManager error in reservation-status-save: " . $e->getMessage());
    }
}

// Preserve filters
$q = isset($_POST['q']) ? (string)$_POST['q'] : '';
$st = isset($_POST['filter_status']) ? (string)$_POST['filter_status'] : '';
$p = isset($_POST['p']) ? (int)$_POST['p'] : 1;
$qs = http_build_query(['page' => 'dashboard-staff', 'q' => $q, 'status' => $st, 'p' => $p]);

header('Location: ' . BASE_URL . '/index.php?' . $qs . '#pane-orders');
exit;
