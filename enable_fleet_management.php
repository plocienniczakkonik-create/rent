<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->prepare("INSERT INTO shop_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
    $stmt->execute(['fleet_management_enabled', '1']);
    echo "Ustawienie fleet_management_enabled zostało włączone.\n";

    // Sprawdź ustawienie
    $stmt = $pdo->prepare("SELECT setting_value FROM shop_settings WHERE setting_key = 'fleet_management_enabled'");
    $stmt->execute();
    $value = $stmt->fetchColumn();
    echo "Wartość ustawienia: " . $value . "\n";
} catch (Exception $e) {
    echo "Błąd: " . $e->getMessage() . "\n";
}
