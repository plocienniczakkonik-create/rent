-- 12. Powiązanie rezerwacji z egzemplarzem pojazdu
ALTER TABLE `reservations` ADD COLUMN `vehicle_id` INT UNSIGNED COMMENT 'ID egzemplarza pojazdu';
ALTER TABLE `reservations` ADD INDEX `idx_vehicle_id` (`vehicle_id`);
ALTER TABLE `reservations` ADD FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles`(`id`) ON DELETE SET NULL;
-- Indeksy dla analityki i raportów
ALTER TABLE `reservations` ADD INDEX `idx_pickup_location` (`pickup_location`), ADD INDEX `idx_status` (`status`);
ALTER TABLE `vehicles` ADD INDEX `idx_status` (`status`);
-- 11. Tabela serwisów i przeglądów pojazdów
CREATE TABLE IF NOT EXISTS `vehicle_services` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `vehicle_id` INT UNSIGNED NOT NULL COMMENT 'ID pojazdu',
  `reservation_id` INT UNSIGNED COMMENT 'ID rezerwacji',
  `service_type` VARCHAR(64) NOT NULL COMMENT 'Typ serwisu (przegląd, naprawa, wymiana)',
  `description` TEXT,
  `cost` DECIMAL(10,2) DEFAULT 0.00,
  `service_date` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `status` ENUM('scheduled','completed','in_progress') DEFAULT 'scheduled',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`reservation_id`) REFERENCES `reservations`(`id`) ON DELETE SET NULL,
  INDEX `idx_vehicle` (`vehicle_id`),
  INDEX `idx_reservation` (`reservation_id`),
  INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
-- 10. Tabela incydentów pojazdów (kolizje, szkody)
CREATE TABLE IF NOT EXISTS `vehicle_incidents` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `vehicle_id` INT UNSIGNED NOT NULL COMMENT 'ID pojazdu',
  `reservation_id` INT UNSIGNED COMMENT 'ID rezerwacji',
  `incident_type` VARCHAR(64) NOT NULL COMMENT 'Typ incydentu (kolizja, szkoda, awaria)',
  `description` TEXT,
  `cost` DECIMAL(10,2) DEFAULT 0.00,
  `incident_date` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `status` ENUM('open','closed','in_review') DEFAULT 'open',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`reservation_id`) REFERENCES `reservations`(`id`) ON DELETE SET NULL,
  INDEX `idx_vehicle` (`vehicle_id`),
  INDEX `idx_reservation` (`reservation_id`),
  INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
-- Część 1: Podstawowe tabele systemu Fleet Management
-- (bez triggerów i procedur składowanych)

-- 1. Tabela lokalizacji
CREATE TABLE IF NOT EXISTS `locations` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL COMMENT 'Nazwa lokalizacji np. "Warszawa Centrum"',
  `address` TEXT COMMENT 'Pełny adres lokalizacji',
  `city` VARCHAR(100) NOT NULL COMMENT 'Miasto dla wyszukiwania',
  `postal_code` VARCHAR(20) COMMENT 'Kod pocztowy',
  `coordinates_lat` DECIMAL(10, 8) COMMENT 'Szerokość geograficzna',
  `coordinates_lng` DECIMAL(11, 8) COMMENT 'Długość geograficzna',
  `contact_phone` VARCHAR(20) COMMENT 'Telefon kontaktowy',
  `opening_hours` TEXT COMMENT 'Godziny otwarcia w formacie JSON',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Czy lokalizacja jest aktywna',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Modyfikacja tabeli vehicles - dodanie current_location_id
-- Sprawdzamy czy kolumna już nie istnieje
SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = 'rental' AND TABLE_NAME = 'vehicles' AND COLUMN_NAME = 'current_location_id');
SET @sqlstmt := IF(@exist = 0, 'ALTER TABLE vehicles ADD COLUMN current_location_id INT COMMENT "Aktualna lokalizacja pojazdu"', 'SELECT "Kolumna current_location_id już istnieje"');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 3. Tabela tras rezerwacji
CREATE TABLE IF NOT EXISTS `reservation_routes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `reservation_id` INT UNSIGNED NOT NULL COMMENT 'ID rezerwacji',
  `pickup_location_id` INT COMMENT 'ID lokalizacji odbioru',
  `return_location_id` INT COMMENT 'ID lokalizacji zwrotu',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Historia lokalizacji pojazdów
