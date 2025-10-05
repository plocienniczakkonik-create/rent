-- Tabela dodatków do wynajmu
CREATE TABLE IF NOT EXISTS `addons` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(128) NOT NULL,
  `type` ENUM('fixed','per_day','per_rental') NOT NULL DEFAULT 'fixed',
  `price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `unit` VARCHAR(32) DEFAULT NULL,
  `active` TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
