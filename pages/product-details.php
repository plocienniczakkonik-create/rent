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
    <div class="card p-4 mb-4 shadow-lg">
        <?php // Ceny będą pobierane dynamicznie przez endpoint API w JS 
        ?>
        <style>
            /* FullCalendar: dni tygodnia i liczby dni na fioletowo/niebiesko */
            #product-calendar .fc-col-header-cell-cushion,
            #product-calendar .fc-daygrid-day-number {
                color: #667eea !important;
            }

            /* FullCalendar: przyciski na fioletowo/niebiesko */
            #product-calendar .fc-button,
            #product-calendar .fc-button-primary {
                background: #667eea !important;
                border-color: #667eea !important;
                color: #fff !important;
            }

            #product-calendar .fc-button-primary:not(:disabled):active,
            #product-calendar .fc-button-primary:not(:disabled):focus,
            #product-calendar .fc-button-primary:not(:disabled):hover {
                background: #764ba2 !important;
                border-color: #764ba2 !important;
                color: #fff !important;
            }

            /* Strzałki przewijania */
            #product-calendar .fc-prev-button,
            #product-calendar .fc-next-button {
                background: #667eea !important;
                border-color: #667eea !important;
                color: #fff !important;
            }

            /* Przycisk Dzisiaj bledszy fioletowy */
            #product-calendar .fc-today-button,
            #product-calendar .fc-button-primary.fc-today-button {
                background: #e6e6fa !important;
                border-color: #667eea !important;
                color: #667eea !important;
            }

            #product-calendar .fc-today-button:disabled,
            #product-calendar .fc-button-primary.fc-today-button:disabled {
                background: #e6e6fa !important;
                color: #667eea !important;
                opacity: 0.7;
            }

            /* Cena w komórce dnia */
            #product-calendar .fc-day-price {
                font-size: 0.85rem;
                margin-top: 2px;
                color: #667eea;
                font-weight: 600;
            }

            #product-calendar .fc-day-price.promo {
                color: #d32f2f;
            }
        </style>
        <!-- Sekcja 1: Zdjęcie + nazwa i parametry -->
        <div class="row g-4 align-items-start mb-4">
            <div class="col-12 col-lg-6">
                <?php $imgPath = ltrim($product['image_path'] ?? '', '/');
                $BASE = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
                // Pobierz klasę samochodu z vehicles
                $carClass = null;
                $stmtClass = $pdo->prepare('SELECT car_class FROM vehicles WHERE product_id = ? LIMIT 1');
                $stmtClass->execute([$product['id']]);
                $carClass = $stmtClass->fetchColumn();
                ?>
                <div style="position:relative;">
                    <img src="<?= $BASE ?>/<?= htmlspecialchars($imgPath) ?>" alt="<?= htmlspecialchars($product['name'] ?? '') ?>" class="img-fluid rounded shadow-sm w-100">
                    <?php if (!empty($carClass)): ?>
                        <span style="position:absolute;top:18px;right:18px;z-index:2;" class="badge bg-primary text-dark px-4 py-2 fs-6"><?= htmlspecialchars($carClass) ?></span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-12 col-lg-6 d-flex flex-column justify-content-start align-items-center">
                <div style="margin-top:2.5rem;"></div>
                <h1 class="mb-2 fw-bold fs-2" style="color: #23272b; text-align: center; width: 100%; padding-bottom: 1.2rem; margin-top:0.5rem;"><?= htmlspecialchars($product['name'] ?? '') ?></h1>
                <div class="badge-grid mb-2" style="width:100%; display:grid; grid-template-columns:repeat(2,1fr); gap:16px; justify-items:start; padding-top: 0.5rem; padding-bottom: 1.2rem;">
                    <span class="badge bg-light text-dark px-4 py-2 fs-5 d-flex align-items-center" style="font-size:1.15rem;min-width:120px; border:1px solid #764ba2; color:#23272b; background:#f3f4f6;">
                        <i class="bi bi-people-fill me-2"></i><?= (int)($product['seats'] ?? 0) ?> osobowy
                    </span>
                    <span class="badge bg-light text-dark px-4 py-2 fs-5 d-flex align-items-center" style="font-size:1.15rem;min-width:120px; border:1px solid #764ba2; color:#23272b; background:#f3f4f6;">
                        <i class="bi bi-fuel-pump-fill me-2"></i><?= htmlspecialchars($product['fuel'] ?? '') ?>
                    </span>
                    <span class="badge bg-light text-dark px-4 py-2 fs-5 d-flex align-items-center" style="font-size:1.15rem;min-width:120px; border:1px solid #764ba2; color:#23272b; background:#f3f4f6;">
                        <i class="bi bi-gear-fill me-2"></i><?= htmlspecialchars($product['gearbox'] ?? '') ?>
                    </span>
                    <span class="badge bg-light text-dark px-4 py-2 fs-5 d-flex align-items-center" style="font-size:1.15rem;min-width:120px; border:1px solid #764ba2; color:#23272b; background:#f3f4f6;">
                        <i class="bi bi-car-front-fill me-2"></i><?= !empty($product['car_type']) ? htmlspecialchars($product['car_type']) : 'Brak typu' ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Sekcja 2: Zalety -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="advantages card p-3 align-items-stretch d-flex flex-column justify-content-center" style="border: 1px solid #424343; background: #f8f9fa;">
                    <h5 class="mb-3" style="color: #667eea;">Dlaczego warto wybrać ten pojazd?</h5>
                    <div class="row">
                        <div class="col-12 col-md-6">
                            <ul class="list-unstyled mb-0">
                                <li><i class="bi bi-star-fill text-primary"></i> Niskie spalanie</li>
                                <li><i class="bi bi-star-fill text-primary"></i> Komfortowe wyposażenie</li>
                            </ul>
                        </div>
                        <div class="col-12 col-md-6">
                            <ul class="list-unstyled mb-0">
                                <li><i class="bi bi-shield-check text-primary"></i> Systemy bezpieczeństwa</li>
                                <li><i class="bi bi-gift text-primary"></i> Atrakcyjne warunki wynajmu</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sekcja 3: Kalendarz + Formularz rezerwacji -->
        <div class="row g-4 align-items-start mb-4">
            <div class="col-12 col-lg-6">
                <div class="card p-3 mb-4">
                    <h5 class="mb-3" style="color: #23272b;"><i class="bi bi-calendar3 me-2"></i>Kalendarz dostępności</h5>
                    <div id="product-calendar" style="min-height:500px; background:#f8fafc; border-radius:1rem; border:1px solid #e0e0e0; box-shadow:0 2px 8px rgba(0,0,0,0.04);"></div>
                </div>
            </div>
            <div class="col-12 col-lg-6">
                <div class="card p-4 h-100 d-flex flex-column justify-content-between">
                    <h4 class="mb-3" style="color: #23272b;"><i class="bi bi-journal-check me-2"></i>Formularz rezerwacji</h4>
                    <div id="product-reservation-form" class="d-flex flex-column justify-content-between h-100">
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
                                <div id="dateRangeError" class="text-danger small d-none">Uzupełnij wszystkie pola: miejsce odbioru, miejsce zwrotu, daty. Data zwrotu musi być późniejsza niż data odbioru.</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Dodatkowe usługi</label><br>
                                <style>
                                    .custom-checkbox {
                                        position: relative;
                                        display: inline-block;
                                        margin-right: 1.5rem;
                                        margin-bottom: 0.7rem;
                                    }

                                    .custom-checkbox input[type="checkbox"] {
                                        opacity: 0;
                                        position: absolute;
                                        left: 0;
                                        top: 0;
                                        width: 100%;
                                        height: 100%;
                                        cursor: pointer;
                                    }

                                    .custom-checkbox .checkmark {
                                        width: 24px;
                                        height: 24px;
                                        border-radius: 50%;
                                        border: 2px solid #667eea;
                                        background: #fff;
                                        display: inline-flex;
                                        align-items: center;
                                        justify-content: center;
                                        transition: border-color 0.2s;
                                        margin-right: 0.5rem;
                                    }

                                    .custom-checkbox input[type="checkbox"]:checked+.checkmark {
                                        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                                        border-color: #764ba2;
                                    }

                                    .custom-checkbox .checkmark i {
                                        color: #fff;
                                        font-size: 1.1rem;
                                        opacity: 0;
                                        transition: opacity 0.2s;
                                    }

                                    .custom-checkbox input[type="checkbox"]:checked+.checkmark i {
                                        opacity: 1;
                                    }

                                    .custom-checkbox .addon-label {
                                        font-weight: 500;
                                        color: #222;
                                    }

                                    .custom-checkbox .addon-desc {
                                        color: #888;
                                        font-size: 0.95rem;
                                        margin-left: 0.3rem;
                                    }
                                </style>
                                <?php if ($addons): ?>
                                    <?php foreach ($addons as $i => $addon): ?>
                                        <label class="custom-checkbox">
                                            <input class="addon-input" type="checkbox" name="extra[]" id="addon<?= $i ?>" value="<?= htmlspecialchars($addon['name']) ?>" data-price="<?= floatval($addon['price']) ?>" data-type="<?= htmlspecialchars($addon['charge_type']) ?>">
                                            <span class="checkmark"><i class="bi bi-check-lg"></i></span>
                                            <span class="addon-label"><?= htmlspecialchars($addon['name']) ?></span>
                                            <?php if ($addon['price'] !== null && $addon['price'] !== ''): ?>
                                                <span class="addon-desc">(<?= number_format($addon['price'], 2) ?> zł<?= $addon['charge_type'] === 'per_day' ? ' / dzień' : ' / jednorazowo' ?>)</span>
                                            <?php endif; ?>
                                        </label>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <span class="text-muted">Brak dostępnych dodatków.</span>
                                <?php endif; ?>
                            </div>
                            <div class="mt-auto">
                                <button type="button" id="reserveBtn" class="btn btn-theme btn-primary btn-lg w-100 mb-3">
                                    <i class="bi bi-calendar-check me-2"></i>Rezerwuj
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
                                        <span class="price-regular fw-semibold" style="color: #667eea; font-size: 2rem;"><?= number_format($standardPriceDirect, 2, ',', ' ') ?> PLN</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sekcja 4: Opis pojazdu -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card p-3">
                    <h5 class="mb-3" style="color: #23272b;"><i class="bi bi-card-text me-2"></i>Opis pojazdu</h5>
                    <p><?= nl2br(htmlspecialchars($product['description'] ?? '')) ?></p>
                </div>
            </div>
        </div>
    </div> <!-- KONIEC głównej karty -->
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

        function validateForm(showError = false) {
            const pickAt = pickAtEl?.value || '';
            const retAt = retAtEl?.value || '';
            const pickLoc = pickLocEl?.value || '';
            const dropLoc = dropLocEl?.value || '';
            const d1 = parseDt(pickAt);
            const d2 = parseDt(retAt);
            let allFilled = pickLoc && dropLoc && d1 && d2;
            let valid = allFilled && d2 > d1;

            // UI feedback: na żywo tylko jeśli wszystkie pola są wypełnione
            if (dateError) dateError.classList.toggle('d-none', valid || !allFilled);
            [pickLocEl, dropLocEl, pickAtEl, retAtEl].forEach(el => {
                if (!el) return;
                el.classList.toggle('is-invalid', showError ? !valid : (allFilled && !valid));
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
                const locationFee = Number(data.location_fee) || 0;
                const fleetEnabled = !!data.fleet_enabled;
                // Suma bez opłaty lokalizacyjnej
                const sumWithoutLocFee = promoApplied ? finalTotal - locationFee : baseTotal;
                if (promoApplied) {
                    html += '<span class="price-label">Suma:</span> ';
                    html += '<span class="price-value-final text-danger fw-bold fs-5">' + fmt(finalTotal) + ' PLN</span> ';
                    if (data.promo_label) {
                        html += '<span class="text-danger fw-semibold">(' + String(data.promo_label) + ')</span>';
                    }
                    if (fleetEnabled && locationFee > 0) {
                        html += '<br><span class="price-label text-primary">W tym opłata lokalizacyjna:</span> ';
                        html += '<span class="fw-bold text-primary">' + fmt(locationFee) + ' PLN</span>';
                    }
                    html += '<br><span class="price-label text-muted small">Cena standardowa za ' + days + ' dni + dodatki:</span> ';
                    html += '<span class="price-old text-muted text-decoration-line-through small">' + fmt(sumWithoutLocFee) + ' PLN</span>';
                } else {
                    html += '<span class="price-label">Suma:</span> ';
                    html += '<span class="price-value-old price-value">' + fmt(sumWithoutLocFee + locationFee) + ' PLN</span>';
                    if (fleetEnabled && locationFee > 0) {
                        html += '<br><span class="price-label text-primary">W tym opłata lokalizacyjna:</span> ';
                        html += '<span class="fw-bold text-primary">' + fmt(locationFee) + ' PLN</span>';
                    }
                }
                priceBox.innerHTML = html;
            } catch (e) {
                // fallback: nie blokuj UI
            }
            // Po przeliczeniu ceny zawsze waliduj formularz
            validateForm(false);
        }

        // Zmiany, które powinny przeliczać cenę
        ;
        [pickLocEl, dropLocEl, pickAtEl, retAtEl].forEach(el => {
            if (!el) return;
            const triggerRefresh = () => setTimeout(refreshPrice, 50);
            el.addEventListener('change', triggerRefresh);
            el.addEventListener('input', triggerRefresh);
            el.addEventListener('blur', triggerRefresh);
        });
        addons.forEach(ch => {
            ch.addEventListener('change', () => {
                refreshPrice();
            });
        });

        // Jawne wywołanie refreshPrice po załadowaniu strony
        document.addEventListener('DOMContentLoaded', function() {
            refreshPrice();
        });

        // Natychmiastowa walidacja na zmianę pól formularza
        [pickLocEl, dropLocEl, pickAtEl, retAtEl].forEach(el => {
            if (!el) return;
            el.addEventListener('change', refreshPrice);
            el.addEventListener('input', refreshPrice);
        });
        addons.forEach(ch => ch.addEventListener('change', refreshPrice));

        // Walidacja tylko ustawia stan przycisku i komunikaty, wywoływana po przeliczeniu ceny
        function refreshPriceAndValidate() {
            refreshPrice();
            validateForm(false);
        }
        addons.forEach(ch => ch.addEventListener('change', refreshPrice));

        // Event listener dla przycisku "Rezerwuj"
        if (reserveBtn) {
            reserveBtn.addEventListener('click', function(e) {
                e.preventDefault();
                // Walidacja tylko po kliknięciu
                if (!validateForm(true)) {
                    // Komunikat i podświetlenie tylko po kliknięciu
                    return;
                }
                // Przekierowanie na checkout z danymi formularza
                form.submit();
            });
        }

        // Początkowe przeliczenie
        validateForm(false);
        refreshPrice();
    })();
</script>