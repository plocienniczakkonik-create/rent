<?php
// /partials/header.php
require_once __DIR__ . '/../auth/auth.php';

// Helper: jeżeli nie masz jeszcze tej funkcji w auth.php
if (!function_exists('is_logged_in')) {
    function is_logged_in(): bool
    {
        return (bool) current_user();
    }
}

$u = current_user();
$role = $u['role'] ?? 'client'; // jeśli nie masz kolumny 'role' w DB, domyślnie 'client'
?>

<nav id="siteNav" class="navbar navbar-expand-lg navbar-light bg-transparent position-absolute top-0 start-0 w-100 z-3">
    <div class="container-fluid px-3 px-lg-5">
        <a class="navbar-brand fw-semibold" href="index.php"><b>CORONA</b></a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
            aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            <!-- LEWE MENU -->
            <ul class="navbar-nav">
                <!-- HOME → index.php -->
                <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/index.php">HOME</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/index.php#offer">OFERTA</a></li>
                <li class="nav-item"><a class="nav-link" href="index.php#offer">ZAREZERWUJ</a></li>
                <li class="nav-item"><a class="nav-link" href="index.php?page=extras">DODATKI</a></li>
                <li class="nav-item"><a class="nav-link" href="index.php?page=contact">KONTAKT</a></li>
            </ul>
            <ul class="navbar-nav ms-auto align-items-center">
                <?php if ($u): ?>
                    <li class="nav-item me-2 d-none d-lg-block">
                        <span class="nav-link text-dark small opacity-75">
                            Witaj, <?= htmlspecialchars($u['first_name'] ?? $u['email'] ?? 'Użytkowniku') ?>
                        </span>
                    </li>

                    <li class="nav-item">
                        <?php if ($role === 'staff'): ?>
                            <a class="nav-link" href="<?= BASE_URL ?>/index.php?page=dashboard-staff">Panel</a>
                        <?php else: ?>
                            <a class="nav-link" href="<?= BASE_URL ?>/index.php?page=dashboard-client">Panel</a>
                        <?php endif; ?>
                    </li>

                    <li class="nav-item ms-2">
                        <a class="btn btn-sm btn-outline-dark rounded-pill" href="<?= BASE_URL ?>/auth/logout.php">Wyloguj</a>
                    </li>
                <?php else: ?>
                    <li class="nav-item ms-2">
                        <a class="btn btn-md btn-dark rounded-pill" href="<?= BASE_URL ?>/index.php?page=login">Zaloguj</a>
                    </li>
                <?php endif; ?>
            </ul>

        </div>
    </div>
</nav>