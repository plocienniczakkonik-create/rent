<?php
// /pages/vehicle-order-form.php
require_once dirname(__DIR__) . '/auth/auth.php';
require_staff();
require_once dirname(__DIR__) . '/includes/db.php';
$db = db();

$vehicle_id = isset($_GET['vehicle_id']) ? (int)$_GET['vehicle_id'] : 0;
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$order = null;
if ($id) {
    $stmt = $db->prepare('SELECT * FROM orders WHERE id = ?');
    $stmt->execute([$id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
}

?>
<div class="container py-4">
    <h2><?= $id ? 'Edytuj zamówienie' : 'Dodaj zamówienie' ?></h2>
    <form method="post" action="<?= $BASE ?>/index.php?page=vehicle-order-save">
        <?php csrf_field(); ?>
        <input type="hidden" name="vehicle_id" value="<?= (int)$vehicle_id ?>">
        <?php if ($id): ?><input type="hidden" name="id" value="<?= (int)$id ?>"><?php endif; ?>
        <div class="mb-3">
            <label for="order_date" class="form-label">Data zamówienia</label>
            <input type="date" class="form-control" name="order_date" id="order_date" value="<?= htmlspecialchars($order['order_date'] ?? '') ?>">
        </div>
        <div class="mb-3">
            <label for="client_name" class="form-label">Klient</label>
            <input type="text" class="form-control" name="client_name" id="client_name" value="<?= htmlspecialchars($order['client_name'] ?? '') ?>">
        </div>
        <div class="mb-3">
            <label for="amount" class="form-label">Kwota</label>
            <input type="number" step="0.01" class="form-control" name="amount" id="amount" value="<?= htmlspecialchars($order['amount'] ?? '') ?>">
        </div>
        <button type="submit" class="btn btn-primary">Zapisz</button>
        <a href="<?= $BASE ?>/index.php?page=vehicle-detail&id=<?= (int)$vehicle_id ?>" class="btn btn-secondary">Anuluj</a>
    </form>
</div>