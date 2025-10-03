<?php
require_once __DIR__ . '/../auth/auth.php';
$staff = require_staff();
require_once __DIR__ . '/../includes/db.php';
$db = db();

$vehicle_id = (int)($_GET['vehicle_id'] ?? $_POST['vehicle_id'] ?? 0);
$id = (int)($_GET['id'] ?? 0);

$svc = [
    'service_date'  => date('Y-m-d'),
    'odometer_km'   => null,
    'workshop_name' => '',
    'invoice_no'    => '',
    'reported_by'   => current_user()['id'] ?? null,
    'handled_by'    => current_user()['id'] ?? null,
    'cost_total'    => 0,
    'issues_found'  => '',
    'actions_taken' => '',
    'notes'         => '',
];
if ($id) {
    $stmt = $db->prepare("SELECT * FROM vehicle_services WHERE id=?");
    $stmt->execute([$id]);
    $svc = $stmt->fetch(PDO::FETCH_ASSOC) ?: $svc;
    $vehicle_id = (int)$svc['vehicle_id'];
}
require_once __DIR__ . '/../partials/head.php';
require_once __DIR__ . '/../partials/header.php';
?>
<div class="container py-4" style="max-width: 900px;">
    <h3 class="mb-4"><?= $id ? 'Edytuj serwis' : 'Dodaj serwis' ?></h3>
    <form method="post" action="<?= BASE_URL ?>/index.php?page=vehicle-service-save" enctype="multipart/form-data" class="vstack gap-3">
        <!-- CSRF token zawsze wewnątrz formularza -->
        <?php
        if (function_exists('csrf_field')) {
            echo csrf_field();
        } else {
            if (session_status() !== PHP_SESSION_ACTIVE) {
                session_start();
            }
            $tok = $_SESSION['_token'] ?? bin2hex(random_bytes(32));
            $_SESSION['_token'] = $tok;
            echo '<input type="hidden" name="_token" value="' . htmlspecialchars($tok, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '">';
        }
        ?>
        <input type="hidden" name="id" value="<?= (int)$id ?>">
        <input type="hidden" name="vehicle_id" value="<?= (int)$vehicle_id ?>">

        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Data serwisu</label>
                <input type="date" name="service_date" class="form-control" required value="<?= htmlspecialchars($svc['service_date']) ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">Przebieg [km]</label>
                <input type="number" name="odometer_km" class="form-control" min="0" value="<?= htmlspecialchars((string)$svc['odometer_km']) ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">Kwota [zł]</label>
                <input type="number" step="0.01" name="cost_total" class="form-control" min="0" value="<?= htmlspecialchars((string)$svc['cost_total']) ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Warsztat</label>
                <input type="text" name="workshop_name" class="form-control" value="<?= htmlspecialchars($svc['workshop_name']) ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Nr faktury / zlecenia</label>
                <input type="text" name="invoice_no" class="form-control" value="<?= htmlspecialchars($svc['invoice_no']) ?>">
            </div>
            <div class="col-12">
                <label class="form-label">Co było zepsute</label>
                <textarea name="issues_found" class="form-control" rows="2"><?= htmlspecialchars($svc['issues_found']) ?></textarea>
            </div>
            <div class="col-12">
                <label class="form-label">Co zrobiono</label>
                <textarea name="actions_taken" class="form-control" rows="2"><?= htmlspecialchars($svc['actions_taken']) ?></textarea>
            </div>
            <div class="col-12">
                <label class="form-label">Notatki</label>
                <textarea name="notes" class="form-control" rows="2"><?= htmlspecialchars($svc['notes']) ?></textarea>
            </div>
            <div class="col-12">
                <label class="form-label">Załączniki (PDF/JPG/PNG) – wiele plików</label>
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