<?php

declare(strict_types=1);

// /pages/staff/_helpers.php
// TYLKO funkcje pomocnicze — BEZ require/include!

/** Bezpieczny escape do HTML (UTF-8) */
function e(string $v): string
{
    return htmlspecialchars($v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function status_badge(string $s): string
{
    return match ($s) {
        'active', 'paid', 'completed' => 'text-bg-success',
        'pending', 'draft'            => 'text-bg-warning',
        'inactive', 'cancelled'       => 'text-bg-secondary',
        default                       => 'text-bg-light',
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

    if ($max < 1) $max = 1;

    $labels = [];
    foreach ($arr as $raw) {
        $rawStr = trim((string)$raw);

        switch ($scopeType) {
            case 'product':
                if ($rawStr !== '' && is_numeric($rawStr)) {
                    $id = (int)$rawStr;
                    $labels[] = isset($maps['byId'][$id]) ? (string)$maps['byId'][$id] : (string)$id;
                } else {
                    $labels[] = isset($maps['bySku'][$rawStr]) ? (string)$maps['bySku'][$rawStr] : $rawStr;
                }
                break;

            case 'category':
                $labels[] = isset($maps['class'][$rawStr]) ? (string)$maps['class'][$rawStr] : ('Klasa ' . strtoupper($rawStr));
                break;

            case 'pickup_location':
            case 'return_location':
                $labels[] = $rawStr;
                break;

            default:
                $labels[] = $rawStr;
        }
    }

    if (!$labels) return '—';

    $first = array_slice($labels, 0, $max);
    $rest  = max(count($labels) - $max, 0);
    $txt   = e(implode(', ', $first));

    if ($rest > 0) {
        $txt .= ' <span class="badge text-bg-light">+' . (int)$rest . '</span>';
    }
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
    $fTs = $from ? strtotime($from) : null;
    $tTs = $to   ? strtotime($to)   : null;

    $f = $fTs ? date('Y-m-d', $fTs) : '—';
    $t = $tTs ? date('Y-m-d', $tTs) : '—';

    return $f . ' → ' . $t;
}
