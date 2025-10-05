<?php
// expects: $promos, $BASE, $productNameById, $productNameBySku, $classLabel
// NOTE: _helpers.php already included in parent dashboard-staff.php

// --- PROMO HELPERS ---
if (!function_exists('promo_discount')) {
    function promo_discount(string $type, float $val): string
    {
        if ($type === 'percent' && $val > 0) {
            $num = rtrim(rtrim(number_format($val, 2, '.', ''), '0'), '.');
            return '-' . $num . '%';
        }
        if ($type === 'fixed' && $val > 0) {
            return '-' . number_format($val, 2) . ' PLN';
        }
        return '';
    }
}

if (!function_exists('promo_period')) {
    function promo_period(?string $from, ?string $to): string
    {
        if (!$from && !$to) return '(' . __('no_restrictions', 'admin', 'bez ograniczeń') . ')';
        $f = $from ? date('Y-m-d', strtotime($from)) : '';
        $t = $to   ? date('Y-m-d', strtotime($to))   : '';
        if ($f && $t) return "$f → $t";
        if ($f) return __('from', 'admin', 'od') . " $f";
        if ($t) return __('to', 'admin', 'do') . " $t";
        return '';
    }
}

if (!function_exists('promo_scope_label')) {
    function promo_scope_label(string $type): string
    {
        return match ($type) {
            'all'        => __('all_products', 'admin', 'Wszystkie produkty'),
            'category'   => __('category', 'admin', 'Kategoria'),
            'product_id' => __('specific_product', 'admin', 'Konkretny produkt'),
            'product_sku' => __('product_sku', 'admin', 'Produkt (SKU)'),
            default      => ucfirst($type),
        };
    }
}

if (!function_exists('promo_values_for_scope')) {
    function promo_values_for_scope(string $type, ?string $vals, array $maps, int $limit = 5): string
    {
        if ($type === 'all') return __('all_products', 'admin', 'Wszystkie');
        if (!$vals) return '(' . __('no_restrictions', 'admin', 'brak') . ')';

        $items = array_filter(array_map('trim', explode(',', $vals)));
        if (empty($items)) return '(' . __('no_restrictions', 'admin', 'brak') . ')';

        $labels = [];
        foreach ($items as $item) {
            if ($type === 'product_id' && isset($maps['byId'][(int)$item])) {
                $labels[] = $maps['byId'][(int)$item];
            } elseif ($type === 'product_sku' && isset($maps['bySku'][$item])) {
                $labels[] = $maps['bySku'][$item];
            } elseif ($type === 'category' && isset($maps['class'][$item])) {
                $labels[] = $maps['class'][$item];
            } else {
                $labels[] = $item;
            }
            if (count($labels) >= $limit) {
                $labels[] = '...';
                break;
            }
        }
        return implode(', ', $labels);
    }
}

// Setup maps for helper functions
$maps = [
    'byId'  => $productNameById ?? [],
    'bySku' => $productNameBySku ?? [],
    'class' => $classLabel ?? [],
];

?>
<div class="card section-promos">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h2 class="h6 mb-0"><?= __('promotions', 'admin', 'Promocje') ?></h2>
        <a class="btn btn-sm btn-primary" href="pages/promo-form.php"><?= __('add_promotion', 'admin', 'Dodaj promocję') ?></a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead class="small text-muted">
                    <tr>
                        <th><?= sort_link_dashboard('promos', 'id', 'ID') ?></th>
                        <th><?= sort_link_dashboard('promos', 'name', __('name', 'admin', 'Nazwa')) ?></th>
                        <th><?= sort_link_dashboard('promos', 'code', __('code', 'admin', 'Kod')) ?></th>
                        <th><?= sort_link_dashboard('promos', 'active', __('active', 'admin', 'Aktywna')) ?></th>
                        <th><?= sort_link_dashboard('promos', 'scope', __('scope', 'admin', 'Zasięg')) ?></th>
                        <th><?= __('applies_to', 'admin', 'Dotyczy') ?></th>
                        <th><?= sort_link_dashboard('promos', 'discount', __('discount', 'admin', 'Zniżka')) ?></th>
                        <th><?= __('min_days', 'admin', 'Min. dni') ?></th>
                        <th><?= __('period', 'admin', 'Okres') ?></th>
                        <th class="text-end"><?= __('actions', 'admin', 'Akcje') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Setup maps for helper functions
                    $maps = [
                        'byId'  => $productNameById,
                        'bySku' => $productNameBySku,
                        'class' => $classLabel,
                    ];

                    if (empty($promos)): ?>
                        <tr>
                            <td colspan="10" class="text-center text-muted py-4"><?= __('no_promotions', 'admin', 'Brak promocji.') ?></td>
                        </tr>
                        <?php else: foreach ($promos as $pr): ?>
                            <?php
                            $id       = (int)($pr['id'] ?? 0);
                            $name     = (string)($pr['name'] ?? '');
                            $code     = $pr['code'] ?? null;
                            $isA      = (int)($pr['is_active'] ?? 0) === 1;
                            $typ      = (string)($pr['scope_type'] ?? 'global');
                            $vals     = $pr['scope_value'] ?? null;

                            $discType = isset($pr['discount_type']) ? (string)$pr['discount_type'] : 'percent';
                            $discVal  = isset($pr['discount_val'])  ? (float)$pr['discount_val']  : 0.0;
                            $disc     = promo_discount($discType, $discVal);

                            $minD     = (int)($pr['min_days'] ?? 1);
                            $period   = promo_period($pr['valid_from'] ?? null, $pr['valid_to'] ?? null);

                            $dotyczy  = promo_values_for_scope($typ, $vals, $maps, 3);
                            ?>
                            <tr>
                                <td><?= $id ?></td>
                                <td><?= htmlspecialchars($name) ?></td>
                                <td class="font-monospace"><?= $code ? htmlspecialchars($code) : '—' ?></td>
                                <td><?= $isA ? __('yes', 'admin', 'Tak') : __('no', 'admin', 'Nie') ?></td>
                                <td><?= promo_scope_label($typ) ?></td>
                                <td><?= $dotyczy ?></td>
                                <td><?= $disc ?></td>
                                <td><?= $minD ?></td>
                                <td><?= $period ?></td>
                                <td class="text-end">
                                    <div class="d-flex gap-1 justify-content-end">
                                        <a href="pages/promo-form.php?id=<?= $id ?>" class="btn btn-outline-primary btn-sm"><?= __('edit', 'admin', 'Edytuj') ?></a>
                                        <a href="pages/promo-delete.php?id=<?= $id ?>&_token=<?= htmlspecialchars(csrf_token(), ENT_QUOTES) ?>"
                                            class="btn btn-outline-danger btn-sm"
                                            onclick="return confirm('<?= __('confirm_delete_promotion', 'admin', 'Usunąć promocję') ?> #<?= $id ?>?');"><?= __('delete', 'admin', 'Usuń') ?></a>
                                    </div>
                                </td>
                            </tr>
                    <?php endforeach;
                    endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>