<?php
// /pages/product-form.php
require_once dirname(__DIR__) . '/auth/auth.php';
$staff = require_staff();

require_once dirname(__DIR__) . '/partials/head.php';
require_once dirname(__DIR__) . '/partials/header.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/_helpers.php';

// Initialize i18n
require_once dirname(__DIR__) . '/includes/i18n.php';
i18n::init();

$BASE = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Domyślne wartości (gdy nowy produkt) — selekty puste, żeby wymusić świadomy wybór
$product = [
    'id'              => 0,
    'name'            => '',
    'sku'             => '',
    'price'           => '0.00',
    'price_unit'      => '',              // ⬅️ było: 'per_day'
    'status'          => 'active',        // zostawiamy domyślnie active
    'category'        => '',              // ⬅️ było: 'Klasa A'
    'car_type'        => '',              // typ samochodu
    'seats'           => '',              // ⬅️ było: 5
    'doors'           => '',              // ⬅️ było: 4
    'gearbox'         => '',              // ⬅️ było: 'Manualna'
    'fuel'            => '',              // ⬅️ było: 'Benzyna'
    'description'     => '',
    'image_path'      => null,
    'deposit_enabled' => 0,
    'deposit_type'    => 'fixed',
    'deposit_amount'  => '0.00',
];

