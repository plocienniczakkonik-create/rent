<?php
// /pages/vehicle-incident-form.php
require_once __DIR__ . '/../auth/auth.php';
$staff = require_staff();

require_once __DIR__ . '/../includes/db.php';
$db = db();

$vehicle_id = (int)($_GET['vehicle_id'] ?? $_POST['vehicle_id'] ?? 0);
$id         = (int)($_GET['id'] ?? 0);

$incident = [
    'incident_date'      => date('Y-m-d\TH:i'),
    'driver_name'        => '',
    'location'           => '',
    'damage_desc'        => '',
    'fault'              => 'unknown',
    'police_called'      => 0,
    'police_report_no'   => '',
    'repair_cost'        => 0,
    'insurance_claim_no' => '',
    'notes'              => '',
];

if ($id) {
    $stmt = $db->prepare("SELECT * FROM vehicle_incidents WHERE id=?");
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        // zamiana DATETIME -> input datetime-local
        $row['incident_date'] = $row['incident_date'] ? date('Y-m-d\TH:i', strtotime($row['incident_date'])) : date('Y-m-d\TH:i');
        $incident = $row;
        $vehicle_id = (int)$row['vehicle_id'];
    }
}

require_once __DIR__ . '/../partials/head.php';
require_once __DIR__ . '/../partials/header.php';
?>
<div class="container py-4" style="max-width: 900px;">
    <h3 class="mb-4"><?= $id ? 'Edytuj kolizję / szkodę' : 'Zgłoś kolizję / szkodę' ?></h3>
    <form method="post" action="<?= BASE_URL ?>/index.php?page=vehicle-incident-save" enctype="multipart/form-data" class="vstack gap-3">
        <?php
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $tok = $_SESSION['_token'] ?? bin2hex(random_bytes(32));
        $_SESSION['_token'] = $tok;
        echo '<input type="hidden" name="_token" value="' . htmlspecialchars($tok, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '">';
        ?>
        <input type="hidden" name="id" value="<?= (int)$id ?>">
        <input type="hidden" name="vehicle_id" value="<?= (int)$vehicle_id ?>">

        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Data i godzina zdarzenia</label>
                <input type="datetime-local" name="incident_date" class="form-control" required
                    value="<?= htmlspecialchars($incident['incident_date']) ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Miejsce</label>
                <input type="text" name="location" class="form-control"
                    value="<?= htmlspecialchars($incident['location']) ?>">
            </div>

            <div class="col-md-6">
                <label class="form-label">Kierowca</label>
                <input type="text" name="driver_name" class="form-control"
                    value="<?= htmlspecialchars($incident['driver_name']) ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Koszt naprawy [zł]</label>
                <input type="number" step="0.01" min="0" name="repair_cost" class="form-control"
                    value="<?= htmlspecialchars((string)$incident['repair_cost']) ?>">
            </div>

            <div class="col-md-6">
                <label class="form-label">Czyja wina</label>
                <select name="fault" class="form-select" required>
                    <?php
                    $opts = [
                        'our'     => 'nasza',
                        'other'   => 'druga strona',
                        'shared'  => 'współwina',
                        'unknown' => 'nieustalona',
                    ];
                    foreach ($opts as $val => $label):
                    ?>
                        <option value="<?= $val ?>" <?= ($incident['fault'] === $val ? 'selected' : '') ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-6 d-flex align-items-end">
                <div class="form-check mt-4">
                    <input class="form-check-input" type="checkbox" id="police_called" name="police_called" value="1"
                        <?= ((int)$incident['police_called'] === 1 ? 'checked' : '') ?>>
                    <label class="form-check-label" for="police_called">Wezwano policję</label>
                </div>
            </div>

            <div class="col-md-6">
                <label class="form-label">Nr notatki / raportu policji</label>
                <input type="text" name="police_report_no" class="form-control"
                    value="<?= htmlspecialchars($incident['police_report_no']) ?>">
            </div>

            <div class="col-md-6">
                <label class="form-label">Nr zgłoszenia do ubezpieczyciela</label>
                <input type="text" name="insurance_claim_no" class="form-control"
                    value="<?= htmlspecialchars($incident['insurance_claim_no']) ?>">
            </div>

            <div class="col-12">
                <label class="form-label">Opis uszkodzeń / okoliczności</label>
                <textarea name="damage_desc" class="form-control" rows="3"><?= htmlspecialchars($incident['damage_desc']) ?></textarea>
            </div>

            <div class="col-12">
                <label class="form-label">Notatki</label>
                <textarea name="notes" class="form-control" rows="2"><?= htmlspecialchars($incident['notes']) ?></textarea>
            </div>

            <div class="col-12">
                <label class="form-label">Załączniki (PDF/JPG/PNG) – można dodać wiele</label>
                <input type="file" name="files[]" class="form-control" multiple accept=".pdf,.jpg,.jpeg,.png">
            </div>
        </div>

        <div class="d-flex gap-2">
            <button class="btn btn-primary">Zapisz</button>
            <a class="btn btn-outline-secondary" href="<?= BASE_URL ?>/index.php?page=vehicle-detail&id=<?= (int)$vehicle_id ?>">Anuluj</a>
        </div>
    </form>
</div>
<?php require_once __DIR__ . '/../partials/footer.php'; ?>