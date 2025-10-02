<?php
// /pages/vehicle-form.php
require_once dirname(__DIR__) . '/auth/auth.php';
require_staff();

require_once dirname(__DIR__) . '/includes/db.php';
$db = db(); // <<< KLUCZ: pobieramy PDO i przypisujemy do $db

// dopiero po przygotowaniu $db wczytujemy head i header
require_once dirname(__DIR__) . '/partials/head.php';
require_once dirname(__DIR__) . '/partials/header.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
if (empty($_SESSION['_token'])) {
    $_SESSION['_token'] = bin2hex(random_bytes(32));
}
function csrf_field_local()
{
    echo '<input type="hidden" name="_token" value="' .
        htmlspecialchars($_SESSION['_token'] ?? '', ENT_QUOTES, 'UTF-8') .
        '">';
}

$BASE = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';


// --- Prefill z ?product_id=... (gdy wchodzimy z „Zarządzaj → Dodaj pojazd tego modelu”)
$prefill_product_id = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;

// --- Dane do selecta modeli (produkty)
// jeśli chcesz tylko aktywne: WHERE status='active'
$products = $db->query("SELECT id, name FROM products ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

// --- Tryb edycji?
$id   = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$edit = null;
if ($id > 0) {
    $st = $db->prepare("SELECT * FROM vehicles WHERE id = :id");
    $st->execute([':id' => $id]);
    $edit = $st->fetch(PDO::FETCH_ASSOC);
    if (!$edit) {
        http_response_code(404);
        echo '<div class="container py-5">Pojazd nie znaleziony.</div>';
        require_once dirname(__DIR__) . '/partials/footer.php';
        exit;
    }
}

/** Helper do wartości pól */
function old($key, $default = '')
{
    if (isset($_POST[$key])) {
        return htmlspecialchars((string)$_POST[$key]);
    }
    global $edit, $prefill_product_id;
    if ($edit && array_key_exists($key, $edit)) {
        return htmlspecialchars((string)$edit[$key]);
    }
    if ($key === 'product_id' && $prefill_product_id) {
        return (string)$prefill_product_id;
    }
    return htmlspecialchars((string)$default);
}

/** Ustalenie URL powrotu (Anuluj) */
$backProductId = $edit ? (int)$edit['product_id'] : ($prefill_product_id ?: 0);
$backUrl = $backProductId > 0
    ? $BASE . '/index.php?page=vehicles-manage&product=' . $backProductId
    : $BASE . '/index.php?page=vehicles';

?>
<div class="container py-4">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h1 class="h4 m-0"><?= $id ? 'Edytuj pojazd' : 'Dodaj pojazd' ?></h1>
    </div>

    <?php if (!empty($_GET['err'])): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($_GET['err']) ?></div>
    <?php endif; ?>

    <form class="card card-body" method="post" action="<?= $BASE ?>/pages/vehicle-save.php" novalidate>
        <?php csrf_field_local(); ?>
        <input type="hidden" name="id" value="<?= (int)$id ?>">

        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Model <span class="text-danger">*</span></label>
                <?php $currentProduct = old('product_id'); ?>
                <select name="product_id" class="form-select" required>
                    <option value="">Wybierz…</option>
                    <?php foreach ($products as $p): ?>
                        <option value="<?= (int)$p['id'] ?>" <?= $currentProduct === (string)$p['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($p['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="form-text">Wybierz model (produkt), do którego należy ten egzemplarz.</div>
            </div>

            <div class="col-md-6">
                <label class="form-label">Nr rejestracyjny <span class="text-danger">*</span></label>
                <input type="text" name="registration_number" class="form-control" required
                    placeholder="np. WX12345"
                    value="<?= old('registration_number') ?>">
                <div class="form-text">Musi być unikalny w całej flocie.</div>
            </div>

            <div class="col-md-6">
                <label class="form-label">VIN</label>
                <input type="text" name="vin" class="form-control" value="<?= old('vin') ?>">
            </div>

            <div class="col-md-3">
                <label class="form-label">Status <span class="text-danger">*</span></label>
                <?php $st = old('status', $edit['status'] ?? ''); ?>
                <select name="status" class="form-select" required>
                    <option value="">Wybierz…</option>
                    <?php
                    $statuses = [
                        'available'   => 'Dostępny',
                        'booked'      => 'Zarezerwowany',
                        'maintenance' => 'Serwis',
                        'unavailable' => 'Niedostępny',
                        'retired'     => 'Wycofany',
                    ];
                    foreach ($statuses as $k => $label) {
                        echo '<option value="' . $k . '" ' . ($st === $k ? 'selected' : '') . '>' . $label . '</option>';
                    }
                    ?>
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label">Przebieg [km]</label>
                <input type="number" name="mileage" min="0" step="1" class="form-control"
                    value="<?= old('mileage') ?>">
            </div>

            <div class="col-md-4">
                <label class="form-label">Lokalizacja</label>
                <input type="text" name="location" class="form-control" placeholder="np. Warszawa / Oddział A"
                    value="<?= old('location') ?>">
            </div>

            <div class="col-md-4">
                <label class="form-label">Data przeglądu</label>
                <input type="date" name="inspection_date" class="form-control"
                    value="<?= old('inspection_date') ?>">
            </div>

            <div class="col-md-4">
                <label class="form-label">Data końca ubezpieczenia</label>
                <input type="date" name="insurance_expiry_date" class="form-control"
                    value="<?= old('insurance_expiry_date') ?>">
            </div>

            <div class="col-12">
                <label class="form-label">Notatki</label>
                <textarea name="notes" class="form-control" rows="3"><?= old('notes') ?></textarea>
            </div>
        </div>

        <div class="d-flex gap-2 mt-4">
            <a href="<?= $backUrl ?>" class="btn btn-outline-secondary">Anuluj</a>
            <button class="btn btn-primary" type="submit">Zapisz</button>
        </div>
    </form>
</div>

<?php require_once dirname(__DIR__) . '/partials/footer.php'; ?>