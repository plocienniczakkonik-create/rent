<?php
require_once __DIR__ . '/../includes/_helpers.php';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/includes/search.php';

// We expect POST from product-details reservation form
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . (defined('BASE_URL') ? BASE_URL : '') . '/index.php');
    exit;
}

$BASE = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';

// Gather inputs
$sku            = trim((string)($_POST['sku'] ?? ''));
$pickupAt       = trim((string)($_POST['pickup_at'] ?? ''));
$returnAt       = trim((string)($_POST['return_at'] ?? ''));
$pickupLocation = trim((string)($_POST['pickup_location'] ?? ''));
$dropLocation   = trim((string)($_POST['dropoff_location'] ?? ''));
$selectedExtras = (array)($_POST['extra'] ?? []); // by name

// Load product
$pdo = db();
$stmt = $pdo->prepare('SELECT * FROM products WHERE sku = ? AND status = "active"');
$stmt->execute([$sku]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$product) {
    header('Location: ' . $BASE . '/index.php');
    exit;
}

// Load addons dictionary (name, price, type)
$addons = [];
try {
    $q = $pdo->prepare("SELECT name, price, charge_type FROM dict_terms WHERE status='active' AND dict_type_id = (SELECT id FROM dict_types WHERE slug='addon' LIMIT 1)");
    $q->execute();
    $addons = $q->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Throwable $e) {
    $addons = [];
}

// Compute days and apply promotions (reuse helpers)
$pickupTs  = strtotime($pickupAt) ?: null;
$returnTs  = strtotime($returnAt) ?: null;
$days = 1;
if ($pickupTs && $returnTs && $returnTs > $pickupTs) {
    $diffH = max(1, (int)ceil(($returnTs - $pickupTs) / 3600));
    $days  = max(1, (int)ceil($diffH / 24));
}

$promos = ($pickupTs && $returnTs) ? fetch_active_promotions($pickupTs, $returnTs) : [];
[$perDayFinal, $promoApplied, $promoLabel] = apply_promotions_to_product(
    $product,
    $promos,
    $days,
    $pickupLocation,
    $dropLocation
);
$perDayBase = (float)$product['price'];

// Map selected addons -> rows with price and charge_type
$addonRows = [];
foreach ($addons as $a) {
    if (in_array((string)$a['name'], $selectedExtras, true)) {
        $addonRows[] = [
            'name' => (string)$a['name'],
            'price' => (float)($a['price'] ?? 0),
            'charge_type' => (string)($a['charge_type'] ?? 'once'),
        ];
    }
}

// Calculate totals
$addonsTotal = 0.0;
foreach ($addonRows as $row) {
    if ($row['charge_type'] === 'per_day') {
        $addonsTotal += $row['price'] * $days;
    } else {
        $addonsTotal += $row['price'];
    }
}
$baseTotal  = $perDayBase  * $days + $addonsTotal;
$finalTotal = $perDayFinal * $days + $addonsTotal;

// Basic formatting helper
$fmt = function ($n) {
    return number_format((float)$n, 2, ',', ' ');
};

