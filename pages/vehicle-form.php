<?php
// /pages/vehicle-form.php
require_once dirname(__DIR__) . '/auth/auth.php';
$staff = require_staff();

require_once dirname(__DIR__) . '/partials/head.php';
require_once dirname(__DIR__) . '/partials/header.php';
require_once dirname(__DIR__) . '/includes/db.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$BASE = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';

/** Prefill z ?product_id=... */
$prefill_product_id = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;

/** Dane do selecta modeli (produkty) */
$products = db()->query("SELECT id, name FROM products ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

/** Tryb edycji? */
$id   = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$edit = null;
if ($id > 0) {
    $st = db()->prepare("SELECT * FROM vehicles WHERE id = :id");
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

/** URL powrotu (Anuluj) – dashboard-staff, zakładka floty */
$backProductId = $edit ? (int)$edit['product_id'] : ($prefill_product_id ?: 0);
$backUrl = $BASE . '/index.php?page=dashboard-staff' . ($backProductId > 0 ? ('&product=' . $backProductId) : '') . '#pane-vehicles';
?>
<div class="container py-4">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h1 class="h4 m-0"><?= $id ? 'Edytuj pojazd' : 'Dodaj pojazd' ?></h1>
    </div>

    <?php
    // FLASH z back-endu
    if (!empty($_SESSION['flash_ok'])) {
        // Ukryj komunikat usunięcia egzemplarza w widoku edycji
        if (strpos($_SESSION['flash_ok'], 'został usunięty') === false) {
            echo '<div class="alert alert-success">' . htmlspecialchars($_SESSION['flash_ok']) . '</div>';
        }
        unset($_SESSION['flash_ok']);
    }
    if (!empty($_SESSION['flash_error'])) {
        echo '<div class="alert alert-danger">' . htmlspecialchars($_SESSION['flash_error']) . '</div>';
        unset($_SESSION['flash_error']);
    }
    ?>

    <!-- walidacja Bootstrap (JS) + HTML5 patterny -->
    <form class="card card-body needs-validation" method="post" action="<?= $BASE ?>/index.php?page=vehicle-save" novalidate>
        <?= csrf_field() ?>
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
                <div class="invalid-feedback">Wybierz model (produkt).</div>
                <div class="form-text">Wybierz model (produkt), do którego należy ten egzemplarz.</div>
            </div>

            <div class="col-md-6">
                <label class="form-label">Nr rejestracyjny <span class="text-danger">*</span></label>
                <input
                    type="text"
                    name="registration_number"
                    class="form-control"
                    required
                    minlength="3"
                    maxlength="20"
                    placeholder="np. WX12345"
                    value="<?= old('registration_number') ?>">
                <div class="invalid-feedback">Podaj numer rejestracyjny.</div>
                <div class="form-text">Musi być unikalny w całej flocie.</div>
            </div>

            <div class="col-md-6">
                <label class="form-label">VIN</label>
                <input
                    type="text"
                    name="vin"
                    class="form-control"
                    inputmode="text"
                    autocomplete="off"
                    spellcheck="false"
                    maxlength="17"
                    pattern="^[A-HJ-NPR-Z0-9]{17}$"
                    placeholder="17 znaków (bez I, O, Q)"
                    value="<?= old('vin') ?>">
                <div class="invalid-feedback">VIN musi mieć 17 znaków (A–H, J–N, P, R–Z, 0–9), bez I, O, Q.</div>
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
                <div class="invalid-feedback">Wybierz status pojazdu.</div>
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
            <a href="<?= $BASE ?>/index.php?page=vehicle-detail&id=<?= (int)$id ?>" class="btn btn-outline-secondary">Anuluj</a>
            <button class="btn btn-primary" type="submit">Zapisz</button>
        </div>
    </form>
</div>

<script>
    /* Bootstrap-like walidacja + normalizacja pól */
    (function() {
        const form = document.querySelector('.needs-validation');

        const normalizeUpper = (el) => {
            if (!el) return;
            el.addEventListener('input', () => {
                const pos = el.selectionStart;
                el.value = el.value.toUpperCase();
                el.setSelectionRange(pos, pos);
            });
        };

        const vinInput = form.querySelector('input[name="vin"]');
        const regInput = form.querySelector('input[name="registration_number"]');

        if (vinInput) {
            vinInput.addEventListener('input', () => {
                // wytnij spacje, na upper
                const raw = vinInput.value.replace(/\s+/g, '');
                vinInput.value = raw.toUpperCase();
            });
            normalizeUpper(vinInput);
        }
        if (regInput) normalizeUpper(regInput);

        form.addEventListener('submit', function(e) {
            // bramkuj HTML5
            if (!form.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    })();
</script>

<?php require_once dirname(__DIR__) . '/partials/footer.php'; ?>