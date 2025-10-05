<?php
require_once dirname(__DIR__, 2) . '/includes/db.php';

echo "=== STRUKTURA TABELI USERS ===\n";
foreach (db()->query('DESCRIBE users')->fetchAll() as $r) {
    echo $r['Field'] . ' (' . $r['Type'] . ")\n";
}
