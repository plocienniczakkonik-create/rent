<?php
// Debug version of dashboard-staff.php that shows what's in each section
require_once __DIR__ . '/auth/auth.php';
$staff = require_staff();

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/pages/staff/_helpers.php';

$BASE = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';

// === DATA: Produkty ===
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
    $classLabel[(string)$p['status']]      = status_badge($p['status']);
}

// === DATA: Promocje ===
$promos = db()->query("
  SELECT id, name, code, is_active, scope_type, scope_value,
         valid_from, valid_to, min_days, discount_type, discount_val
  FROM promotions
  ORDER BY id DESC
")->fetchAll(PDO::FETCH_ASSOC);

// === DATA: Raporty ===
$reports = [
    'revenue_today' => 457.00,
    'orders_today'  => 6,
    'top_product'   => 'Toyota Corolla'
];

?>

<!DOCTYPE html>
<html lang="pl">

<head>
    <meta charset="utf-8">
    <title>Debug Dashboard Staff</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <div class="container-fluid mt-4">
        <h1>Debug Dashboard Staff</h1>

        <div class="alert alert-info">
            <strong>Debug Info:</strong><br>
            Staff: <?= htmlspecialchars($staff['email']) ?><br>
            Products count: <?= count($products) ?><br>
            Promos count: <?= count($promos) ?><br>
            BASE: <?= htmlspecialchars($BASE) ?>
        </div>

        <ul class="nav nav-pills nav-justified mb-4" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="tab-products" data-bs-toggle="pill" data-bs-target="#pane-products" type="button" role="tab">
                    Produkty (<?= count($products) ?>)
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-promos" data-bs-toggle="pill" data-bs-target="#pane-promos" type="button" role="tab">
                    Promocje (<?= count($promos) ?>)
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-reports" data-bs-toggle="pill" data-bs-target="#pane-reports" type="button" role="tab">
                    Raporty
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-settings" data-bs-toggle="pill" data-bs-target="#pane-settings" type="button" role="tab">
                    Ustawienia
                </button>
            </li>
        </ul>

        <div class="tab-content">
            <div class="tab-pane fade show active" id="pane-products" role="tabpanel">
                <div class="alert alert-success">Loading section-products.php...</div>
                <?php
                try {
                    include __DIR__ . '/pages/staff/section-products.php';
                    echo '<div class="alert alert-success mt-2">✅ section-products.php loaded successfully</div>';
                } catch (Exception $e) {
                    echo '<div class="alert alert-danger mt-2">❌ Error in section-products.php: ' . htmlspecialchars($e->getMessage()) . '</div>';
                } catch (Error $e) {
                    echo '<div class="alert alert-danger mt-2">❌ Fatal in section-products.php: ' . htmlspecialchars($e->getMessage()) . '</div>';
                }
                ?>
            </div>

            <div class="tab-pane fade" id="pane-promos" role="tabpanel">
                <div class="alert alert-info">Loading section-promos.php...</div>
                <?php
                try {
                    include __DIR__ . '/pages/staff/section-promos.php';
                    echo '<div class="alert alert-success mt-2">✅ section-promos.php loaded successfully</div>';
                } catch (Exception $e) {
                    echo '<div class="alert alert-danger mt-2">❌ Error in section-promos.php: ' . htmlspecialchars($e->getMessage()) . '</div>';
                } catch (Error $e) {
                    echo '<div class="alert alert-danger mt-2">❌ Fatal in section-promos.php: ' . htmlspecialchars($e->getMessage()) . '</div>';
                }
                ?>
            </div>

            <div class="tab-pane fade" id="pane-reports" role="tabpanel">
                <div class="alert alert-info">Loading section-reports.php...</div>
                <?php
                try {
                    include __DIR__ . '/pages/staff/section-reports.php';
                    echo '<div class="alert alert-success mt-2">✅ section-reports.php loaded successfully</div>';
                } catch (Exception $e) {
                    echo '<div class="alert alert-danger mt-2">❌ Error in section-reports.php: ' . htmlspecialchars($e->getMessage()) . '</div>';
                } catch (Error $e) {
                    echo '<div class="alert alert-danger mt-2">❌ Fatal in section-reports.php: ' . htmlspecialchars($e->getMessage()) . '</div>';
                }
                ?>
            </div>

            <div class="tab-pane fade" id="pane-settings" role="tabpanel">
                <div class="alert alert-info">Loading section-settings.php...</div>
                <?php
                try {
                    include __DIR__ . '/pages/staff/section-settings.php';
                    echo '<div class="alert alert-success mt-2">✅ section-settings.php loaded successfully</div>';
                } catch (Exception $e) {
                    echo '<div class="alert alert-danger mt-2">❌ Error in section-settings.php: ' . htmlspecialchars($e->getMessage()) . '</div>';
                } catch (Error $e) {
                    echo '<div class="alert alert-danger mt-2">❌ Fatal in section-settings.php: ' . htmlspecialchars($e->getMessage()) . '</div>';
                }
                ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>