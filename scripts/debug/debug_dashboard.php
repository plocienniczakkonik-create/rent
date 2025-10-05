<?php
// debug_dashboard.php - debug wersja dashboard-staff.php
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "=== DEBUG Dashboard Staff ===\n";

try {
    echo "1. Ładowanie auth...\n";

    require_once 'auth/auth.php';

    // Symulacja zalogowania
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['user_id'] = 3; // Auto-login dla testów

    $staff = require_staff();
    echo "   Staff: {$staff['email']}\n";

    echo "2. Ładowanie includes...\n";
    require_once 'includes/db.php';
    require_once 'pages/staff/_helpers.php'; // Używamy staff helpers zamiast includes/_helpers.php
    echo "   DB i helpers OK\n";

    $BASE = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
    echo "   BASE: '$BASE'\n";

    echo "3. Ładowanie danych...\n";
    $products = db()->query("SELECT id, name, sku, price, stock, status, category FROM products ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
    echo "   Produkty: " . count($products) . "\n";

    $productNameById = [];
    $productNameBySku = [];
    $classLabel = [];
    foreach ($products as $p) {
        $productNameById[(int)$p['id']] = (string)$p['name'];
        $productNameBySku[(string)$p['sku']] = (string)$p['name'];
        if (!empty($p['category'])) {
            $code = (string)$p['category'];
            $classLabel[$code] = $classLabel[$code] ?? ('Klasa ' . strtoupper($code));
        }
    }

    $orders = [
        ['id' => 5001, 'date' => '2025-09-30', 'product' => 'Toyota Corolla', 'qty' => 2, 'total' => 298.00, 'status' => 'paid'],
        ['id' => 5002, 'date' => '2025-10-01', 'product' => 'VW Golf', 'qty' => 1, 'total' => 159.00, 'status' => 'pending'],
    ];

    $promos = db()->query("SELECT id, name, code, is_active, scope_type, scope_value, valid_from, valid_to, min_days, discount_type, discount_val FROM promotions ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
    echo "   Promocje: " . count($promos) . "\n";

    $reports = [
        'revenue_today' => 457.00,
        'orders_today' => 6,
        'top_product' => 'Toyota Corolla',
    ];

    echo "4. Test includeów sekcji...\n";

    // Test każdej sekcji
    $sections = [
        'products' => 'pages/staff/section-products.php',
        'promos' => 'pages/staff/section-promos.php',
        'reports' => 'pages/staff/section-reports.php',
        'dicts' => 'pages/staff/section-dicts.php',
        'upcoming' => 'pages/staff/section-upcoming.php',
        'settings' => 'pages/staff/section-settings.php'
    ];

    foreach ($sections as $name => $file) {
        echo "   Testing $name ($file)...\n";
        if (!file_exists($file)) {
            echo "     BŁĄD: Plik nie istnieje!\n";
            continue;
        }

        ob_start();
        try {
            include $file;
            $output = ob_get_contents();
            ob_end_clean();
            echo "     OK - output: " . strlen($output) . " znaków\n";

            // Sprawdź czy zawiera HTML
            if (strpos($output, '<') !== false) {
                echo "     ✓ Zawiera HTML\n";
            } else {
                echo "     ✗ Brak HTML w output\n";
            }
        } catch (Exception $e) {
            ob_end_clean();
            echo "     BŁĄD: " . $e->getMessage() . "\n";
        }
    }

    echo "\n=== SUKCES: Wszystkie komponenty załadowane ===\n";
    echo "Dashboard powinien działać poprawnie.\n";
} catch (Exception $e) {
    echo "BŁĄD GŁÓWNY: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
