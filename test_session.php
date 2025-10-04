<?php
echo "=== TEST BROWSER SESSION ===\n";

// Start session
session_start();

// Check current session
echo "1. Session status: " . (session_status() === PHP_SESSION_ACTIVE ? 'ACTIVE' : 'INACTIVE') . "\n";
echo "2. Session ID: " . session_id() . "\n";
echo "3. User in session: " . (isset($_SESSION['user']) ? 'YES' : 'NO') . "\n";

if (isset($_SESSION['user'])) {
    echo "   Email: " . ($_SESSION['user']['email'] ?? 'BRAK') . "\n";
    echo "   Role: " . ($_SESSION['user']['role'] ?? 'BRAK') . "\n";
}

// Test dashboard access
echo "\n4. Testing dashboard access...\n";
require_once 'auth/auth.php';
try {
    $staff = require_staff();
    echo "   Staff access: OK\n";
    echo "   Staff email: " . $staff['email'] . "\n";
} catch (Exception $e) {
    echo "   Staff access: FAILED - " . $e->getMessage() . "\n";
}

echo "=== END TEST ===\n";
