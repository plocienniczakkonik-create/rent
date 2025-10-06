<?php
require_once __DIR__ . '/../includes/_helpers.php';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/includes/search.php';
require_once __DIR__ . '/../auth/auth.php';

// Autoloader dla klas Fleet Management
function autoload_fleet_classes($className)
{
    $classFile = __DIR__ . '/../classes/' . $className . '.php';
    if (file_exists($classFile)) {
        require_once $classFile;
    }
}
spl_autoload_register('autoload_fleet_classes');

csrf_verify();

$BASE = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $BASE . '/index.php');
    exit;
}

// Helpery
function parse_dt(string $s): ?string
{
    $ts = strtotime($s);
    if (!$ts) return null;
    return date('Y-m-d H:i:s', $ts);
}

function diff_days(string $from, string $to): int
{
    $a = strtotime($from);
    $b = strtotime($to);
    if (!$a || !$b || $b <= $a) return 0;
    $hours = (int)ceil(($b - $a) / 3600);
    return max(1, (int)ceil($hours / 24));
}

$pdo = db();

// Zbierz i normalizuj dane z POST
$sku            = trim((string)($_POST['sku'] ?? ''));
$pickupAtRaw    = trim((string)($_POST['pickup_at'] ?? ''));
$returnAtRaw    = trim((string)($_POST['return_at'] ?? ''));
$pickupLocation = trim((string)($_POST['pickup_location'] ?? ''));
$dropLocation   = trim((string)($_POST['dropoff_location'] ?? ''));
$extrasIn       = (array)($_POST['extra'] ?? []);

$customerName   = trim((string)($_POST['customer_name'] ?? ''));
$customerEmail  = trim((string)($_POST['customer_email'] ?? ''));
$customerPhone  = trim((string)($_POST['customer_phone'] ?? ''));
$billingAddress = trim((string)($_POST['billing_address'] ?? ''));
$billingCity    = trim((string)($_POST['billing_city'] ?? ''));
$billingPostcode = trim((string)($_POST['billing_postcode'] ?? ''));
$billingCountry = trim((string)($_POST['billing_country'] ?? ''));
$paymentMethod  = trim((string)($_POST['payment_method'] ?? ''));

$errors = [];
if ($sku === '') $errors[] = 'Brak identyfikatora produktu.';
if ($pickupLocation === '' || $dropLocation === '') $errors[] = 'Wybierz miejsca odbioru i zwrotu.';
if ($customerName === '' || $customerEmail === '' || $customerPhone === '') $errors[] = 'Uzupełnij dane kontaktowe.';
if ($billingAddress === '' || $billingCity === '' || $billingCountry === '') $errors[] = 'Uzupełnij adres rozliczeniowy.';
if (!preg_match('/^\+[1-9]\d{1,14}$/', $customerPhone)) $errors[] = 'Numer telefonu musi zawierać kod kraju (np. +48123456789).';
if (!in_array($paymentMethod, ['online', 'card_on_pickup', 'cash_on_pickup'], true)) $errors[] = 'Nieprawidłowa forma płatności.';

$pickupAt = parse_dt($pickupAtRaw);
$returnAt = parse_dt($returnAtRaw);
if (!$pickupAt || !$returnAt || strtotime($returnAt) <= strtotime($pickupAt)) {
    $errors[] = 'Zakres dat jest nieprawidłowy.';
}

// Pobierz produkt
$stmt = $pdo->prepare('SELECT * FROM products WHERE sku = ? AND status = "active"');
$stmt->execute([$sku]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$product) {
    $errors[] = 'Produkt niedostępny.';
}

// Wczytaj słownik dodatków i odfiltruj wybrane
$addonsDict = [];
try {
    $q = $pdo->prepare("SELECT name, price, charge_type FROM dict_terms WHERE status='active' AND dict_type_id = (SELECT id FROM dict_types WHERE slug='addon' LIMIT 1)");
    $q->execute();
    foreach ($q->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $addonsDict[(string)$row['name']] = [
            'name' => (string)$row['name'],
            'price' => (float)($row['price'] ?? 0),
            'charge_type' => (string)($row['charge_type'] ?? 'once'),
        ];
    }
} catch (Throwable $e) {
    // brak słownika dodatków nie jest krytyczny — po prostu brak pozycji
}

