<?php
require_once __DIR__ . '/../includes/_helpers.php';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/includes/search.php';

// Autoloader dla klas Fleet Management
function autoload_fleet_classes($className)
{
    $classFile = __DIR__ . '/../classes/' . $className . '.php';
    if (file_exists($classFile)) {
        require_once $classFile;
    }
}
spl_autoload_register('autoload_fleet_classes');

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

// --- FLEET MANAGEMENT: Obliczanie kaucji i opłat lokalizacyjnych ---
$depositAmount = 0.0;
$depositType = null;
$locationFee = 0.0;
$fleetEnabled = false;

try {
    $pdo = db();
    $depositManager = new DepositManager($pdo);
    $locationFeeManager = new LocationFeeManager($pdo);
    $fleetManager = new FleetManager($pdo);

    $fleetEnabled = $fleetManager->isEnabled();

    if ($fleetEnabled) {
        // 1. Oblicz kaucję na podstawie ustawień produktu
        if ($product['deposit_enabled']) {
            $depositType = $product['deposit_type'] ?? 'fixed';
            $depositAmount = $depositManager->calculateDeposit(
                $product['id'],
                $finalTotal, // Suma wynajmu bez kaucji
                $depositType,
                $product['deposit_amount'] ?? 0
            );
        }

        // 2. Oblicz opłatę lokalizacyjną (jeśli lokalizacje są różne)
        if ($pickupLocation !== $dropLocation) {
            // Znajdź ID lokalizacji
            $locations = $fleetManager->getActiveLocations();
            $pickupLocationId = null;
            $dropoffLocationId = null;

            foreach ($locations as $location) {
                $displayName = $location['name'] . ' (' . $location['city'] . ')';
                if ($displayName === $pickupLocation || $location['name'] === $pickupLocation || $location['city'] === $pickupLocation) {
                    $pickupLocationId = $location['id'];
                }
                if ($displayName === $dropLocation || $location['name'] === $dropLocation || $location['city'] === $dropLocation) {
                    $dropoffLocationId = $location['id'];
                }
            }

            if ($pickupLocationId && $dropoffLocationId && $pickupLocationId !== $dropoffLocationId) {
                $feeData = $locationFeeManager->calculateLocationFee($pickupLocationId, $dropoffLocationId);
                $locationFee = $feeData['amount'] ?? 0.0;
            }
        }
    }
} catch (Exception $e) {
    // W przypadku błędu, kontynuuj bez Fleet Management
    $depositAmount = 0.0;
    $locationFee = 0.0;
}

// Aktualizuj sumy z uwzględnieniem Fleet Management
$finalTotal += $locationFee;
$totalWithDeposit = $finalTotal + $depositAmount;

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
                    <label class="form-label">Telefon <small class="text-muted">(z kodem kraju, np. +48)</small></label>
                    <input type="tel" class="form-control" name="customer_phone" required form="checkout-form"
                        placeholder="+48 123 456 789" pattern="^\+[1-9]\d{1,14}$" title="Numer telefonu z kodem kraju (np. +48123456789)">
                </div>
                <div class="mb-2">
                    <label class="form-label">Adres</label>
                    <input type="text" class="form-control" name="billing_address" required form="checkout-form"
                        placeholder="ul. Przykładowa 123">
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-2">
                            <label class="form-label">Miasto</label>
                            <input type="text" class="form-control" name="billing_city" required form="checkout-form">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-2">
                            <label class="form-label">Kod pocztowy</label>
                            <input type="text" class="form-control" name="billing_postcode" required form="checkout-form"
                                placeholder="00-000">
                        </div>
                    </div>
                </div>
                <div class="mb-2">
                    <label class="form-label">Kraj</label>
                    <select class="form-select" name="billing_country" required form="checkout-form">
                        <option value="" disabled selected>Wybierz kraj...</option>
                        <option value="PL" selected>Polska</option>
                        <option value="DE">Niemcy</option>
                        <option value="CZ">Czechy</option>
                        <option value="SK">Słowacja</option>
                        <option value="UA">Ukraina</option>
                        <option value="LT">Litwa</option>
                        <option value="LV">Łotwa</option>
                        <option value="EE">Estonia</option>
                        <option value="AT">Austria</option>
                        <option value="HU">Węgry</option>
                        <option value="RO">Rumunia</option>
                        <option value="BG">Bułgaria</option>
                        <option value="HR">Chorwacja</option>
                        <option value="SI">Słowenia</option>
                        <option value="Other">Inny</option>
                    </select>
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

                            <?php if ($fleetEnabled && ($locationFee > 0 || $depositAmount > 0)): ?>
                                <tr class="border-top">
                                    <td colspan="2" class="pt-3"><small class="text-muted">Fleet Management</small></td>
                                </tr>
                                <?php if ($locationFee > 0): ?>
                                    <tr>
                                        <td>Opłata międzymiastowa</td>
                                        <td class="text-end"><?= $fmt($locationFee) ?> PLN</td>
                                    </tr>
                                <?php endif; ?>
                                <?php if ($depositAmount > 0): ?>
                                    <tr>
                                        <td>
                                            Kaucja
                                            <small class="text-muted">(<?= $depositType === 'percentage' ? 'procent' : 'stała kwota' ?>)</small>
                                        </td>
                                        <td class="text-end"><?= $fmt($depositAmount) ?> PLN</td>
                                    </tr>
                                <?php endif; ?>
                            <?php endif; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th>Suma do zapłaty</th>
                                <th class="text-end"><?= $fmt($finalTotal) ?> PLN</th>
                            </tr>
                            <?php if ($fleetEnabled && $depositAmount > 0): ?>
                                <tr>
                                    <th>Łącznie z kaucją</th>
                                    <th class="text-end"><?= $fmt($totalWithDeposit) ?> PLN</th>
                                </tr>
                            <?php endif; ?>
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