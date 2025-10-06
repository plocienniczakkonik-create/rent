<?php
// /pages/promo-save.php
require_once dirname(__DIR__) . '/auth/auth.php';
$staff = require_staff();
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/_helpers.php';

csrf_verify();

$BASE = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';

$id            = (int)($_POST['id'] ?? 0);
$name          = trim((string)($_POST['name'] ?? ''));
$requiresCode  = (int)($_POST['requiresCode'] ?? 0); // przyjdzie z JS? nie – bierzemy z selectu na froncie
// jeśli nie ma w POST (bo to select bez name), to nadrabiamy na podstawie 'code'
$code          = trim((string)($_POST['code'] ?? ''));
$is_active     = (int)($_POST['is_active'] ?? 1) === 1 ? 1 : 0;

$scope_type    = (string)($_POST['scope_type'] ?? 'global');
$valid_from    = trim((string)($_POST['valid_from'] ?? ''));
$valid_to      = trim((string)($_POST['valid_to'] ?? ''));
$min_days      = (int)($_POST['min_days'] ?? 1);
$discount_type = (string)($_POST['discount_type'] ?? 'percent');
$discount_val  = (float)($_POST['discount_val'] ?? 0);

// walidacje
$allowed_scope = ['global', 'product', 'category', 'pickup_location', 'return_location', 'both_locations', 
                  'product_pickup', 'product_return', 'product_both', 
                  'category_pickup', 'category_return', 'category_both'];
$allowed_disc  = ['percent', 'amount'];

if ($name === '' || !in_array($scope_type, $allowed_scope, true) || !in_array($discount_type, $allowed_disc, true)) {
    http_response_code(422);
    exit('Błędne dane.');
}
if ($discount_type === 'percent' && ($discount_val < 0 || $discount_val > 100)) {
    http_response_code(422);
    exit('Procent poza zakresem (0–100).');
}
if ($discount_type === 'amount' && $discount_val < 0) {
    http_response_code(422);
    exit('Kwota rabatu nie może być ujemna.');
}
if ($min_days < 1 || $min_days > 365) {
    http_response_code(422);
    exit('Minimalna liczba dni poza zakresem 1–365.');
}

