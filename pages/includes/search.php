<?php
// /pages/includes/search.php
declare(strict_types=1);

// z /pages/includes/ cofamy się dwa poziomy do /includes/db.php
require_once dirname(__DIR__, 2) . '/includes/db.php';

// Autoloader dla klas Fleet Management
// Autoloader dla klas Fleet Management (ładuje z katalogu /classes)
if (!function_exists('autoload_fleet_classes')) {
    function autoload_fleet_classes($className)
    {
        $classFile = dirname(__DIR__, 2) . '/classes/' . $className . '.php';
        if (is_file($classFile)) {
            require_once $classFile;
        }
    }
    spl_autoload_register('autoload_fleet_classes');
}

/**
 * Wejście: $_GET z formularza.
 * Wyjście:
 * [
 *   'active'   => bool,      // czy cokolwiek było filtrowane
 *   'products' => array[],   // przefiltrowane produkty + dane o promocji
 * ]
 */
function run_search(array $input): array
{
    // --- 1) Normalizacja wejścia (NAZWY PÓL = jak w formularzu!) ---
    $pickup_location  = trim((string)($input['pickup_location']  ?? ''));
    $dropoff_location = trim((string)($input['dropoff_location'] ?? ''));

    // Akceptujemy obie nazwy dat, żeby uniknąć rozjazdów (pickup_at / pickup_datetime)
    $pickup_dt_raw  = trim((string)($input['pickup_at']   ?? $input['pickup_datetime']  ?? ''));
    $dropoff_dt_raw = trim((string)($input['return_at']   ?? $input['dropoff_datetime'] ?? ''));

    $vehicleType = trim((string)($input['vehicle_type'] ?? '')); // na razie nie mapujemy do category – zrobimy później
    $transRaw    = trim((string)($input['transmission'] ?? '')); // 'manual' / 'automatic' / ''
    $seats_min   = (int)($input['seats_min'] ?? 0);
    $fuelRaw     = trim((string)($input['fuel'] ?? ''));          // 'benzyna'/'diesel'/'hybryda'/'elektryczny' / ''

    $active = (
        $pickup_location !== '' || $dropoff_location !== '' ||
        $pickup_dt_raw !== ''   || $dropoff_dt_raw !== ''   ||
        $vehicleType !== '' || $transRaw !== '' || $seats_min > 0 || $fuelRaw !== ''
    );

    // Promocje naliczamy WYŁĄCZNIE, gdy użytkownik faktycznie wyszukał
    // i podał daty (żeby warunki okresu i min_days miały sens).
    $applyPromos = $active && $pickup_dt_raw !== '' && $dropoff_dt_raw !== '';

    // --- 2) Wyliczenie liczby dni najmu (dla min_days w promocjach) ---
    $rental_days = null;
    $pickup_ts   = null;
    $dropoff_ts  = null;

    if ($pickup_dt_raw !== '' && $dropoff_dt_raw !== '') {
        $pickup_ts  = strtotime($pickup_dt_raw) ?: null;
        $dropoff_ts = strtotime($dropoff_dt_raw) ?: null;

        if ($pickup_ts && $dropoff_ts && $dropoff_ts > $pickup_ts) {
            $diff_hours  = max(1, (int)ceil(($dropoff_ts - $pickup_ts) / 3600));
            $rental_days = max(1, (int)ceil($diff_hours / 24));
        }
    }

    // --- 3) Budowa zapytania po produkty (uwzględniamy filtry) ---
    $sql = "
      SELECT
        id, name, sku, price, price_unit, stock, status,
        category, seats, doors, gearbox, fuel, image_path, description
      FROM products
      WHERE status = 'active' AND stock > 0
    ";
    $bind = [];

    // (vehicleType odkładamy na później – teraz nie filtrujemy po nim)

    // Skrzynia biegów: mapowanie 'manual'/'automatic' → wartości w DB
    if ($transRaw !== '') {
        $transMap   = ['manual' => 'Manualna', 'automatic' => 'Automatyczna'];
        $transHuman = $transMap[strtolower($transRaw)] ?? $transRaw;
        $sql .= " AND LOWER(gearbox) = LOWER(:gearbox)";
        $bind[':gearbox'] = $transHuman;
    }

    if ($seats_min > 0) {
        $sql .= " AND seats >= :seats_min";
        $bind[':seats_min'] = $seats_min;
    }

    if ($fuelRaw !== '') {
        $sql .= " AND LOWER(fuel) = LOWER(:fuel)";
        $bind[':fuel'] = $fuelRaw;
    }

    $sql .= " ORDER BY id DESC";

    $stmt = db()->prepare($sql);
    $stmt->execute($bind);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // --- 4) Filtrowanie według dostępności w lokalizacji (Fleet Management) ---
    if ($pickup_location !== '') {
        $items = filter_products_by_location($items, $pickup_location, $pickup_dt_raw, $dropoff_dt_raw);
    }

    // --- 5) Pobierz aktywne promocje, ale tylko gdy faktycznie je naliczamy ---
    $promos = $applyPromos ? fetch_active_promotions($pickup_ts, $dropoff_ts) : [];

    // --- 6) Naliczenie promocji (wybieramy najlepszą) albo ustawiamy ceny bazowe ---
    foreach ($items as &$p) {
        if ($applyPromos) {
            [$final, $applied, $label] = apply_promotions_to_product(
                $p,
                $promos,
                $rental_days,
                $pickup_location,
                $dropoff_location
            );
            $p['price_final']      = $final;
            $p['discount_applied'] = $applied;
            $p['discount_label']   = $label;
        } else {
            // brak wyszukiwania z datami → brak promocji, cena bazowa
            $p['price_final']      = (float)$p['price'];
            $p['discount_applied'] = false;
            $p['discount_label']   = null;
        }
    }
    unset($p);

    return [
        'active'   => $active,
        'products' => $items,
    ];
}

