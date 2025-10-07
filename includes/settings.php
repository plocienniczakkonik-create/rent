<?php
// helpers/settings.php
// Funkcja pobierająca ustawienie z bazy (lub domyślne)
require_once __DIR__ . '/db.php';
function get_setting($key, $default = null) {
    static $cache = [];
    if (isset($cache[$key])) return $cache[$key];
    $db = db();
    $stmt = $db->prepare('SELECT setting_value FROM settings WHERE setting_key = ? LIMIT 1');
    $stmt->execute([$key]);
    $val = $stmt->fetchColumn();
    if ($val === false) return $default;
    $cache[$key] = $val;
    return $val;
}
