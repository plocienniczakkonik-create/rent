<?php
require 'includes/db.php';
$tables = db()->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
echo "=== DATABASE TABLES ===\n";
foreach ($tables as $t) {
    echo "$t\n";
}
echo "=== END ===\n";
