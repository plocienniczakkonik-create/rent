<?php
// /pages/staff/section-vehicles.php
require_once dirname(__DIR__, 2) . '/includes/db.php';
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
        min-width: 150px;
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

    /* Optymalizacja szerokości kolumn */
    .table-vehicles .th-id {
        width: 60px;
        max-width: 60px;
    }

    .table-vehicles .th-name {
        width: 80px;
        max-width: 80px;
    }

    /* Łamanie długich nazw w wąskiej kolumnie */
    .table-vehicles td:nth-child(2) {
        word-break: break-word;
        hyphens: auto;
        line-height: 1.3;
    }

    .table-vehicles .th-sku {
        width: 80px;
        max-width: 80px;
    }

    .table-vehicles .th-total {
        width: 100px;
        max-width: 100px;
    }

    /* Kolumny statusów - bardzo wąskie */
    .table-vehicles th:nth-child(5),
    /* Dostępne */
    .table-vehicles th:nth-child(6),
    /* Serwis */
    .table-vehicles th:nth-child(7),
    /* Rezerwacje */
    .table-vehicles th:nth-child(8) {
        /* Niedostępne */
        width: 70px;
        max-width: 70px;
        padding: 0.5rem 0.25rem;
    }

    .table-vehicles td:nth-child(5),
    .table-vehicles td:nth-child(6),
    .table-vehicles td:nth-child(7),
    .table-vehicles td:nth-child(8) {
        padding: 0.5rem 0.25rem;
    }

    .table-vehicles .th-actions {
        width: 120px;
        max-width: 120px;
    }

    /* Style dla przycisku z ikoną */
    .table-vehicles .btn-sm {
        min-width: 55px;
        height: 35px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 10;
    }

    .table-vehicles .btn-sm i {
        font-size: 0.875rem;
    }

    .table-vehicles .btn-sm .bi-gear {
        padding-right: 4px;
    }

    /* Na mniejszych ekranach optymalizacje */
    @media (max-width: 1200px) {
        .table-vehicles .th-name {
            width: 75px;
            max-width: 75px;
        }

        /* Jeszcze mniejsze kolumny statusów */
        .table-vehicles th:nth-child(5),
        .table-vehicles th:nth-child(6),
        .table-vehicles th:nth-child(7),
        .table-vehicles th:nth-child(8) {
            width: 60px;
            max-width: 60px;
        }
    }

    @media (max-width: 992px) {
        .table-vehicles .th-name {
            width: 70px;
            max-width: 70px;
        }

        .table-vehicles th:nth-child(5),
        .table-vehicles th:nth-child(8) {
            width: 65px;
            max-width: 65px;
        }
    }

    @media (max-width: 768px) {
        .table-vehicles .th-name {
            width: 65px;
            max-width: 65px;
        }

        .table-vehicles .th-id {
            width: 50px;
            max-width: 50px;
        }

        .table-vehicles .th-total {
            width: 85px;
            max-width: 85px;
        }

        .table-vehicles th:nth-child(5) {
            /* Dostępne - jedyna widoczna */
            width: 60px;
            max-width: 60px;
        }

        .table-vehicles .th-actions {
            width: 110px;
            max-width: 110px;
        }

        /* Zmniejsz padding w komórkach */
        .table-vehicles td,
        .table-vehicles th {
            padding: 0.5rem 0.3rem;
        }

        /* Zmniejsz czcionkę */
        .table-vehicles {
            font-size: 0.875rem;
        }

        /* Zmniejsz badges */
        .table-vehicles .badge-dot {
            min-width: 1.5rem;
            height: 1.5rem;
            font-size: 0.75rem;
        }

        .table-vehicles .badge-count {
            font-size: 0.75rem;
            padding: 0.25rem 0.4rem;
        }

        /* Zmniejsz przycisk na mobile */
        .table-vehicles .btn-sm {
            font-size: 0.75rem;
            padding: 0.2rem 0.4rem;
            min-width: 90px;
            gap: 0.2rem;
        }
    }
