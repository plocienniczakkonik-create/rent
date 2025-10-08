<?php
// /pages/login.php
require_once dirname(__DIR__) . '/partials/head.php';
?>

<style>
    /* Cała strona (nav + main + footer) w 100vh bez scrolla */
    html,
    body {
        height: 100%;
    }

    body {
        min-height: 100vh;
        display: flex;
        flex-direction: column;
        margin: 0;
    }

    /* Na loginie nawigacja ma być w normalnym flow (nie absolute) */
    #siteNav {
        position: static !important;
    }

    /* Main wypełnia przestrzeń między nav i footerem i centruje formularz */
    main {
        flex: 1 0 auto;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 24px 16px;
    }

    /* (opcjonalnie lepsze zachowanie na mobile) */
    @supports (min-height: 100dvh) {
        body {
            min-height: 100dvh;
        }
    }
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
        <h1 class="h3 mb-3 text-center w-100" style="font-weight:600;">Logowanie</h1>

        <?php if ($err === 'empty'): ?>
            <div class="alert alert-warning">Wpisz e-mail i hasło.</div>
        <?php elseif ($err === 'invalid'): ?>
            <div class="alert alert-danger">Nieprawidłowe dane logowania.</div>
        <?php elseif ($loggedOut): ?>
            <div class="alert alert-success">Zostałeś wylogowany.</div>
        <?php endif; ?>

        <style>
            .login-modern-card {
                max-width: 400px;
                margin: 0 auto;
                border-radius: 18px;
                box-shadow: 0 4px 32px rgba(0, 0, 0, 0.10);
                border: 1px solid #e5e7eb;
                background: #fff;
                padding: 38px 32px 28px 32px;
            }

            .login-modern-card .form-label {
                font-weight: 500;
                color: #343a40;
                margin-bottom: 6px;
            }

            .login-modern-card .form-control {
                border-radius: 10px;
                border: 1px solid #e5e7eb;
                font-size: 1.08em;
                padding: 12px 14px;
                margin-bottom: 18px;
                background: #f8fafc;
                transition: border .2s;
            }

            .login-modern-card .form-control:focus {
                border-color: #8b5cf6;
                background: #fff;
                box-shadow: 0 0 0 2px #e9d5ff;
            }

            .login-modern-card .form-check-input {
                border-radius: 6px;
                border: 1.5px solid #bdbdbd;
                width: 20px;
                height: 20px;
                margin-right: 8px;
            }

            .login-modern-card .form-check-label {
                font-size: 1em;
                color: #555;
                margin-bottom: 0;
            }

            .login-modern-card .btn-gradient-primary {
                background: linear-gradient(90deg, #6d8cff 0%, #8b5cf6 100%);
                color: #fff;
                border: none;
                border-radius: 10px;
                font-size: 1.13em;
                font-weight: 500;
                padding: 12px 0;
                margin-top: 8px;
                box-shadow: 0 2px 12px rgba(139, 92, 246, 0.08);
                transition: background .2s;
            }

            .login-modern-card .btn-gradient-primary:hover {
                background: linear-gradient(90deg, #8b5cf6 0%, #6d8cff 100%);
            }

            .login-modern-card .login-links {
                margin-top: 22px;
                text-align: center;
            }

            .login-modern-card .login-links a {
                color: #4f46e5;
                text-decoration: underline;
                font-size: 1.04em;
                font-weight: 500;
                transition: color .2s;
            }

            .login-modern-card .login-links a:hover {
                color: #8b5cf6;
            }
        </style>
        <form method="post" action="<?= $BASE ? ($BASE . '/auth/login-handler.php') : 'auth/login-handler.php' ?>" class="login-modern-card" aria-labelledby="loginTitle">
            <?php require_once dirname(__DIR__) . '/includes/_helpers.php';
            csrf_field(); ?>
            <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect) ?>">
            <div class="mb-2">
                <label class="form-label" for="login_email">E-mail</label>
                <input type="email" id="login_email" name="email" class="form-control" required autocomplete="username" aria-required="true" aria-label="Adres e-mail" />
            </div>
            <div class="mb-2">
                <label class="form-label" for="login_password">Hasło</label>
                <input type="password" id="login_password" name="password" class="form-control" required autocomplete="current-password" aria-required="true" aria-label="Hasło" />
            </div>
            <div class="mb-2 form-check d-flex align-items-center">
                <input type="checkbox" class="form-check-input" id="remember" name="remember" aria-checked="false" aria-label="Zapamiętaj mnie" />
                <label class="form-check-label ms-1" for="remember">Pamiętaj mnie</label>
            </div>
            <button type="submit" class="btn btn-gradient-primary w-100" aria-label="Zaloguj się">Zaloguj</button>
            <div class="login-links">
                <a href="index.php?page=register" aria-label="Przejdź do rejestracji">Nie masz konta? Zarejestruj się</a><br>
                <a href="index.php?page=reset-password" class="mt-2 d-inline-block" aria-label="Przypomnij hasło">Nie pamiętasz hasła?</a>
            </div>
        </form>
    </div>
</main>

<?php require_once dirname(__DIR__) . '/partials/footer.php'; ?>