// Jeśli edycja – pobierz co się da
if ($id > 0) {
    $stmt = db()->prepare("
        SELECT
          id, name, sku, price, status,
          category, car_type, seats, doors, gearbox, fuel, price_unit, description, image_path,
          deposit_enabled, deposit_type, deposit_amount
        FROM products
        WHERE id = ?
    ");
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

    foreach ($product as $key => $def) {
        if (array_key_exists($key, $row) && $row[$key] !== null) {
            $product[$key] = $row[$key];
        }
    }
}

/* =========================
   DYNAMICZNE SŁOWNIKI
   ========================= */
function dict_names_active(PDO $pdo, string $typeSlug): array
{
    $sql = "
        SELECT t.name
        FROM dict_terms t
        JOIN dict_types dt ON dt.id = t.dict_type_id
        WHERE dt.slug = :slug AND t.status = 'active'
        ORDER BY t.sort_order ASC, t.name ASC
    ";
    $st = $pdo->prepare($sql);
    $st->execute([':slug' => $typeSlug]);
    $rows = $st->fetchAll(PDO::FETCH_COLUMN);
    return $rows ? array_values(array_unique(array_map('strval', $rows))) : [];
}

$pdo = db();
$categories = dict_names_active($pdo, 'car_class'); // Klasy samochodu
$carTypes   = dict_names_active($pdo, 'car_type');  // Typy samochodu

// słowniki stałe (bez zmian)
$seatOpts   = [2, 3, 4, 5];
$doorOpts   = [2, 4];
$gearboxes  = ['Manualna', 'Automatyczna'];
$fuels      = ['Benzyna', 'Diesel', 'Hybryda', 'Elektryczny'];
$units      = ['per_day' => i18n::__('per_day'), 'per_hour' => i18n::__('per_hour')]; // na przyszłość

?>
<!-- Wycentrowany wrapper jak w dashboardach -->
<main class="container-md py-4 d-flex align-items-center justify-content-center"
    style="min-height: calc(100vh - 72px); padding-top: 72px;">
    <div class="w-100" style="max-width: 820px; margin: 0 auto;">

        <h1 class="h4 mb-3"><?= $id ? i18n::__('edit_product') : i18n::__('new_product') ?></h1>

        <!-- multipart do uploadu zdjęcia -->
        <form method="post"
            action="<?= $BASE ?>/index.php?page=product-save"
            enctype="multipart/form-data"
            class="card p-3 shadow-sm">

            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int)$product['id'] ?>">

            <!-- Sekcja: Podstawowe informacje -->
            <div class="card border-primary mb-3">
                <div class="card-header bg-primary-subtle">
                    <h6 class="mb-0">
                        <i class="bi bi-info-circle me-2"></i><?= i18n::__('basic_information') ?>
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label"><?= i18n::__('product_name') ?></label>
                            <input type="text" name="name" class="form-control" required
                                value="<?= htmlspecialchars($product['name']) ?>" id="product-name">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label"><?= i18n::__('sku_unique') ?></label>
                            <div class="input-group">
                                <input type="text" name="sku" class="form-control" required
                                    value="<?= htmlspecialchars($product['sku']) ?>" id="product-sku">
                                <button type="button" class="btn btn-outline-secondary" id="auto-generate-sku"
                                    title="<?= i18n::__('auto_generate_sku') ?>">
                                    <i class="bi bi-arrow-clockwise"></i>
                                </button>
                            </div>
                            <div class="form-text"><?= i18n::__('sku_auto_generated_info') ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sekcja: Klasyfikacja pojazdu -->
            <div class="card border-secondary mb-3">
                <div class="card-header bg-secondary-subtle">
                    <h6 class="mb-0">
                        <i class="bi bi-car-front me-2"></i><?= i18n::__('vehicle_classification') ?>
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label"><?= i18n::__('class') ?></label>
                            <select name="category" class="form-select" required>
                                <option value="" <?= $product['category'] === '' ? 'selected' : '' ?> disabled>— <?= i18n::__('choose') ?> —</option>
                                <?php foreach ($categories as $opt): ?>
                                    <option value="<?= htmlspecialchars($opt) ?>"
                                        <?= $product['category'] === $opt ? 'selected' : ''; ?>><?= htmlspecialchars($opt) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label"><?= i18n::__('seats_count') ?></label>
                            <select name="seats" class="form-select" required>
                                <option value="" <?= $product['seats'] === '' ? 'selected' : '' ?> disabled>— <?= i18n::__('choose') ?> —</option>
                                <?php foreach ($seatOpts as $n): ?>
                                    <option value="<?= $n ?>" <?= ((string)$product['seats'] === (string)$n) ? 'selected' : ''; ?>><?= $n ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label"><?= i18n::__('doors_count') ?></label>
                            <select name="doors" class="form-select" required>
                                <option value="" <?= $product['doors'] === '' ? 'selected' : '' ?> disabled>— <?= i18n::__('choose') ?> —</option>
                                <?php foreach ($doorOpts as $n): ?>
                                    <option value="<?= $n ?>" <?= ((string)$product['doors'] === (string)$n) ? 'selected' : ''; ?>><?= $n ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sekcja: Parametry techniczne -->
            <div class="card border-success mb-3">
                <div class="card-header bg-success-subtle">
                    <h6 class="mb-0">
                        <i class="bi bi-gear me-2"></i><?= i18n::__('technical_parameters') ?>
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label"><?= i18n::__('car_type') ?></label>
                            <select name="car_type" class="form-select" required>
                                <option value="" <?= $product['car_type'] === '' ? 'selected' : '' ?> disabled>— <?= i18n::__('choose') ?> —</option>
                                <?php foreach ($carTypes as $opt): ?>
                                    <option value="<?= htmlspecialchars($opt) ?>"
                                        <?= ($product['car_type'] === $opt) ? 'selected' : ''; ?>>
                                        <?= htmlspecialchars($opt) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label"><?= i18n::__('transmission') ?></label>
                            <select name="gearbox" class="form-select" required>
                                <option value="" <?= $product['gearbox'] === '' ? 'selected' : '' ?> disabled>— <?= i18n::__('choose') ?> —</option>
                                <?php foreach ($gearboxes as $opt): ?>
                                    <option value="<?= htmlspecialchars($opt) ?>"
                                        <?= $product['gearbox'] === $opt ? 'selected' : ''; ?>><?= htmlspecialchars($opt) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label"><?= i18n::__('fuel_type') ?></label>
                            <select name="fuel" class="form-select" required>
                                <option value="" <?= $product['fuel'] === '' ? 'selected' : '' ?> disabled>— <?= i18n::__('choose') ?> —</option>
                                <?php foreach ($fuels as $opt): ?>
                                    <option value="<?= htmlspecialchars($opt) ?>"
                                        <?= $product['fuel'] === $opt ? 'selected' : ''; ?>><?= htmlspecialchars($opt) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sekcja: Cena i jednostka -->
            <div class="card border-warning mb-3">
                <div class="card-header bg-warning-subtle">
                    <h6 class="mb-0">
                        <i class="bi bi-currency-dollar me-2"></i><?= i18n::__('pricing') ?>
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label"><?= i18n::__('price') ?></label>
                            <div class="input-group">
                                <span class="input-group-text">PLN</span>
                                <input type="number" name="price" class="form-control" step="0.01" min="0" required
                                    value="<?= htmlspecialchars($product['price']) ?>">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label"><?= i18n::__('unit') ?></label>
                            <select name="price_unit" class="form-select" required>
                                <option value="" <?= $product['price_unit'] === '' ? 'selected' : '' ?> disabled>— <?= i18n::__('choose') ?> —</option>
                                <?php foreach ($units as $key => $label): ?>
                                    <option value="<?= $key ?>" <?= $product['price_unit'] === $key ? 'selected' : ''; ?>><?= $label ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sekcja: Kaucja -->
            <div class="card border-info mb-3">
                <div class="card-header bg-info-subtle">
                    <h6 class="mb-0">
                        <i class="bi bi-shield-check me-2"></i><?= i18n::__('deposit_settings') ?>
                    </h6>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div>
                            <label class="form-label mb-1"><?= i18n::__('enable_individual_deposit') ?></label>
                            <div class="form-text small"><?= i18n::__('enable_individual_deposit_info') ?></div>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="deposit_enabled" value="1"
                                id="deposit-enabled" <?= $product['deposit_enabled'] ? 'checked' : '' ?>
                                style="width: 3rem; height: 1.5rem;">
                            <label class="form-check-label" for="deposit-enabled"></label>
                        </div>
                    </div>

                    <div id="deposit-settings" style="<?= $product['deposit_enabled'] ? '' : 'display: none;' ?>">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label"><?= i18n::__('deposit_type') ?></label>
                                <select name="deposit_type" class="form-select" id="deposit-type">
                                    <option value="fixed" <?= $product['deposit_type'] === 'fixed' ? 'selected' : ''; ?>>
                                        <?= i18n::__('fixed_amount') ?>
                                    </option>
                                    <option value="percentage" <?= $product['deposit_type'] === 'percentage' ? 'selected' : ''; ?>>
                                        <?= i18n::__('percentage_of_price') ?>
                                    </option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" id="deposit-amount-label">
                                    <?= $product['deposit_type'] === 'percentage' ? i18n::__('percentage') : i18n::__('amount') ?>
                                </label>
                                <div class="input-group">
                                    <input type="number" name="deposit_amount" class="form-control"
                                        step="0.01" min="0" value="<?= htmlspecialchars($product['deposit_amount']) ?>"
                                        id="deposit-amount">
                                    <span class="input-group-text" id="deposit-unit">
                                        <?= $product['deposit_type'] === 'percentage' ? '%' : 'PLN' ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sekcja: Status -->
            <div class="card border-dark mb-3">
                <div class="card-header bg-dark text-white">
                    <h6 class="mb-0">
                        <i class="bi bi-toggle-on me-2"></i><?= i18n::__('status') ?>
                    </h6>
                </div>
                <div class="card-body">
                    <select name="status" class="form-select">
                        <option value="active" <?= $product['status'] === 'active' ? 'selected' : ''; ?>>
                            <i class="bi bi-check-circle"></i> <?= i18n::__('active') ?>
                        </option>
                        <option value="inactive" <?= $product['status'] === 'inactive' ? 'selected' : ''; ?>>
                            <i class="bi bi-x-circle"></i> <?= i18n::__('inactive') ?>
                        </option>
                    </select>
                </div>
            </div>

            <!-- Sekcja: Zdjęcie pojazdu -->
            <div class="card border-light mb-3">
                <div class="card-header bg-light">
                    <h6 class="mb-0">
                        <i class="bi bi-camera me-2"></i><?= i18n::__('vehicle_photo') ?>
                    </h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label"><?= i18n::__('choose_photo') ?></label>
                        <input type="file" name="image" class="form-control" accept="image/*">
                        <div class="form-text"><?= i18n::__('supported_formats') ?></div>
                    </div>

                    <?php if (!empty($product['image_path'])): ?>
                        <div class="d-flex align-items-center gap-3">
                            <img src="<?= htmlspecialchars($product['image_path']) ?>" alt="<?= i18n::__('preview') ?>"
                                style="height: 72px; width:auto; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="remove_image" value="1" id="rmimg">
                                <label class="form-check-label" for="rmimg"><?= i18n::__('remove_current_image') ?></label>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Sekcja: Opis produktu -->
            <div class="card border-muted mb-3">
                <div class="card-header bg-light">
                    <h6 class="mb-0">
                        <i class="bi bi-card-text me-2"></i><?= i18n::__('product_description') ?>
                    </h6>
                </div>
                <div class="card-body">
                    <textarea name="description" rows="5" class="form-control"
                        placeholder="<?= i18n::__('product_description_placeholder') ?>"><?= htmlspecialchars($product['description']) ?></textarea>
                </div>
            </div>

            <!-- Przyciski akcji -->
            <div class="d-flex gap-3 mt-4">
                <button class="btn btn-primary btn-lg" type="submit">
                    <i class="bi bi-check-lg me-2"></i><?= i18n::__('save') ?>
                </button>
                <a class="btn btn-outline-secondary btn-lg" href="<?= $BASE ?>/index.php?page=dashboard-staff">
                    <i class="bi bi-x-lg me-2"></i><?= i18n::__('cancel') ?>
                </a>

                <?php if ($id): ?>
                    <a class="btn btn-outline-danger btn-lg ms-auto"
                        href="<?= $BASE ?>/index.php?page=product-delete&id=<?= (int)$product['id'] ?>&csrf=<?= htmlspecialchars(csrf_token()) ?>"
                        onclick="return confirm('<?= i18n::__('confirm_delete_product_id') ?><?= (int)$product['id'] ?>?');">
                        <i class="bi bi-trash me-2"></i><?= i18n::__('delete') ?>
                    </a>
                <?php endif; ?>
            </div>
        </form>

        <style>
            /* Custom styling for form sections */
            .card {
                transition: all 0.2s ease-in-out;
            }

            .card:hover {
                transform: translateY(-2px);
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15) !important;
            }

            .form-switch .form-check-input {
                background-color: #e9ecef;
                border-color: #ced4da;
                background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='-4 -4 8 8'%3e%3ccircle r='3' fill='%23495057'/%3e%3c/svg%3e");
            }

            .form-switch .form-check-input:checked {
                background-color: #0d6efd;
                border-color: #0d6efd;
                background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='-4 -4 8 8'%3e%3ccircle r='3' fill='%23ffffff'/%3e%3c/svg%3e");
            }

            .form-switch .form-check-input:focus {
                box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
            }

            .form-switch .form-check-input:hover:not(:checked) {
                background-color: #f8f9fa;
                border-color: #adb5bd;
            }

            .card-header h6 {
                font-weight: 600;
            }

            .btn-lg {
                padding: 0.75rem 1.5rem;
                font-size: 1.1rem;
            }

            /* Smooth transitions for deposit settings */
            #deposit-settings {
                transition: all 0.3s ease-in-out;
                overflow: hidden;
            }

            #deposit-settings.hide {
                max-height: 0;
                opacity: 0;
                margin: 0;
                padding: 0;
            }

            #deposit-settings.show {
                max-height: 200px;
                opacity: 1;
            }
        </style>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Auto-generate SKU functionality
                const nameInput = document.getElementById('product-name');
                const skuInput = document.getElementById('product-sku');
                const generateBtn = document.getElementById('auto-generate-sku');

                function generateSKU(name) {
                    return name
                        .toUpperCase()
                        .replace(/[^A-Z0-9\s]/g, '') // Remove special characters
                        .replace(/\s+/g, '-') // Replace spaces with dashes
                        .replace(/^-+|-+$/g, '') // Remove leading/trailing dashes
                        .substring(0, 20); // Limit length
                }

                generateBtn.addEventListener('click', function() {
                    if (nameInput.value.trim()) {
                        skuInput.value = generateSKU(nameInput.value.trim());
                    }
                });

                // Auto-generate on name change (optional)
                nameInput.addEventListener('input', function() {
                    if (!skuInput.value || skuInput.dataset.autoGenerated === 'true') {
                        skuInput.value = generateSKU(this.value.trim());
                        skuInput.dataset.autoGenerated = 'true';
                    }
                });

                skuInput.addEventListener('input', function() {
                    if (this.value !== generateSKU(nameInput.value.trim())) {
                        this.dataset.autoGenerated = 'false';
                    }
                });

                // Deposit settings toggle with smooth animation
                const depositEnabled = document.getElementById('deposit-enabled');
                const depositSettings = document.getElementById('deposit-settings');
                const depositType = document.getElementById('deposit-type');
                const depositAmountLabel = document.getElementById('deposit-amount-label');
                const depositUnit = document.getElementById('deposit-unit');

                depositEnabled.addEventListener('change', function() {
                    if (this.checked) {
                        depositSettings.style.display = 'block';
                        setTimeout(() => depositSettings.classList.add('show'), 10);
                    } else {
                        depositSettings.classList.remove('show');
                        setTimeout(() => depositSettings.style.display = 'none', 300);
                    }
                });

                depositType.addEventListener('change', function() {
                    const isPercentage = this.value === 'percentage';
                    depositAmountLabel.textContent = isPercentage ? '<?= i18n::__('percentage') ?>' : '<?= i18n::__('amount') ?>';
                    depositUnit.textContent = isPercentage ? '%' : 'PLN';
                });
            });
        </script>

    </div>
</main>

<?php require_once dirname(__DIR__) . '/partials/footer.php'; ?>