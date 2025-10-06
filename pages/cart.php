<?php
require_once __DIR__ . '/../includes/_helpers.php';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

$BASE = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';

// Load products for cart validation
$pdo = db();
$products = [];
try {
    $stmt = $pdo->query('SELECT sku, name, price, status FROM products WHERE status = "active"');
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $productsBySku = array_column($products, null, 'sku');
} catch (Exception $e) {
    $productsBySku = [];
}

?>

<div class="container py-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="bi bi-bag-fill me-2"></i><?= __('shopping_cart', 'frontend', 'Koszyk') ?></h2>
                <a href="<?= $BASE ?>/index.php?page=search-results" class="btn btn-outline-primary">
                    <i class="bi bi-arrow-left me-1"></i><?= __('continue_shopping', 'frontend', 'Kontynuuj zakupy') ?>
                </a>
            </div>

            <div id="cart-container">
                <!-- Koszyk będzie renderowany przez JavaScript -->
                <div class="text-center py-5" id="empty-cart">
                    <i class="bi bi-bag-x display-1 text-muted mb-3"></i>
                    <h4><?= __('cart_empty', 'frontend', 'Twój koszyk jest pusty') ?></h4>
                    <p class="text-muted mb-4"><?= __('cart_empty_desc', 'frontend', 'Dodaj pojazdy do koszyka, aby rozpocząć rezerwację.') ?></p>
                    <a href="<?= $BASE ?>/index.php?page=search-results" class="btn btn-primary">
                        <?= __('browse_vehicles', 'frontend', 'Przeglądaj pojazdy') ?>
                    </a>
                </div>

                <div id="cart-items" style="display: none;">
                    <!-- Elementy koszyka -->
                </div>

                <div id="cart-summary" class="mt-4" style="display: none;">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle me-2"></i>
                                <?= __('cart_info', 'frontend', 'Ceny są orientacyjne. Finalna kwota zostanie obliczona w procesie rezerwacji z uwzględnieniem promocji i opłat dodatkowych.') ?>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title"><?= __('summary', 'frontend', 'Podsumowanie') ?></h5>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span><?= __('items', 'frontend', 'Pozycje') ?>:</span>
                                        <span id="cart-items-count">0</span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-3">
                                        <strong><?= __('estimated_total', 'frontend', 'Szacowana wartość') ?>:</strong>
                                        <strong id="cart-total">0,00 PLN</strong>
                                    </div>
                                    <button class="btn btn-success w-100" onclick="proceedToCheckout()">
                                        <?= __('proceed_to_checkout', 'frontend', 'Przejdź do rezerwacji') ?>
                                    </button>
                                    <button class="btn btn-outline-danger w-100 mt-2" onclick="clearCart()">
                                        <?= __('clear_cart', 'frontend', 'Wyczyść koszyk') ?>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const productsData = <?= json_encode($productsBySku) ?>;

        function renderCart() {
            const cart = window.cartManager.getCart();
            const emptyCart = document.getElementById('empty-cart');
            const cartItems = document.getElementById('cart-items');
            const cartSummary = document.getElementById('cart-summary');

            if (cart.length === 0) {
                emptyCart.style.display = 'block';
                cartItems.style.display = 'none';
                cartSummary.style.display = 'none';
                return;
            }

            emptyCart.style.display = 'none';
            cartItems.style.display = 'block';
            cartSummary.style.display = 'block';

            let html = '';
            let totalEstimate = 0;

            cart.forEach((item, index) => {
                const product = productsData[item.sku];
                const price = product ? parseFloat(product.price) : parseFloat(item.price);
                const days = calculateDays(item.pickup_at, item.return_at);
                const itemTotal = price * days;
                totalEstimate += itemTotal;

                html += `
                <div class="card mb-3">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <h5 class="mb-1">${escapeHtml(item.name)}</h5>
                                <p class="text-muted mb-1">SKU: ${escapeHtml(item.sku)}</p>
                                <small class="text-muted">
                                    <i class="bi bi-calendar-range me-1"></i>
                                    ${escapeHtml(item.pickup_at)} - ${escapeHtml(item.return_at)} (${days} dni)
                                </small><br>
                                <small class="text-muted">
                                    <i class="bi bi-geo-alt me-1"></i>
                                    ${escapeHtml(item.pickup_location)} → ${escapeHtml(item.dropoff_location)}
                                </small>
                                ${item.extras && item.extras.length > 0 ? `
                                    <br><small class="text-muted">
                                        <i class="bi bi-plus-circle me-1"></i>
                                        Dodatki: ${item.extras.join(', ')}
                                    </small>
                                ` : ''}
                            </div>
                            <div class="col-md-3 text-center">
                                <div class="fw-bold">${formatPrice(price)} PLN/dzień</div>
                                <div class="text-muted">${days} dni</div>
                                <div class="fw-bold text-primary">${formatPrice(itemTotal)} PLN</div>
                            </div>
                            <div class="col-md-3 text-end">
                                <a href="<?= $BASE ?>/index.php?page=product-details&sku=${item.sku}" class="btn btn-sm btn-outline-primary me-2">
                                    <i class="bi bi-pencil"></i> Edytuj
                                </a>
                                <button class="btn btn-sm btn-outline-danger" onclick="removeFromCart('${item.sku}')">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            });

            cartItems.innerHTML = html;
            document.getElementById('cart-items-count').textContent = cart.length;
            document.getElementById('cart-total').textContent = formatPrice(totalEstimate) + ' PLN';
        }

        function calculateDays(pickupAt, returnAt) {
            const pickup = new Date(pickupAt);
            const returnDate = new Date(returnAt);
            const diffTime = Math.abs(returnDate - pickup);
            return Math.max(1, Math.ceil(diffTime / (1000 * 60 * 60 * 24)));
        }

        function formatPrice(price) {
            return new Intl.NumberFormat('pl-PL', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }).format(price);
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Global functions for buttons
        window.removeFromCart = function(sku) {
            window.cartManager.removeFromCart(sku);
            renderCart();
        };

        window.clearCart = function() {
            if (confirm('<?= __('confirm_clear_cart', 'frontend', 'Czy na pewno chcesz wyczyścić koszyk?') ?>')) {
                window.cartManager.clearCart();
                renderCart();
            }
        };

        window.proceedToCheckout = function() {
            const cart = window.cartManager.getCart();
            if (cart.length === 0) {
                alert('<?= __('cart_empty_checkout', 'frontend', 'Koszyk jest pusty') ?>');
                return;
            }

            // Dla pojedynczego elementu - przejdź bezpośrednio do checkout
            if (cart.length === 1) {
                const item = cart[0];
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '<?= $BASE ?>/index.php?page=checkout';

                const fields = {
                    'sku': item.sku,
                    'pickup_at': item.pickup_at,
                    'return_at': item.return_at,
                    'pickup_location': item.pickup_location,
                    'dropoff_location': item.dropoff_location
                };

                if (item.extras) {
                    item.extras.forEach(extra => {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'extra[]';
                        input.value = extra;
                        form.appendChild(input);
                    });
                }

                Object.entries(fields).forEach(([name, value]) => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = name;
                    input.value = value;
                    form.appendChild(input);
                });

                document.body.appendChild(form);
                form.submit();
            } else {
                // Dla wielu elementów - info o ograniczeniu
                alert('<?= __('multiple_items_info', 'frontend', 'Obecnie system obsługuje rezerwację jednego pojazdu na raz. Proszę usunąć inne pozycje z koszyka.') ?>');
            }
        };

        // Nasłuchuj zmian koszyka
        window.addEventListener('cartUpdated', renderCart);

        // Initial render
        renderCart();
    });
</script>

<style>
    .cart-icon .bi-bag-fill {
        font-size: 1.2rem;
    }

    .cart-count {
        font-size: 0.7rem;
        line-height: 1;
        min-width: 18px;
        height: 18px;
    }
</style>