// Re-komputacja ceny po stronie serwera (ochrona przed manipulacją)
$days = ($pickupAt && $returnAt) ? diff_days($pickupAt, $returnAt) : 0;
if ($days <= 0) $errors[] = 'Zakres dat jest nieprawidłowy.';

$perDayBase = (float)($product['price'] ?? 0);
$promos = ($pickupAt && $returnAt) ? fetch_active_promotions(strtotime($pickupAt), strtotime($returnAt)) : [];
[$perDayFinal, $promoApplied, $promoLabel] = apply_promotions_to_product(
    $product,
    $promos,
    $days,
    $pickupLocation,
    $dropLocation
);

// Wybrane dodatki
$addonRows = [];
foreach ($extrasIn as $name) {
    $name = (string)$name;
    if (isset($addonsDict[$name])) {
        $addonRows[] = $addonsDict[$name];
    }
}

$addonsTotal = 0.0;
foreach ($addonRows as $row) {
    $addonsTotal += ($row['charge_type'] === 'per_day') ? ($row['price'] * $days) : $row['price'];
}

$baseTotal  = $perDayBase  * $days + $addonsTotal;
$finalTotal = $perDayFinal * $days + $addonsTotal;

// --- FLEET MANAGEMENT: Obliczanie kaucji, opłat i wybór pojazdu ---
$depositAmount = 0.0;
$depositType = null;
$locationFee = 0.0;
$selectedVehicleId = null;
$pickupLocationId = null;
$dropoffLocationId = null;
$fleetEnabled = false;

try {
    $depositManager = new DepositManager($pdo);
    $locationFeeManager = new LocationFeeManager($pdo);
    $fleetManager = new FleetManager($pdo);

    $fleetEnabled = $fleetManager->isEnabled();

    if ($fleetEnabled) {
        // 1. Znajdź ID lokalizacji (używamy ujednoliconych nazw z dict_terms)
        $locations = $fleetManager->getActiveLocations();
        foreach ($locations as $location) {
            if ($location['name'] === $pickupLocation) {
                $pickupLocationId = $location['id'];
            }
            if ($location['name'] === $dropLocation) {
                $dropoffLocationId = $location['id'];
            }
        }

        // 2. Wybierz dostępny pojazd dla tego produktu w lokalizacji odbioru
        if ($pickupLocationId) {
            $availableVehicles = $fleetManager->getAvailableVehiclesInLocation($pickupLocationId, $product['sku']);
            if (!empty($availableVehicles)) {
                $selectedVehicleId = $availableVehicles[0]['id']; // Wybierz pierwszy dostępny
            }
        }

        // 3. Oblicz kaucję
        if ($product['deposit_enabled']) {
            $depositType = $product['deposit_type'] ?? 'fixed';
            $depositAmount = $depositManager->calculateDeposit(
                $product['id'],
                $finalTotal,
                $depositType,
                $product['deposit_amount'] ?? 0
            );
        }

        // 4. Oblicz opłatę lokalizacyjną
        if ($pickupLocationId && $dropoffLocationId && $pickupLocationId !== $dropoffLocationId) {
            $locationFeeResult = $locationFeeManager->calculateLocationFee($pickupLocationId, $dropoffLocationId);
            $locationFee = $locationFeeResult['amount'] ?? 0;
        }
    }
} catch (Exception $e) {
    // Błąd Fleet Management - kontynuuj bez niego
    $depositAmount = 0.0;
    $locationFee = 0.0;
}

// Przelicz sumy z Fleet Management
$totalWithFees = $finalTotal + $locationFee;
$totalWithDeposit = $totalWithFees + $depositAmount;

