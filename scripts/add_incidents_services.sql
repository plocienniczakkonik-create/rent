-- Dodawanie danych serwisów i incydentów dla nowych pojazdów

-- SERWISY dla nowych pojazdów
-- Toyota Corolla (TOY-COR-001)
INSERT INTO vehicle_services (vehicle_id, service_date, odometer_km, workshop_name, invoice_no, cost_total, issues_found, actions_taken, notes) VALUES
(50, '2025-08-15', 44500, 'Toyota ASO', 'FV/2025/50/1/0001', 850.00, 'Wymiana oleju i filtrów', 'Wykonano przegląd okresowy', 'Przegląd 45tys km'),
(50, '2025-06-20', 42000, 'Toyota ASO', 'FV/2025/50/2/0002', 320.00, 'Kontrola płynów', 'Uzupełniono płyny', 'Przegląd średni'),
(51, '2025-09-10', 37500, 'Toyota ASO', 'FV/2025/51/1/0003', 890.00, 'Wymiana oleju, filtr powietrza', 'Wykonano przegląd', 'Przegląd okresowy'),
(52, '2025-07-25', 51000, 'Toyota ASO', 'FV/2025/52/1/0004', 1250.00, 'Wymiana klocków hamulcowych', 'Wymieniono komplet klocków', 'Serwis hamulców');

-- BMW X3 (BMW-X3-001)
INSERT INTO vehicle_services (vehicle_id, service_date, odometer_km, workshop_name, invoice_no, cost_total, issues_found, actions_taken, notes) VALUES
(53, '2025-09-05', 31500, 'BMW ASO Premium', 'FV/2025/53/1/0005', 1450.00, 'Przegląd inspekcyjny', 'Wykonano inspekcję', 'Inspekcja I'),
(54, '2025-08-20', 27800, 'BMW ASO Premium', 'FV/2025/54/1/0006', 980.00, 'Wymiana oleju silnika', 'Wymieniono olej i filtry', 'Serwis olejowy'),
(55, '2025-09-15', 40500, 'BMW Serwis', 'FV/2025/55/1/0007', 2250.00, 'Naprawa układu chłodzenia', 'Wymieniono termostat', 'Awaria chłodzenia'),
(56, '2025-08-10', 34200, 'BMW ASO Premium', 'FV/2025/56/1/0008', 1100.00, 'Wymiana oleju i filtrów', 'Przegląd standardowy', 'Serwis okresowy');

-- Mercedes Sprinter (MER-SPR-001)
INSERT INTO vehicle_services (vehicle_id, service_date, odometer_km, workshop_name, invoice_no, cost_total, issues_found, actions_taken, notes) VALUES
(57, '2025-09-20', 77500, 'Mercedes Serwis', 'FV/2025/57/1/0009', 1850.00, 'Wymiana oleju, filtry', 'Przegląd komercyjny', 'Serwis dostawczy'),
(58, '2025-08-30', 64200, 'Mercedes Serwis', 'FV/2025/58/1/0010', 2100.00, 'Wymiana klocków i tarcz', 'Serwis hamulców', 'Hamulce przednie'),
(59, '2025-07-15', 88500, 'Mercedes Serwis', 'FV/2025/59/1/0011', 3200.00, 'Naprawa skrzyni biegów', 'Regeneracja skrzyni', 'Awaria skrzyni');

-- Audi A4 (AUD-A4-001)
INSERT INTO vehicle_services (vehicle_id, service_date, odometer_km, workshop_name, invoice_no, cost_total, issues_found, actions_taken, notes) VALUES
(60, '2025-09-12', 24500, 'Audi ASO', 'FV/2025/60/1/0012', 1200.00, 'Pierwszy przegląd', 'Przegląd gwarancyjny', 'Przegląd 25tys km'),
(61, '2025-08-25', 30500, 'Audi ASO', 'FV/2025/61/1/0013', 850.00, 'Wymiana oleju', 'Serwis olejowy', 'Wymiana oleju'),
(62, '2025-09-18', 28800, 'Audi ASO', 'FV/2025/62/1/0014', 950.00, 'Kontrola zawieszenia', 'Regulacja geometrii', 'Serwis zawieszenia'),
(63, '2025-08-05', 21500, 'Audi ASO', 'FV/2025/63/1/0015', 750.00, 'Wymiana filtrów', 'Wymiana filtrów powietrza i kabinowego', 'Serwis filtrów');

