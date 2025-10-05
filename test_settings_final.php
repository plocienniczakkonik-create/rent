<?php
// Test final settings panel
require_once 'includes/db.php';

echo "<h2>Test panelu ustawień - wszystkie komponenty</h2>";

// Test 1: Database connection
try {
    $db = db();
    echo "✅ Połączenie z bazą danych: OK<br>";
} catch (Exception $e) {
    echo "❌ Błąd bazy danych: " . $e->getMessage() . "<br>";
    exit;
}

// Test 2: Check settings tables
$tables = ['payment_settings', 'shop_settings', 'email_templates', 'email_settings'];
foreach ($tables as $table) {
    try {
        $stmt = $db->query("SELECT COUNT(*) FROM $table");
        $count = $stmt->fetchColumn();
        echo "✅ Tabela $table: $count rekordów<br>";
    } catch (Exception $e) {
        echo "❌ Błąd tabeli $table: " . $e->getMessage() . "<br>";
    }
}

// Test 3: Check users table structure
try {
    $stmt = $db->query("SELECT first_name, last_name, email, role FROM users LIMIT 1");
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user) {
        echo "✅ Struktura users: first_name={$user['first_name']}, last_name={$user['last_name']}, role={$user['role']}<br>";
    }
} catch (Exception $e) {
    echo "❌ Błąd struktury users: " . $e->getMessage() . "<br>";
}

// Test 4: Syntax check of all settings files
$settings_files = [
    'pages/staff/settings/account-profile.php',
    'pages/staff/settings/users-list.php',
    'pages/staff/settings/users-add.php',
    'pages/staff/settings/payments-general.php',
    'pages/staff/settings/payments-gateways.php',
    'pages/staff/settings/shop-general.php',
    'pages/staff/settings/email-templates.php',
    'pages/staff/settings/email-smtp.php'
];

echo "<br><h3>Sprawdzenie składni plików ustawień:</h3>";
foreach ($settings_files as $file) {
    if (file_exists($file)) {
        $output = [];
        $return_var = 0;
        exec("php -l \"$file\" 2>&1", $output, $return_var);

        if ($return_var === 0) {
            echo "✅ $file: Składnia OK<br>";
        } else {
            echo "❌ $file: " . implode(' ', $output) . "<br>";
        }
    } else {
        echo "❌ $file: Plik nie istnieje<br>";
    }
}

echo "<br><h3>Test kompletny!</h3>";
echo "<p><a href='?page=dashboard-staff&section=settings'>Przejdź do panelu ustawień</a></p>";
