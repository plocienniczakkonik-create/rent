<?php
require_once dirname(__DIR__, 2) . '/includes/config.php';
require_once dirname(__DIR__, 2) . '/includes/db.php';

$pdo = db();

echo "=== DODAWANIE DANYCH TESTOWYCH DO RAPORTÓW ===\n\n";

try {
    // 1. Sprawdź strukturę tabel
    echo "1. Sprawdzanie struktury tabel:\n";

    $tables = ['products', 'reservations', 'users', 'vehicles'];
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) FROM $table");
            $count = $stmt->fetchColumn();
            echo "   ✅ $table: $count rekordów\n";
        } catch (Exception $e) {
            echo "   ❌ $table: nie istnieje\n";
        }
    }


    echo "\n2. Dodawanie produktów testowych:\n";

    $testProducts = [
        ['Toyota Corolla', 'TOY-COR-001', 'Economy', 180.00, 5],
        ['BMW X3', 'BMW-X3-001', 'Premium', 320.00, 3],
        ['Mercedes Sprinter', 'MER-SPR-001', 'Commercial', 250.00, 2],
        ['Audi A4', 'AUD-A4-001', 'Business', 280.00, 4],
        ['Ford Focus', 'FOR-FOC-001', 'Economy', 170.00, 6],
        ['VW Passat', 'VW-PAS-001', 'Business', 220.00, 3],
        ['Peugeot 508', 'PEU-508-001', 'Premium', 290.00, 2],
        ['Renault Clio', 'REN-CLI-001', 'Economy', 160.00, 4]
    ];

    foreach ($testProducts as $product) {
        try {
            $stmt = $pdo->prepare("
                INSERT IGNORE INTO products (name, sku, category, price, stock, status, created_at) 
                VALUES (?, ?, ?, ?, ?, 'active', NOW())
            ");
            $stmt->execute($product);

            if ($stmt->rowCount() > 0) {
                echo "   ✅ Dodano: {$product[0]}\n";
            } else {
                echo "   ⚠️ Już istnieje: {$product[0]}\n";
            }
        } catch (Exception $e) {
            echo "   ❌ Błąd dla {$product[0]}: " . $e->getMessage() . "\n";
        }
    }

    // Dodawanie egzemplarzy pojazdów do tabeli vehicles
    echo "\n2a. Dodawanie egzemplarzy pojazdów:\n";
    $locations = $pdo->query("SELECT id, name FROM locations WHERE is_active = 1")->fetchAll();
    $productsDb = $pdo->query("SELECT id, name, sku FROM products WHERE status = 'active'")->fetchAll();
    $vinBase = 100000;
    foreach ($productsDb as $product) {
        $numVehicles = rand(2, 5); // Dodaj 2-5 egzemplarzy każdego modelu
        for ($i = 1; $i <= $numVehicles; $i++) {
            $location = $locations[array_rand($locations)];
            $vin = 'VIN' . ($vinBase + $product['id'] * 10 + $i);
            $reg = strtoupper(substr($product['sku'], 0, 3)) . '-' . str_pad($i, 3, '0', STR_PAD_LEFT);
            try {
                $stmt = $pdo->prepare("
                    INSERT IGNORE INTO vehicles (product_id, vin, registration_number, current_location_id, status, created_at)
                    VALUES (?, ?, ?, ?, 'available', NOW())
                ");
                $stmt->execute([
                    $product['id'],
                    $vin,
                    $reg,
                    $location['id']
                ]);
                echo "   ✅ Egzemplarz: {$product['name']} | VIN: $vin | Rej: $reg | Lokalizacja: {$location['name']}\n";
            } catch (Exception $e) {
                echo "   ❌ Błąd egzemplarza {$product['name']}: " . $e->getMessage() . "\n";
            }
        }
    }

    echo "\n3. Dodawanie użytkowników testowych:\n";

    $testUsers = [
        ['jan.kowalski@example.com', 'Jan', 'Kowalski', 'user'],
        ['anna.nowak@example.com', 'Anna', 'Nowak', 'user'],
        ['piotr.wisniewski@example.com', 'Piotr', 'Wiśniewski', 'user'],
        ['maria.brown@example.com', 'Maria', 'Brown', 'user'],
        ['tomasz.krol@example.com', 'Tomasz', 'Król', 'user']
    ];

    foreach ($testUsers as $user) {
        try {
            $stmt = $pdo->prepare("
                INSERT IGNORE INTO users (email, first_name, last_name, role, password_hash, created_at) 
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $hashedPassword = password_hash('test123', PASSWORD_DEFAULT);
            $stmt->execute([$user[0], $user[1], $user[2], $user[3], $hashedPassword]);

            if ($stmt->rowCount() > 0) {
                echo "   ✅ Dodano: {$user[1]} {$user[2]}\n";
            } else {
                echo "   ⚠️ Już istnieje: {$user[1]} {$user[2]}\n";
            }
        } catch (Exception $e) {
            echo "   ❌ Błąd dla {$user[1]} {$user[2]}: " . $e->getMessage() . "\n";
        }
    }

    echo "\n4. Dodawanie rezerwacji testowych (ostatnie 3 miesiące):\n";

    // Pobierz IDs produktów i użytkowników
    $products = $pdo->query("SELECT id, name, price FROM products WHERE status = 'active'")->fetchAll();
    $users = $pdo->query("SELECT id, first_name, last_name FROM users WHERE role = 'user'")->fetchAll();

    if (empty($products) || empty($users)) {
        echo "   ❌ Brak produktów lub użytkowników do tworzenia rezerwacji\n";
    } else {
        // Pobierz egzemplarze pojazdów
        $vehicles = $pdo->query("SELECT v.id, v.product_id, v.vin FROM vehicles v")->fetchAll();
        // Generuj 50 rezerwacji z ostatnich 3 miesięcy
        for ($i = 0; $i < 50; $i++) {
            $product = $products[array_rand($products)];
            $user = $users[array_rand($users)];
            $vehicle = $vehicles[array_rand($vehicles)];

            // Losowa data z ostatnich 90 dni
            $daysAgo = rand(1, 90);
            $createdAt = date('Y-m-d H:i:s', strtotime("-$daysAgo days"));

            // Losowe daty odbioru i zwrotu
            $pickupDays = rand(1, 30);
            $rentalDays = rand(1, 14);
            $pickupDate = date('Y-m-d', strtotime("-$daysAgo days +$pickupDays days"));
            $returnDate = date('Y-m-d', strtotime("$pickupDate +$rentalDays days"));

            // Losowa lokalizacja
            $locations = ['Warszawa Centrum', 'Kraków Główny', 'Gdańsk Port', 'Wrocław Rynek', 'Poznań Plaza'];
            $pickupLocation = $locations[array_rand($locations)];
            $dropoffLocation = $locations[array_rand($locations)];

            // Cenę ustal na podstawie produktu i dni
            $dailyPrice = $product['price'];
            $totalGross = $dailyPrice * $rentalDays;

            // Losowy status
            $statuses = ['confirmed', 'confirmed', 'confirmed', 'completed', 'completed', 'cancelled'];
            $status = $statuses[array_rand($statuses)];

            // SKU rezerwacji
            $sku = 'RES-' . date('Ymd', strtotime($createdAt)) . '-' . str_pad($i + 1, 3, '0', STR_PAD_LEFT);

            try {
                $stmt = $pdo->prepare("
                    INSERT INTO reservations (
                        sku, product_name, vehicle_id,
                        customer_name, customer_email, customer_phone,
                        pickup_location, dropoff_location, pickup_at, return_at,
                        rental_days, total_gross,
                        status, created_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");

                $customerName = $user['first_name'] . ' ' . $user['last_name'];
                $customerEmail = 'test' . $user['id'] . '@example.com';
                $customerPhone = '+48' . rand(100000000, 999999999);

                $stmt->execute([
                    $sku,
                    $product['name'],
                    $vehicle['id'],
                    $customerName,
                    $customerEmail,
                    $customerPhone,
                    $pickupLocation,
                    $dropoffLocation,
                    $pickupDate . ' 10:00:00',
                    $returnDate . ' 18:00:00',
                    $rentalDays,
                    $totalGross,
                    $status,
                    $createdAt
                ]);

                if ($i % 10 === 0) {
                    echo "   ✅ Dodano " . ($i + 1) . " rezerwacji...\n";
                }
            } catch (Exception $e) {
                echo "   ❌ Błąd rezerwacji $i: " . $e->getMessage() . "\n";
            }
        }

        echo "   ✅ Zakończono dodawanie rezerwacji\n";
    }

    echo "\n5. Podsumowanie danych:\n";

    $stats = [
        'products' => $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn(),
        'users' => $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn(),
        'reservations' => $pdo->query("SELECT COUNT(*) FROM reservations")->fetchColumn(),
        'revenue_total' => $pdo->query("SELECT COALESCE(SUM(total_gross), 0) FROM reservations WHERE status != 'cancelled'")->fetchColumn()
    ];

    foreach ($stats as $key => $value) {
        echo "   📊 $key: $value\n";
    }

    echo "\n✅ Dane testowe zostały dodane pomyślnie!\n";
    echo "🔥 Teraz raporty będą wyświetlać prawdziwe dane!\n";
} catch (Exception $e) {
    echo "❌ Błąd: " . $e->getMessage() . "\n";
}
