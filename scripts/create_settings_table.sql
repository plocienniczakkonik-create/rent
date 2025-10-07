-- Tworzy tabelę settings do przechowywania ustawień systemowych (np. URL polityki prywatności, tekst banera cookies)
CREATE TABLE IF NOT EXISTS `settings` (
  `setting_key` VARCHAR(64) NOT NULL PRIMARY KEY,
  `setting_value` TEXT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
