<?php
// Strona szczegółów produktu (pojazdu) z nowoczesną kartą, kalendarzem i formularzem rezerwacji
// Dane $product, $search są przekazywane z reserve.php
require_once dirname(__DIR__) . '/includes/db.php';
require_once __DIR__ . '/includes/search.php'; // wspólna logika promocji
$pdo = db();

// Dodatki
$addons = [];
$stmt = $pdo->prepare("SELECT name, price, charge_type FROM dict_terms WHERE status='active' AND dict_type_id = (SELECT id FROM dict_types WHERE slug='addon' LIMIT 1) ORDER BY sort_order, name");
$stmt->execute();
$addons = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Lokalizacje - używaj nazw z dict_terms jako źródła prawdy
$locations = [];
$stmtLoc = $pdo->prepare("SELECT t.name FROM dict_terms t JOIN dict_types dt ON dt.id = t.dict_type_id WHERE dt.slug = 'location' AND t.status = 'active' ORDER BY t.sort_order ASC, t.name ASC");
$stmtLoc->execute();
$rowsLoc = $stmtLoc->fetchAll(PDO::FETCH_COLUMN);
if ($rowsLoc) {
    $locations = array_values(array_unique(array_map('strval', $rowsLoc)));
}
// Oblicz promocję dla tego produktu wyłącznie, gdy podano zakres dat (spójnie z listą wyników)
$basePrice   = isset($product['price']) ? (float)$product['price'] : 0.0;
$priceFinal  = $basePrice;
$standardPriceDirect = $basePrice;
$promoLabel  = '';

// Parametry z wyszukiwarki (jeśli istnieją)
$pickupStr  = isset($search['pickup_at']) ? (string)$search['pickup_at'] : '';
$returnStr  = isset($search['return_at']) ? (string)$search['return_at'] : '';
$pickup_location  = (string)($search['pickup_location'] ?? '');
$dropoff_location = (string)($search['dropoff_location'] ?? '');

// Promocje na stronie produktu pokazujemy dopiero po podaniu OBU dat (jak w search-results)
$applyPromos = ($pickupStr !== '' && $returnStr !== '');
if ($applyPromos) {
    $pickup_ts  = strtotime($pickupStr) ?: null;
    $dropoff_ts = strtotime($returnStr) ?: null;
    $rental_days = null;
    if ($pickup_ts && $dropoff_ts && $dropoff_ts > $pickup_ts) {
        $diff_hours  = max(1, (int)ceil(($dropoff_ts - $pickup_ts) / 3600));
        $rental_days = max(1, (int)ceil($diff_hours / 24));
    }

    // Pobierz aktywne promocje (z oknem czasowym) i nalicz
    $promos = fetch_active_promotions($pickup_ts, $dropoff_ts);
    [$final, $applied, $label] = apply_promotions_to_product(
        $product,
        $promos,
        $rental_days,
        $pickup_location,
        $dropoff_location
    );
    if ($applied && $final < $basePrice) {
        $priceFinal = $final;
        $promoLabel = $label ?: '';
    }
}
?>
<!-- head, doctype, html usunięte, renderowane tylko w index.php -->

