<?php
// /partials/head.php
// Upewnij się, że w /includes/config.php masz:
// define('BASE_URL', '/rental'); // lub odpowiednią ścieżkę katalogu projektu

$BASE   = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
$cssUrl = $BASE . '/assets/css/main.css';
$cssFs  = dirname(__DIR__) . '/assets/css/main.css'; // ścieżka na dysku (../assets/css/main.css)
$cssVer = file_exists($cssFs) ? filemtime($cssFs) : time();
?>
<!DOCTYPE html>
<html lang="pl">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Bootstrap demo</title>

  <!-- Bootstrap CSS -->
  <link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
    rel="stylesheet"
    integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB"
    crossorigin="anonymous" />

  <!-- Twój skompilowany CSS z cache-busterem -->
  <link rel="stylesheet" href="<?= htmlspecialchars($cssUrl) ?>?v=<?= $cssVer ?>" />

  <!-- Font Awesome -->
  <link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    crossorigin="anonymous"
    referrerpolicy="no-referrer" />
</head>
