-- Dodawanie pozostałych egzemplarzy pojazdów do bazy danych
-- (Toyota Corolla, BMW X3, Mercedes Sprinter i Audi A4 już dodane)

-- Ford Focus (FOR-FOC-001) - product_id = 10
INSERT INTO vehicles (product_id, registration_number, vin, mileage, status, notes, current_location_id) VALUES
(10, 'WE56789', 'WF0AXXWPAABX123456', 42000, 'available', 'Ford Focus - egzemplarz 1', 1),
(10, 'WE56790', 'WF0AXXWPAABX123457', 39000, 'available', 'Ford Focus - egzemplarz 2', 2),
(10, 'WE56791', 'WF0AXXWPAABX123458', 48000, 'available', 'Ford Focus - egzemplarz 3', 5);

-- VW Passat (VW-PAS-001) - product_id = 11
INSERT INTO vehicles (product_id, registration_number, vin, mileage, status, notes, current_location_id) VALUES
(11, 'WE67890', 'WVWZZZ3BZAE123456', 55000, 'available', 'VW Passat - egzemplarz 1', 1),
(11, 'WE67891', 'WVWZZZ3BZAE123457', 47000, 'available', 'VW Passat - egzemplarz 2', 3),
(11, 'WE67892', 'WVWZZZ3BZAE123458', 62000, 'available', 'VW Passat - egzemplarz 3', 4),
(11, 'WE67893', 'WVWZZZ3BZAE123459', 51000, 'maintenance', 'VW Passat - egzemplarz 4 w serwisie', 2);

-- Peugeot 508 (PEU-508-001) - product_id = 12
INSERT INTO vehicles (product_id, registration_number, vin, mileage, status, notes, current_location_id) VALUES
(12, 'WE78901', 'VF38CRHA1JL123456', 33000, 'available', 'Peugeot 508 - egzemplarz 1', 1),
(12, 'WE78902', 'VF38CRHA1JL123457', 27000, 'available', 'Peugeot 508 - egzemplarz 2', 2),
(12, 'WE78903', 'VF38CRHA1JL123458', 35000, 'available', 'Peugeot 508 - egzemplarz 3', 5);

-- Renault Clio (REN-CLI-001) - product_id = 13
INSERT INTO vehicles (product_id, registration_number, vin, mileage, status, notes, current_location_id) VALUES
(13, 'WE89012', 'VF1RJ0B0559123456', 28000, 'available', 'Renault Clio - egzemplarz 1', 1),
(13, 'WE89013', 'VF1RJ0B0559123457', 31000, 'available', 'Renault Clio - egzemplarz 2', 3),
(13, 'WE89014', 'VF1RJ0B0559123458', 25000, 'available', 'Renault Clio - egzemplarz 3', 4),
(13, 'WE89015', 'VF1RJ0B0559123459', 33000, 'booked', 'Renault Clio - egzemplarz 4 zarezerwowany', 2);