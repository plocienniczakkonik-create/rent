<?php
// Prosty test sekcji bez setupu danych

echo "=== SIMPLE SECTION TEST ===\n";

// Test tylko jednej sekcji
echo "1. Testing section-promos.php:\n";

ob_start();
try {
    // Setup minimal required variables
    $promos = [
        ['id' => 1, 'name' => 'Test Promo', 'code' => 'TEST', 'is_active' => 1]
    ];
    $productNameById = [1 => 'Test Product'];
    $productNameBySku = ['ABC' => 'Test Product'];
    $classLabel = ['active' => 'bg-success'];

    include 'pages/staff/section-promos.php';
    $output = ob_get_contents();
    echo "   SUCCESS: " . strlen($output) . " chars\n";
    echo "   Contains HTML: " . (strpos($output, '<') !== false ? 'YES' : 'NO') . "\n";
} catch (Exception $e) {
    echo "   ERROR: " . $e->getMessage() . "\n";
    $output = '';
} catch (Error $e) {
    echo "   FATAL: " . $e->getMessage() . "\n";
    $output = '';
}
ob_end_clean();

echo "\n2. Testing section-settings.php:\n";

ob_start();
try {
    include 'pages/staff/section-settings.php';
    $output2 = ob_get_contents();
    echo "   SUCCESS: " . strlen($output2) . " chars\n";
    echo "   Contains HTML: " . (strpos($output2, '<') !== false ? 'YES' : 'NO') . "\n";
} catch (Exception $e) {
    echo "   ERROR: " . $e->getMessage() . "\n";
    $output2 = '';
} catch (Error $e) {
    echo "   FATAL: " . $e->getMessage() . "\n";
    $output2 = '';
}
ob_end_clean();

echo "\n=== END TEST ===\n";
