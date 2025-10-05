-- ==================================================
-- SYSTEM ZARZĄDZANIA FLOTĄ - FLEET MANAGEMENT SYSTEM
-- ==================================================

-- 1. Tabela lokalizacji (miejsca odbioru/zwrotu)
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
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  INDEX `idx_city` (`city`),
  INDEX `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Modyfikacja tabeli vehicles - dodanie current_location_id
ALTER TABLE `vehicles` 
ADD COLUMN `current_location_id` INT COMMENT 'Aktualna lokalizacja pojazdu',
ADD FOREIGN KEY (`current_location_id`) REFERENCES `locations`(`id`) ON DELETE SET NULL;

-- 3. Tabela tras rezerwacji (skąd-dokąd)
CREATE TABLE IF NOT EXISTS `reservation_routes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `reservation_id` INT UNSIGNED NOT NULL COMMENT 'ID rezerwacji',
  `pickup_location_id` INT COMMENT 'ID lokalizacji odbioru',
  `return_location_id` INT COMMENT 'ID lokalizacji zwrotu',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  
  FOREIGN KEY (`reservation_id`) REFERENCES `reservations`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`pickup_location_id`) REFERENCES `locations`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`return_location_id`) REFERENCES `locations`(`id`) ON DELETE SET NULL,
  
  INDEX `idx_reservation` (`reservation_id`),
  INDEX `idx_pickup` (`pickup_location_id`),
  INDEX `idx_return` (`return_location_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Historia lokalizacji pojazdów
CREATE TABLE IF NOT EXISTS `vehicle_location_history` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `vehicle_id` INT UNSIGNED NOT NULL COMMENT 'ID pojazdu',
  `location_id` INT COMMENT 'ID lokalizacji',
  `moved_at` DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT 'Kiedy pojazd został przeniesiony',
  `moved_by` INT UNSIGNED COMMENT 'Kto przeniósł (ID użytkownika)',
  `reason` ENUM('rental_pickup', 'rental_return', 'maintenance', 'manual', 'initial') NOT NULL DEFAULT 'manual' COMMENT 'Powód przeniesienia',
  `notes` TEXT COMMENT 'Dodatkowe notatki',
  
  FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`location_id`) REFERENCES `locations`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`moved_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  
  INDEX `idx_vehicle` (`vehicle_id`),
  INDEX `idx_location` (`location_id`),
  INDEX `idx_moved_at` (`moved_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==================================================
-- SYSTEM KAUCJI ZWROTNYCH - DEPOSIT SYSTEM
-- ==================================================

-- 1. Rozszerzenie tabeli products o pola kaucji
ALTER TABLE `products` 
ADD COLUMN `deposit_enabled` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Czy kaucja jest włączona dla tego produktu',
ADD COLUMN `deposit_type` ENUM('fixed', 'percentage') DEFAULT 'fixed' COMMENT 'Typ kaucji: stała kwota lub procent',
ADD COLUMN `deposit_amount` DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Kwota kaucji lub procent (np. 15.00 = 15%)';

-- 2. Ustawienia systemowe kaucji
CREATE TABLE IF NOT EXISTS `shop_deposit_settings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `setting_key` VARCHAR(50) NOT NULL UNIQUE,
  `setting_value` TEXT,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Domyślne ustawienia kaucji
INSERT INTO `shop_deposit_settings` (`setting_key`, `setting_value`) VALUES
('deposit_system_enabled', '0'),
('deposit_include_in_payment', '0'),
('deposit_display_mode', 'separate'),
('deposit_default_type', 'fixed'),
('deposit_default_amount', '500.00');

-- 3. Kaucje dla konkretnych rezerwacji
CREATE TABLE IF NOT EXISTS `reservation_deposits` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `reservation_id` INT UNSIGNED NOT NULL COMMENT 'ID rezerwacji',
  `deposit_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Kwota kaucji',
  `deposit_type` ENUM('fixed', 'percentage') NOT NULL DEFAULT 'fixed',
  `included_in_payment` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Czy wliczona w płatność',
  `status` ENUM('pending', 'paid', 'returned', 'withheld') NOT NULL DEFAULT 'pending',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  
  FOREIGN KEY (`reservation_id`) REFERENCES `reservations`(`id`) ON DELETE CASCADE,
  
  INDEX `idx_reservation` (`reservation_id`),
  INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==================================================
-- SYSTEM OPŁAT LOKALIZACYJNYCH - LOCATION FEES SYSTEM
-- ==================================================

-- 1. Ustawienia systemowe opłat lokalizacyjnych
CREATE TABLE IF NOT EXISTS `location_fees_settings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `setting_key` VARCHAR(50) NOT NULL UNIQUE,
  `setting_value` TEXT,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Domyślne ustawienia opłat lokalizacyjnych
INSERT INTO `location_fees_settings` (`setting_key`, `setting_value`) VALUES
('location_fees_enabled', '0'),
('default_fee_amount', '50.00'),
('fee_calculation_method', 'fixed');

-- 2. Tabela opłat między lokalizacjami
CREATE TABLE IF NOT EXISTS `location_fees` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `pickup_location_id` INT NOT NULL COMMENT 'ID lokalizacji odbioru',
  `return_location_id` INT NOT NULL COMMENT 'ID lokalizacji zwrotu',
  `fee_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Opłata za trasę',
  `fee_type` ENUM('fixed', 'per_km', 'per_day') NOT NULL DEFAULT 'fixed',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  FOREIGN KEY (`pickup_location_id`) REFERENCES `locations`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`return_location_id`) REFERENCES `locations`(`id`) ON DELETE CASCADE,
  
  UNIQUE KEY `unique_route` (`pickup_location_id`, `return_location_id`),
  INDEX `idx_pickup` (`pickup_location_id`),
  INDEX `idx_return` (`return_location_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Opłaty lokalizacyjne dla konkretnych rezerwacji
CREATE TABLE IF NOT EXISTS `reservation_location_fees` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `reservation_id` INT UNSIGNED NOT NULL COMMENT 'ID rezerwacji',
  `pickup_location_id` INT COMMENT 'ID lokalizacji odbioru',
  `return_location_id` INT COMMENT 'ID lokalizacji zwrotu',
  `fee_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Naliczona opłata',
  `fee_type` ENUM('fixed', 'per_km', 'per_day') NOT NULL DEFAULT 'fixed',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  
  FOREIGN KEY (`reservation_id`) REFERENCES `reservations`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`pickup_location_id`) REFERENCES `locations`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`return_location_id`) REFERENCES `locations`(`id`) ON DELETE SET NULL,
  
  INDEX `idx_reservation` (`reservation_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==================================================
-- TRIGGERY I PROCEDURY
-- ==================================================

-- Trigger do automatycznej aktualizacji lokalizacji pojazdu po zakończeniu rezerwacji
DELIMITER $$

CREATE TRIGGER `update_vehicle_location_after_rental` 
AFTER UPDATE ON `reservations`
FOR EACH ROW
BEGIN
    -- Jeśli status zmienił się na 'confirmed' i rezerwacja ma return_location_id
    IF NEW.status = 'confirmed' AND OLD.status != 'confirmed' THEN
        -- Znajdź pojazd związany z tą rezerwacją (przez SKU/product_id)
        SET @vehicle_id = (
            SELECT v.id 
            FROM vehicles v 
            JOIN products p ON v.product_id = p.id 
            WHERE p.sku = NEW.sku 
            LIMIT 1
        );
        
        -- Znajdź return_location_id z reservation_routes
        SET @return_location_id = (
            SELECT return_location_id 
            FROM reservation_routes 
            WHERE reservation_id = NEW.id 
            LIMIT 1
        );
        
        -- Aktualizuj lokalizację pojazdu
        IF @vehicle_id IS NOT NULL AND @return_location_id IS NOT NULL THEN
            UPDATE vehicles 
            SET current_location_id = @return_location_id 
            WHERE id = @vehicle_id;
            
            -- Dodaj wpis do historii
            INSERT INTO vehicle_location_history 
            (vehicle_id, location_id, reason, notes) 
            VALUES 
            (@vehicle_id, @return_location_id, 'rental_return', CONCAT('Auto move after reservation #', NEW.id));
        END IF;
    END IF;
END$$

DELIMITER ;

-- ==================================================
-- PRZYKŁADOWE DANE TESTOWE
-- ==================================================

-- Dodaj przykładowe lokalizacje
INSERT INTO `locations` (`name`, `address`, `city`, `postal_code`, `is_active`) VALUES
('Warszawa Centrum', 'ul. Marszałkowska 123, Warszawa', 'Warszawa', '00-123', 1),
('Kraków Główny', 'ul. Floriańska 45, Kraków', 'Kraków', '31-019', 1),
('Gdańsk Port', 'ul. Długi Targ 10, Gdańsk', 'Gdańsk', '80-828', 1),
('Wrocław Rynek', 'Rynek 1, Wrocław', 'Wrocław', '50-996', 1),
('Poznań Plaza', 'ul. Półwiejska 42, Poznań', 'Poznań', '61-888', 1);

-- Dodaj przykładowe opłaty lokalizacyjne
INSERT INTO `location_fees` (`pickup_location_id`, `return_location_id`, `fee_amount`) VALUES
(1, 2, 150.00), -- Warszawa -> Kraków
(1, 3, 200.00), -- Warszawa -> Gdańsk  
(1, 4, 120.00), -- Warszawa -> Wrocław
(1, 5, 100.00), -- Warszawa -> Poznań
(2, 1, 150.00), -- Kraków -> Warszawa
(2, 3, 250.00), -- Kraków -> Gdańsk
(3, 1, 200.00), -- Gdańsk -> Warszawa
(4, 1, 120.00), -- Wrocław -> Warszawa
(5, 1, 100.00); -- Poznań -> Warszawa