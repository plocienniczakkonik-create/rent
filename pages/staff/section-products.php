<?php // expects: $products, $BASE 
?>
<div class="card section-products">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h2 class="h6 mb-0"><?= __('products', 'admin', 'Produkty') ?></h2>
        <!-- usunięto lokalny przycisk "Dodaj produkt" -->
    </div>
    <div class="card-body p-0">
        <!-- reszta pliku bez zmian -->

        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead class="small text-muted">
                    <tr>
                        <th><?= sort_link_dashboard('products', 'id', __('id', 'admin', 'ID')) ?></th>
                        <th><?= sort_link_dashboard('products', 'name', __('name', 'admin', 'Nazwa')) ?></th>
                        <th><?= sort_link_dashboard('products', 'sku', __('sku', 'admin', 'SKU')) ?></th>
                        <th><?= sort_link_dashboard('products', 'price', __('price', 'admin', 'Cena')) ?></th>
                        <th><?= sort_link_dashboard('products', 'stock', __('stock', 'admin', 'Stan')) ?></th>
                        <th><?= sort_link_dashboard('products', 'status', __('status', 'admin', 'Status')) ?></th>
                        <th class="text-end"><?= __('actions', 'admin', 'Akcje') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$products): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4"><?= __('no_products', 'admin', 'Brak produktów.') ?></td>
                        </tr>
                        <?php else: foreach ($products as $p): ?>
                            <tr>
                                <td><?= (int)$p['id'] ?></td>
                                <td><?= htmlspecialchars($p['name']) ?></td>
                                <td class="text-muted font-monospace"><?= htmlspecialchars($p['sku']) ?></td>
                                <td><?= number_format((float)$p['price'], 2, ',', ' ') ?> PLN</td>
                                <td><?= (int)$p['stock'] ?></td>
                                <td><span class="badge <?= status_badge($p['status']) ?>"><?= htmlspecialchars($p['status']) ?></span></td>
                                <td class="text-end">
                                    <!-- NOWE: Flota (egzemplarze tego modelu) -->
                                    <a href="<?= $BASE ?>/index.php?page=vehicles-manage&product=<?= (int)$p['id'] ?>"
                                        class="btn btn-outline-info btn-sm"><?= __('fleet', 'admin', 'Flota') ?></a>

                                    <!-- Istniejące akcje -->
                                    <a href="<?= $BASE ?>/index.php?page=product-form&id=<?= (int)$p['id'] ?>"
                                        class="btn btn-outline-primary btn-sm"><?= __('edit', 'admin', 'Edytuj') ?></a>
                                    <a href="<?= $BASE ?>/index.php?page=product-delete&id=<?= (int)$p['id'] ?>&csrf=<?= htmlspecialchars(csrf_token()) ?>"
                                        class="btn btn-outline-danger btn-sm"
                                        onclick="return confirm('<?= __('confirm_delete_product', 'admin', 'Usunąć produkt') ?> #<?= (int)$p['id'] ?>?');"><?= __('delete', 'admin', 'Usuń') ?></a>
                                </td>
                            </tr>
                    <?php endforeach;
                    endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>