-- Ford Focus (FOR-FOC-001)
INSERT INTO vehicle_services (vehicle_id, service_date, odometer_km, workshop_name, invoice_no, cost_total, issues_found, actions_taken, notes) VALUES
(70, '2025-08-28', 44200, 'Ford Serwis', 'FV/2025/70/1/0016', 780.00, 'Wymiana oleju i filtrów', 'Przegląd okresowy', 'Serwis 45tys km'),
(71, '2025-09-08', 51500, 'Ford Serwis', 'FV/2025/71/1/0017', 1100.00, 'Wymiana świec zapłonowych', 'Wymiana świec i cewek', 'Serwis zapłonu'),
(72, '2025-07-20', 37200, 'Ford Serwis', 'FV/2025/72/1/0018', 650.00, 'Kontrola układu hamulcowego', 'Wymiana płynu hamulcowego', 'Serwis hamulców'),
(73, '2025-09-25', 66500, 'Ford Serwis Specjalistyczny', 'FV/2025/73/1/0019', 1850.00, 'Naprawa skrzyni biegów', 'Wymiana sprzęgła', 'Awaria sprzęgła');

-- Peugeot 508 (PEU-508-001)  
INSERT INTO vehicle_services (vehicle_id, service_date, odometer_km, workshop_name, invoice_no, cost_total, issues_found, actions_taken, notes) VALUES
(74, '2025-09-02', 31800, 'Peugeot Serwis', 'FV/2025/74/1/0020', 920.00, 'Wymiana oleju silnika', 'Przegląd standardowy', 'Serwis 30tys km'),
(75, '2025-08-15', 27500, 'Peugeot Serwis', 'FV/2025/75/1/0021', 680.00, 'Kontrola zawieszenia', 'Regulacja amortyzatorów', 'Serwis zawieszenia'),
(76, '2025-09-20', 40200, 'Peugeot Serwis', 'FV/2025/76/1/0022', 1350.00, 'Wymiana rozrządu', 'Wymiana paska rozrządu', 'Serwis rozrządu'),
(77, '2025-07-10', 54200, 'Peugeot Autoryzowany', 'FV/2025/77/1/0023', 2100.00, 'Naprawa klimatyzacji', 'Wymiana sprężarki', 'Awaria klimy');

-- Renault Clio (REN-CLI-001)
INSERT INTO vehicle_services (vehicle_id, service_date, odometer_km, workshop_name, invoice_no, cost_total, issues_found, actions_taken, notes) VALUES
(78, '2025-08-22', 27500, 'Renault Serwis', 'FV/2025/78/1/0024', 580.00, 'Wymiana oleju', 'Serwis olejowy', 'Wymiana oleju'),
(79, '2025-09-10', 18800, 'Renault Serwis', 'FV/2025/79/1/0025', 450.00, 'Pierwszy przegląd', 'Przegląd gwarancyjny', 'Przegląd 20tys km'),
(80, '2025-07-30', 32500, 'Renault Serwis', 'FV/2025/80/1/0026', 850.00, 'Wymiana klocków hamulcowych', 'Serwis hamulców', 'Hamulce tylne'),
(81, '2025-09-28', 46200, 'Renault Specjalistyczny', 'FV/2025/81/1/0027', 1750.00, 'Naprawa układu elektrycznego', 'Wymiana alternatora', 'Awaria ładowania');

