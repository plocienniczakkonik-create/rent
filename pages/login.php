<?php
require_once __DIR__ . '/../auth/auth.php';
if (is_logged_in()) {
    header('Location: index.php');
    exit;
}

/* włączamy centrowanie tylko na tej stronie */
echo '<script>document.addEventListener("DOMContentLoaded",function(){document.querySelector(".site-main")?.classList.add("login-center");});</script>';
?>

<section class="login-page py-5">
    <div class="container">
        <div class="card shadow-lg mx-auto" style="max-width: 480px; width:100%; border-radius:18px;">
            <div class="card-body p-4 p-md-5">
                <div class="text-center mb-4">
                    <a href="index.php" class="brand-badge mb-2">CORONA</a>
                    <h4 class="mb-0">Zaloguj się</h4>
                    <p class="text-muted mb-0">Użyj konta klienta lub obsługi</p>
                </div>

                <?php if (isset($_GET['error'])): ?>
                    <div class="alert alert-danger" role="alert">Nieprawidłowy e-mail lub hasło.</div>
                <?php endif; ?>

                <form action="auth/login-handler.php" method="post" novalidate>
                    <div class="mb-3">
                        <label class="form-label">E-mail</label>
                        <input type="email" class="form-control" name="email" placeholder="name@example.com" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Hasło</label>
                        <input type="password" class="form-control" name="password" placeholder="••••••••" required>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="remember">
                            <label class="form-check-label" for="remember">Zapamiętaj mnie</label>
                        </div>
                        <a href="#" class="small">Nie pamiętasz hasła?</a>
                    </div>
                    <button class="btn btn-dark w-100 rounded-pill">Zaloguj</button>
                </form>

                <hr class="my-4">
                <div class="small text-muted">
                    <strong>Dane testowe:</strong><br>
                    klient: <code>klient@example.com</code> / <code>demo123</code><br>
                    obsługa: <code>staff@example.com</code> / <code>demo123</code>
                </div>
            </div>
        </div>
    </div>
</section>