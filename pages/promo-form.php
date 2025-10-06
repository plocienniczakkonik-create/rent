<?php
// /pages/promo-form.php
require_once dirname(__DIR__) . '/auth/auth.php';
$staff = require_staff();

require_once dirname(__DIR__) . '/partials/head.php';
require_once dirname(__DIR__) . '/partials/header.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/_helpers.php';

$BASE = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$defaults = [
    'id'            => 0,
    'name'          => '',
    'code'          => '',
    'is_active'     => 1,
    'scope_type'    => 'global',   // global | product | category | pickup_location | return_location
    'scope_value'   => '[]',       // JSON array
    'valid_from'    => null,
    'valid_to'      => null,
    'min_days'      => 1,
    'discount_type' => 'percent',  // percent | amount
    'discount_val'  => '10.00',
];

$promo = $defaults;
if ($id > 0) {
    $stmt = db()->prepare("
    SELECT id, name, code, is_active, scope_type, scope_value,
           valid_from, valid_to, min_days, discount_type, discount_val
    FROM promotions WHERE id = ?
  ");
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        foreach ($promo as $k => $v) {
            if (array_key_exists($k, $row) && $row[$k] !== null) $promo[$k] = $row[$k];
        }
    }
}

// Zamień scope_value z DB (JSON lub pojedynczy string) na tablicę
$scopeArr = [];
$pickupLocationIds = [];
$returnLocationIds = [];
$productArr = [];
$categoryArr = [];

if ($promo['scope_value'] !== null && $promo['scope_value'] !== '') {
    $dec = json_decode((string)$promo['scope_value'], true);
    if (is_array($dec)) {
        // Sprawdź czy to nowa struktura kombinowana
        if (isset($dec['products']) || isset($dec['categories']) || isset($dec['pickup_location_ids']) || isset($dec['return_location_ids'])) {
            // Nowa struktura kombinowana
            $productArr = $dec['products'] ?? [];
            $categoryArr = $dec['categories'] ?? [];
            $pickupLocationIds = $dec['pickup_location_ids'] ?? [];
            $returnLocationIds = $dec['return_location_ids'] ?? [];
        } else {
            // Stara struktura - lista
            $scopeArr = $dec;
        }
    } elseif (is_string($promo['scope_value']) && $promo['scope_value'] !== '') {
        $scopeArr = [$promo['scope_value']];
    }
}

