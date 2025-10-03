<?php
// Strona szczegółów produktu (pojazdu) z nowoczesną kartą, kalendarzem i formularzem rezerwacji
// Dane $product, $search są przekazywane z reserve.php
require_once dirname(__DIR__) . '/includes/db.php';
$pdo = db();
// Dodatki
$addons = [];
$stmt = $pdo->prepare("SELECT name, price, charge_type FROM dict_terms WHERE status='active' AND dict_type_id = (SELECT id FROM dict_types WHERE slug='addon' LIMIT 1) ORDER BY sort_order, name");
$stmt->execute();
$addons = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Lokalizacje
$locations = [];
$stmtLoc = $pdo->prepare("SELECT t.name FROM dict_terms t JOIN dict_types dt ON dt.id = t.dict_type_id WHERE dt.slug = 'location' AND t.status = 'active' ORDER BY t.sort_order ASC, t.name ASC");
$stmtLoc->execute();
$rowsLoc = $stmtLoc->fetchAll(PDO::FETCH_COLUMN);
if ($rowsLoc) {
    $locations = array_values(array_unique(array_map('strval', $rowsLoc)));
}
// Pobierz promocję dla tego produktu (jeśli jest aktywna)
$promoPrice = null;
$promoLabel = '';
if (!empty($product['sku'])) {
    $now = date('Y-m-d H:i:s');
    $stmtPromo = $pdo->prepare("SELECT discount_type, discount_val FROM promotions WHERE is_active = 1 AND scope_type = 'product' AND JSON_CONTAINS(scope_value, ?) AND (valid_from IS NULL OR valid_from <= ?) AND (valid_to IS NULL OR valid_to >= ?) LIMIT 1");
    $skuJson = '"' . $product['sku'] . '"';
    $stmtPromo->execute([$skuJson, $now, $now]);
    $promo = $stmtPromo->fetch(PDO::FETCH_ASSOC);
    if ($promo) {
        if ($promo['discount_type'] === 'percent') {
            $promoPrice = $product['price'] * (1 - $promo['discount_val'] / 100);
            $promoLabel = 'Cena promocyjna (' . floatval($promo['discount_val']) . '% taniej):';
        } elseif ($promo['discount_type'] === 'amount') {
            $promoPrice = max(0, $product['price'] - $promo['discount_val']);
            $promoLabel = 'Cena promocyjna (-' . number_format($promo['discount_val'], 2) . ' PLN):';
        }
    }
}
?>
<!-- head, doctype, html usunięte, renderowane tylko w index.php -->

