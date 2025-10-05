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
            <?php
            // PAGINATION LOGIC
            $page = isset($_GET['page_num']) ? max(1, (int)$_GET['page_num']) : 1;
            $perPage = 12;
            $total = count($products);
            $pages = max(1, ceil($total / $perPage));
            $start = ($page - 1) * $perPage;
            $productsPage = array_slice($products, $start, $perPage);
            ?>
            <div class="row g-4">
                <?php foreach ($productsPage as $idx => $p): ?>
                    <?php
                    $rel = !empty($p['image_path']) ? ltrim((string)$p['image_path'], '/') : $placeholderRel;
                    $img = asset_url($rel);
                    $hasPromo   = $searchActive && !empty($p['discount_applied']);
                    $promoLabel = $hasPromo ? ($p['discount_label'] ?? null) : null;
                    $priceBase  = (float)($p['price'] ?? 0);
                    $priceFinal = isset($p['price_final']) ? (float)$p['price_final'] : $priceBase;
                    $modalId = 'productModal' . $idx;
                    ?>
                    <div class="col-12 col-sm-6 col-lg-3">
                        <div class="product-card h-100 position-relative">
                            <?php if ($hasPromo && $promoLabel): ?>
                                <span class="badge text-bg-danger position-absolute" style="top:10px; right:10px; z-index:2;">
                                    <?= htmlspecialchars($promoLabel) ?>
                                </span>
                            <?php endif; ?>
                            <img src="<?= htmlspecialchars($img) ?>" alt="<?= htmlspecialchars((string)$p['name']) ?>" class="product-image" loading="lazy" decoding="async">
                            <div class="px-3 pb-3">
                                <div class="product-title"><?= htmlspecialchars((string)$p['name']) ?></div>
                                <div class="product-meta">
                                    <?php if (!empty($p['seats'])): ?><span><i class="bi bi-person"></i> <?= (int)$p['seats'] ?> miejsc</span><?php endif; ?>
                                    <?php if (!empty($p['fuel'])): ?><span><i class="bi bi-fuel-pump"></i> <?= htmlspecialchars($p['fuel']) ?></span><?php endif; ?>
                                    <?php if (!empty($p['gearbox'])): ?><span><i class="bi bi-gear"></i> <?= htmlspecialchars($p['gearbox']) ?></span><?php endif; ?>
                                    <?php if (!empty($p['doors'])): ?><span><i class="bi bi-door-open"></i> <?= (int)$p['doors'] ?> drzwi</span><?php endif; ?>
                                    <?php if (!empty($p['category'])): ?><span><i class="bi bi-grid"></i> Klasa <?= htmlspecialchars(strtoupper((string)$p['category'])) ?></span><?php endif; ?>
                                </div>
                                <div class="product-price">
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
                                <div class="product-actions">
                                    <button type="button" class="btn btn-theme btn-secondary" data-bs-toggle="modal" data-bs-target="#<?= $modalId ?>">Szczegóły</button>
                                    <?php
                                    // Przekaż parametry wyszukiwania do rezerwacji
                                    $params = [
                                        'page' => 'reserve',
                                        'sku' => (string)$p['sku'],
                                    ];
                                    foreach (
                                        [
                                            'pickup_location',
                                            'dropoff_location',
                                            'pickup_at',
                                            'return_at',
                                            'vehicle_type',
                                            'transmission',
                                            'seats_min',
                                            'fuel'
                                        ] as $key
                                    ) {
                                        if (!empty($_GET[$key])) {
                                            $params[$key] = $_GET[$key];
                                        }
                                    }
                                    $reserveUrl = $BASE . '/index.php?' . http_build_query($params);
                                    ?>
                                    <a href="<?= htmlspecialchars($reserveUrl) ?>" class="btn btn-theme btn-primary">Zarezerwuj</a>
                                </div>
                                <div class="d-flex align-items-center justify-content-between mt-2">
                                    <span class="text-muted small">SKU: <?= htmlspecialchars((string)$p['sku']) ?></span>
                                    <span class="badge <?= ((int)$p['stock'] > 0 ? 'text-bg-success' : 'text-bg-secondary') ?>">
                                        <?= (int)$p['stock'] ?> szt.
                                    </span>
                                </div>
                            </div>
                        </div>
                        <!-- MODAL -->
                        <div class="modal fade" id="<?= $modalId ?>" tabindex="-1" aria-labelledby="<?= $modalId ?>Label" aria-hidden="true">
                            <div class="modal-dialog modal-lg modal-dialog-centered">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="<?= $modalId ?>Label"><?= htmlspecialchars((string)$p['name']) ?></h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Zamknij"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="row g-4">
                                            <div class="col-md-6">
                                                <img src="<?= htmlspecialchars($img) ?>" alt="<?= htmlspecialchars((string)$p['name']) ?>" class="img-fluid rounded shadow-sm mb-3">
                                            </div>
                                            <div class="col-md-6">
                                                <div class="bg-light rounded-3 shadow-sm p-4 h-100 d-flex flex-column justify-content-center" style="min-height:340px; background: #f3f4f6; color: #222;">
                                                    <div class="mb-2">
                                                        <strong>Opis:</strong><br>
                                                        <span><?= htmlspecialchars($p['description'] ?? '-') ?></span>
                                                    </div>
                                                    <div class="mb-2">
                                                        <strong>Liczba miejsc:</strong> <?= (int)$p['seats'] ?>
                                                    </div>
                                                    <div class="mb-2">
                                                        <strong>Liczba drzwi:</strong> <?= (int)$p['doors'] ?>
                                                    </div>
                                                    <div class="mb-2">
                                                        <strong>Paliwo:</strong> <?= htmlspecialchars($p['fuel'] ?? '-') ?>
                                                    </div>
                                                    <div class="mb-2">
                                                        <strong>Skrzynia biegów:</strong> <?= htmlspecialchars($p['gearbox'] ?? '-') ?>
                                                    </div>
                                                    <div class="mb-2">
                                                        <strong>Klasa:</strong> <?= htmlspecialchars(strtoupper((string)$p['category'])) ?>
                                                    </div>
                                                    <div class="mb-2">
                                                        <strong>Cena:</strong> <?= number_format($priceBase, 2, ',', ' ') ?> PLN<?= price_unit_label($p['price_unit'] ?? null) ?>
                                                    </div>
                                                    <div class="mb-2">
                                                        <strong>Dostępność:</strong> <span class="badge <?= ((int)$p['stock'] > 0 ? 'text-bg-success' : 'text-bg-secondary') ?>"><?= (int)$p['stock'] ?> szt.</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-theme btn-light" data-bs-dismiss="modal">Zamknij</button>
                                        <a href="index.php?page=reserve&sku=<?= urlencode((string)$p['sku']) ?>" class="btn btn-theme btn-primary">Zarezerwuj</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <!-- PAGINATION -->
            <?php if ($pages > 1): ?>
                <nav class="mt-4">
                    <ul class="pagination justify-content-center">
                        <li class="page-item<?= $page <= 1 ? ' disabled' : '' ?>">
                            <a class="page-link" href="?page_num=<?= $page - 1 ?>">Poprzednia</a>
                        </li>
                        <?php for ($i = 1; $i <= $pages; $i++): ?>
                            <li class="page-item<?= $i === $page ? ' active' : '' ?>">
                                <a class="page-link" href="?page_num=<?= $i ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item<?= $page >= $pages ? ' disabled' : '' ?>">
                            <a class="page-link" href="?page_num=<?= $page + 1 ?>">Następna</a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</section>