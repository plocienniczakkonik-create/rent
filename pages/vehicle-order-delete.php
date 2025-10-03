<?php
// /pages/vehicle-order-delete.php
require_once dirname(__DIR__) . '/auth/auth.php';
require_staff();
csrf_verify();
require_once dirname(__DIR__) . '/includes/db.php';
$db = db();

$id = (int)($_POST['id'] ?? 0);
$vehicle_id = (int)($_POST['vehicle_id'] ?? 0);

if ($id) {
    $db->prepare('DELETE FROM vehicle_orders WHERE id=?')->execute([$id]);
}

header('Location: ' . BASE_URL . '/index.php?page=vehicle-detail&id=' . $vehicle_id . '&order_deleted=1');
exit;