CREATE TABLE IF NOT EXISTS `vehicle_location_history` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `vehicle_id` INT UNSIGNED NOT NULL COMMENT 'ID pojazdu',
  `location_id` INT COMMENT 'ID lokalizacji',
  `moved_at` DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT 'Kiedy pojazd został przeniesiony',
  `moved_by` INT UNSIGNED COMMENT 'Kto przeniósł (ID użytkownika)',
  `reason` ENUM('rental_pickup', 'rental_return', 'maintenance', 'manual', 'initial') NOT NULL DEFAULT 'manual' COMMENT 'Powód przeniesienia',
  `notes` TEXT COMMENT 'Dodatkowe notatki'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Rozszerzenie tabeli products o pola kaucji
SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = 'rental' AND TABLE_NAME = 'products' AND COLUMN_NAME = 'deposit_enabled');
SET @sqlstmt := IF(@exist = 0, 'ALTER TABLE products ADD COLUMN deposit_enabled TINYINT(1) NOT NULL DEFAULT 0 COMMENT "Czy kaucja jest włączona"', 'SELECT "Kolumna deposit_enabled już istnieje"');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = 'rental' AND TABLE_NAME = 'products' AND COLUMN_NAME = 'deposit_type');
SET @sqlstmt := IF(@exist = 0, 'ALTER TABLE products ADD COLUMN deposit_type ENUM("fixed", "percentage") DEFAULT "fixed" COMMENT "Typ kaucji"', 'SELECT "Kolumna deposit_type już istnieje"');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = 'rental' AND TABLE_NAME = 'products' AND COLUMN_NAME = 'deposit_amount');
SET @sqlstmt := IF(@exist = 0, 'ALTER TABLE products ADD COLUMN deposit_amount DECIMAL(10,2) DEFAULT 0.00 COMMENT "Kwota lub procent kaucji"', 'SELECT "Kolumna deposit_amount już istnieje"');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 6. Ustawienia systemowe kaucji
CREATE TABLE IF NOT EXISTS `shop_deposit_settings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `setting_key` VARCHAR(50) NOT NULL UNIQUE,
  `setting_value` TEXT,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. Kaucje dla konkretnych rezerwacji
CREATE TABLE IF NOT EXISTS `reservation_deposits` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `reservation_id` INT UNSIGNED NOT NULL COMMENT 'ID rezerwacji',
  `deposit_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Kwota kaucji',
  `deposit_type` ENUM('fixed', 'percentage') NOT NULL DEFAULT 'fixed',
  `included_in_payment` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Czy wliczona w płatność',
  `status` ENUM('pending', 'paid', 'returned', 'withheld') NOT NULL DEFAULT 'pending',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 8. Ustawienia systemowe opłat lokalizacyjnych
CREATE TABLE IF NOT EXISTS `location_fees_settings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `setting_key` VARCHAR(50) NOT NULL UNIQUE,
  `setting_value` TEXT,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 9. Tabela opłat między lokalizacjami
CREATE TABLE IF NOT EXISTS `location_fees` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `pickup_location_id` INT NOT NULL COMMENT 'ID lokalizacji odbioru',
  `return_location_id` INT NOT NULL COMMENT 'ID lokalizacji zwrotu',
  `fee_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Opłata za trasę',
  `fee_type` ENUM('fixed', 'per_km', 'per_day') NOT NULL DEFAULT 'fixed',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;