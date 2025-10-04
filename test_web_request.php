<?php
// Test via web request instead of CLI
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost/rental/index.php?page=dashboard-staff');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_COOKIEFILE, ''); // Enable cookies
curl_setopt($ch, CURLOPT_COOKIEJAR, ''); // Save cookies

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "=== WEB REQUEST TEST ===\n";
echo "HTTP Code: $httpCode\n";
echo "Response length: " . strlen($response) . " chars\n";

// Check if redirected to login
$isLogin = strpos($response, 'id="loginForm"') !== false || strpos($response, 'page=login') !== false;
echo "Is login page: " . ($isLogin ? 'YES' : 'NO') . "\n";

// Check for dashboard sections
$sections = ['nav-pills', 'Produkty', 'Promocje', 'Raporty', 'Słowniki', 'Ustawienia'];
$found = 0;
foreach ($sections as $section) {
    if (strpos($response, $section) !== false) {
        $found++;
        echo "✓ $section\n";
    } else {
        echo "✗ $section\n";
    }
}

echo "\nResult: " . ($isLogin ? "❌ REDIRECTED TO LOGIN" : ($found >= 4 ? "✅ SUCCESS" : "❌ PROBLEM")) . "\n";
echo "=== END TEST ===\n";
