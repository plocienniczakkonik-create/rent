DELETE FROM user_consents WHERE source = 'registration' OR source = 'register_form';
INSERT INTO user_consents (user_id, consent_type, consent_text, given_at, ip_address, source) VALUES
(1, 'privacy_policy', 'Akceptacja polityki prywatności przy rejestracji', NOW(), '127.0.0.1', 'rejestracja');