?>
<div class="container py-4">
    <h2 class="mb-4">Podsumowanie rezerwacji</h2>

    <div class="row g-4">
        <div class="col-12 col-lg-5">
            <div class="card p-3 mb-3">
                <h5 class="mb-3">Dane klienta</h5>
                <div class="mb-2">
                    <label class="form-label">Imię i nazwisko</label>
                    <input type="text" class="form-control" name="customer_name" required form="checkout-form">
                </div>
                <div class="mb-2">
                    <label class="form-label">E-mail</label>
                    <input type="email" class="form-control" name="customer_email" required form="checkout-form">
                </div>
                <div class="mb-2">
                    <label class="form-label">Telefon</label>
                    <input type="tel" class="form-control" name="customer_phone" required form="checkout-form">
                </div>
                <div class="mb-3">
                    <label class="form-label">Forma płatności</label>
                    <select class="form-select" name="payment_method" required form="checkout-form">
                        <option value="" disabled selected>Wybierz...</option>
                        <option value="online">Płatność online</option>
                        <option value="card_on_pickup">Karta przy odbiorze</option>
                        <option value="cash_on_pickup">Gotówka przy odbiorze</option>
                    </select>
                </div>

                <div class="alert alert-info">
                    Finalizacja rezerwacji nastąpi w kolejnym kroku. Tu możesz jeszcze wrócić i zmienić dane przed potwierdzeniem.
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-7">
            <div class="card p-3 mb-3">
                <h5 class="mb-3">Szczegóły</h5>
                <ul class="list-unstyled mb-0">
                    <li><strong>Pojazd:</strong> <?= htmlspecialchars($product['name']) ?> (SKU: <?= htmlspecialchars($product['sku']) ?>)</li>
                    <li><strong>Odbiór:</strong> <?= htmlspecialchars($pickupLocation) ?>, <?= htmlspecialchars($pickupAt) ?></li>
                    <li><strong>Zwrot:</strong> <?= htmlspecialchars($dropLocation) ?>, <?= htmlspecialchars($returnAt) ?></li>
                    <li><strong>Liczba dni:</strong> <?= (int)$days ?></li>
                </ul>
            </div>

            <div class="card p-3 mb-3">
                <h5 class="mb-3">Rozliczenie</h5>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <tbody>
                            <tr>
                                <td>Cena bazowa za dzień</td>
                                <td class="text-end"><?= $fmt($perDayBase) ?> PLN</td>
                            </tr>
                            <?php if ($promoApplied && $perDayFinal < $perDayBase): ?>
                                <tr>
                                    <td>Cena promocyjna za dzień <?= $promoLabel ? '(' . htmlspecialchars($promoLabel) . ')' : '' ?></td>
                                    <td class="text-end text-danger"><?= $fmt($perDayFinal) ?> PLN</td>
                                </tr>
                            <?php endif; ?>
                            <tr>
                                <td>Ilość dni</td>
                                <td class="text-end"><?= (int)$days ?></td>
                            </tr>
                            <?php if ($addonRows): ?>
                                <tr>
                                    <td colspan="2"><strong>Dodatkowe usługi</strong></td>
                                </tr>
                                <?php foreach ($addonRows as $row): ?>
                                    <tr>
                                        <td>
                                            <?= htmlspecialchars($row['name']) ?>
                                            <span class="text-muted small">
                                                (<?= $row['charge_type'] === 'per_day' ? 'za dzień' : 'jednorazowo' ?>)
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <?php if ($row['charge_type'] === 'per_day'): ?>
                                                <?= $fmt($row['price']) ?> × <?= (int)$days ?> = <strong><?= $fmt($row['price'] * $days) ?></strong> PLN
                                            <?php else: ?>
                                                <strong><?= $fmt($row['price']) ?></strong> PLN
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <tr class="border-top">
                                    <td colspan="2" class="pt-3"></td>
                                </tr>
                            <?php endif; ?>
                            <tr>
                                <td><strong>Łącznie za wynajem</strong></td>
                                <td class="text-end">
                                    <strong><?= $fmt($perDayFinal) ?> × <?= (int)$days ?> = <?= $fmt($perDayFinal * $days) ?> PLN</strong>
                                </td>
                            </tr>
                            <?php if ($addonRows): ?>
                                <tr>
                                    <td><strong>Suma dodatkowych usług</strong></td>
                                    <td class="text-end"><strong><?= $fmt($addonsTotal) ?> PLN</strong></td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th>Suma</th>
                                <th class="text-end"><?= $fmt($finalTotal) ?> PLN</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                
                <form id="checkout-form" method="post" action="<?= $BASE ?>/index.php?page=checkout-confirm" class="mt-3">
                    <?php if (function_exists('csrf_field')) csrf_field(); ?>
                    <input type="hidden" name="sku" value="<?= htmlspecialchars($product['sku']) ?>">
                    <input type="hidden" name="pickup_at" value="<?= htmlspecialchars($pickupAt) ?>">
                    <input type="hidden" name="return_at" value="<?= htmlspecialchars($returnAt) ?>">
                    <input type="hidden" name="pickup_location" value="<?= htmlspecialchars($pickupLocation) ?>">
                    <input type="hidden" name="dropoff_location" value="<?= htmlspecialchars($dropLocation) ?>">
                    <?php foreach ($selectedExtras as $ex): ?>
                        <input type="hidden" name="extra[]" value="<?= htmlspecialchars($ex) ?>">
                    <?php endforeach; ?>
                    
                    <button type="submit" class="btn btn-success w-100">Przejdź do potwierdzenia</button>
                </form>
            </div>
        </div>
    </div>
</div>