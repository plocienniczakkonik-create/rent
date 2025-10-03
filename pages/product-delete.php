<?php
// /pages/product-delete.php
require_once dirname(__DIR__) . '/auth/auth.php';
$staff = require_staff();
require_once dirname(__DIR__) . '/includes/db.php';

// CSRF można przekazywać GET-em przy uważnym użyciu (link + confirm)
if (!isset($_GET['_token']) || !hash_equals($_SESSION['_token'] ?? '', (string)$_GET['_token'])) {
    http_response_code(403);
    exit('Invalid CSRF token');
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id > 0) {
    $stmt = db()->prepare("DELETE FROM products WHERE id=?");
    $stmt->execute([$id]);
}

$BASE = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
header('Location: ' . $BASE . '/index.php?page=dashboard-staff');
exit;
