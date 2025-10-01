<?php
// /pages/promo-delete.php
require_once dirname(__DIR__) . '/auth/auth.php';
$staff = require_staff();
require_once dirname(__DIR__) . '/includes/db.php';

$BASE = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';

$id   = (int)($_GET['id'] ?? 0);
$csrf = (string)($_GET['csrf'] ?? '');

// CSRF: w tym endpointzie przyjmujemy token przez GET (link w tabeli)
if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrf)) {
    http_response_code(403);
    exit('Invalid CSRF token');
}

if ($id > 0) {
    $stmt = db()->prepare("DELETE FROM promotions WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);
}

header('Location: ' . $BASE . '/index.php?page=dashboard-staff#pane-promos');
exit;