<!-- Flatpickr CSS CDN -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<main class="container product-details product-details-main">


                <div class="row g-4 align-items-start product-details-row">
                    <!-- Lewa kolumna: zdjęcie + kalendarz -->
                    <div class="col-12 col-lg-7 d-flex flex-column justify-content-between product-details-left">
                        <?php $imgPath = ltrim($product['image_path'], '/'); ?>
                        <div>
                            <img src="/rental/<?= htmlspecialchars($imgPath) ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="img-fluid rounded shadow-sm mb-3 w-100">
                        </div>
                        <!-- Sekcja zalet pojazdu przeniesiona pod zdjęcie -->
                        <div class="advantages card p-3 mb-4 align-items-stretch d-flex flex-column justify-content-center" style="height:180px;">
                            <h5 class="mb-3">Dlaczego warto wybrać ten pojazd?</h5>
                            <ul class="list-unstyled mb-0">
                                <li><i class="bi bi-check2-circle text-success"></i> Niskie spalanie</li>
                                <li><i class="bi bi-check2-circle text-success"></i> Komfortowe wyposażenie</li>
                                <li><i class="bi bi-check2-circle text-success"></i> Systemy bezpieczeństwa</li>
                                <li><i class="bi bi-check2-circle text-success"></i> Atrakcyjne warunki wynajmu</li>
                            </ul>
                        </div>
                        <div class="calendar-placeholder card p-3 mb-3 flex-grow-1" style="min-height:220px;">
                            <div id="flatpickr-calendar"></div>
                        </div>
                        <!-- Usunięto niepotrzebny kontener ceny pod kalendarzem -->
                    </div>
                    <!-- Prawa kolumna: parametry, opis, zalety, formularz, cena -->
                    <div class="col-12 col-lg-5 d-flex flex-column justify-content-between product-details-right">
                        <div>
                            <h1><?= htmlspecialchars($product['name']) ?></h1>
                            <div class="mb-4">
                                <div class="row g-2">
                                    <div class="col-6 d-flex justify-content-start">
                                        <span class="param-box"><span class="icon-circle"></span><?= htmlspecialchars($product['fuel']) ?></span>
                                    </div>
                                    <div class="col-6 d-flex justify-content-start">
                                        <span class="param-box"><span class="icon-circle"></span><?= (int)$product['seats'] ?> osobowy</span>
                                    </div>
                                    <div class="col-6 d-flex justify-content-start">
                                        <span class="param-box"><span class="icon-circle"></span><?= htmlspecialchars($product['gearbox']) ?></span>
                                    </div>
                                    <div class="col-6 d-flex justify-content-start">
                                        <span class="param-box"><span class="icon-circle"></span><?= htmlspecialchars($product['body_type'] ?? '-') ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card p-4 h-100 d-flex flex-column justify-content-between">
                            <h4 class="text-success mb-3">Formularz rezerwacji</h4>
                            <form method="post" action="product-reserve.php">
                                <input type="hidden" name="sku" value="<?= htmlspecialchars($product['sku']) ?>">
                                <div class="row g-3 mb-3">
                                    <div class="col-12 col-lg-6">
                                        <label class="form-label">Miejsce odbioru</label>
                                        <select class="form-select" name="pickup_location">
                                            <option value="" <?= empty($search['pickup_location']) ? 'selected' : '' ?> disabled>Wybierz...</option>
                                            <?php foreach ($locations as $loc): ?>
                                                <option value="<?= htmlspecialchars($loc) ?>" <?= ($search['pickup_location'] ?? '') === $loc ? 'selected' : '' ?>><?= htmlspecialchars($loc) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-12 col-lg-6">
                                        <label class="form-label">Miejsce zwrotu</label>
                                        <select class="form-select" name="dropoff_location">
                                            <option value="" <?= empty($search['dropoff_location']) ? 'selected' : '' ?> disabled>Wybierz...</option>
                                            <?php foreach ($locations as $loc): ?>
                                                <option value="<?= htmlspecialchars($loc) ?>" <?= ($search['dropoff_location'] ?? '') === $loc ? 'selected' : '' ?>><?= htmlspecialchars($loc) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="row g-3 mb-3">
                                    <div class="col-12 col-lg-6">
                                        <label class="form-label">Data odbioru</label>
                                        <input type="datetime-local" class="form-control" name="pickup_at" value="<?= htmlspecialchars($search['pickup_at'] ?? '') ?>">
                                    </div>
                                    <div class="col-12 col-lg-6">
                                        <label class="form-label">Data zwrotu</label>
                                        <input type="datetime-local" class="form-control" name="return_at" value="<?= htmlspecialchars($search['return_at'] ?? '') ?>">
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Dodatkowe usługi</label><br>
                                    <?php if ($addons): ?>
                                        <?php foreach ($addons as $i => $addon): ?>
                                            <div class="form-check form-check-inline mb-2">
                                                <input class="form-check-input" type="checkbox" name="extra[]" id="addon<?= $i ?>" value="<?= htmlspecialchars($addon['name']) ?>" data-price="<?= floatval($addon['price']) ?>" data-type="<?= htmlspecialchars($addon['charge_type']) ?>">
                                                <label class="form-check-label" for="addon<?= $i ?>">
                                                    <?= htmlspecialchars($addon['name']) ?>
                                                    <?php if ($addon['price'] !== null && $addon['price'] !== ''): ?>
                                                        <span class="text-muted small">(<?= number_format($addon['price'], 2) ?> zł<?= $addon['charge_type'] === 'per_day' ? ' / dzień' : ' / jednorazowo' ?>)</span>
                                                    <?php endif; ?>
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <span class="text-muted">Brak dostępnych dodatków.</span>
                                    <?php endif; ?>
                                </div>
                                <button type="submit" class="btn btn-success btn-lg w-100 mb-3">Rezerwuj</button>
                                <div class="price-box text-center mb-2">
                                        <?php if ($promoPrice !== null): ?>
                                            <span class="price-label text-danger fw-bold fs-5"><?= htmlspecialchars($promoLabel) ?></span>
                                            <span class="price-value text-danger fw-bold fs-5"><?= number_format($promoPrice, 2, ',', ' ') ?> PLN</span>
                                            <br>
                                            <span class="price-label text-muted small">Standardowa cena:</span>
                                            <span class="price-value text-muted small" style="font-size:0.95em"><s><?= number_format($product['price'], 2, ',', ' ') ?> PLN</s></span>
                                        
                                        <?php else: ?>
                                            <span class="price-label small">Cena:</span>
                                            <span class="price-value text-muted small"><s><?= number_format($product['price'], 2, ',', ' ') ?> PLN</s></span>
                                        
                                        <?php endif; ?>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
    </div>
    <!-- Główny opis poniżej karty na całą szerokość -->
    <div class="row mt-5">
        <div class="col-12">
            <div class="main-desc card p-4">
                <h3 class="mb-3">Opis pojazdu</h3>
                <p><?= htmlspecialchars($product['main_description'] ?? $product['description']) ?></p>
            </div>
        </div>
    </div>
</main>
<!-- Flatpickr JS CDN -->
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.js"></script>
<script src="/rental/assets/js/components/productFlatpickrCalendar.js"></script>
</main>
