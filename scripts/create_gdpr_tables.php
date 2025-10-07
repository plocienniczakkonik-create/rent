<?php
// Skrypt do automatycznego tworzenia tabel RODO jeśli nie istnieją
require_once __DIR__ . '/../includes/db.php';

$db = db();

function tableExists(PDO $db, string $table): bool {
    try {
        $db->query("SELECT 1 FROM `$table` LIMIT 1");
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

$created = [];

if (!tableExists($db, 'user_consents')) {
    $db->exec(<<<SQL
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
SQL);
    $created[] = 'user_consents';
}

if (!tableExists($db, 'gdpr_requests')) {
    $db->exec(<<<SQL
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
SQL);
    $created[] = 'gdpr_requests';
}

if (!tableExists($db, 'gdpr_audit')) {
    $db->exec(<<<SQL
CREATE TABLE IF NOT EXISTS gdpr_audit (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED,
    action VARCHAR(32) NOT NULL,
    details TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_action (action)
);
SQL);
    $created[] = 'gdpr_audit';
}

if ($created) {
    echo "Utworzono tabele: " . implode(", ", $created) . ".";
} else {
    echo "Wszystkie tabele RODO już istnieją.";
}
