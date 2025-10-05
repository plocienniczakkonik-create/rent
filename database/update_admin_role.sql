-- Dodaj rolę 'admin' do ENUM role w tabeli users
ALTER TABLE users MODIFY COLUMN role ENUM('client','staff','admin') DEFAULT 'client';

-- Ustaw użytkownika plocienniczak.konik@gmail.com jako admin
UPDATE users SET role = 'admin', first_name = 'Admin', last_name = 'Administrator' 
WHERE email = 'plocienniczak.konik@gmail.com';

-- Ustaw aktywność na 1 dla tego użytkownika
UPDATE users SET is_active = 1 WHERE email = 'plocienniczak.konik@gmail.com';