<?php
require_once 'includes/config.php';
require_once 'includes/db.php';

$pdo = db();

echo "Sprawdzanie ustawień Fleet Management:\n\n";

// Sprawdź ustawienia Fleet Management
$settings = ['fleet_management_enabled', 'deposit_system_enabled', 'location_fees_enabled'];

foreach ($settings as $setting) {
    $stmt = $pdo->prepare("SELECT setting_value FROM shop_settings WHERE setting_key = ?");
    $stmt->execute([$setting]);
    $value = $stmt->fetchColumn();
    echo "{$setting}: " . ($value ?? 'brak') . "\n";
}

echo "\n--- Dodawanie brakujących ustawień ---\n";

// Dodaj brakujące ustawienia
$defaultSettings = [
    'fleet_management_enabled' => '1',
    'deposit_system_enabled' => '1',
    'location_fees_enabled' => '1'
];

foreach ($defaultSettings as $key => $defaultValue) {
    $stmt = $pdo->prepare("SELECT setting_value FROM shop_settings WHERE setting_key = ?");
    $stmt->execute([$key]);
    $existing = $stmt->fetchColumn();

    if ($existing === false) {
        $stmt = $pdo->prepare("INSERT INTO shop_settings (setting_key, setting_value) VALUES (?, ?)");
        $stmt->execute([$key, $defaultValue]);
        echo "✅ Dodano ustawienie: {$key} = {$defaultValue}\n";
    } else {
        echo "⚠️ Ustawienie istnieje: {$key} = {$existing}\n";
    }
}

echo "\nSprawdzanie ustawień po aktualizacji:\n";

foreach ($settings as $setting) {
    $stmt = $pdo->prepare("SELECT setting_value FROM shop_settings WHERE setting_key = ?");
    $stmt->execute([$setting]);
    $value = $stmt->fetchColumn();
    echo "{$setting}: " . ($value ?? 'brak') . "\n";
}
