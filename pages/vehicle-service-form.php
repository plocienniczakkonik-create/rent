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
    <div class="card mb-4">
        <div class="card-header text-white d-flex align-items-center" style="background: var(--gradient-primary); border: none;">
            <h1 class="mb-0 d-flex align-items-center">
                <i class="fas fa-tools me-3"></i>
                <?= $id ? 'Edytuj serwis pojazdu' : 'Dodaj serwis pojazdu' ?>
            </h1>
        </div>
        <div class="card-body">
            <form method="post" action="<?= BASE_URL ?>/index.php?page=vehicle-service-save" enctype="multipart/form-data" class="vstack gap-3">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int)$id ?>">
                <input type="hidden" name="vehicle_id" value="<?= (int)$vehicle_id ?>">

                <!-- Sekcja: Podstawowe informacje -->
                <div class="card mb-4">
                    <div class="card-header" style="background: white; border-bottom: 1px solid #6b7280;">
                        <h5 class="mb-0 d-flex align-items-center"><i class="fas fa-info-circle me-2"></i>Podstawowe informacje</h5>
                    </div>
                    <div class="card-body row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Data serwisu</label>
                            <input type="date" name="service_date" class="form-control" required value="<?= htmlspecialchars($service['service_date']) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Nr faktury/zlecenia</label>
                            <input type="text" name="invoice_no" class="form-control" value="<?= htmlspecialchars($service['invoice_no']) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Przebieg [km]</label>
                            <input type="number" min="0" name="odometer_km" class="form-control" value="<?= htmlspecialchars((string)$service['odometer_km']) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Koszt całkowity [zł]</label>
                            <input type="number" step="0.01" min="0" name="cost_total" class="form-control" value="<?= htmlspecialchars((string)$service['cost_total']) ?>">
                        </div>
                    </div>
                </div>

                <!-- Sekcja: Warsztat -->
                <div class="card mb-4">
                    <div class="card-header" style="background: white; border-bottom: 1px solid #6b7280;">
                        <h5 class="mb-0 d-flex align-items-center"><i class="fas fa-tools me-2"></i>Warsztat</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Nazwa warsztatu/serwisu</label>
                            <input type="text" name="workshop_name" class="form-control" value="<?= htmlspecialchars($service['workshop_name']) ?>">
                        </div>
                    </div>
                </div>

                <!-- Sekcja: Usterki i naprawy -->
                <div class="card mb-4">
                    <div class="card-header" style="background: white; border-bottom: 1px solid #6b7280;">
                        <h5 class="mb-0 d-flex align-items-center"><i class="fas fa-clipboard-list me-2"></i>Usterki i naprawy</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Stwierdzone usterki/problemy</label>
                            <textarea name="issues_found" class="form-control" rows="3"><?= htmlspecialchars($service['issues_found']) ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Wykonane czynności/naprawy</label>
                            <textarea name="actions_taken" class="form-control" rows="3" required><?= htmlspecialchars($service['actions_taken']) ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- Sekcja: Notatki i załączniki -->
                <div class="card mb-4">
                    <div class="card-header" style="background: white; border-bottom: 1px solid #6b7280;">
                        <h5 class="mb-0 d-flex align-items-center"><i class="fas fa-sticky-note me-2"></i>Notatki i załączniki</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Notatki</label>
                            <textarea name="notes" class="form-control" rows="2"><?= htmlspecialchars($service['notes']) ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Załączniki (PDF/JPG/PNG) – można dodać wiele</label>
                            <input type="file" name="files[]" class="form-control" multiple accept=".pdf,.jpg,.jpeg,.png">
                        </div>
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button class="btn btn-primary"><i class="fas fa-save me-2"></i>Zapisz</button>
                    <a class="btn btn-outline-secondary" href="<?= BASE_URL ?>/index.php?page=vehicle-detail&id=<?= (int)$vehicle_id ?>">Anuluj</a>
                </div>
            </form>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../partials/footer.php'; ?>