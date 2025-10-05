<?php
session_start();

// Poprawne ustawienie sesji - user_id zamiast user
$_SESSION['user_id'] = 3; // ID staff user z bazy (test2@example.com)

try {
    ob_start();
    include 'pages/dashboard-staff.php';
    $output = ob_get_contents();
    ob_end_clean();

    echo "SUCCESS: Dashboard loaded, " . strlen($output) . " chars\n";

    // Check for key content
    $hasNav = strpos($output, 'nav-pills') !== false;
    $hasPromos = strpos($output, 'Promocje') !== false;
    $hasSettings = strpos($output, 'Ustawienia') !== false;
    $hasReports = strpos($output, 'Raporty') !== false;
    $hasDicts = strpos($output, 'Słowniki') !== false;

    echo "Has nav-pills: " . ($hasNav ? 'YES' : 'NO') . "\n";
    echo "Has Promocje: " . ($hasPromos ? 'YES' : 'NO') . "\n";
    echo "Has Ustawienia: " . ($hasSettings ? 'YES' : 'NO') . "\n";
    echo "Has Raporty: " . ($hasReports ? 'YES' : 'NO') . "\n";
    echo "Has Słowniki: " . ($hasDicts ? 'YES' : 'NO') . "\n";

    if ($hasNav && $hasPromos && $hasSettings) {
        echo "✅ ALL SECTIONS WORKING!\n";
    } else {
        echo "❌ Some sections missing\n";
    }
} catch (Exception $e) {
    echo "EXCEPTION: " . $e->getMessage() . "\n";
} catch (Error $e) {
    echo "FATAL: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine() . "\n";
}
