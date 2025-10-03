<?php
// /pages/staff/dicts-delete.php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/db.php';
require_once dirname(__DIR__, 2) . '/auth/auth.php';
require_once dirname(__DIR__, 2) . '/includes/config.php';

require_staff();

/* ===== CSRF (kompatybilnie z auth.php; fallback gdyby helpery nie były wczytane) ===== */
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
function posted_csrf_token(): ?string
{
    return !empty($_POST['_token']) ? (string)$_POST['_token'] : null;
}
function session_csrf_tokens(): array
{
    $out = [];
    if (!empty($_SESSION['_token'])) $out[] = (string)$_SESSION['_token'];
    return array_values(array_unique($out));
}
function verify_csrf_or_fail(): void
{
    $posted = posted_csrf_token();
    $valids = session_csrf_tokens();
    $ok = $posted && $valids && array_reduce($valids, fn($c, $v) => $c || hash_equals($v, $posted), false);
    if (!$ok) {
        http_response_code(403);
        exit('Invalid CSRF token');
    }
}
if (function_exists('csrf_verify')) {
    csrf_verify();
} else {
    verify_csrf_or_fail();
}

$kind = $_POST['kind'] ?? 'location'; // obsługuje też 'addon'
$id   = isset($_POST['id']) ? (int)$_POST['id'] : 0;

function redirect_back(string $kind, string $msg = '', string $err = ''): never
{
    $base = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
    $qs   = http_build_query(array_filter([
        'page' => 'dashboard-staff',
        'tab'  => 'dicts',
        'kind' => $kind,
        'msg'  => $msg ?: null,
        'err'  => $err ?: null,
    ]));
    header('Location: ' . $base . '/index.php?' . $qs . '#pane-dicts'); // ⬅️ zostajemy na zakładce
    exit;
}

if ($id <= 0) {
    redirect_back($kind, '', 'Brak prawidłowego ID do usunięcia.');
}

$pdo = db();

try {
    // Opcjonalnie: sprawdź, czy rekord istnieje
    $chk = $pdo->prepare('SELECT id FROM dict_terms WHERE id = :id LIMIT 1');
    $chk->execute([':id' => $id]);
    if (!$chk->fetch()) {
        redirect_back($kind, '', 'Pozycja nie istnieje.');
    }

    $del = $pdo->prepare('DELETE FROM dict_terms WHERE id = :id');
    $del->execute([':id' => $id]);

    redirect_back($kind, 'Usunięto pozycję.');
} catch (Throwable $e) {
    redirect_back($kind, '', 'Błąd usuwania: ' . $e->getMessage());
}
