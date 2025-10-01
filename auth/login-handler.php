<?php
session_start();

// UWAGA: to mock do etapu UI. Później podepniemy bazę + hashowanie.
$users = [
    'klient@example.com' => ['password' => 'demo123', 'role' => 'client', 'name' => 'Jan Klient'],
    'staff@example.com'  => ['password' => 'demo123', 'role' => 'staff',  'name' => 'Anna Obsługa'],
];

$email = trim($_POST['email'] ?? '');
$pass  = $_POST['password'] ?? '';
$redirect = 'index.php?page=login&error=1';

if ($email && $pass && isset($users[$email]) && $users[$email]['password'] === $pass) {
    $_SESSION['user'] = [
        'email' => $email,
        'name'  => $users[$email]['name'],
        'role'  => $users[$email]['role'],
    ];
    // przekierowanie wg roli
    if ($users[$email]['role'] === 'staff') {
        header('Location: index.php?page=dashboard-staff');
    } else {
        header('Location: index.php?page=dashboard-client');
    }
    exit;
}
header("Location: {$redirect}");
exit;
