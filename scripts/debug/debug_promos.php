<?php
// Debug section-promos specifically

echo "=== DEBUG SECTION-PROMOS ===\n";

// Setup required variables
$productNameById = [1 => 'Test Product'];
$productNameBySku = ['ABC' => 'Test Product'];
$classLabel = ['active' => 'bg-success'];
$promos = []; // Empty promos to test

echo "1. Variables set\n";

// Capture all output including errors
ob_start();
error_reporting(E_ALL);

try {
    include 'pages/staff/section-promos.php';
    $output = ob_get_contents();
    $error = false;
} catch (Exception $e) {
    $output = ob_get_contents();
    $error = "Exception: " . $e->getMessage();
} catch (Error $e) {
    $output = ob_get_contents();
    $error = "Fatal: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine();
}

ob_end_clean();

echo "2. Include completed\n";
echo "3. Output length: " . strlen($output) . "\n";
echo "4. Error: " . ($error ?: 'NONE') . "\n";

if (strlen($output) > 0) {
    echo "5. First 200 chars:\n";
    echo substr($output, 0, 200) . "\n";
} else {
    echo "5. NO OUTPUT GENERATED\n";
}

echo "=== END DEBUG ===\n";
