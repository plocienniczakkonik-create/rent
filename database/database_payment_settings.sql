-- Tabela dla ustawień płatności
CREATE TABLE IF NOT EXISTS payment_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_setting_key (setting_key)
);

-- Domyślne ustawienia ogólne płatności
INSERT INTO payment_settings (setting_key, setting_value) VALUES
('general_currency', 'PLN'),
('general_min_deposit', '100'),
('general_deposit_type', 'fixed'),
('general_deposit_percentage', '20'),
('general_auto_refund', '0'),
('general_refund_days', '7'),
('general_payment_timeout', '15'),
('general_receipt_email', '1'),
('general_invoice_enabled', '0'),
('general_tax_rate', '23'),
('general_rounding', '0.01')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);

-- Domyślne ustawienia bramek (wyłączone)
INSERT INTO payment_settings (setting_key, setting_value) VALUES
('stripe_enabled', '0'),
('paypal_enabled', '0'),
('przelewy24_enabled', '0')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);