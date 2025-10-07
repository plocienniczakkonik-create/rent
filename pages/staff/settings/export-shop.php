<?php
// Eksport danych sklepu (CSV/XLSX) - samodzielny endpoint
require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo 'Method Not Allowed';
    exit;
}
$type = $_POST['export_type'] ?? '';
$db = db();
$map = [
    'models' => ['table' => 'products', 'fields' => ['id','name','sku','price','category','status']],
    'vehicles' => ['table' => 'vehicles', 'fields' => ['id','product_id','registration_number','vin','status']],
    'locations' => ['table' => 'dict_terms', 'fields' => ['id','name','slug','sort_order','status'], 'where' => "AND dict_type_id=(SELECT id FROM dict_types WHERE slug='location')"],
    'classes' => ['table' => 'dict_terms', 'fields' => ['id','name','slug','sort_order','status'], 'where' => "AND dict_type_id=(SELECT id FROM dict_types WHERE slug='car_class')"],
    'types' => ['table' => 'dict_terms', 'fields' => ['id','name','slug','sort_order','status'], 'where' => "AND dict_type_id=(SELECT id FROM dict_types WHERE slug='car_type')"],
    'extras' => ['table' => 'dict_terms', 'fields' => ['id','name','slug','sort_order','status','price','charge_type'], 'where' => "AND dict_type_id=(SELECT id FROM dict_types WHERE slug='addon')"],
];
if (!isset($map[$type])) {
    http_response_code(400);
    exit;
}
$m = $map[$type];
$fields = $m['fields'];
$fields_sql = implode(',', $fields);
$where = $m['where'] ?? '';
$rows = $db->query("SELECT $fields_sql FROM {$m['table']} WHERE 1 $where ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
if (!$rows) {
    http_response_code(204);
    exit;
}
if (isset($_POST['export_csv'])) {
    header('Content-Type: text/csv; charset=utf-8');
    $filename = 'export_' . $type . '_' . date('Ymd_His') . '.csv';
    header('Content-Disposition: attachment; filename=' . $filename);
    $out = fopen('php://output', 'w');
    fputcsv($out, $fields);
    foreach ($rows as $row) fputcsv($out, $row);
    fclose($out);
    exit;
} elseif (isset($_POST['export_xlsx'])) {
    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    foreach ($fields as $i => $f) {
        $col = chr(65 + $i);
        $sheet->setCellValue($col.'1', $f);
    }
    $rowNum = 2;
    foreach ($rows as $row) {
        foreach ($fields as $i => $f) {
            $col = chr(65 + $i);
            $sheet->setCellValue($col.$rowNum, $row[$f]);
        }
        $rowNum++;
    }
    $filename = 'export_' . $type . '_' . date('Ymd_His') . '.xlsx';
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename=' . $filename);
    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
} else {
    http_response_code(400);
    exit;
}
