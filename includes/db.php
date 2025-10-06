<?php
require_once __DIR__ . '/config.php';

function db(): PDO
{
    static $pdo = null;
    $needReconnect = false;
    if ($pdo) {
        try {
            $pdo->query('SELECT 1');
        } catch (PDOException $e) {
            $needReconnect = true;
        }
        if (!$needReconnect) return $pdo;
    }

    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
    $opts = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $opts);
    return $pdo;
}
