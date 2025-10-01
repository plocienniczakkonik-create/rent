<?php
// /pages/promo-form.php
require_once dirname(__DIR__) . '/auth/auth.php';
$staff = require_staff();

require_once dirname(__DIR__) . '/partials/head.php';
require_once dirname(__DIR__) . '/partials/header.php';
require_once dirname(__DIR__) . '/includes/db.php';

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
if ($promo['scope_value'] !== null && $promo['scope_value'] !== '') {
    $dec = json_decode((string)$promo['scope_value'], true);
    if (is_array($dec)) {
        $scopeArr = $dec;
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

$scopeTypes = [
    'global'          => 'Wszystko',
    'product'         => 'Samochód',
    'category'        => 'Klasa',
    'pickup_location' => 'Miejsce odbioru',
    'return_location' => 'Miejsce zwrotu',
];
?>
<main class="container-md py-4 d-flex align-items-center justify-content-center"
    style="min-height: calc(100vh - 72px); padding-top: 72px;">
    <div class="w-100" style="max-width: 860px; margin: 0 auto;">

        <h1 class="h4 mb-3"><?= $id ? 'Edytuj promocję' : 'Nowa promocja' ?></h1>

        <form method="post" action="<?= $BASE ? ($BASE . '/pages/promo-save.php') : 'promo-save.php' ?>" class="card p-3 shadow-sm">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int)$promo['id'] ?>">

            <div class="row g-3">
                <div class="col-md-7">
                    <label class="form-label">Nazwa promocji</label>
                    <input type="text" name="name" class="form-control" required maxlength="120"
                        value="<?= htmlspecialchars($promo['name']) ?>">
                </div>
                <div class="col-md-5">
                    <label class="form-label">Wymaga kodu kuponu?</label>
                    <select id="requiresCode" class="form-select">
                        <option value="0" <?= $promo['code'] ? '' : 'selected' ?>>Nie</option>
                        <option value="1" <?= $promo['code'] ? 'selected' : '' ?>>Tak</option>
                    </select>
                </div>
                <div class="col-md-5" id="codeWrap" style="display: <?= $promo['code'] ? '' : 'none' ?>;">
                    <label class="form-label">Kod kuponu</label>
                    <input type="text" name="code" class="form-control" maxlength="40"
                        value="<?= htmlspecialchars((string)$promo['code']) ?>" placeholder="np. JESIEN10">
                    <div class="form-text">Jeśli ustawisz kod, promocja zadziała tylko po jego wpisaniu przez klienta.</div>
                </div>
            </div>

            <div class="row g-3 mt-1">
                <div class="col-md-6">
                    <label class="form-label">Zakres działania</label>
                    <select name="scope_type" id="scopeType" class="form-select" required>
                        <?php foreach ($scopeTypes as $val => $label): ?>
                            <option value="<?= $val ?>" <?= $promo['scope_type'] === $val ? 'selected' : ''; ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Samochód: multi-select (value = SKU, label = nazwa + SKU) -->
                <div class="col-md-6" id="scopeProduct" style="display:none;">
                    <label class="form-label">Samochód (możesz wybrać wiele)</label>
                    <select name="scope_value_product[]" class="form-select" multiple size="6">
                        <?php foreach ($products as $pr): ?>
                            <?php
                            $val = (string)$pr['sku'];
                            $text = $pr['name'] . ' (' . $pr['sku'] . ')';
                            $sel = in_array($val, $scopeArr, true) ? 'selected' : '';
                            ?>
                            <option value="<?= htmlspecialchars($val) ?>" <?= $sel ?>><?= htmlspecialchars($text) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Klasa: multi-select (DISTINCT z DB) -->
                <div class="col-md-6" id="scopeCategory" style="display:none;">
                    <label class="form-label">Klasa (możesz wybrać wiele)</label>
                    <select name="scope_value_category[]" class="form-select" multiple size="6">
                        <?php foreach ($categories as $c): ?>
                            <?php $sel = in_array($c, $scopeArr, true) ? 'selected' : ''; ?>
                            <option value="<?= htmlspecialchars($c) ?>" <?= $sel ?>><?= htmlspecialchars($c) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Odbiór: multi-string (comma-to-tags UX bez JS – na razie multi-line) -->
                <div class="col-md-6" id="scopePickup" style="display:none;">
                    <label class="form-label">Miejsce odbioru (wiele, po jednym w wierszu)</label>
                    <textarea name="scope_value_pickup" class="form-control" rows="4"
                        placeholder="np. Warszawa Lotnisko&#10;Kraków Dworzec"><?=
                                                                                $promo['scope_type'] === 'pickup_location' ? htmlspecialchars(implode("\n", $scopeArr)) : '' ?></textarea>
                </div>

                <!-- Zwrot: multi-string -->
                <div class="col-md-6" id="scopeReturn" style="display:none;">
                    <label class="form-label">Miejsce zwrotu (wiele, po jednym w wierszu)</label>
                    <textarea name="scope_value_return" class="form-control" rows="4"
                        placeholder="np. Warszawa Lotnisko&#10;Gdańsk Centrum"><?=
                                                                                $promo['scope_type'] === 'return_location' ? htmlspecialchars(implode("\n", $scopeArr)) : '' ?></textarea>
                </div>
            </div>

            <!-- Daty obowiązywania -->
            <div class="row g-3 mt-1">
                <div class="col-md-6">
                    <label class="form-label">Obowiązuje od</label>
                    <input type="datetime-local" name="valid_from" class="form-control"
                        value="<?= $promo['valid_from'] ? htmlspecialchars(date('Y-m-d\TH:i', strtotime($promo['valid_from']))) : '' ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Obowiązuje do</label>
                    <input type="datetime-local" name="valid_to" class="form-control"
                        value="<?= $promo['valid_to'] ? htmlspecialchars(date('Y-m-d\TH:i', strtotime($promo['valid_to']))) : '' ?>">
                </div>
            </div>

            <div class="row g-3 mt-1">
                <div class="col-md-6">
                    <label class="form-label">Minimalna liczba dni</label>
                    <input type="number" name="min_days" class="form-control" min="1" max="10" step="1" required
                        value="<?= (int)$promo['min_days'] ?>">
                </div>

                <div class="col-md-3">
                    <label class="form-label">Rodzaj rabatu</label>
                    <select name="discount_type" id="discountType" class="form-select" required>
                        <option value="percent" <?= $promo['discount_type'] === 'percent' ? 'selected' : ''; ?>>Procentowy</option>
                        <option value="amount" <?= $promo['discount_type'] === 'amount' ? 'selected' : '';  ?>>Kwotowy</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Wartość</label>
                    <div class="input-group">
                        <span class="input-group-text" id="rabLabel"><?= $promo['discount_type'] === 'amount' ? 'PLN' : '%' ?></span>
                        <input type="number" name="discount_val" class="form-control" min="0" step="0.01" required
                            value="<?= htmlspecialchars($promo['discount_val']) ?>">
                    </div>
                </div>
            </div>

            <div class="mt-3">
                <label class="form-label">Status</label>
                <select name="is_active" class="form-select">
                    <option value="1" <?= (int)$promo['is_active'] === 1 ? 'selected' : ''; ?>>Aktywna</option>
                    <option value="0" <?= (int)$promo['is_active'] === 0 ? 'selected' : ''; ?>>Wyłączona</option>
                </select>
            </div>

            <div class="d-flex gap-2 mt-3">
                <button class="btn btn-primary" type="submit">Zapisz</button>
                <a class="btn btn-outline-secondary" href="<?= $BASE ?>/index.php?page=dashboard-staff#pane-promos">Anuluj</a>
                <?php if ($id): ?>
                    <a class="btn btn-outline-danger ms-auto"
                        href="<?= $BASE ?>/pages/promo-delete.php?id=<?= (int)$promo['id'] ?>&csrf=<?= htmlspecialchars(csrf_token()) ?>"
                        onclick="return confirm('Usunąć promocję #<?= (int)$promo['id'] ?>?');">Usuń</a>
                <?php endif; ?>
            </div>
        </form>
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
            ['scopeProduct', 'scopeCategory', 'scopePickup', 'scopeReturn'].forEach(hide);
            const t = scopeType.value;
            if (t === 'product') show('scopeProduct');
            if (t === 'category') show('scopeCategory');
            if (t === 'pickup_location') show('scopePickup');
            if (t === 'return_location') show('scopeReturn');
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