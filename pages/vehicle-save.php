<?php
// /pages/vehicle-save.php
require_once dirname(__DIR__) . '/auth/auth.php';
$staff = require_staff();
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/_helpers.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

csrf_verify();

$BASE = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
$db   = db();
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

/** Helper: sprawdź, czy kolumna vehicles.vin dopuszcza NULL */
function vin_allows_null(PDO $db): bool
{
    $r = $db->query("SHOW COLUMNS FROM vehicles LIKE 'vin'")->fetch(PDO::FETCH_ASSOC);
    if (!$r) return true; // defensywnie
    return strtoupper((string)$r['Null']) === 'YES';
}

/** Helper: unikalny placeholder VIN (17 znaków, litery/cyfry) */
function make_tmp_vin(): string
{
    // 'TMP' + 14 hex = 17 znaków
    return 'TMP' . strtoupper(substr(bin2hex(random_bytes(8)), 0, 14));
}

/* Dane z formularza */
$id                 = (int)($_POST['id'] ?? 0);
$product_id         = (int)($_POST['product_id'] ?? 0);
$registrationNumber = (string)($_POST['registration_number'] ?? $_POST['reg_no'] ?? '');
$status             = (string)($_POST['status'] ?? '');
$mileage            = ($_POST['mileage'] ?? '') === '' ? null : (int)$_POST['mileage'];
$vinRaw             = (string)($_POST['vin'] ?? '');
$location           = trim((string)($_POST['location'] ?? ''));
$notes              = trim((string)($_POST['notes'] ?? ''));
$insp_raw           = trim((string)($_POST['inspection_date'] ?? ''));
$ins_exp_raw        = trim((string)($_POST['insurance_expiry_date'] ?? $_POST['insurance_until'] ?? ''));

/* Normalizacja */
$registrationNumber = strtoupper(trim($registrationNumber));
$vinNorm = strtoupper(str_replace(' ', '', trim($vinRaw)));
// VIN opcjonalny -> null jeśli pusty
$vin = ($vinNorm === '') ? null : $vinNorm;

/* Mapowanie etykiet PL -> wartości techniczne */
$label2val = [
    'Dostępny'      => 'available',
    'Zarezerwowany' => 'booked',
    'Serwis'        => 'maintenance',
    'Niedostępny'   => 'unavailable',
    'Wycofany'      => 'retired',
];
$status = $label2val[$status] ?? $status;

/* Daty */
$inspection_date       = $insp_raw    !== '' ? date('Y-m-d', strtotime($insp_raw))    : null;
$insurance_expiry_date = $ins_exp_raw !== '' ? date('Y-m-d', strtotime($ins_exp_raw)) : null;

/* Walidacja */
$allowed_status = ['available', 'booked', 'maintenance', 'unavailable', 'retired'];
$errors = [];
if ($product_id <= 0)                                $errors[] = 'Wybierz model (produkt).';
if ($registrationNumber === '')                      $errors[] = 'Podaj numer rejestracyjny.';
if ($status === '' || !in_array($status, $allowed_status, true))
    $errors[] = 'Wybierz prawidłowy status.';
if ($vin !== null) {
    if (strlen($vin) !== 17 || preg_match('/[^A-HJ-NPR-Z0-9]/', $vin)) {
        $errors[] = 'VIN musi mieć 17 znaków (A–H, J–N, P, R–Z, 0–9), bez I, O, Q i spacji.';
    }
}

if ($errors) {
    $_SESSION['flash_error'] = implode(' ', $errors);
    $back = $BASE . '/index.php?page=vehicle-form' . ($id ? ('&id=' . $id) : ($product_id ? ('&product_id=' . $product_id) : ''));
    header('Location: ' . $back);
    exit;
}

/* Ustalenie zachowania VIN vs. schemat DB */
$vinAllowsNull = vin_allows_null($db);

