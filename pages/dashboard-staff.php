<?php
// /pages/dashboard-staff.php
require_once dirname(__DIR__) . '/auth/auth.php';
$staff = require_staff();

require_once dirname(__DIR__) . '/partials/head.php';
require_once dirname(__DIR__) . '/partials/header.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/_helpers.php';
require_once dirname(__DIR__) . '/includes/i18n.php';
require_once __DIR__ . '/staff/_helpers.php';

// Force reload translations after header initialization
i18n::init();

$BASE = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';

// === FUNKCJA SORTOWANIA ===
function sort_link_dashboard(string $section, string $key, string $label): string
{
    $currentSection = $_GET['section'] ?? '';
    $currentSort = $_GET['sort'] ?? '';
    $currentDir = strtolower($_GET['dir'] ?? 'asc');

    // Tylko sortuj jeśli jesteśmy w tej sekcji
    $nextDir = ($currentSection === $section && $currentSort === $key && $currentDir === 'asc') ? 'desc' : 'asc';
    $arrowUpActive = ($currentSection === $section && $currentSort === $key && $currentDir === 'asc');
    $arrowDownActive = ($currentSection === $section && $currentSort === $key && $currentDir === 'desc');

    $BASE = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
    $qs = http_build_query([
        'page' => 'dashboard-staff',
        'section' => $section,
        'sort' => $key,
        'dir' => $nextDir,
    ]);

    // Dodaj hash dla sekcji
    $hash = '#pane-' . $section;

    return '<a class="th-sort-link" href="' . htmlspecialchars($BASE . '/index.php?' . $qs . $hash) . '">'
        . '<span class="label">' . htmlspecialchars($label) . '</span>'
        . '<span class="chevs"><span class="chev ' . ($arrowUpActive ? 'active' : '') . '">▲</span><span class="chev ' . ($arrowDownActive ? 'active' : '') . '">▼</span></span>'
        . '</a>';
}

// === SORTOWANIE PARAMETRY ===
$section = $_GET['section'] ?? '';
$sort = $_GET['sort'] ?? '';
$dir = strtolower($_GET['dir'] ?? 'asc');
$dir = in_array($dir, ['asc', 'desc'], true) ? $dir : 'asc';

// === DATA: Produkty z sortowaniem ===
$productOrder = '';
if ($section === 'products') {
    $productOrder = match ($sort) {
        'id' => "ORDER BY id $dir",
        'name' => "ORDER BY name $dir",
        'sku' => "ORDER BY sku $dir",
        'price' => "ORDER BY price $dir",
        'stock' => "ORDER BY stock $dir",
        'status' => "ORDER BY status $dir",
        'category' => "ORDER BY category $dir",
        default => "ORDER BY id DESC"
    };
} else {
    $productOrder = "ORDER BY id DESC";
}

