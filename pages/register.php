<?php
require_once dirname(__DIR__) . '/partials/head.php';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/i18n.php';
i18n::init();
require_once dirname(__DIR__) . '/partials/header.php';

$success = false;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $privacy = $_POST['privacy_consent'] ?? '';
    $marketing = $_POST['marketing_consent'] ?? '';

    if ($first_name === '') $errors[] = 'Imię jest wymagane.';
    if ($last_name === '') $errors[] = 'Nazwisko jest wymagane.';
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Poprawny email jest wymagany.';
    if (strlen($password) < 6) $errors[] = 'Hasło musi mieć min. 6 znaków.';
    if (!$privacy) $errors[] = 'Musisz zaakceptować politykę prywatności.';

    if (empty($errors)) {
        $db = db();
        // Sprawdź czy email już istnieje
        $exists = $db->prepare('SELECT id FROM users WHERE email = ?');
        $exists->execute([$email]);
        if ($exists->fetch()) {
            $errors[] = 'Użytkownik z tym adresem email już istnieje.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare('INSERT INTO users (first_name, last_name, email, password_hash, role, is_active, created_at) VALUES (?, ?, ?, ?, ?, 1, NOW())');
            $stmt->execute([$first_name, $last_name, $email, $hash, 'user']);
            $user_id = $db->lastInsertId();
            // Zapisz zgodę na politykę prywatności
            $db->prepare('INSERT INTO user_consents (user_id, consent_type, consent_text, given_at, ip_address, source) VALUES (?, ?, ?, NOW(), ?, ?)')
                ->execute([$user_id, 'privacy_policy', 'Akceptacja polityki prywatności przy rejestracji', $_SERVER['REMOTE_ADDR'] ?? '', 'register_form']);
            // Zapisz zgodę marketingową jeśli zaznaczona
            if ($marketing) {
                $db->prepare('INSERT INTO user_consents (user_id, consent_type, consent_text, given_at, ip_address, source) VALUES (?, ?, ?, NOW(), ?, ?)')
                    ->execute([$user_id, 'marketing', 'Zgoda na otrzymywanie informacji marketingowych', $_SERVER['REMOTE_ADDR'] ?? '', 'register_form']);
            }
            $success = true;
        }
    }
}
?>
<style>
    main {
        flex: 1 0 auto;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 24px 16px;
    }

    .register-modern-card {
        max-width: 500px;
        width: 100%;
        border-radius: 18px;
        box-shadow: 0 4px 32px rgba(0, 0, 0, 0.10);
        border: 1px solid #e5e7eb;
        background: #fff;
        padding: 0;
        overflow: hidden;
    }

    .register-modern-card .card-header {
        background: linear-gradient(90deg, #6d8cff 0%, #8b5cf6 100%);
        color: #fff;
        font-size: 1.25em;
        font-weight: 600;
        padding: 20px 28px 16px 28px;
        border-bottom: none;
    }

    .register-modern-card .card-body {
        padding: 32px 28px 28px 28px;
    }

    .register-modern-card .form-label {
        font-weight: 500;
        color: #343a40;
        margin-bottom: 6px;
    }

    .register-modern-card .form-control {
        border-radius: 10px;
        border: 1px solid #e5e7eb;
        font-size: 1.08em;
        padding: 12px 14px;
        margin-bottom: 18px;
        background: #f8fafc;
        transition: border .2s;
    }

    .register-modern-card .form-control:focus {
        border-color: #8b5cf6;
        background: #fff;
        box-shadow: 0 0 0 2px #e9d5ff;
    }

    .register-modern-card .form-check-input {
        border-radius: 6px;
        border: 1.5px solid #bdbdbd;
        width: 20px;
        height: 20px;
        margin-right: 8px;
    }

    .register-modern-card .form-check-label {
        font-size: 1em;
        color: #555;
        margin-bottom: 0;
    }

    .register-modern-card .btn-theme {
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

    .register-modern-card .btn-theme:hover {
        background: linear-gradient(90deg, #8b5cf6 0%, #6d8cff 100%);
    }
</style>
<main>
    <div class="register-modern-card">
        <div class="card-header">Rejestracja użytkownika</div>
        <div class="card-body">
            <?php if ($success): ?>
                <div class="alert alert-success">Rejestracja zakończona sukcesem! Możesz się teraz zalogować.</div>
            <?php else: ?>
                <?php if ($errors): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0 ps-3">
                            <?php foreach ($errors as $err): ?>
                                <li><?= htmlspecialchars($err) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                <form method="POST" autocomplete="off">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="first_name" class="form-label">Imię</label>
                            <input type="text" class="form-control" id="first_name" name="first_name" required value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="last_name" class="form-label">Nazwisko</label>
                            <input type="text" class="form-control" id="last_name" name="last_name" required value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Hasło</label>
                        <input type="password" class="form-control" id="password" name="password" required minlength="6">
                    </div>
                    <div class="form-check mb-2 d-flex align-items-start">
                        <input class="form-check-input mt-1" type="checkbox" id="privacy_consent" name="privacy_consent" value="1" required <?= isset($_POST['privacy_consent']) ? 'checked' : '' ?>>
                        <label class="form-check-label ms-2" for="privacy_consent" style="line-height:1.5;">
                            Akceptuję <a href="privacy-policy" target="_blank">politykę prywatności</a> i wyrażam zgodę na przetwarzanie danych w celu założenia konta.
                        </label>
                    </div>
                    <div class="form-check mb-3 d-flex align-items-start">
                        <input class="form-check-input mt-1" type="checkbox" id="marketing_consent" name="marketing_consent" value="1" <?= isset($_POST['marketing_consent']) ? 'checked' : '' ?>>
                        <label class="form-check-label ms-2" for="marketing_consent" style="line-height:1.5;">
                            Zgadzam się na otrzymywanie informacji marketingowych (opcjonalnie)
                        </label>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-theme btn-primary">Zarejestruj się</button>
                    </div>
                </form>
                <div class="d-grid mt-2">
                    <a href="index.php?page=login" class="btn btn-outline-secondary">&larr; Powrót do logowania</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>
<?php require_once dirname(__DIR__) . '/partials/footer.php'; ?>