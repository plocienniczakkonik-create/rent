<?php
// Test końcowy dashboard-staff.php
session_start();
$_SESSION['user'] = ['email' => 'test2@example.com', 'role' => 'staff'];

// Capture output
ob_start();
$_GET['page'] = 'dashboard-staff';
include 'index.php';
$output = ob_get_contents();
ob_end_clean();

// Analyze output
echo "=== FINAL DASHBOARD TEST ===\n";
echo "1. Output length: " . strlen($output) . " characters\n";
echo "2. Contains 'nav-pills': " . (strpos($output, 'nav-pills') !== false ? 'YES' : 'NO') . "\n";
echo "3. Contains 'Produkty': " . (strpos($output, 'Produkty') !== false ? 'YES' : 'NO') . "\n";
echo "4. Contains 'Promocje': " . (strpos($output, 'Promocje') !== false ? 'YES' : 'NO') . "\n";
echo "5. Contains 'Raporty': " . (strpos($output, 'Raporty') !== false ? 'YES' : 'NO') . "\n";
echo "6. Contains 'Słowniki': " . (strpos($output, 'Słowniki') !== false ? 'YES' : 'NO') . "\n";
echo "7. Contains 'Ustawienia': " . (strpos($output, 'Ustawienia') !== false ? 'YES' : 'NO') . "\n";

// Count sections with content
$content_count = 0;
$sections = ['Produkty', 'Pojazdy', 'Zamówienia', 'Promocje', 'Najbliższe terminy', 'Raporty', 'Słowniki', 'Ustawienia'];
foreach ($sections as $section) {
    if (strpos($output, $section) !== false) {
        $content_count++;
        echo "   ✓ $section found\n";
    } else {
        echo "   ✗ $section missing\n";
    }
}

echo "\n" . ($content_count >= 6 ? "✅ SUKCES" : "❌ PROBLEM") . ": $content_count/8 sekcji znalezionych\n";
echo "=== END TEST ===\n";
