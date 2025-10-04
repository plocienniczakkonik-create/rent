<?php
// /components/search-form.php

require_once dirname(__DIR__) . '/includes/db.php';

// Wspólny index z BASE_URL (niezależnie skąd include)
$BASE = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
$ROOT_INDEX = ($BASE ? $BASE . '/' : '') . 'index.php';

// Pozwalamy nadpisać ACTION z zewnątrz (opcjonalnie), ale domyślnie kierujemy na index.php
$__formAction = isset($SEARCH_FORM_ACTION) && $SEARCH_FORM_ACTION !== ''
    ? $SEARCH_FORM_ACTION
    : $ROOT_INDEX;

// GET-y do wstępnego zaznaczania
$pickupLoc   = $_GET['pickup_location']  ?? '';
$dropoffLoc  = $_GET['dropoff_location'] ?? '';
$pickupAt    = $_GET['pickup_at']        ?? '';
$returnAt    = $_GET['return_at']        ?? '';

$vehicleType = $_GET['vehicle_type']     ?? '';
$trans       = $_GET['transmission']     ?? '';
$seatsMin    = $_GET['seats_min']        ?? '';
$fuel        = $_GET['fuel']             ?? '';

$labelVehicle = [
    '' => 'Typ pojazdu',
    'economy' => 'Miejski/Economy',
    'compact' => 'Kompakt',
    'suv'     => 'SUV',
    'van'     => 'Van',
    'premium' => 'Premium',
];
$labelTrans = [
    '' => 'Skrzynia biegów',
    'manual' => 'Manualna',
    'automatic' => 'Automatyczna',
];
$labelSeats = [
    '' => 'Minimalna liczba miejsc',
    '2' => '2',
    '4' => '4',
    '5' => '5',
    '7' => '7',
    '9' => '9',
];
$labelFuel = [
    '' => 'Rodzaj paliwa',
    'benzyna' => 'Benzyna',
    'diesel' => 'Diesel',
    'hybryda' => 'Hybryda',
    'elektryczny' => 'Elektryczny',
];

