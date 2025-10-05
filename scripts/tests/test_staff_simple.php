<?php
// test_staff_simple.php - prosty test bez partial'ów
session_start();
$_SESSION['user_id'] = 3;

require_once 'auth/auth.php';
$staff = require_staff();

require_once 'includes/db.php';
require_once 'pages/staff/_helpers.php';

$BASE = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';

// === DATA: Produkty ===
$products = db()->query("
  SELECT id, name, sku, price, stock, status, category
  FROM products
  ORDER BY id DESC
")->fetchAll(PDO::FETCH_ASSOC);

$productNameById  = [];
$productNameBySku = [];
$classLabel       = [];
foreach ($products as $p) {
    $productNameById[(int)$p['id']]        = (string)$p['name'];
    $productNameBySku[(string)$p['sku']]   = (string)$p['name'];
    if (!empty($p['category'])) {
        $code = (string)$p['category'];
        $classLabel[$code] = $classLabel[$code] ?? ('Klasa ' . strtoupper($code));
    }
}

// === DATA: Promocje ===
$promos = db()->query("
  SELECT id, name, code, is_active, scope_type, scope_value,
         valid_from, valid_to, min_days, discount_type, discount_val
  FROM promotions
  ORDER BY id DESC
")->fetchAll(PDO::FETCH_ASSOC);

// === DATA: Raporty ===
$reports = [
    'revenue_today' => 457.00,
    'orders_today'  => 6,
    'top_product'   => 'Toyota Corolla',
];

echo "=== Dane załadowane ===\n";
echo "Produkty: " . count($products) . "\n";
echo "Promocje: " . count($promos) . "\n";
echo "Staff: " . $staff['email'] . "\n";

echo "\n=== Test includeów sekcji ===\n";

// Test promocji
echo "1. Test section-promos.php:\n";
ob_start();
try {
    include 'pages/staff/section-promos.php';
    $output = ob_get_contents();
    echo "   Sukces - długość: " . strlen($output) . " znaków\n";
    if (strpos($output, 'Promocje') !== false) {
        echo "   ✓ Zawiera nagłówek 'Promocje'\n";
    }
} catch (Exception $e) {
    echo "   Błąd: " . $e->getMessage() . "\n";
} finally {
    ob_end_clean();
}

// Test raportów
echo "2. Test section-reports.php:\n";
ob_start();
try {
    include 'pages/staff/section-reports.php';
    $output = ob_get_contents();
    echo "   Sukces - długość: " . strlen($output) . " znaków\n";
    if (strpos($output, 'Przychód') !== false) {
        echo "   ✓ Zawiera 'Przychód'\n";
    }
} catch (Exception $e) {
    echo "   Błąd: " . $e->getMessage() . "\n";
} finally {
    ob_end_clean();
}

// Test słowników
echo "3. Test section-dicts.php:\n";
ob_start();
try {
    include 'pages/staff/section-dicts.php';
    $output = ob_get_contents();
    echo "   Sukces - długość: " . strlen($output) . " znaków\n";
    if (strpos($output, 'Słowniki') !== false) {
        echo "   ✓ Zawiera 'Słowniki'\n";
    }
} catch (Exception $e) {
    echo "   Błąd: " . $e->getMessage() . "\n";
} finally {
    ob_end_clean();
}

echo "\nTest zakończony pomyślnie!\n";
