<?php
// /index.php

// Start sesji (raz, bez duplikatów)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Konfiguracja i auth (BASE_URL, db, helpery usera)
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/auth/auth.php';

// Prosty router po ?page=
$page = $_GET['page'] ?? 'home';

// Biała lista widoków -> pliki
$routes = [
    'home'             => __DIR__ . '/pages/home.php',
    'login'            => __DIR__ . '/pages/login.php',
    'dashboard-client' => __DIR__ . '/pages/dashboard-client.php',
    'dashboard-staff'  => __DIR__ . '/pages/dashboard-staff.php',
    // NOWE: strona wyników wyszukiwania (bez hero)
    'search-results'   => __DIR__ . '/pages/search-results.php',
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
        <?php
        // Wczytaj stronę (każdy dashboard sam pilnuje uprawnień: require_auth()/require_staff())
        include $viewFile;
        ?>
    </main>

    <?php
    include __DIR__ . '/components/back-to-top.php';
    include __DIR__ . '/components/site-footer.php';
    include __DIR__ . '/partials/footer.php';
    ?>
</body>

</html>