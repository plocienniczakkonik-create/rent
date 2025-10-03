<?php
// /pages/staff/section-vehicles.php
require_once dirname(__DIR__) . '/../includes/db.php';
$db = db();

$BASE = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';

/* Parametry sortowania (oddzielne od produktów, żeby się nie gryźć) */
$allowed = [
    'id'           => 'p.id',
    'name'         => 'p.name',
    'sku'          => 'p.sku',
    'total'        => 'total',
    'available'    => 'available',
    'maintenance'  => 'maintenance',
    'booked'       => 'booked',
    'unavailable'  => 'unavailable',
];
$sort = $_GET['v_sort'] ?? 'name';
$sortKey = $allowed[$sort] ?? 'p.name';
$dir  = strtolower($_GET['v_dir'] ?? 'asc');
$dir  = in_array($dir, ['asc', 'desc'], true) ? $dir : 'asc';

/* Dane */
$sql = "SELECT p.id, p.name, p.sku, p.price, p.status,
        COALESCE(COUNT(v.id), 0)                           AS total,
        COALESCE(SUM(v.status = 'available'), 0)          AS available,
        COALESCE(SUM(v.status = 'maintenance'), 0)        AS maintenance,
        COALESCE(SUM(v.status = 'booked'), 0)             AS booked,
        COALESCE(SUM(v.status = 'unavailable'), 0)        AS unavailable
        FROM products p
        LEFT JOIN vehicles v ON v.product_id = p.id
        GROUP BY p.id
        ORDER BY $sortKey $dir, p.name ASC";
$rows = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);

/* Helper do linków sortujących – wracamy do dashboardu na #pane-vehicles */
function v_sort_link(string $key, string $label): string
{
    $currentSort = $_GET['v_sort'] ?? 'name';
    $currentDir  = strtolower($_GET['v_dir'] ?? 'asc');
    $nextDir     = ($currentSort === $key && $currentDir === 'asc') ? 'desc' : 'asc';

    $upActive   = ($currentSort === $key && $currentDir === 'asc');
    $downActive = ($currentSort === $key && $currentDir === 'desc');

    $BASE = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
    $href = $BASE . '/index.php?page=dashboard-staff&v_sort=' . urlencode($key) . '&v_dir=' . $nextDir . '#pane-vehicles';

    return '<a class="th-sort-link" href="' . htmlspecialchars($href) . '">'
        . '<span class="label">' . htmlspecialchars($label) . '</span>'
        . '<span class="chevs"><span class="chev ' . ($upActive ? 'active' : '') . '">▲</span><span class="chev ' . ($downActive ? 'active' : '') . '">▼</span></span>'
        . '</a>';
}
?>
<style>
    /* Tabela floty – kompaktowy layout, wszystko się mieści */
    .table-vehicles th {
        vertical-align: middle;
        white-space: nowrap;
    }

    .table-vehicles .th-narrow {
        width: 1%;
    }

    .table-vehicles .th-name {
        min-width: 220px;
    }

    /* rezerwa na nazwę */

    /* Link sortowania – mniejsze labelki i strzałki */
    .th-sort-link {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        text-decoration: none;
    }

    .th-sort-link .label {
        font-size: .92rem;
        font-weight: 600;
    }

    .th-sort-link .chevs {
        display: inline-flex;
        flex-direction: column;
        line-height: .7;
    }

    .th-sort-link .chev {
        font-size: .65rem;
        opacity: .35;
    }

    .th-sort-link .chev.active {
        opacity: 1;
    }

    /* Badge liczbowe – równe i wycentrowane */
    .badge-count {
        font-weight: 600;
    }

    .badge-dot {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 2rem;
        height: 2rem;
        border-radius: 999px;
        font-weight: 700;
        padding: 0 .5rem;
    }

    /* Na mniejszych ekranach lekko ściskamy marginesy */
    @media (max-width: 1200px) {
        .table-vehicles .th-name {
            min-width: 180px;
        }
    }
</style>

<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h2 class="h6 mb-0">Zarządzaj flotą</h2>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 table-vehicles">
            <thead>
                <tr>
                    <th class="th-id th-narrow"><?= v_sort_link('id', 'ID') ?></th>
                    <th class="th-name"><?= v_sort_link('name', 'Nazwa') ?></th>
                    <th class="th-sku th-narrow"><?= v_sort_link('sku', 'SKU') ?></th>
                    <th class="th-total th-narrow text-end"><?= v_sort_link('total', 'Egzemplarze') ?></th>

                    <th class="th-narrow text-center"><?= v_sort_link('available', 'Dostępne') ?></th>
                    <th class="th-narrow text-center"><?= v_sort_link('maintenance', 'Serwis') ?></th>
                    <th class="th-narrow text-center"><?= v_sort_link('booked', 'Rezerwacje') ?></th>
                    <th class="th-narrow text-center"><?= v_sort_link('unavailable', 'Niedostępne') ?></th>

                    <th class="th-actions th-narrow text-end">Akcje</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$rows): ?>
                    <tr>
                        <td colspan="9" class="text-center py-5 text-muted">Brak modeli. Dodaj pierwszy w „Dodaj model”.</td>
                    </tr>
                    <?php else: foreach ($rows as $r): ?>
                        <tr>
                            <td class="text-muted">#<?= (int)$r['id'] ?></td>

                            <td class="fw-semibold">
                                <a class="text-decoration-none" href="<?= $BASE ?>/index.php?page=product-form&id=<?= (int)$r['id'] ?>">
                                    <?= htmlspecialchars($r['name']) ?>
                                </a>
                            </td>

                            <td class="text-muted"><?= htmlspecialchars($r['sku'] ?? '') ?></td>

                            <td class="text-end">
                                <span class="badge bg-secondary badge-count"><?= (int)$r['total'] ?> egz.</span>
                            </td>

                            <!-- statusy w osobnych, wycentrowanych kolumnach -->
                            <td class="text-center">
                                <span class="badge bg-success badge-dot"><?= (int)$r['available'] ?></span>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-warning text-dark badge-dot"><?= (int)$r['maintenance'] ?></span>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-secondary badge-dot"><?= (int)$r['booked'] ?></span>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-danger badge-dot"><?= (int)$r['unavailable'] ?></span>
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