<?php
// Test autoloadera Composer i PhpSpreadsheet
require_once __DIR__ . '/vendor/autoload.php';

if (class_exists('PhpOffice\PhpSpreadsheet\Spreadsheet')) {
    echo 'OK: PhpSpreadsheet działa';
} else {
    echo 'BŁĄD: PhpSpreadsheet nie działa';
}
