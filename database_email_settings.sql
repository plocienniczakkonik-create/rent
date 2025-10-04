-- Tabela dla szablonów emaili
CREATE TABLE IF NOT EXISTS email_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    template_key VARCHAR(50) NOT NULL UNIQUE,
    template_name VARCHAR(100) NOT NULL,
    subject VARCHAR(200) NOT NULL,
    content TEXT NOT NULL,
    variables TEXT,
    enabled TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_template_key (template_key),
    INDEX idx_enabled (enabled)
);

-- Tabela dla ustawień email
CREATE TABLE IF NOT EXISTS email_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_setting_key (setting_key)
);

-- Domyślne szablony emaili
INSERT INTO email_templates (template_key, template_name, subject, content, variables, enabled) VALUES
('booking_confirmation', 'Potwierdzenie rezerwacji', 'Potwierdzenie rezerwacji #{booking_id}', 
'<h2>Dziękujemy za rezerwację!</h2>
<p>Szanowny/a {customer_name},</p>
<p>Potwierdzamy Państwa rezerwację na następujące parametry:</p>
<ul>
<li><strong>Numer rezerwacji:</strong> #{booking_id}</li>
<li><strong>Pojazd:</strong> {vehicle_name}</li>
<li><strong>Data od:</strong> {date_from}</li>
<li><strong>Data do:</strong> {date_to}</li>
<li><strong>Całkowity koszt:</strong> {total_price}</li>
</ul>
<p>W razie pytań prosimy o kontakt.</p>
<p>Pozdrawiamy,<br>{company_name}</p>',
'{customer_name}, {booking_id}, {vehicle_name}, {date_from}, {date_to}, {total_price}, {company_name}', 1),

('booking_cancellation', 'Anulowanie rezerwacji', 'Anulowanie rezerwacji #{booking_id}',
'<h2>Anulowanie rezerwacji</h2>
<p>Szanowny/a {customer_name},</p>
<p>Informujemy, że rezerwacja #{booking_id} została anulowana.</p>
<p><strong>Szczegóły anulowanej rezerwacji:</strong></p>
<ul>
<li><strong>Pojazd:</strong> {vehicle_name}</li>
<li><strong>Data od:</strong> {date_from}</li>
<li><strong>Data do:</strong> {date_to}</li>
<li><strong>Powód anulowania:</strong> {cancellation_reason}</li>
</ul>
<p>Kwota {refund_amount} zostanie zwrócona na Państwa konto w ciągu {refund_days} dni roboczych.</p>
<p>Pozdrawiamy,<br>{company_name}</p>',
'{customer_name}, {booking_id}, {vehicle_name}, {date_from}, {date_to}, {cancellation_reason}, {refund_amount}, {refund_days}, {company_name}', 1),

('payment_confirmation', 'Potwierdzenie płatności', 'Płatność potwierdzona - rezerwacja #{booking_id}',
'<h2>Płatność potwierdzona</h2>
<p>Szanowny/a {customer_name},</p>
<p>Potwierdzamy otrzymanie płatności za rezerwację #{booking_id}.</p>
<p><strong>Szczegóły płatności:</strong></p>
<ul>
<li><strong>Kwota:</strong> {payment_amount}</li>
<li><strong>Metoda płatności:</strong> {payment_method}</li>
<li><strong>Data płatności:</strong> {payment_date}</li>
<li><strong>Status:</strong> Opłacone</li>
</ul>
<p>Rezerwacja jest potwierdzona i oczekuje na realizację.</p>
<p>Pozdrawiamy,<br>{company_name}</p>',
'{customer_name}, {booking_id}, {payment_amount}, {payment_method}, {payment_date}, {company_name}', 1),

('reminder', 'Przypomnienie o rezerwacji', 'Przypomnienie - rezerwacja #{booking_id} jutro',
'<h2>Przypomnienie o rezerwacji</h2>
<p>Szanowny/a {customer_name},</p>
<p>Przypominamy o Państwa jutrzejszej rezerwacji:</p>
<ul>
<li><strong>Numer rezerwacji:</strong> #{booking_id}</li>
<li><strong>Pojazd:</strong> {vehicle_name}</li>
<li><strong>Odbiór:</strong> {pickup_date} o {pickup_time}</li>
<li><strong>Miejsce odbioru:</strong> {pickup_location}</li>
</ul>
<p>Prosimy o punktualne przybycie z dokumentem tożsamości i prawem jazdy.</p>
<p>Pozdrawiamy,<br>{company_name}</p>',
'{customer_name}, {booking_id}, {vehicle_name}, {pickup_date}, {pickup_time}, {pickup_location}, {company_name}', 1)

ON DUPLICATE KEY UPDATE 
template_name = VALUES(template_name),
subject = VALUES(subject),
content = VALUES(content),
variables = VALUES(variables);

-- Domyślne ustawienia SMTP
INSERT INTO email_settings (setting_key, setting_value) VALUES
('smtp_enabled', '0'),
('smtp_host', ''),
('smtp_port', '587'),
('smtp_security', 'tls'),
('smtp_username', ''),
('smtp_password', ''),
('smtp_from_email', ''),
('smtp_from_name', ''),
('smtp_reply_to', ''),
('smtp_timeout', '30'),
('smtp_keepalive', '0'),
('smtp_debug', '0')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);