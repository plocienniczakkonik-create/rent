-- Część 2: Dane domyślne i przykładowe

-- 10. Opłaty lokalizacyjne dla konkretnych rezerwacji
CREATE TABLE IF NOT EXISTS `reservation_location_fees` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `reservation_id` INT UNSIGNED NOT NULL COMMENT 'ID rezerwacji',
  `pickup_location_id` INT COMMENT 'ID lokalizacji odbioru',
  `return_location_id` INT COMMENT 'ID lokalizacji zwrotu',
  `fee_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Naliczona opłata',
  `fee_type` ENUM('fixed', 'per_km', 'per_day') NOT NULL DEFAULT 'fixed',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Dodaj przykładowe lokalizacje (tylko jeśli tabela jest pusta)
INSERT IGNORE INTO `locations` (`name`, `address`, `city`, `postal_code`, `is_active`) VALUES
('Warszawa Centrum', 'ul. Marszałkowska 123, Warszawa', 'Warszawa', '00-123', 1),
('Kraków Główny', 'ul. Floriańska 45, Kraków', 'Kraków', '31-019', 1),
('Gdańsk Port', 'ul. Długi Targ 10, Gdańsk', 'Gdańsk', '80-828', 1),
('Wrocław Rynek', 'Rynek 1, Wrocław', 'Wrocław', '50-996', 1),
('Poznań Plaza', 'ul. Półwiejska 42, Poznań', 'Poznań', '61-888', 1);

-- Domyślne ustawienia kaucji
INSERT IGNORE INTO `shop_deposit_settings` (`setting_key`, `setting_value`) VALUES
('deposit_system_enabled', '0'),
('deposit_include_in_payment', '0'),
('deposit_display_mode', 'separate'),
('deposit_default_type', 'fixed'),
('deposit_default_amount', '500.00');

-- Domyślne ustawienia opłat lokalizacyjnych  
INSERT IGNORE INTO `location_fees_settings` (`setting_key`, `setting_value`) VALUES
('location_fees_enabled', '0'),
('default_fee_amount', '50.00'),
('fee_calculation_method', 'fixed');

-- Dodaj przykładowe opłaty lokalizacyjne (tylko między różnymi miastami)
INSERT IGNORE INTO `location_fees` (`pickup_location_id`, `return_location_id`, `fee_amount`) VALUES
(1, 2, 150.00), -- Warszawa -> Kraków
(1, 3, 200.00), -- Warszawa -> Gdańsk  
(1, 4, 120.00), -- Warszawa -> Wrocław
(1, 5, 100.00), -- Warszawa -> Poznań
(2, 1, 150.00), -- Kraków -> Warszawa
(2, 3, 250.00), -- Kraków -> Gdańsk
(2, 4, 180.00), -- Kraków -> Wrocław
(2, 5, 200.00), -- Kraków -> Poznań
(3, 1, 200.00), -- Gdańsk -> Warszawa
(3, 2, 250.00), -- Gdańsk -> Kraków
(3, 4, 300.00), -- Gdańsk -> Wrocław
(3, 5, 350.00), -- Gdańsk -> Poznań
(4, 1, 120.00), -- Wrocław -> Warszawa
(4, 2, 180.00), -- Wrocław -> Kraków
(4, 3, 300.00), -- Wrocław -> Gdańsk
(4, 5, 150.00), -- Wrocław -> Poznań
(5, 1, 100.00), -- Poznań -> Warszawa
(5, 2, 200.00), -- Poznań -> Kraków
(5, 3, 350.00), -- Poznań -> Gdańsk
(5, 4, 150.00); -- Poznań -> Wrocław