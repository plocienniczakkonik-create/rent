<?php
require_once dirname(__DIR__) . '/partials/head.php';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/i18n.php';
i18n::init();
require_once dirname(__DIR__) . '/partials/header.php';

$success = false;
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Podaj poprawny adres e-mail.';
    } else {
        // Tu można dodać wysyłkę maila z linkiem resetującym hasło
        $success = true;
    }
}
?>
<style>
  main { flex: 1 0 auto; display: flex; align-items: center; justify-content: center; padding: 24px 16px; }
  .reset-modern-card {
    max-width: 400px;
    width: 100%;
    border-radius: 18px;
    box-shadow: 0 4px 32px rgba(0,0,0,0.10);
    border: 1px solid #e5e7eb;
    background: #fff;
    padding: 38px 32px 28px 32px;
  }
  .reset-modern-card .form-label {
    font-weight: 500;
    color: #343a40;
    margin-bottom: 6px;
  }
  .reset-modern-card .form-control {
    border-radius: 10px;
    border: 1px solid #e5e7eb;
    font-size: 1.08em;
    padding: 12px 14px;
    margin-bottom: 18px;
    background: #f8fafc;
    transition: border .2s;
  }
  .reset-modern-card .form-control:focus {
    border-color: #8b5cf6;
    background: #fff;
    box-shadow: 0 0 0 2px #e9d5ff;
  }
  .reset-modern-card .btn-gradient-primary {
    background: linear-gradient(90deg, #6d8cff 0%, #8b5cf6 100%);
    color: #fff;
    border: none;
    border-radius: 10px;
    font-size: 1.13em;
    font-weight: 500;
    padding: 12px 0;
    margin-top: 8px;
    box-shadow: 0 2px 12px rgba(139,92,246,0.08);
    transition: background .2s;
  }
  .reset-modern-card .btn-gradient-primary:hover {
    background: linear-gradient(90deg, #8b5cf6 0%, #6d8cff 100%);
  }
  .reset-modern-card .login-links {
    margin-top: 22px;
    text-align: center;
  }
  .reset-modern-card .login-links a {
    color: #4f46e5;
    text-decoration: underline;
    font-size: 1.04em;
    font-weight: 500;
    transition: color .2s;
  }
  .reset-modern-card .login-links a:hover {
    color: #8b5cf6;
  }
</style>
<main>
  <div class="reset-modern-card">
    <h1 class="h4 mb-3 text-center w-100" style="font-weight:600;">Reset hasła</h1>
    <?php if ($success): ?>
      <div class="alert alert-success">Jeśli podany e-mail istnieje w bazie, wysłaliśmy instrukcje resetu hasła.</div>
    <?php else: ?>
      <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
      <form method="post">
        <div class="mb-3">
          <label class="form-label">E-mail</label>
          <input type="email" name="email" class="form-control" required autocomplete="username">
        </div>
        <button type="submit" class="btn btn-gradient-primary w-100">Wyślij link resetujący</button>
        <div class="login-links">
          <a href="index.php?page=login">Powrót do logowania</a>
        </div>
      </form>
    <?php endif; ?>
  </div>
</main>
<?php require_once dirname(__DIR__) . '/partials/footer.php'; ?>
