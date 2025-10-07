<?php
// Ustawienia banera cookies/RODO
$db = db();
$msg = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $privacy_url = trim($_POST['privacy_policy_url'] ?? '');
    $banner_text = trim($_POST['banner_text'] ?? '');
    $db->prepare("REPLACE INTO settings (setting_key, setting_value) VALUES (?, ?), (?, ?)")
        ->execute(['privacy_policy_url', $privacy_url, 'cookie_banner_text', $banner_text]);
    $msg = '<div class="alert alert-success">Zapisano ustawienia banera cookies/RODO.</div>';
}
$privacy_url = $db->query("SELECT setting_value FROM settings WHERE setting_key = 'privacy_policy_url'")->fetchColumn() ?: '/rental/index.php?page=privacy-policy';
$banner_text = $db->query("SELECT setting_value FROM settings WHERE setting_key = 'cookie_banner_text'")->fetchColumn() ?: 'Ta strona korzysta z plików cookies w celach opisanych w <a href=\"/rental/index.php?page=privacy-policy\">Polityce prywatności</a>.';
echo $msg;
?>
<div class="card mb-4"><div class="card-header"><b>Ustawienia banera cookies/RODO</b></div>
<div class="card-body">
<form method="post">
    <div class="mb-3">
        <label for="privacy_policy_url" class="form-label">Adres URL polityki prywatności</label>
        <input type="text" class="form-control" id="privacy_policy_url" name="privacy_policy_url" value="<?= htmlspecialchars($privacy_url) ?>" required>
    </div>
    <div class="mb-3">
        <label for="banner_text" class="form-label">Treść banera cookies (HTML dozwolony)</label>
        <textarea class="form-control" id="banner_text" name="banner_text" rows="3" required><?= htmlspecialchars($banner_text) ?></textarea>
    </div>
    <button type="submit" class="btn btn-primary">Zapisz ustawienia</button>
</form>
</div></div>
