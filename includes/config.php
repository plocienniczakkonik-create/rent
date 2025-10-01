<?php
// DB
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'rental');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// App
define('APP_ENV', 'local');
define('APP_DEBUG', true); // na PROD ustaw false


// ... Twoje dotychczasowe stałe ...
if (!defined('BASE_URL')) define('BASE_URL', '/rental'); // UWAGA: dopasuj do ścieżki w URL (literki!)
