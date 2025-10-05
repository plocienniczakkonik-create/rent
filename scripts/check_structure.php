<?php
require_once __DIR__ . '/../includes/db.php';

$pdo = db();
$stmt = $pdo->query('DESCRIBE reservations');
echo "=== STRUKTURA TABELI RESERVATIONS ===\n";
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $row['Field'] . ' (' . $row['Type'] . ")\n";
}
