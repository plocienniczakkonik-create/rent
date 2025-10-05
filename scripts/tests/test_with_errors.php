<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
$_SESSION['user'] = ['email' => 'test2@example.com', 'role' => 'staff'];
$_GET['page'] = 'dashboard-staff';

ob_start();
include 'index.php';
$fullOutput = ob_get_contents();
ob_end_clean();

// Check for errors in output
echo "=== OUTPUT WITH ERRORS ===\n";
echo "Length: " . strlen($fullOutput) . "\n";

if (strpos($fullOutput, 'Fatal error') !== false) {
    echo "FATAL ERROR FOUND!\n";
    // Extract the error
    if (preg_match('/Fatal error:.*?in.*?on line \d+/', $fullOutput, $matches)) {
        echo "Error: " . $matches[0] . "\n";
    }
}

if (strpos($fullOutput, 'Parse error') !== false) {
    echo "PARSE ERROR FOUND!\n";
}

if (strpos($fullOutput, 'Warning') !== false) {
    echo "WARNINGS FOUND!\n";
}

// Check what's in main
$mainStart = strpos($fullOutput, '<main class="site-main">');
$mainEnd = strpos($fullOutput, '</main>');

if ($mainStart !== false && $mainEnd !== false) {
    $mainContent = substr($fullOutput, $mainStart + 24, $mainEnd - $mainStart - 24);
    echo "Main content length: " . strlen(trim($mainContent)) . "\n";

    if (strlen(trim($mainContent)) > 0) {
        echo "Main content first 200 chars:\n";
        echo substr(trim($mainContent), 0, 200) . "\n";
    } else {
        echo "MAIN IS EMPTY!\n";
    }
} else {
    echo "NO MAIN TAGS FOUND!\n";
}
