<?php
require_once '../includes/db.php';
$id = isset($_POST['id']) ? intval($_POST['id']) : null;
$name = trim($_POST['name'] ?? '');
$type = $_POST['type'] ?? 'fixed';
$price = floatval($_POST['price'] ?? 0);
$unit = trim($_POST['unit'] ?? '');
$active = isset($_POST['active']) ? 1 : 0;
if ($id) {
    $stmt = $pdo->prepare('UPDATE addons SET name=?, type=?, price=?, unit=?, active=? WHERE id=?');
    $stmt->execute([$name, $type, $price, $unit, $active, $id]);
} else {
    $stmt = $pdo->prepare('INSERT INTO addons (name, type, price, unit, active) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([$name, $type, $price, $unit, $active]);
}
header('Location: dashboard-staff.php?section=addons');
exit;
