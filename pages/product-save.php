<?php
// /pages/product-save.php
require_once dirname(__DIR__) . '/auth/auth.php';
$staff = require_staff();
require_once dirname(__DIR__) . '/includes/db.php';

csrf_verify();

$BASE = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';

// --- Wejście ---
$id          = (int)($_POST['id'] ?? 0);
$name        = trim((string)($_POST['name'] ?? ''));
$sku         = trim((string)($_POST['sku'] ?? ''));
$priceStr    = (string)($_POST['price'] ?? '0');
$stockStr    = (string)($_POST['stock'] ?? '0');
$status      = (($_POST['status'] ?? 'active') === 'inactive') ? 'inactive' : 'active';

$category    = trim((string)($_POST['category'] ?? 'Klasa C'));
$seatsStr    = (string)($_POST['seats'] ?? '5');
$doorsStr    = (string)($_POST['doors'] ?? '4');
$gearbox     = (string)($_POST['gearbox'] ?? 'Manualna');
$fuel        = (string)($_POST['fuel'] ?? 'Benzyna');
$price_unit  = (string)($_POST['price_unit'] ?? 'per_day');
$description = trim((string)($_POST['description'] ?? ''));
$removeImage = !empty($_POST['remove_image']);

// --- Normalizacja liczb ---
$price = (float) str_replace(',', '.', $priceStr);
$stock = (int) $stockStr;
$seats = (int) $seatsStr;
$doors = (int) $doorsStr;

// --- Białe listy wartości ---
$allowedCategories = ['Klasa A', 'Klasa B', 'Klasa C', 'Klasa D', 'Klasa E'];
$allowedSeats      = [2, 3, 4, 5];
$allowedDoors      = [2, 4];
$allowedGearbox    = ['Manualna', 'Automatyczna'];
$allowedFuel       = ['Benzyna', 'Diesel', 'Hybryda', 'Elektryczny'];
$allowedUnits      = ['per_day', 'per_hour'];

// Walidacja podstawowa
if ($name === '' || $sku === '' || $price < 0 || $stock < 0) {
    http_response_code(422);
    exit('Błędne dane formularza.');
}
if (!in_array($category, $allowedCategories, true)) $category = 'Klasa C';
if (!in_array($seats,     $allowedSeats, true))     $seats    = 5;
if (!in_array($doors,     $allowedDoors, true))     $doors    = 4;
if (!in_array($gearbox,   $allowedGearbox, true))   $gearbox  = 'Manualna';
if (!in_array($fuel,      $allowedFuel, true))      $fuel     = 'Benzyna';
if (!in_array($price_unit, $allowedUnits, true))     $price_unit = 'per_day';
if (mb_strlen($name) > 190 || mb_strlen($sku) > 64) {
    http_response_code(422);
    exit('Zbyt długie wartości pola.');
}

// --- Przygotowanie uploadu (opcjonalnie) ---
$uploadedImagePath = null;
$deleteOldImage    = false;

