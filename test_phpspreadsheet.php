<?php
require_once __DIR__ . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;

$spreadsheet = new Spreadsheet();
if ($spreadsheet instanceof Spreadsheet) {
    echo 'OK: PhpSpreadsheet działa';
} else {
    echo 'BŁĄD: PhpSpreadsheet nie działa';
}
