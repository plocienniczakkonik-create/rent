<?php
// Test with hardcoded session bypass
session_start();

// Temporarily set session for testing
$_SESSION['user_id'] = 3;

// Now test dashboard through actual HTTP request with session
$sessionName = session_name();
$sessionId = session_id();

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost/rental/index.php?page=dashboard-staff');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIE, "$sessionName=$sessionId");

$response = curl_exec($ch);
curl_close($ch);

echo "=== SESSION WEB TEST ===\n";
echo "Session ID: $sessionId\n";
echo "Response length: " . strlen($response) . " chars\n";

// Check for dashboard content
$hasNav = strpos($response, 'nav-pills') !== false;
$hasPromos = strpos($response, 'Promocje') !== false;
$hasReports = strpos($response, 'Raporty') !== false;

echo "Has nav-pills: " . ($hasNav ? 'YES' : 'NO') . "\n";
echo "Has Promocje: " . ($hasPromos ? 'YES' : 'NO') . "\n";
echo "Has Raporty: " . ($hasReports ? 'YES' : 'NO') . "\n";

if ($hasNav && $hasPromos && $hasReports) {
    echo "✅ ALL SECTIONS WORKING!\n";
} else {
    // Check what we actually got
    if (strpos($response, 'Zaloguj się') !== false) {
        echo "❌ Still showing login form\n";
    } else {
        echo "❌ Dashboard partial - some sections missing\n";

        // Count what we have
        $allSections = ['Produkty', 'Pojazdy', 'Zamówienia', 'Promocje', 'Najbliższe terminy', 'Raporty', 'Słowniki', 'Ustawienia'];
        $foundCount = 0;
        foreach ($allSections as $section) {
            if (strpos($response, $section) !== false) {
                $foundCount++;
                echo "  ✓ $section\n";
            } else {
                echo "  ✗ $section\n";
            }
        }
        echo "  Total: $foundCount/8 sections\n";
    }
}

echo "=== END TEST ===\n";
