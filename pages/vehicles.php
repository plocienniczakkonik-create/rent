<?php
// /pages/vehicles.php — Flota (modele) + pełne sortowanie + poprawiony layout
require_once dirname(__DIR__) . '/auth/auth.php';
require_staff();

require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/theme-config.php';
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

<style>
    <?= ThemeConfig::generateCSSVariables() ?>

    /* Minimalistyczny design zgodny ze standardem projektu */
    .vehicle-models-header {
        background: var(--gradient-primary);
        border: none;
        color: white;
    }

    .fleet-status {
        display: flex;
        flex-wrap: wrap;
        gap: 4px;
        align-items: center;
    }

    .fleet-status .badge {
        font-size: 0.75rem;
        font-weight: 500;
        border-radius: 12px;
        padding: 4px 8px;
        white-space: nowrap;
    }

    /* Pastelowe kolory dla badge'ów statusu */
    .badge-available {
        background-color: #dcfce7 !important;
        color: #166534 !important;
        border: 1px solid #bbf7d0;
    }

    .badge-maintenance {
        background-color: #fef3c7 !important;
        color: #92400e !important;
        border: 1px solid #fde68a;
    }

    .badge-booked {
        background-color: #e0e7ff !important;
        color: #3730a3 !important;
        border: 1px solid #c7d2fe;
    }

    .badge-unavailable {
        background-color: #fecaca !important;
        color: #991b1b !important;
        border: 1px solid #fca5a5;
    }

    .badge-retired {
        background-color: #f3f4f6 !important;
        color: #374151 !important;
        border: 1px solid #d1d5db;
    }

    .badge-total {
        background-color: #f1f5f9 !important;
        color: #475569 !important;
        border: 1px solid #cbd5e1;
        font-weight: 600;
    }

    /* Sortowanie nagłówków */
    .th-sort-link {
        color: white;
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 4px;
        font-weight: 500;
    }

    .th-sort-link:hover {
        color: rgba(255, 255, 255, 0.9);
    }

    .chevs {
        display: flex;
        flex-direction: column;
        font-size: 0.6rem;
        line-height: 0.8;
        margin-left: 2px;
    }

    .chev {
        opacity: 0.4;
        transition: opacity 0.2s;
    }

    .chev.active {
        opacity: 1;
    }

    /* Responsywność dla fleet-status */
    @media (max-width: 768px) {
        .fleet-status {
            flex-direction: column;
            align-items: flex-start;
            gap: 2px;
        }

        .fleet-status .badge {
            font-size: 0.7rem;
            padding: 2px 6px;
        }
    }

    /* Hover effects dla tabeli */
    .table tbody tr:hover {
        background-color: rgba(139, 92, 246, 0.03);
    }

    /* Model name link styling */
    .model-name-link {
        color: var(--color-primary);
        font-weight: 600;
        text-decoration: none;
        transition: color 0.2s;
    }

    .model-name-link:hover {
        color: var(--color-primary-dark);
        text-decoration: underline;
    }
</style>

<div class="container py-4">
    <!-- Breadcrumb/Navigation -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= $BASE ?>/index.php?page=dashboard-staff">Dashboard</a></li>
            <li class="breadcrumb-item active">Pojazdy — modele</li>
        </ol>
    </nav>

    <!-- Główny nagłówek sekcji -->
    <div class="card mb-4">
        <div class="card-header text-white d-flex align-items-center justify-content-between vehicle-models-header">
            <h1 class="mb-0 d-flex align-items-center">
                <i class="fas fa-car me-3"></i>
                Pojazdy — modele
            </h1>
            <div class="d-flex gap-2">
                <a href="<?= $BASE ?>/index.php?page=product-form" class="btn btn-light">
                    <i class="fas fa-plus me-1"></i>Dodaj model
                </a>
            </div>
        </div>
    </div>

    <!-- Sekcja: Lista modeli -->
    <div class="card">
        <div class="card-header" style="background: white; border-bottom: 1px solid #6b7280;">
            <h5 class="mb-0 d-flex align-items-center">
                <i class="fas fa-list me-2"></i>
                Modele
            </h5>
        </div>
        <div class="card-body p-0">
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