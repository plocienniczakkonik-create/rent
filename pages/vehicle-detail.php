<?php
// /pages/vehicle-detail.php — karta pojedynczego pojazdu
require_once dirname(__DIR__) . '/auth/auth.php';
require_staff();
require_once dirname(__DIR__) . '/includes/db.php';

$BASE = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = $db->prepare("SELECT v.*, p.name AS product_name
                      FROM vehicles v
                      JOIN products p ON p.id = v.product_id
                      WHERE v.id = :id");
$stmt->execute([':id' => $id]);
$veh = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$veh) {
    http_response_code(404);
    echo '<div class="container py-5">Pojazd nie znaleziony.</div>';
    return;
}

$badge = match ($veh['status']) {
    'available'   => 'bg-success',
    'booked'      => 'bg-secondary',
    'maintenance' => 'bg-warning text-dark',
    'unavailable' => 'bg-danger',
    'retired'     => 'bg-dark',
};
?>
<div class="container py-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= $BASE ?>/index.php?page=vehicles">Pojazdy — modele</a></li>
            <li class="breadcrumb-item"><a href="<?= $BASE ?>/index.php?page=vehicles-manage&product=<?= (int)$veh['product_id'] ?>"><?= htmlspecialchars($veh['product_name']) ?></a></li>
            <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($veh['registration_number']) ?></li>
        </ol>
    </nav>

    <div class="d-flex flex-wrap align-items-center justify-content-between mb-3">
        <h1 class="h4 m-0">Pojazd: <?= htmlspecialchars($veh['registration_number']) ?></h1>
        <div class="d-flex gap-2">
            <span class="badge <?= $badge ?> align-self-center"><?= htmlspecialchars($veh['status']) ?></span>
            <a href="<?= $BASE ?>/index.php?page=vehicle-form&id=<?= (int)$veh['id'] ?>" class="btn btn-outline-secondary">Edytuj</a>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">Metryka pojazdu</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="text-muted">Model</div>
                            <div class="fw-semibold"><?= htmlspecialchars($veh['product_name']) ?></div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted">VIN</div>
                            <div class="fw-semibold"><?= $veh['vin'] ? htmlspecialchars($veh['vin']) : '—' ?></div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-muted">Przebieg</div>
                            <div class="fw-semibold"><?= $veh['mileage'] !== null ? (int)$veh['mileage'] . ' km' : '—' ?></div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-muted">Lokalizacja</div>
                            <div class="fw-semibold"><?= $veh['location'] ? htmlspecialchars($veh['location']) : '—' ?></div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-muted">Status</div>
                            <div class="fw-semibold"><span class="badge <?= $badge ?>"><?= htmlspecialchars($veh['status']) ?></span></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">Terminy</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="text-muted">Przegląd techniczny</div>
                            <div class="fw-semibold"><?= $veh['inspection_date'] ? htmlspecialchars($veh['inspection_date']) : '—' ?></div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted">Ubezpieczenie do</div>
                            <div class="fw-semibold"><?= $veh['insurance_expiry_date'] ? htmlspecialchars($veh['insurance_expiry_date']) : '—' ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">Notatki</div>
                <div class="card-body">
                    <div><?= $veh['notes'] ? nl2br(htmlspecialchars($veh['notes'])) : '<span class="text-muted">Brak notatek.</span>' ?></div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">Szybkie akcje</div>
                <div class="card-body d-grid gap-2">
                    <a class="btn btn-outline-primary disabled" title="W kolejnej iteracji">Przypisz do rezerwacji</a>
                    <a class="btn btn-outline-warning disabled" title="W kolejnej iteracji">Wyślij do serwisu</a>
                    <a class="btn btn-outline-success disabled" title="W kolejnej iteracji">Ustaw przypomnienia</a>
                </div>
            </div>
        </div>
    </div>
</div>