<?php

declare(strict_types=1);

// /includes/_helpers.php
// Uniwersalne helpery CSRF (i tylko one). Bez innych include’ów.

// Upewnij się, że sesja działa (bez duplikatów)
if (session_status() !== PHP_SESSION_ACTIVE) {
    // Jeżeli aplikacja działa w subfolderze (np. /rental), możesz wymusić ścieżkę ciasteczka:
    // if (defined('BASE_URL')) { @ini_set('session.cookie_path', rtrim((string)BASE_URL, '/') . '/'); }
    session_start();
    // Wymuś token CSRF na starcie sesji
    if (empty($_SESSION['_token'])) {
        $_SESSION['_token'] = bin2hex(random_bytes(32));
    }
}


/**
 * Weryfikacja tokenu (używaj w *-save.php i *-delete.php).
 * Akceptuje token z:
 *  - POST['_token'] (preferowane)
 *  - nagłówka 'X-CSRF-Token' (np. dla XHR/fetch)
 *  - cookie '_token' (awaryjnie)
 *  - GET['_token'] (ostatnia deska ratunku)
 */
if (!function_exists('csrf_verify')) {
    function csrf_verify(): void
    {
        $sess = (string)($_SESSION['_token'] ?? '');

        // 1) POST
        $sent = (string)($_POST['_token'] ?? '');

        // 2) Nagłówek
        if ($sent === '') {
            // getallheaders() może nie istnieć na wszystkich SAPIs
            $headers = function_exists('getallheaders') ? getallheaders() : [];
            $hdrTok  = '';
            if (is_array($headers)) {
                foreach ($headers as $k => $v) {
                    if (strcasecmp((string)$k, 'X-CSRF-Token') === 0) {
                        $hdrTok = (string)$v;
                        break;
                    }
                }
            }
            if ($hdrTok !== '') {
                $sent = $hdrTok;
            }
        }

        // 3) Cookie (awaryjnie)
        if ($sent === '' && isset($_COOKIE['_token'])) {
            $sent = (string)$_COOKIE['_token'];
        }

        // 4) GET (ostatnia opcja)
        if ($sent === '') {
            $sent = (string)($_GET['_token'] ?? '');
        }

        if ($sent === '' || $sess === '' || !hash_equals($sess, $sent)) {
            http_response_code(419);
            exit('Invalid CSRF token');
        }

        // (opcjonalnie) regeneracja tokenu po udanym sprawdzeniu:
        // $_SESSION['_token'] = bin2hex(random_bytes(32));
    }
}
// Uniwersalne pole CSRF do formularzy
if (!function_exists('csrf_field')) {
    function csrf_field(): void
    {
        if (empty($_SESSION['_token'])) {
            $_SESSION['_token'] = bin2hex(random_bytes(32));
        }
        echo '<input type="hidden" name="_token" value="' . htmlspecialchars($_SESSION['_token'], ENT_QUOTES) . '">';
    }
}
