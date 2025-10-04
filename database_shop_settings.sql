-- Tabela dla ustawień sklepu
CREATE TABLE IF NOT EXISTS shop_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_setting_key (setting_key)
);

-- Domyślne ustawienia sklepu
INSERT INTO shop_settings (setting_key, setting_value) VALUES
-- Informacje biznesowe
('company_name', ''),
('company_address', ''),
('company_city', ''),
('company_postal_code', ''),
('company_country', 'Polska'),
('company_phone', ''),
('company_email', ''),
('company_website', ''),
('company_nip', ''),
('company_regon', ''),

-- Ustawienia regionalne
('default_currency', 'PLN'),
('timezone', 'Europe/Warsaw'),
('default_language', 'pl'),
('date_format', 'd.m.Y'),
('time_format', 'H:i'),

-- Podatki i ceny
('default_tax_rate', '23'),
('tax_included_in_prices', '0'),
('show_prices_with_tax', '1'),
('currency_symbol_position', 'after'),
('decimal_places', '2'),
('thousand_separator', ' '),
('decimal_separator', ','),

-- Godziny pracy
('business_hours_enabled', '0'),
('business_hours_monday', ''),
('business_hours_tuesday', ''),
('business_hours_wednesday', ''),
('business_hours_thursday', ''),
('business_hours_friday', ''),
('business_hours_saturday', ''),
('business_hours_sunday', ''),

-- Ustawienia rezerwacji
('min_rental_hours', '24'),
('max_rental_days', '30'),
('advance_booking_days', '365'),
('cancellation_hours', '24'),
('auto_confirmation', '0'),

-- Powiadomienia
('notification_new_booking', '1'),
('notification_cancellation', '1'),
('notification_payment', '1'),
('notification_email', ''),

-- SEO i marketing
('site_title', ''),
('meta_description', ''),
('meta_keywords', ''),
('google_analytics_id', ''),
('facebook_pixel_id', '')

ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);