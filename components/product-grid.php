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
    if (!empty($p['car_type'])) $parts[] = htmlspecialchars((string)$p['car_type']);
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
        category, car_type, seats, doors, gearbox, fuel, image_path, description
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
        <div class="card shadow-lg border-0 rounded-4 overflow-hidden">
            <div class="card-header bg-primary py-4 border-0">
                <div class="d-flex align-items-end justify-content-between mb-0">
                    <h2 class="h3 mb-0 text-white">
                        <i class="bi bi-car-front-fill me-2"></i>Nasza flota
                        <?php if ($searchActive): ?>
                            <span class="text-white-50 h6 ms-2">(wyniki wyszukiwania)</span>
                        <?php endif; ?>
                    </h2>
                </div>
            </div>
            
            <div class="card-body p-4">

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
            <div class="alert alert-info mb-0 text-center py-5">
                <i class="bi bi-car-front display-4 text-muted mb-3"></i>
                <h5>Brak dostępnych pojazdów</h5>
                <p class="mb-0">Nie znaleziono pojazdów dla wybranych filtrów. Spróbuj zmienić kryteria wyszukiwania.</p>
            </div>
        <?php else: ?>
            <?php
            // PAGINATION LOGIC
            $page = isset($_GET['page_num']) ? max(1, (int)$_GET['page_num']) : 1;
            $perPage = 6; // 2 rzędy po 3 karty = 6 kart na stronę
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
                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="card h-100 product-card-modern shadow-sm border-0 overflow-hidden">
                            <?php if ($hasPromo && $promoLabel): ?>
                                <div class="position-absolute top-0 end-0 m-3" style="z-index: 10;">
                                    <span class="badge bg-danger px-3 py-2 rounded-pill">
                                        <i class="bi bi-percent me-1"></i><?= htmlspecialchars($promoLabel) ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                            
                            <div class="position-relative overflow-hidden product-image-container">
                                <img src="<?= htmlspecialchars($img) ?>" 
                                     alt="<?= htmlspecialchars((string)$p['name']) ?>" 
                                     class="card-img-top product-image-modern" 
                                     loading="lazy" decoding="async">
                                <div class="product-overlay">
                                    <button type="button" class="btn btn-light btn-sm rounded-pill" 
                                            data-bs-toggle="modal" data-bs-target="#<?= $modalId ?>">
                                        <i class="bi bi-eye me-1"></i>Szczegóły
                                    </button>
                                </div>
                            </div>
                            
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title product-title-modern mb-2">
                                    <?= htmlspecialchars((string)$p['name']) ?>
                                </h5>
                                
                                <div class="product-specs mb-3">
                                    <div class="row g-2 text-center">
                                        <?php if (!empty($p['seats'])): ?>
                                        <div class="col-6">
                                            <div class="spec-item">
                                                <i class="bi bi-people text-primary"></i>
                                                <small class="d-block"><?= (int)$p['seats'] ?> miejsc</small>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                        <?php if (!empty($p['gearbox'])): ?>
                                        <div class="col-6">
                                            <div class="spec-item">
                                                <i class="bi bi-gear text-primary"></i>
                                                <small class="d-block"><?= htmlspecialchars($p['gearbox']) ?></small>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                        <?php if (!empty($p['fuel'])): ?>
                                        <div class="col-6">
                                            <div class="spec-item">
                                                <i class="bi bi-fuel-pump text-primary"></i>
                                                <small class="d-block"><?= htmlspecialchars($p['fuel']) ?></small>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                        <?php if (!empty($p['car_type'])): ?>
                                        <div class="col-6">
                                            <div class="spec-item">
                                                <i class="bi bi-award text-primary"></i>
                                                <small class="d-block"><?= htmlspecialchars((string)$p['car_type']) ?></small>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="product-pricing mb-3">
                                    <?php if ($hasPromo && $priceFinal < $priceBase): ?>
                                        <div class="price-old text-muted text-decoration-line-through small">
                                            <?= number_format($priceBase, 2, ',', ' ') ?> PLN<?= price_unit_label($p['price_unit'] ?? null) ?>
                                        </div>
                                        <div class="price-final h5 text-success mb-0 fw-bold">
                                            <?= number_format($priceFinal, 2, ',', ' ') ?> PLN<?= price_unit_label($p['price_unit'] ?? null) ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="price-current h5 text-primary mb-0 fw-bold">
                                            <?= number_format($priceBase, 2, ',', ' ') ?> PLN<?= price_unit_label($p['price_unit'] ?? null) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="mt-auto">
                                    
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
                                    
                                    <a href="<?= htmlspecialchars($reserveUrl) ?>" 
                                       class="btn btn-primary w-100 rounded-pill">
                                        <i class="bi bi-calendar-check me-2"></i>Zarezerwuj
                                    </a>
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
                                                        <strong>Typ:</strong> <?= !empty($p['car_type']) ? htmlspecialchars((string)$p['car_type']) : 'Nieokreślony' ?>
                                                    </div>
                                                    <div class="mb-2">
                                                        <strong>Cena:</strong> <?= number_format($priceBase, 2, ',', ' ') ?> PLN<?= price_unit_label($p['price_unit'] ?? null) ?>
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
                <nav class="mt-5">
                    <ul class="pagination-custom justify-content-center d-flex gap-2">
                        <li class="page-item-custom<?= $page <= 1 ? ' disabled' : '' ?>">
                            <a class="page-link-custom" href="?page_num=<?= $page - 1 ?>">
                                <i class="bi bi-chevron-left"></i>
                                <span class="d-none d-sm-inline ms-1">Poprzednia</span>
                            </a>
                        </li>
                        <?php
                        // Zaawansowana logika paginacji - pokazujemy tylko kilka stron na raz
                        $start = max(1, $page - 2);
                        $end = min($pages, $page + 2);
                        
                        if ($start > 1): ?>
                            <li class="page-item-custom">
                                <a class="page-link-custom" href="?page_num=1">1</a>
                            </li>
                            <?php if ($start > 2): ?>
                                <li class="page-item-custom disabled">
                                    <span class="page-link-custom">...</span>
                                </li>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <?php for ($i = $start; $i <= $end; $i++): ?>
                            <li class="page-item-custom<?= $i === $page ? ' active' : '' ?>">
                                <a class="page-link-custom" href="?page_num=<?= $i ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($end < $pages): ?>
                            <?php if ($end < $pages - 1): ?>
                                <li class="page-item-custom disabled">
                                    <span class="page-link-custom">...</span>
                                </li>
                            <?php endif; ?>
                            <li class="page-item-custom">
                                <a class="page-link-custom" href="?page_num=<?= $pages ?>"><?= $pages ?></a>
                            </li>
                        <?php endif; ?>
                        
                        <li class="page-item-custom<?= $page >= $pages ? ' disabled' : '' ?>">
                            <a class="page-link-custom" href="?page_num=<?= $page + 1 ?>">
                                <span class="d-none d-sm-inline me-1">Następna</span>
                                <i class="bi bi-chevron-right"></i>
                            </a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<style>
