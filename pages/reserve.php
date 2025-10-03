<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/config.php';

$sku = $_GET['sku'] ?? null;
if (!$sku) {
    header('Location: index.php');
    exit;
}
$stmt = db()->prepare('SELECT * FROM products WHERE sku = ?');
$stmt->execute([$sku]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$product) {
    header('Location: index.php');
    exit;
}
$search = $_SESSION['search'] ?? [];
// Nadpisz wartości z GET jeśli są dostępne (np. po przejściu z wyszukiwarki)
foreach ([
    'pickup_location', 'dropoff_location', 'pickup_at', 'return_at',
    'vehicle_type', 'transmission', 'seats_min', 'fuel'
] as $key) {
    if (isset($_GET[$key])) {
        $search[$key] = $_GET[$key];
    }
}
// Przekazanie zmiennych do include
if (!isset($product)) $product = null;
if (!isset($search)) $search = null;
extract(['product' => $product, 'search' => $search]);
include __DIR__ . '/product-details.php';
