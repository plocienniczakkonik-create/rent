<?php
require_once dirname(__DIR__) . '/auth/auth.php';
$staff = require_staff();
require_once dirname(__DIR__) . '/includes/db.php';

if (session_status() !== PHP_SESSION_ACTIVE) session_start();

$BASE = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
$id   = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$csrf = $_GET['csrf'] ?? '';

if ($id <= 0) {
    $_SESSION['flash_error'] = 'Brak identyfikatora pojazdu.';
    header('Location: ' . $BASE . '/index.php?page=dashboard-staff#pane-vehicles');
    exit;
}
if (!function_exists('csrf_token') || $csrf !== csrf_token()) {
    $_SESSION['flash_error'] = 'Nieprawidłowy token bezpieczeństwa.';
    header('Location: ' . $BASE . '/index.php?page=dashboard-staff#pane-vehicles');
    exit;
}

$db = db();
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// bierzemy product_id do powrotu
$st = $db->prepare("SELECT product_id FROM vehicles WHERE id = ? LIMIT 1");
$st->execute([$id]);
$product_id = (int)($st->fetchColumn() ?: 0);

try {
    $del = $db->prepare("DELETE FROM vehicles WHERE id = ? LIMIT 1");
    $del->execute([$id]);

    if ($del->rowCount() > 0) {
        $_SESSION['flash_ok'] = "Egzemplarz #{$id} został usunięty.";
    } else {
        $_SESSION['flash_error'] = "Nie znaleziono egzemplarza #{$id}.";
    }
} catch (PDOException $e) {
    $_SESSION['flash_error'] = ($e->getCode() === '23000')
        ? 'Nie można usunąć – egzemplarz jest powiązany z innymi danymi (np. rezerwacjami).'
        : 'Błąd usuwania: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
}

$target = $product_id > 0
    ? ($BASE . '/index.php?page=vehicles-manage&product=' . $product_id)
    : ($BASE . '/index.php?page=dashboard-staff#pane-vehicles');

header('Location: ' . $target);
exit;
