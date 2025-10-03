

<?php
require_once __DIR__ . '/../auth/auth.php';
require_staff();

echo '<pre style="color:blue">DEBUG $_POST: ' . print_r($_POST, true) . '</pre>';

/* --- Gdy POST jest pusty (np. zbyt duży upload) — pokaż czytelny błąd --- */
if (empty($_POST) && !empty($_SERVER['CONTENT_LENGTH'])) {
    http_response_code(413);
    exit('Przesłane dane są za duże (POST jest pusty). ' .
        'Zmniejsz liczbę/rozmiar załączników lub zwiększ limity post_max_size i upload_max_filesize w php.ini.');
}

/* --- CSRF --- */
echo '<pre style="color:red">DEBUG: SESSION _token = ' . htmlspecialchars($_SESSION['_token'] ?? '') . "\n" .
    'POST _token = ' . htmlspecialchars($_POST['_token'] ?? '') . "</pre>";
csrf_verify();

require_once __DIR__ . '/../includes/db.php';
$db = db();

$vehicle_id   = (int)($_POST['vehicle_id'] ?? 0);
$id           = (int)($_POST['id'] ?? 0);
$service_date = $_POST['service_date'] ?? null;

if (!$vehicle_id || !$service_date) {
    header('Location: ' . BASE_URL . '/index.php?page=vehicle-service-form&vehicle_id=' . $vehicle_id . '&err=1');
    exit;
}

$data = [
    'vehicle_id'    => $vehicle_id,
    'service_date'  => $service_date,
    'odometer_km'   => ($_POST['odometer_km'] === '' ? null : (int)$_POST['odometer_km']),
    'workshop_name' => trim($_POST['workshop_name'] ?? ''),
    'invoice_no'    => trim($_POST['invoice_no'] ?? ''),
    'reported_by'   => current_user()['id'] ?? null,
    'handled_by'    => current_user()['id'] ?? null,
    'cost_total'    => (float)($_POST['cost_total'] ?? 0),
    'issues_found'  => trim($_POST['issues_found'] ?? ''),
    'actions_taken' => trim($_POST['actions_taken'] ?? ''),
    'notes'         => trim($_POST['notes'] ?? ''),
];

if ($id) {
    $sql = "UPDATE vehicle_services SET
              service_date=:service_date,
              odometer_km=:odometer_km,
              workshop_name=:workshop_name,
              invoice_no=:invoice_no,
              handled_by=:handled_by,
              cost_total=:cost_total,
              issues_found=:issues_found,
              actions_taken=:actions_taken,
              notes=:notes
            WHERE id=:id";
    $params = [
        'service_date'  => $data['service_date'],
        'odometer_km'   => $data['odometer_km'],
        'workshop_name' => $data['workshop_name'],
        'invoice_no'    => $data['invoice_no'],
        'handled_by'    => $data['handled_by'],
        'cost_total'    => $data['cost_total'],
        'issues_found'  => $data['issues_found'],
        'actions_taken' => $data['actions_taken'],
        'notes'         => $data['notes'],
        'id'            => $id
    ];
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $service_id = $id;
} else {
    $sql = "INSERT INTO vehicle_services
              (vehicle_id, service_date, odometer_km, workshop_name, invoice_no,
               reported_by, handled_by, cost_total, issues_found, actions_taken, notes)
            VALUES
              (:vehicle_id, :service_date, :odometer_km, :workshop_name, :invoice_no,
               :reported_by, :handled_by, :cost_total, :issues_found, :actions_taken, :notes)";
    $params = [
        'vehicle_id'    => $data['vehicle_id'],
        'service_date'  => $data['service_date'],
        'odometer_km'   => $data['odometer_km'],
        'workshop_name' => $data['workshop_name'],
        'invoice_no'    => $data['invoice_no'],
        'reported_by'   => $data['reported_by'],
        'handled_by'    => $data['handled_by'],
        'cost_total'    => $data['cost_total'],
        'issues_found'  => $data['issues_found'],
        'actions_taken' => $data['actions_taken'],
        'notes'         => $data['notes']
    ];
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $service_id = (int)$db->lastInsertId();
}

/** Upload plików */
$uploadDir = __DIR__ . '/../uploads/services/' . $service_id;
@mkdir($uploadDir, 0775, true);

$allowed = ['application/pdf', 'image/jpeg', 'image/png'];
if (!empty($_FILES['files']['name'][0])) {
    foreach ($_FILES['files']['name'] as $idx => $name) {
        if ($_FILES['files']['error'][$idx] !== UPLOAD_ERR_OK) continue;
        $tmp  = $_FILES['files']['tmp_name'][$idx];
        $type = @mime_content_type($tmp);
        if (!in_array($type, $allowed, true)) continue;

        $safe = preg_replace('/[^a-zA-Z0-9._-]+/', '_', $name);
        $dest = $uploadDir . '/' . uniqid('', true) . '_' . $safe;
        if (move_uploaded_file($tmp, $dest)) {
            $stmt = $db->prepare(
                "INSERT INTO vehicle_service_files (service_id, file_path, original_name, mime_type, file_size, uploaded_by)
                 VALUES (?,?,?,?,?,?)"
            );
            $stmt->execute([
                $service_id,
                str_replace(__DIR__ . '/..', '', $dest),
                $name,
                $type,
                (int)filesize($dest),
                current_user()['id'] ?? null
            ]);
        }
    }
}

/* Pobierz product_id na podstawie vehicle_id */
$product_id = 0;
$stmt = $db->prepare('SELECT product_id FROM vehicles WHERE id = ? LIMIT 1');
$stmt->execute([$vehicle_id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if ($row && isset($row['product_id'])) {
    $product_id = (int)$row['product_id'];
}
header('Location: ' . BASE_URL . '/index.php?page=vehicle-detail&id=' . $vehicle_id . '&ok=1');
exit;
