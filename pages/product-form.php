<?php
require_once dirname(__DIR__) . '/auth/auth.php';
$staff = require_staff();
require_once dirname(__DIR__) . '/partials/head.php';
require_once dirname(__DIR__) . '/partials/header.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/_helpers.php';
require_once dirname(__DIR__) . '/includes/i18n.php';
i18n::init();
$BASE = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$product = [
    'id'              => 0,
    'name'            => '',
    'sku'             => '',
    'price'           => '0.00',
    'price_unit'      => '',
    'status'          => 'active',
    'category'        => '',
    'car_type'        => '',
    'seats'           => '',
    'doors'           => '',
    'gearbox'         => '',
    'fuel'            => '',
    'description'     => '',
    'image_path'      => null,
    'deposit_enabled' => 0,
    'deposit_type'    => 'fixed',
    'deposit_amount'  => '0.00',
];
if ($id > 0) {
    $stmt = db()->prepare("SELECT id, name, sku, price, status, category, car_type, seats, doors, gearbox, fuel, price_unit, description, image_path, deposit_enabled, deposit_type, deposit_amount FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    foreach ($product as $key => $def) {
        if (array_key_exists($key, $row) && $row[$key] !== null) {
            $product[$key] = $row[$key];
        }
    }
}
function dict_names_active(PDO $pdo, string $typeSlug): array {
    $sql = "SELECT t.name FROM dict_terms t JOIN dict_types dt ON dt.id = t.dict_type_id WHERE dt.slug = :slug AND t.status = 'active' ORDER BY t.sort_order ASC, t.name ASC";
    $st = $pdo->prepare($sql);
    $st->execute([':slug' => $typeSlug]);
    $rows = $st->fetchAll(PDO::FETCH_COLUMN);
    return $rows ? array_values(array_unique(array_map('strval', $rows))) : [];
}
$pdo = db();
$categories = dict_names_active($pdo, 'car_class');
$carTypes   = dict_names_active($pdo, 'car_type');
$seatOpts   = [2, 3, 4, 5];
$doorOpts   = [2, 4];
$gearboxes  = ['Manualna', 'Automatyczna'];
$fuels      = ['Benzyna', 'Diesel', 'Hybryda', 'Elektryczny'];
$units      = ['per_day' => i18n::__('per_day'), 'per_hour' => i18n::__('per_hour')];
?>
<div class="container py-4">
    <div class="d-flex justify-content-center">
        <div class="card-product-form" style="background: #fff; border-radius: 1.25rem; box-shadow: 0 4px 24px rgba(0,0,0,0.07); padding: 2.5rem 2rem; max-width: 900px; width: 100%; margin: 0 auto;">
            <div class="card-header mb-4" style="background: var(--gradient-primary); color: white; border-radius: 0.75rem 0.75rem 0 0; border-bottom: 1px solid #e5e7eb; padding: 1.25rem 1.5rem;">
                <div class="d-flex flex-wrap align-items-center justify-content-between">
                    <h4 class="mb-0">
                        <i class="bi bi-box-seam me-2"></i><?= $id ? i18n::__('edit_product') : i18n::__('new_product') ?>
                    </h4>
                    <?php if ($id): ?>
                        <div class="d-flex gap-2 align-items-center">
                            <span class="badge fs-6" style="background: #f3f4f6; color: #374151; border: 1px solid #e5e7eb;">
                                ID: <?= $id; ?>
                            </span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <form method="post" action="<?= $BASE ?>/index.php?page=product-save" enctype="multipart/form-data" class="needs-validation" novalidate>
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int)$product['id'] ?>">
                <!-- Sekcja: Podstawowe informacje -->
                <div class="mb-4">
                    <div class="section-header d-flex align-items-center mb-2" style="background: #eef1f3; border-bottom: 1px solid #6b7280; border-radius: 0.5rem 0.5rem 0 0; padding: 0.75rem 1rem;">
                        <h5 class="mb-0 d-flex align-items-center">
                            <i class="bi bi-info-circle me-2"></i>
                            <?= i18n::__('basic_information') ?>
                        </h5>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label"><?= i18n::__('product_name') ?> <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" required value="<?= htmlspecialchars($product['name']) ?>" id="product-name">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">SKU <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="text" name="sku" class="form-control" required value="<?= htmlspecialchars($product['sku']) ?>" id="product-sku">
                                <button type="button" class="btn btn-outline-secondary" id="auto-generate-sku" title="<?= i18n::__('auto_generate_sku') ?>">
                                    <i class="bi bi-arrow-clockwise"></i>
                                </button>
                            </div>
                            <div class="form-text"><?= i18n::__('sku_auto_generated_info') ?></div>
                        </div>
                    </div>
                </div>
                <!-- Sekcja: Klasyfikacja pojazdu -->
                <div class="mb-4">
                    <div class="section-header d-flex align-items-center mb-2" style="background: #eef1f3; border-bottom: 1px solid #6b7280; border-radius: 0.5rem 0.5rem 0 0; padding: 0.75rem 1rem;">
                        <h5 class="mb-0 d-flex align-items-center">
                            <i class="bi bi-car-front me-2"></i>
                            <?= i18n::__('vehicle_classification') ?>
                        </h5>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label"><?= i18n::__('class') ?> <span class="text-danger">*</span></label>
                            <select name="category" class="form-select" required>
                                <option value="" <?= $product['category'] === '' ? 'selected' : '' ?> disabled>— <?= i18n::__('choose') ?> —</option>
                                <?php foreach ($categories as $opt): ?>
                                    <option value="<?= htmlspecialchars($opt) ?>" <?= $product['category'] === $opt ? 'selected' : ''; ?>><?= htmlspecialchars($opt) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label"><?= i18n::__('seats_count') ?> <span class="text-danger">*</span></label>
                            <select name="seats" class="form-select" required>
                                <option value="" <?= $product['seats'] === '' ? 'selected' : '' ?> disabled>— <?= i18n::__('choose') ?> —</option>
                                <?php foreach ($seatOpts as $n): ?>
                                    <option value="<?= $n ?>" <?= ((string)$product['seats'] === (string)$n) ? 'selected' : ''; ?>><?= $n ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label"><?= i18n::__('doors_count') ?> <span class="text-danger">*</span></label>
                            <select name="doors" class="form-select" required>
                                <option value="" <?= $product['doors'] === '' ? 'selected' : '' ?> disabled>— <?= i18n::__('choose') ?> —</option>
                                <?php foreach ($doorOpts as $n): ?>
                                    <option value="<?= $n ?>" <?= ((string)$product['doors'] === (string)$n) ? 'selected' : ''; ?>><?= $n ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <!-- Sekcja: Parametry techniczne -->
                <div class="mb-4">
                    <div class="section-header d-flex align-items-center mb-2" style="background: #eef1f3; border-bottom: 1px solid #6b7280; border-radius: 0.5rem 0.5rem 0 0; padding: 0.75rem 1rem;">
                        <h5 class="mb-0 d-flex align-items-center">
                            <i class="bi bi-gear me-2"></i>
                            <?= i18n::__('technical_parameters') ?>
                        </h5>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label"><?= i18n::__('car_type') ?> <span class="text-danger">*</span></label>
                            <select name="car_type" class="form-select" required>
                                <option value="" <?= $product['car_type'] === '' ? 'selected' : '' ?> disabled>— <?= i18n::__('choose') ?> —</option>
                                <?php foreach ($carTypes as $opt): ?>
                                    <option value="<?= htmlspecialchars($opt) ?>" <?= ($product['car_type'] === $opt) ? 'selected' : ''; ?>><?= htmlspecialchars($opt) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label"><?= i18n::__('transmission') ?> <span class="text-danger">*</span></label>
                            <select name="gearbox" class="form-select" required>
                                <option value="" <?= $product['gearbox'] === '' ? 'selected' : '' ?> disabled>— <?= i18n::__('choose') ?> —</option>
                                <?php foreach ($gearboxes as $opt): ?>
                                    <option value="<?= htmlspecialchars($opt) ?>" <?= $product['gearbox'] === $opt ? 'selected' : ''; ?>><?= htmlspecialchars($opt) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label"><?= i18n::__('fuel_type') ?> <span class="text-danger">*</span></label>
                            <select name="fuel" class="form-select" required>
                                <option value="" <?= $product['fuel'] === '' ? 'selected' : '' ?> disabled>— <?= i18n::__('choose') ?> —</option>
                                <?php foreach ($fuels as $opt): ?>
                                    <option value="<?= htmlspecialchars($opt) ?>" <?= $product['fuel'] === $opt ? 'selected' : ''; ?>><?= htmlspecialchars($opt) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <!-- Sekcja: Cena i jednostka -->
                <div class="mb-4">
                    <div class="section-header d-flex align-items-center mb-2" style="background: #eef1f3; border-bottom: 1px solid #6b7280; border-radius: 0.5rem 0.5rem 0 0; padding: 0.75rem 1rem;">
                        <h5 class="mb-0 d-flex align-items-center">
                            <i class="bi bi-currency-dollar me-2"></i>
                            <?= i18n::__('pricing') ?>
                        </h5>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label"><?= i18n::__('price') ?> <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">PLN</span>
                                <input type="number" name="price" class="form-control" step="0.01" min="0" required value="<?= htmlspecialchars($product['price']) ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><?= i18n::__('unit') ?> <span class="text-danger">*</span></label>
                            <select name="price_unit" class="form-select" required>
                                <option value="" <?= $product['price_unit'] === '' ? 'selected' : '' ?> disabled>— <?= i18n::__('choose') ?> —</option>
                                <?php foreach ($units as $key => $label): ?>
                                    <option value="<?= $key ?>" <?= $product['price_unit'] === $key ? 'selected' : ''; ?>><?= $label ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <!-- Sekcja: Kaucja -->
                <div class="mb-4">
                    <div class="section-header d-flex align-items-center mb-2" style="background: #eef1f3; border-bottom: 1px solid #6b7280; border-radius: 0.5rem 0.5rem 0 0; padding: 0.75rem 1rem;">
                        <h5 class="mb-0 d-flex align-items-center">
                            <i class="bi bi-shield-check me-2"></i>
                            <?= i18n::__('deposit_settings') ?>
                        </h5>
                    </div>
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div>
                            <label class="form-label mb-1"><?= i18n::__('enable_individual_deposit') ?></label>
                            <div class="form-text small"><?= i18n::__('enable_individual_deposit_info') ?></div>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="deposit_enabled" value="1" id="deposit-enabled" <?= $product['deposit_enabled'] ? 'checked' : '' ?> style="width: 3rem; height: 1.5rem;">
                            <label class="form-check-label" for="deposit-enabled"></label>
                        </div>
                    </div>
                    <div id="deposit-settings" style="<?= $product['deposit_enabled'] ? '' : 'display: none;' ?>">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label"><?= i18n::__('deposit_type') ?></label>
                                <select name="deposit_type" class="form-select" id="deposit-type">
                                    <option value="fixed" <?= $product['deposit_type'] === 'fixed' ? 'selected' : ''; ?>><?= i18n::__('fixed_amount') ?></option>
                                    <option value="percentage" <?= $product['deposit_type'] === 'percentage' ? 'selected' : ''; ?>><?= i18n::__('percentage_of_price') ?></option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" id="deposit-amount-label">
                                    <?= $product['deposit_type'] === 'percentage' ? i18n::__('percentage') : i18n::__('amount') ?>
                                </label>
                                <div class="input-group">
                                    <input type="number" name="deposit_amount" class="form-control" step="0.01" min="0" value="<?= htmlspecialchars($product['deposit_amount']) ?>" id="deposit-amount">
                                    <span class="input-group-text" id="deposit-unit">
                                        <?= $product['deposit_type'] === 'percentage' ? '%' : 'PLN' ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Sekcja: Status -->
                <div class="mb-4">
                    <div class="section-header d-flex align-items-center mb-2" style="background: #eef1f3; border-bottom: 1px solid #6b7280; border-radius: 0.5rem 0.5rem 0 0; padding: 0.75rem 1rem;">
                        <h5 class="mb-0 d-flex align-items-center">
                            <i class="bi bi-toggle-on me-2"></i>
                            <?= i18n::__('status') ?>
                        </h5>
                    </div>
                    <select name="status" class="form-select">
                        <option value="active" <?= $product['status'] === 'active' ? 'selected' : ''; ?>><?= i18n::__('active') ?></option>
                        <option value="inactive" <?= $product['status'] === 'inactive' ? 'selected' : ''; ?>><?= i18n::__('inactive') ?></option>
                    </select>
                </div>
                <!-- Sekcja: Zdjęcie pojazdu -->
                <div class="mb-4">
                    <div class="section-header d-flex align-items-center mb-2" style="background: #eef1f3; border-bottom: 1px solid #6b7280; border-radius: 0.5rem 0.5rem 0 0; padding: 0.75rem 1rem;">
                        <h5 class="mb-0 d-flex align-items-center">
                            <i class="bi bi-camera me-2"></i>
                            <?= i18n::__('vehicle_photo') ?>
                        </h5>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?= i18n::__('choose_photo') ?></label>
                        <input type="file" name="image" class="form-control" accept="image/*">
                        <div class="form-text"><?= i18n::__('supported_formats') ?></div>
                    </div>
                    <?php if (!empty($product['image_path'])): ?>
                        <div class="d-flex align-items-center gap-3">
                            <img src="<?= htmlspecialchars($product['image_path']) ?>" alt="<?= i18n::__('preview') ?>" style="height: 72px; width:auto; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="remove_image" value="1" id="rmimg">
                                <label class="form-check-label" for="rmimg"><?= i18n::__('remove_current_image') ?></label>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                <!-- Sekcja: Opis produktu -->
                <div class="mb-4">
                    <div class="section-header d-flex align-items-center mb-2" style="background: #eef1f3; border-bottom: 1px solid #6b7280; border-radius: 0.5rem 0.5rem 0 0; padding: 0.75rem 1rem;">
                        <h5 class="mb-0 d-flex align-items-center">
                            <i class="bi bi-card-text me-2"></i>
                            <?= i18n::__('product_description') ?>
                        </h5>
                    </div>
                    <textarea name="description" rows="5" class="form-control" placeholder="<?= i18n::__('product_description_placeholder') ?>"><?= htmlspecialchars($product['description']) ?></textarea>
                </div>
                <div class="d-flex gap-2 mt-4">
                    <a href="<?= $BASE ?>/index.php?page=dashboard-staff" class="btn btn-outline-secondary"><?= i18n::__('cancel') ?></a>
                    <button class="btn btn-primary" type="submit"><?= i18n::__('save') ?></button>
                    <?php if ($id): ?>
                        <a class="btn btn-outline-danger ms-auto" href="<?= $BASE ?>/index.php?page=product-delete&id=<?= (int)$product['id'] ?>&csrf=<?= htmlspecialchars(csrf_token()) ?>" onclick="return confirm('<?= i18n::__('confirm_delete_product_id') ?><?= (int)$product['id'] ?>?');">
                            <i class="bi bi-trash me-2"></i><?= i18n::__('delete') ?>
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Auto-generate SKU functionality
        const nameInput = document.getElementById('product-name');
        const skuInput = document.getElementById('product-sku');
        const generateBtn = document.getElementById('auto-generate-sku');
        function generateSKU(name) {
            return name.toUpperCase().replace(/[^A-Z0-9\s]/g, '').replace(/\s+/g, '-').replace(/^-+|-+$/g, '').substring(0, 20);
        }
        generateBtn.addEventListener('click', function() {
            if (nameInput.value.trim()) {
                skuInput.value = generateSKU(nameInput.value.trim());
            }
        });
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
<?php require_once dirname(__DIR__) . '/partials/footer.php'; ?>