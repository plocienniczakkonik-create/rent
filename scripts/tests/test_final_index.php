<?php
session_start();
$_SESSION['user_id'] = 3; // Poprawny staff user
$_GET['page'] = 'dashboard-staff';

ob_start();
include 'index.php';
$fullOutput = ob_get_contents();
ob_end_clean();

echo "=== FINAL INDEX.PHP TEST ===\n";
echo "Output length: " . strlen($fullOutput) . " chars\n";

// Check for dashboard content
$sections = [
    'nav-pills' => strpos($fullOutput, 'nav-pills') !== false,
    'tab-content' => strpos($fullOutput, 'tab-content') !== false,
    'Produkty' => strpos($fullOutput, 'Produkty') !== false,
    'Promocje' => strpos($fullOutput, 'Promocje') !== false,
    'Raporty' => strpos($fullOutput, 'Raporty') !== false,
    'Słowniki' => strpos($fullOutput, 'Słowniki') !== false,
    'Ustawienia' => strpos($fullOutput, 'Ustawienia') !== false,
];

$found = 0;
foreach ($sections as $name => $exists) {
    if ($exists) {
        $found++;
        echo "✓ $name\n";
    } else {
        echo "✗ $name\n";
    }
}

echo "\nResult: " . ($found >= 5 ? "✅ SUCCESS" : "❌ PROBLEM") . " ($found/7 sections found)\n";
echo "=== END TEST ===\n";
