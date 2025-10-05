<?php
// /pages/vehicle-service-form.php
require_once __DIR__ . '/../auth/auth.php';
$staff = require_staff();

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/_helpers.php';
$db = db();

$vehicle_id = (int)($_GET['vehicle_id'] ?? $_POST['vehicle_id'] ?? 0);
$id         = (int)($_GET['id'] ?? 0);

$service = [
    'service_date'      => date('Y-m-d'),
    'odometer_km'       => '',
    'workshop_name'     => '',
    'invoice_no'        => '',
    'cost_total'        => 0,
    'issues_found'      => '',
    'actions_taken'     => '',
    'notes'             => '',
];

if ($id) {
    $stmt = $db->prepare("SELECT * FROM vehicle_services WHERE id=?");
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $service = $row;
        $vehicle_id = (int)$row['vehicle_id'];
    }
}

require_once __DIR__ . '/../partials/head.php';
require_once __DIR__ . '/../partials/header.php';
?>
<div class="container py-4" style="max-width: 900px;">
    <h3 class="mb-4"><?= $id ? 'Edytuj serwis' : 'Dodaj serwis' ?></h3>
    <form method="post" action="<?= BASE_URL ?>/index.php?page=vehicle-service-save" enctype="multipart/form-data" class="vstack gap-3">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= (int)$id ?>">
        <input type="hidden" name="vehicle_id" value="<?= (int)$vehicle_id ?>">

        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Data serwisu</label>
                <input type="date" name="service_date" class="form-control" required
                    value="<?= htmlspecialchars($service['service_date']) ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Nr faktury/zlecenia</label>
                <input type="text" name="invoice_no" class="form-control"
                    value="<?= htmlspecialchars($service['invoice_no']) ?>">
            </div>

            <div class="col-md-6">
                <label class="form-label">Przebieg [km]</label>
                <input type="number" min="0" name="odometer_km" class="form-control"
                    value="<?= htmlspecialchars((string)$service['odometer_km']) ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Koszt całkowity [zł]</label>
                <input type="number" step="0.01" min="0" name="cost_total" class="form-control"
                    value="<?= htmlspecialchars((string)$service['cost_total']) ?>">
            </div>

            <div class="col-12">
                <label class="form-label">Nazwa warsztatu/serwisu</label>
                <input type="text" name="workshop_name" class="form-control"
                    value="<?= htmlspecialchars($service['workshop_name']) ?>">
            </div>

            <div class="col-12">
                <label class="form-label">Stwierdzone usterki/problemy</label>
                <textarea name="issues_found" class="form-control" rows="3"><?= htmlspecialchars($service['issues_found']) ?></textarea>
            </div>

            <div class="col-12">
                <label class="form-label">Wykonane czynności/naprawy</label>
                <textarea name="actions_taken" class="form-control" rows="3" required><?= htmlspecialchars($service['actions_taken']) ?></textarea>
            </div>

            <div class="col-12">
                <label class="form-label">Notatki</label>
                <textarea name="notes" class="form-control" rows="2"><?= htmlspecialchars($service['notes']) ?></textarea>
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