<!-- XDSoft DateTimePicker CDN -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery-datetimepicker/2.5.20/jquery.datetimepicker.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-datetimepicker/2.5.20/jquery.datetimepicker.full.min.js"></script>
<main class="container product-details product-details-main">
    <?php // Ceny będą pobierane dynamicznie przez endpoint API w JS 
    ?>
    <style>
        /* FullCalendar: dni tygodnia i liczby dni na zielono (#146c43) */
        #product-calendar .fc-col-header-cell-cushion,
        #product-calendar .fc-daygrid-day-number {
            color: #146c43 !important;
        }

        /* FullCalendar: przyciski na zielono */
        #product-calendar .fc-button,
        #product-calendar .fc-button-primary {
            background: #146c43 !important;
            border-color: #146c43 !important;
            color: #fff !important;
        }

        #product-calendar .fc-button-primary:not(:disabled):active,
        #product-calendar .fc-button-primary:not(:disabled):focus,
        #product-calendar .fc-button-primary:not(:disabled):hover {
            background: #125a38 !important;
            border-color: #125a38 !important;
            color: #fff !important;
        }

        /* Strzałki przewijania */
        #product-calendar .fc-prev-button,
        #product-calendar .fc-next-button {
            background: #146c43 !important;
            border-color: #146c43 !important;
            color: #fff !important;
        }

        /* Przycisk Dzisiaj bledszy zielony */
        #product-calendar .fc-today-button,
        #product-calendar .fc-button-primary.fc-today-button {
            background: #e6f4ec !important;
            border-color: #146c43 !important;
            color: #146c43 !important;
        }

        #product-calendar .fc-today-button:disabled,
        #product-calendar .fc-button-primary.fc-today-button:disabled {
            background: #e6f4ec !important;
            color: #146c43 !important;
            opacity: 0.7;
        }

        /* Cena w komórce dnia */
        #product-calendar .fc-day-price {
            font-size: 0.85rem;
            margin-top: 2px;
            color: #146c43;
            font-weight: 600;
        }

        #product-calendar .fc-day-price.promo {
            color: #d32f2f;
        }
    </style>
    <!-- Pierwszy row: zdjęcie + nazwa i parametry -->
    <div class="row g-4 align-items-start mb-4">
        <div class="col-12 col-lg-6">
            <!-- Zdjęcie pojazdu -->
            <?php $imgPath = ltrim($product['image_path'] ?? '', '/');
            $BASE = defined('BASE_URL') ? rtrim(BASE_URL, '/') : ''; ?>
            <img src="<?= $BASE ?>/<?= htmlspecialchars($imgPath) ?>" alt="<?= htmlspecialchars($product['name'] ?? '') ?>" class="img-fluid rounded shadow-sm w-100">
        </div>
        <div class="col-12 col-lg-6 d-flex flex-column justify-content-start align-items-start">
            <!-- Nazwa i parametry -->
            <h1 class="mb-3"><?= htmlspecialchars($product['name'] ?? '') ?></h1>
            <div class="mb-3">
                <span class="badge bg-secondary me-2"><?= (int)($product['seats'] ?? 0) ?> osobowy</span>
                <span class="badge bg-info me-2"><?= htmlspecialchars($product['fuel'] ?? '') ?></span>
                <span class="badge bg-light text-dark me-2"><?= htmlspecialchars($product['gearbox'] ?? '') ?></span>
                <?php if (!empty($product['body_type'])): ?><span class="badge bg-light text-dark me-2"><?= htmlspecialchars($product['body_type']) ?></span><?php endif; ?>
            </div>
        </div>
    </div>

    <!-- RZĄD 2: Zalety na całą szerokość -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="advantages card p-3 align-items-stretch d-flex flex-column justify-content-center">
                <h5 class="mb-3">Dlaczego warto wybrać ten pojazd?</h5>
                <div class="row">
                    <div class="col-12 col-md-6">
                        <ul class="list-unstyled mb-0">
                            <li><i class="bi bi-check2-circle text-success"></i> Niskie spalanie</li>
                            <li><i class="bi bi-check2-circle text-success"></i> Komfortowe wyposażenie</li>
                        </ul>
                    </div>
                    <div class="col-12 col-md-6">
                        <ul class="list-unstyled mb-0">
                            <li><i class="bi bi-check2-circle text-success"></i> Systemy bezpieczeństwa</li>
                            <li><i class="bi bi-check2-circle text-success"></i> Atrakcyjne warunki wynajmu</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- RZĄD 3: Kalendarz + Formularz rezerwacji -->
    <div class="row g-4 align-items-start mb-4">
        <div class="col-12 col-lg-6">
            <div class="card p-3 mb-4">
                <h5 class="mb-3">Kalendarz dostępności</h5>
                <div id="product-calendar" style="min-height:500px; background:#f8fafc; border-radius:1rem; border:1px solid #e0e0e0; box-shadow:0 2px 8px rgba(0,0,0,0.04);"></div>
            </div>
        </div>
        <div class="col-12 col-lg-6">
            <div class="card p-4 h-100 d-flex flex-column justify-content-between">
                <h4 class="text-success mb-3">Formularz rezerwacji</h4>
                <div id="product-reservation-form">
                    <form id="reservationForm" method="post" action="<?= $BASE ?>/index.php?page=checkout">
                        <input type="hidden" name="sku" value="<?= htmlspecialchars($product['sku'] ?? '') ?>">
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
                        <div class="row g-3 mb-1">
                            <div class="col-12 col-lg-6">
                                <label class="form-label">Data odbioru</label>
                                <input type="text" class="form-select search-date" name="pickup_at" placeholder="Data odbioru" value="<?= htmlspecialchars($search['pickup_at'] ?? '') ?>" autocomplete="off" spellcheck="false" inputmode="none" readonly>
                            </div>
                            <div class="col-12 col-lg-6">
                                <label class="form-label">Data zwrotu</label>
                                <input type="text" class="form-select search-date" name="return_at" placeholder="Data zwrotu" value="<?= htmlspecialchars($search['return_at'] ?? '') ?>" autocomplete="off" spellcheck="false" inputmode="none" readonly>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div id="dateRangeError" class="text-danger small d-none">Uzupełnij obie daty, a data zwrotu musi być późniejsza niż data odbioru.</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Dodatkowe usługi</label><br>
                            <?php if ($addons): ?>
                                <?php foreach ($addons as $i => $addon): ?>
                                    <div class="form-check form-check-inline mb-2">
                                        <input class="form-check-input addon-input" type="checkbox" name="extra[]" id="addon<?= $i ?>" value="<?= htmlspecialchars($addon['name']) ?>" data-price="<?= floatval($addon['price']) ?>" data-type="<?= htmlspecialchars($addon['charge_type']) ?>">
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
                        <button type="button" id="reserveBtn" class="btn btn-theme btn-primary btn-lg w-100 mb-3">
                            <i class="bi bi-bag-plus me-2"></i>Dodaj do koszyka
                        </button>
                        <div class="price-box text-center mb-2" id="livePriceBox">
                            <?php if ($priceFinal < $standardPriceDirect && $promoLabel !== ''): ?>
                                <span class="price-label">Cena:</span>
                                <span class="price-value-final text-danger fw-bold fs-5"><?= number_format($priceFinal, 2, ',', ' ') ?> PLN</span>
                                <span class="text-danger fw-semibold">(<?= htmlspecialchars($promoLabel) ?>)</span>
                                <br>
                                <span class="price-label text-muted small">Standardowa cena:</span>
                                <span class="price-old text-muted text-decoration-line-through small">
                                    <?= number_format($standardPriceDirect, 2, ',', ' ') ?> PLN
                                </span>
                            <?php else: ?>
                                <span class="price-label">Cena:</span>
                                <span class="price-regular fw-semibold"><?= number_format($standardPriceDirect, 2, ',', ' ') ?> PLN</span>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- RZĄD 4: Opis pojazdu na całą szerokość -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card p-3">
                <h5 class="mb-3">Opis pojazdu</h5>
                <p><?= nl2br(htmlspecialchars($product['description'] ?? '')) ?></p>
            </div>
        </div>
    </div>
