<?php
// Test z BASE

$BASE = '/rental';

echo "=== TEST Z BASE ===\n";

echo "1. Testing section-settings.php:\n";
ob_start();
try {
    $staff = ['email' => 'test@example.com']; // required variable
    include 'pages/staff/section-settings.php';
    $output = ob_get_contents();
    echo "   SUCCESS: " . strlen($output) . " chars\n";
    echo "   Contains form: " . (strpos($output, '<form') !== false ? 'YES' : 'NO') . "\n";
} catch (Exception $e) {
    echo "   ERROR: " . $e->getMessage() . "\n";
} catch (Error $e) {
    echo "   FATAL: " . $e->getMessage() . "\n";
}
ob_end_clean();

echo "\n2. Testing section-products.php:\n";
ob_start();
try {
    $products = [['id' => 1, 'name' => 'Test', 'sku' => 'ABC', 'price' => 100, 'stock' => 5, 'status' => 'active']];
    include 'pages/staff/section-products.php';
    $output = ob_get_contents();
    echo "   SUCCESS: " . strlen($output) . " chars\n";
    echo "   Contains table: " . (strpos($output, '<table') !== false ? 'YES' : 'NO') . "\n";
} catch (Exception $e) {
    echo "   ERROR: " . $e->getMessage() . "\n";
} catch (Error $e) {
    echo "   FATAL: " . $e->getMessage() . "\n";
}
ob_end_clean();

echo "=== END TEST ===\n";
