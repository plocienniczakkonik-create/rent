<?php
session_start();

// Prosty router po ?page=
$view = $_GET['page'] ?? 'home';
$allowed = ['home', 'login', 'dashboard-client', 'dashboard-staff'];
if (!in_array($view, $allowed, true)) {
    $view = 'home';
}

include __DIR__ . '/partials/head.php';
?>

<body id="top">
    <div id="navSentinel" style="position:absolute; top:0; left:0; right:0; height:1px;"></div>

    <?php include __DIR__ . '/partials/header.php'; ?>

    <main class="site-main">
        <?php include __DIR__ . "/pages/{$view}.php"; ?>
    </main>

    <?php include __DIR__ . '/components/back-to-top.php'; ?>
    <?php include __DIR__ . '/components/site-footer.php'; ?>
    <?php include __DIR__ . '/partials/footer.php'; ?>
</body>

</html>