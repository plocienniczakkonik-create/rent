<?php
// /pages/login.php
require_once dirname(__DIR__) . '/partials/head.php';
require_once dirname(__DIR__) . '/partials/header.php';

// BASE URL (jeśli zdefiniowane w includes/config.php)
$BASE = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';

// Domyślny redirect po zalogowaniu (do panelu klienta przez router)
$defaultRedirect = ($BASE ? $BASE . '/' : '') . 'index.php?page=dashboard-client';

// Odczytaj redirect z query string lub przyjmij domyślny
$redirect  = $_GET['redirect'] ?? $defaultRedirect;
$err       = $_GET['e'] ?? null;
$loggedOut = isset($_GET['out']);
?>

<main class="container py-5" style="max-width: 520px;">
    <h1 class="h3 mb-3">Logowanie</h1>

    <?php if ($err === 'empty'): ?>
        <div class="alert alert-warning">Wpisz e-mail i hasło.</div>
    <?php elseif ($err === 'invalid'): ?>
        <div class="alert alert-danger">Nieprawidłowe dane logowania.</div>
    <?php elseif ($loggedOut): ?>
        <div class="alert alert-success">Zostałeś wylogowany.</div>
    <?php endif; ?>

    <form method="post" action="<?= $BASE ? ($BASE . '/auth/login-handler.php') : 'auth/login-handler.php' ?>" class="card p-3">
        <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect) ?>">
        <div class="mb-3">
            <label class="form-label">E-mail</label>
            <input type="email" name="email" class="form-control" required autocomplete="username">
        </div>
        <div class="mb-3">
            <label class="form-label">Hasło</label>
            <input type="password" name="password" class="form-control" required autocomplete="current-password">
        </div>
        <button type="submit" class="btn btn-primary w-100">Zaloguj</button>
    </form>
</main>

<?php require_once dirname(__DIR__) . '/partials/footer.php'; ?>