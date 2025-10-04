<?php
// /pages/vehicle-order-save.php
require_once dirname(__DIR__) . '/auth/auth.php';
require_once dirname(__DIR__) . '/includes/_helpers.php';
require_staff();
csrf_verify();
require_once dirname(__DIR__) . '/includes/db.php';
$db = db();

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$vehicle_id = isset($_POST['vehicle_id']) ? (int)$_POST['vehicle_id'] : 0;
$order_date = $_POST['order_date'] ?? '';
$client_name = $_POST['client_name'] ?? '';
$amount = $_POST['amount'] ?? 0;

if ($id) {
    $stmt = $db->prepare('UPDATE vehicle_orders SET order_date=?, client_name=?, amount=? WHERE id=?');
    $stmt->execute([$order_date, $client_name, $amount, $id]);
} else {
    $stmt = $db->prepare('INSERT INTO vehicle_orders (vehicle_id, order_date, client_name, amount) VALUES (?, ?, ?, ?)');
    $stmt->execute([$vehicle_id, $order_date, $client_name, $amount]);
}

header('Location: ' . BASE_URL . '/index.php?page=vehicle-detail&id=' . $vehicle_id . '&order_saved=1');
exit;