// === DYNAMICZNE LISTY ===
$products = db()->query("
  SELECT sku, name
  FROM products
  ORDER BY name ASC
")->fetchAll(PDO::FETCH_ASSOC);

$categories = db()->query("
  SELECT DISTINCT category
  FROM products
  WHERE category IS NOT NULL AND category <> ''
  ORDER BY category ASC
")->fetchAll(PDO::FETCH_COLUMN);

// Lokalizacje z bazy danych
$locations = db()->query("
  SELECT id, name, city, is_active
  FROM locations
  WHERE is_active = 1
  ORDER BY city ASC, name ASC
")->fetchAll(PDO::FETCH_ASSOC);

$scopeTypes = [
    'global'              => 'Wszystko (bez ograniczeń)',
    'product'             => 'Konkretne samochody',
    'category'            => 'Konkretne klasy pojazdów', 
    'product_pickup'      => 'Samochody + miejsca odbioru',
    'product_return'      => 'Samochody + miejsca zwrotu',
    'product_both'        => 'Samochody + miejsca odbioru i zwrotu',
    'category_pickup'     => 'Klasy + miejsca odbioru',
    'category_return'     => 'Klasy + miejsca zwrotu', 
    'category_both'       => 'Klasy + miejsca odbioru i zwrotu',
    'pickup_location'     => 'Tylko miejsca odbioru',
    'return_location'     => 'Tylko miejsca zwrotu',
    'both_locations'      => 'Konkretne miejsca odbioru i zwrotu',
];
?>
<main class="container-fluid py-4 d-flex align-items-center justify-content-center"
    style="min-height: calc(100vh - 72px); padding-top: 72px;">
    <div class="w-100" style="max-width: 900px; margin: 0 auto;">

        <h1 class="text-dark text-center mb-4 d-flex align-items-center justify-content-center">
            <i class="bi bi-tag-fill me-3" style="font-size: 1.5rem; color: #374151;"></i>
            <?= $id ? 'Edytuj promocję' : 'Nowa promocja' ?>
        </h1>

        <div class="card shadow-lg border-0" style="background: var(--gradient-primary); border-radius: 1rem; overflow: hidden;">
            <div class="card-header text-white d-flex align-items-center" style="background: rgba(255,255,255,0.1); border-bottom: 1px solid rgba(255,255,255,0.2); padding: 1.5rem;">
                <i class="bi bi-tag me-3" style="font-size: 1.25rem;"></i>
                <h4 class="mb-0"><?= $id ? 'Edycja promocji' : 'Dodaj nową promocję' ?></h4>
            </div>
            
            <div class="card-body p-0" style="background: white;">
                <form method="post" action="<?= $BASE ? ($BASE . '/pages/promo-save.php') : 'promo-save.php' ?>" class="p-4">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int)$promo['id'] ?>">

                    <!-- Sekcja: Podstawowe informacje -->
                    <div class="mb-4">
                        <div class="section-header d-flex align-items-center mb-2" style="background: #eef1f3; border-bottom: 1px solid #6b7280; border-radius: 0.5rem 0.5rem 0 0; padding: 0.75rem 1rem;">
                            <h5 class="mb-0 d-flex align-items-center">
                                <i class="bi bi-info-circle me-2"></i>
                                Podstawowe informacje
                            </h5>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-7">
                                <label class="form-label">Nazwa promocji <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" required maxlength="120"
                                    value="<?= htmlspecialchars($promo['name']) ?>" placeholder="np. Rabat na wypożyczenie jesienne">
                            </div>
                            <div class="col-md-5">
                                <label class="form-label">Status promocji</label>
                                <select name="is_active" class="form-select">
                                    <option value="1" <?= (int)$promo['is_active'] === 1 ? 'selected' : ''; ?>>
                                        <i class="bi bi-check-circle"></i> Aktywna
                                    </option>
                                    <option value="0" <?= (int)$promo['is_active'] === 0 ? 'selected' : ''; ?>>
                                        <i class="bi bi-x-circle"></i> Wyłączona
                                    </option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Sekcja: Kod kuponu -->
                    <div class="mb-4">
                        <div class="section-header d-flex align-items-center mb-2" style="background: #eef1f3; border-bottom: 1px solid #6b7280; border-radius: 0.5rem 0.5rem 0 0; padding: 0.75rem 1rem;">
                            <h5 class="mb-0 d-flex align-items-center">
                                <i class="bi bi-ticket-detailed me-2"></i>
                                Kod kuponu
                            </h5>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-5">
                                <label class="form-label">Wymaga kodu kuponu?</label>
                                <select id="requiresCode" class="form-select">
                                    <option value="0" <?= $promo['code'] ? '' : 'selected' ?>>Nie - promocja automatyczna</option>
                                    <option value="1" <?= $promo['code'] ? 'selected' : '' ?>>Tak - wymagany kod</option>
                                </select>
                            </div>
                            <div class="col-md-7" id="codeWrap" style="display: <?= $promo['code'] ? '' : 'none' ?>;">
                                <label class="form-label">Kod kuponu</label>
                                <input type="text" name="code" class="form-control" maxlength="40"
                                    value="<?= htmlspecialchars((string)$promo['code']) ?>" placeholder="np. JESIEN10">
                                <div class="form-text">Jeśli ustawisz kod, promocja zadziała tylko po jego wpisaniu przez klienta.</div>
                            </div>
                        </div>
                    </div>

                    <!-- Sekcja: Zakres działania -->
                    <div class="mb-4">
                        <div class="section-header d-flex align-items-center mb-2" style="background: #eef1f3; border-bottom: 1px solid #6b7280; border-radius: 0.5rem 0.5rem 0 0; padding: 0.75rem 1rem;">
                            <h5 class="mb-0 d-flex align-items-center">
                                <i class="bi bi-bullseye me-2"></i>
                                Zakres działania
                            </h5>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label">Zastosowanie promocji <span class="text-danger">*</span></label>
                                <select name="scope_type" id="scopeType" class="form-select" required>
                                    <?php foreach ($scopeTypes as $val => $label): ?>
                                        <option value="<?= $val ?>" <?= $promo['scope_type'] === $val ? 'selected' : ''; ?>><?= $label ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Samochód: multi-select -->
                            <div class="col-md-6" id="scopeProduct" style="display:none;">
                                <label class="form-label">Wybierz samochody</label>
                                <select name="scope_value_product[]" class="form-select" multiple size="6">
                                    <?php foreach ($products as $pr): ?>
                                        <?php
                                        $val = (string)$pr['sku'];
                                        $text = $pr['name'] . ' (' . $pr['sku'] . ')';
                                        $sel = in_array($val, array_merge($scopeArr, $productArr), true) ? 'selected' : '';
                                        ?>
                                        <option value="<?= htmlspecialchars($val) ?>" <?= $sel ?>><?= htmlspecialchars($text) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text">Przytrzymaj Ctrl, aby wybrać wiele pozycji</div>
                            </div>

                            <!-- Klasa: multi-select -->
                            <div class="col-md-6" id="scopeCategory" style="display:none;">
                                <label class="form-label">Wybierz klasy pojazdów</label>
                                <select name="scope_value_category[]" class="form-select" multiple size="6">
                                    <?php foreach ($categories as $c): ?>
                                        <?php $sel = in_array($c, array_merge($scopeArr, $categoryArr), true) ? 'selected' : ''; ?>
                                        <option value="<?= htmlspecialchars($c) ?>" <?= $sel ?>><?= htmlspecialchars($c) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text">Przytrzymaj Ctrl, aby wybrać wiele pozycji</div>
                            </div>

                            <!-- Miejsca odbioru -->
                            <div class="col-md-6" id="scopePickup" style="display:none;">
                                <label class="form-label">Miejsca odbioru</label>
                                <select name="scope_value_pickup_ids[]" class="form-select" multiple size="6">
                                    <?php foreach ($locations as $loc): ?>
                                        <?php 
                                        $locationId = (string)$loc['id'];
                                        $locationText = $loc['name'] . ' (' . $loc['city'] . ')';
                                        $sel = in_array($locationId, $pickupLocationIds, true) ? 'selected' : '';
                                        ?>
                                        <option value="<?= htmlspecialchars($locationId) ?>" <?= $sel ?>><?= htmlspecialchars($locationText) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text">Przytrzymaj Ctrl, aby wybrać wiele lokalizacji</div>
                            </div>

                            <!-- Miejsca zwrotu -->
                            <div class="col-md-6" id="scopeReturn" style="display:none;">
                                <label class="form-label">Miejsca zwrotu</label>
                                <select name="scope_value_return_ids[]" class="form-select" multiple size="6">
                                    <?php foreach ($locations as $loc): ?>
                                        <?php 
                                        $locationId = (string)$loc['id'];
                                        $locationText = $loc['name'] . ' (' . $loc['city'] . ')';
                                        $sel = in_array($locationId, $returnLocationIds, true) ? 'selected' : '';
                                        ?>
                                        <option value="<?= htmlspecialchars($locationId) ?>" <?= $sel ?>><?= htmlspecialchars($locationText) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text">Przytrzymaj Ctrl, aby wybrać wiele lokalizacji</div>
                            </div>
                        </div>
                    </div>

                    <!-- Sekcja: Okres obowiązywania -->
                    <div class="mb-4">
                        <div class="section-header d-flex align-items-center mb-2" style="background: #eef1f3; border-bottom: 1px solid #6b7280; border-radius: 0.5rem 0.5rem 0 0; padding: 0.75rem 1rem;">
                            <h5 class="mb-0 d-flex align-items-center">
                                <i class="bi bi-calendar-range me-2"></i>
                                Okres obowiązywania
                            </h5>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Obowiązuje od</label>
                                <input type="datetime-local" name="valid_from" class="form-control"
                                    value="<?= $promo['valid_from'] ? htmlspecialchars(date('Y-m-d\TH:i', strtotime($promo['valid_from']))) : '' ?>">
                                <div class="form-text">Pozostaw puste dla natychmiastowego uruchomienia</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Obowiązuje do</label>
                                <input type="datetime-local" name="valid_to" class="form-control"
                                    value="<?= $promo['valid_to'] ? htmlspecialchars(date('Y-m-d\TH:i', strtotime($promo['valid_to']))) : '' ?>">
                                <div class="form-text">Pozostaw puste dla promocji bez daty końcowej</div>
                            </div>
                        </div>
                    </div>

                    <!-- Sekcja: Warunki i rabat -->
                    <div class="mb-4">
                        <div class="section-header d-flex align-items-center mb-2" style="background: #eef1f3; border-bottom: 1px solid #6b7280; border-radius: 0.5rem 0.5rem 0 0; padding: 0.75rem 1rem;">
                            <h5 class="mb-0 d-flex align-items-center">
                                <i class="bi bi-percent me-2"></i>
                                Warunki i wartość rabatu
                            </h5>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Minimalna liczba dni <span class="text-danger">*</span></label>
                                <input type="number" name="min_days" class="form-control" min="1" max="365" step="1" required
                                    value="<?= (int)$promo['min_days'] ?>">
                                <div class="form-text">Promocja zostanie zastosowana od tego minimum dni wynajmu</div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Rodzaj rabatu <span class="text-danger">*</span></label>
                                <select name="discount_type" id="discountType" class="form-select" required>
                                    <option value="percent" <?= $promo['discount_type'] === 'percent' ? 'selected' : ''; ?>>Procentowy (%)</option>
                                    <option value="amount" <?= $promo['discount_type'] === 'amount' ? 'selected' : '';  ?>>Kwotowy (PLN)</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Wartość rabatu <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text" id="rabLabel"><?= $promo['discount_type'] === 'amount' ? 'PLN' : '%' ?></span>
                                    <input type="number" name="discount_val" class="form-control" min="0" step="0.01" required
                                        value="<?= htmlspecialchars($promo['discount_val']) ?>">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <button class="btn btn-primary" type="submit">
                            <i class="bi bi-check-lg me-2"></i>Zapisz promocję
                        </button>
                        <a class="btn btn-outline-secondary" href="<?= $BASE ?>/index.php?page=dashboard-staff#pane-promos">
                            <i class="bi bi-x-lg me-2"></i>Anuluj
                        </a>
                        <?php if ($id): ?>
                            <a class="btn btn-outline-danger ms-auto"
                                href="<?= $BASE ?>/pages/promo-delete.php?id=<?= (int)$promo['id'] ?>&csrf=<?= htmlspecialchars(csrf_token()) ?>"
                                onclick="return confirm('Usunąć promocję #<?= (int)$promo['id'] ?>?');">
                                <i class="bi bi-trash me-2"></i>Usuń
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>

<script>
    (function() {
        const scopeType = document.getElementById('scopeType');
        const requiresCode = document.getElementById('requiresCode');
        const codeWrap = document.getElementById('codeWrap');
        const rabLabel = document.getElementById('rabLabel');
        const discountType = document.getElementById('discountType');

        const show = id => document.getElementById(id).style.display = '';
        const hide = id => document.getElementById(id).style.display = 'none';

        function syncScope() {
            // Ukryj wszystkie sekcje
            ['scopeProduct', 'scopeCategory', 'scopePickup', 'scopeReturn'].forEach(hide);
            
            const t = scopeType.value;
            
            // Pokaż odpowiednie sekcje w zależności od typu
            if (t === 'product' || t.startsWith('product_')) {
                show('scopeProduct');
            }
            if (t === 'category' || t.startsWith('category_')) {
                show('scopeCategory');
            }
            if (t === 'pickup_location' || t.endsWith('_pickup') || t === 'both_locations' || t.endsWith('_both')) {
                show('scopePickup');
            }
            if (t === 'return_location' || t.endsWith('_return') || t === 'both_locations' || t.endsWith('_both')) {
                show('scopeReturn');
            }
        }

        function syncCode() {
            codeWrap.style.display = (requiresCode.value === '1') ? '' : 'none';
            if (requiresCode.value === '0') {
                const input = codeWrap.querySelector('input[name="code"]');
                if (input) input.value = '';
            }
        }

        function syncLabel() {
            rabLabel.textContent = discountType.value === 'amount' ? 'PLN' : '%';
        }

        scopeType.addEventListener('change', syncScope);
        requiresCode.addEventListener('change', syncCode);
        discountType.addEventListener('change', syncLabel);

        syncScope();
        syncCode();
        syncLabel();
    })();
</script>

<?php require_once dirname(__DIR__) . '/partials/footer.php'; ?>