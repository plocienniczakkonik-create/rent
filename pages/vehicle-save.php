<?php
// /pages/vehicle-save.php
require_once dirname(__DIR__) . '/auth/auth.php';
require_staff();
require_once dirname(__DIR__) . '/includes/db.php';

if (session_status() !== PHP_SESSION_ACTIVE) session_start();

function redirect_err($msg, $id = 0, $product_id = 0)
{
    $BASE = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
    $loc  = $BASE . '/index.php?page=vehicle-form' . ($id ? '&id=' . (int)$id : '');
    if ($product_id) $loc .= ($id ? '&' : '&') . 'product_id=' . (int)$product_id;
    header('Location: ' . $loc . '&err=' . urlencode($msg));
    exit;
}

if (($_POST['_token'] ?? '') !== ($_SESSION['_token'] ?? '')) {
    redirect_err('Błędny token CSRF.');
}

$id          = (int)($_POST['id'] ?? 0);
$product_id  = (int)($_POST['product_id'] ?? 0);
$reg         = trim($_POST['registration_number'] ?? '');
$status      = trim($_POST['status'] ?? '');
$vin         = trim($_POST['vin'] ?? '');
$mileage     = ($_POST['mileage'] !== '' ? (int)$_POST['mileage'] : null);
$location    = trim($_POST['location'] ?? '');
$insp_date   = ($_POST['inspection_date'] !== '' ? $_POST['inspection_date'] : null);
$insur_date  = ($_POST['insurance_expiry_date'] !== '' ? $_POST['insurance_expiry_date'] : null);
$notes       = trim($_POST['notes'] ?? '');

if ($product_id <= 0) redirect_err('Wybierz model pojazdu.', $id, $product_id);
if ($reg === '')       redirect_err('Podaj numer rejestracyjny.', $id, $product_id);
if ($status === '')    redirect_err('Wybierz status.', $id, $product_id);

try {
    // unikalność rejestracji
    if ($id > 0) {
        $st = $db->prepare("SELECT COUNT(*) FROM vehicles WHERE registration_number = :reg AND id <> :id");
        $st->execute([':reg' => $reg, ':id' => $id]);
    } else {
        $st = $db->prepare("SELECT COUNT(*) FROM vehicles WHERE registration_number = :reg");
        $st->execute([':reg' => $reg]);
    }
    if ((int)$st->fetchColumn() > 0) {
        redirect_err('Pojazd z takim numerem rejestracyjnym już istnieje.', $id, $product_id);
    }

    if ($id > 0) {
        $sql = "UPDATE vehicles
            SET product_id=:product_id, registration_number=:reg, vin=:vin, status=:status,
                mileage=:mileage, location=:location, inspection_date=:insp, insurance_expiry_date=:insur, notes=:notes
            WHERE id=:id";
        $st = $db->prepare($sql);
        $st->execute([
            ':product_id' => $product_id,
            ':reg'        => $reg,
            ':vin'        => $vin ?: null,
            ':status'     => $status,
            ':mileage'    => $mileage,
            ':location'   => $location ?: null,
            ':insp'       => $insp_date,
            ':insur'      => $insur_date,
            ':notes'      => $notes ?: null,
            ':id'         => $id,
        ]);
    } else {
        $sql = "INSERT INTO vehicles (product_id, registration_number, vin, status, mileage, location, inspection_date, insurance_expiry_date, notes)
            VALUES (:product_id, :reg, :vin, :status, :mileage, :location, :insp, :insur, :notes)";
        $st = $db->prepare($sql);
        $st->execute([
            ':product_id' => $product_id,
            ':reg'        => $reg,
            ':vin'        => $vin ?: null,
            ':status'     => $status,
            ':mileage'    => $mileage,
            ':location'   => $location ?: null,
            ':insp'       => $insp_date,
            ':insur'      => $insur_date,
            ':notes'      => $notes ?: null,
        ]);
        $id = (int)$db->lastInsertId();
    }

    $BASE = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
    header('Location: ' . $BASE . '/index.php?page=vehicle-detail&id=' . (int)$id);
    exit;
} catch (Throwable $e) {
    redirect_err('Błąd zapisu: ' . $e->getMessage(), $id, $product_id);
}
