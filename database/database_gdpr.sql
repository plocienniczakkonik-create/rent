-- Tabela rejestru zgód użytkownika
CREATE TABLE IF NOT EXISTS user_consents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    consent_type VARCHAR(64) NOT NULL,
    consent_text TEXT NOT NULL,
    given_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    revoked_at DATETIME NULL,
    ip_address VARCHAR(45),
    source VARCHAR(64),
    INDEX idx_user_id (user_id),
    INDEX idx_consent_type (consent_type)
);

-- Przykładowe zgody
INSERT INTO user_consents (user_id, consent_type, consent_text, given_at, ip_address, source) VALUES
(1, 'privacy_policy', 'Akceptacja polityki prywatności', NOW(), '127.0.0.1', 'registration'),
(1, 'marketing', 'Zgoda na otrzymywanie informacji marketingowych', NOW(), '127.0.0.1', 'profile_edit');

-- Tabela rejestru żądań RODO
CREATE TABLE IF NOT EXISTS gdpr_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    request_type ENUM('access','erase','rectify','export') NOT NULL,
    status ENUM('new','processing','completed','rejected') DEFAULT 'new',
    requested_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    processed_at DATETIME NULL,
    details TEXT,
    INDEX idx_user_id (user_id),
    INDEX idx_request_type (request_type)
);

-- Przykładowe żądania
INSERT INTO gdpr_requests (user_id, request_type, status, details) VALUES
(1, 'access', 'completed', 'Użytkownik poprosił o dostęp do danych.'),
(1, 'erase', 'new', 'Użytkownik poprosił o usunięcie danych.');
