<?php
// /pages/dashboard-staff.php
require_once dirname(__DIR__) . '/auth/auth.php';
$staff = require_staff();

require_once dirname(__DIR__) . '/partials/head.php';
require_once dirname(__DIR__) . '/partials/header.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once __DIR__ . '/staff/_helpers.php';

$BASE = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';

// === DATA: Produkty (pobieramy też category) ===
$products = db()->query("
  SELECT id, name, sku, price, stock, status, category
  FROM products
  ORDER BY id DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Mapy do wyświetlania nazw
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

// === DATA: Zamówienia (mock) ===
$orders = [
    ['id' => 5001, 'date' => '2025-09-30', 'product' => 'Toyota Corolla', 'qty' => 2, 'total' => 298.00, 'status' => 'paid'],
    ['id' => 5002, 'date' => '2025-10-01', 'product' => 'VW Golf',        'qty' => 1, 'total' => 159.00, 'status' => 'pending'],
];

// === DATA: Promocje ===
$promos = db()->query("
  SELECT id, name, code, is_active, scope_type, scope_value,
         valid_from, valid_to, min_days, discount_type, discount_val
  FROM promotions
  ORDER BY id DESC
")->fetchAll(PDO::FETCH_ASSOC);

// === DATA: Raporty (mock) ===
$reports = [
    'revenue_today' => 457.00,
    'orders_today'  => 6,
    'top_product'   => 'Toyota Corolla',
];
?>
<main class="container-xl py-4 d-flex align-items-center justify-content-center"
    style="min-height: calc(100vh - 72px); padding-top: 72px;">
    <div class="w-100" style="max-width: 1200px; margin: 0 auto;">
        <div class="row g-3">
            <div class="col-12">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div>
                        <h1 class="h4 mb-0">Panel pracownika</h1>
                        <div class="text-muted small">
                            <?= htmlspecialchars($staff['first_name'] ?? '') . ' ' . htmlspecialchars($staff['last_name'] ?? '') ?>
                            <?= !empty($staff['job_title']) ? ' • ' . htmlspecialchars($staff['job_title']) : '' ?>
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <a class="btn btn-outline-secondary btn-sm" href="<?= $BASE ?>/index.php">Podgląd strony</a>
                        <a class="btn btn-primary btn-sm" href="pages/product-form.php" id="product-new">+ Dodaj produkt</a>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <ul class="nav nav-pills gap-2" id="staffTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="tab-products" data-bs-toggle="pill" data-bs-target="#pane-products" type="button" role="tab">Produkty</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tab-orders" data-bs-toggle="pill" data-bs-target="#pane-orders" type="button" role="tab">Zamówienia</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tab-promos" data-bs-toggle="pill" data-bs-target="#pane-promos" type="button" role="tab">Promocje</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tab-reports" data-bs-toggle="pill" data-bs-target="#pane-reports" type="button" role="tab">Raporty</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tab-settings" data-bs-toggle="pill" data-bs-target="#pane-settings" type="button" role="tab">Ustawienia</button>
                    </li>
                </ul>
            </div>

            <div class="col-12">
                <div class="tab-content">

                    <div class="tab-pane fade show active" id="pane-products" role="tabpanel" aria-labelledby="tab-products">
                        <?php include __DIR__ . '/staff/section-products.php'; ?>
                    </div>

                    <div class="tab-pane fade" id="pane-orders" role="tabpanel" aria-labelledby="tab-orders">
                        <?php include __DIR__ . '/staff/section-orders.php'; ?>
                    </div>

                    <div class="tab-pane fade" id="pane-promos" role="tabpanel" aria-labelledby="tab-promos">
                        <?php include __DIR__ . '/staff/section-promos.php'; ?>
                    </div>

                    <div class="tab-pane fade" id="pane-reports" role="tabpanel" aria-labelledby="tab-reports">
                        <?php include __DIR__ . '/staff/section-reports.php'; ?>
                    </div>

                    <div class="tab-pane fade" id="pane-settings" role="tabpanel" aria-labelledby="tab-settings">
                        <?php include __DIR__ . '/staff/section-settings.php'; ?>
                    </div>

                </div>
            </div>
        </div>
    </div>
</main>

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

<?php require_once dirname(__DIR__) . '/partials/footer.php'; ?>