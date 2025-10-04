<?php
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
