<?php
require_once __DIR__ . '/../includes/_helpers.php';
require_once __DIR__ . '/../auth/auth.php';
require_staff();
echo '<pre style="color:red">DEBUG: SESSION _token = ' . htmlspecialchars($_SESSION['_token'] ?? '') . "\n" .
    'POST _token = ' . htmlspecialchars($_POST['_token'] ?? '') . "</pre>";
csrf_verify();

require_once __DIR__ . '/../includes/db.php';
$db = db();

$id = (int)($_POST['id'] ?? 0);
$vehicle_id = (int)($_POST['vehicle_id'] ?? 0);

if ($id) {
    // usuń pliki
    $files = $db->prepare("SELECT file_path FROM vehicle_service_files WHERE service_id=?");
    $files->execute([$id]);
    foreach ($files->fetchAll(PDO::FETCH_COLUMN) as $rel) {
        $abs = __DIR__ . '/..' . $rel;
        if (is_file($abs)) @unlink($abs);
    }
    $db->prepare("DELETE FROM vehicle_service_files WHERE service_id=?")->execute([$id]);
    $db->prepare("DELETE FROM vehicle_services WHERE id=?")->execute([$id]);
}
header('Location: ' . BASE_URL . '/index.php?page=vehicle-detail&id=' . $vehicle_id . '&deleted=1');
exit;
