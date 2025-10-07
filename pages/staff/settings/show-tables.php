<?php
require_once __DIR__ . '/../../../includes/db.php';

function showTable($table) {
    $db = db();
    $stmt = $db->query("SHOW CREATE TABLE `$table`");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<h3>$table</h3><pre>" . htmlspecialchars($row['Create Table']) . "</pre>";
}
?><!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Struktura tabel bazy danych</title>
    <style>body{font-family:monospace;background:#f8f9fa;padding:2em;}pre{background:#fff;border:1px solid #ccc;padding:1em;}</style>
</head>
<body>
<h2>Struktura tabel: vehicles, products, dict_terms</h2>
<?php
showTable('vehicles');
showTable('products');
showTable('dict_terms');
?>
</body>
</html>