if (isset($_FILES['image']) && is_array($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
    $err = (int)$_FILES['image']['error'];
    if ($err !== UPLOAD_ERR_OK) {
        http_response_code(422);
        exit('Błąd przesyłania pliku (kod ' . $err . ').');
    }

    // Walidacja rozmiaru (np. 5 MB)
    $maxBytes = 5 * 1024 * 1024;
    if ((int)$_FILES['image']['size'] > $maxBytes) {
        http_response_code(422);
        exit('Plik jest zbyt duży (max 5 MB).');
    }

    // Walidacja typu MIME
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($_FILES['image']['tmp_name']);
    $allowedMime = [
        'image/jpeg' => '.jpg',
        'image/png'  => '.png',
        'image/webp' => '.webp',
    ];
    if (!isset($allowedMime[$mime])) {
        http_response_code(422);
        exit('Nieobsługiwany format pliku. Dozwolone: JPG, PNG, WEBP.');
    }

    // Katalog docelowy (względem root projektu; dopasuj do swojej struktury publicznej)
    $uploadDirRel = '/assets/uploads/products';
    $uploadDirAbs = dirname(__DIR__) . $uploadDirRel; // /pages -> /assets/uploads/products

    if (!is_dir($uploadDirAbs)) {
        if (!mkdir($uploadDirAbs, 0775, true) && !is_dir($uploadDirAbs)) {
            http_response_code(500);
            exit('Nie można utworzyć katalogu upload.');
        }
    }

    // Unikalna nazwa pliku
    $ext   = $allowedMime[$mime];
    $fname = 'p_' . date('Ymd_His') . '_' . bin2hex(random_bytes(6)) . $ext;

    $destAbs = $uploadDirAbs . '/' . $fname;
    $destRel = $uploadDirRel . '/' . $fname; // to zapiszemy w DB

    if (!move_uploaded_file($_FILES['image']['tmp_name'], $destAbs)) {
        http_response_code(500);
        exit('Nie udało się zapisać pliku.');
    }

    $uploadedImagePath = $destRel;
    // jeśli upload nowego, a było stare i user zaznaczył usuń – oznacz do skasowania starego po update
    if ($id > 0) {
        $deleteOldImage = true;
    }
}

// --- Jeśli edycja, pobierz dotychczasowy image_path (do ewentualnego usunięcia) ---
$oldImagePath = null;
if ($id > 0) {
    $q = db()->prepare("SELECT image_path FROM products WHERE id = ?");
    $q->execute([$id]);
    $oldImagePath = (string)($q->fetchColumn() ?: '');
}

// --- Upsert produktu ---
try {
    if ($id > 0) {
        // UPDATE
        $sql = "
      UPDATE products
         SET name = ?, sku = ?, price = ?, price_unit = ?, stock = ?, status = ?,
             category = ?, seats = ?, doors = ?, gearbox = ?, fuel = ?, description = ?
             " . ($uploadedImagePath !== null ? ", image_path = ?" : "") . "
       WHERE id = ?
       LIMIT 1
    ";
        $params = [
            $name,
            $sku,
            $price,
            $price_unit,
            $stock,
            $status,
            $category,
            $seats,
            $doors,
            $gearbox,
            $fuel,
            $description
        ];
        if ($uploadedImagePath !== null) $params[] = $uploadedImagePath;
        $params[] = $id;

        $stmt = db()->prepare($sql);
        $stmt->execute($params);
    } else {
        // INSERT
        $sql = "
      INSERT INTO products
        (name, sku, price, price_unit, stock, status, category, seats, doors, gearbox, fuel, description, image_path)
      VALUES
        (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ";
        $stmt = db()->prepare($sql);
        $stmt->execute([
            $name,
            $sku,
            $price,
            $price_unit,
            $stock,
            $status,
            $category,
            $seats,
            $doors,
            $gearbox,
            $fuel,
            $description,
            $uploadedImagePath
        ]);
        $id = (int)db()->lastInsertId();
    }
} catch (PDOException $e) {
    if ((int)$e->getCode() === 23000) {
        http_response_code(409);
        exit('Konflikt: SKU musi być unikalny.');
    }
    throw $e;
}

// --- Usuwanie starego zdjęcia (jeśli trzeba) ---
if ($id > 0) {
    // 1) jeśli zaznaczono checkbox „Usuń obecne zdjęcie” i było stare
    if ($removeImage && $oldImagePath) {
        $abs = dirname(__DIR__) . $oldImagePath;
        if (is_file($abs)) @unlink($abs);
        // Wyczyść w DB, jeśli nie przesłano nowego
        if ($uploadedImagePath === null) {
            $stmt = db()->prepare("UPDATE products SET image_path = NULL WHERE id = ? LIMIT 1");
            $stmt->execute([$id]);
        }
    }
    // 2) jeśli przesłano nowe zdjęcie i było stare → skasuj stare
    if ($uploadedImagePath !== null && $oldImagePath && $oldImagePath !== $uploadedImagePath) {
        $abs = dirname(__DIR__) . $oldImagePath;
        if (is_file($abs)) @unlink($abs);
    }
}

// Redirect: wracamy do listy produktów
header('Location: ' . $BASE . '/index.php?page=dashboard-staff');
exit;