.bg-gradient {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.product-card-modern {
    transition: all 0.3s ease;
    border-radius: 16px !important;
    border: 1px solid #4a5568 !important;
}

.product-card-modern:hover {
    transform: translateY(-8px);
    box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15) !important;
}

.product-image-container {
    height: 200px;
    background: #f8f9fa;
}

.product-image-modern {
    width: 100%;
    height: 200px;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.product-card-modern:hover .product-image-modern {
    transform: scale(1.05);
}

.product-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.7);
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.product-card-modern:hover .product-overlay {
    opacity: 1;
}

.product-title-modern {
    font-size: 1.1rem;
    font-weight: 600;
    color: #2d3748;
    line-height: 1.3;
}

.product-specs {
    background: #f8f9fa;
    border-radius: 12px;
    padding: 0.75rem;
}

.spec-item {
    padding: 0.25rem;
}

.spec-item i {
    font-size: 1.1rem;
    margin-bottom: 0.25rem;
}

.spec-item small {
    font-weight: 500;
    color: #4a5568;
}

.product-pricing .price-old {
    font-size: 0.85rem;
}

.product-pricing .price-final,
.product-pricing .price-current {
    font-size: 1.25rem;
}

.badge.bg-danger {
    background: linear-gradient(135deg, #e53e3e, #c53030) !important;
}

.badge.bg-success {
    background: linear-gradient(135deg, #38a169, #2f855a) !important;
}

.btn-primary.rounded-pill {
    background: linear-gradient(135deg, #667eea, #764ba2);
    border: none;
    font-weight: 600;
    padding: 0.75rem 1.5rem;
    transition: all 0.3s ease;
}

.btn-primary.rounded-pill:hover {
    background: linear-gradient(135deg, #5a67d8, #6b46c1);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

/* Custom Pagination Styles */
.pagination-custom {
    list-style: none;
    padding: 0;
    margin: 2rem 0;
}

.page-item-custom {
    display: inline-block;
}

.page-link-custom {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0.75rem 1rem;
    min-width: 44px;
    height: 44px;
    border-radius: 12px;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.3s ease;
    background: #ffffff;
    border: 2px solid #e2e8f0;
    color: #4a5568;
    font-size: 0.875rem;
}

.page-link-custom:hover {
    background: linear-gradient(135deg, #667eea, #764ba2);
    border-color: #667eea;
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
    text-decoration: none;
}

.page-item-custom.active .page-link-custom {
    background: linear-gradient(135deg, #667eea, #764ba2);
    border-color: #667eea;
    color: white;
    box-shadow: 0 2px 8px rgba(102, 126, 234, 0.4);
}

.page-item-custom.disabled .page-link-custom {
    background: #f7fafc;
    color: #a0aec0;
    border-color: #e2e8f0;
    cursor: not-allowed;
    opacity: 0.6;
}

.page-item-custom.disabled .page-link-custom:hover {
    background: #f7fafc;
    color: #a0aec0;
    transform: none;
    box-shadow: none;
}

.pagination-custom .page-item-custom + .page-item-custom {
    margin-left: 0.5rem;
}

@media (max-width: 576px) {
    .product-card-modern {
        margin-bottom: 1.5rem;
    }
    
    .product-specs .row {
        gap: 0.5rem;
    }
    
    .product-specs .col-6 {
        flex: 0 0 calc(50% - 0.25rem);
    }
}
</style>