// scope_value jako JSON array wg typu
$scope_values = [];
switch ($scope_type) {
    case 'product':
        $scope_values = (array)($_POST['scope_value_product'] ?? []); // array of SKU
        $scope_values = array_values(array_filter(array_map('strval', $scope_values), fn($s) => $s !== ''));
        if (!$scope_values) {
            http_response_code(422);
            exit('Wybierz co najmniej jeden samochód.');
        }
        break;

    case 'category':
        $scope_values = (array)($_POST['scope_value_category'] ?? []); // array of category names
        $scope_values = array_values(array_filter(array_map('strval', $scope_values), fn($s) => $s !== ''));
        if (!$scope_values) {
            http_response_code(422);
            exit('Wybierz co najmniej jedną klasę.');
        }
        break;

    case 'pickup_location':
        $pickup_ids = (array)($_POST['scope_value_pickup_ids'] ?? []);
        $scope_values = array_values(array_filter(array_map('intval', $pickup_ids), fn($id) => $id > 0));
        if (!$scope_values) {
            http_response_code(422);
            exit('Wybierz co najmniej jedno miejsce odbioru.');
        }
        break;

    case 'return_location':
        $return_ids = (array)($_POST['scope_value_return_ids'] ?? []);
        $scope_values = array_values(array_filter(array_map('intval', $return_ids), fn($id) => $id > 0));
        if (!$scope_values) {
            http_response_code(422);
            exit('Wybierz co najmniej jedno miejsce zwrotu.');
        }
        break;

    case 'both_locations':
        $pickup_ids = (array)($_POST['scope_value_pickup_ids'] ?? []);
        $pickup_ids = array_values(array_filter(array_map('intval', $pickup_ids), fn($id) => $id > 0));
        
        $return_ids = (array)($_POST['scope_value_return_ids'] ?? []);
        $return_ids = array_values(array_filter(array_map('intval', $return_ids), fn($id) => $id > 0));
        
        if (!$pickup_ids || !$return_ids) {
            http_response_code(422);
            exit('Wybierz co najmniej jedno miejsce odbioru i zwrotu.');
        }
        
        $scope_values = [
            'pickup_location_ids' => $pickup_ids,
            'return_location_ids' => $return_ids
        ];
        break;

    case 'product_pickup':
        $products_arr = (array)($_POST['scope_value_product'] ?? []);
        $products_arr = array_values(array_filter(array_map('strval', $products_arr), fn($s) => $s !== ''));
        
        $pickup_ids = (array)($_POST['scope_value_pickup_ids'] ?? []);
        $pickup_ids = array_values(array_filter(array_map('intval', $pickup_ids), fn($id) => $id > 0));
        
        if (!$products_arr) {
            http_response_code(422);
            exit('Wybierz co najmniej jeden samochód.');
        }
        if (!$pickup_ids) {
            http_response_code(422);
            exit('Wybierz co najmniej jedno miejsce odbioru.');
        }
        
        $scope_values = [
            'products' => $products_arr,
            'pickup_location_ids' => $pickup_ids
        ];
        break;

    case 'product_return':
        $products_arr = (array)($_POST['scope_value_product'] ?? []);
        $products_arr = array_values(array_filter(array_map('strval', $products_arr), fn($s) => $s !== ''));
        
        $return_ids = (array)($_POST['scope_value_return_ids'] ?? []);
        $return_ids = array_values(array_filter(array_map('intval', $return_ids), fn($id) => $id > 0));
        
        if (!$products_arr) {
            http_response_code(422);
            exit('Wybierz co najmniej jeden samochód.');
        }
        if (!$return_ids) {
            http_response_code(422);
            exit('Wybierz co najmniej jedno miejsce zwrotu.');
        }
        
        $scope_values = [
            'products' => $products_arr,
            'return_location_ids' => $return_ids
        ];
        break;

    case 'product_both':
        $products_arr = (array)($_POST['scope_value_product'] ?? []);
        $products_arr = array_values(array_filter(array_map('strval', $products_arr), fn($s) => $s !== ''));
        
        $pickup_ids = (array)($_POST['scope_value_pickup_ids'] ?? []);
        $pickup_ids = array_values(array_filter(array_map('intval', $pickup_ids), fn($id) => $id > 0));
        
        $return_ids = (array)($_POST['scope_value_return_ids'] ?? []);
        $return_ids = array_values(array_filter(array_map('intval', $return_ids), fn($id) => $id > 0));
        
        if (!$products_arr) {
            http_response_code(422);
            exit('Wybierz co najmniej jeden samochód.');
        }
        if (!$pickup_ids || !$return_ids) {
            http_response_code(422);
            exit('Wybierz co najmniej jedno miejsce odbioru i zwrotu.');
        }
        
        $scope_values = [
            'products' => $products_arr,
            'pickup_location_ids' => $pickup_ids,
            'return_location_ids' => $return_ids
        ];
        break;

    case 'category_pickup':
        $categories_arr = (array)($_POST['scope_value_category'] ?? []);
        $categories_arr = array_values(array_filter(array_map('strval', $categories_arr), fn($s) => $s !== ''));
        
        $pickup_ids = (array)($_POST['scope_value_pickup_ids'] ?? []);
        $pickup_ids = array_values(array_filter(array_map('intval', $pickup_ids), fn($id) => $id > 0));
        
        if (!$categories_arr) {
            http_response_code(422);
            exit('Wybierz co najmniej jedną klasę.');
        }
        if (!$pickup_ids) {
            http_response_code(422);
            exit('Wybierz co najmniej jedno miejsce odbioru.');
        }
        
        $scope_values = [
            'categories' => $categories_arr,
            'pickup_location_ids' => $pickup_ids
        ];
        break;

    case 'category_return':
        $categories_arr = (array)($_POST['scope_value_category'] ?? []);
        $categories_arr = array_values(array_filter(array_map('strval', $categories_arr), fn($s) => $s !== ''));
        
        $return_ids = (array)($_POST['scope_value_return_ids'] ?? []);
        $return_ids = array_values(array_filter(array_map('intval', $return_ids), fn($id) => $id > 0));
        
        if (!$categories_arr) {
            http_response_code(422);
            exit('Wybierz co najmniej jedną klasę.');
        }
        if (!$return_ids) {
            http_response_code(422);
            exit('Wybierz co najmniej jedno miejsce zwrotu.');
        }
        
        $scope_values = [
            'categories' => $categories_arr,
            'return_location_ids' => $return_ids
        ];
        break;

    case 'category_both':
        $categories_arr = (array)($_POST['scope_value_category'] ?? []);
        $categories_arr = array_values(array_filter(array_map('strval', $categories_arr), fn($s) => $s !== ''));
        
        $pickup_ids = (array)($_POST['scope_value_pickup_ids'] ?? []);
        $pickup_ids = array_values(array_filter(array_map('intval', $pickup_ids), fn($id) => $id > 0));
        
        $return_ids = (array)($_POST['scope_value_return_ids'] ?? []);
        $return_ids = array_values(array_filter(array_map('intval', $return_ids), fn($id) => $id > 0));
        
        if (!$categories_arr) {
            http_response_code(422);
            exit('Wybierz co najmniej jedną klasę.');
        }
        if (!$pickup_ids || !$return_ids) {
            http_response_code(422);
            exit('Wybierz co najmniej jedno miejsce odbioru i zwrotu.');
        }
        
        $scope_values = [
            'categories' => $categories_arr,
            'pickup_location_ids' => $pickup_ids,
            'return_location_ids' => $return_ids
        ];
        break;

    case 'global':
    default:
        $scope_values = []; // pusty zakres oznacza „wszystko"
}

$scope_value_json = json_encode($scope_values, JSON_UNESCAPED_UNICODE);

// Daty (puste -> NULL)
$vf = $valid_from !== '' ? date('Y-m-d H:i:s', strtotime($valid_from)) : null;
$vt = $valid_to   !== '' ? date('Y-m-d H:i:s', strtotime($valid_to))   : null;

// Jeśli pole kodu puste, nie wymaga kuponu
$code = ($code !== '') ? $code : null;

try {
    if ($id > 0) {
        $sql = "
      UPDATE promotions
         SET name = ?, code = ?, is_active = ?,
             scope_type = ?, scope_value = ?,
             valid_from = ?, valid_to = ?,
             min_days = ?, discount_type = ?, discount_val = ?
       WHERE id = ? LIMIT 1
    ";
        $params = [
            $name,
            $code,
            $is_active,
            $scope_type,
            $scope_value_json,
            $vf,
            $vt,
            $min_days,
            $discount_type,
            $discount_val,
            $id
        ];
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
    } else {
        $sql = "
      INSERT INTO promotions
        (name, code, is_active, scope_type, scope_value, valid_from, valid_to, min_days, discount_type, discount_val)
      VALUES
        (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ";
        $stmt = db()->prepare($sql);
        $stmt->execute([
            $name,
            $code,
            $is_active,
            $scope_type,
            $scope_value_json,
            $vf,
            $vt,
            $min_days,
            $discount_type,
            $discount_val
        ]);
        $id = (int)db()->lastInsertId();
    }
} catch (PDOException $e) {
    throw $e;
}

header('Location: ' . $BASE . '/index.php?page=dashboard-staff#pane-promos');
exit;