<?php
// Auto login and dashboard test (no output before session)
session_start();

// Simulate login
$_POST['email'] = 'test2@example.com';
$_POST['password'] = 'test1234';
$_POST['_token'] = 'test';

// Login
ob_start();
include 'auth/login-handler.php';
ob_end_clean();

// Check session
if (!isset($_SESSION['user'])) {
    die("LOGIN FAILED\n");
}

// Test dashboard
ob_start();
$_GET['page'] = 'dashboard-staff';
include 'index.php';
$dashboard = ob_get_contents();
ob_end_clean();

// Analyze results
echo "=== DASHBOARD ANALYSIS ===\n";
echo "1. Dashboard size: " . strlen($dashboard) . " chars\n";
echo "2. User: " . $_SESSION['user']['email'] . " (" . $_SESSION['user']['role'] . ")\n";

// Check sections
$sections = [
    'Produkty' => 'products',
    'Pojazdy' => 'vehicles',
    'Zamówienia' => 'orders',
    'Promocje' => 'promos',
    'Najbliższe terminy' => 'upcoming',
    'Raporty' => 'reports',
    'Słowniki' => 'dicts',
    'Ustawienia' => 'settings'
];

echo "\n3. Section analysis:\n";
$active_sections = 0;
foreach ($sections as $name => $id) {
    $has_name = strpos($dashboard, $name) !== false;
    $has_id = strpos($dashboard, "id=\"$id\"") !== false;
    $has_content = strpos($dashboard, "tab-pane") !== false && strpos($dashboard, $id) !== false;

    if ($has_name && $has_id) {
        $active_sections++;
        echo "   ✓ $name (ID: $id)\n";
    } else {
        echo "   ✗ $name (name:" . ($has_name ? 'YES' : 'NO') . " id:" . ($has_id ? 'YES' : 'NO') . ")\n";
    }
}

echo "\n4. Navigation structure:\n";
echo "   nav-pills: " . (strpos($dashboard, 'nav-pills') !== false ? 'YES' : 'NO') . "\n";
echo "   tab-content: " . (strpos($dashboard, 'tab-content') !== false ? 'YES' : 'NO') . "\n";
echo "   Active tabs: $active_sections/8\n";

// Show first few tab navigation items
if (preg_match_all('/<li class="nav-item">.*?<\/li>/s', $dashboard, $matches)) {
    echo "\n5. Found " . count($matches[0]) . " navigation items\n";
    foreach ($matches[0] as $i => $nav) {
        if (preg_match('/href="#([^"]+)"[^>]*>([^<]+)/', $nav, $tab)) {
            echo "   Tab " . ($i + 1) . ": {$tab[2]} -> #{$tab[1]}\n";
        }
    }
}

echo "\n" . ($active_sections >= 6 ? "✅ SUCCESS" : "❌ PROBLEM") . "\n";
echo "=== END ===\n";
