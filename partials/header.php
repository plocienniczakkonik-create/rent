<?php
// /partials/header.php
require_once __DIR__ . '/../auth/auth.php';

if (!function_exists('is_logged_in')) {
    function is_logged_in(): bool { return (bool) current_user(); }
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
    <a class="navbar-brand fw-semibold" href="<?= BASE_URL ?>/index.php"><b>CORONA</b></a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
      aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarNav">
      <!-- LEWE MENU -->
      <ul class="navbar-nav">
        <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/index.php">HOME</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/index.php#offer">OFERTA</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/index.php#offer">ZAREZERWUJ</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/index.php?page=extras">DODATKI</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/index.php?page=contact">KONTAKT</a></li>
      </ul>

      <!-- PRAWA STRONA -->
      <ul class="navbar-nav ms-auto align-items-center">
        <?php if ($u): ?>
          <li class="nav-item me-2 d-none d-lg-block">
            <span class="nav-link text-dark small opacity-75">
              Witaj, <?= htmlspecialchars($u['first_name'] ?? $u['email'] ?? 'Użytkowniku') ?>
            </span>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="<?= BASE_URL ?>/index.php?page=<?= $role === 'staff' ? 'dashboard-staff' : 'dashboard-client' ?>">Panel</a>
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
