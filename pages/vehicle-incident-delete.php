<?php
// /pages/vehicle-incident-delete.php
require_once __DIR__ . '/../auth/auth.php';
require_staff();
csrf_verify();

require_once __DIR__ . '/../includes/db.php';
$db = db();

$id         = (int)($_POST['id'] ?? 0);
$vehicle_id = (int)($_POST['vehicle_id'] ?? 0);

if ($id) {
    // usuń pliki z dysku
    $files = $db->prepare("SELECT file_path FROM vehicle_incident_files WHERE incident_id=?");
    $files->execute([$id]);
    foreach ($files->fetchAll(PDO::FETCH_COLUMN) as $rel) {
        $abs = __DIR__ . '/..' . $rel;
        if (is_file($abs)) @unlink($abs);
    }
    // usuń rekordy powiązane
    $db->prepare("DELETE FROM vehicle_incident_files WHERE incident_id=?")->execute([$id]);
    $db->prepare("DELETE FROM vehicle_incidents WHERE id=?")->execute([$id]);
}

header('Location: ' . BASE_URL . '/index.php?page=vehicle-detail&id=' . $vehicle_id . '&deleted=1');
exit;
