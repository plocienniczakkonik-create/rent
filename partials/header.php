<?php require_once __DIR__ . '/../auth/auth.php'; ?>

<nav id="siteNav" class="navbar navbar-expand-lg navbar-light bg-transparent position-absolute top-0 start-0 w-100 z-3">
    <div class="container-fluid px-3 px-lg-5">
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
            aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav">
                <!-- HOME → index.php -->
                <li class="nav-item"><a class="nav-link" href="index.php">HOME</a></li>
                <li class="nav-item"><a class="nav-link" href="#offer">OFERTA</a></li>
                <li class="nav-item"><a class="nav-link" href="#offer">ZAREZERWUJ</a></li>
                <li class="nav-item"><a class="nav-link" href="#">DODATKI</a></li>
                <li class="nav-item"><a class="nav-link" href="#">KONTAKT</a></li>
            </ul>

            <!-- PRAWY BLOK: login/panel/wyloguj -->
            <ul class="navbar-nav ms-auto align-items-center">
                <?php if (is_logged_in()): ?>
                    <?php if (current_user()['role'] === 'staff'): ?>
                        <li class="nav-item"><a class="nav-link" href="index.php?page=dashboard-staff">Panel</a></li>
                    <?php else: ?>
                        <li class="nav-item"><a class="nav-link" href="index.php?page=dashboard-client">Panel</a></li>
                    <?php endif; ?>
                    <li class="nav-item ms-2">
                        <a class="btn btn-sm btn-outline-light rounded-pill" href="auth/logout.php">Wyloguj</a>
                    </li>
                <?php else: ?>
                    <li class="nav-item ms-2">
                        <a class="btn btn-md btn-light rounded-pill" href="index.php?page=login">Zaloguj</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>