-- VW Passat (VW-PAS-001)
INSERT INTO vehicle_services (vehicle_id, service_date, odometer_km, workshop_name, invoice_no, cost_total, issues_found, actions_taken, notes) VALUES
(82, '2025-08-18', 41500, 'Volkswagen Serwis', 'FV/2025/82/1/0028', 1150.00, 'Przegląd inspekcyjny', 'Inspekcja standardowa', 'Przegląd okresowy'),
(83, '2025-09-05', 35800, 'Volkswagen Serwis', 'FV/2025/83/1/0029', 890.00, 'Wymiana oleju i filtrów', 'Serwis olejowy', 'Serwis 40tys km'),
(84, '2025-07-25', 28200, 'Volkswagen Serwis', 'FV/2025/84/1/0030', 750.00, 'Kontrola układu wydechowego', 'Wymiana tłumika', 'Serwis wydechu'),
(85, '2025-09-15', 57200, 'VW Specjalistyczny', 'FV/2025/85/1/0031', 2850.00, 'Naprawa turbosprężarki', 'Regeneracja turbo', 'Awaria turbo');

-- INCYDENTY dla nowych pojazdów
-- Toyota Corolla (TOY-COR-001) - tylko drobne incydenty
INSERT INTO vehicle_incidents (vehicle_id, incident_date, driver_name, location, damage_desc, fault, police_called, repair_cost, insurance_claim_no, notes) VALUES
(51, '2025-07-10', 'Jan Kowalski', 'Warszawa Centrum', 'Rysa na zderzaku - parking', 'our', 0, 450.00, 'CLM-2025-51-001', 'Drobna szkoda parkingowa'),
(52, '2025-08-20', 'Anna Nowak', 'Kraków', 'Pęknięte lusterko boczne', 'other', 0, 280.00, 'CLM-2025-52-001', 'Uszkodzenie przez innych');

-- BMW X3 (BMW-X3-001) - średnie incydenty  
INSERT INTO vehicle_incidents (vehicle_id, incident_date, driver_name, location, damage_desc, fault, police_called, repair_cost, insurance_claim_no, notes) VALUES
(53, '2025-08-15', 'Piotr Wiśniewski', 'Gdańsk', 'Stłuczka na parkingu - wgniecenie drzwi', 'shared', 1, 1850.00, 'CLM-2025-53-001', 'Kolizja na parkingu'),
(55, '2025-09-02', 'Katarzyna Zielińska', 'Wrocław', 'Uszkodzenie felgi - wybój na drodze', 'unknown', 0, 950.00, NULL, 'Szkoda drogowa'),
(56, '2025-07-25', 'Marek Lewandowski', 'Poznań', 'Zarysowana maska - grad', 'unknown', 0, 1200.00, 'CLM-2025-56-001', 'Szkoda pogodowa');

-- Mercedes Sprinter (MER-SPR-001) - incydenty typowe dla aut dostawczych
INSERT INTO vehicle_incidents (vehicle_id, incident_date, driver_name, location, damage_desc, fault, police_called, repair_cost, insurance_claim_no, notes) VALUES
(57, '2025-08-10', 'Tomasz Kaczmarek', 'Warszawa Port', 'Kolizja z barierką - uszkodzenie zderzaka', 'our', 1, 2100.00, 'CLM-2025-57-001', 'Kolizja podczas rozładunku'),
(58, '2025-07-20', 'Michał Szymański', 'Gdańsk Port', 'Uszkodzenie boku podczas rozładunku', 'our', 0, 1650.00, 'CLM-2025-58-001', 'Szkoda operacyjna'),
(59, '2025-09-05', 'Rafał Woźniak', 'Budowa A1', 'Przebita opona - gwóźdź na budowie', 'unknown', 0, 320.00, NULL, 'Szkoda na budowie');

-- Audi A4 (AUD-A4-001) - incydenty premium
INSERT INTO vehicle_incidents (vehicle_id, incident_date, driver_name, location, damage_desc, fault, police_called, repair_cost, insurance_claim_no, notes) VALUES
(60, '2025-08-28', 'Agnieszka Kamińska', 'Warszawa Mokotów', 'Rysa na masce - parkowanie', 'our', 0, 580.00, NULL, 'Drobna szkoda'),
(62, '2025-09-12', 'Łukasz Dąbrowski', 'Kraków Nowa Huta', 'Uderzenie w słup - lusterko i drzwi', 'our', 1, 2200.00, 'CLM-2025-62-001', 'Kolizja z infrastrukturą');

