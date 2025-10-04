<?php
session_start();
$_SESSION['user'] = ['email' => 'test2@example.com', 'role' => 'staff'];

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

    echo "Has nav-pills: " . ($hasNav ? 'YES' : 'NO') . "\n";
    echo "Has Promocje: " . ($hasPromos ? 'YES' : 'NO') . "\n";
    echo "Has Ustawienia: " . ($hasSettings ? 'YES' : 'NO') . "\n";
} catch (Exception $e) {
    echo "EXCEPTION: " . $e->getMessage() . "\n";
} catch (Error $e) {
    echo "FATAL: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine() . "\n";
}
