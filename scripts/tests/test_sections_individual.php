<?php
session_start();
$_SESSION['user'] = ['email' => 'test2@example.com', 'role' => 'staff'];

require_once 'includes/db.php';
require_once 'pages/staff/_helpers.php';

// Setup data for sections
$products = db()->query("SELECT id, name, sku, price, stock, status, category FROM products ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
$productNameById = [];
$productNameBySku = [];
$classLabel = [];
foreach ($products as $p) {
    $productNameById[(int)$p['id']] = (string)$p['name'];
    $productNameBySku[(string)$p['sku']] = (string)$p['name'];
    $classLabel[(string)$p['status']] = status_badge($p['status']);
}

$promos = db()->query("SELECT * FROM product_promos ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);

echo "=== INDIVIDUAL SECTION TESTS ===\n";

// Test each section
$sections = [
    'products' => 'section-products.php',
    'vehicles' => 'section-vehicles.php',
    'orders' => 'section-orders.php',
    'promos' => 'section-promos.php',
    'upcoming' => 'section-upcoming.php',
    'reports' => 'section-reports.php',
    'dicts' => 'section-dicts.php',
    'settings' => 'section-settings.php'
];

foreach ($sections as $name => $file) {
    echo "\nTesting $name ($file):\n";

    ob_start();
    try {
        include "pages/staff/$file";
        $output = ob_get_contents();
        $success = true;
    } catch (Exception $e) {
        $output = "ERROR: " . $e->getMessage();
        $success = false;
    } catch (Error $e) {
        $output = "FATAL: " . $e->getMessage();
        $success = false;
    }
    ob_end_clean();

    echo "  Length: " . strlen($output) . " chars\n";
    echo "  Status: " . ($success ? 'OK' : 'FAILED') . "\n";

    if (!$success) {
        echo "  Error: $output\n";
    } else {
        // Check for content indicators
        $indicators = [
            'card' => strpos($output, 'card') !== false,
            'table' => strpos($output, 'table') !== false,
            'btn' => strpos($output, 'btn') !== false
        ];
        echo "  Content: " . json_encode($indicators) . "\n";
    }
}

echo "\n=== END TESTS ===\n";
