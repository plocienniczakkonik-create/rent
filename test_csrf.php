<?php
echo "=== TEST CSRF Function ===\n";

// Test 1: Load staff helpers
echo "1. Loading staff helpers...\n";
require_once 'pages/staff/_helpers.php';

// Test 2: Check if csrf_token exists
echo "2. csrf_token function exists: " . (function_exists('csrf_token') ? 'YES' : 'NO') . "\n";

// Test 3: Check if session is started
echo "3. Session active: " . (session_status() === PHP_SESSION_ACTIVE ? 'YES' : 'NO') . "\n";

// Test 4: Try to call csrf_token
if (function_exists('csrf_token')) {
    try {
        $token = csrf_token();
        echo "4. csrf_token() result: " . $token . "\n";
    } catch (Exception $e) {
        echo "4. csrf_token() error: " . $e->getMessage() . "\n";
    }
} else {
    echo "4. csrf_token() not available\n";
}

echo "=== END TEST ===\n";
