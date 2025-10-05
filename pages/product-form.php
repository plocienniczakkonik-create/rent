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
    'id'          => 0,
    'name'        => '',
    'sku'         => '',
    'price'       => '0.00',
    'price_unit'  => '',              // ⬅️ było: 'per_day'
    'stock'       => 0,
    'status'      => 'active',        // zostawiamy domyślnie active
    'category'    => '',              // ⬅️ było: 'Klasa A'
    'car_type'    => '',              // typ samochodu
    'seats'       => '',              // ⬅️ było: 5
    'doors'       => '',              // ⬅️ było: 4
    'gearbox'     => '',              // ⬅️ było: 'Manualna'
    'fuel'        => '',              // ⬅️ było: 'Benzyna'
    'description' => '',
    'image_path'  => null,
];

// Jeśli edycja – pobierz co się da
if ($id > 0) {
    $stmt = db()->prepare("
        SELECT
          id, name, sku, price, stock, status,
          /* optional */ category, car_type, seats, doors, gearbox, fuel, price_unit, description, image_path
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
            action="<?= $BASE ? ($BASE . '/pages/product-save.php') : 'product-save.php' ?>"
            enctype="multipart/form-data"
            class="card p-3 shadow-sm">

            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int)$product['id'] ?>">

            <!-- Nazwa + SKU -->
            <div class="row g-3">
                <div class="col-md-8">
                    <label class="form-label"><?= i18n::__('product_name') ?></label>
                    <input type="text" name="name" class="form-control" required
                        value="<?= htmlspecialchars($product['name']) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label"><?= i18n::__('sku_unique') ?></label>
                    <input type="text" name="sku" class="form-control" required
                        value="<?= htmlspecialchars($product['sku']) ?>">
                </div>
            </div>

            <!-- Klasyfikacja pojazdu -->
            <div class="row g-3 mt-1">
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

            <!-- Typ samochodu -->
            <div class="row g-3 mt-1">
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

            <!-- Cena + jednostka + stan -->
            <div class="row g-3 mt-1">
                <div class="col-md-5">
                    <label class="form-label"><?= i18n::__('price') ?></label>
                    <div class="input-group">
                        <span class="input-group-text">PLN</span>
                        <input type="number" name="price" class="form-control" step="0.01" min="0" required
                            value="<?= htmlspecialchars($product['price']) ?>">
                    </div>
                </div>

                <div class="col-md-4">
                    <label class="form-label"><?= i18n::__('unit') ?></label>
                    <select name="price_unit" class="form-select" required>
                        <option value="" <?= $product['price_unit'] === '' ? 'selected' : '' ?> disabled>— <?= i18n::__('choose') ?> —</option>
                        <?php foreach ($units as $key => $label): ?>
                            <option value="<?= $key ?>" <?= $product['price_unit'] === $key ? 'selected' : ''; ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label"><?= i18n::__('stock_quantity') ?></label>
                    <input type="number" name="stock" class="form-control" step="1" min="0" required
                        value="<?= (int)$product['stock'] ?>">
                </div>
            </div>

            <!-- Status (zostawiamy domyślnie active; jeśli chcesz też wymuszać wybór, dodaj required i placeholder) -->
            <div class="mt-3">
                <label class="form-label"><?= i18n::__('status') ?></label>
                <select name="status" class="form-select">
                    <option value="active" <?= $product['status'] === 'active' ? 'selected' : ''; ?>><?= i18n::__('active') ?></option>
                    <option value="inactive" <?= $product['status'] === 'inactive' ? 'selected' : ''; ?>><?= i18n::__('inactive') ?></option>
                </select>
            </div>

            <!-- Zdjęcie (upload) -->
            <div class="mt-3">
                <label class="form-label"><?= i18n::__('vehicle_photo') ?></label>
                <input type="file" name="image" class="form-control" accept="image/*">
                <div class="form-text"><?= i18n::__('supported_formats') ?></div>

                <?php if (!empty($product['image_path'])): ?>
                    <div class="d-flex align-items-center gap-3 mt-2">
                        <img src="<?= htmlspecialchars($product['image_path']) ?>" alt="<?= i18n::__('preview') ?>" style="height: 72px; width:auto; border-radius: 8px;">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="remove_image" value="1" id="rmimg">
                            <label class="form-check-label" for="rmimg"><?= i18n::__('remove_current_image') ?></label>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Opis -->
            <div class="mt-3">
                <label class="form-label"><?= i18n::__('product_description') ?></label>
                <textarea name="description" rows="5" class="form-control" placeholder="<?= i18n::__('product_description_placeholder') ?>"><?= htmlspecialchars($product['description']) ?></textarea>
            </div>

            <!-- Przyciski -->
            <div class="d-flex gap-2 mt-3">
                <button class="btn btn-primary" type="submit"><?= i18n::__('save') ?></button>
                <a class="btn btn-outline-secondary" href="<?= $BASE ?>/index.php?page=dashboard-staff"><?= i18n::__('cancel') ?></a>

                <?php if ($id): ?>
                    <a class="btn btn-outline-danger ms-auto"
                        href="<?= $BASE ?>/pages/product-delete.php?id=<?= (int)$product['id'] ?>&csrf=<?= htmlspecialchars(csrf_token()) ?>"
                        onclick="return confirm('<?= i18n::__('confirm_delete_product_id') ?><?= (int)$product['id'] ?>?');"><?= i18n::__('delete') ?></a>
                <?php endif; ?>
            </div>
        </form>

    </div>
</main>

<?php require_once dirname(__DIR__) . '/partials/footer.php'; ?>