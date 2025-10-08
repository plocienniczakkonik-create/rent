<?php
// /partials/header.php
require_once __DIR__ . '/../auth/auth.php';

// Initialize i18n system
if (!class_exists('i18n')) {
    require_once __DIR__ . '/../includes/i18n.php';
}
i18n::init(); // Always reinitialize to ensure current language settings

if (!function_exists('is_logged_in')) {
    function is_logged_in(): bool
    {
        return (bool) current_user();
    }
}

$u    = current_user();
$role = $u['role'] ?? 'client';

// Bezpieczne wykrycie "HOME":
// - jeśli korzystasz z routera index.php?page=home → overlay
// - jeśli brak page, ale to index.php → overlay
// - jeśli to jakakolwiek inna ścieżka (np. /pages/login.php) → brak overlay
$script   = basename($_SERVER['SCRIPT_NAME'] ?? '');
$isIndex  = ($script === 'index.php');
$page     = $_GET['page'] ?? ($isIndex ? 'home' : 'other');
$overlay  = ($page === 'home');

$navPosClass = $overlay
    ? 'position-absolute top-0 start-0 w-100 z-3'   // overlay tylko na HOME
    : 'position-sticky top-0 bg-white shadow-sm';    // sticky na pozostałych stronach

// Dodaj z-index dla dashboard pages
$isDashboard = (isset($_GET['page']) && strpos($_GET['page'], 'dashboard') !== false);
if ($isDashboard && !$overlay) {
    $navPosClass .= ' dashboard-nav-fix';
}
?>