</style>

<div class="card section-vehicles">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h2 class="h6 mb-0"><?= __('fleet_management', 'admin', 'Zarządzaj flotą') ?></h2>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 table-vehicles">
                <thead>
                    <tr>
                        <th class="th-id th-narrow"><?= v_sort_link('id', __('id', 'admin', 'ID')) ?></th>
                        <th class="th-name"><?= v_sort_link('name', __('name', 'admin', 'Nazwa')) ?></th>
                        <th class="th-sku th-narrow d-none d-xl-table-cell"><?= v_sort_link('sku', __('sku', 'admin', 'SKU')) ?></th>
                        <th class="th-total th-narrow text-end"><?= v_sort_link('total', __('copies', 'admin', 'Egz.')) ?></th>

                        <th class="th-narrow text-center" title="<?= __('available', 'admin', 'Dostępne') ?>"><?= v_sort_link('available', __('available_short', 'admin', 'Dost.')) ?></th>
                        <th class="th-narrow text-center d-none d-lg-table-cell" title="<?= __('service', 'admin', 'Serwis') ?>"><?= v_sort_link('maintenance', __('service_short', 'admin', 'Serw.')) ?></th>
                        <th class="th-narrow text-center d-none d-lg-table-cell" title="<?= __('reservations', 'admin', 'Rezerwacje') ?>"><?= v_sort_link('booked', __('reservations_short', 'admin', 'Rez.')) ?></th>
                        <th class="th-narrow text-center d-none d-md-table-cell" title="<?= __('unavailable', 'admin', 'Niedostępne') ?>"><?= v_sort_link('unavailable', __('unavailable_short', 'admin', 'Niedost.')) ?></th>

                        <th class="th-actions th-narrow text-end"><?= __('actions', 'admin', 'Akcje') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$rows): ?>
                        <tr>
                            <td colspan="9" class="text-center py-5 text-muted"><?= __('no_models_add_first', 'admin', 'Brak modeli. Dodaj pierwszy w Dodaj model.') ?></td>
                        </tr>
                        <?php else: foreach ($rows as $r): ?>
                            <tr>
                                <td class="text-muted">#<?= (int)$r['id'] ?></td>

                                <td class="fw-semibold">
                                    <a class="text-decoration-none" href="<?= $BASE ?>/index.php?page=product-form&id=<?= (int)$r['id'] ?>">
                                        <?= htmlspecialchars($r['name']) ?>
                                    </a>
                                </td>

                                <td class="text-muted d-none d-xl-table-cell"><?= htmlspecialchars($r['sku'] ?? '') ?></td>

                                <td class="text-end">
                                    <span class="badge bg-secondary badge-count"><?= (int)$r['total'] ?> egz.</span>
                                </td>

                                <!-- statusy w osobnych, wycentrowanych kolumnach -->
                                <td class="text-center">
                                    <span class="badge bg-success badge-dot"><?= (int)$r['available'] ?></span>
                                </td>
                                <td class="text-center d-none d-lg-table-cell">
                                    <span class="badge bg-warning text-dark badge-dot"><?= (int)$r['maintenance'] ?></span>
                                </td>
                                <td class="text-center d-none d-lg-table-cell">
                                    <span class="badge bg-secondary badge-dot"><?= (int)$r['booked'] ?></span>
                                </td>
                                <td class="text-center d-none d-md-table-cell">
                                    <span class="badge bg-danger badge-dot"><?= (int)$r['unavailable'] ?></span>
                                </td>

                                <td class="text-end">
                                    <a class="btn btn-sm btn-primary" title="<?= __('fleet_management', 'admin', 'Zarządzaj flotą') ?>"
                                        href="<?= $BASE ?>/index.php?page=vehicles-manage&product=<?= (int)$r['id'] ?>">
                                        <i class="bi bi-gear"></i> <?= __('manage', 'admin', 'Zarządzaj') ?>
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