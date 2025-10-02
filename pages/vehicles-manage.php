<?php
// /pages/vehicles-manage.php — egzemplarze danego modelu (z sortowaniem + przycisk Wstecz)
require_once dirname(__DIR__) . '/auth/auth.php';
require_staff();

require_once dirname(__DIR__) . '/includes/db.php';
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

    <div class="d-flex align-items-center justify-content-between mb-3">
        <h1 class="h4 m-0">Egzemplarze: <?= htmlspecialchars($product['name']) ?></h1>
        <div class="d-flex gap-2">
            <!-- NOWY: Wstecz -->
            <a href="<?= $BASE ?>/index.php?page=dashboard-staff#pane-vehicles" class="btn btn-outline-secondary">← Wstecz</a>
            <!-- Dodaj pojazd -->
            <a href="<?= $BASE ?>/index.php?page=vehicle-form&product_id=<?= (int)$product_id ?>"
                class="btn btn-primary">+ Dodaj pojazd tego modelu</a>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-striped align-middle mb-0">
                <thead>
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
                            <td colspan="8" class="text-center py-5 text-muted">Brak egzemplarzy tego modelu. Dodaj pierwszy.</td>
                        </tr>
                        <?php else: foreach ($rows as $r): ?>
                            <tr>
                                <td class="fw-semibold">
                                    <a class="text-decoration-none"
                                        href="<?= $BASE ?>/index.php?page=vehicle-detail&id=<?= (int)$r['id'] ?>">
                                        <?= htmlspecialchars($r['registration_number']) ?>
                                    </a>
                                </td>
                                <td><?= $r['vin'] ? htmlspecialchars($r['vin']) : '<span class="text-muted">—</span>' ?></td>
                                <td><span class="badge <?= status_badge($r['status']) ?>"><?= htmlspecialchars($r['status']) ?></span></td>
                                <td><?= $r['inspection_date'] ? htmlspecialchars($r['inspection_date']) : '<span class="text-muted">—</span>' ?></td>
                                <td><?= $r['insurance_expiry_date'] ? htmlspecialchars($r['insurance_expiry_date']) : '<span class="text-muted">—</span>' ?></td>
                                <td><?= $r['mileage'] !== null ? (int)$r['mileage'] . ' km' : '<span class="text-muted">—</span>' ?></td>
                                <td><?= $r['location'] ? htmlspecialchars($r['location']) : '<span class="text-muted">—</span>' ?></td>
                                <td class="text-end">
                                    <a href="<?= $BASE ?>/index.php?page=vehicle-detail&id=<?= (int)$r['id'] ?>" class="btn btn-sm btn-outline-primary">Szczegóły</a>
                                    <a href="<?= $BASE ?>/index.php?page=vehicle-form&id=<?= (int)$r['id'] ?>" class="btn btn-sm btn-outline-secondary">Edytuj</a>
                                </td>
                            </tr>
                    <?php endforeach;
                    endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>