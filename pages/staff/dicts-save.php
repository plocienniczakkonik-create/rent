<?php
// /pages/staff/dicts-save.php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/db.php';
require_once dirname(__DIR__, 2) . '/auth/auth.php';
require_once dirname(__DIR__, 2) . '/includes/config.php';

require_staff();

/* ===== CSRF (kompatybilnie z auth.php) ===== */
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function posted_csrf_token(): ?string {
    foreach (['csrf', '_token', 'csrf_token', 'token'] as $k) {
        if (!empty($_POST[$k])) return (string)$_POST[$k];
    }
    return null;
}
function session_csrf_tokens(): array {
    $out = [];
    if (!empty($_SESSION['csrf_token'])) $out[] = (string)$_SESSION['csrf_token'];
    if (!empty($_SESSION['_token']))     $out[] = (string)$_SESSION['_token'];
    return array_values(array_unique($out));
}
function verify_csrf_or_fail(): void {
    $posted = posted_csrf_token();
    $valids = session_csrf_tokens();
    $ok = $posted && $valids && array_reduce($valids, fn($c,$v)=>$c||hash_equals($v,$posted), false);
    if (!$ok) { http_response_code(403); exit('Invalid CSRF token'); }
}
if (function_exists('csrf_verify')) { csrf_verify(); } else { verify_csrf_or_fail(); }

/* ===== Parametry + helper redirectu (z kotwicą) ===== */
$BASE   = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
$kind   = $_POST['kind']   ?? 'location';
$action = $_POST['action'] ?? 'create';

function redirect_back(string $kind, string $msg = '', string $err = ''): never {
    $base = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
    $qs   = http_build_query(array_filter([
        'page' => 'dashboard-staff',
        'tab'  => 'dicts',
        'kind' => $kind,
        'msg'  => $msg ?: null,
        'err'  => $err ?: null,
    ]));
    header('Location: ' . ($base !== '' ? $base : '') . '/index.php?' . $qs . '#pane-dicts');
    exit;
}

/* ===== Utils ===== */
/** Stabilne slugify z polską mapą znaków (bez iconv). */
function slugify(string $s): string {
    $s = trim($s);
    if ($s === '') return 'item';

    // małe litery w UTF-8
    if (function_exists('mb_strtolower')) {
        $s = mb_strtolower($s, 'UTF-8');
    } else {
        $s = strtolower($s);
    }

    // mapowanie PL znaków (także gdyby przyszły wielkie litery)
    $map = [
        'ą'=>'a','ć'=>'c','ę'=>'e','ł'=>'l','ń'=>'n','ó'=>'o','ś'=>'s','ż'=>'z','ź'=>'z',
        'Ą'=>'a','Ć'=>'c','Ę'=>'e','Ł'=>'l','Ń'=>'n','Ó'=>'o','Ś'=>'s','Ż'=>'z','Ź'=>'z',
    ];
    $s = strtr($s, $map);

    // sprowadź inne separatory/whitespace do spacji
    $s = preg_replace('/[^\S\r\n]+/u', ' ', $s);
    // usuń wszystko poza a-z0-9, zamień sekwencje na myślnik
    $s = preg_replace('/[^a-z0-9]+/u', '-', $s);
    $s = trim($s, '-');

    return $s ?: 'item';
}

$pdo = db();

try {
    // Typ słownika
    $qType = $pdo->prepare('SELECT id, slug, is_hierarchical FROM dict_types WHERE slug = :slug LIMIT 1');
    $qType->execute([':slug' => $kind]);
    $dictType = $qType->fetch(PDO::FETCH_ASSOC);
    if (!$dictType) redirect_back($kind, '', 'Nie znaleziono typu słownika.');

    $dictTypeId = (int)$dictType['id'];
    $isHier     = (bool)$dictType['is_hierarchical'];

    // Dane z formularza
    $id        = isset($_POST['id']) ? (int)$_POST['id'] : null;
    $name      = trim((string)($_POST['name'] ?? ''));
    $slug      = trim((string)($_POST['slug'] ?? ''));
    $statusRaw = (string)($_POST['status'] ?? 'active');
    $status    = in_array($statusRaw, ['active', 'inactive', 'archived'], true) ? $statusRaw : 'active';
    $sortOrder = isset($_POST['sort_order']) ? (int)$_POST['sort_order'] : 0;
    $parent_id = ($isHier && isset($_POST['parent_id']) && $_POST['parent_id'] !== '') ? (int)$_POST['parent_id'] : null;

    if ($name === '') redirect_back($kind, '', 'Nazwa nie może być pusta.');
    if ($slug === '') $slug = slugify($name); else $slug = slugify($slug);

    if ($action === 'update') {
        if (!$id) redirect_back($kind, '', 'Brak identyfikatora pozycji do edycji.');

        // kolizja slug
        $q = $pdo->prepare('SELECT id FROM dict_terms WHERE dict_type_id = :t AND slug = :s AND id <> :id LIMIT 1');
        $q->execute([':t' => $dictTypeId, ':s' => $slug, ':id' => $id]);
        if ($q->fetch()) redirect_back($kind, '', 'Slug już istnieje dla tego typu.');

        if ($isHier && $parent_id !== null && $parent_id === $id) {
            redirect_back($kind, '', 'Pozycja nie może być swoim własnym rodzicem.');
        }

        $u = $pdo->prepare('
            UPDATE dict_terms
            SET parent_id = :p, name = :n, slug = :s, sort_order = :so, status = :st
            WHERE id = :id AND dict_type_id = :t
        ');
        $u->execute([
            ':p'  => $isHier ? $parent_id : null,
            ':n'  => $name,
            ':s'  => $slug,
            ':so' => $sortOrder,
            ':st' => $status,
            ':id' => $id,
            ':t'  => $dictTypeId,
        ]);

        redirect_back($kind, 'Zaktualizowano pozycję.');
    } else {
        // create
        $q = $pdo->prepare('SELECT id FROM dict_terms WHERE dict_type_id = :t AND slug = :s LIMIT 1');
        $q->execute([':t' => $dictTypeId, ':s' => $slug]);
        if ($q->fetch()) redirect_back($kind, '', 'Slug już istnieje dla tego typu.');

        $ins = $pdo->prepare('
            INSERT INTO dict_terms (dict_type_id, parent_id, name, slug, sort_order, status)
            VALUES (:t, :p, :n, :s, :so, :st)
        ');
        $ins->execute([
            ':t'  => $dictTypeId,
            ':p'  => $isHier ? $parent_id : null,
            ':n'  => $name,
            ':s'  => $slug,
            ':so' => $sortOrder,
            ':st' => $status,
        ]);

        redirect_back($kind, 'Dodano pozycję.');
    }
} catch (Throwable $e) {
    redirect_back($kind, '', 'Błąd zapisu: ' . $e->getMessage());
}