/**
 * Aktywne promocje (jeśli znamy zakres dat, to tylko przecinające się).
 */
function fetch_active_promotions(?int $pickup_ts, ?int $dropoff_ts): array
{
    $sql = "SELECT
              id, name, code, is_active, scope_type, scope_value,
              valid_from, valid_to, min_days, discount_type, discount_val
            FROM promotions
            WHERE is_active = 1";
    $bind = [];

    if ($pickup_ts && $dropoff_ts) {
        // przecinające się przedziały: [valid_from..valid_to] ∩ [pickup..dropoff] ≠ ∅
        $sql .= " AND ( (valid_from IS NULL OR valid_from <= :dropoff)
                        AND (valid_to   IS NULL OR valid_to   >= :pickup) )";
        $bind[':pickup']  = date('Y-m-d H:i:s', $pickup_ts);
        $bind[':dropoff'] = date('Y-m-d H:i:s', $dropoff_ts);
    }

    $sql .= " ORDER BY id DESC";

    $stmt = db()->prepare($sql);
    $stmt->execute($bind);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Zwraca [final_price, applied(bool), label(string|null)] dla pojedynczego produktu.
 */
function apply_promotions_to_product(
    array $p,
    array $promos,
    ?int $rental_days,
    string $pickup_location,
    string $dropoff_location
): array {
    $base      = (float)$p['price'];
    $bestFinal = $base;
    $bestLabel = null;
    $applied   = false;

    foreach ($promos as $pr) {
        // min. liczba dni
        $min_days = (int)($pr['min_days'] ?? 1);
        if ($rental_days !== null && $min_days > 1 && $rental_days < $min_days) {
            continue;
        }

        // dopasowanie zakresu promocji
        if (!product_matches_promo($p, $pr, $pickup_location, $dropoff_location)) {
            continue;
        }

        $final = $base;
        $label = null;
        $type  = (string)($pr['discount_type'] ?? 'percent'); // 'percent' | 'amount'
        $val   = (float)($pr['discount_val'] ?? 0);

        if ($type === 'percent' && $val > 0) {
            $final = $base * max(0.0, (1.0 - $val / 100.0));
            $num   = rtrim(rtrim(number_format($val, 2, '.', ''), '0'), '.'); // 10 lub 10.5
            $label = 'Promocja -' . $num . '%';
        } elseif ($type === 'amount' && $val > 0) {
            $final = max(0.0, $base - $val);
            $label = 'Promocja -' . number_format($val, 0, ',', ' ') . ' zł';
        }

        if ($final < $bestFinal) {
            $bestFinal = $final;
            $bestLabel = $label;
            $applied   = true;
        }
    }

    return [$bestFinal, $applied, $bestLabel];
}

