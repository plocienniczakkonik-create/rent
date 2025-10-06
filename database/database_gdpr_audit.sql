-- Tabela audytu operacji RODO/GDPR
CREATE TABLE IF NOT EXISTS gdpr_audit (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED,
    action VARCHAR(32) NOT NULL,
    details TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_action (action)
);

-- Przykładowy wpis testowy
INSERT INTO gdpr_audit (user_id, action, details) VALUES (1, 'erase', 'Testowa anonimizacja użytkownika');
