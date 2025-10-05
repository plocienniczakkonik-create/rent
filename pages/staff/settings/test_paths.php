<?php
// Test podstawowy - sprawdzenie ścieżek
echo "Test ścieżek:\n\n";

echo "Obecny katalog: " . __DIR__ . "\n";
echo "Ścieżka do config: " . __DIR__ . '/../../../includes/config.php' . "\n";
echo "Czy config istnieje: " . (file_exists(__DIR__ . '/../../../includes/config.php') ? 'TAK' : 'NIE') . "\n\n";

echo "Ścieżka do db: " . __DIR__ . '/../../../includes/db.php' . "\n";
echo "Czy db istnieje: " . (file_exists(__DIR__ . '/../../../includes/db.php') ? 'TAK' : 'NIE') . "\n\n";

echo "Ścieżka do LocationFeeManager: " . __DIR__ . '/../../../classes/LocationFeeManager.php' . "\n";
echo "Czy LocationFeeManager istnieje: " . (file_exists(__DIR__ . '/../../../classes/LocationFeeManager.php') ? 'TAK' : 'NIE') . "\n\n";

echo "Ścieżka do FleetManager: " . __DIR__ . '/../../../classes/FleetManager.php' . "\n";
echo "Czy FleetManager istnieje: " . (file_exists(__DIR__ . '/../../../classes/FleetManager.php') ? 'TAK' : 'NIE') . "\n\n";

echo "Zawartość katalogu nadrzędnego:\n";
$parentDir = __DIR__ . '/../../..';
$files = scandir($parentDir);
foreach ($files as $file) {
    if ($file !== '.' && $file !== '..') {
        echo "- $file\n";
    }
}
