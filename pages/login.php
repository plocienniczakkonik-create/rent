<?php
// /pages/login.php
require_once dirname(__DIR__) . '/partials/head.php';
?>

<style>
  /* Cała strona (nav + main + footer) w 100vh bez scrolla */
  html, body { height: 100%; }
  body { min-height: 100vh; display: flex; flex-direction: column; margin: 0; }

  /* Na loginie nawigacja ma być w normalnym flow (nie absolute) */
  #siteNav { position: static !important; }

  /* Main wypełnia przestrzeń między nav i footerem i centruje formularz */
  main { flex: 1 0 auto; display: flex; align-items: center; justify-content: center; padding: 24px 16px; }

  /* (opcjonalnie lepsze zachowanie na mobile) */
  @supports (min-height: 100dvh) { body { min-height: 100dvh; } }
</style>

<?php
require_once dirname(__DIR__) . '/partials/header.php';

$BASE = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
$defaultRedirect = ($BASE ? $BASE . '/' : '') . 'index.php?page=dashboard-client';
$redirect  = $_GET['redirect'] ?? $defaultRedirect;
$err       = $_GET['e'] ?? null;
$loggedOut = isset($_GET['out']);
?>

<main>
  <div class="container" style="max-width:520px;">
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
  </div>
</main>

<?php require_once dirname(__DIR__) . '/partials/footer.php'; ?>
