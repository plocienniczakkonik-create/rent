<?php
// test_dashboard_direct.php - bezpośredni test dashboard z HTML
session_start();
$_SESSION['user_id'] = 3; // Logujemy jako staff

// Minimalna wersja dashboard-staff.php
require_once 'auth/auth.php';
$staff = require_staff();
require_once 'includes/db.php';

$BASE = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';

// DATA
$products = db()->query("SELECT id, name, sku, price, stock, status, category FROM products ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
$productNameById = [];
$productNameBySku = [];
$classLabel = [];
foreach ($products as $p) {
    $productNameById[(int)$p['id']] = (string)$p['name'];
    $productNameBySku[(string)$p['sku']] = (string)$p['name'];
    if (!empty($p['category'])) {
        $code = (string)$p['category'];
        $classLabel[$code] = 'Klasa ' . strtoupper($code);
    }
}

$promos = db()->query("SELECT id, name, code, is_active, scope_type, scope_value, valid_from, valid_to, min_days, discount_type, discount_val FROM promotions ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
$reports = ['revenue_today' => 457.00, 'orders_today' => 6, 'top_product' => 'Toyota Corolla'];
?>
<!DOCTYPE html>
<html>

<head>
    <title>Test Dashboard Staff</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <div class="container-xl mt-4">
        <h1>Test Panel Pracownika</h1>

        <ul class="nav nav-pills mb-3" id="staffTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="tab-products" data-bs-toggle="pill" data-bs-target="#pane-products" type="button" role="tab">Produkty</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-promos" data-bs-toggle="pill" data-bs-target="#pane-promos" type="button" role="tab">Promocje</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-reports" data-bs-toggle="pill" data-bs-target="#pane-reports" type="button" role="tab">Raporty</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-dicts" data-bs-toggle="pill" data-bs-target="#pane-dicts" type="button" role="tab">Słowniki</button>
            </li>
        </ul>

        <div class="tab-content">
            <div class="tab-pane fade show active" id="pane-products" role="tabpanel">
                <?php include 'pages/staff/section-products.php'; ?>
            </div>

            <div class="tab-pane fade" id="pane-promos" role="tabpanel">
                <?php include 'pages/staff/section-promos.php'; ?>
            </div>

            <div class="tab-pane fade" id="pane-reports" role="tabpanel">
                <?php include 'pages/staff/section-reports.php'; ?>
            </div>

            <div class="tab-pane fade" id="pane-dicts" role="tabpanel">
                <?php include 'pages/staff/section-dicts.php'; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>