<?php
// Setup session for browser testing
session_start();

// Simulate successful login
$_SESSION['user'] = [
    'email' => 'test2@example.com',
    'role' => 'staff'
];

echo "Session set for browser testing\n";
echo "Email: " . $_SESSION['user']['email'] . "\n";
echo "Role: " . $_SESSION['user']['role'] . "\n";
echo "\nNow try accessing: http://localhost/rental/index.php?page=dashboard-staff\n";