// Walidacja dostępności: sprawdź ile rezerwacji nachodzi na zakres vs. stock produktu
if (empty($errors)) {
    try {
        // rezerwacje aktywne to pending/confirmed
        $sql = "SELECT COUNT(*) AS cnt FROM reservations
        WHERE sku = :sku AND status IN ('pending','confirmed')
          AND pickup_at < :ret AND return_at > :pick";
        $st = $pdo->prepare($sql);
        $st->execute([
            ':sku'  => $sku,
            ':pick' => $pickupAt,
            ':ret'  => $returnAt,
        ]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        $overlaps = (int)($row['cnt'] ?? 0);
        $stock = (int)($product['stock'] ?? 0);
        if ($stock <= 0 || $overlaps >= $stock) {
            $errors[] = 'Brak dostępności na wybrany termin. Prosimy wybrać inne daty.';
        }
    } catch (Throwable $e) {
        $errors[] = 'Błąd podczas sprawdzania dostępności (czy uruchomiono migracje SQL?).';
    }
}

// Zapis do DB
$reservationId = null;
if (empty($errors)) {
    try {
        $pdo->beginTransaction();

        $ins = $pdo->prepare("INSERT INTO reservations
      (sku, product_name, pickup_location, dropoff_location, pickup_at, return_at, rental_days,
       price_per_day_base, price_per_day_final, addons_total, total_gross, promo_applied, promo_label,
       customer_name, customer_email, customer_phone, billing_address, billing_city, billing_postcode, billing_country, payment_method, status,
       vehicle_id, pickup_location_id, dropoff_location_id, deposit_amount, deposit_type, location_fee, total_with_deposit)
       VALUES
      (:sku, :pname, :pick_loc, :drop_loc, :pick_at, :ret_at, :days,
       :ppb, :ppf, :addons, :total, :promo_applied, :promo_label,
       :cname, :cemail, :cphone, :billing_address, :billing_city, :billing_postcode, :billing_country, :pmethod, 'pending',
       :vehicle_id, :pickup_location_id, :dropoff_location_id, :deposit_amount, :deposit_type, :location_fee, :total_with_deposit)");

        $ins->execute([
            ':sku'   => $sku,
            ':pname' => (string)$product['name'],
            ':pick_loc' => $pickupLocation,
            ':drop_loc' => $dropLocation,
            ':pick_at'  => $pickupAt,
            ':ret_at'   => $returnAt,
            ':days'     => $days,
            ':ppb'      => $perDayBase,
            ':ppf'      => $perDayFinal,
            ':addons'   => $addonsTotal,
            ':total'    => $totalWithFees, // Używamy sumy z opłatami lokalizacyjnymi
            ':promo_applied' => $promoApplied ? 1 : 0,
            ':promo_label'   => $promoLabel ?: null,
            ':cname'   => $customerName,
            ':cemail'  => $customerEmail,
            ':cphone'  => $customerPhone,
            ':billing_address' => $billingAddress,
            ':billing_city' => $billingCity,
            ':billing_postcode' => $billingPostcode,
            ':billing_country' => $billingCountry,
            ':pmethod' => $paymentMethod,
            // Fleet Management parametry
            ':vehicle_id' => $selectedVehicleId,
            ':pickup_location_id' => $pickupLocationId,
            ':dropoff_location_id' => $dropoffLocationId,
            ':deposit_amount' => $depositAmount,
            ':deposit_type' => $depositType,
            ':location_fee' => $locationFee,
            ':total_with_deposit' => $totalWithDeposit,
        ]);

        $reservationId = (int)$pdo->lastInsertId();

        if ($reservationId && $addonRows) {
            $insAd = $pdo->prepare("INSERT INTO reservation_addons
        (reservation_id, name, charge_type, unit_price, quantity, line_total)
         VALUES (:rid, :name, :ctype, :price, :qty, :line)");
            foreach ($addonRows as $row) {
                $qty = ($row['charge_type'] === 'per_day') ? $days : 1;
                $line = ($row['charge_type'] === 'per_day') ? $row['price'] * $days : $row['price'];
                $insAd->execute([
                    ':rid'   => $reservationId,
                    ':name'  => $row['name'],
                    ':ctype' => $row['charge_type'],
                    ':price' => $row['price'],
                    ':qty'   => $qty,
                    ':line'  => $line,
                ]);
            }
        }

        // Update vehicle status to 'booked' if Fleet Management is active
        if ($selectedVehicleId) {
            $updateVehicleStmt = $pdo->prepare("UPDATE vehicles SET status = 'booked' WHERE id = :vehicle_id");
            $updateVehicleStmt->execute([':vehicle_id' => $selectedVehicleId]);
        }

        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $errors[] = 'Nie udało się zapisać rezerwacji. Spróbuj ponownie.';
    }
}

// Widok odpowiedzi
?>
<div class="container py-4">
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <h5 class="mb-2">Nie można sfinalizować rezerwacji</h5>
            <ul class="mb-0">
                <?php foreach ($errors as $e): ?>
                    <li><?= htmlspecialchars($e) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php
        // Zbuduj link powrotny na kartę produktu z zachowaniem parametrów
        $qs = http_build_query([
            'sku' => $sku,
            'pickup_location'  => $pickupLocation,
            'dropoff_location' => $dropLocation,
            'pickup_at' => $pickupAtRaw,
            'return_at' => $returnAtRaw,
        ]);
        ?>
        <a href="<?= $BASE ?>/index.php?page=reserve&<?= htmlspecialchars($qs) ?>" class="btn btn-theme btn-secondary">Wróć i zmień terminy</a>
        <a href="<?= $BASE ?>/index.php" class="btn btn-theme btn-light">Strona główna</a>
    <?php else: ?>
        <div class="alert alert-success">
            <h5 class="mb-2">Rezerwacja złożona pomyślnie</h5>
            <p class="mb-0">Numer rezerwacji: <strong>#<?= (int)$reservationId ?></strong>. Wkrótce się skontaktujemy.</p>
        </div>
        <div class="card p-3 mb-3">
            <h6>Szczegóły</h6>
            <ul class="list-unstyled mb-0">
                <li><strong>Pojazd:</strong> <?= htmlspecialchars((string)$product['name']) ?> (SKU: <?= htmlspecialchars($sku) ?>)</li>
                <?php if ($selectedVehicleId): ?>
                    <li><strong>Numer pojazdu:</strong> #<?= (int)$selectedVehicleId ?></li>
                <?php endif; ?>
                <li><strong>Odbiór:</strong> <?= htmlspecialchars($pickupLocation) ?>, <?= htmlspecialchars($pickupAt) ?></li>
                <li><strong>Zwrot:</strong> <?= htmlspecialchars($dropLocation) ?>, <?= htmlspecialchars($returnAt) ?></li>
                <li><strong>Liczba dni:</strong> <?= (int)$days ?></li>
                <?php if ($locationFee > 0): ?>
                    <li><strong>Opłata za trasę:</strong> <?= number_format($locationFee, 2, ',', ' ') ?> PLN</li>
                <?php endif; ?>
                <?php if ($depositAmount > 0): ?>
                    <li><strong>Kaucja (<?= htmlspecialchars($depositType) ?>):</strong> <?= number_format($depositAmount, 2, ',', ' ') ?> PLN</li>
                <?php endif; ?>
                <li><strong>Suma:</strong> <?= number_format($finalTotal, 2, ',', ' ') ?> PLN</li>
                <?php if ($totalWithDeposit > $finalTotal): ?>
                    <li><strong>Do zapłaty (z kaucją):</strong> <?= number_format($totalWithDeposit, 2, ',', ' ') ?> PLN</li>
                <?php endif; ?>
            </ul>
        </div>
        <div class="card p-3 mb-3">
            <h6>Dane klienta</h6>
            <ul class="list-unstyled mb-0">
                <li><strong>Imię i nazwisko:</strong> <?= htmlspecialchars($customerName) ?></li>
                <li><strong>E-mail:</strong> <?= htmlspecialchars($customerEmail) ?></li>
                <li><strong>Telefon:</strong> <?= htmlspecialchars($customerPhone) ?></li>
                <li><strong>Adres:</strong> <?= htmlspecialchars($billingAddress) ?></li>
                <li><strong>Miasto:</strong> <?= htmlspecialchars($billingCity) ?>
                    <?php if ($billingPostcode): ?>
                        <?= htmlspecialchars($billingPostcode) ?>
                    <?php endif; ?>
                </li>
                <li><strong>Kraj:</strong> <?= htmlspecialchars($billingCountry) ?></li>
                <li><strong>Płatność:</strong> <?= htmlspecialchars($paymentMethod) ?></li>
            </ul>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= $BASE ?>/index.php?page=reservation-details&id=<?= (int)$reservationId ?>" class="btn btn-theme btn-primary">Szczegóły rezerwacji</a>
            <a href="<?= $BASE ?>/index.php" class="btn btn-primary">Zakończ</a>
        </div>
    <?php endif; ?>
</div>