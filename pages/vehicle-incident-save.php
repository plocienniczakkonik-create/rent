<?php
// /pages/vehicle-incident-save.php
require_once __DIR__ . '/../auth/auth.php';
require_staff();

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

$vehicle_id    = (int)($_POST['vehicle_id'] ?? 0);
$id            = (int)($_POST['id'] ?? 0);
$incident_date = $_POST['incident_date'] ?? null;

if (!$vehicle_id || !$incident_date) {
    header('Location: ' . BASE_URL . '/index.php?page=vehicle-incident-form&vehicle_id=' . $vehicle_id . '&err=1');
    exit;
}

/** Normalizacja daty: input datetime-local -> 'Y-m-d H:i:s' */
$incident_date_sql = date('Y-m-d H:i:s', strtotime($incident_date));

$data = [
    'vehicle_id'         => $vehicle_id,
    'incident_date'      => $incident_date_sql,
    'driver_name'        => trim($_POST['driver_name'] ?? ''),
    'location'           => trim($_POST['location'] ?? ''),
    'damage_desc'        => trim($_POST['damage_desc'] ?? ''),
    'fault'              => in_array($_POST['fault'] ?? 'unknown', ['our', 'other', 'shared', 'unknown'], true)
        ? $_POST['fault'] : 'unknown',
    'police_called'      => isset($_POST['police_called']) ? 1 : 0,
    'police_report_no'   => trim($_POST['police_report_no'] ?? ''),
    'repair_cost'        => (float)($_POST['repair_cost'] ?? 0),
    'insurance_claim_no' => trim($_POST['insurance_claim_no'] ?? ''),
    'reported_by'        => current_user()['id'] ?? null,
    'handled_by'         => current_user()['id'] ?? null,
    'notes'              => trim($_POST['notes'] ?? ''),
];

if ($id) {
    $sql = "UPDATE vehicle_incidents
          SET incident_date=:incident_date, driver_name=:driver_name, location=:location,
              damage_desc=:damage_desc, fault=:fault, police_called=:police_called,
              police_report_no=:police_report_no, repair_cost=:repair_cost,
              insurance_claim_no=:insurance_claim_no, handled_by=:handled_by, notes=:notes
          WHERE id=:id";
    $params = [
        'incident_date'      => $data['incident_date'],
        'driver_name'        => $data['driver_name'],
        'location'           => $data['location'],
        'damage_desc'        => $data['damage_desc'],
        'fault'              => $data['fault'],
        'police_called'      => $data['police_called'],
        'police_report_no'   => $data['police_report_no'],
        'repair_cost'        => $data['repair_cost'],
        'insurance_claim_no' => $data['insurance_claim_no'],
        'handled_by'         => $data['handled_by'],
        'notes'              => $data['notes'],
        'id'                 => $id
    ];
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $incident_id = $id;
} else {
    $sql = "INSERT INTO vehicle_incidents
          (vehicle_id, incident_date, driver_name, location, damage_desc, fault, police_called,
           police_report_no, repair_cost, insurance_claim_no, reported_by, handled_by, notes)
          VALUES
          (:vehicle_id, :incident_date, :driver_name, :location, :damage_desc, :fault, :police_called,
           :police_report_no, :repair_cost, :insurance_claim_no, :reported_by, :handled_by, :notes)";
    $stmt = $db->prepare($sql);
    $stmt->execute($data);
    $incident_id = (int)$db->lastInsertId();
}

/** Upload plików */
$uploadDir = __DIR__ . '/../uploads/incidents/' . $incident_id;
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
            $stmt = $db->prepare("INSERT INTO vehicle_incident_files
                            (incident_id, file_path, original_name, mime_type, file_size, uploaded_by)
                            VALUES (?,?,?,?,?,?)");
            $stmt->execute([
                $incident_id,
                str_replace(__DIR__ . '/..', '', $dest), // ścieżka względna do /pages/..
                $name,
                $type,
                (int)filesize($dest),
                current_user()['id'] ?? null
            ]);
        }
    }
}

/* Powrót do widoku pojazdu (u Ciebie to vehicles-manage) */
header('Location: ' . BASE_URL . '/index.php?page=vehicle-detail&id=' . $vehicle_id . '&ok=1');
exit;