-- Ford Focus (FOR-FOC-001) - typowe incydenty miejskie
INSERT INTO vehicle_incidents (vehicle_id, incident_date, driver_name, location, damage_desc, fault, police_called, repair_cost, insurance_claim_no, notes) VALUES
(70, '2025-07-30', 'Monika Jankowska', 'Wrocław Centrum', 'Uszkodzenie zderzaka - parking', 'shared', 0, 680.00, 'CLM-2025-70-001', 'Stłuczka parkingowa'),
(71, '2025-08-18', 'Krzysztof Mazur', 'Poznań', 'Stłuczka z tyłu - sygnalizacja', 'other', 1, 1450.00, 'CLM-2025-71-001', 'Najechanie z tyłu'),
(73, '2025-09-10', 'Ewa Król', 'Trasa A4', 'Pęknięta szyba przednia - kamień z drogi', 'unknown', 0, 850.00, NULL, 'Szkoda drogowa');

-- Peugeot 508 (PEU-508-001) - incydenty sedan
INSERT INTO vehicle_incidents (vehicle_id, incident_date, driver_name, location, damage_desc, fault, police_called, repair_cost, insurance_claim_no, notes) VALUES
(74, '2025-08-05', 'Paweł Wójcik', 'Gdańsk Stare Miasto', 'Zarysowany bok - wąska ulica', 'unknown', 0, 1100.00, 'CLM-2025-74-001', 'Szkoda w centrum'),
(76, '2025-09-22', 'Magdalena Sikora', 'Warszawa Praga', 'Kolizja boczna - skrzyżowanie', 'shared', 1, 2850.00, 'CLM-2025-76-001', 'Kolizja na skrzyżowaniu'),
(77, '2025-07-15', 'Adam Michalski', 'Kraków Podgórze', 'Uszkodzenie klapy bagażnika', 'other', 0, 920.00, 'CLM-2025-77-001', 'Uszkodzenie przez innych');

-- Renault Clio (REN-CLI-001) - drobne incydenty miejskie  
INSERT INTO vehicle_incidents (vehicle_id, incident_date, driver_name, location, damage_desc, fault, police_called, repair_cost, insurance_claim_no, notes) VALUES
(78, '2025-08-12', 'Natalia Pawlak', 'Wrocław Krzyki', 'Rysa na drzwiach - parking', 'our', 0, 380.00, NULL, 'Drobna rysa'),
(80, '2025-09-08', 'Bartosz Krawczyk', 'Poznań Jeżyce', 'Uszkodzenie lusterka - wąska droga', 'unknown', 0, 250.00, NULL, 'Stłuczone lusterko'),
(81, '2025-09-25', 'Izabela Adamczyk', 'Gdańsk Wrzeszcz', 'Przebita opona - gwóźdź', 'unknown', 0, 180.00, NULL, 'Przebicie opony');

-- VW Passat (VW-PAS-001) - incydenty biznesowe
INSERT INTO vehicle_incidents (vehicle_id, incident_date, driver_name, location, damage_desc, fault, police_called, repair_cost, insurance_claim_no, notes) VALUES
(82, '2025-07-28', 'Grzegorz Urbański', 'Autostrada A2', 'Kolizja na autostradzie - zderzak i maska', 'other', 1, 3200.00, 'CLM-2025-82-001', 'Kolizja autostradowa'),
(83, '2025-08-20', 'Joanna Błaszczyk', 'Warszawa Bielany', 'Uszkodzenie felgi - dziura w jezdni', 'unknown', 0, 650.00, NULL, 'Szkoda infrastrukturalna'),
(85, '2025-09-18', 'Sebastian Marciniak', 'Kraków Bronowice', 'Stłuczka na parkingu - drzwi i próg', 'shared', 0, 1850.00, 'CLM-2025-85-001', 'Kolizja parkingowa');