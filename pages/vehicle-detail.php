<?php
// /pages/vehicles-manage.php — egzemplarze danego modelu (widok szczegółów pojazdu)
require_once dirname(__DIR__) . '/auth/auth.php';
require_staff();

require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/theme-config.php';
require_once dirname(__DIR__) . '/includes/vehicle-location-manager.php';
$db = db();

$BASE = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
$id   = isset($_GET['id']) ? (int)$_GET['id'] : 0;


/** Pojazd */
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

/** Pobierz aktualną lokalizację pojazdu */
$currentLocation = VehicleLocationManager::getCurrentLocation($id);
$locationHistory = VehicleLocationManager::getLocationHistory($id, 5);

/** Mapowanie badge statusu */
$statusLabels = [
    'available'   => 'Dostępny',
    'booked'      => 'Zarezerwowany',
    'maintenance' => 'Serwis',
    'unavailable' => 'Niedostępny',
    'retired'     => 'Wycofany',
];

$badge = match ($veh['status']) {
    'available'   => 'bg-success',
    'booked'      => 'bg-secondary',
    'maintenance' => 'bg-warning text-dark',
    'unavailable' => 'bg-danger',
    'retired'     => 'bg-dark',
    default       => 'bg-secondary'
};

/** FUNKCJE SORTOWANIA */
function sort_link_detail(string $section, string $key, string $label, int $vehicleId): string
{
    $currentSection = $_GET['section'] ?? '';
    $currentSort = $_GET['sort'] ?? '';
    $currentDir = strtolower($_GET['dir'] ?? 'desc');

    // Tylko sortuj jeśli jesteśmy w tej sekcji
    $nextDir = ($currentSection === $section && $currentSort === $key && $currentDir === 'desc') ? 'asc' : 'desc';
    $arrowUpActive = ($currentSection === $section && $currentSort === $key && $currentDir === 'asc');
    $arrowDownActive = ($currentSection === $section && $currentSort === $key && $currentDir === 'desc');

    $BASE = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
    $qs = http_build_query([
        'page' => 'vehicle-detail',
        'id' => $vehicleId,
        'section' => $section,
        'sort' => $key,
        'dir' => $nextDir,
    ]);

    return '<a class="th-sort-link" href="' . htmlspecialchars($BASE . '/index.php?' . $qs) . '">'
        . '<span class="label">' . htmlspecialchars($label) . '</span>'
        . '<span class="chevs"><span class="chev ' . ($arrowUpActive ? 'active' : '') . '">▲</span><span class="chev ' . ($arrowDownActive ? 'active' : '') . '">▼</span></span>'
        . '</a>';
}

/** SORTOWANIE PARAMETRY */
$section = $_GET['section'] ?? '';
$sort = $_GET['sort'] ?? '';
$dir = strtolower($_GET['dir'] ?? 'desc');
$dir = in_array($dir, ['asc', 'desc'], true) ? $dir : 'desc';

/** SERWISY z sortowaniem */
$serviceOrder = '';
if ($section === 'services') {
    $serviceOrder = match ($sort) {
        'date' => "ORDER BY service_date $dir, id $dir",
        'mileage' => "ORDER BY odometer_km $dir, service_date DESC",
        'cost' => "ORDER BY cost_total $dir, service_date DESC",
        'workshop' => "ORDER BY workshop_name $dir, service_date DESC",
        default => "ORDER BY service_date DESC, id DESC"
    };
} else {
    $serviceOrder = "ORDER BY service_date DESC, id DESC";
}

$q1 = $db->prepare("SELECT * FROM vehicle_services WHERE vehicle_id = ? $serviceOrder LIMIT 20");
$q1->execute([(int)$veh['id']]);
$services = $q1->fetchAll(PDO::FETCH_ASSOC);

/** KOLIZJE z sortowaniem */
$incidentOrder = '';
if ($section === 'incidents') {
    $incidentOrder = match ($sort) {
        'date' => "ORDER BY incident_date $dir, id $dir",
        'location' => "ORDER BY location $dir, incident_date DESC",
        'fault' => "ORDER BY fault $dir, incident_date DESC",
        'cost' => "ORDER BY repair_cost $dir, incident_date DESC",
        'driver' => "ORDER BY driver_name $dir, incident_date DESC",
        default => "ORDER BY incident_date DESC, id DESC"
    };
} else {
    $incidentOrder = "ORDER BY incident_date DESC, id DESC";
}

$q2 = $db->prepare("SELECT * FROM vehicle_incidents WHERE vehicle_id = ? $incidentOrder LIMIT 20");
$q2->execute([(int)$veh['id']]);
$incidents = $q2->fetchAll(PDO::FETCH_ASSOC);

/** Pomocnicze mapy dla winy w kolizjach */
$faultLabel = [
    'our'     => 'nasza',
    'other'   => 'druga strona',
    'shared'  => 'współwina',
    'unknown' => 'nieustalona',
];
$faultBadge = [
    'our'     => 'danger',    // czerwony - nasza
    'other'   => 'success',   // zielony - druga strona  
    'shared'  => 'warning',   // żółty - współwina
    'unknown' => 'primary',   // niebieski - nieustalona
];
?>
<style>
    <?= ThemeConfig::generateCSSVariables() ?>
