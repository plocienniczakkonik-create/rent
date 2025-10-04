<?php
session_start();
$_SESSION['user_id'] = 3;

require_once 'includes/db.php';
require_once 'includes/_helpers.php';
require_once 'pages/staff/_helpers.php';

// Setup all required variables like dashboard-staff.php does
$BASE = '/rental';

$products = db()->query("SELECT id, name, sku, price, stock, status, category FROM products ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
$productNameById = [];
$productNameBySku = [];
$classLabel = [];
foreach ($products as $p) {
    $productNameById[(int)$p['id']] = (string)$p['name'];
    $productNameBySku[(string)$p['sku']] = (string)$p['name'];
    $classLabel[(string)$p['status']] = status_badge($p['status']);
}

$promos = db()->query("SELECT id, name, code, is_active, scope_type, scope_value, valid_from, valid_to, min_days, discount_type, discount_val FROM promotions ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);

$reports = [
    'revenue_today' => 457.00,
    'orders_today' => 6,
    'top_product' => 'Toyota Corolla'
];

echo "=== INDIVIDUAL SECTION TEST ===\n";

$sections = [
    'products' => 'section-products.php',
    'promos' => 'section-promos.php',
    'reports' => 'section-reports.php',
    'settings' => 'section-settings.php',
    'dicts' => 'section-dicts.php'
];

foreach ($sections as $name => $file) {
    echo "\nTesting $name:\n";

    ob_start();
    try {
        $staff = ['email' => 'test2@example.com', 'first_name' => 'Test', 'last_name' => 'User'];
        include "pages/staff/$file";
        $output = ob_get_contents();
        $error = null;
    } catch (Exception $e) {
        $output = ob_get_contents();
        $error = "Exception: " . $e->getMessage();
    } catch (Error $e) {
        $output = ob_get_contents();
        $error = "Fatal: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine();
    }
    ob_end_clean();

    echo "  Output: " . strlen($output) . " chars\n";

    if ($error) {
        echo "  ❌ ERROR: $error\n";
    } else {
        $hasContent = strpos($output, '<div') !== false || strpos($output, '<table') !== false;
        echo "  " . ($hasContent ? "✅ OK" : "❌ NO CONTENT") . "\n";
    }
}

echo "\n=== END TEST ===\n";
