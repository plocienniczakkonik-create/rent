<?php
// expects: $promos, $productNameById, $productNameBySku, $classLabel
$maps = [
    'byId'  => $productNameById,
    'bySku' => $productNameBySku,
    'class' => $classLabel,
];
?>
<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h2 class="h6 mb-0">Promocje</h2>
        <a class="btn btn-sm btn-primary" href="pages/promo-form.php">Dodaj promocję</a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead class="small text-muted">
                    <tr>
                        <th>ID</th>
                        <th>Nazwa</th>
                        <th>Kod</th>
                        <th>Aktywna</th>
                        <th>Typ</th>
                        <th>Dotyczy</th>
                        <th>Rabat</th>
                        <th>Min dni</th>
                        <th>Okres</th>
                        <th class="text-end">Akcje</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($promos)): ?>
                        <tr>
                            <td colspan="10" class="text-center text-muted py-4">Brak promocji.</td>
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
                                <td><?= $isA ? 'Tak' : 'Nie' ?></td>
                                <td><?= promo_scope_label($typ) ?></td>
                                <td><?= $dotyczy ?></td>
                                <td><?= $disc ?></td>
                                <td><?= $minD ?></td>
                                <td><?= $period ?></td>
                                <td class="text-end">
                                    <a href="pages/promo-form.php?id=<?= $id ?>" class="btn btn-outline-primary btn-sm">Edytuj</a>
                                    <a href="pages/promo-delete.php?id=<?= $id ?>&csrf=<?= htmlspecialchars(csrf_token()) ?>"
                                        class="btn btn-outline-danger btn-sm"
                                        onclick="return confirm('Usunąć promocję #<?= $id ?>?');">Usuń</a>
                                </td>
                            </tr>
                    <?php endforeach;
                    endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>