</style>
<div class="container py-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= $BASE ?>/index.php?page=vehicles">Pojazdy — modele</a></li>
            <li class="breadcrumb-item">
                <a href="<?= $BASE ?>/index.php?page=vehicles-manage&product=<?= (int)$veh['product_id'] ?>">
                    <?= htmlspecialchars($veh['product_name']) ?>
                </a>
            </li>
            <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($veh['registration_number']) ?></li>
        </ol>
    </nav>

    <!-- Sekcja: Header pojazdu -->
    <div class="card shadow-sm mb-4" style="border: 1px solid var(--color-light);">
        <div class="card-header" style="background: var(--gradient-primary); color: white; border-bottom: 1px solid var(--color-primary-dark);">
            <div class="d-flex flex-wrap align-items-center justify-content-between">
                <h4 class="mb-0">
                    <i class="bi bi-car-front-fill me-2"></i>Pojazd: <?= htmlspecialchars($veh['registration_number']) ?>
                </h4>
                <div class="d-flex gap-2 align-items-center">
                    <span class="badge fs-6" style="background: #f3f4f6; color: #374151; border: 1px solid #d1d5db;">
                        <?= htmlspecialchars($statusLabels[$veh['status']] ?? $veh['status']) ?>
                    </span>
                    <a href="<?= $BASE ?>/index.php?page=vehicle-form&id=<?= (int)$veh['id'] ?>"
                        class="btn btn-sm" style="background: var(--color-warning); color: white; border: none;">
                        <i class="bi bi-pencil me-1"></i>Edytuj
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <!-- Lewa kolumna: główne sekcje -->
        <div class="col-lg-8">
            <!-- Sekcja: Metryka pojazdu -->
            <div class="card mb-3 shadow-sm" style="border: 1px solid var(--color-light);">
                <div class="card-header" style="background: white; border-bottom: 1px solid #6b7280; color: var(--color-dark);">
                    <h6 class="mb-0">
                        <i class="bi bi-info-circle-fill me-2" style="color: var(--color-primary);"></i>Metryka pojazdu
                    </h6>
                </div>
                <div class="card-body p-0">
                    <table class="table table-striped mb-0">
                        <tbody>
                            <tr>
                                <th class="w-25 p-3">
                                    <i class="bi bi-car-front text-primary me-2"></i>Model
                                </th>
                                <td class="fw-semibold p-3"><?= htmlspecialchars($veh['product_name']) ?></td>
                                <th class="w-25 p-3">
                                    <i class="bi bi-qr-code text-secondary me-2"></i>VIN
                                </th>
                                <td class="fw-semibold p-3">
                                    <?php if ($veh['vin']): ?>
                                        <code><?= htmlspecialchars($veh['vin']) ?></code>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <th class="p-3">
                                    <i class="bi bi-speedometer text-warning me-2"></i>Przebieg
                                </th>
                                <td class="p-3">
                                    <?php if ($veh['mileage'] !== null): ?>
                                        <span class="fw-semibold"><?= number_format((int)$veh['mileage']) ?> km</span>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <th class="p-3">
                                    <i class="bi bi-geo-alt text-danger me-2"></i>Lokalizacja
                                </th>
                                <td class="p-3">
                                    <?= VehicleLocationManager::formatLocationDisplay($currentLocation) ?>
                                    <?php if ($currentLocation && $currentLocation['location_id']): ?>
                                        <div class="small text-muted mt-1">
                                            <i class="bi bi-building me-1"></i>ID lokalizacji: <?= $currentLocation['location_id'] ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <th class="p-3">
                                    <i class="bi bi-toggle-on text-success me-2"></i>Status
                                </th>
                                <td class="p-3">
                                    <span class="badge <?= $badge ?> fs-6"><?= htmlspecialchars($veh['status']) ?></span>
                                </td>
                                <th class="p-3"></th>
                                <td class="p-3"></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Sekcja: Terminy -->
            <div class="card mb-3 shadow-sm" style="border: 1px solid var(--color-light);">
                <div class="card-header" style="background: white; border-bottom: 1px solid #6b7280; color: var(--color-dark);">
                    <h6 class="mb-0">
                        <i class="bi bi-calendar-check me-2" style="color: var(--color-primary);"></i>Terminy
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="d-flex align-items-center mb-2">
                                <i class="bi bi-tools text-success me-2"></i>
                                <div class="text-muted small">Przegląd techniczny</div>
                            </div>
                            <div class="fw-semibold fs-5">
                                <?php if ($veh['inspection_date']): ?>
                                    <?= htmlspecialchars($veh['inspection_date']) ?>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex align-items-center mb-2">
                                <i class="bi bi-shield-check text-info me-2"></i>
                                <div class="text-muted small">Ubezpieczenie do</div>
                            </div>
                            <div class="fw-semibold fs-5">
                                <?php if ($veh['insurance_expiry_date']): ?>
                                    <?= htmlspecialchars($veh['insurance_expiry_date']) ?>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sekcja: Notatki -->
            <div class="card mb-3 shadow-sm" style="border: 1px solid var(--color-light);">
                <div class="card-header" style="background: white; border-bottom: 1px solid #6b7280; color: var(--color-dark);">
                    <h6 class="mb-0">
                        <i class="bi bi-sticky me-2" style="color: var(--color-primary);"></i>Notatki
                    </h6>
                </div>
                <div class="card-body">
                    <?php if ($veh['notes']): ?>
                        <div class="p-3 bg-light rounded">
                            <i class="bi bi-quote text-muted me-2"></i>
                            <?= nl2br(htmlspecialchars($veh['notes'])) ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center text-muted py-4">
                            <i class="bi bi-journal-x display-6 d-block mb-2 opacity-50"></i>
                            <span>Brak notatek dla tego pojazdu</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Sekcja: Historia lokalizacji -->
            <div class="card mb-3 shadow-sm" style="border: 1px solid var(--color-light);">
                <div class="card-header d-flex justify-content-between align-items-center" style="background: white; border-bottom: 1px solid #6b7280; color: var(--color-dark);">
                    <h6 class="mb-0">
                        <i class="bi bi-geo-alt me-2" style="color: var(--color-primary);"></i>Historia lokalizacji
                    </h6>
                    <button class="btn btn-sm" style="background: var(--color-primary); color: white; border: none;"
                        data-bs-toggle="modal" data-bs-target="#changeLocationModal">
                        <i class="bi bi-arrow-left-right me-1"></i>Zmień lokalizację
                    </button>
                </div>
                <div class="card-body">
                    <?php if (count($locationHistory) > 0): ?>
                        <div class="timeline">
                            <?php foreach ($locationHistory as $history): ?>
                                <div class="d-flex mb-3">
                                    <div class="flex-shrink-0 me-3">
                                        <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                            <i class="bi bi-geo-alt text-white small"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="fw-semibold">
                                            <?= $history['location_name'] ? htmlspecialchars($history['location_name'] . ' - ' . $history['location_city']) : 'Lokalizacja usunięta' ?>
                                        </div>
                                        <div class="small text-muted">
                                            <?= date('d.m.Y H:i', strtotime($history['moved_at'])) ?>
                                            <?php if ($history['moved_by_username']): ?>
                                                przez <?= htmlspecialchars($history['moved_by_username']) ?>
                                            <?php endif; ?>
                                        </div>
                                        <?php if ($history['reason'] !== 'manual'): ?>
                                            <div class="small">
                                                <span class="badge bg-info"><?= ucfirst($history['reason']) ?></span>
                                            </div>
                                        <?php endif; ?>
                                        <?php if ($history['notes']): ?>
                                            <div class="small text-muted mt-1">
                                                <i class="bi bi-chat-square-quote me-1"></i><?= htmlspecialchars($history['notes']) ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center text-muted py-4">
                            <i class="bi bi-geo display-6 d-block mb-2 opacity-50"></i>
                            <span>Brak historii zmian lokalizacji</span>
                            <div class="small mt-2">
                                Pojazd nie ma jeszcze przypisanej lokalizacji w systemie flotowym
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Sekcja: Serwisy -->
            <div class="card mb-3 shadow-sm" style="border: 1px solid var(--color-light);">
                <div class="card-header d-flex justify-content-between align-items-center" style="background: white; border-bottom: 1px solid #6b7280; color: var(--color-dark);">
                    <h6 class="mb-0">
                        <i class="bi bi-tools me-2" style="color: var(--color-primary);"></i>Serwisy
                    </h6>
                    <a class="btn btn-sm" style="background: var(--color-info); color: white; border: none;"
                        href="<?= $BASE ?>/index.php?page=vehicle-service-form&vehicle_id=<?= (int)$veh['id'] ?>">
                        <i class="bi bi-plus-circle me-1"></i>Dodaj serwis
                    </a>
                </div>
                <div class="card-body p-0">
                    <?php if (!$services): ?>
                        <div class="text-center text-muted py-5">
                            <i class="bi bi-wrench-adjustable display-4 d-block mb-3 opacity-25"></i>
                            <h6>Brak wpisów serwisowych</h6>
                            <p class="mb-0">Ten pojazd nie ma jeszcze żadnych zapisów serwisowych</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0 align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 120px;"><i class="bi bi-calendar-event me-1"></i><?= sort_link_detail('services', 'date', 'Data', (int)$veh['id']) ?></th>
                                        <th style="width: 130px;"><i class="bi bi-speedometer me-1"></i><?= sort_link_detail('services', 'mileage', 'Przebieg', (int)$veh['id']) ?></th>
                                        <th><i class="bi bi-exclamation-triangle me-1"></i>Usterka</th>
                                        <th style="width: 130px;"><i class="bi bi-currency-dollar me-1"></i><?= sort_link_detail('services', 'cost', 'Kwota', (int)$veh['id']) ?></th>
                                        <th style="width: 160px;"><i class="bi bi-building me-1"></i><?= sort_link_detail('services', 'workshop', 'Warsztat', (int)$veh['id']) ?></th>
                                        <th class="text-end" style="width: 100px;"><i class="bi bi-gear me-1"></i>Akcje</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($services as $s): ?>
                                        <tr style="border-left: 3px solid #3b82f6;">
                                            <td>
                                                <i class="bi bi-calendar3 me-1" style="color: #6b7280;"></i>
                                                <?= htmlspecialchars($s['service_date']) ?>
                                            </td>
                                            <td>
                                                <?php if ($s['odometer_km']): ?>
                                                    <span class="badge" style="background: #f3f4f6; color: #374151; border: 1px solid #d1d5db;">
                                                        <i class="bi bi-speedometer2 me-1"></i><?= (int)$s['odometer_km'] ?> km
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-muted">—</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-truncate" style="max-width:320px;">
                                                <?php if ($s['issues_found']): ?>
                                                    <small class="text-muted d-block">
                                                        <i class="bi bi-tools me-1"></i>
                                                        <?= nl2br(htmlspecialchars($s['issues_found'])) ?>
                                                    </small>
                                                <?php else: ?>
                                                    <span class="text-muted">—</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="fw-bold" style="color: #374151; white-space: nowrap;">
                                                    <?= number_format((float)$s['cost_total'], 2, ',', ' ') ?> zł
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($s['workshop_name']): ?>
                                                    <i class="bi bi-shop me-1" style="color: #6b7280;"></i>
                                                    <?= htmlspecialchars($s['workshop_name']) ?>
                                                <?php else: ?>
                                                    <span class="text-muted">—</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-end">
                                                <div class="d-flex gap-1 justify-content-end align-items-center">
                                                    <a class="btn btn-sm p-1" style="width: 32px; height: 32px; background: #f3f4f6; border: 1px solid #d1d5db; border-radius: 6px;"
                                                        href="<?= $BASE ?>/index.php?page=vehicle-service-form&id=<?= (int)$s['id'] ?>&vehicle_id=<?= (int)$veh['id'] ?>"
                                                        title="Edytuj serwis">
                                                        <i class="bi bi-gear" style="color: #6b7280; font-size: 14px;"></i>
                                                    </a>
                                                    <form method="post" action="<?= $BASE ?>/index.php?page=vehicle-service-delete"
                                                        class="d-inline" onsubmit="return confirm('Usunąć wpis serwisu?')">
                                                        <?php
                                                        if (session_status() !== PHP_SESSION_ACTIVE) {
                                                            session_start();
                                                        }
                                                        $tok = $_SESSION['_token'] ?? bin2hex(random_bytes(32));
                                                        $_SESSION['_token'] = $tok;
                                                        echo '<input type="hidden" name="_token" value="' . htmlspecialchars($tok, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '">';
                                                        ?>
                                                        <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
                                                        <input type="hidden" name="vehicle_id" value="<?= (int)$veh['id'] ?>">
                                                        <button class="btn btn-sm p-1" style="width: 32px; height: 32px; background: #fef2f2; border: 1px solid #fecaca; border-radius: 6px;" title="Usuń serwis">
                                                            <i class="bi bi-trash" style="color: #ef4444; font-size: 14px;"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Sekcja: Kolizje / Szkody -->
            <div class="card mb-3 shadow-sm" style="border: 1px solid var(--color-light);">
                <div class="card-header d-flex justify-content-between align-items-center" style="background: white; border-bottom: 1px solid #6b7280; color: var(--color-dark);">
                    <h6 class="mb-0">
                        <i class="bi bi-exclamation-triangle me-2" style="color: var(--color-primary);"></i>Kolizje / szkody
                    </h6>
                    <a class="btn btn-sm" style="background: var(--color-danger); color: white; border: none;"
                        href="<?= $BASE ?>/index.php?page=vehicle-incident-form&vehicle_id=<?= (int)$veh['id'] ?>">
                        <i class="bi bi-plus-circle me-1"></i>Zgłoś kolizję
                    </a>
                </div>
                <div class="card-body p-0">
                    <?php if (!$incidents): ?>
                        <div class="text-center text-muted py-5">
                            <i class="bi bi-shield-check display-4 d-block mb-3 text-success opacity-25"></i>
                            <h6>Brak zgłoszonych kolizji</h6>
                            <p class="mb-0">Ten pojazd nie ma żadnych zgłoszonych incydentów</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0 align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 120px;"><i class="bi bi-calendar-event me-1"></i><?= sort_link_detail('incidents', 'date', 'Data', (int)$veh['id']) ?></th>
                                        <th style="width: 150px;"><i class="bi bi-person me-1"></i><?= sort_link_detail('incidents', 'driver', 'Kierowca', (int)$veh['id']) ?></th>
                                        <th><i class="bi bi-file-text me-1"></i>Opis</th>
                                        <th style="width: 120px;"><i class="bi bi-shield-exclamation me-1"></i><?= sort_link_detail('incidents', 'fault', 'Wina', (int)$veh['id']) ?></th>
                                        <th style="width: 130px;"><i class="bi bi-currency-dollar me-1"></i><?= sort_link_detail('incidents', 'cost', 'Koszt', (int)$veh['id']) ?></th>
                                        <th class="text-end" style="width: 100px;"><i class="bi bi-gear me-1"></i>Akcje</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($incidents as $i): ?>
                                        <tr style="border-left: 3px solid #ef4444;">
                                            <td>
                                                <i class="bi bi-calendar3 me-1" style="color: #6b7280;"></i>
                                                <?= htmlspecialchars($i['incident_date']) ?>
                                            </td>
                                            <td>
                                                <?php if ($i['driver_name']): ?>
                                                    <i class="bi bi-person-fill me-1" style="color: #6b7280;"></i>
                                                    <?= htmlspecialchars($i['driver_name']) ?>
                                                <?php else: ?>
                                                    <span class="text-muted">—</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-truncate" style="max-width:320px;">
                                                <?php if ($i['damage_desc']): ?>
                                                    <small class="text-muted d-block">
                                                        <i class="bi bi-file-text me-1"></i>
                                                        <?= nl2br(htmlspecialchars($i['damage_desc'])) ?>
                                                    </small>
                                                <?php else: ?>
                                                    <span class="text-muted">—</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php
                                                $f = $i['fault'] ?? 'unknown';
                                                $lbl = $faultLabel[$f] ?? $f;
                                                $cls = $faultBadge[$f] ?? 'secondary';
                                                ?>
                                                <span class="badge px-3 py-2" style="background: #f3f4f6; color: #374151; border: 1px solid #d1d5db;">
                                                    <i class="bi bi-shield-<?= $f === 'none' ? 'check' : 'exclamation' ?> me-1"></i>
                                                    <?= htmlspecialchars($lbl) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="fw-bold" style="color: #374151; white-space: nowrap;">
                                                    <?= number_format((float)$i['repair_cost'], 2, ',', ' ') ?> zł
                                                </span>
                                            </td>
                                            <td class="text-end">
                                                <div class="d-flex gap-1 justify-content-end align-items-center">
                                                    <a class="btn btn-sm p-1" style="width: 32px; height: 32px; background: #f3f4f6; border: 1px solid #d1d5db; border-radius: 6px;"
                                                        href="<?= $BASE ?>/index.php?page=vehicle-incident-form&id=<?= (int)$i['id'] ?>&vehicle_id=<?= (int)$veh['id'] ?>"
                                                        title="Edytuj kolizję">
                                                        <i class="bi bi-gear" style="color: #6b7280; font-size: 14px;"></i>
                                                    </a>
                                                    <form method="post" action="<?= $BASE ?>/index.php?page=vehicle-incident-delete"
                                                        class="d-inline" onsubmit="return confirm('Usunąć wpis kolizji?')">
                                                        <?php
                                                        if (session_status() !== PHP_SESSION_ACTIVE) {
                                                            session_start();
                                                        }
                                                        $tok = $_SESSION['_token'] ?? bin2hex(random_bytes(32));
                                                        $_SESSION['_token'] = $tok;
                                                        echo '<input type="hidden" name="_token" value="' . htmlspecialchars($tok, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '">';
                                                        ?>
                                                        <input type="hidden" name="id" value="<?= (int)$i['id'] ?>">
                                                        <input type="hidden" name="vehicle_id" value="<?= (int)$veh['id'] ?>">
                                                        <button class="btn btn-sm p-1" style="width: 32px; height: 32px; background: #fef2f2; border: 1px solid #fecaca; border-radius: 6px;" title="Usuń kolizję">
                                                            <i class="bi bi-trash" style="color: #ef4444; font-size: 14px;"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Zamówienia / Wynajem -->
            <?php
            // Zamówienia z sortowaniem
            $orderOrder = '';
            if ($section === 'orders') {
                $orderOrder = match ($sort) {
                    'number' => "ORDER BY number $dir, placed_at DESC",
                    'status' => "ORDER BY status $dir, placed_at DESC",
                    'net' => "ORDER BY total_net $dir, placed_at DESC",
                    'vat' => "ORDER BY total_vat $dir, placed_at DESC",
                    'gross' => "ORDER BY total_gross $dir, placed_at DESC",
                    'currency' => "ORDER BY currency $dir, placed_at DESC",
                    'date' => "ORDER BY placed_at $dir, id $dir",
                    default => "ORDER BY placed_at DESC, id DESC"
                };
            } else {
                $orderOrder = "ORDER BY placed_at DESC, id DESC";
            }

            $q3 = $db->prepare("SELECT * FROM orders WHERE vehicle_id = ? $orderOrder LIMIT 20");
            $q3->execute([(int)$veh['id']]);
            $orders = $q3->fetchAll(PDO::FETCH_ASSOC);
            $orderCount = count($orders);
            $orderSum = array_sum(array_column($orders, 'total_gross'));
            ?>
            <!-- Sekcja: Historia wynajmu -->
            <div class="card mb-3 shadow-sm" style="border: 1px solid var(--color-light);">
                <div class="card-header" style="background: white; border-bottom: 1px solid #6b7280; color: var(--color-dark);">
                    <h6 class="mb-0">
                        <i class="bi bi-clock-history me-2" style="color: var(--color-primary);"></i>Historia wynajmu
                    </h6>
                </div>
                <div class="card-body p-0">
                    <?php if (!$orders): ?>
                        <div class="text-center text-muted py-5">
                            <i class="bi bi-receipt display-4 d-block mb-3 opacity-25"></i>
                            <h6>Brak historii wynajmu</h6>
                            <p class="mb-0">Ten pojazd nie ma jeszcze żadnych zamówień</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0 align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th><i class="bi bi-hash me-1"></i><?= sort_link_detail('orders', 'number', 'Numer', (int)$veh['id']) ?></th>
                                        <th><i class="bi bi-flag me-1"></i><?= sort_link_detail('orders', 'status', 'Status', (int)$veh['id']) ?></th>
                                        <th><i class="bi bi-calculator me-1"></i><?= sort_link_detail('orders', 'net', 'Netto', (int)$veh['id']) ?></th>
                                        <th><i class="bi bi-percent me-1"></i><?= sort_link_detail('orders', 'vat', 'VAT', (int)$veh['id']) ?></th>
                                        <th><i class="bi bi-currency-dollar me-1"></i><?= sort_link_detail('orders', 'gross', 'Brutto', (int)$veh['id']) ?></th>
                                        <th><i class="bi bi-globe me-1"></i><?= sort_link_detail('orders', 'currency', 'Waluta', (int)$veh['id']) ?></th>
                                        <th><i class="bi bi-calendar-event me-1"></i><?= sort_link_detail('orders', 'date', 'Data', (int)$veh['id']) ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    function order_status_badge($status)
                                    {
                                        return match (strtolower($status)) {
                                            'paid' => 'bg-primary',
                                            'completed' => 'bg-success',
                                            'cancelled' => 'bg-danger',
                                            default => 'bg-secondary',
                                        };
                                    }
                                    foreach ($orders as $o): ?>
                                        <tr style="border-left: 3px solid #8b5cf6;">
                                            <td>
                                                <i class="bi bi-receipt-cutoff me-1" style="color: #6b7280;"></i>
                                                <strong><?= htmlspecialchars($o['number']) ?></strong>
                                            </td>
                                            <td>
                                                <span class="badge px-3 py-2" style="background: #f3f4f6; color: #374151; border: 1px solid #d1d5db;">
                                                    <?= htmlspecialchars($o['status']) ?>
                                                </span>
                                            </td>
                                            <td class="text-muted"><?= number_format((float)$o['total_net'], 2, ',', ' ') ?> <?= htmlspecialchars($o['currency']) ?></td>
                                            <td class="text-muted"><?= number_format((float)$o['total_vat'], 2, ',', ' ') ?> <?= htmlspecialchars($o['currency']) ?></td>
                                            <td>
                                                <span class="fw-bold" style="color: #374151;">
                                                    <?= number_format((float)$o['total_gross'], 2, ',', ' ') ?> <?= htmlspecialchars($o['currency']) ?>
                                                </span>
                                            </td>
                                            <td><span class="badge" style="background: #f3f4f6; color: #374151; border: 1px solid #d1d5db;"><?= htmlspecialchars($o['currency']) ?></span></td>
                                            <td>
                                                <i class="bi bi-calendar3 me-1" style="color: #6b7280;"></i>
                                                <small><?= htmlspecialchars($o['placed_at']) ?></small>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>


        <!-- Prawa kolumna: szybkie akcje i statystyki -->
        <div class="col-lg-4">
            <!-- Sekcja: Szybkie akcje -->
            <div class="card mb-3 shadow-sm" style="border: 1px solid var(--color-light);">
                <div class="card-header" style="background: white; border-bottom: 1px solid #6b7280; color: var(--color-dark);">
                    <h6 class="mb-0">
                        <i class="bi bi-lightning-charge me-2" style="color: var(--color-primary);"></i>Szybkie akcje
                    </h6>
                </div>
                <div class="card-body d-grid gap-3">
                    <a class="btn btn-sm d-flex align-items-center" style="background: var(--color-info); color: white; border: none;"
                        href="<?= $BASE ?>/index.php?page=vehicle-service-form&vehicle_id=<?= (int)$veh['id'] ?>">
                        <i class="bi bi-tools me-2"></i>Dodaj serwis
                    </a>
                    <a class="btn btn-sm d-flex align-items-center" style="background: var(--color-danger); color: white; border: none;"
                        href="<?= $BASE ?>/index.php?page=vehicle-incident-form&vehicle_id=<?= (int)$veh['id'] ?>">
                        <i class="bi bi-exclamation-triangle me-2"></i>Zgłoś kolizję
                    </a>
                    <a class="btn btn-sm d-flex align-items-center" style="background: var(--color-success); color: white; border: none;"
                        href="<?= $BASE ?>/index.php?page=vehicle-order-form&vehicle_id=<?= (int)$veh['id'] ?>">
                        <i class="bi bi-plus-circle me-2"></i>Dodaj zamówienie
                    </a>
                    <hr class="my-2">
                    <button class="btn btn-outline-secondary btn-sm disabled" title="W kolejnej iteracji">
                        <i class="bi bi-calendar-check me-2"></i>Przypisz do rezerwacji
                    </button>
                    <button class="btn btn-outline-secondary btn-sm disabled" title="W kolejnej iteracji">
                        <i class="bi bi-bell me-2"></i>Ustaw przypomnienia
                    </button>
                </div>
            </div>

            <!-- Statystyki pojazdu -->
            <?php
            // Filtr okresu
            $period = $_GET['period'] ?? 'all';
            $today = date('Y-m-d');
            $yearStart = date('Y-01-01');
            $periodSql = '';
            switch ($period) {
                case 'today':
                    $periodSql = "AND DATE(placed_at) = '$today'";
                    break;
                case '7d':
                    $periodSql = "AND placed_at >= DATE_SUB('$today', INTERVAL 7 DAY)";
                    break;
                case '30d':
                    $periodSql = "AND placed_at >= DATE_SUB('$today', INTERVAL 30 DAY)";
                    break;
                case 'month':
                    $periodSql = "AND MONTH(placed_at) = MONTH('$today') AND YEAR(placed_at) = YEAR('$today')";
                    break;
                case 'year':
                    $periodSql = "AND YEAR(placed_at) = YEAR('$today')";
                    break;
                case 'all':
                default:
                    $periodSql = '';
            }
            // Statystyki zamówień
            $statsOrders = $db->query("SELECT COUNT(*) AS cnt, SUM(total_net) AS net, SUM(total_gross) AS gross FROM orders WHERE vehicle_id = " . (int)$veh['id'] . " $periodSql")->fetch(PDO::FETCH_ASSOC);
            // Statystyki kolizji
            $statsIncidents = $db->query("SELECT COUNT(*) AS cnt, SUM(repair_cost) AS cost FROM vehicle_incidents WHERE vehicle_id = " . (int)$veh['id'] . ($period != 'all' ? " AND incident_date >= DATE_SUB('$today', INTERVAL 30 DAY)" : ""))->fetch(PDO::FETCH_ASSOC);
            // Statystyki serwisów
            $statsServices = $db->query("SELECT COUNT(*) AS cnt, SUM(cost_total) AS cost FROM vehicle_services WHERE vehicle_id = " . (int)$veh['id'] . ($period != 'all' ? " AND service_date >= DATE_SUB('$today', INTERVAL 30 DAY)" : ""))->fetch(PDO::FETCH_ASSOC);
            ?>
            <!-- Sekcja: Statystyki pojazdu -->
            <div class="card mb-3 shadow-sm" style="border: 1px solid var(--color-light);">
                <div class="card-header d-flex justify-content-between align-items-center" style="background: white; border-bottom: 1px solid #6b7280; color: var(--color-dark);">
                    <h6 class="mb-0">
                        <i class="bi bi-graph-up me-2" style="color: var(--color-primary);"></i>Statystyki pojazdu
                    </h6>
                    <form method="get" class="d-inline-flex gap-2 align-items-center" style="margin-bottom:0;">
                        <input type="hidden" name="page" value="vehicle-detail">
                        <input type="hidden" name="id" value="<?= (int)$veh['id'] ?>">
                        <select name="period" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="all" <?= $period == 'all' ? ' selected' : '' ?>>Cały okres</option>
                            <option value="today" <?= $period == 'today' ? ' selected' : '' ?>>Dzisiaj</option>
                            <option value="7d" <?= $period == '7d' ? ' selected' : '' ?>>Ostatnie 7 dni</option>
                            <option value="30d" <?= $period == '30d' ? ' selected' : '' ?>>Ostatnie 30 dni</option>
                            <option value="month" <?= $period == 'month' ? ' selected' : '' ?>>Ostatni miesiąc</option>
                            <option value="year" <?= $period == 'year' ? ' selected' : '' ?>>Bieżący rok</option>
                        </select>
                    </form>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <tbody>
                                <tr>
                                    <th class="py-3 px-3">
                                        <i class="bi bi-car-front me-2" style="color: #6b7280;"></i>Liczba wynajmów
                                    </th>
                                    <td class="py-3">
                                        <span class="badge px-3 py-2" style="background: #f3f4f6; color: #374151; border: 1px solid #d1d5db;"><?= (int)($statsOrders['cnt'] ?? 0) ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th class="py-3 px-3">
                                        <i class="bi bi-exclamation-triangle me-2" style="color: #6b7280;"></i>Liczba szkód
                                    </th>
                                    <td class="py-3">
                                        <span class="badge px-3 py-2" style="background: #f3f4f6; color: #374151; border: 1px solid #d1d5db;"><?= (int)($statsIncidents['cnt'] ?? 0) ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th class="py-3 px-3">
                                        <i class="bi bi-tools me-2" style="color: #6b7280;"></i>Liczba serwisów
                                    </th>
                                    <td class="py-3">
                                        <span class="badge px-3 py-2" style="background: #f3f4f6; color: #374151; border: 1px solid #d1d5db;"><?= (int)($statsServices['cnt'] ?? 0) ?></span>
                                    </td>
                                </tr>
                                <tr style="background: #f9fafb;">
                                    <th class="py-3 px-3">
                                        <i class="bi bi-currency-dollar me-2" style="color: #6b7280;"></i>Przychód netto
                                    </th>
                                    <td class="py-3 fw-bold" style="color: #374151;">
                                        <?= number_format((float)($statsOrders['net'] ?? 0), 2, ',', ' ') ?> zł
                                    </td>
                                </tr>
                                <tr style="background: #f9fafb;">
                                    <th class="py-3 px-3">
                                        <i class="bi bi-currency-dollar me-2" style="color: #6b7280;"></i>Przychód brutto
                                    </th>
                                    <td class="py-3 fw-bold" style="color: #374151;">
                                        <?= number_format((float)($statsOrders['gross'] ?? 0), 2, ',', ' ') ?> zł
                                    </td>
                                </tr>
                                <tr style="background: #f9fafb;">
                                    <th class="py-3 px-3">
                                        <i class="bi bi-wrench me-2" style="color: #6b7280;"></i>Koszty serwisów
                                    </th>
                                    <td class="py-3 fw-bold" style="color: #374151;">
                                        <?= number_format((float)($statsServices['cost'] ?? 0), 2, ',', ' ') ?> zł
                                    </td>
                                </tr>
                                <tr style="background: #f9fafb;">
                                    <th class="py-3 px-3">
                                        <i class="bi bi-exclamation-triangle me-2" style="color: #6b7280;"></i>Koszty kolizji
                                    </th>
                                    <td class="py-3 fw-bold" style="color: #374151;">
                                        <?= number_format((float)($statsIncidents['cost'] ?? 0), 2, ',', ' ') ?> zł
                                    </td>
                                </tr>
                                <tr style="background: #f9fafb; border-top: 1px solid #6b7280;">
                                    <th class="py-4 px-3">
                                        <i class="bi bi-trophy me-2" style="color: var(--color-primary);"></i><strong>Zysk netto</strong>
                                    </th>
                                    <td class="py-4">
                                        <span class="badge fs-6 px-4 py-3" style="background: #10b981; color: white;">
                                            <i class="bi bi-cash-coin me-1"></i>
                                            <?= number_format((float)($statsOrders['net'] ?? 0) - (float)($statsServices['cost'] ?? 0) - (float)($statsIncidents['cost'] ?? 0), 2, ',', ' ') ?> zł
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    /* Profesjonalna paleta kolorów w stylu Apple */
    .card {
        transition: all 0.2s ease-in-out;
        border-radius: 12px;
    }

    .card:hover {
        transform: translateY(-1px);
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08) !important;
    }

    .card-header {
        border-radius: 12px 12px 0 0 !important;
        font-weight: 600;
    }

    .table-hover tbody tr:hover {
        background-color: rgba(59, 130, 246, 0.03);
    }

    .badge {
        font-size: 0.85rem;
        padding: 0.5em 0.9em;
        border-radius: 8px;
        font-weight: 500;
    }

    .btn {
        border-radius: 8px;
        font-weight: 500;
        transition: all 0.15s ease-in-out;
        min-width: 80px;
        /* Minimalna szerokość przycisków */
    }

    .btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }

    .btn-sm {
        padding: 0.375rem 0.75rem;
        font-size: 0.875rem;
    }

    /* Poprawione odstepy dla przycisków akcji */
    .d-flex.gap-2 {
        gap: 0.75rem !important;
    }

    /* Minimalistyczne kolory dla różnych elementów */
    .text-professional {
        color: #374151 !important;
    }

    /* Minimalistyczne przyciski akcji */
    .btn.p-1 {
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.15s ease-in-out;
        border: 1px solid;
    }

    .btn.p-1:hover {
        transform: translateY(-1px);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    /* Poprawione odstępy dla przycisków akcji */
    .d-flex.gap-1 {
        gap: 0.25rem !important;
    }

    /* Stała szerokość kolumny akcji */
    .table th:last-child,
    .table td:last-child {
        width: 100px;
        min-width: 100px;
    }

    .bg-professional {
        background-color: #f8fafc !important;
    }

    .border-professional {
        border-color: #e5e7eb !important;
    }

    /* Delikatne akcenty kolorów */
    .accent-blue {
        color: #3b82f6;
    }

    .accent-green {
        color: #10b981;
    }

    .accent-amber {
        color: #f59e0b;
    }

    .accent-red {
        color: #ef4444;
    }

    .accent-purple {
        color: #8b5cf6;
    }

    .accent-gray {
        color: #6b7280;
    }

    /* VIN code styling */
    code {
        background-color: #f8f9fa;
        color: #495057;
        padding: 0.2rem 0.4rem;
        border-radius: 0.25rem;
        font-size: 0.875em;
        font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
    }

    /* Card headers with better spacing */
    .card-header h6 {
        font-weight: 600;
        letter-spacing: 0.025em;
    }

    /* Better table styling */
    .table th {
        font-weight: 600;
        border-top: none;
    }

    /* Numbers formatting */
    .fw-semibold {
        font-weight: 600 !important;
    }

    .fs-5 {
        font-size: 1.25rem !important;
    }

    /* Timeline styling for location history */
    .timeline .bg-primary {
        background-color: var(--color-primary) !important;
    }
</style>

<!-- Modal zmiany lokalizacji -->
<div class="modal fade" id="changeLocationModal" tabindex="-1" aria-labelledby="changeLocationModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="changeLocationForm" action="<?= $BASE ?>/api/vehicle-change-location.php" method="POST">
                <input type="hidden" name="vehicle_id" value="<?= $id ?>">
                <div class="modal-header">
                    <h5 class="modal-title" id="changeLocationModalLabel">
                        <i class="bi bi-geo-alt me-2"></i>Zmień lokalizację pojazdu
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        <strong>Aktualna lokalizacja:</strong><br>
                        <?= VehicleLocationManager::formatLocationDisplay($currentLocation) ?>
                    </div>

                    <div class="mb-3">
                        <label for="new_location_id" class="form-label">Nowa lokalizacja <span class="text-danger">*</span></label>
                        <select name="new_location_id" id="new_location_id" class="form-select" required>
                            <option value="">Wybierz lokalizację...</option>
                            <?php
                            $allLocations = VehicleLocationManager::getAllLocations();
                            foreach ($allLocations as $loc):
                                $selected = ($currentLocation && $currentLocation['location_id'] == $loc['id']) ? 'disabled' : '';
                            ?>
                                <option value="<?= $loc['id'] ?>" <?= $selected ?>>
                                    <?= htmlspecialchars($loc['name'] . ' - ' . $loc['city']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="reason" class="form-label">Powód zmiany</label>
                        <select name="reason" id="reason" class="form-select">
                            <option value="manual">Ręczna zmiana</option>
                            <option value="maintenance">Serwis</option>
                            <option value="rental_pickup">Odbiór wynajmu</option>
                            <option value="rental_return">Zwrot wynajmu</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="notes" class="form-label">Notatki</label>
                        <textarea name="notes" id="notes" class="form-control" rows="3"
                            placeholder="Opcjonalne notatki dotyczące zmiany lokalizacji..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anuluj</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-geo-alt me-1"></i>Zmień lokalizację
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.getElementById('changeLocationForm').addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(this);

        fetch(this.action, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Odśwież stronę po udanej zmianie
                    window.location.reload();
                } else {
                    alert('Błąd: ' + (data.message || 'Nie udało się zmienić lokalizacji'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Wystąpił błąd podczas zmiany lokalizacji');
            });
    });
</script>