<?php
// ...existing code...
// Funkcja sortowania dla dashboard-staff i sekcji
function sort_link_dashboard(string $section, string $key, string $label): string
{
    $currentSection = $_GET['section'] ?? '';
    $currentSort = $_GET['sort'] ?? '';
    $currentDir = strtolower($_GET['dir'] ?? 'asc');

    // Tylko sortuj jeśli jesteśmy w tej sekcji
    $nextDir = ($currentSection === $section && $currentSort === $key && $currentDir === 'asc') ? 'desc' : 'asc';
    $arrowUpActive = ($currentSection === $section && $currentSort === $key && $currentDir === 'asc');
    $arrowDownActive = ($currentSection === $section && $currentSort === $key && $currentDir === 'desc');

    $BASE = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
    $qs = http_build_query([
        'page' => 'dashboard-staff',
        'section' => $section,
        'sort' => $key,
        'dir' => $nextDir,
    ]);

    // Dodaj hash dla sekcji
    $hash = '#pane-' . $section;

    return '<a class="th-sort-link" href="' . htmlspecialchars($BASE . '/index.php?' . $qs . $hash) . '">' .
        '<span class="label">' . htmlspecialchars($label) . '</span>' .
        '<span class="chevs"><span class="chev ' . ($arrowUpActive ? 'active' : '') . '">▲</span><span class="chev ' . ($arrowDownActive ? 'active' : '') . '">▼</span></span>' .
        '</a>';
}
// Uniwersalne helpery CSRF (i tylko one). Bez innych include’ów.
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
if (empty($_SESSION['_token'])) {
    $_SESSION['_token'] = bin2hex(random_bytes(32));
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        if (empty($_SESSION['_token'])) {
            $_SESSION['_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_token'];
    }
}

if (!function_exists('csrf_verify')) {
    function csrf_verify(): void
    {
        $sess = (string)($_SESSION['_token'] ?? '');
        $sent = (string)($_POST['_token'] ?? '');
        if ($sent === '' && isset($_COOKIE['_token'])) {
            $sent = (string)$_COOKIE['_token'];
        }
        if ($sent === '' && isset($_GET['_token'])) {
            $sent = (string)$_GET['_token'];
        }
        if ($sent === '' || $sess === '' || !hash_equals($sess, $sent)) {
            http_response_code(419);
            exit('Invalid CSRF token');
        }
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): void
    {
        if (empty($_SESSION['_token'])) {
            $_SESSION['_token'] = bin2hex(random_bytes(32));
        }
        echo '<input type="hidden" name="_token" value="' . htmlspecialchars($_SESSION['_token'], ENT_QUOTES) . '">';
    }
}
