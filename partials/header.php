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
    : 'position-static';                             // normalnie w flow na pozostałych
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
                        ?>
                    </li>
                    <li class="nav-item ms-2">
                        <a class="btn btn-md btn-dark rounded-pill" href="<?= BASE_URL ?>/index.php?page=login"><?= __('login', 'frontend', 'Zaloguj') ?></a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>