<nav id="siteNav" class="navbar navbar-expand-lg navbar-light bg-transparent <?= $navPosClass ?>">
    <div class="container-fluid px-3 px-lg-5">
        <a class="navbar-brand fw-semibold" href="<?= BASE_URL ?>/index.php"><?= theme_render_brand('', false) ?></a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
            aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            <!-- LEWE MENU -->
            <ul class="navbar-nav">
                <li class="nav-item"><a class="nav-link <?= ($page === 'home' || $page === '') ? 'fw-bold text-primary' : '' ?>" href="<?= BASE_URL ?>/index.php"><?= __('nav_home', 'frontend', 'HOME') ?></a></li>
                <li class="nav-item"><a class="nav-link <?= ($page === 'search-results') ? 'fw-bold text-primary' : '' ?>" href="<?= BASE_URL ?>/index.php?page=search-results"><?= __('nav_offer', 'frontend', 'OFERTA') ?></a></li>
                <li class="nav-item"><a class="nav-link <?= ($page === 'reserve' || $page === 'checkout' || $page === 'product-details') ? 'fw-bold text-primary' : '' ?>" href="<?= BASE_URL ?>/index.php?page=search-results"><?= __('nav_reserve', 'frontend', 'ZAREZERWUJ') ?></a></li>
                <li class="nav-item"><a class="nav-link <?= ($page === 'extras') ? 'fw-bold text-primary' : '' ?>" href="<?= BASE_URL ?>/index.php?page=extras"><?= __('nav_extras', 'frontend', 'DODATKI') ?></a></li>
                <li class="nav-item"><a class="nav-link <?= ($page === 'contact') ? 'fw-bold text-primary' : '' ?>" href="<?= BASE_URL ?>/index.php?page=contact"><?= __('nav_contact', 'frontend', 'KONTAKT') ?></a></li>
            </ul>

            <!-- PRAWA STRONA -->
            <ul class="navbar-nav ms-auto align-items-center">
                <!-- Ikona koszyka -->
                <li class="nav-item me-3">
                    <button type="button" class="nav-link btn btn-link position-relative cart-icon" id="cart-toggle-btn" style="border: none; background: none; padding: 0.5rem 0.75rem;">
                        <i class="bi bi-bag fs-5" id="cart-icon-outline"></i>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger cart-count" id="cart-count" style="display: none;">
                            0
                        </span>
                    </button>
                </li>
                <?php if ($u): ?>
                    <!-- Language Switcher dla zalogowanych -->
                    <li class="nav-item me-3">
                        <?php
                        // Include i18n if not already included
                        if (!class_exists('i18n')) {
                            require_once __DIR__ . '/../includes/i18n.php';
                            i18n::init();
                        }
                        echo i18n::renderLanguageSwitcher('both', $_SERVER['REQUEST_URI']);
                        echo "<script>window.languageJustChanged = true; setTimeout(function(){window.languageJustChanged = false;}, 2000);</script>";
                        ?>
                    </li>
                    <li class="nav-item me-2 d-none d-lg-block">
                        <span class="nav-link text-dark small opacity-75">
                            <?= __('welcome', 'frontend', 'Witaj') ?>, <?= htmlspecialchars($u['first_name'] ?? $u['email'] ?? __('user', 'frontend', 'Użytkowniku')) ?>
                        </span>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= BASE_URL ?>/index.php?page=<?= in_array($role, ['staff', 'admin']) ? 'dashboard-staff' : 'dashboard-client' ?>"><?= __('dashboard', 'frontend', 'Panel') ?></a>
                    </li>
                    <li class="nav-item">
                        <button id="themeToggleBtn" type="button" class="nav-link btn btn-link px-2 py-1 theme-toggle-btn" aria-label="Przełącz tryb ciemny/jasny">
                            <i id="themeToggleIcon" class="bi bi-moon" aria-hidden="true"></i>
                        </button>
                    </li>
                    <li class="nav-item ms-2">
                        <a class="btn btn-sm btn-outline-dark rounded-pill" href="<?= BASE_URL ?>/auth/logout.php"><?= __('logout', 'frontend', 'Wyloguj') ?></a>
                    </li>
                <?php else: ?>
                    <!-- Language Switcher dla niezalogowanych -->
                    <li class="nav-item me-3">
                        <?php
                        // Include i18n if not already included
                        if (!class_exists('i18n')) {
                            require_once __DIR__ . '/../includes/i18n.php';
                            i18n::init();
                        }
                        echo i18n::renderLanguageSwitcher('frontend', $_SERVER['REQUEST_URI']);
                        echo "<script>window.languageJustChanged = true; setTimeout(function(){window.languageJustChanged = false;}, 2000);</script>";
                        ?>
                    </li>
                    <li class="nav-item">
                        <button id="themeToggleBtn" type="button" class="nav-link btn btn-link px-2 py-1 theme-toggle-btn" aria-label="Przełącz tryb ciemny/jasny">
                            <i id="themeToggleIcon" class="bi bi-moon" aria-hidden="true"></i>
                        </button>
                        <style>
                            .theme-toggle-btn {
                                background: none;
                                border: 2px solid #111;
                                border-radius: 50%;
                                outline: none;
                                box-shadow: none;
                                font-size: 1.35em;
                                line-height: 1;
                                width: 2.2em;
                                height: 2.2em;
                                display: flex;
                                align-items: center;
                                justify-content: center;
                                padding: 0;
                                transition: border-color 0.2s, background 0.2s;
                            }

                            .theme-toggle-btn:hover,
                            .theme-toggle-btn:focus {
                                border-color: #8b5cf6;
                                background: #f3f4f6;
                            }

                            .theme-toggle-btn #themeToggleIcon {
                                color: #111;
                                transition: color 0.2s;
                            }

                            [data-theme="dark"] .theme-toggle-btn {
                                border-color: #fff;
                                background: #23272a;
                            }

                            [data-theme="dark"] .theme-toggle-btn #themeToggleIcon {
                                color: #fff;
                            }
                        </style>
                    </li>
                    <li class="nav-item ms-2">
                        <a class="btn btn-md btn-dark rounded-pill" href="<?= BASE_URL ?>/index.php?page=login"><?= __('login', 'frontend', 'Zaloguj') ?></a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<script>
    // Cart Management System
    class CartManager {
        constructor() {
            this.cartKey = 'rental_cart';
            this.init();
        }

        init() {
            this.updateCartDisplay();

            // Nasłuchuj zmian w localStorage (dla synchronizacji między kartami)
            window.addEventListener('storage', (e) => {
                if (e.key === this.cartKey) {
                    this.updateCartDisplay();
                }
            });
        }

        getCart() {
            try {
                return JSON.parse(localStorage.getItem(this.cartKey) || '[]');
            } catch {
                return [];
            }
        }

        saveCart(cart) {
            localStorage.setItem(this.cartKey, JSON.stringify(cart));
            this.updateCartDisplay();

            // Wyślij custom event dla innych komponentów
            window.dispatchEvent(new CustomEvent('cartUpdated', {
                detail: {
                    cart
                }
            }));
        }

        addToCart(item) {
            const cart = this.getCart();
            const existingIndex = cart.findIndex(cartItem => cartItem.sku === item.sku);

            if (existingIndex >= 0) {
                // Aktualizuj istniejący element
                cart[existingIndex] = {
                    ...cart[existingIndex],
                    ...item
                };
            } else {
                // Dodaj nowy element
                cart.push({
                    sku: item.sku,
                    name: item.name,
                    price: item.price,
                    pickup_at: item.pickup_at,
                    return_at: item.return_at,
                    pickup_location: item.pickup_location,
                    dropoff_location: item.dropoff_location,
                    extras: item.extras || [],
                    added_at: new Date().toISOString()
                });
            }

            this.saveCart(cart);
            this.showCartNotification('Dodano do koszyka!');
        }

        removeFromCart(sku) {
            const cart = this.getCart().filter(item => item.sku !== sku);
            this.saveCart(cart);
            this.showCartNotification('Usunięto z koszyka');
        }

        clearCart() {
            this.saveCart([]);
        }

        updateCartDisplay() {
            const cart = this.getCart();
            const cartCount = document.getElementById('cart-count');

            if (cartCount) {
                if (cart.length > 0) {
                    cartCount.textContent = cart.length;
                    cartCount.style.display = 'inline-block';
                } else {
                    cartCount.style.display = 'none';
                }
            }
        }

        showCartNotification(message) {
            // Simple toast notification
            const toast = document.createElement('div');
            toast.className = 'toast-notification';
            toast.textContent = message;
            toast.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: #28a745;
            color: white;
            padding: 12px 20px;
            border-radius: 8px;
            z-index: 9999;
            animation: slideIn 0.3s ease-out;
        `;

            document.body.appendChild(toast);

            setTimeout(() => {
                toast.style.animation = 'slideOut 0.3s ease-out';
                setTimeout(() => toast.remove(), 300);
            }, 2000);
        }
    }

    // Initialize cart manager
    window.cartManager = new CartManager();

    // CSS dla animacji toast
    const style = document.createElement('style');
    style.textContent = `
    @keyframes slideIn {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    @keyframes slideOut {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(100%); opacity: 0; }
    }
    .cart-icon .bi-bag-fill {
        transition: color 0.2s ease;
    }
    .cart-icon:hover .bi-bag-fill {
        color: var(--bs-primary, #0d6efd) !important;
    }
    
    /* Dashboard Navigation Fix */
    .dashboard-nav-fix {
        z-index: 1000 !important;
    }
    
    /* Cart Sidebar Styles */
    .cart-sidebar-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        z-index: 9998;
        opacity: 0;
        visibility: hidden;
        transition: all 0.3s ease;
    }
    
    .cart-sidebar-overlay.show {
        opacity: 1;
        visibility: visible;
    }
    
    .cart-sidebar {
        position: fixed;
        top: 0;
        right: 0;
        width: 400px;
        height: 100vh;
        background: white;
        z-index: 9999;
        transform: translateX(100%);
        transition: transform 0.3s ease;
        box-shadow: -2px 0 10px rgba(0, 0, 0, 0.1);
        display: flex;
        flex-direction: column;
    }
    
    .cart-sidebar.show {
        transform: translateX(0);
    }
    
    .cart-sidebar-header {
        padding: 1.25rem;
        border-bottom: 1px solid #dee2e6;
        background: #f8f9fa;
    }
    
    .cart-sidebar-body {
        flex: 1;
        overflow-y: auto;
        padding: 0;
    }
    
    .cart-sidebar-footer {
        padding: 1.25rem;
        border-top: 1px solid #dee2e6;
        background: #f8f9fa;
    }
    
    .cart-item {
        padding: 1rem;
        border-bottom: 1px solid #f0f0f0;
        display: flex;
        gap: 0.75rem;
    }
    
    .cart-item:last-child {
        border-bottom: none;
    }
    
    .cart-item-image {
        width: 60px;
        height: 45px;
        background: #f8f9fa;
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        border: 1px solid #dee2e6;
    }
    
    .cart-item-details {
        flex: 1;
        min-width: 0;
    }
    
    .cart-item-name {
        font-weight: 600;
        font-size: 0.9rem;
        margin-bottom: 0.25rem;
        line-height: 1.3;
    }
    
    .cart-item-info {
        font-size: 0.75rem;
        color: #6c757d;
        margin-bottom: 0.25rem;
    }
    
    .cart-item-price {
        font-weight: 600;
        color: #28a745;
        font-size: 0.85rem;
    }
    
    .cart-empty-state {
        text-align: center;
        padding: 3rem 1.5rem;
        color: #6c757d;
    }
    
    @media (max-width: 480px) {
        .cart-sidebar {
            width: 100vw;
        }
    }
`;
    document.head.appendChild(style);

    // Cart Sidebar Management
    class CartSidebar {
        constructor() {
            this.isOpen = false;
            this.createSidebar();
            this.bindEvents();
        }

        createSidebar() {
            const sidebarHTML = `
            <!-- Cart Sidebar Overlay -->
            <div class="cart-sidebar-overlay" id="cart-sidebar-overlay"></div>
            
            <!-- Cart Sidebar -->
            <div class="cart-sidebar" id="cart-sidebar">
                <div class="cart-sidebar-header">
                    <div class="d-flex align-items-center justify-content-between w-100">
                        <h5 class="mb-0">
                            <i class="bi bi-bag-fill me-2"></i>Twój koszyk
                        </h5>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="close-cart-sidebar">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                </div>
                <div class="cart-sidebar-body" id="cart-sidebar-content">
                    <!-- Content will be populated by JavaScript -->
                </div>
                <div class="cart-sidebar-footer" id="cart-sidebar-footer" style="display: none;">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="fw-bold">Razem:</span>
                        <span class="fw-bold text-success" id="cart-sidebar-total">0,00 PLN</span>
                    </div>
                    <div class="d-grid gap-2">
                        <a href="<?= BASE_URL ?>/index.php?page=cart" class="btn btn-outline-primary btn-sm">
                            Zobacz koszyk
                        </a>
                        <button type="button" class="btn btn-success btn-sm" onclick="proceedToCheckoutFromSidebar()">
                            Przejdź do rezerwacji
                        </button>
                    </div>
                </div>
            </div>
        `;

            document.body.insertAdjacentHTML('beforeend', sidebarHTML);
        }

        bindEvents() {
            const toggleBtn = document.getElementById('cart-toggle-btn');
            const overlay = document.getElementById('cart-sidebar-overlay');
            const closeBtn = document.getElementById('close-cart-sidebar');

            if (toggleBtn) {
                toggleBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    this.toggle();
                });
            }

            if (overlay) {
                overlay.addEventListener('click', () => this.close());
            }

            if (closeBtn) {
                closeBtn.addEventListener('click', () => this.close());
            }

            // ESC key to close
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && this.isOpen) {
                    this.close();
                }
            });

            // Update sidebar when cart changes
            window.addEventListener('cartUpdated', () => {
                if (this.isOpen) {
                    this.updateContent();
                }
            });
        }

        toggle() {
            if (this.isOpen) {
                this.close();
            } else {
                this.open();
            }
        }

        open() {
            this.isOpen = true;
            document.body.style.overflow = 'hidden';
            document.getElementById('cart-sidebar-overlay').classList.add('show');
            document.getElementById('cart-sidebar').classList.add('show');
            this.updateContent();
        }

        close() {
            this.isOpen = false;
            document.body.style.overflow = '';
            document.getElementById('cart-sidebar-overlay').classList.remove('show');
            document.getElementById('cart-sidebar').classList.remove('show');
        }

        updateContent() {
            const cart = window.cartManager.getCart();
            const contentEl = document.getElementById('cart-sidebar-content');
            const footerEl = document.getElementById('cart-sidebar-footer');
            const totalEl = document.getElementById('cart-sidebar-total');

            if (cart.length === 0) {
                contentEl.innerHTML = `
                <div class="cart-empty-state">
                    <i class="bi bi-bag-x display-6 text-muted mb-3"></i>
                    <h6 class="text-muted">Koszyk jest pusty</h6>
                    <p class="text-muted small">Dodaj pojazdy do koszyka, aby rozpocząć rezerwację.</p>
                </div>
            `;
                footerEl.style.display = 'none';
                return;
            }

            let html = '';
            let total = 0;

            cart.forEach(item => {
                const days = this.calculateDays(item.pickup_at, item.return_at);
                const itemTotal = parseFloat(item.price) * days;
                total += itemTotal;

                html += `
                <div class="cart-item">
                    <div class="cart-item-image">
                        <i class="bi bi-car-front text-muted"></i>
                    </div>
                    <div class="cart-item-details">
                        <div class="cart-item-name">${this.escapeHtml(item.name)}</div>
                        <div class="cart-item-info">
                            <i class="bi bi-calendar-range"></i> ${this.formatDate(item.pickup_at)} - ${this.formatDate(item.return_at)}
                        </div>
                        <div class="cart-item-info">
                            <i class="bi bi-geo-alt"></i> ${this.escapeHtml(item.pickup_location)} → ${this.escapeHtml(item.dropoff_location)}
                        </div>
                        ${item.extras && item.extras.length > 0 ? `
                            <div class="cart-item-info">
                                <i class="bi bi-plus-circle"></i> ${item.extras.length} dodatek(ów)
                            </div>
                        ` : ''}
                        <div class="cart-item-price">${this.formatPrice(itemTotal)} PLN (${days} dni)</div>
                    </div>
                    <div class="flex-shrink-0">
                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeFromCartSidebar('${item.sku}')" title="Usuń">
                            <i class="bi bi-trash3"></i>
                        </button>
                    </div>
                </div>
            `;
            });

            contentEl.innerHTML = html;
            totalEl.textContent = this.formatPrice(total) + ' PLN';
            footerEl.style.display = 'block';
        }

        calculateDays(pickupAt, returnAt) {
            const pickup = new Date(pickupAt);
            const returnDate = new Date(returnAt);
            const diffTime = Math.abs(returnDate - pickup);
            return Math.max(1, Math.ceil(diffTime / (1000 * 60 * 60 * 24)));
        }

        formatPrice(price) {
            return new Intl.NumberFormat('pl-PL', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }).format(price);
        }

        formatDate(dateStr) {
            const date = new Date(dateStr);
            return date.toLocaleDateString('pl-PL', {
                day: '2-digit',
                month: '2-digit',
                hour: '2-digit',
                minute: '2-digit'
            });
        }

        escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    }

    // Global functions for sidebar
    window.removeFromCartSidebar = function(sku) {
        window.cartManager.removeFromCart(sku);
    };

    window.proceedToCheckoutFromSidebar = function() {
        const cart = window.cartManager.getCart();
        if (cart.length === 0) {
            alert('Koszyk jest pusty');
            return;
        }

        if (cart.length === 1) {
            // Redirect to checkout for single item
            const item = cart[0];
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '<?= BASE_URL ?>/index.php?page=checkout';

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
            // Multiple items - go to cart page
            window.location.href = '<?= BASE_URL ?>/index.php?page=cart';
        }
    };

    // Initialize cart sidebar
    document.addEventListener('DOMContentLoaded', function() {
        window.cartSidebar = new CartSidebar();
    });
</script>

<!-- Cart Sidebar will be inserted here by JavaScript -->