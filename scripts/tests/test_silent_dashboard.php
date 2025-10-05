<?php
// Silent complete dashboard test
session_start();
$_SESSION['user'] = ['email' => 'test2@example.com', 'role' => 'staff'];
$_GET['page'] = 'dashboard-staff';

ob_start();
include 'index.php';
$fullOutput = ob_get_contents();
ob_end_clean();

// Now analyze
echo "=== SILENT DASHBOARD TEST ===\n";
echo "Output length: " . strlen($fullOutput) . " chars\n";

// Check for dashboard content
$sections = [
    'nav-pills' => strpos($fullOutput, 'nav-pills') !== false,
    'tab-content' => strpos($fullOutput, 'tab-content') !== false,
    'Produkty' => strpos($fullOutput, 'Produkty') !== false,
    'Pojazdy' => strpos($fullOutput, 'Pojazdy') !== false,
    'Zamówienia' => strpos($fullOutput, 'Zamówienia') !== false,
    'Promocje' => strpos($fullOutput, 'Promocje') !== false,
    'Najbliższe terminy' => strpos($fullOutput, 'Najbliższe terminy') !== false,
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

// Check for login redirect
$isLogin = strpos($fullOutput, 'page=login') !== false || strpos($fullOutput, 'Zaloguj się') !== false;
echo "\nIs login page: " . ($isLogin ? 'YES' : 'NO') . "\n";

// Check specific tab panes
echo "\nTab panes:\n";
$panes = ['products', 'vehicles', 'orders', 'promos', 'upcoming', 'reports', 'dicts', 'settings'];
foreach ($panes as $pane) {
    $hasPane = strpos($fullOutput, "id=\"pane-$pane\"") !== false;
    echo "  pane-$pane: " . ($hasPane ? 'YES' : 'NO') . "\n";
}

echo "\nResult: " . ($found >= 6 && !$isLogin ? "✅ SUCCESS" : "❌ PROBLEM") . " ($found/10 sections found)\n";
echo "=== END TEST ===\n";
