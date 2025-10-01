<?php
// /auth/auth.php
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'httponly' => true,
        'secure'   => !empty($_SERVER['HTTPS']),
        'samesite' => 'Lax',
        'path'     => '/', // ważne dla /pages i /auth
    ]);
    session_start();
}

require_once dirname(__DIR__) . '/includes/db.php';

/**
 * Zwraca BASE_URL z configu (np. /rental) albo pusty string.
 */
function base_url(): string
{
    return defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
}

/**
 * Aktualnie zalogowany użytkownik (lub null).
 * !!! Pobieramy także role i job_title, żeby rozróżnić client/staff.
 */
function current_user(): ?array
{
    if (empty($_SESSION['user_id'])) return null;

    $stmt = db()->prepare("
        SELECT id, email, first_name, last_name, is_active, role, job_title
        FROM users
        WHERE id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $u = $stmt->fetch(PDO::FETCH_ASSOC);

    return ($u && (int)$u['is_active'] === 1) ? $u : null;
}

/**
 * Wymaga zalogowania — w przeciwnym razie przekierowuje na /index.php?page=login
 * z parametrem redirect ustawionym na bieżący URI (żeby po zalogowaniu wrócić).
 */
function require_auth(): array
{
    $u = current_user();
    if (!$u) {
        // Dokąd wrócić po zalogowaniu
        $req = $_SERVER['REQUEST_URI'] ?? (base_url() . '/index.php?page=dashboard-client');

        // Routerowa strona logowania z redirectem
        header('Location: ' . base_url() . '/index.php?page=login&redirect=' . urlencode($req));
        exit;
    }
    return $u;
}

/** Szybki helper */
function is_logged_in(): bool
{
    return (bool) current_user();
}

/** Czy user ma rolę 'staff' */
function is_staff(): bool
{
    $u = current_user();
    return $u && ($u['role'] ?? 'client') === 'staff';
}

/**
 * Wymaga roli 'staff' (najpierw wymaga zalogowania).
 * Jeśli nie ma uprawnień — przenosi do panelu klienta.
 */
function require_staff(): array
{
    $u = require_auth(); // już pilnuje zalogowania
    if (($u['role'] ?? 'client') !== 'staff') {
        header('Location: ' . base_url() . '/index.php?page=dashboard-client&err=forbidden');
        exit;
    }
    return $u;
}

/* --- CSRF helpery --- */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES) . '">';
}

function csrf_verify(): void
{
    $ok = isset($_POST['csrf'], $_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], (string)$_POST['csrf']);
    if (!$ok) {
        http_response_code(403);
        exit('Invalid CSRF token');
    }
}
