<?php
// Live list of reservations with quick filters
// NOTE: _helpers.php is already included in parent dashboard-staff.php
// require_once dirname(__DIR__, 2) . '/includes/_helpers.php'; // REMOVED - causing conflicts
require_once dirname(__DIR__, 2) . '/includes/db.php';
$db = db();

$status = $_GET['status'] ?? '';
$q = trim((string)($_GET['q'] ?? ''));
// Pagination
$perPage = 20;
$p = isset($_GET['p']) ? max(1, (int)$_GET['p']) : 1;
$offset = ($p - 1) * $perPage;

$sql = "SELECT id, sku, product_name, pickup_location, dropoff_location, pickup_at, return_at,
                             rental_days, total_gross, status, created_at
                FROM reservations WHERE 1=1";
$bind = [];
if ($status !== '') {
    $sql .= " AND status = :st";
    $bind[':st'] = $status;
}
if ($q !== '') {
    $sql .= " AND (product_name LIKE :q OR sku LIKE :q OR customer_name LIKE :q OR customer_email LIKE :q)";
    $bind[':q'] = '%' . $q . '%';
}
$sqlCount = "SELECT COUNT(*) FROM reservations WHERE 1=1";
$bindCount = $bind;
if (strpos($sql, 'status = :st') !== false) {
    $sqlCount .= " AND status = :st";
}
if (strpos($sql, 'LIKE :q') !== false) {
    $sqlCount .= " AND (product_name LIKE :q OR sku LIKE :q OR customer_name LIKE :q OR customer_email LIKE :q)";
}

// Sortowanie zamówień
$orderSort = '';
if ($section === 'orders' && !empty($sort)) {
    $orderSort = match ($sort) {
        'id' => "ORDER BY id $dir",
        'created' => "ORDER BY created_at $dir",
        'product' => "ORDER BY product_name $dir",
        'days' => "ORDER BY rental_days $dir",
        'total' => "ORDER BY total_gross $dir",
        'status' => "ORDER BY status $dir",
        default => "ORDER BY id DESC"
    };
} else {
    $orderSort = "ORDER BY id DESC";
}

$sql .= " $orderSort LIMIT :limit OFFSET :offset";

$stmt = $db->prepare($sql);
foreach ($bind as $k => $v) {
    $stmt->bindValue($k, $v);
}
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmtC = $db->prepare($sqlCount);
foreach ($bindCount as $k => $v) {
    $stmtC->bindValue($k, $v);
}
$stmtC->execute();
$total = (int)$stmtC->fetchColumn();
$pages = max(1, (int)ceil($total / $perPage));
?>
<div class="card section-orders">
    <div class="card-header">
        <h2 class="h6 mb-0">Zamówienia</h2>
    </div>
    <div class="card-body">
        <form method="get" class="d-flex gap-2 align-items-center mb-3">
            <input type="text" class="form-control form-control-sm" name="q" placeholder="Szukaj (nazwa/sku/email)" value="<?= e($q) ?>">
            <select name="status" class="form-select form-select-sm">
                <option value="">Wszystkie statusy</option>
                <?php foreach (["pending", "confirmed", "cancelled"] as $s): ?>
                    <option value="<?= e($s) ?>" <?= $status === $s ? 'selected' : '' ?>><?= e($s) ?></option>
                <?php endforeach; ?>
            </select>
            <button class="btn btn-sm btn-outline-primary" type="submit">Filtruj</button>
        </form>
        
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead class="small text-muted">
                    <tr>
                        <th><?= sort_link_dashboard('orders', 'id', 'ID') ?></th>
                        <th><?= sort_link_dashboard('orders', 'created', 'Dodano') ?></th>
                        <th><?= sort_link_dashboard('orders', 'product', 'Produkt') ?></th>
                        <th>Terminy</th>
                        <th><?= sort_link_dashboard('orders', 'days', 'Dni') ?></th>
                        <th class="text-end"><?= sort_link_dashboard('orders', 'total', 'Suma') ?></th>
                        <th><?= sort_link_dashboard('orders', 'status', 'Status') ?></th>
                        <th class="text-end">Akcje</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$rows): ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">Brak rezerwacji.</td>
                        </tr>
                        <?php else: foreach ($rows as $r): ?>
                            <tr>
                                <td>#<?= (int)$r['id'] ?></td>
                                <td><?= e((string)$r['created_at']) ?></td>
                                <td>
                                    <div class="fw-semibold"><?= e((string)$r['product_name']) ?></div>
                                    <div class="text-muted small">SKU: <?= e((string)$r['sku']) ?></div>
                                </td>
                                <td>
                                    <div><span class="text-muted small">Odbiór:</span> <?= e((string)$r['pickup_location']) ?>, <?= e((string)$r['pickup_at']) ?></div>
                                    <div><span class="text-muted small">Zwrot:</span> <?= e((string)$r['dropoff_location']) ?>, <?= e((string)$r['return_at']) ?></div>
                                </td>
                                <td><?= (int)$r['rental_days'] ?></td>
                                <td class="text-end"><?= number_format((float)$r['total_gross'], 2, ',', ' ') ?> PLN</td>
                                <td>
                                    <form method="post" action="index.php?page=reservation-status-save" class="d-flex gap-1 align-items-center">
                                        <?php if (function_exists('csrf_field')) csrf_field(); ?>
                                        <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                        <input type="hidden" name="q" value="<?= e($q) ?>">
                                        <input type="hidden" name="filter_status" value="<?= e($status) ?>">
                                        <input type="hidden" name="p" value="<?= (int)$p ?>">
                                        <select name="status" class="form-select form-select-sm" style="width:auto">
                                            <?php foreach (["pending", "confirmed", "cancelled"] as $s): ?>
                                                <option value="<?= e($s) ?>" <?= ((string)$r['status'] === $s) ? 'selected' : '' ?>><?= e($s) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button class="btn btn-sm btn-outline-success" type="submit">Zmień</button>
                                    </form>
                                </td>
                                <td class="text-end">
                                    <a href="index.php?page=reservation-details&id=<?= (int)$r['id'] ?>" class="btn btn-outline-primary btn-sm">Szczegóły</a>
                                </td>
                            </tr>
                    <?php endforeach;
                    endif; ?>
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-between align-items-center p-2 border-top small text-muted">
            <div>
                Razem: <?= (int)$total ?> • Strona <?= (int)$p ?> / <?= (int)$pages ?>
            </div>
            <div class="d-flex gap-2">
                <?php
                $baseUrl = (isset($BASE) ? ($BASE . '/index.php#pane-orders') : 'index.php#pane-orders');
                $qs = function ($pageNum) use ($status, $q) {
                    return http_build_query(['page' => 'dashboard-staff', 'status' => $status, 'q' => $q, 'p' => $pageNum]);
                };
                ?>
                <a class="btn btn-sm btn-outline-secondary <?= $p <= 1 ? 'disabled' : '' ?>" href="<?= $baseUrl ?>?<?= htmlspecialchars($qs(max(1, $p - 1))) ?>">« Poprzednia</a>
                <a class="btn btn-sm btn-outline-secondary <?= $p >= $pages ? 'disabled' : '' ?>" href="<?= $baseUrl ?>?<?= htmlspecialchars($qs(min($pages, $p + 1))) ?>">Następna »</a>
            </div>
        </div>
    </div>
</div>