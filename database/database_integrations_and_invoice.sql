-- Skrypt SQL dla rozbudowy systemu o dane do faktury i integracje
-- Dodanie kolumn do tabeli reservations dla danych do faktury

-- 1. Dodaj kolumny do przechowywania danych do faktury w tabeli reservations
ALTER TABLE reservations 
ADD COLUMN request_invoice TINYINT(1) DEFAULT 0 COMMENT 'Czy klient chce fakturę',
ADD COLUMN invoice_company_name VARCHAR(255) NULL COMMENT 'Nazwa firmy do faktury',
ADD COLUMN invoice_tax_number VARCHAR(50) NULL COMMENT 'NIP firmy',
ADD COLUMN invoice_address VARCHAR(500) NULL COMMENT 'Adres firmy',
ADD COLUMN invoice_city VARCHAR(100) NULL COMMENT 'Miasto firmy',
ADD COLUMN invoice_postcode VARCHAR(20) NULL COMMENT 'Kod pocztowy firmy',
ADD COLUMN invoice_country VARCHAR(10) NULL COMMENT 'Kraj firmy';

-- 2. Utwórz tabelę system_settings dla przechowywania ustawień integracji (jeśli nie istnieje)
CREATE TABLE IF NOT EXISTS system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT NULL,
    setting_type ENUM('string', 'integer', 'boolean', 'json') DEFAULT 'string',
    description TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_setting_key (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_polish_ci;

-- 3. Wstaw domyślne ustawienia integracji
INSERT INTO system_settings (setting_key, setting_value, setting_type, description) VALUES
-- Integracje fakturowe
('invoice_integration_enabled', '0', 'boolean', 'Czy włączona integracja z systemem fakturującym'),
('invoice_api_provider', '', 'string', 'Dostawca API faktury (fakturownia, ifirma, wfirma, taxe, custom)'),
('invoice_api_key', '', 'string', 'Klucz API systemu fakturującego'),
('invoice_api_url', '', 'string', 'URL API dla własnego systemu fakturującego'),
('auto_create_invoices', '0', 'boolean', 'Czy automatycznie tworzyć faktury po zakończeniu rezerwacji'),

-- Integracje ERP
('erp_integration_enabled', '0', 'boolean', 'Czy włączona integracja z systemem ERP'),
('erp_system_type', '', 'string', 'Typ systemu ERP (sap, oracle, microsoft, sage, custom)'),
('erp_api_endpoint', '', 'string', 'Endpoint API systemu ERP'),
('erp_auth_token', '', 'string', 'Token autoryzacyjny dla systemu ERP'),

-- Integracje CRM
('crm_integration_enabled', '0', 'boolean', 'Czy włączona integracja z systemem CRM'),
('crm_system_type', '', 'string', 'Typ systemu CRM (salesforce, hubspot, pipedrive, freshworks, custom)'),
('crm_api_key', '', 'string', 'Klucz API systemu CRM'),
('crm_webhook_url', '', 'string', 'URL webhook dla systemu CRM'),

-- Ustawienia synchronizacji
('webhook_secret', '', 'string', 'Tajny klucz dla walidacji webhook'),
('sync_customer_data', '0', 'boolean', 'Czy synchronizować dane klientów'),
('sync_reservations', '0', 'boolean', 'Czy synchronizować rezerwacje')

ON DUPLICATE KEY UPDATE 
description = VALUES(description),
updated_at = CURRENT_TIMESTAMP;

-- 4. Utwórz tabelę integration_logs dla logowania działań integracji
CREATE TABLE IF NOT EXISTS integration_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    integration_type ENUM('invoice', 'erp', 'crm', 'webhook') NOT NULL,
    action VARCHAR(100) NOT NULL COMMENT 'Typ akcji (create_invoice, sync_customer, etc.)',
    reference_id INT NULL COMMENT 'ID powiązanego rekordu (reservation_id, customer_id)',
    reference_table VARCHAR(50) NULL COMMENT 'Nazwa tabeli powiązanego rekordu',
    request_data JSON NULL COMMENT 'Dane wysłane do API',
    response_data JSON NULL COMMENT 'Odpowiedź z API',
    status ENUM('pending', 'success', 'error', 'retry') DEFAULT 'pending',
    error_message TEXT NULL,
    external_id VARCHAR(255) NULL COMMENT 'ID w zewnętrznym systemie',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_integration_type (integration_type),
    INDEX idx_status (status),
    INDEX idx_reference (reference_id, reference_table),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_polish_ci;

-- 5. Utwórz tabelę webhook_events dla przychodzących webhook
CREATE TABLE IF NOT EXISTS webhook_events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    source_system VARCHAR(100) NOT NULL COMMENT 'System źródłowy (crm, erp, invoice)',
    event_type VARCHAR(100) NOT NULL COMMENT 'Typ wydarzenia',
    payload JSON NOT NULL COMMENT 'Dane webhook',
    signature VARCHAR(255) NULL COMMENT 'Podpis do walidacji',
    processed TINYINT(1) DEFAULT 0 COMMENT 'Czy przetworzono',
    processed_at TIMESTAMP NULL,
    error_message TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_source_system (source_system),
    INDEX idx_processed (processed),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_polish_ci;

-- 6. Dodaj indeksy dla lepszej wydajności na istniejących tabelach
ALTER TABLE reservations 
ADD INDEX idx_request_invoice (request_invoice),
ADD INDEX idx_invoice_tax_number (invoice_tax_number);

-- 7. Utwórz widok dla rezerwacji z danymi do faktury
CREATE OR REPLACE VIEW reservations_with_invoice AS
SELECT 
    r.*,
    CASE 
        WHEN r.request_invoice = 1 AND r.invoice_company_name IS NOT NULL 
        THEN CONCAT(r.invoice_company_name, ' (', r.invoice_tax_number, ')')
        ELSE r.customer_name
    END as billing_name,
    CASE 
        WHEN r.request_invoice = 1 AND r.invoice_address IS NOT NULL 
        THEN CONCAT(r.invoice_address, ', ', r.invoice_city, ' ', r.invoice_postcode, ', ', r.invoice_country)
        ELSE CONCAT(r.billing_address, ', ', r.billing_city, ' ', r.billing_postcode, ', ', r.billing_country)
    END as full_billing_address
FROM reservations r;

-- 8. Dodaj triggery dla automatycznego logowania zmian w integracjach
DELIMITER $$

CREATE TRIGGER after_reservation_insert_integration 
AFTER INSERT ON reservations
FOR EACH ROW
BEGIN
    -- Jeśli włączone synchronizowanie rezerwacji, dodaj do kolejki
    IF (SELECT setting_value FROM system_settings WHERE setting_key = 'sync_reservations' LIMIT 1) = '1' THEN
        INSERT INTO integration_logs (integration_type, action, reference_id, reference_table, status, created_at)
        VALUES ('crm', 'create_reservation', NEW.id, 'reservations', 'pending', NOW());
        
        -- Jeśli to rezerwacja z fakturą i włączone automatyczne faktury
        IF NEW.request_invoice = 1 AND (SELECT setting_value FROM system_settings WHERE setting_key = 'auto_create_invoices' LIMIT 1) = '1' THEN
            INSERT INTO integration_logs (integration_type, action, reference_id, reference_table, status, created_at)
            VALUES ('invoice', 'create_invoice', NEW.id, 'reservations', 'pending', NOW());
        END IF;
    END IF;
END$$

CREATE TRIGGER after_reservation_update_integration 
AFTER UPDATE ON reservations
FOR EACH ROW
BEGIN
    -- Jeśli zmienił się status na completed i włączone automatyczne faktury
    IF NEW.status = 'completed' AND OLD.status != 'completed' AND NEW.request_invoice = 1 
       AND (SELECT setting_value FROM system_settings WHERE setting_key = 'auto_create_invoices' LIMIT 1) = '1' THEN
        INSERT INTO integration_logs (integration_type, action, reference_id, reference_table, status, created_at)
        VALUES ('invoice', 'create_final_invoice', NEW.id, 'reservations', 'pending', NOW());
    END IF;
    
    -- Synchronizuj zmiany z CRM jeśli włączone
    IF (SELECT setting_value FROM system_settings WHERE setting_key = 'sync_reservations' LIMIT 1) = '1' THEN
        INSERT INTO integration_logs (integration_type, action, reference_id, reference_table, status, created_at)
        VALUES ('crm', 'update_reservation', NEW.id, 'reservations', 'pending', NOW());
    END IF;
END$$

DELIMITER ;

-- 9. Dodaj tłumaczenia dla nowych pól (opcjonalnie, jeśli używasz tabel tłumaczeń)
-- INSERT INTO translations (language_code, key_name, translation) VALUES
-- ('pl', 'request_invoice', 'Chcę otrzymać fakturę'),
-- ('en', 'request_invoice', 'I want to receive an invoice'),
-- ('pl', 'invoice_data', 'Dane do faktury'),
-- ('en', 'invoice_data', 'Invoice data');

-- 10. Przykładowe dane testowe (opcjonalnie)
-- INSERT INTO system_settings (setting_key, setting_value, description) VALUES
-- ('test_integration_mode', '1', 'Tryb testowy dla integracji'),
-- ('integration_timeout', '30', 'Timeout dla zapytań API w sekundach');

COMMIT;