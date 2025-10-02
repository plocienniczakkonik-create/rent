<?php
// /components/product-grid.php
require_once dirname(__DIR__) . '/includes/db.php';

$BASE = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';

/** Buduje URL względny do BASE_URL, niezależnie czy w DB jest z lub bez wiodącego / */
function asset_url(string $path): string
{
    global $BASE;
    $p = ltrim($path, '/');
    return ($BASE !== '' ? $BASE . '/' : '') . $p;
}

function price_unit_label(?string $u): string
{
    return match ($u) {
        'per_hour' => ' / godz.',
        default    => ' / dzień',
    };
}

function spec_line(array $p): string
{
    $parts = [];
    if (!empty($p['category'])) $parts[] = 'Klasa ' . strtoupper((string)$p['category']);
    if (!empty($p['seats']))    $parts[] = (int)$p['seats'] . ' miejsca';
    if (!empty($p['doors']))    $parts[] = (int)$p['doors'] . ' drzwi';
    if (!empty($p['gearbox']))  $parts[] = (string)$p['gearbox'];
    if (!empty($p['fuel']))     $parts[] = (string)$p['fuel'];
    return implode(' · ', $parts);
}

// 1) Jeżeli home.php zainicjalizował $SEARCH = run_search($_GET), użyj jego wyników.
//    TYLKO gdy nie ma $SEARCH — dopiero wtedy pobierz wszystko z DB.
$usingSearch   = isset($SEARCH) && is_array($SEARCH) && array_key_exists('products', $SEARCH);
$searchActive  = $usingSearch && !empty($SEARCH['active']); // użyjemy do napisu i badge
$products      = [];

