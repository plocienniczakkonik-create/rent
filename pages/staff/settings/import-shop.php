<?php
// Import danych sklepu (CSV/XLSX) - samodzielny endpoint
require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo 'Method Not Allowed';
    exit;
}
$type = $_POST['import_type'] ?? '';
$db = db();
$fieldsMap = [
    'models' => ['id','name','sku','price','category','status'],
    'vehicles' => ['id','model_id','vin','reg_number','status'],
    'locations' => ['id','name','slug','sort_order','status'],
    'classes' => ['id','name','slug','sort_order','status'],
    'types' => ['id','name','slug','sort_order','status'],
    'extras' => ['id','name','slug','sort_order','status','price','charge_type'],
];
if (!isset($fieldsMap[$type])) {
    http_response_code(400);
    echo 'Nieobsługiwany lub nieokreślony typ danych do importu.';
    exit;
}
$fields = $fieldsMap[$type];
if (!isset($_FILES['import_file']) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo 'Błąd podczas przesyłania pliku.';
    exit;
}
$file = $_FILES['import_file']['tmp_name'];
$ext = strtolower(pathinfo($_FILES['import_file']['name'], PATHINFO_EXTENSION));
$imported = 0;
$skipped = 0;
if ($ext === 'csv') {
    $handle = fopen($file, 'r');
    if (!$handle) {
        http_response_code(400);
        echo 'Nie można otworzyć pliku CSV.';
        exit;
    }
    $header = fgetcsv($handle, 1000, ',');
    if ($header === false || array_diff($fields, $header)) {
        http_response_code(400);
        echo 'Nieprawidłowy nagłówek pliku CSV. Wymagane kolumny: '.implode(", ", $fields);
        fclose($handle);
        exit;
    }
    while (($row = fgetcsv($handle, 1000, ',')) !== false) {
        $data = array_combine($header, $row);
        if (!$data || !$fields) { $skipped++; continue; }
        // ...mapowanie i REPLACE INTO jak wcześniej...
        if ($type === 'models') {
            if (!$data['name'] || !$data['sku']) { $skipped++; continue; }
            $stmt = $db->prepare("REPLACE INTO products (id, name, sku, price, category, status) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $data['id'] ?? null,
                $data['name'],
                $data['sku'],
                $data['price'] ?? 0,
                $data['category'] ?? '',
                $data['status'] ?? 'active'
            ]);
            $imported++;
        } elseif ($type === 'vehicles') {
            if (!$data['vin'] || !$data['reg_number']) { $skipped++; continue; }
            $stmt = $db->prepare("REPLACE INTO vehicles (id, model_id, vin, reg_number, status) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([
                $data['id'] ?? null,
                $data['model_id'] ?? null,
                $data['vin'],
                $data['reg_number'],
                $data['status'] ?? 'active'
            ]);
            $imported++;
        } elseif ($type === 'locations') {
            if (!$data['name']) { $skipped++; continue; }
            $stmt = $db->prepare("REPLACE INTO dict_terms (id, name, slug, sort_order, status, dict_type_id) VALUES (?, ?, ?, ?, ?, (SELECT id FROM dict_types WHERE slug='location'))");
            $stmt->execute([
                $data['id'] ?? null,
                $data['name'],
                $data['slug'] ?? '',
                $data['sort_order'] ?? 0,
                $data['status'] ?? 'active'
            ]);
            $imported++;
        } elseif ($type === 'classes') {
            if (!$data['name']) { $skipped++; continue; }
            $stmt = $db->prepare("REPLACE INTO dict_terms (id, name, slug, sort_order, status, dict_type_id) VALUES (?, ?, ?, ?, ?, (SELECT id FROM dict_types WHERE slug='car_class'))");
            $stmt->execute([
                $data['id'] ?? null,
                $data['name'],
                $data['slug'] ?? '',
                $data['sort_order'] ?? 0,
                $data['status'] ?? 'active'
            ]);
            $imported++;
        } elseif ($type === 'types') {
            if (!$data['name']) { $skipped++; continue; }
            $stmt = $db->prepare("REPLACE INTO dict_terms (id, name, slug, sort_order, status, dict_type_id) VALUES (?, ?, ?, ?, ?, (SELECT id FROM dict_types WHERE slug='car_type'))");
            $stmt->execute([
                $data['id'] ?? null,
                $data['name'],
                $data['slug'] ?? '',
                $data['sort_order'] ?? 0,
                $data['status'] ?? 'active'
            ]);
            $imported++;
        } elseif ($type === 'extras') {
            if (!$data['name']) { $skipped++; continue; }
            $stmt = $db->prepare("REPLACE INTO dict_terms (id, name, slug, sort_order, status, price, charge_type, dict_type_id) VALUES (?, ?, ?, ?, ?, ?, ?, (SELECT id FROM dict_types WHERE slug='addon'))");
            $stmt->execute([
                $data['id'] ?? null,
                $data['name'],
                $data['slug'] ?? '',
                $data['sort_order'] ?? 0,
                $data['status'] ?? 'active',
                $data['price'] ?? 0,
                $data['charge_type'] ?? 'once'
            ]);
            $imported++;
        } else {
            $skipped++;
        }
    }
    fclose($handle);
    echo '<div class="alert alert-success mt-3">Zaimportowano: ' . $imported . ' / Pominięto: ' . $skipped . '</div>';
    exit;
} elseif ($ext === 'xlsx') {
    $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file);
    $sheet = $spreadsheet->getActiveSheet();
    $header = [];
    $rows = [];
    foreach ($sheet->getRowIterator() as $rowIndex => $row) {
        $cellIterator = $row->getCellIterator();
        $cellIterator->setIterateOnlyExistingCells(false);
        $rowData = [];
        foreach ($cellIterator as $cell) {
            $rowData[] = $cell->getValue();
        }
        if ($rowIndex === 1) {
            $header = $rowData;
        } else {
            $rows[] = $rowData;
        }
    }
    if ($header === [] || array_diff($fields, $header)) {
        http_response_code(400);
        echo 'Nieprawidłowy nagłówek pliku XLSX. Wymagane kolumny: '.implode(", ", $fields);
        exit;
    }
    foreach ($rows as $row) {
        $data = array_combine($header, $row);
        if (!$data || !$fields) { $skipped++; continue; }
        // ...mapowanie i REPLACE INTO jak wyżej...
        if ($type === 'models') {
            if (!$data['name'] || !$data['sku']) { $skipped++; continue; }
            $stmt = $db->prepare("REPLACE INTO products (id, name, sku, price, category, status) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $data['id'] ?? null,
                $data['name'],
                $data['sku'],
                $data['price'] ?? 0,
                $data['category'] ?? '',
                $data['status'] ?? 'active'
            ]);
            $imported++;
        } elseif ($type === 'vehicles') {
            if (!$data['vin'] || !$data['reg_number']) { $skipped++; continue; }
            $stmt = $db->prepare("REPLACE INTO vehicles (id, model_id, vin, reg_number, status) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([
                $data['id'] ?? null,
                $data['model_id'] ?? null,
                $data['vin'],
                $data['reg_number'],
                $data['status'] ?? 'active'
            ]);
            $imported++;
        } elseif ($type === 'locations') {
            if (!$data['name']) { $skipped++; continue; }
            $stmt = $db->prepare("REPLACE INTO dict_terms (id, name, slug, sort_order, status, dict_type_id) VALUES (?, ?, ?, ?, ?, (SELECT id FROM dict_types WHERE slug='location'))");
            $stmt->execute([
                $data['id'] ?? null,
                $data['name'],
                $data['slug'] ?? '',
                $data['sort_order'] ?? 0,
                $data['status'] ?? 'active'
            ]);
            $imported++;
        } elseif ($type === 'classes') {
            if (!$data['name']) { $skipped++; continue; }
            $stmt = $db->prepare("REPLACE INTO dict_terms (id, name, slug, sort_order, status, dict_type_id) VALUES (?, ?, ?, ?, ?, (SELECT id FROM dict_types WHERE slug='car_class'))");
            $stmt->execute([
                $data['id'] ?? null,
                $data['name'],
                $data['slug'] ?? '',
                $data['sort_order'] ?? 0,
                $data['status'] ?? 'active'
            ]);
            $imported++;
        } elseif ($type === 'types') {
            if (!$data['name']) { $skipped++; continue; }
            $stmt = $db->prepare("REPLACE INTO dict_terms (id, name, slug, sort_order, status, dict_type_id) VALUES (?, ?, ?, ?, ?, (SELECT id FROM dict_types WHERE slug='car_type'))");
            $stmt->execute([
                $data['id'] ?? null,
                $data['name'],
                $data['slug'] ?? '',
                $data['sort_order'] ?? 0,
                $data['status'] ?? 'active'
            ]);
            $imported++;
        } elseif ($type === 'extras') {
            if (!$data['name']) { $skipped++; continue; }
            $stmt = $db->prepare("REPLACE INTO dict_terms (id, name, slug, sort_order, status, price, charge_type, dict_type_id) VALUES (?, ?, ?, ?, ?, ?, ?, (SELECT id FROM dict_types WHERE slug='addon'))");
            $stmt->execute([
                $data['id'] ?? null,
                $data['name'],
                $data['slug'] ?? '',
                $data['sort_order'] ?? 0,
                $data['status'] ?? 'active',
                $data['price'] ?? 0,
                $data['charge_type'] ?? 'once'
            ]);
            $imported++;
        } else {
            $skipped++;
        }
    }
    echo '<div class="alert alert-success mt-3">Zaimportowano: ' . $imported . ' / Pominięto: ' . $skipped . '</div>';
    exit;
} else {
    http_response_code(400);
    echo 'Nieobsługiwany format pliku. Dozwolone: CSV, XLSX.';
    exit;
}
