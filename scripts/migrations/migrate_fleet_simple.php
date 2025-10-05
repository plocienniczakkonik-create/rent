<?php
require_once 'includes/db.php';

echo "=== Tworzenie tabel Fleet Management - jedna po drugiej ===\n\n";

$tables = [
    // 1. Tabela lokalizacji
    "CREATE TABLE IF NOT EXISTS `locations` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(100) NOT NULL,
        `address` TEXT,
        `city` VARCHAR(100) NOT NULL,
        `postal_code` VARCHAR(20),
        `is_active` TINYINT(1) NOT NULL DEFAULT 1,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    // 2. Trasy rezerwacji
    "CREATE TABLE IF NOT EXISTS `reservation_routes` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `reservation_id` INT UNSIGNED NOT NULL,
        `pickup_location_id` INT,
        `return_location_id` INT,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    // 3. Historia lokalizacji pojazdów
    "CREATE TABLE IF NOT EXISTS `vehicle_location_history` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `vehicle_id` INT UNSIGNED NOT NULL,
        `location_id` INT,
        `moved_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        `moved_by` INT UNSIGNED,
        `reason` ENUM('rental_pickup', 'rental_return', 'maintenance', 'manual', 'initial') NOT NULL DEFAULT 'manual',
        `notes` TEXT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    // 4. Ustawienia kaucji
    "CREATE TABLE IF NOT EXISTS `shop_deposit_settings` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `setting_key` VARCHAR(50) NOT NULL UNIQUE,
        `setting_value` TEXT,
        `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    // 5. Kaucje rezerwacji
    "CREATE TABLE IF NOT EXISTS `reservation_deposits` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `reservation_id` INT UNSIGNED NOT NULL,
        `deposit_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        `deposit_type` ENUM('fixed', 'percentage') NOT NULL DEFAULT 'fixed',
        `included_in_payment` TINYINT(1) NOT NULL DEFAULT 0,
        `status` ENUM('pending', 'paid', 'returned', 'withheld') NOT NULL DEFAULT 'pending',
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    // 6. Ustawienia opłat lokalizacyjnych
    "CREATE TABLE IF NOT EXISTS `location_fees_settings` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `setting_key` VARCHAR(50) NOT NULL UNIQUE,
        `setting_value` TEXT,
        `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    // 7. Opłaty lokalizacyjne
    "CREATE TABLE IF NOT EXISTS `location_fees` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `pickup_location_id` INT NOT NULL,
        `return_location_id` INT NOT NULL,
        `fee_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        `fee_type` ENUM('fixed', 'per_km', 'per_day') NOT NULL DEFAULT 'fixed',
        `is_active` TINYINT(1) NOT NULL DEFAULT 1,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    // 8. Opłaty w rezerwacjach
    "CREATE TABLE IF NOT EXISTS `reservation_location_fees` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `reservation_id` INT UNSIGNED NOT NULL,
        `pickup_location_id` INT,
        `return_location_id` INT,
        `fee_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        `fee_type` ENUM('fixed', 'per_km', 'per_day') NOT NULL DEFAULT 'fixed',
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
];

$success = 0;
$errors = 0;

foreach ($tables as $i => $sql) {
    try {
        db()->exec($sql);
        $success++;
        echo "✓ Tabela " . ($i + 1) . " utworzona\n";
    } catch (Exception $e) {
        $errors++;
        echo "✗ Błąd tabeli " . ($i + 1) . ": " . $e->getMessage() . "\n";
    }
}

echo "\n=== Dodawanie kolumn do istniejących tabel ===\n";

// Dodanie kolumn do products
$productColumns = [
    "ALTER TABLE `products` ADD COLUMN `deposit_enabled` TINYINT(1) NOT NULL DEFAULT 0",
    "ALTER TABLE `products` ADD COLUMN `deposit_type` ENUM('fixed', 'percentage') DEFAULT 'fixed'",
    "ALTER TABLE `products` ADD COLUMN `deposit_amount` DECIMAL(10,2) DEFAULT 0.00"
];

