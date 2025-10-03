<?php
// /pages/vehicles-manage.php — egzemplarze danego modelu (widok szczegółów pojazdu)
require_once dirname(__DIR__) . '/auth/auth.php';
require_staff();

require_once dirname(__DIR__) . '/includes/db.php';
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

/** Mapowanie badge statusu */
$badge = match ($veh['status']) {
    'available'   => 'bg-success',
    'booked'      => 'bg-secondary',
    'maintenance' => 'bg-warning text-dark',
    'unavailable' => 'bg-danger',
    'retired'     => 'bg-dark',
    default       => 'bg-secondary'
};

/** SERWISY — ostatnie 5 wpisów */
$q1 = $db->prepare("SELECT *
                    FROM vehicle_services
                    WHERE vehicle_id = ?
                    ORDER BY service_date DESC, id DESC
                    LIMIT 5");
$q1->execute([(int)$veh['id']]);
$services = $q1->fetchAll(PDO::FETCH_ASSOC);

/** KOLIZJE — ostatnie 5 wpisów */
$q2 = $db->prepare("SELECT *
                    FROM vehicle_incidents
                    WHERE vehicle_id = ?
                    ORDER BY incident_date DESC, id DESC
                    LIMIT 5");
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
    'our'     => 'danger',
    'other'   => 'success',
    'shared'  => 'warning',
    'unknown' => 'secondary',
];
?>
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

    <div class="d-flex flex-wrap align-items-center justify-content-between mb-3">
        <h1 class="h4 m-0">Pojazd: <?= htmlspecialchars($veh['registration_number']) ?></h1>
        <div class="d-flex gap-2">
            <span class="badge <?= $badge ?> align-self-center"><?= htmlspecialchars($veh['status']) ?></span>
            <a href="<?= $BASE ?>/index.php?page=vehicle-form&id=<?= (int)$veh['id'] ?>" class="btn btn-outline-secondary">Edytuj</a>
        </div>
    </div>

    <div class="row g-3">
        <!-- Lewa kolumna: główne sekcje -->
        <div class="col-lg-8">
            <!-- Metryka -->
            <div class="card mb-3">
                <div class="card-header">Metryka pojazdu</div>
                <div class="card-body p-3">
                    <table class="table table-sm mb-0 vehicle-stats-table">
                        <style>
                        .vehicle-stats-table th, .vehicle-stats-table td {
                            padding: 0.75rem 1.25rem !important;
                        }
                        </style>
                        <tbody class="vehicle-stats-table">
                        <tbody>
                            <tr>
                                <th class="w-25">Model</th>
                                <td class="fw-semibold"><?= htmlspecialchars($veh['product_name']) ?></td>
                                <th class="w-25">VIN</th>
                                <td class="fw-semibold"><?= $veh['vin'] ? htmlspecialchars($veh['vin']) : '—' ?></td>
                            </tr>
                            <tr>
                                <th>Przebieg</th>
                                <td><?= $veh['mileage'] !== null ? (int)$veh['mileage'] . ' km' : '—' ?></td>
                                <th>Lokalizacja</th>
                                <td><?= $veh['location'] ? htmlspecialchars($veh['location']) : '—' ?></td>
                            </tr>
                            <tr>
                                <th>Status</th>
                                <td><span class="badge <?= $badge ?>"><?= htmlspecialchars($veh['status']) ?></span></td>
                                <th></th>
                                <td></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Terminy -->
            <div class="card mb-3">
                <div class="card-header">Terminy</div>
                <div class="card-body p-3">
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

            <!-- Notatki -->
            <div class="card mb-3">
                <div class="card-header">Notatki</div>
                <div class="card-body p-3">
                    <div><?= $veh['notes'] ? nl2br(htmlspecialchars($veh['notes'])) : '<span class="text-muted">Brak notatek.</span>' ?></div>
                </div>
            </div>

            <!-- Serwisy -->
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Serwisy</span>
                    <a class="btn btn-sm btn-outline-primary"
                        href="<?= $BASE ?>/index.php?page=vehicle-service-form&vehicle_id=<?= (int)$veh['id'] ?>">
                        Dodaj serwis
                    </a>
                </div>
                <div class="card-body p-0">
                    <?php if (!$services): ?>
                        <div class="p-3 text-muted">Brak wpisów serwisowych.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table mb-0 align-middle">
                                <thead>
                                    <tr>
                                        <th>Data</th>
                                        <th>Przebieg</th>
                                        <th>Co było zepsute</th>
                                        <th>Kwota</th>
                                        <th>Warsztat</th>
                                        <th class="text-end">Akcje</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($services as $s): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($s['service_date']) ?></td>
                                            <td><?= $s['odometer_km'] ? (int)$s['odometer_km'] . ' km' : '—' ?></td>
                                            <td class="text-truncate" style="max-width:420px;"><?= nl2br(htmlspecialchars($s['issues_found'] ?: '—')) ?></td>
                                            <td><?= number_format((float)$s['cost_total'], 2, ',', ' ') ?> zł</td>
                                            <td><?= htmlspecialchars($s['workshop_name'] ?: '—') ?></td>
                                            <td class="text-end">
                                                <a class="btn btn-sm btn-link"
                                                    href="<?= $BASE ?>/index.php?page=vehicle-service-form&id=<?= (int)$s['id'] ?>&vehicle_id=<?= (int)$veh['id'] ?>">
                                                    Edytuj
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
                                                    <button class="btn btn-sm btn-outline-danger">Usuń</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Kolizje / Szkody -->
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Kolizje / szkody</span>
                    <a class="btn btn-sm btn-outline-primary"
                        href="<?= $BASE ?>/index.php?page=vehicle-incident-form&vehicle_id=<?= (int)$veh['id'] ?>">
                        Zgłoś kolizję
                    </a>
                </div>
                <div class="card-body p-0">
                    <?php if (!$incidents): ?>
                        <div class="p-3 text-muted">Brak zgłoszonych kolizji.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table mb-0 align-middle vehicle-section-table">
                                <thead>
                                    <tr>
                                        <th>Data</th>
                                        <th>Kierowca</th>
                                        <th>Opis uszkodzeń</th>
                                        <th>Wina</th>
                                        <th>Koszt</th>
                                        <th class="text-end">Akcje</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($incidents as $i): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($i['incident_date']) ?></td>
                                            <td><?= htmlspecialchars($i['driver_name'] ?: '—') ?></td>
                                            <td class="text-truncate" style="max-width:420px;"><?= nl2br(htmlspecialchars($i['damage_desc'] ?: '—')) ?></td>
                                            <td>
                                                <?php
                                                $f = $i['fault'] ?? 'unknown';
                                                $lbl = $faultLabel[$f] ?? $f;
                                                $cls = $faultBadge[$f] ?? 'secondary';
                                                ?>
                                                <span class="badge text-bg-<?= $cls ?>"><?= htmlspecialchars($lbl) ?></span>
                                            </td>
                                            <td><?= number_format((float)$i['repair_cost'], 2, ',', ' ') ?> zł</td>
                                            <td class="text-end">
                                                <a class="btn btn-sm btn-link"
                                                    href="<?= $BASE ?>/index.php?page=vehicle-incident-form&id=<?= (int)$i['id'] ?>&vehicle_id=<?= (int)$veh['id'] ?>">
                                                    Edytuj
                                                </a>
                                                <form method="post" action="/rental/index.php?page=vehicle-incident-delete"
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
                                                    <button class="btn btn-sm btn-outline-danger">Usuń</button>
                                                </form>
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
            // Pobierz zamówienia dla pojazdu z tabeli orders
            $q3 = $db->prepare("SELECT * FROM orders WHERE vehicle_id = ? ORDER BY placed_at DESC, id DESC");
            $q3->execute([(int)$veh['id']]);
            $orders = $q3->fetchAll(PDO::FETCH_ASSOC);
            $orderCount = count($orders);
            $orderSum = array_sum(array_column($orders, 'total_gross'));
            ?>
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Historia wynajmu</span>
                    <a class="btn btn-sm btn-outline-primary"
                        href="<?= $BASE ?>/index.php?page=vehicle-order-form&vehicle_id=<?= (int)$veh['id'] ?>">
                        Dodaj zamówienie
                    </a>
                </div>
                <div class="card-body p-0">
                    <?php if (!$orders): ?>
                        <div class="p-3 text-muted">Brak zamówień/wynajmów.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table mb-0 align-middle vehicle-section-table">
                                <thead>
                                    <tr>
                                        <th>Numer</th>
                                        <th>Status</th>
                                        <th>Kwota netto</th>
                                        <th>Kwota VAT</th>
                                        <th>Kwota brutto</th>
                                        <th>Waluta</th>
                                        <th>Data złożenia</th>
                                        <th class="text-end">Akcje</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    function order_status_badge($status) {
                                        return match (strtolower($status)) {
                                            'paid' => 'bg-primary',
                                            'completed' => 'bg-success',
                                            'cancelled' => 'bg-danger',
                                            default => 'bg-secondary',
                                        };
                                    }
                                    foreach ($orders as $o): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($o['number']) ?></td>
                                            <td><span class="badge <?= order_status_badge($o['status']) ?>"><?= htmlspecialchars($o['status']) ?></span></td>
                                            <td><?= number_format((float)$o['total_net'], 2, ',', ' ') ?> <?= htmlspecialchars($o['currency']) ?></td>
                                            <td><?= number_format((float)$o['total_vat'], 2, ',', ' ') ?> <?= htmlspecialchars($o['currency']) ?></td>
                                            <td><?= number_format((float)$o['total_gross'], 2, ',', ' ') ?> <?= htmlspecialchars($o['currency']) ?></td>
                                            <td><?= htmlspecialchars($o['currency']) ?></td>
                                            <td><?= htmlspecialchars($o['placed_at']) ?></td>
                                            <td class="text-end">
                                                <div class="d-inline-flex gap-1">
                                                    <a class="btn btn-sm btn-link"
                                                        href="<?= $BASE ?>/index.php?page=vehicle-order-form&id=<?= (int)$o['id'] ?>&vehicle_id=<?= (int)$veh['id'] ?>">
                                                        Edytuj
                                                    </a>
                                                    <form method="post" action="/rental/index.php?page=vehicle-order-delete"
                                                        class="d-inline" onsubmit="return confirm('Usunąć zamówienie?')">
                                                        <?php
                                                        if (session_status() !== PHP_SESSION_ACTIVE) {
                                                            session_start();
                                                        }
                                                        $tok = $_SESSION['_token'] ?? bin2hex(random_bytes(32));
                                                        $_SESSION['_token'] = $tok;
                                                        echo '<input type="hidden" name="_token" value="' . htmlspecialchars($tok, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '">';
                                                        ?>
                                                        <input type="hidden" name="id" value="<?= (int)$o['id'] ?>">
                                                        <input type="hidden" name="vehicle_id" value="<?= (int)$veh['id'] ?>">
                                                        <button class="btn btn-sm btn-outline-danger">Usuń</button>
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
        </div>


        <!-- Prawa kolumna: szybkie akcje i statystyki -->
        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header">Szybkie akcje</div>
                <div class="card-body d-grid gap-2">
                    <a class="btn btn-outline-primary"
                        href="<?= $BASE ?>/index.php?page=vehicle-service-form&vehicle_id=<?= (int)$veh['id'] ?>">
                        Dodaj serwis
                    </a>
                    <a class="btn btn-outline-warning"
                        href="<?= $BASE ?>/index.php?page=vehicle-incident-form&vehicle_id=<?= (int)$veh['id'] ?>">
                        Zgłoś kolizję
                    </a>
                    <a class="btn btn-outline-secondary disabled" title="W kolejnej iteracji">Przypisz do rezerwacji</a>
                    <a class="btn btn-outline-success disabled" title="W kolejnej iteracji">Ustaw przypomnienia</a>
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
            <div class="card vehicle-stats-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Statystyki pojazdu</span>
                    <form method="get" class="d-inline-flex gap-2 align-items-center" style="margin-bottom:0;">
                        <input type="hidden" name="page" value="vehicle-detail">
                        <input type="hidden" name="id" value="<?= (int)$veh['id'] ?>">
                        <select name="period" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="all"<?= $period=='all'?' selected':'' ?>>Cały okres</option>
                            <option value="today"<?= $period=='today'?' selected':'' ?>>Dzisiaj</option>
                            <option value="7d"<?= $period=='7d'?' selected':'' ?>>Ostatnie 7 dni</option>
                            <option value="30d"<?= $period=='30d'?' selected':'' ?>>Ostatnie 30 dni</option>
                            <option value="month"<?= $period=='month'?' selected':'' ?>>Ostatni miesiąc</option>
                            <option value="year"<?= $period=='year'?' selected':'' ?>>Bieżący rok</option>
                        </select>
                    </form>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        <tbody>
                            <tr>
                                <th>Liczba wynajmów</th>
                                <td><?= (int)($statsOrders['cnt'] ?? 0) ?></td>
                            </tr>
                            <tr>
                                <th>Liczba szkód</th>
                                <td><?= (int)($statsIncidents['cnt'] ?? 0) ?></td>
                            </tr>
                            <tr>
                                <th>Liczba serwisów</th>
                                <td><?= (int)($statsServices['cnt'] ?? 0) ?></td>
                            </tr>
                            <tr>
                                <th>Przychód netto</th>
                                <td><?= number_format((float)($statsOrders['net'] ?? 0), 2, ',', ' ') ?> zł</td>
                            </tr>
                            <tr>
                                <th>Przychód brutto</th>
                                <td><?= number_format((float)($statsOrders['gross'] ?? 0), 2, ',', ' ') ?> zł</td>
                            </tr>
                            <tr>
                                <th>Koszty serwisów</th>
                                <td><?= number_format((float)($statsServices['cost'] ?? 0), 2, ',', ' ') ?> zł</td>
                            </tr>
                            <tr>
                                <th>Koszty kolizji</th>
                                <td><?= number_format((float)($statsIncidents['cost'] ?? 0), 2, ',', ' ') ?> zł</td>
                            </tr>
                            <tr class="table-info">
                                <th>Zysk netto</th>
                                <td><strong><?= number_format((float)($statsOrders['net'] ?? 0) - (float)($statsServices['cost'] ?? 0) - (float)($statsIncidents['cost'] ?? 0), 2, ',', ' ') ?> zł</strong></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>