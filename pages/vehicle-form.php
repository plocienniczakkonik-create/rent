
<?php
require_once dirname(__DIR__) . '/auth/auth.php';
require_once dirname(__DIR__) . '/partials/head.php';
require_once dirname(__DIR__) . '/partials/header.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/theme-config.php';
require_once dirname(__DIR__) . '/includes/_helpers.php';
require_once dirname(__DIR__) . '/includes/vehicle-location-manager.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$BASE = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
$prefill_product_id = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;
$products = db()->query("SELECT id, name FROM products ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
$id   = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$edit = null;
$currentLocation = null;
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
    $currentLocation = VehicleLocationManager::getCurrentLocation($id);
}
$allLocations = VehicleLocationManager::getAllLocations();
function old($key, $default = '') {
    if (isset($_POST[$key])) return htmlspecialchars((string)$_POST[$key]);
    global $edit, $prefill_product_id;
    if ($edit && array_key_exists($key, $edit)) return htmlspecialchars((string)$edit[$key]);
    if ($key === 'product_id' && $prefill_product_id) return (string)$prefill_product_id;
    return htmlspecialchars((string)$default);
}
$backProductId = $edit ? (int)$edit['product_id'] : ($prefill_product_id ?: 0);
$backUrl = $BASE . '/index.php?page=dashboard-staff' . ($backProductId > 0 ? ('&product=' . $backProductId) : '') . '#pane-vehicles';
?>
<div class="container py-4">
    <div class="d-flex justify-content-center">
        <div class="card-product-form" style="background: #fff; border-radius: 1.25rem; box-shadow: 0 4px 24px rgba(0,0,0,0.07); padding: 2.5rem 2rem; max-width: 900px; width: 100%; margin: 0 auto;">
            <div class="card-header mb-4" style="background: var(--gradient-primary); color: white; border-radius: 0.75rem 0.75rem 0 0; border-bottom: 1px solid #e5e7eb; padding: 1.25rem 1.5rem;">
                <div class="d-flex flex-wrap align-items-center justify-content-between">
                    <h4 class="mb-0">
                        <i class="bi bi-car-front-fill me-2"></i><?php echo $id ? 'Edytuj pojazd' : 'Dodaj pojazd'; ?>
                    </h4>
                    <?php if ($id): ?>
                        <div class="d-flex gap-2 align-items-center">
                            <span class="badge fs-6" style="background: #f3f4f6; color: #374151; border: 1px solid #e5e7eb;">
                                ID: <?php echo $id; ?>
                            </span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php
            if (!empty($_SESSION['flash_ok'])) {
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
            <form class="needs-validation" method="post" action="<?= $BASE ?>/index.php?page=vehicle-save" novalidate>
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int)$id ?>">
                <!-- Sekcja: Podstawowe informacje -->
                <div class="mb-4">
                    <div class="section-header d-flex align-items-center mb-2" style="background: #eef1f3; border-bottom: 1px solid #6b7280; border-radius: 0.5rem 0.5rem 0 0; padding: 0.75rem 1rem;">
                        <h5 class="mb-0 d-flex align-items-center">
                            <i class="fas fa-info-circle me-2"></i>
                            Podstawowe informacje
                        </h5>
                    </div>
                    <div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Model <span class="text-danger">*</span></label>
                                <?php $currentProduct = old('product_id'); ?>
                                <select name="product_id" class="form-select" required>
                                    <option value="">Wybierz…</option>
                                    <?php foreach ($products as $p): ?>
                                        <option value="<?= (int)$p['id'] ?>" <?= $currentProduct == (string)$p['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($p['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="invalid-feedback">Wybierz model (produkt).</div>
                                <div class="form-text">Wybierz model (produkt), do którego należy ten egzemplarz.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nr rejestracyjny <span class="text-danger">*</span></label>
                                <input type="text" name="registration_number" class="form-control" required minlength="3" maxlength="20" placeholder="np. WX12345" value="<?= old('registration_number') ?>">
                                <div class="invalid-feedback">Podaj numer rejestracyjny.</div>
                                <div class="form-text">Musi być unikalny w całej flocie.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">VIN</label>
                                <input type="text" name="vin" class="form-control" inputmode="text" autocomplete="off" spellcheck="false" maxlength="17" pattern="^[A-HJ-NPR-Z0-9]{17}$" placeholder="17 znaków (bez I, O, Q)" value="<?= old('vin') ?>">
                                <div class="invalid-feedback">VIN musi mieć 17 znaków (A–H, J–N, P, R–Z, 0–9), bez I, O, Q.</div>
                            </div>
                            <div class="col-md-6">
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
                        </div>
                    </div>
                </div>
                <!-- Sekcja: Stan i lokalizacja -->
                <div class="mb-4">
                    <div class="section-header d-flex align-items-center mb-2" style="background: #eef1f3; border-bottom: 1px solid #6b7280; border-radius: 0.5rem 0.5rem 0 0; padding: 0.75rem 1rem;">
                        <h5 class="mb-0 d-flex align-items-center">
                            <i class="fas fa-tachometer-alt me-2"></i>
                            Stan i lokalizacja
                        </h5>
                    </div>
                    <div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Przebieg [km]</label>
                                <input type="number" name="mileage" min="0" step="1" class="form-control" value="<?= old('mileage') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Lokalizacja w systemie flotowym</label>
                                <?php if ($id > 0): ?>
                                    <div class="form-control-plaintext">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div>
                                                <?= VehicleLocationManager::formatLocationDisplay($currentLocation) ?>
                                                <?php if ($currentLocation && $currentLocation['location_id']): ?>
                                                    <div class="small text-muted">
                                                        <i class="bi bi-building me-1"></i>ID: <?= $currentLocation['location_id'] ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="window.open('<?= $BASE ?>/index.php?page=vehicle-detail&id=<?= $id ?>', '_blank')">
                                                <i class="bi bi-arrow-left-right me-1"></i>Zmień
                                            </button>
                                        </div>
                                    </div>
                                    <div class="form-text">
                                        <i class="bi bi-info-circle me-1"></i>
                                        Lokalizacja jest zarządzana automatycznie przez system flotowy. Aby ją zmienić, użyj przycisku powyżej.
                                    </div>
                                <?php else: ?>
                                    <select name="location_id" class="form-select">
                                        <option value="">Wybierz lokalizację...</option>
                                        <?php foreach ($allLocations as $loc): ?>
                                            <option value="<?= $loc['id'] ?>">
                                                <?= htmlspecialchars($loc['name'] . ' - ' . $loc['city']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="form-text">
                                        <i class="bi bi-geo-alt me-1"></i>
                                        Wybierz początkową lokalizację pojazdu w systemie flotowym
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Sekcja: Terminy -->
                <div class="mb-4">
                    <div class="section-header d-flex align-items-center mb-2" style="background: #eef1f3; border-bottom: 1px solid #6b7280; border-radius: 0.5rem 0.5rem 0 0; padding: 0.75rem 1rem;">
                        <h5 class="mb-0 d-flex align-items-center">
                            <i class="fas fa-calendar-alt me-2"></i>
                            Terminy
                        </h5>
                    </div>
                    <div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Data przeglądu</label>
                                <input type="date" name="inspection_date" class="form-control" value="<?= old('inspection_date') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Data końca ubezpieczenia</label>
                                <input type="date" name="insurance_expiry_date" class="form-control" value="<?= old('insurance_expiry_date') ?>">
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Sekcja: Notatki -->
                <div class="mb-4">
                    <div class="section-header d-flex align-items-center mb-2" style="background: #eef1f3; border-bottom: 1px solid #6b7280; border-radius: 0.5rem 0.5rem 0 0; padding: 0.75rem 1rem;">
                        <h5 class="mb-0 d-flex align-items-center">
                            <i class="fas fa-sticky-note me-2"></i>
                            Notatki
                        </h5>
                    </div>
                    <div>
                        <label class="form-label">Notatki</label>
                        <textarea name="notes" class="form-control" rows="3" placeholder="Dodatkowe informacje o pojeździe..."><?= old('notes') ?></textarea>
                    </div>
                </div>
                <div class="d-flex gap-2 mt-4">
                    <a href="<?= $backUrl ?>" class="btn btn-outline-secondary">Anuluj</a>
                    <button class="btn btn-primary" type="submit">Zapisz</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
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
                const raw = vinInput.value.replace(/\s+/g, '');
                vinInput.value = raw.toUpperCase();
            });
            normalizeUpper(vinInput);
        }
        if (regInput) normalizeUpper(regInput);
        form.addEventListener('submit', function(e) {
            if (!form.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    })();
</script>
<?php require_once dirname(__DIR__) . '/partials/footer.php'; ?>

<?php require_once dirname(__DIR__) . '/partials/footer.php'; ?>