/** ⬇️ Dynamiczne lokalizacje ze słownika 'location' (tylko aktywne) */
$locations = [];
try {
    $pdo = db();
    $stmt = $pdo->prepare("
        SELECT t.name
        FROM dict_terms t
        JOIN dict_types dt ON dt.id = t.dict_type_id
        WHERE dt.slug = :slug AND t.status = 'active'
        ORDER BY t.sort_order ASC, t.name ASC
    ");
    $stmt->execute([':slug' => 'location']);
    $rows = $stmt->fetchAll(PDO::FETCH_COLUMN);
    if ($rows) {
        // upewnijmy się, że to stringi i bez duplikatów
        $locations = array_values(array_unique(array_map('strval', $rows)));
    }
} catch (Throwable $e) {
    $locations = [];
}

$anyFilterOn = ($pickupLoc || $dropoffLoc || $pickupAt || $returnAt || $vehicleType || $trans || $seatsMin || $fuel);

// Link czyszczenia — zostajemy w tej samej „rodzinie”
$currentPage = $_GET['page'] ?? 'home';
$clearToPage = ($currentPage === 'search-results') ? 'search-results' : 'home';
$clearUrl = $ROOT_INDEX . '?page=' . $clearToPage;
?>
<!-- XDSoft DateTimePicker CDN -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery-datetimepicker/2.5.20/jquery.datetimepicker.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-datetimepicker/2.5.20/jquery.datetimepicker.full.min.js"></script>
<script src="<?= $BASE ?>/assets/js/components/searchDateTimePicker.js"></script>
<style>
    /* Kursor łapka dla wszystkich pól formularza */
    .search-wrapper .form-select,
    .search-wrapper input.search-date {
        cursor: pointer !important;
    }
</style>

<section aria-label="Wyszukiwarka" id="offer" class="wyszukiwarkaCL pt-4">
    <div class="container py-4 search-wrapper ">
        <div class="card p-3 p-md-4" style="border-radius:18px; box-shadow:0 6px 24px rgba(0,0,0,.08); border:1px solid rgba(0,0,0,.06); overflow: visible;">

            <!-- Akcja zawsze na index.php; stronę wyników wymuszamy ukrytym polem page=search-results -->
            <form id="search-form" action="<?= htmlspecialchars($__formAction) ?>" method="get" novalidate autocomplete="off">
                <input type="hidden" name="page" value="search-results">

                <div class="row g-3 align-items-end">
                    <div class="col-12 col-lg-3">
                        <label class="form-label mb-1">Miejsce odbioru</label>
                        <select class="form-select" name="pickup_location">
                            <option value="" <?= $pickupLoc === '' ? 'selected' : '' ?> disabled>Wybierz...</option>
                            <?php foreach ($locations as $loc): ?>
                                <option value="<?= htmlspecialchars($loc) ?>" <?= $pickupLoc === $loc ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($loc) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-12 col-lg-3">
                        <label class="form-label mb-1">Miejsce zwrotu</label>
                        <select class="form-select" id="dropoffLocation" name="dropoff_location">
                            <option value="" <?= $dropoffLoc === '' ? 'selected' : '' ?> disabled>Wybierz...</option>
                            <option value="To samo co odbiór" <?= $dropoffLoc === 'To samo co odbiór' ? 'selected' : '' ?>>To samo co odbiór</option>
                            <?php foreach ($locations as $loc): ?>
                                <option value="<?= htmlspecialchars($loc) ?>" <?= $dropoffLoc === $loc ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($loc) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-12 col-lg-3">
                        <label class="form-label mb-1">Data odbioru</label>
                        <input type="text" class="form-select search-date" name="pickup_at" value="<?= htmlspecialchars($pickupAt) ?>" placeholder="Data odbioru" autocomplete="off" spellcheck="false" inputmode="none" readonly>
                    </div>

                    <div class="col-12 col-lg-3">
                        <label class="form-label mb-1">Data zwrotu</label>
                        <input type="text" class="form-select search-date" name="return_at" value="<?= htmlspecialchars($returnAt) ?>" placeholder="Data zwrotu" autocomplete="off" spellcheck="false" inputmode="none" readonly>
                    </div>
                </div>

                <hr class="my-3" />

                <div class="d-flex align-items-center flex-wrap gap-2" style="overflow: visible;">
                    <div class="d-flex flex-wrap gap-2 flex-grow-1" style="overflow: visible;">

                        <!-- Typ pojazdu -->
                        <div class="dropdown" data-bs-display="static" style="overflow: visible;">
                            <button class="btn btn-light rounded-pill px-3 dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                <?= htmlspecialchars($labelVehicle[$vehicleType] ?? 'Typ pojazdu') ?>
                            </button>
                            <ul class="dropdown-menu shadow mt-2" style="position:absolute; inset:auto auto 0 0; transform:translateY(100%);">
                                <li><a class="dropdown-item" data-value="">Dowolny</a></li>
                                <li><a class="dropdown-item" data-value="economy">Miejski/Economy</a></li>
                                <li><a class="dropdown-item" data-value="compact">Kompakt</a></li>
                                <li><a class="dropdown-item" data-value="suv">SUV</a></li>
                                <li><a class="dropdown-item" data-value="van">Van</a></li>
                                <li><a class="dropdown-item" data-value="premium">Premium</a></li>
                            </ul>
                            <input type="hidden" name="vehicle_type" id="classHidden" value="<?= htmlspecialchars($vehicleType) ?>">
                        </div>

                        <!-- Skrzynia biegów -->
                        <div class="dropdown" data-bs-display="static" style="overflow: visible;">
                            <button class="btn btn-light rounded-pill px-3 dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                <?= htmlspecialchars($labelTrans[$trans] ?? 'Skrzynia biegów') ?>
                            </button>
                            <ul class="dropdown-menu shadow mt-2" style="position:absolute; inset:auto auto 0 0; transform:translateY(100%);">
                                <li><a class="dropdown-item" data-value="">Dowolna</a></li>
                                <li><a class="dropdown-item" data-value="manual">Manualna</a></li>
                                <li><a class="dropdown-item" data-value="automatic">Automatyczna</a></li>
                            </ul>
                            <input type="hidden" name="transmission" id="transHidden" value="<?= htmlspecialchars($trans) ?>">
                        </div>

                        <!-- Minimalna liczba miejsc -->
                        <div class="dropdown" data-bs-display="static" style="overflow: visible;">
                            <button class="btn btn-light rounded-pill px-3 dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                <?= htmlspecialchars($labelSeats[$seatsMin] ?? 'Minimalna liczba miejsc') ?>
                            </button>
                            <ul class="dropdown-menu shadow mt-2" style="position:absolute; inset:auto auto 0 0; transform:translateY(100%);">
                                <li><a class="dropdown-item" data-value="">Dowolna</a></li>
                                <li><a class="dropdown-item" data-value="2">2</a></li>
                                <li><a class="dropdown-item" data-value="4">4</a></li>
                                <li><a class="dropdown-item" data-value="5">5</a></li>
                                <li><a class="dropdown-item" data-value="7">7</a></li>
                                <li><a class="dropdown-item" data-value="9">9</a></li>
                            </ul>
                            <input type="hidden" name="seats_min" id="seatsHidden" value="<?= htmlspecialchars($seatsMin) ?>">
                        </div>

                        <!-- Rodzaj paliwa -->
                        <div class="dropdown" data-bs-display="static" style="overflow: visible;">
                            <button class="btn btn-light rounded-pill px-3 dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                <?= htmlspecialchars($labelFuel[$fuel] ?? 'Rodzaj paliwa') ?>
                            </button>
                            <ul class="dropdown-menu shadow mt-2" style="position:absolute; inset:auto auto 0 0; transform:translateY(100%);">
                                <li><a class="dropdown-item" data-value="">Dowolny</a></li>
                                <li><a class="dropdown-item" data-value="benzyna">Benzyna</a></li>
                                <li><a class="dropdown-item" data-value="diesel">Diesel</a></li>
                                <li><a class="dropdown-item" data-value="hybryda">Hybryda</a></li>
                                <li><a class="dropdown-item" data-value="elektryczny">Elektryczny</a></li>
                            </ul>
                            <input type="hidden" name="fuel" id="fuelHidden" value="<?= htmlspecialchars($fuel) ?>">
                        </div>
                    </div>

                    <!-- CLEAR + CTA -->
                    <?php if ($anyFilterOn): ?>
                        <a class="btn btn-outline-secondary rounded-pill px-3" href="<?= htmlspecialchars($clearUrl) ?>" title="Wyczyść filtry">Wyczyść filtry</a>
                    <?php endif; ?>

                    <button class="btn rounded-pill px-4 ms-auto" type="submit"
                        style="background:#188f45; border-color:#255b35; color:#fff; white-space:nowrap;">
                        Pokaż samochody
                    </button>
                </div>
            </form>
        </div>
    </div>
</section>

<script>
    // Obsługa dropdownów (bez przeładowań)
    document.querySelectorAll('.dropdown .dropdown-item').forEach(function(item) {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            var value = this.getAttribute('data-value') || '';
            var menu = this.closest('.dropdown');
            var btn = menu.querySelector('button');
            var input = menu.querySelector('input[type="hidden"]');
            if (input && btn) {
                input.value = value;
                btn.textContent = this.textContent.trim();
            }
        });
    });

    // „To samo co odbiór”
    document.getElementById('dropoffLocation')?.addEventListener('change', function() {
        if (this.value === 'To samo co odbiór') {
            var pick = document.querySelector('select[name="pickup_location"]');
            if (pick && pick.value) this.value = pick.value;
        }
    });
</script>