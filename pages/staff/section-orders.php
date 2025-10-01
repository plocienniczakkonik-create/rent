<?php // expects: $orders 
?>
<div class="card">
    <div class="card-header">
        <h2 class="h6 mb-0">Zamówienia</h2>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead class="small text-muted">
                    <tr>
                        <th>ID</th>
                        <th>Data</th>
                        <th>Produkt</th>
                        <th>Ilość</th>
                        <th>Suma</th>
                        <th>Status</th>
                        <th class="text-end">Akcje</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$orders): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">Brak zamówień.</td>
                        </tr>
                        <?php else: foreach ($orders as $o): ?>
                            <tr>
                                <td>#<?= (int)$o['id'] ?></td>
                                <td><?= htmlspecialchars($o['date']) ?></td>
                                <td><?= htmlspecialchars($o['product']) ?></td>
                                <td><?= (int)$o['qty'] ?></td>
                                <td><?= number_format((float)$o['total'], 2, ',', ' ') ?> PLN</td>
                                <td><span class="badge <?= status_badge($o['status']) ?>"><?= htmlspecialchars($o['status']) ?></span></td>
                                <td class="text-end">
                                    <a href="pages/order.php?id=<?= (int)$o['id'] ?>" class="btn btn-outline-primary btn-sm">Szczegóły</a>
                                </td>
                            </tr>
                    <?php endforeach;
                    endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>