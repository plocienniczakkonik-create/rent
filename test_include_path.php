<?php
echo "=== TEST INCLUDE PATH ===\n";

echo "1. Current dir: " . __DIR__ . "\n";
echo "2. Staff helpers path from pages/staff/: " . dirname(__DIR__) . '/includes/_helpers.php' . "\n";
echo "3. File exists: " . (file_exists(dirname(__DIR__) . '/includes/_helpers.php') ? 'YES' : 'NO') . "\n";

// Test loading directly
echo "4. Loading includes/_helpers.php directly...\n";
require_once 'includes/_helpers.php';

echo "5. csrf_token function exists: " . (function_exists('csrf_token') ? 'YES' : 'NO') . "\n";

if (function_exists('csrf_token')) {
    try {
        $token = csrf_token();
        echo "6. csrf_token() result: " . $token . "\n";
    } catch (Exception $e) {
        echo "6. csrf_token() error: " . $e->getMessage() . "\n";
    }
}

echo "=== END TEST ===\n";
