<?php
// /partials/head.php
// Upewnij się, że w /includes/config.php masz:
// define('BASE_URL', '/rental'); // lub odpowiednią ścieżkę katalogu projektu

// Include systemu zarządzania motywem
require_once dirname(__DIR__) . '/includes/theme-config.php';

$BASE   = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
$cssUrl = $BASE . '/assets/css/main.css';
$cssFs  = dirname(__DIR__) . '/assets/css/main.css'; // ścieżka na dysku (../assets/css/main.css)
$cssVer = file_exists($cssFs) ? filemtime($cssFs) : time();

// Theme CSS
$themeCssUrl = $BASE . '/assets/css/theme-system.css';
$themeCssFs  = dirname(__DIR__) . '/assets/css/theme-system.css';
$themeCssVer = file_exists($themeCssFs) ? filemtime($themeCssFs) : time();
?>
<!DOCTYPE html>
<html lang="pl">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Wypożyczalnia samochodów</title>

    <!-- Dynamiczne CSS Variables -->
    <style>
        <?= ThemeConfig::generateCSSVariables() ?>
    </style>

    <!-- Bootstrap CSS -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
        rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB"
        crossorigin="anonymous" />

    <!-- Bootstrap Icons -->
    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
        crossorigin="anonymous" />

    <!-- System kolorów -->
    <link rel="stylesheet" href="<?= htmlspecialchars($themeCssUrl) ?>?v=<?= $themeCssVer ?>" />

    <!-- Twój skompilowany CSS z cache-busterem -->
    <link rel="stylesheet" href="<?= htmlspecialchars($cssUrl) ?>?v=<?= $cssVer ?>" />

    <!-- Font Awesome -->
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
        crossorigin="anonymous"
        referrerpolicy="no-referrer" />

    <!-- FullCalendar CDN -->
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>
</head>
</head>