if ($usingSearch) {
    $products = $SEARCH['products'] ?? [];
} else {
    // Fallback — bez filtrów
    $stmt = db()->query("
      SELECT
        id, name, sku, price, price_unit, stock, status,
        category, seats, doors, gearbox, fuel, image_path, description
      FROM products
      WHERE status = 'active' AND stock > 0
      ORDER BY id DESC
    ");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// placeholder (bez wiodącego slasha – dołączymy BASE_URL helperem)
$placeholderRel = 'assets/img/placeholder-car.webp';

// Toolbar (pod nagłówkiem „Nasza flota”)
// - prawa strona: HTML formularza sortowania przekazany z /pages/search-results.php
// - lewa strona: liczba pojazdów (jeśli nie przekazano własnego tekstu)
$GRID_TOOLBAR_LEFT  = $GRID_TOOLBAR_LEFT  ?? (count($products) . ' pojazdów');
$GRID_TOOLBAR_RIGHT = $GRID_TOOLBAR_RIGHT ?? null;
?>
<section id="offer" class="py-5">
    <div class="container">

        <div class="d-flex align-items-end justify-content-between mb-2">
            <h2 class="h4 mb-0">
                Nasza flota
                <?php if ($searchActive): ?>
                    <span class="text-muted h6 ms-2">(wyniki wyszukiwania)</span>
                <?php endif; ?>
            </h2>
        </div>

        <?php if (!empty($GRID_TOOLBAR_LEFT) || !empty($GRID_TOOLBAR_RIGHT)): ?>
            <div class="results-toolbar d-flex align-items-center justify-content-between gap-2 mb-3">
                <div class="results-left text-muted small">
                    <?= htmlspecialchars((string)$GRID_TOOLBAR_LEFT) ?>
                </div>
                <div class="results-right">
                    <?= !empty($GRID_TOOLBAR_RIGHT) ? $GRID_TOOLBAR_RIGHT : '' ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!$products): ?>
            <div class="alert alert-info mb-0">Brak dostępnych pojazdów dla wybranych filtrów.</div>
        <?php else: ?>
            <div class="row g-3 g-md-4">
                <?php foreach ($products as $p): ?>
                    <?php
                    $rel = !empty($p['image_path']) ? ltrim((string)$p['image_path'], '/') : $placeholderRel;
                    $img = asset_url($rel);

                    // Promocję pokazujemy tylko gdy to są wyniki wyszukiwania i rzeczywiście naliczono zniżkę
                    $hasPromo   = $searchActive && !empty($p['discount_applied']);
                    $promoLabel = $hasPromo ? ($p['discount_label'] ?? null) : null;

                    $priceBase  = (float)($p['price'] ?? 0);
                    $priceFinal = isset($p['price_final']) ? (float)$p['price_final'] : $priceBase;
                    ?>
                    <div class="col-12 col-sm-6 col-lg-4">
                        <div class="card h-100 shadow-sm position-relative">

                            <?php if ($hasPromo && $promoLabel): ?>
                                <span class="badge text-bg-danger position-absolute" style="top:10px; right:10px; z-index:2;">
                                    <?= htmlspecialchars($promoLabel) ?>
                                </span>
                            <?php endif; ?>

                            <!-- Stabilny kadr 16:9 + cover, lazy-load -->
                            <div class="ratio ratio-16x9 bg-light rounded-top overflow-hidden">
                                <img
                                    src="<?= htmlspecialchars($img) ?>"
                                    alt="<?= htmlspecialchars((string)$p['name']) ?>"
                                    class="w-100 h-100"
                                    style="object-fit: cover;"
                                    loading="lazy" decoding="async">
                            </div>

                            <div class="card-body d-flex flex-column">
                                <div class="d-flex align-items-start justify-content-between mb-1">
                                    <h3 class="h6 mb-0"><?= htmlspecialchars((string)$p['name']) ?></h3>
                                    <?php if (!empty($p['category'])): ?>
                                        <span class="badge text-bg-secondary ms-2">Klasa <?= htmlspecialchars(strtoupper((string)$p['category'])) ?></span>
                                    <?php endif; ?>
                                </div>

                                <?php if ($spec = spec_line($p)): ?>
                                    <div class="text-muted small mb-2"><?= htmlspecialchars($spec) ?></div>
                                <?php endif; ?>

                                <?php if (!empty($p['description'])): ?>
                                    <p class="small text-muted mb-3" style="min-height: 2.5em; line-height:1.25em; overflow:hidden;">
                                        <?= htmlspecialchars(mb_strimwidth((string)$p['description'], 0, 140, '…')) ?>
                                    </p>
                                <?php else: ?>
                                    <div class="mb-3"></div>
                                <?php endif; ?>

                                <!-- Wiersz ceny z ustaloną wysokością i bez zawijania jednostki -->
                                <div class="mt-auto d-flex align-items-center justify-content-between">
                                    <div class="price-row d-flex align-items-baseline gap-2 me-2 flex-grow-1"
                                        style="min-height:28px; white-space:nowrap;">
                                        <?php if ($hasPromo && $priceFinal < $priceBase): ?>
                                            <span class="text-muted text-decoration-line-through">
                                                <?= number_format($priceBase, 2, ',', ' ') ?> PLN
                                            </span>
                                            <span class="fw-semibold">
                                                <?= number_format($priceFinal, 2, ',', ' ') ?> PLN
                                            </span>
                                        <?php else: ?>
                                            <span class="fw-semibold">
                                                <?= number_format($priceBase, 2, ',', ' ') ?> PLN
                                            </span>
                                        <?php endif; ?>
                                        <span class="text-muted small"><?= price_unit_label($p['price_unit'] ?? null) ?></span>
                                    </div>

                                    <div class="d-flex gap-2 flex-shrink-0">
                                        <a class="btn btn-outline-primary btn-sm" href="index.php?page=product&sku=<?= urlencode((string)$p['sku']) ?>">Szczegóły</a>
                                        <a class="btn btn-primary btn-sm" href="index.php?page=reserve&sku=<?= urlencode((string)$p['sku']) ?>">Zarezerwuj</a>
                                    </div>
                                </div>
                            </div>

                            <div class="card-footer bg-transparent d-flex align-items-center justify-content-between">
                                <span class="text-muted small">SKU: <?= htmlspecialchars((string)$p['sku']) ?></span>
                                <span class="badge <?= ((int)$p['stock'] > 0 ? 'text-bg-success' : 'text-bg-secondary') ?>">
                                    <?= (int)$p['stock'] ?> szt.
                                </span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
