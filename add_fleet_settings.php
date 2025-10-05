<?php
require_once 'includes/db.php';

echo "Dodawanie domyślnych ustawień dla systemów Fleet Management...\n";

$stmt = db()->prepare('INSERT IGNORE INTO shop_settings (setting_key, setting_value) VALUES (?, ?)');

$settings = [
    // Fleet Management
    ['fleet_management_enabled', '0'],
    ['fleet_default_location', ''],
    ['fleet_auto_update_location', '1'],
    ['fleet_require_location_selection', '1'],

    // Deposit System
    ['deposit_system_enabled', '0'],
    ['deposit_include_in_payment', '0'],
    ['deposit_display_mode', 'separate'],
    ['deposit_default_type', 'fixed'],
    ['deposit_default_amount', '500.00'],

    // Location Fees
    ['location_fees_enabled', '0'],
    ['location_fees_display_mode', 'separate'],
    ['location_fees_auto_calculate', '1']
];

$added = 0;
foreach ($settings as $setting) {
    $stmt->execute($setting);
    if ($stmt->rowCount() > 0) {
        $added++;
    }
}

echo "✓ Dodano $added nowych ustawień do shop_settings\n";

$total = db()->query("SELECT COUNT(*) FROM shop_settings")->fetchColumn();
echo "✓ Łącznie ustawień w bazie: $total\n";
echo "✓ Panel administracyjny jest gotowy!\n";
