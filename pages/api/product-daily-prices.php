<?php
// /pages/api/product-daily-prices.php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

try {
    require_once dirname(__DIR__, 2) . '/includes/db.php';
    require_once dirname(__DIR__) . '/includes/search.php'; // fetch_active_promotions, apply_promotions_to_product

    $pdo = db();

    $sku = isset($_GET['sku']) ? trim((string)$_GET['sku']) : '';
    $startStr = isset($_GET['start']) ? trim((string)$_GET['start']) : '';
    $endStr   = isset($_GET['end'])   ? trim((string)$_GET['end'])   : '';
    $pickup_location  = trim((string)($_GET['pickup_location']  ?? ''));
    $dropoff_location = trim((string)($_GET['dropoff_location'] ?? ''));

    if ($sku === '' || $startStr === '' || $endStr === '') {
        http_response_code(400);
        echo json_encode(['error' => 'Missing sku/start/end']);
        exit;
    }

    // Walidacja i ograniczenie zakresu
    $startTs = strtotime($startStr);
    $endTs   = strtotime($endStr);
    if (!$startTs || !$endTs || $endTs < $startTs) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid date range']);
        exit;
    }
    $maxSpanDays = 62; // ograniczamy do ~2 miesięcy
    if (($endTs - $startTs) / 86400 > $maxSpanDays) {
        $endTs = $startTs + $maxSpanDays * 86400;
    }

    // Pobierz produkt po SKU
    $stmt = $pdo->prepare("SELECT * FROM products WHERE sku = :sku AND status='active' LIMIT 1");
    $stmt->execute([':sku' => $sku]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$product) {
        http_response_code(404);
        echo json_encode(['error' => 'Product not found']);
        exit;
    }

    // Iteracja po dniach i wyliczenie ceny finalnej z promocjami dla każdego dnia
    $prices = [];
    $curTs = strtotime(date('Y-m-d 00:00:00', $startTs));
    $endDayTs = strtotime(date('Y-m-d 00:00:00', $endTs));

    while ($curTs <= $endDayTs) {
        $dayStart = $curTs;
        $dayEnd   = $curTs + 86399; // koniec dnia

        // Pobierz promocje aktywne tego dnia
        $promos = fetch_active_promotions($dayStart, $dayEnd);
        // Zakładamy podgląd 1-dniowy (min_days > 1 zazwyczaj nie powinien aktywować podglądu dziennego)
        [$final, $applied, $label] = apply_promotions_to_product(
            $product,
            $promos,
            1, // rental_days = 1 na potrzeby podglądu
            $pickup_location,
            $dropoff_location
        );

        $dateKey = date('Y-m-d', $curTs);
        $prices[$dateKey] = [
            'base'   => (float)$product['price'],
            'final'  => (float)$final,
            'promo'  => (bool)$applied,
            'label'  => $label,
        ];

        $curTs += 86400;
    }

    echo json_encode([
        'sku' => $sku,
        'start' => date('Y-m-d', $startTs),
        'end' => date('Y-m-d', $endTs),
        'prices' => $prices,
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error', 'message' => $e->getMessage()]);
}
