<?php
// /pages/api/product-rate.php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

try {
    require_once dirname(__DIR__, 2) . '/includes/db.php';
    require_once dirname(__DIR__) . '/includes/search.php'; // fetch_active_promotions, apply_promotions_to_product

    $pdo = db();

    $sku = isset($_GET['sku']) ? trim((string)$_GET['sku']) : '';
    $pickupStr  = isset($_GET['pickup_at']) ? trim((string)$_GET['pickup_at']) : '';
    $returnStr  = isset($_GET['return_at']) ? trim((string)$_GET['return_at']) : '';
    $pickup_location  = trim((string)($_GET['pickup_location']  ?? ''));
    $dropoff_location = trim((string)($_GET['dropoff_location'] ?? ''));

    if ($sku === '') {
        http_response_code(400);
        echo json_encode(['error' => 'Missing sku']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT * FROM products WHERE sku = :sku AND status='active' LIMIT 1");
    $stmt->execute([':sku' => $sku]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$product) {
        http_response_code(404);
        echo json_encode(['error' => 'Product not found']);
        exit;
    }

    $perDayBase = (float)$product['price'];
    $perDayFinal = $perDayBase;
    $promoApplied = false;
    $promoLabel = null;
    $rentalDays = 1;

    // Promocje tylko przy kompletnych datach
    if ($pickupStr !== '' && $returnStr !== '') {
        $pickup_ts  = strtotime($pickupStr) ?: null;
        $dropoff_ts = strtotime($returnStr) ?: null;
        if ($pickup_ts && $dropoff_ts && $dropoff_ts > $pickup_ts) {
            $diff_hours  = max(1, (int)ceil(($dropoff_ts - $pickup_ts) / 3600));
            $rentalDays = max(1, (int)ceil($diff_hours / 24));
        }

        $promos = fetch_active_promotions($pickup_ts, $dropoff_ts);
        [$final, $applied, $label] = apply_promotions_to_product(
            $product,
            $promos,
            $rentalDays,
            $pickup_location,
            $dropoff_location
        );
        if ($applied && $final < $perDayBase) {
            $perDayFinal = (float)$final;
            $promoApplied = true;
            $promoLabel = $label ?: null;
        }
    }

    echo json_encode([
        'sku' => $sku,
        'per_day_base' => (float)$perDayBase,
        'per_day_final' => (float)$perDayFinal,
        'promo_applied' => (bool)$promoApplied,
        'promo_label' => $promoLabel,
        'rental_days' => (int)$rentalDays,
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error', 'message' => $e->getMessage()]);
}
