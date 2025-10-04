<?php
// dashboard_minimal.php - minimalna wersja bez partial'ów
session_start();

// Auto-login jako staff
require_once 'includes/db.php';
$stmt = db()->prepare('SELECT id FROM users WHERE email = ? AND role = ?');
$stmt->execute(['test2@example.com', 'staff']);
$user_id = $stmt->fetchColumn();
if ($user_id) {
    $_SESSION['user_id'] = $user_id;
}

// Ładujemy tylko niezbędne rzeczy z dashboard-staff.php
require_once 'auth/auth.php';
$staff = require_staff();

require_once 'pages/staff/_helpers.php';

$BASE = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';

// === DATA: z dashboard-staff.php ===
$products = db()->query("
  SELECT id, name, sku, price, stock, status, category
  FROM products
  ORDER BY id DESC
")->fetchAll(PDO::FETCH_ASSOC);

$productNameById  = [];
$productNameBySku = [];
$classLabel       = [];
foreach ($products as $p) {
    $productNameById[(int)$p['id']]        = (string)$p['name'];
    $productNameBySku[(string)$p['sku']]   = (string)$p['name'];
    if (!empty($p['category'])) {
        $code = (string)$p['category'];
        $classLabel[$code] = $classLabel[$code] ?? ('Klasa ' . strtoupper($code));
    }
}

$orders = [
    ['id' => 5001, 'date' => '2025-09-30', 'product' => 'Toyota Corolla', 'qty' => 2, 'total' => 298.00, 'status' => 'paid'],
    ['id' => 5002, 'date' => '2025-10-01', 'product' => 'VW Golf',        'qty' => 1, 'total' => 159.00, 'status' => 'pending'],
];

$promos = db()->query("
  SELECT id, name, code, is_active, scope_type, scope_value,
         valid_from, valid_to, min_days, discount_type, discount_val
  FROM promotions
  ORDER BY id DESC
")->fetchAll(PDO::FETCH_ASSOC);

$reports = [
    'revenue_today' => 457.00,
    'orders_today'  => 6,
    'top_product'   => 'Toyota Corolla',
];
?>
<!DOCTYPE html>
<html lang="pl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Pracownika - Minimal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <main class="container-xl staff-main" style="padding-top: 72px;">
        <div class="w-100" style="max-width: 1200px; margin: 0 auto;">
            <div class="row g-3">
                <div class="col-12">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <div>
                            <h1 class="h4 mb-0">Panel pracownika (MINIMAL)</h1>
                            <div class="text-muted small">
                                <?= htmlspecialchars($staff['first_name'] ?? '') . ' ' . htmlspecialchars($staff['last_name'] ?? '') ?>
                                <?= !empty($staff['job_title']) ? ' • ' . htmlspecialchars($staff['job_title']) : '' ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12">
                    <ul class="nav nav-pills gap-2" id="staffTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="tab-products" data-bs-toggle="pill" data-bs-target="#pane-products" type="button" role="tab">Produkty</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="tab-vehicles" data-bs-toggle="pill" data-bs-target="#pane-vehicles" type="button" role="tab">Pojazdy</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="tab-orders" data-bs-toggle="pill" data-bs-target="#pane-orders" type="button" role="tab">Zamówienia</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="tab-promos" data-bs-toggle="pill" data-bs-target="#pane-promos" type="button" role="tab">Promocje</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="tab-upcoming" data-bs-toggle="pill" data-bs-target="#pane-upcoming" type="button" role="tab">Najbliższe terminy</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="tab-reports" data-bs-toggle="pill" data-bs-target="#pane-reports" type="button" role="tab">Raporty</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="tab-dicts" data-bs-toggle="pill" data-bs-target="#pane-dicts" type="button" role="tab">Słowniki</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="tab-settings" data-bs-toggle="pill" data-bs-target="#pane-settings" type="button" role="tab">Ustawienia</button>
                        </li>
                    </ul>
                </div>

                <div class="col-12">
                    <div class="tab-content">

                        <div class="tab-pane fade show active" id="pane-products" role="tabpanel" aria-labelledby="tab-products">
                            <?php include 'pages/staff/section-products.php'; ?>
                        </div>

                        <div class="tab-pane fade" id="pane-vehicles" role="tabpanel" aria-labelledby="tab-vehicles">
                            <?php include 'pages/staff/section-vehicles.php'; ?>
                        </div>

                        <div class="tab-pane fade" id="pane-orders" role="tabpanel" aria-labelledby="tab-orders">
                            <?php include 'pages/staff/section-orders.php'; ?>
                        </div>

                        <div class="tab-pane fade" id="pane-promos" role="tabpanel" aria-labelledby="tab-promos">
                            <?php include 'pages/staff/section-promos.php'; ?>
                        </div>

                        <div class="tab-pane fade" id="pane-upcoming" role="tabpanel" aria-labelledby="tab-upcoming">
                            <?php include 'pages/staff/section-upcoming.php'; ?>
                        </div>

                        <div class="tab-pane fade" id="pane-reports" role="tabpanel" aria-labelledby="tab-reports">
                            <?php include 'pages/staff/section-reports.php'; ?>
                        </div>

                        <div class="tab-pane fade" id="pane-dicts" role="tabpanel" aria-labelledby="tab-dicts">
                            <?php include 'pages/staff/section-dicts.php'; ?>
                        </div>

                        <div class="tab-pane fade" id="pane-settings" role="tabpanel" aria-labelledby="tab-settings">
                            <?php include 'pages/staff/section-settings.php'; ?>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var hash = window.location.hash;
            if (hash) {
                var trigger = document.querySelector('button[data-bs-target="' + hash + '"]');
                if (trigger) {
                    new bootstrap.Tab(trigger).show();
                }
            }
            document.querySelectorAll('button[data-bs-toggle="pill"]').forEach(function(btn) {
                btn.addEventListener('shown.bs.tab', function(e) {
                    var target = e.target.getAttribute('data-bs-target');
                    if (target) history.replaceState(null, '', target);
                });
            });
        });
    </script>
</body>

</html>