foreach ($productColumns as $i => $sql) {
    try {
        db()->exec($sql);
        $success++;
        echo "✓ Kolumna products " . ($i + 1) . " dodana\n";
    } catch (Exception $e) {
        // Sprawdź czy to błąd duplikatu kolumny
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "~ Kolumna products " . ($i + 1) . " już istnieje\n";
        } else {
            $errors++;
            echo "✗ Błąd kolumny products " . ($i + 1) . ": " . $e->getMessage() . "\n";
        }
    }
}

// Dodanie kolumny do vehicles
try {
    db()->exec("ALTER TABLE `vehicles` ADD COLUMN `current_location_id` INT");
    $success++;
    echo "✓ Kolumna vehicles.current_location_id dodana\n";
} catch (Exception $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "~ Kolumna vehicles.current_location_id już istnieje\n";
    } else {
        $errors++;
        echo "✗ Błąd kolumny vehicles: " . $e->getMessage() . "\n";
    }
}

echo "\n=== Dodawanie przykładowych danych ===\n";

// Lokalizacje
try {
    $stmt = db()->prepare("INSERT IGNORE INTO `locations` (`name`, `city`, `is_active`) VALUES (?, ?, 1)");
    $locations = [
        ['Warszawa Centrum', 'Warszawa'],
        ['Kraków Główny', 'Kraków'],
        ['Gdańsk Port', 'Gdańsk'],
        ['Wrocław Rynek', 'Wrocław'],
        ['Poznań Plaza', 'Poznań']
    ];

    foreach ($locations as $loc) {
        $stmt->execute($loc);
    }
    echo "✓ Lokalizacje dodane\n";
    $success++;
} catch (Exception $e) {
    echo "✗ Błąd lokalizacji: " . $e->getMessage() . "\n";
    $errors++;
}

// Ustawienia kaucji
try {
    $stmt = db()->prepare("INSERT IGNORE INTO `shop_deposit_settings` (`setting_key`, `setting_value`) VALUES (?, ?)");
    $settings = [
        ['deposit_system_enabled', '0'],
        ['deposit_include_in_payment', '0'],
        ['deposit_display_mode', 'separate'],
        ['deposit_default_type', 'fixed'],
        ['deposit_default_amount', '500.00']
    ];

    foreach ($settings as $setting) {
        $stmt->execute($setting);
    }
    echo "✓ Ustawienia kaucji dodane\n";
    $success++;
} catch (Exception $e) {
    echo "✗ Błąd ustawień kaucji: " . $e->getMessage() . "\n";
    $errors++;
}

// Ustawienia opłat
try {
    $stmt = db()->prepare("INSERT IGNORE INTO `location_fees_settings` (`setting_key`, `setting_value`) VALUES (?, ?)");
    $settings = [
        ['location_fees_enabled', '0'],
        ['default_fee_amount', '50.00'],
        ['fee_calculation_method', 'fixed']
    ];

    foreach ($settings as $setting) {
        $stmt->execute($setting);
    }
    echo "✓ Ustawienia opłat dodane\n";
    $success++;
} catch (Exception $e) {
    echo "✗ Błąd ustawień opłat: " . $e->getMessage() . "\n";
    $errors++;
}

echo "\n=== PODSUMOWANIE MIGRACJI ===\n";
echo "Pomyślnych operacji: $success\n";
echo "Błędów: $errors\n";

if ($errors === 0) {
    echo "🎉 System Fleet Management został POMYŚLNIE zainstalowany!\n";
} else {
    echo "⚠️  System Fleet Management został CZĘŚCIOWO zainstalowany.\n";
}

// Sprawdzenie final
echo "\n=== Sprawdzenie final ===\n";
$finalTables = ['locations', 'reservation_routes', 'shop_deposit_settings', 'location_fees'];
foreach ($finalTables as $table) {
    try {
        $count = db()->query("SELECT COUNT(*) FROM $table")->fetchColumn();
        echo "✓ $table: $count rekordów\n";
    } catch (Exception $e) {
        echo "✗ $table: brak tabeli\n";
    }
}
