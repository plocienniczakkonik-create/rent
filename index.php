<?php
// Obsługa zmiany języka na samym początku
if (isset($_GET['set_language']) && isset($_GET['context'])) {
    session_start();

    $language = $_GET['set_language'];
    $context = $_GET['context'];
    $return_url = $_GET['return'] ?? $_SERVER['HTTP_REFERER'] ?? '/rental/index.php';

    // Ustaw język w sesji
    if ($context === 'admin') {
        $_SESSION['admin_language'] = $language;
    } elseif ($context === 'both') {
        $_SESSION['frontend_language'] = $language;
        $_SESSION['admin_language'] = $language;
    } else {
        $_SESSION['frontend_language'] = $language;
    }

    // Przekieruj z powrotem, usuwając parametry języka
    $return_url = preg_replace('/[?&]set_language=[^&]*/', '', $return_url);
    $return_url = preg_replace('/[?&]context=[^&]*/', '', $return_url);
    $return_url = preg_replace('/[?&]return=[^&]*/', '', $return_url);

    header('Location: ' . $return_url);
    exit;
}

// /index.ph// 4) Dalej standardowe include'y
require_once __DIR__ . '/auth/auth.php';
require_once __DIR__ . '/includes/i18n.php';

// Initialize i18n system AFTER auth is loaded
i18n::init();

// CSRF helper tylko z auth.php
// 1) Najpierw config (żeby mieć BASE_URL)
require_once __DIR__ . '/includes/config.php';

// 2) Ustaw ścieżkę ciasteczka sesji na BASE_URL (ważne przy subfolderze, np. /rental)
if (defined('BASE_URL')) {
    @ini_set('session.cookie_path', rtrim((string)BASE_URL, '/') . '/');
}
// (opcjonalnie, ale pomocne)
@ini_set('session.cookie_httponly', '1');
@ini_set('session.use_strict_mode', '1');

// 3) Start sesji DOPIERO teraz (raz, bez duplikatów)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 4) Dalej standardowe include’y
require_once __DIR__ . '/auth/auth.php';

// CSRF helper tylko z auth.php


// Prosty router po ?page=
$page = $_GET['page'] ?? 'home';

/**
 * === AKCJE (POST/GET) — obsługujemy PRZED renderem HTML ===
 * Akcje nie renderują layoutu — wykonują logikę i same robią redirect.
 */
$actionRoutes = [
    'vehicle-save'            => __DIR__ . '/pages/vehicle-save.php',
    'vehicle-delete'          => __DIR__ . '/pages/vehicle-delete.php', // <— DODANE wcześniej przez Ciebie

    // >>> DODANE (serwisy i kolizje)
    'vehicle-service-save'    => __DIR__ . '/pages/vehicle-service-save.php',
    'vehicle-service-delete'  => __DIR__ . '/pages/vehicle-service-delete.php',
    'vehicle-incident-save'   => __DIR__ . '/pages/vehicle-incident-save.php',
    'vehicle-incident-delete' => __DIR__ . '/pages/vehicle-incident-delete.php',
    // >>> DODANE (zamówienia/wynajem)
    'vehicle-order-save'      => __DIR__ . '/pages/vehicle-order-save.php',
    'vehicle-order-delete'    => __DIR__ . '/pages/vehicle-order-delete.php',
    // rezerwacje (status)
    'reservation-status-save' => __DIR__ . '/pages/reservation-status-save.php',

    // Produkty (modele pojazdów)
    'product-save'            => __DIR__ . '/pages/product-save.php',
    'product-delete'          => __DIR__ . '/pages/product-delete.php',
];

// 1) Biała lista akcji
if (isset($actionRoutes[$page])) {
    require $actionRoutes[$page];
    exit;
}

// 2) Auto-detekcja akcji: jeśli nazwa kończy się na -save lub -delete
//    i istnieje plik /pages/<page>.php, traktuj jako akcję.
if (preg_match('/-(save|delete)$/', $page)) {
    $maybeActionFile = __DIR__ . '/pages/' . $page . '.php';
    if (is_file($maybeActionFile)) {
        require $maybeActionFile;
        exit;
    }
}

/** === WIDOKI (render z layoutem) === */
// Dodaj widok rezerwacji pojazdu
$routes = [
    'home'             => __DIR__ . '/pages/home.php',
    'extras'           => __DIR__ . '/pages/extras.php',
    'contact'          => __DIR__ . '/pages/contact.php',
    'privacy-policy'   => __DIR__ . '/pages/privacy-policy.php',
    'terms'            => __DIR__ . '/pages/terms.php',
    'theme-admin'      => __DIR__ . '/pages/theme-admin.php',
    'login'            => __DIR__ . '/pages/login.php',
    'dashboard-client' => __DIR__ . '/pages/dashboard-client.php',
    'dashboard-staff'  => __DIR__ . '/pages/dashboard-staff.php',
    'search-results'   => __DIR__ . '/pages/search-results.php',

    // Flota / Pojazdy (widoki)
    'vehicles'         => __DIR__ . '/pages/vehicles.php',
    'vehicles-manage'  => __DIR__ . '/pages/vehicles-manage.php',
    'vehicle-form'     => __DIR__ . '/pages/vehicle-form.php',
    'vehicle-detail'   => __DIR__ . '/pages/vehicle-detail.php',
    'reserve'          => __DIR__ . '/pages/reserve.php',
    'checkout'         => __DIR__ . '/pages/checkout.php',
    'checkout-confirm' => __DIR__ . '/pages/checkout-confirm.php',
    'reservation-details' => __DIR__ . '/pages/reservation-details.php',

    // Produkty (modele pojazdów)
    'product-form'     => __DIR__ . '/pages/product-form.php',

    // >>> DODANE (formularze serwisów i kolizji)
    'vehicle-service-form'   => __DIR__ . '/pages/vehicle-service-form.php',
    'vehicle-incident-form'  => __DIR__ . '/pages/vehicle-incident-form.php',
    // >>> DODANE (zamówienia/wynajem)
    'vehicle-order-form'     => __DIR__ . '/pages/vehicle-order-form.php',
];

// Fallback na home, jeśli nieznana strona
$viewFile = $routes[$page] ?? $routes['home'];

// Head (doctype, <head> itd.)
include __DIR__ . '/partials/head.php';
?>

<body id="top">
    <div id="navSentinel" style="position:absolute; top:0; left:0; right:0; height:1px;"></div>

    <?php include __DIR__ . '/partials/header.php'; ?>

    <main class="site-main">
        <?php include $viewFile; ?>
    </main>

    <?php
    include __DIR__ . '/components/back-to-top.php';
    include __DIR__ . '/components/site-footer.php';
    include __DIR__ . '/partials/footer.php';
    ?>
</body>

</html>