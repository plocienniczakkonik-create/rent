<?php
// /pages/vehicles.php — Flota (modele) + pełne sortowanie + poprawiony layout
require_once dirname(__DIR__) . '/auth/auth.php';
require_staff();

require_once dirname(__DIR__) . '/includes/db.php';
$db = db(); // <— kluczowe

$BASE = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';

/* 1) Parametry sortowania (biała lista) */
$allowed = [
    'id'           => 'p.id',
    'name'         => 'p.name',
    'sku'          => 'p.sku',
    'total'        => 'total',
    'available'    => 'available',
    'maintenance'  => 'maintenance',
    'booked'       => 'booked',
    'unavailable'  => 'unavailable',
    'retired'      => 'retired',
];
$sort = $_GET['sort'] ?? 'name';
$sortKey = $allowed[$sort] ?? 'p.name';
$dir = strtolower($_GET['dir'] ?? 'asc');
$dir = in_array($dir, ['asc', 'desc'], true) ? $dir : 'asc';

/* 2) Dane */
$sql = "SELECT p.id, p.name, p.sku, p.price, p.status,
        COALESCE(COUNT(v.id), 0)                           AS total,
        COALESCE(SUM(v.status = 'available'), 0)          AS available,
        COALESCE(SUM(v.status = 'maintenance'), 0)        AS maintenance,
        COALESCE(SUM(v.status = 'unavailable'), 0)        AS unavailable,
        COALESCE(SUM(v.status = 'booked'), 0)             AS booked,
        COALESCE(SUM(v.status = 'retired'), 0)            AS retired
        FROM products p
        LEFT JOIN vehicles v ON v.product_id = p.id
        GROUP BY p.id
        ORDER BY $sortKey $dir, p.name ASC";
$rows = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);

/* 3) Helper do linków sortujących — zawsze pokazuje ▲▼, aktywna strzałka podświetlona */
function sort_link(string $key, string $label): string
{
    $currentSort = $_GET['sort'] ?? 'name';
    $currentDir  = strtolower($_GET['dir'] ?? 'asc');
    $nextDir     = ($currentSort === $key && $currentDir === 'asc') ? 'desc' : 'asc';

    $BASE = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
    $href = $BASE . '/index.php?page=vehicles&sort=' . urlencode($key) . '&dir=' . $nextDir;

    // stan strzałek
    $upActive   = ($currentSort === $key && $currentDir === 'asc');
    $downActive = ($currentSort === $key && $currentDir === 'desc');

    return '<a class="th-sort-link" href="' . htmlspecialchars($href) . '">'
        . '<span class="label">' . htmlspecialchars($label) . '</span>'
        . '<span class="chevs">'
        . '<span class="chev ' . ($upActive ? 'active' : '') . '">▲</span>'
        . '<span class="chev ' . ($downActive ? 'active' : '') . '">▼</span>'
        . '</span>'
        . '</a>';
}
?>

<div class="container py-4">
    <div class="d-flex pt-5 align-items-center justify-content-between mb-3">
        <h1 class="h3 m-0">Pojazdy — modele</h1>
        <a href="<?= $BASE ?>/index.php?page=products" class="btn btn-outline-secondary">Przejdź do produktów</a>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Modele</span>
            <a href="<?= $BASE ?>/index.php?page=products" class="btn btn-sm btn-primary">Dodaj model</a>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="th-id"><?= sort_link('id', 'ID') ?></th>
                        <th class="th-name"><?= sort_link('name', 'Nazwa') ?></th>
                        <th class="th-sku"><?= sort_link('sku', 'SKU') ?></th>
                        <th class="th-total"><?= sort_link('total', 'Egzemplarze') ?></th>
                        <th>
                            <div class="fleet-status">
                                <?= sort_link('available', 'Dost.') ?>
                                <?= sort_link('maintenance', 'Serwis') ?>
                                <?= sort_link('booked', 'Rezerw.') ?>
                                <?= sort_link('unavailable', 'Niedost.') ?>
                                <?= sort_link('retired', 'Wycof.') ?>
                            </div>
                        </th>
                        <th class="th-actions">Akcje</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$rows): ?>
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">Brak modeli. Dodaj pierwszy model w sekcji „Produkty”.</td>
                        </tr>
                        <?php else: foreach ($rows as $r): ?>
                            <tr>
                                <td class="text-muted">#<?= (int)$r['id'] ?></td>
                                <td class="fw-semibold">
                                    <a class="text-decoration-none" href="<?= $BASE ?>/index.php?page=product-edit&id=<?= (int)$r['id'] ?>">
                                        <?= htmlspecialchars($r['name']) ?>
                                    </a>
                                </td>
                                <td class="text-muted"><?= htmlspecialchars($r['sku'] ?? '') ?></td>
                                <td class="text-end"><span class="badge bg-secondary"><?= (int)$r['total'] ?> egz.</span></td>
                                <td>
                                    <div class="fleet-status">
                                        <span class="badge bg-success">Dost.: <?= (int)$r['available'] ?></span>
                                        <span class="badge bg-warning text-dark">Serwis: <?= (int)$r['maintenance'] ?></span>
                                        <span class="badge bg-secondary">Rezerw.: <?= (int)$r['booked'] ?></span>
                                        <span class="badge bg-danger">Niedost.: <?= (int)$r['unavailable'] ?></span>
                                        <span class="badge bg-dark">Wycof.: <?= (int)$r['retired'] ?></span>
                                    </div>
                                </td>
                                <td class="text-end">
                                    <a class="btn btn-sm btn-outline-primary"
                                        href="<?= $BASE ?>/index.php?page=vehicles-manage&product=<?= (int)$r['id'] ?>">
                                        Zarządzaj
                                    </a>
                                </td>
                            </tr>
                    <?php endforeach;
                    endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>