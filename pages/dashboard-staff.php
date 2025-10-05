<?php
require_once dirname(__DIR__) . '/partials/head.php';
require_once dirname(__DIR__) . '/partials/header.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/_helpers.php';
require_once dirname(__DIR__) . '/includes/i18n.php';
require_once dirname(__DIR__) . '/includes/theme-config.php';
require_once __DIR__ . '/staff/_helpers.php';

// Force reload translations after header initialization
i18n::init();

$BASE = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
echo '<style>' . ThemeConfig::generateCSSVariables() . '</style>';

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
                <div class="dashboard-header p-4 rounded-4 mb-4 d-flex align-items-center justify-content-between flex-wrap gap-3" style="background: var(--gradient-primary); color: white; border-bottom: 1px solid var(--color-primary-dark);">
                    <div>
                        <h1 class="h3 mb-2 fw-bold text-white"><i class="bi bi-person-badge me-2"></i><?= __('dashboard', 'admin', 'Panel główny') ?></h1>
                        <div class="text-white-50 fs-5">
                            <?= htmlspecialchars($staff['first_name'] ?? '') . ' ' . htmlspecialchars($staff['last_name'] ?? '') ?>
                            <?= !empty($staff['job_title']) ? ' • ' . htmlspecialchars($staff['job_title']) : '' ?>
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <a class="btn btn-clean btn-sm" href="<?= $BASE ?>/index.php"><i class="bi bi-eye"></i> <?= __('view_site', 'admin', 'Podgląd strony') ?></a>
                        <a class="btn btn-clean btn-sm fw-medium" href="index.php?page=product-form" id="product-new"><i class="bi bi-plus-lg"></i> <?= __('add_product', 'admin', 'Dodaj produkt') ?></a>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="nav-container p-3 bg-white rounded-4 shadow-sm">
                    <ul class="nav nav-pills-custom gap-2" id="staffTabs" role="tablist" style="background: transparent;">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link-custom active" id="tab-products" data-bs-toggle="pill" data-bs-target="#pane-products" type="button" role="tab"><i class="bi bi-box-seam"></i> <?= __('products', 'admin', 'Produkty') ?></button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link-custom" id="tab-vehicles" data-bs-toggle="pill" data-bs-target="#pane-vehicles" type="button" role="tab"><i class="bi bi-truck"></i> <?= __('vehicles', 'admin', 'Pojazdy') ?></button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link-custom" id="tab-orders" data-bs-toggle="pill" data-bs-target="#pane-orders" type="button" role="tab"><i class="bi bi-receipt"></i> <?= __('orders', 'admin', 'Zamówienia') ?></button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link-custom" id="tab-promos" data-bs-toggle="pill" data-bs-target="#pane-promos" type="button" role="tab"><i class="bi bi-stars"></i> <?= __('promotions', 'admin', 'Promocje') ?></button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link-custom" id="tab-upcoming" data-bs-toggle="pill" data-bs-target="#pane-upcoming" type="button" role="tab"><i class="bi bi-calendar-event"></i> <?= __('upcoming_dates', 'admin', 'Najbliższe terminy') ?></button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link-custom" id="tab-reports" data-bs-toggle="pill" data-bs-target="#pane-reports" type="button" role="tab"><i class="bi bi-bar-chart"></i> <?= __('reports', 'admin', 'Raporty') ?></button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link-custom" id="tab-dicts" data-bs-toggle="pill" data-bs-target="#pane-dicts" type="button" role="tab"><i class="bi bi-journal-bookmark"></i> <?= __('dictionaries', 'admin', 'Słowniki') ?></button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link-custom" id="tab-settings" data-bs-toggle="pill" data-bs-target="#pane-settings" type="button" role="tab"><i class="bi bi-gear"></i> <?= __('settings', 'admin', 'Ustawienia') ?></button>
                        </li>
                    </ul>
                </div>
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
        // Funkcja do dezaktywacji wszystkich zakładek
        function clearAllActiveStates() {
            // Usuń klasy active z wszystkich przycisków nawigacji
            document.querySelectorAll('.nav-link-custom').forEach(function(tab) {
                tab.classList.remove('active');
            });

            // Usuń klasy active i show z wszystkich pane
            document.querySelectorAll('.tab-pane').forEach(function(pane) {
                pane.classList.remove('active', 'show');
            });
        }

        // Funkcja do aktywacji konkretnej zakładki
        function activateTab(targetHash) {
            clearAllActiveStates();

            var trigger = document.querySelector('button[data-bs-target="' + targetHash + '"]');
            var targetPane = document.querySelector(targetHash);

            if (trigger && targetPane) {
                trigger.classList.add('active');
                targetPane.classList.add('active', 'show');
                return true;
            }
            return false;
        }

        // Obsługa kliknięć w zakładki
        document.querySelectorAll('.nav-link-custom').forEach(function(tab) {
            tab.addEventListener('click', function(e) {
                e.preventDefault();
                var target = this.getAttribute('data-bs-target');
                if (target) {
                    activateTab(target);
                }
            });
        });

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
                if (!activateTab(hash)) {
                    // Jeśli nie udało się aktywować zakładki z hash, aktywuj domyślną
                    activateTab('#pane-products');
                }
            } else {
                // Upewnij się, że produkty są aktywne domyślnie
                activateTab('#pane-products');
            }

            // Przewiń do kotwicy tylko jeśli jest w URL i to nie jest POST request
            if (window.location.hash && window.location.hash === '#location-fees') {
                setTimeout(function() {
                    var anchor = document.querySelector(window.location.hash);
                    if (anchor) {
                        anchor.scrollIntoView({
                            behavior: 'smooth'
                        });
                    }
                }, 200);
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