/**
 * Dopasowanie produktu do zakresu promocji.
 */
function product_matches_promo(
    array $p,
    array $pr,
    string $pickup_location,
    string $dropoff_location
): bool {
    $type = (string)($pr['scope_type'] ?? 'global');
    $raw  = $pr['scope_value'] ?? null;

    if ($type === 'global' || $raw === null || $raw === '') {
        return true;
    }

    $vals = json_decode((string)$raw, true);
    if (!is_array($vals) || !$vals) return false;

    switch ($type) {
        case 'product': {
                $id  = (int)$p['id'];
                $sku = (string)$p['sku'];
                foreach ($vals as $v) {
                    if (is_numeric($v) && (int)$v === $id)   return true;
                    if (!is_numeric($v) && (string)$v === $sku) return true;
                }
                return false;
            }

        case 'category': {
                $cat = (string)($p['category'] ?? '');
                foreach ($vals as $v) {
                    if ((string)$v === $cat) return true;
                }
                return false;
            }

        case 'pickup_location': {
                foreach ($vals as $v) {
                    if ((string)$v === $pickup_location) return true;
                }
                return false;
            }

        case 'return_location': {
                foreach ($vals as $v) {
                    if ((string)$v === $dropoff_location) return true;
                }
                return false;
            }

        default:
            return false;
    }
}

/**
 * Filtruje produkty według dostępności w określonej lokalizacji (Fleet Management)
 */
function filter_products_by_location(array $products, string $pickup_location, string $pickup_dt_raw = '', string $dropoff_dt_raw = ''): array
{
    try {
        // Inicjalizacja połączenia z bazą danych
        $pdo = db(); // Używamy funkcji db() z config.php
        $fleetManager = new FleetManager($pdo);

        // Jeśli system Fleet Management jest wyłączony, zwróć wszystkie produkty
        if (!$fleetManager->isEnabled()) {
            return $products;
        }

        // Znajdź ID lokalizacji na podstawie nazwy
        $locations = $fleetManager->getActiveLocations();
        $locationId = null;

        foreach ($locations as $location) {
            // Porównaj z formatem nazwy jak w formularzu: "Nazwa (Miasto)"
            $displayName = $location['name'] . ' (' . $location['city'] . ')';
            if (
                $displayName === $pickup_location ||
                $location['name'] === $pickup_location ||
                $location['city'] === $pickup_location
            ) {
                $locationId = $location['id'];
                break;
            }
        }

        // Jeśli nie znaleziono lokalizacji, zwróć puste wyniki
        if (!$locationId) {
            return [];
        }

        // Filtruj produkty według dostępności w tej lokalizacji
        $filteredProducts = [];

        foreach ($products as $product) {
            $isAvailable = $fleetManager->isProductAvailableInLocation(
                $product['id'],  // Używamy product_id zamiast sku
                $locationId,
                $pickup_dt_raw ?: null,
                $dropoff_dt_raw ?: null
            );

            if ($isAvailable) {
                $filteredProducts[] = $product;
            }
        }

        return $filteredProducts;
    } catch (Exception $e) {
        // W przypadku błędu, zwróć wszystkie produkty (graceful degradation)
        return $products;
    }
}
