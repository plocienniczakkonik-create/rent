-- Dodanie pól do dict_terms dla dodatków
ALTER TABLE `dict_terms`
  ADD COLUMN `price` DECIMAL(10,2) DEFAULT NULL,
  ADD COLUMN `charge_type` ENUM('per_day','once') DEFAULT NULL;
