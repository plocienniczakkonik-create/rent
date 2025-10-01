<?php
// /pages/product-form.php
require_once dirname(__DIR__) . '/auth/auth.php';
$staff = require_staff();

require_once dirname(__DIR__) . '/partials/head.php';
require_once dirname(__DIR__) . '/partials/header.php';
require_once dirname(__DIR__) . '/includes/db.php';

$BASE = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Domyślne wartości (gdy nowy produkt)
$product = [
    'id'          => 0,
    'name'        => '',
    'sku'         => '',
    'price'       => '0.00',
    'price_unit'  => 'per_day',       // per_day | per_hour (na przyszłość)
    'stock'       => 0,
    'status'      => 'active',        // active | inactive
    'category'    => 'Klasa A',       // Klasa A-E
    'seats'       => 5,               // 2,3,4,5
    'doors'       => 4,               // 2,4
    'gearbox'     => 'Manualna',      // Manualna | Automatyczna
    'fuel'        => 'Benzyna',       // Benzyna | Diesel | Hybryda | Elektryczny
    'description' => '',
    'image_path'  => null,            // ścieżka do pliku (jeśli mamy w DB)
];

// Jeśli edycja – pobierz co się da (jeśli w DB jeszcze nie ma kolumn, nie szkodzi)
if ($id > 0) {
    $stmt = db()->prepare("
    SELECT
      id, name, sku, price, stock, status,
      -- poniższe kolumny mogą jeszcze nie istnieć; gdy będą, SELECT zwróci wartości
      /* optional */ category, seats, doors, gearbox, fuel, price_unit, description, image_path
    FROM products
    WHERE id = ?
  ");
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

    // bezpieczne scalenie (jeśli czegoś nie ma w $row – zostaje domyślne)
    foreach ($product as $key => $def) {
        if (array_key_exists($key, $row) && $row[$key] !== null) {
            $product[$key] = $row[$key];
        }
    }
}

// słowniki
$categories = ['Klasa A', 'Klasa B', 'Klasa C', 'Klasa D', 'Klasa E'];
$seatOpts   = [2, 3, 4, 5];
$doorOpts   = [2, 4];
$gearboxes  = ['Manualna', 'Automatyczna'];
$fuels      = ['Benzyna', 'Diesel', 'Hybryda', 'Elektryczny'];
$units      = ['per_day' => 'za dzień', 'per_hour' => 'za godzinę']; // na przyszłość

?>
<!-- Wycentrowany wrapper jak w dashboardach -->
<main class="container-md py-4 d-flex align-items-center justify-content-center"
    style="min-height: calc(100vh - 72px); padding-top: 72px;">
    <div class="w-100" style="max-width: 820px; margin: 0 auto;">

        <h1 class="h4 mb-3"><?= $id ? 'Edytuj produkt' : 'Nowy produkt' ?></h1>

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
                    <label class="form-label">Nazwa</label>
                    <input type="text" name="name" class="form-control" required
                        value="<?= htmlspecialchars($product['name']) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">SKU (unikalny)</label>
                    <input type="text" name="sku" class="form-control" required
                        value="<?= htmlspecialchars($product['sku']) ?>">
                </div>
            </div>

            <!-- Klasyfikacja pojazdu -->
            <div class="row g-3 mt-1">
                <div class="col-md-4">
                    <label class="form-label">Klasa</label>
                    <select name="category" class="form-select">
                        <?php foreach ($categories as $opt): ?>
                            <option value="<?= htmlspecialchars($opt) ?>"
                                <?= $product['category'] === $opt ? 'selected' : ''; ?>><?= htmlspecialchars($opt) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Ilość miejsc</label>
                    <select name="seats" class="form-select">
                        <?php foreach ($seatOpts as $n): ?>
                            <option value="<?= $n ?>" <?= (int)$product['seats'] === $n ? 'selected' : ''; ?>><?= $n ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Ilość drzwi</label>
                    <select name="doors" class="form-select">
                        <?php foreach ($doorOpts as $n): ?>
                            <option value="<?= $n ?>" <?= (int)$product['doors'] === $n ? 'selected' : ''; ?>><?= $n ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="row g-3 mt-1">
                <div class="col-md-6">
                    <label class="form-label">Skrzynia biegów</label>
                    <select name="gearbox" class="form-select">
                        <?php foreach ($gearboxes as $opt): ?>
                            <option value="<?= htmlspecialchars($opt) ?>"
                                <?= $product['gearbox'] === $opt ? 'selected' : ''; ?>><?= htmlspecialchars($opt) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Rodzaj paliwa</label>
                    <select name="fuel" class="form-select">
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
                    <label class="form-label">Cena</label>
                    <div class="input-group">
                        <span class="input-group-text">PLN</span>
                        <input type="number" name="price" class="form-control" step="0.01" min="0" required
                            value="<?= htmlspecialchars($product['price']) ?>">
                    </div>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Jednostka</label>
                    <select name="price_unit" class="form-select">
                        <?php foreach ($units as $key => $label): ?>
                            <option value="<?= $key ?>" <?= $product['price_unit'] === $key ? 'selected' : ''; ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Stan (liczba szt.)</label>
                    <input type="number" name="stock" class="form-control" step="1" min="0" required
                        value="<?= (int)$product['stock'] ?>">
                </div>
            </div>

            <!-- Status -->
            <div class="mt-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="active" <?= $product['status'] === 'active' ? 'selected' : ''; ?>>active</option>
                    <option value="inactive" <?= $product['status'] === 'inactive' ? 'selected' : ''; ?>>inactive</option>
                </select>
            </div>

            <!-- Zdjęcie (upload) -->
            <div class="mt-3">
                <label class="form-label">Zdjęcie pojazdu</label>
                <input type="file" name="image" class="form-control" accept="image/*">
                <div class="form-text">Obsługiwane: JPG, PNG, WEBP. Maks. ~3–5 MB (zależnie od serwera).</div>

                <?php if (!empty($product['image_path'])): ?>
                    <div class="d-flex align-items-center gap-3 mt-2">
                        <img src="<?= htmlspecialchars($product['image_path']) ?>" alt="Podgląd" style="height: 72px; width:auto; border-radius: 8px;">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="remove_image" value="1" id="rmimg">
                            <label class="form-check-label" for="rmimg">Usuń obecne zdjęcie</label>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Opis -->
            <div class="mt-3">
                <label class="form-label">Opis produktu</label>
                <textarea name="description" rows="5" class="form-control" placeholder="Krótki opis, warunki wynajmu, wyposażenie itp."><?= htmlspecialchars($product['description']) ?></textarea>
            </div>

            <!-- Przyciski -->
            <div class="d-flex gap-2 mt-3">
                <button class="btn btn-primary" type="submit">Zapisz</button>
                <a class="btn btn-outline-secondary" href="<?= $BASE ?>/index.php?page=dashboard-staff">Anuluj</a>

                <?php if ($id): ?>
                    <a class="btn btn-outline-danger ms-auto"
                        href="<?= $BASE ?>/pages/product-delete.php?id=<?= (int)$product['id'] ?>&csrf=<?= htmlspecialchars(csrf_token()) ?>"
                        onclick="return confirm('Usunąć produkt #<?= (int)$product['id'] ?>?');">Usuń</a>
                <?php endif; ?>
            </div>
        </form>

    </div>
</main>

<?php require_once dirname(__DIR__) . '/partials/footer.php'; ?>