</main>

<!-- Inicjalizacja FullCalendar -->
<?php $BASE = defined('BASE_URL') ? rtrim(BASE_URL, '/') : ''; ?>
<script src="<?= $BASE ?>/assets/js/index.js"></script>
<script src="<?= $BASE ?>/assets/js/components/searchDateTimePicker.js"></script>
<script>
    (function() {
        const form = document.getElementById('reservationForm');
        if (!form) return;

        const sku = form.querySelector('input[name="sku"]').value;
        const pickLocEl = form.querySelector('select[name="pickup_location"]');
        const dropLocEl = form.querySelector('select[name="dropoff_location"]');
        const pickAtEl = form.querySelector('input[name="pickup_at"]');
        const retAtEl = form.querySelector('input[name="return_at"]');
        const addons = Array.from(form.querySelectorAll('.addon-input'));
        const priceBox = document.getElementById('livePriceBox');
        const dateError = document.getElementById('dateRangeError');
        const reserveBtn = document.getElementById('reserveBtn');

        function fmt(n) {
            return (Math.round(n * 100) / 100).toLocaleString('pl-PL', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }

        function computeAddons(days) {
            let add = 0;
            addons.forEach(ch => {
                if (!ch.checked) return;
                const price = parseFloat(ch.getAttribute('data-price') || '0') || 0;
                const type = ch.getAttribute('data-type') || 'once';
                if (type === 'per_day') add += price * Math.max(1, days);
                else add += price; // once
            });
            return add;
        }

        function parseDt(s) {
            // Oczekiwany format: YYYY-MM-DD HH:mm
            if (!s) return null;
            const m = s.match(/^(\d{4})-(\d{2})-(\d{2})\s(\d{2}):(\d{2})$/);
            if (!m) return null;
            const [_, Y, M, D, h, i] = m;
            const dt = new Date(Number(Y), Number(M) - 1, Number(D), Number(h), Number(i));
            return isNaN(dt.getTime()) ? null : dt;
        }

        function validateDates() {
            const pickAt = pickAtEl?.value || '';
            const retAt = retAtEl?.value || '';
            const d1 = parseDt(pickAt);
            const d2 = parseDt(retAt);
            let valid = true;
            if (!d1 || !d2 || d2 <= d1) valid = false;

            // UI feedback
            if (dateError) dateError.classList.toggle('d-none', valid);
            [pickAtEl, retAtEl].forEach(el => {
                if (!el) return;
                el.classList.toggle('is-invalid', !valid);
            });
            if (reserveBtn) reserveBtn.disabled = !valid;
            return valid;
        }

        async function refreshPrice() {
            try {
                const params = new URLSearchParams();
                params.set('sku', sku);
                const pickAt = pickAtEl?.value || '';
                const retAt = retAtEl?.value || '';
                const pickLoc = pickLocEl?.value || '';
                const dropLoc = dropLocEl?.value || '';
                if (pickAt) params.set('pickup_at', pickAt);
                if (retAt) params.set('return_at', retAt);
                if (pickLoc) params.set('pickup_location', pickLoc);
                if (dropLoc) params.set('dropoff_location', dropLoc);

                const res = await fetch('<?= $BASE ?>/pages/api/product-rate.php?' + params.toString(), {
                    cache: 'no-store'
                });
                if (!res.ok) throw new Error('HTTP ' + res.status);
                const data = await res.json();

                const perDayFinal = Number(data.per_day_final) || 0;
                const perDayBase = Number(data.per_day_base) || 0;
                const days = Math.max(1, Number(data.rental_days) || 1);

                const addonsTotal = computeAddons(days);
                const baseTotal = perDayBase * days + addonsTotal;
                const finalTotal = perDayFinal * days + addonsTotal;

                const promoApplied = !!data.promo_applied && perDayFinal < perDayBase && pickAt && retAt;

                let html = '';
                if (promoApplied) {
                    html += '<span class="price-label">Suma:</span> ';
                    html += '<span class="price-value-final text-danger fw-bold fs-5">' + fmt(finalTotal) + ' PLN</span> ';
                    if (data.promo_label) {
                        html += '<span class="text-danger fw-semibold">(' + String(data.promo_label) + ')</span>';
                    }
                    html += '<br><span class="price-label text-muted small">Cena standardowa za ' + days + ' dni + dodatki:</span> ';
                    html += '<span class="price-old text-muted text-decoration-line-through small">' + fmt(baseTotal) + ' PLN</span>';
                } else {
                    html += '<span class="price-label">Suma:</span> ';
                    html += '<span class="price-value-old price-value">' + fmt(baseTotal) + ' PLN</span>';
                }
                priceBox.innerHTML = html;
                // Po przeliczeniu sprawdź też walidację zakresu
                validateDates();
            } catch (e) {
                // fallback: nie blokuj UI
            }
        }

        // Zmiany, które powinny przeliczać cenę
        ;
        [pickLocEl, dropLocEl, pickAtEl, retAtEl].forEach(el => {
            if (!el) return;
            el.addEventListener('change', refreshPrice);
            el.addEventListener('input', refreshPrice);
        });
        addons.forEach(ch => ch.addEventListener('change', refreshPrice));

        // Waliduj także na zmianę dat
        ;
        [pickAtEl, retAtEl].forEach(el => {
            if (!el) return;
            el.addEventListener('change', validateDates);
            el.addEventListener('input', validateDates);
        });

        // Event listener dla przycisku "Dodaj do koszyka"
        if (reserveBtn) {
            reserveBtn.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Sprawdź czy formularz jest poprawnie wypełniony
                if (!validateDates()) {
                    alert('Proszę podać prawidłowe daty rezerwacji.');
                    return;
                }
                
                const pickAt = pickAtEl?.value || '';
                const retAt = retAtEl?.value || '';
                const pickLoc = pickLocEl?.value || '';
                const dropLoc = dropLocEl?.value || '';
                
                if (!pickAt || !retAt || !pickLoc || !dropLoc) {
                    alert('Proszę wypełnić wszystkie wymagane pola.');
                    return;
                }
                
                // Zbierz wybrane dodatki
                const selectedExtras = [];
                addons.forEach(ch => {
                    if (ch.checked) {
                        selectedExtras.push(ch.value);
                    }
                });
                
                // Dodaj do koszyka
                const cartItem = {
                    sku: sku,
                    name: '<?= htmlspecialchars($product['name'] ?? '') ?>',
                    price: <?= $basePrice ?>,
                    pickup_at: pickAt,
                    return_at: retAt,
                    pickup_location: pickLoc,
                    dropoff_location: dropLoc,
                    extras: selectedExtras
                };
                
                if (window.cartManager) {
                    window.cartManager.addToCart(cartItem);
                } else {
                    alert('System koszyka nie jest dostępny. Spróbuj ponownie.');
                }
            });
        }

        // Początkowe przeliczenie
        // Inicjalny stan: blokuj przycisk, dopóki zakres niepoprawny
        validateDates();
        refreshPrice();
    })();
</script>