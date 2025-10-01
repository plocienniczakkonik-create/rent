<?php
// /pages/staff/_helpers.php
// TYLKO funkcje pomocnicze — BEZ require/include!

function status_badge($s)
{
    return match ($s) {
        'active', 'paid', 'completed' => 'text-bg-success',
        'pending', 'draft'           => 'text-bg-warning',
        'inactive', 'cancelled'      => 'text-bg-secondary',
        default                      => 'text-bg-light',
    };
}

function promo_scope_label(string $t): string
{
    return match ($t) {
        'product'         => 'Samochód',
        'category'        => 'Klasa',
        'pickup_location' => 'Miejsce odbioru',
        'return_location' => 'Miejsce zwrotu',
        'global'          => 'Wszystko',
        default           => ucfirst($t),
    };
}

/**
 * Zamienia JSON z scope_value na czytelne etykiety zależnie od scope_type.
 * $maps = ['byId'=>[id=>name], 'bySku'=>[sku=>name], 'class'=>[code=>label]]
 */
function promo_values_for_scope(
    string $scopeType,
    ?string $json,
    array $maps,
    int $max = 3
): string {
    if (!$json) return '—';
    $arr = json_decode($json, true);
    if (!is_array($arr) || !$arr) return '—';

    $labels = [];
    foreach ($arr as $raw) {
        switch ($scopeType) {
            case 'product':
                if (is_numeric($raw)) {
                    $id = (int)$raw;
                    $labels[] = $maps['byId'][$id] ?? (string)$raw;
                } else {
                    $sku = (string)$raw;
                    $labels[] = $maps['bySku'][$sku] ?? $sku;
                }
                break;

            case 'category':
                $code = (string)$raw;
                $labels[] = $maps['class'][$code] ?? ('Klasa ' . strtoupper($code));
                break;

            case 'pickup_location':
            case 'return_location':
                $labels[] = (string)$raw;
                break;

            default:
                $labels[] = (string)$raw;
        }
    }

    if (!$labels) return '—';
    $first = array_slice($labels, 0, $max);
    $rest  = max(count($labels) - $max, 0);
    $txt   = htmlspecialchars(implode(', ', $first));
    if ($rest > 0) $txt .= ' <span class="badge text-bg-light">+' . $rest . '</span>';
    return $txt;
}

function promo_discount(string $type, float $val): string
{
    if ($type === 'percent') {
        $s = rtrim(rtrim(number_format($val, 2, '.', ''), '0'), '.');
        return '-' . $s . '%';
    }
    return '-' . number_format($val, 2, ',', ' ') . ' PLN';
}

function promo_period(?string $from, ?string $to): string
{
    $f = $from ? date('Y-m-d', strtotime($from)) : '—';
    $t = $to   ? date('Y-m-d', strtotime($to))   : '—';
    return $f . ' → ' . $t;
}
