<?php
// Formularz zgód RODO/GDPR dla użytkownika
require_once dirname(__DIR__) . '/includes/db.php';
$db = db();
$user_id = $_SESSION['user_id'] ?? 1; // demo/test

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $privacy = !empty($_POST['privacy_policy']) ? 1 : 0;
    $marketing = !empty($_POST['marketing']) ? 1 : 0;
    // Zapisz/aktualizuj zgody
    $db->prepare('INSERT INTO user_consents (user_id, consent_type, consent_text, given_at, ip_address, source) VALUES (?, ?, ?, NOW(), ?, ?)')
        ->execute([$user_id, 'privacy_policy', 'Akceptacja polityki prywatności', $_SERVER['REMOTE_ADDR'], 'profile']);
    if ($marketing) {
        $db->prepare('INSERT INTO user_consents (user_id, consent_type, consent_text, given_at, ip_address, source) VALUES (?, ?, ?, NOW(), ?, ?)')
            ->execute([$user_id, 'marketing', 'Zgoda na marketing', $_SERVER['REMOTE_ADDR'], 'profile']);
    }
    echo '<div class="alert alert-success">Zgody zapisane!</div>';
}
// Pobierz status zgód
$consents = $db->query('SELECT consent_type, given_at, revoked_at FROM user_consents WHERE user_id = ' . (int)$user_id)->fetchAll(PDO::FETCH_ASSOC);
$privacy_accepted = false;
$marketing_accepted = false;
foreach ($consents as $c) {
    if ($c['consent_type'] === 'privacy_policy' && !$c['revoked_at']) $privacy_accepted = true;
    if ($c['consent_type'] === 'marketing' && !$c['revoked_at']) $marketing_accepted = true;
}
?>
<div class="card">
    <div class="card-header" style="background: var(--gradient-primary); color: #fff;">
        <h3 class="h6 mb-0"><i class="bi bi-shield-lock me-2"></i>Moje zgody RODO/GDPR</h3>
    </div>
    <div class="card-body">
        <form method="POST">
            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" name="privacy_policy" id="privacy_policy" value="1" <?= $privacy_accepted ? 'checked disabled' : '' ?>>
                <label class="form-check-label" for="privacy_policy">
                    Akceptuję <a href="#" data-bs-toggle="modal" data-bs-target="#privacyModal">politykę prywatności</a> (wymagane)
                </label>
            </div>
            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" name="marketing" id="marketing" value="1" <?= $marketing_accepted ? 'checked' : '' ?>>
                <label class="form-check-label" for="marketing">
                    Wyrażam zgodę na otrzymywanie informacji marketingowych
                </label>
            </div>
            <button type="submit" class="btn btn-gradient-primary" style="background: var(--gradient-primary); color: #fff; border-radius: 8px; border: none;">Zapisz zgody</button>
        </form>
    </div>
</div>
<!-- Modal polityki prywatności -->
<div class="modal fade" id="privacyModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background: var(--gradient-primary); color: #fff;">
                <h5 class="modal-title">Polityka prywatności</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Tu wklej aktualną politykę prywatności zgodną z RODO/GDPR...</p>
            </div>
        </div>
    </div>
</div>