/* Duplikaty (app-level) */
try {
    // rejestracja – zawsze
    if ($id > 0) {
        $du = $db->prepare("SELECT id FROM vehicles WHERE registration_number = ? AND id <> ? LIMIT 1");
        $du->execute([$registrationNumber, $id]);
    } else {
        $du = $db->prepare("SELECT id FROM vehicles WHERE registration_number = ? LIMIT 1");
        $du->execute([$registrationNumber]);
    }
    if ($du->fetchColumn()) {
        $_SESSION['flash_error'] = 'Numer rejestracyjny musi być unikalny.';
        $back = $BASE . '/index.php?page=vehicle-form' . ($id ? ('&id=' . $id) : ($product_id ? ('&product_id=' . $product_id) : ''));
        header('Location: ' . $back);
        exit;
    }

    // VIN – tylko jeśli faktycznie coś chcemy wstawić (nie-null)
    if ($vin !== null) {
        if ($id > 0) {
            $duv = $db->prepare("SELECT id FROM vehicles WHERE vin = ? AND id <> ? LIMIT 1");
            $duv->execute([$vin, $id]);
        } else {
            $duv = $db->prepare("SELECT id FROM vehicles WHERE vin = ? LIMIT 1");
            $duv->execute([$vin]);
        }
        if ($duv->fetchColumn()) {
            $_SESSION['flash_error'] = 'VIN musi być unikalny.';
            $back = $BASE . '/index.php?page=vehicle-form' . ($id ? ('&id=' . $id) : ($product_id ? ('&product_id=' . $product_id) : ''));
            header('Location: ' . $back);
            exit;
        }
    }

    /* Zapis */
    if ($id > 0) {
        // UPDATE
        // jeśli próbujesz wyczyścić VIN, a kolumna nie pozwala na NULL -> zostaw stary VIN i pokaż info
        if ($vin === null && !$vinAllowsNull) {
            // pobierz obecny VIN
            $cur = $db->prepare("SELECT vin FROM vehicles WHERE id = ? LIMIT 1");
            $cur->execute([$id]);
            $existingVin = $cur->fetchColumn();
            if ($existingVin !== false && $existingVin !== null && $existingVin !== '') {
                $_SESSION['flash_ok'] = 'Zapisano bez zmiany VIN (w tym systemie VIN nie może być pusty).';
                $vinToStore = $existingVin;
            } else {
                // skrajny przypadek: w bazie pusto, musimy coś dać
                $vinToStore = make_tmp_vin();
            }
        } else {
            $vinToStore = $vin; // może być null, jeśli schema pozwala
        }

        $sql = "UPDATE vehicles
                   SET product_id = ?,
                       registration_number = ?,
                       status = ?,
                       mileage = ?,
                       vin = ?,
                       location = ?,
                       inspection_date = ?,
                       insurance_expiry_date = ?,
                       notes = ?,
                       updated_at = NOW()
                 WHERE id = ? LIMIT 1";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            $product_id,
            $registrationNumber,
            $status,
            $mileage,
            $vinToStore,
            $location,
            $inspection_date,
            $insurance_expiry_date,
            $notes,
            $id
        ]);

        if (empty($_SESSION['flash_ok'])) {
            $_SESSION['flash_ok'] = 'Zapisano zmiany pojazdu.';
        }
    } else {
        // INSERT
        $vinToStore = $vin;
        if ($vinToStore === null && !$vinAllowsNull) {
            // kolumna nie przyjmuje NULL -> dajemy unikalny placeholder
            $vinToStore = make_tmp_vin();
        }

        $sql = "INSERT INTO vehicles
                    (product_id, registration_number, status, mileage, vin, location,
                     inspection_date, insurance_expiry_date, notes, created_at, updated_at)
                VALUES
                    (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            $product_id,
            $registrationNumber,
            $status,
            $mileage,
            $vinToStore,
            $location,
            $inspection_date,
            $insurance_expiry_date,
            $notes
        ]);

        $id = (int)$db->lastInsertId();
        $_SESSION['flash_ok'] = 'Dodano nowy pojazd.';
    }

    header('Location: ' . $BASE . '/index.php?page=vehicle-detail&id=' . $id);
    exit;
} catch (PDOException $e) {
    if ($e->getCode() === '23000') {
        $_SESSION['flash_error'] = 'Duplikat w danych (nr rej. lub VIN).';
        $back = $BASE . '/index.php?page=vehicle-form' . ($id ? ('&id=' . $id) : ($product_id ? ('&product_id=' . $product_id) : ''));
        header('Location: ' . $back);
        exit;
    }
    throw $e;
}
