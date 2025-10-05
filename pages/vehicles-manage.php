<?php
// /pages/vehicles-manage.php — egzemplarze danego modelu (z sortowaniem + przycisk Wstecz)
require_once dirname(__DIR__) . '/auth/auth.php';
require_staff();

require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/_helpers.php';
$db = db();

$BASE = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';

$product_id = isset($_GET['product']) ? (int)$_GET['product'] : 0;
if ($product_id <= 0) {
    http_response_code(400);
    echo '<div class="container py-5">Brak ID modelu.</div>';
    return;
}

$stmt = $db->prepare("SELECT name, sku FROM products WHERE id = :id");
$stmt->execute([':id' => $product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$product) {
    http_response_code(404);
    echo '<div class="container py-5">Model nie znaleziony.</div>';
    return;
}

/* ——— SORTOWANIE ——— */
$allowed = [
    'reg'      => 'v.registration_number',
    'vin'      => 'v.vin',
    'status'   => 'v.status',
    'insp'     => 'v.inspection_date',
    'insur'    => 'v.insurance_expiry_date',
    'mileage'  => 'v.mileage',
    'location' => 'v.location',
];
$sort = $_GET['sort'] ?? 'reg';
$orderCol = $allowed[$sort] ?? 'v.registration_number';
$dir = strtolower($_GET['dir'] ?? 'asc');
$dir = in_array($dir, ['asc', 'desc'], true) ? $dir : 'asc';

$sql = "SELECT v.* 
        FROM vehicles v 
        WHERE v.product_id = :pid 
        ORDER BY $orderCol $dir, v.registration_number ASC";
$st = $db->prepare($sql);
$st->execute([':pid' => $product_id]);
$rows = $st->fetchAll(PDO::FETCH_ASSOC);

/* ——— HELPER linków sortujących (▲▼) ——— */
function sort_link_manage(string $key, string $label): string
{
    $currentSort = $_GET['sort'] ?? 'reg';
    $currentDir  = strtolower($_GET['dir'] ?? 'asc');
    $nextDir     = ($currentSort === $key && $currentDir === 'asc') ? 'desc' : 'asc';
    $arrowUpActive   = ($currentSort === $key && $currentDir === 'asc');
    $arrowDownActive = ($currentSort === $key && $currentDir === 'desc');

    $BASE = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
    $qs = http_build_query([
        'page'    => 'vehicles-manage',
        'product' => (int)($_GET['product'] ?? 0),
        'sort'    => $key,
        'dir'     => $nextDir,
    ]);

    return '<a class="th-sort-link" href="' . htmlspecialchars($BASE . '/index.php?' . $qs) . '">'
        . '<span class="label">' . htmlspecialchars($label) . '</span>'
        . '<span class="chevs"><span class="chev ' . ($arrowUpActive ? 'active' : '') . '">▲</span><span class="chev ' . ($arrowDownActive ? 'active' : '') . '">▼</span></span>'
        . '</a>';
}

function status_badge($s)
{
    return match ($s) {
        'available'   => 'bg-success',
        'booked'      => 'bg-secondary',
        'maintenance' => 'bg-warning text-dark',
        'unavailable' => 'bg-danger',
        'retired'     => 'bg-dark',
        default       => 'bg-light text-dark',
    };
}
?>
<div class="container py-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= $BASE ?>/index.php?page=vehicles">Pojazdy — modele</a></li>
            <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($product['name']) ?></li>
        </ol>
    </nav>

    <!-- Sekcja: Lista egzemplarzy -->
    <div class="card border-primary shadow-sm">
        <div class="card-header bg-primary text-white">
            <div class="d-flex align-items-center justify-content-between">
                <h4 class="mb-0">
                    <i class="bi bi-car-front-fill me-2"></i>Egzemplarze: <?= htmlspecialchars($product['name']) ?>
                </h4>
                <div class="d-flex gap-2">
                    <!-- Wstecz -->
                    <a href="<?= $BASE ?>/index.php?page=dashboard-staff#pane-vehicles"
                        class="btn btn-outline-light btn-sm">
                        <i class="bi bi-arrow-left me-1"></i>Wstecz
                    </a>
                    <!-- Dodaj pojazd -->
                    <a href="<?= $BASE ?>/index.php?page=vehicle-form&product_id=<?= (int)$product_id ?>"
                        class="btn btn-warning btn-sm">
                        <i class="bi bi-plus-lg me-1"></i>Dodaj pojazd tego modelu
                    </a>
                </div>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th><?= sort_link_manage('reg', 'Nr rej.') ?></th>
                            <th><?= sort_link_manage('vin', 'VIN') ?></th>
                            <th><?= sort_link_manage('status', 'Status') ?></th>
                            <th><?= sort_link_manage('insp', 'Przegląd') ?></th>
                            <th><?= sort_link_manage('insur', 'Ubezpieczenie') ?></th>
                            <th><?= sort_link_manage('mileage', 'Przebieg') ?></th>
                            <th><?= sort_link_manage('location', 'Lokalizacja') ?></th>
                            <th class="text-end">Akcje</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$rows): ?>
                            <tr>
                                <td colspan="8" class="text-center py-5">
                                    <div class="text-muted">
                                        <i class="bi bi-inbox display-4 d-block mb-3"></i>
                                        <h5>Brak egzemplarzy tego modelu</h5>
                                        <p>Dodaj pierwszy pojazd tego modelu, aby rozpocząć zarządzanie flotą.</p>
                                        <a href="<?= $BASE ?>/index.php?page=vehicle-form&product_id=<?= (int)$product_id ?>"
                                            class="btn btn-primary">
                                            <i class="bi bi-plus-lg me-1"></i>Dodaj pierwszy pojazd
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php else: foreach ($rows as $r): ?>
                                <?php $vid = (int)$r['id']; ?>
                                <tr>
                                    <td class="fw-semibold">
                                        <a class="text-decoration-none text-primary"
                                            href="<?= $BASE ?>/index.php?page=vehicle-detail&id=<?= $vid ?>">
                                            <i class="bi bi-car-front me-1"></i><?= htmlspecialchars($r['registration_number']) ?>
                                        </a>
                                    </td>
                                    <td>
                                        <?php if ($r['vin']): ?>
                                            <code class="small"><?= htmlspecialchars($r['vin']) ?></code>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><span class="badge <?= status_badge($r['status']) ?>"><?= htmlspecialchars($r['status']) ?></span></td>
                                    <td>
                                        <?php if ($r['inspection_date']): ?>
                                            <i class="bi bi-calendar-check text-success me-1"></i><?= htmlspecialchars($r['inspection_date']) ?>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($r['insurance_expiry_date']): ?>
                                            <i class="bi bi-shield-check text-info me-1"></i><?= htmlspecialchars($r['insurance_expiry_date']) ?>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($r['mileage'] !== null): ?>
                                            <i class="bi bi-speedometer text-warning me-1"></i><?= number_format((int)$r['mileage']) ?> km
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($r['location']): ?>
                                            <i class="bi bi-geo-alt text-danger me-1"></i><?= htmlspecialchars($r['location']) ?>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <div class="btn-group" role="group">
                                            <a class="btn btn-sm btn-outline-secondary"
                                                href="<?= $BASE ?>/index.php?page=vehicle-detail&id=<?= $vid ?>"
                                                title="Szczegóły">
                                                <i class="bi bi-eye"></i>
                                            </a>

                                            <a class="btn btn-sm btn-outline-primary"
                                                href="<?= $BASE ?>/index.php?page=vehicle-form&id=<?= $vid ?>"
                                                title="Edytuj">
                                                <i class="bi bi-pencil"></i>
                                            </a>

                                            <a class="btn btn-sm btn-outline-danger"
                                                href="<?= $BASE ?>/index.php?page=vehicle-delete&id=<?= $vid ?>&csrf=<?= htmlspecialchars(csrf_token()) ?>"
                                                onclick="return confirm('Usunąć egzemplarz #<?= $vid ?>? Tej operacji nie można cofnąć.');"
                                                title="Usuń">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                        <?php endforeach;
                        endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
    /* Improved card styling */
    .card {
        transition: all 0.2s ease-in-out;
    }

    .card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15) !important;
    }

    .table-hover tbody tr:hover {
        background-color: rgba(13, 110, 253, 0.05);
    }

    .btn-group .btn {
        border-radius: 0.375rem !important;
        margin: 0 1px;
    }

    .badge {
        font-size: 0.75rem;
        padding: 0.35em 0.65em;
    }

    /* VIN code styling */
    code {
        background-color: #f8f9fa;
        color: #495057;
        padding: 0.2rem 0.4rem;
        border-radius: 0.25rem;
        font-size: 0.875em;
    }

    /* Icon colors */
    .text-primary {
        color: #0d6efd !important;
    }

    .text-success {
        color: #198754 !important;
    }

    .text-info {
        color: #0dcaf0 !important;
    }

    .text-warning {
        color: #ffc107 !important;
    }

    .text-danger {
        color: #dc3545 !important;
    }

    /* Empty state styling */
    .table td .display-4 {
        opacity: 0.3;
    }
</style>