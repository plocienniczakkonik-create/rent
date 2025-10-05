<?php
echo "=== AUTO LOGIN TEST ===\n";

// Simulate login POST
$_POST['email'] = 'test2@example.com';
$_POST['password'] = 'test1234';
$_POST['_token'] = 'test'; // We'll handle this in auth

// Start fresh session
session_start();

// Login through handler
ob_start();
include 'auth/login-handler.php';
$result = ob_get_contents();
ob_end_clean();

echo "1. Login result: " . strlen($result) . " chars\n";
echo "2. Session user: " . (isset($_SESSION['user']) ? 'SET' : 'NOT SET') . "\n";

if (isset($_SESSION['user'])) {
    echo "   Email: " . $_SESSION['user']['email'] . "\n";
    echo "   Role: " . $_SESSION['user']['role'] . "\n";
}

// Now test dashboard
echo "\n3. Testing dashboard access...\n";
ob_start();
$_GET['page'] = 'dashboard-staff';
include 'index.php';
$dashboard = ob_get_contents();
ob_end_clean();

echo "4. Dashboard output: " . strlen($dashboard) . " chars\n";

// Check for specific sections
$sections = ['Produkty', 'Pojazdy', 'Zamówienia', 'Promocje', 'Najbliższe terminy', 'Raporty', 'Słowniki', 'Ustawienia'];
$found = 0;
foreach ($sections as $section) {
    if (strpos($dashboard, $section) !== false) {
        $found++;
        echo "   ✓ $section\n";
    } else {
        echo "   ✗ $section MISSING\n";
    }
}

echo "\n" . ($found >= 6 ? "✅ SUCCESS" : "❌ PROBLEM") . ": $found/8 sections found\n";

// Check for nav structure
echo "\n5. Navigation structure:\n";
echo "   nav-pills: " . (strpos($dashboard, 'nav-pills') !== false ? 'YES' : 'NO') . "\n";
echo "   tab-content: " . (strpos($dashboard, 'tab-content') !== false ? 'YES' : 'NO') . "\n";

echo "=== END TEST ===\n";
