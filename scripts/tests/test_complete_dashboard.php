<?php
// Complete dashboard test via index.php

echo "=== COMPLETE DASHBOARD TEST ===\n";

// Step 1: Setup session
session_start();
$_SESSION['user'] = ['email' => 'test2@example.com', 'role' => 'staff'];
echo "1. Session setup complete\n";

// Step 2: Simulate GET request to dashboard
$_GET['page'] = 'dashboard-staff';
echo "2. GET page set to dashboard-staff\n";

// Step 3: Capture output from index.php
ob_start();
include 'index.php';
$fullOutput = ob_get_contents();
ob_end_clean();

echo "3. Index.php processed\n";
echo "4. Full output length: " . strlen($fullOutput) . " chars\n";

// Step 4: Analyze the output
$hasTabs = strpos($fullOutput, 'nav-pills') !== false;
$hasTabContent = strpos($fullOutput, 'tab-content') !== false;
$hasPromos = strpos($fullOutput, 'Promocje') !== false;
$hasReports = strpos($fullOutput, 'Raporty') !== false;
$hasSettings = strpos($fullOutput, 'Ustawienia') !== false;
$hasDicts = strpos($fullOutput, 'Słowniki') !== false;

echo "5. Analysis:\n";
echo "   - Has nav-pills: " . ($hasTabs ? 'YES' : 'NO') . "\n";
echo "   - Has tab-content: " . ($hasTabContent ? 'YES' : 'NO') . "\n";
echo "   - Has Promocje: " . ($hasPromos ? 'YES' : 'NO') . "\n";
echo "   - Has Raporty: " . ($hasReports ? 'YES' : 'NO') . "\n";
echo "   - Has Ustawienia: " . ($hasSettings ? 'YES' : 'NO') . "\n";
echo "   - Has Słowniki: " . ($hasDicts ? 'YES' : 'NO') . "\n";

// Step 5: Check for specific section content
$sections = ['products', 'vehicles', 'orders', 'promos', 'upcoming', 'reports', 'dicts', 'settings'];
echo "\n6. Tab panes analysis:\n";
foreach ($sections as $section) {
    $hasPane = strpos($fullOutput, "id=\"pane-$section\"") !== false;
    echo "   - pane-$section: " . ($hasPane ? 'YES' : 'NO') . "\n";
}

// Step 6: Look for errors or redirects
$hasErrors = strpos($fullOutput, 'Fatal error') !== false || strpos($fullOutput, 'Parse error') !== false;
$hasRedirect = strpos($fullOutput, 'Location:') !== false;

echo "\n7. Error check:\n";
echo "   - Has errors: " . ($hasErrors ? 'YES' : 'NO') . "\n";
echo "   - Has redirect: " . ($hasRedirect ? 'YES' : 'NO') . "\n";

if ($hasRedirect) {
    // Extract redirect location
    if (preg_match('/Location: (.+)/', $fullOutput, $matches)) {
        echo "   - Redirect to: " . trim($matches[1]) . "\n";
    }
}

echo "\n" . ($hasTabs && $hasTabContent && $hasPromos ? "✅ SUCCESS" : "❌ PROBLEM") . "\n";
echo "=== END TEST ===\n";