$products = db()->query("
  SELECT id, name, sku, price, stock, status, category
  FROM products
  $productOrder
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

// === DATA: Zamówienia z sortowaniem (mock) ===
$orders = [
    ['id' => 5001, 'date' => '2025-09-30', 'product' => 'Toyota Corolla', 'qty' => 2, 'total' => 298.00, 'status' => 'paid'],
    ['id' => 5002, 'date' => '2025-10-01', 'product' => 'VW Golf',        'qty' => 1, 'total' => 159.00, 'status' => 'pending'],
];

// Sortowanie zamówień
if ($section === 'orders' && !empty($sort)) {
    usort($orders, function ($a, $b) use ($sort, $dir) {
        $result = match ($sort) {
            'id' => $a['id'] <=> $b['id'],
            'date' => strcmp($a['date'], $b['date']),
            'product' => strcmp($a['product'], $b['product']),
            'qty' => $a['qty'] <=> $b['qty'],
            'total' => $a['total'] <=> $b['total'],
            'status' => strcmp($a['status'], $b['status']),
            default => 0
        };
        return $dir === 'desc' ? -$result : $result;
    });
}

// === DATA: Promocje z sortowaniem ===
$promoOrder = '';
if ($section === 'promos') {
    $promoOrder = match ($sort) {
        'id' => "ORDER BY id $dir",
        'name' => "ORDER BY name $dir",
        'code' => "ORDER BY code $dir",
        'active' => "ORDER BY is_active $dir",
        'scope' => "ORDER BY scope_type $dir",
        'valid_from' => "ORDER BY valid_from $dir",
        'valid_to' => "ORDER BY valid_to $dir",
        'discount' => "ORDER BY discount_val $dir",
        default => "ORDER BY id DESC"
    };
} else {
    $promoOrder = "ORDER BY id DESC";
}

$promos = db()->query("
  SELECT id, name, code, is_active, scope_type, scope_value,
         valid_from, valid_to, min_days, discount_type, discount_val
  FROM promotions
  $promoOrder
")->fetchAll(PDO::FETCH_ASSOC);

// === DATA: Raporty (mock) ===
$reports = [
    'revenue_today' => 457.00,
    'orders_today'  => 6,
    'top_product'   => 'Toyota Corolla',
];
?>
<main class="container-fluid staff-main"
    style="padding-top: 72px;">
    <div class="w-100" style="margin: 0 auto; padding: 0 60px;">
        <div class="row g-3">
            <div class="col-12">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div>
                        <h1 class="h4 mb-0"><?= __('dashboard', 'admin', 'Panel pracownika') ?></h1>
                        <div class="text-muted small">
                            <?= htmlspecialchars($staff['first_name'] ?? '') . ' ' . htmlspecialchars($staff['last_name'] ?? '') ?>
                            <?= !empty($staff['job_title']) ? ' • ' . htmlspecialchars($staff['job_title']) : '' ?>
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <a class="btn btn-outline-secondary btn-sm" href="<?= $BASE ?>/index.php"><?= __('view_site', 'admin', 'Podgląd strony') ?></a>
                        <a class="btn btn-primary btn-sm" href="index.php?page=product-form" id="product-new">+ <?= __('add_product', 'admin', 'Dodaj produkt') ?></a>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <ul class="nav nav-pills gap-2" id="staffTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="tab-products" data-bs-toggle="pill" data-bs-target="#pane-products" type="button" role="tab"><?= __('products', 'admin', 'Produkty') ?></button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tab-vehicles" data-bs-toggle="pill" data-bs-target="#pane-vehicles" type="button" role="tab">
                            <?= __('vehicles', 'admin', 'Pojazdy') ?>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tab-orders" data-bs-toggle="pill" data-bs-target="#pane-orders" type="button" role="tab"><?= __('orders', 'admin', 'Zamówienia') ?></button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tab-promos" data-bs-toggle="pill" data-bs-target="#pane-promos" type="button" role="tab"><?= __('promotions', 'admin', 'Promocje') ?></button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tab-upcoming" data-bs-toggle="pill" data-bs-target="#pane-upcoming" type="button" role="tab"><?= __('upcoming_dates', 'admin', 'Najbliższe terminy') ?></button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tab-reports" data-bs-toggle="pill" data-bs-target="#pane-reports" type="button" role="tab"><?= __('reports', 'admin', 'Raporty') ?></button>
                    </li>

                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tab-dicts" data-bs-toggle="pill" data-bs-target="#pane-dicts" type="button" role="tab"><?= __('dictionaries', 'admin', 'Słowniki') ?></button>
                    </li>

                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tab-settings" data-bs-toggle="pill" data-bs-target="#pane-settings" type="button" role="tab"><?= __('settings', 'admin', 'Ustawienia') ?></button>
                    </li>
                </ul>
            </div>

            <div class="col-12">
                <div class="tab-content">
                    <div class="tab-pane fade show active" id="pane-products" role="tabpanel" aria-labelledby="tab-products">
                        <?php include __DIR__ . '/staff/section-products.php'; ?>
                    </div>
                    <div class="tab-pane fade" id="pane-vehicles" role="tabpanel" aria-labelledby="tab-vehicles">
                        <?php include __DIR__ . '/staff/section-vehicles.php'; ?>
                    </div>
                    <div class="tab-pane fade" id="pane-orders" role="tabpanel" aria-labelledby="tab-orders">
                        <?php include __DIR__ . '/staff/section-orders.php'; ?>
                    </div>
                    <div class="tab-pane fade" id="pane-promos" role="tabpanel" aria-labelledby="tab-promos">
                        <?php include __DIR__ . '/staff/section-promos.php'; ?>
                    </div>
                    <div class="tab-pane fade" id="pane-upcoming" role="tabpanel" aria-labelledby="tab-upcoming">
                        <?php include __DIR__ . '/staff/section-upcoming.php'; ?>
                    </div>
                    <div class="tab-pane fade" id="pane-reports" role="tabpanel" aria-labelledby="tab-reports">
                        <?php include __DIR__ . '/staff/section-reports.php'; ?>
                    </div>
                    <div class="tab-pane fade" id="pane-dicts" role="tabpanel" aria-labelledby="tab-dicts">
                        <?php include __DIR__ . '/staff/section-dicts.php'; ?>
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
        // Małe opóźnienie dla Bootstrap
        setTimeout(function() {
            // Obsługa hash URL - tylko przy załadowaniu strony
            var hash = window.location.hash;
            var urlParams = new URLSearchParams(window.location.search);
            var section = urlParams.get('section');

            console.log('URL params:', {
                section: section,
                hash: hash
            });

            // Jeśli mamy parametr section, użyj go
            if (section && !hash) {
                hash = '#pane-' + section;
            }

            // Jeśli mamy hash z kotwicą, nadal aktywuj odpowiednią zakładkę
            if (section === 'settings' || hash === '#location-fees') {
                hash = '#pane-settings';
            }

            console.log('Target hash:', hash);

            if (hash) {
                var trigger = document.querySelector('button[data-bs-target="' + hash + '"]');
                console.log('Found trigger:', trigger);
                if (trigger) {
                    // Dezaktywuj obecną aktywną zakładkę
                    var activeTab = document.querySelector('.nav-link.active');
                    var activePane = document.querySelector('.tab-pane.active');
                    if (activeTab) activeTab.classList.remove('active');
                    if (activePane) {
                        activePane.classList.remove('active', 'show');
                    }

                    // Aktywuj nową zakładkę
                    trigger.classList.add('active');
                    var targetPane = document.querySelector(hash);
                    if (targetPane) {
                        targetPane.classList.add('active', 'show');
                    }

                    // Przewiń do kotwicy tylko jeśli jest w URL i to nie jest POST request
                    if (window.location.hash && window.location.hash !== hash && window.location.hash === '#location-fees') {
                        setTimeout(function() {
                            var anchor = document.querySelector(window.location.hash);
                            if (anchor) {
                                anchor.scrollIntoView({
                                    behavior: 'smooth'
                                });
                            }
                        }, 200);
                    }
                }
            }
        }, 100);
    });
</script>

<style>
    /* Responsive padding dla głównego panelu */
    @media (max-width: 768px) {
        .staff-main>div {
            padding: 0 20px !important;
        }
    }

    @media (max-width: 576px) {
        .staff-main>div {
            padding: 0 15px !important;
        }
    }
</style>

<?php require_once dirname(__DIR__) . '/partials/footer.php'; ?>