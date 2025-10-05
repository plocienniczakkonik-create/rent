<?php
// Script to add realistic test data to reservations table
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';

try {
    $pdo = db();
    echo "Adding diverse test data to reservations table...\n";

    // First, let's check what products and locations exist
    $existingProducts = $pdo->query("SELECT DISTINCT product_name FROM reservations WHERE product_name IS NOT NULL")->fetchAll(PDO::FETCH_COLUMN);
    $existingLocations = $pdo->query("SELECT DISTINCT pickup_location FROM reservations WHERE pickup_location IS NOT NULL")->fetchAll(PDO::FETCH_COLUMN);

    // Define realistic product categories with different price ranges
    $productCategories = [
        'Ekonomiczne' => [
            'Volkswagen Golf',
            'Opel Corsa',
            'Ford Fiesta',
            'Renault Clio',
            'Peugeot 208',
            'Skoda Fabia',
            'Hyundai i20',
            'Kia Rio'
        ],
        'Średnia klasa' => [
            'Toyota Corolla',
            'Volkswagen Passat',
            'Ford Focus',
            'Honda Civic',
            'Mazda 3',
            'Skoda Octavia',
            'Opel Astra',
            'Renault Megane'
        ],
        'Premium' => [
            'BMW Seria 3',
            'Mercedes C-Class',
            'Audi A4',
            'Volvo S60',
            'Lexus IS',
            'BMW X3',
            'Mercedes GLC',
            'Audi Q5'
        ],
        'Luksusowe' => [
            'BMW Seria 7',
            'Mercedes S-Class',
            'Audi A8',
            'Jaguar XF',
            'Tesla Model S',
            'Porsche Panamera',
            'Bentley Continental'
        ],
        'SUV/Van' => [
            'Toyota RAV4',
            'Honda CR-V',
            'Ford Kuga',
            'Volkswagen Tiguan',
            'Nissan Qashqai',
            'Ford Transit',
            'Mercedes Sprinter',
            'Volkswagen Crafter'
        ]
    ];

    // Define realistic locations across Poland
    $locations = [
        'Warszawa Centrum',
        'Warszawa Lotnisko Chopina',
        'Warszawa Dworzec Centralny',
        'Kraków Centrum',
        'Kraków Lotnisko Balice',
        'Kraków Dworzec Główny',
        'Gdańsk Centrum',
        'Gdańsk Lotnisko',
        'Gdańsk Port',
        'Wrocław Centrum',
        'Wrocław Lotnisko',
        'Wrocław Dworzec',
        'Poznań Centrum',
        'Poznań Lotnisko Ławica',
        'Poznań Plaza',
        'Katowice Centrum',
        'Katowice Lotnisko Pyrzowice',
        'Katowice Spodek',
        'Łódź Centrum',
        'Łódź Manufaktura',
        'Szczecin Centrum',
        'Lublin Centrum'
    ];

    // Price ranges by category (daily rates in PLN)
    $priceRanges = [
        'Ekonomiczne' => [80, 150],
        'Średnia klasa' => [150, 250],
        'Premium' => [250, 400],
        'Luksusowe' => [400, 800],
        'SUV/Van' => [200, 350]
    ];

    $statuses = ['confirmed', 'pending', 'cancelled'];
    $statusWeights = [70, 25, 5]; // 70% confirmed, 25% pending, 5% cancelled

    $customers = [
        ['Jan', 'Kowalski', 'jan.kowalski@email.com', '+48 500 123 456'],
        ['Anna', 'Nowak', 'anna.nowak@email.com', '+48 600 234 567'],
        ['Piotr', 'Wiśniewski', 'piotr.wisniewski@email.com', '+48 700 345 678'],
        ['Maria', 'Wójcik', 'maria.wojcik@email.com', '+48 800 456 789'],
        ['Tomasz', 'Kowalczyk', 'tomasz.kowalczyk@email.com', '+48 900 567 890'],
        ['Katarzyna', 'Kamińska', 'katarzyna.kaminska@email.com', '+48 501 678 901'],
        ['Michał', 'Lewandowski', 'michal.lewandowski@email.com', '+48 602 789 012'],
        ['Magdalena', 'Zielińska', 'magdalena.zielinska@email.com', '+48 703 890 123'],
        ['Paweł', 'Szymański', 'pawel.szymanski@email.com', '+48 804 901 234'],
        ['Agnieszka', 'Woźniak', 'agnieszka.wozniak@email.com', '+48 905 012 345'],
        ['Robert', 'Dąbrowski', 'robert.dabrowski@email.com', '+48 506 123 456'],
        ['Joanna', 'Górska', 'joanna.gorska@email.com', '+48 607 234 567'],
        ['Marcin', 'Kozłowski', 'marcin.kozlowski@email.com', '+48 708 345 678'],
        ['Beata', 'Jankowska', 'beata.jankowska@email.com', '+48 809 456 789'],
        ['Krzysztof', 'Mazur', 'krzysztof.mazur@email.com', '+48 910 567 890']
    ];

    $reservationCount = 0;
    $totalRevenue = 0;

    // Generate reservations for the last 6 months with realistic distribution
    for ($i = 0; $i < 150; $i++) {
        // Weight recent dates more heavily
        $daysAgo = rand(1, 180);
        if ($daysAgo <= 30) {
            $weight = 40; // 40% in last month
        } elseif ($daysAgo <= 90) {
            $weight = 35; // 35% in last 2-3 months
        } else {
            $weight = 25; // 25% in 3-6 months ago
        }

        if (rand(1, 100) > $weight) {
            continue; // Skip this iteration based on weight
        }

        // Select category and product
        $category = array_rand($productCategories);
        $product = $productCategories[$category][array_rand($productCategories[$category])];

        // Select location
        $pickupLocation = $locations[array_rand($locations)];
        $returnLocation = (rand(1, 100) <= 85) ? $pickupLocation : $locations[array_rand($locations)]; // 85% same location

        // Select customer
        $customer = $customers[array_rand($customers)];

        // Generate realistic dates
        $createdAt = date('Y-m-d H:i:s', strtotime("-{$daysAgo} days"));
        $pickupDaysFromCreation = rand(1, 14); // 1-14 days from booking to pickup
        $pickupDate = date('Y-m-d', strtotime($createdAt . " +{$pickupDaysFromCreation} days"));
        $rentalDays = rand(1, 14); // 1-14 days rental
        $returnDate = date('Y-m-d', strtotime($pickupDate . " +{$rentalDays} days"));

        // Calculate price based on category and duration
        $priceRange = $priceRanges[$category];
        $dailyRate = rand($priceRange[0], $priceRange[1]);
        $totalPrice = $dailyRate * $rentalDays;

        // Add seasonal adjustments
        $month = date('n', strtotime($pickupDate));
        if (in_array($month, [7, 8])) { // Summer premium
            $totalPrice *= 1.2;
        } elseif (in_array($month, [12, 1])) { // Winter premium
            $totalPrice *= 1.1;
        } elseif (in_array($month, [11, 2, 3])) { // Low season discount
            $totalPrice *= 0.9;
        }

        // Select status with weighted probability
        $statusRand = rand(1, 100);
        if ($statusRand <= $statusWeights[0]) {
            $status = $statuses[0]; // confirmed
        } elseif ($statusRand <= $statusWeights[0] + $statusWeights[1]) {
            $status = $statuses[1]; // pending
        } else {
            $status = $statuses[2]; // cancelled
        }

        // For cancelled reservations, reduce price to 0 or small cancellation fee
        if ($status === 'cancelled') {
            $totalPrice = rand(0, 50); // Cancellation fee
        }

        $totalPrice = round($totalPrice, 2);

        $stmt = $pdo->prepare("
            INSERT INTO reservations (
                customer_name, customer_email, customer_phone,
                product_name, pickup_location, dropoff_location,
                pickup_at, return_at, total_gross, status, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $customer[0] . ' ' . $customer[1],
            $customer[2],
            $customer[3],
            $product,
            $pickupLocation,
            $returnLocation,
            $pickupDate . ' 10:00:00',
            $returnDate . ' 18:00:00',
            $totalPrice,
            $status,
            $createdAt
        ]);

        $reservationCount++;
        if ($status === 'confirmed') {
            $totalRevenue += $totalPrice;
        }

        if ($reservationCount % 20 == 0) {
            echo "Generated {$reservationCount} reservations...\n";
        }
    }

    echo "\n=== DATA GENERATION COMPLETE ===\n";
    echo "Total reservations added: {$reservationCount}\n";
    echo "Total revenue from confirmed reservations: " . number_format($totalRevenue, 2) . " PLN\n";

    // Show statistics
    $stats = $pdo->query("
        SELECT 
            status,
            COUNT(*) as count,
            SUM(total_gross) as revenue
        FROM reservations 
        GROUP BY status
        ORDER BY count DESC
    ")->fetchAll();

    echo "\n=== RESERVATION STATISTICS ===\n";
    foreach ($stats as $stat) {
        echo "{$stat['status']}: {$stat['count']} reservations, " . number_format($stat['revenue'], 2) . " PLN\n";
    }

    // Show top products
    $topProducts = $pdo->query("
        SELECT 
            product_name,
            COUNT(*) as reservations,
            SUM(total_gross) as revenue
        FROM reservations 
        WHERE status = 'confirmed'
        GROUP BY product_name
        ORDER BY reservations DESC
        LIMIT 10
    ")->fetchAll();

    echo "\n=== TOP 10 PRODUCTS ===\n";
    foreach ($topProducts as $product) {
        echo "{$product['product_name']}: {$product['reservations']} reservations, " . number_format($product['revenue'], 2) . " PLN\n";
    }

    // Show monthly revenue
    $monthlyRevenue = $pdo->query("
        SELECT 
            DATE_FORMAT(pickup_at, '%Y-%m') as month,
            COUNT(*) as reservations,
            SUM(total_gross) as revenue
        FROM reservations 
        WHERE status = 'confirmed'
        AND pickup_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(pickup_at, '%Y-%m')
        ORDER BY month DESC
    ")->fetchAll();

    echo "\n=== MONTHLY REVENUE (Last 6 months) ===\n";
    foreach ($monthlyRevenue as $month) {
        echo "{$month['month']}: {$month['reservations']} reservations, " . number_format($month['revenue'], 2) . " PLN\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
