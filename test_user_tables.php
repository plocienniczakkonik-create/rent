<?php
require_once 'includes/db.php';

echo "SPRAWDZANIE TABEL UŻYTKOWNIKÓW:\n";
echo "═══════════════════════════════════\n\n";

$stmt = db()->query('SHOW TABLES');
$userTables = [];
while ($row = $stmt->fetch()) {
    $tableName = $row[0];
    if (stripos($tableName, 'user') !== false || stripos($tableName, 'staff') !== false || stripos($tableName, 'admin') !== false) {
        $userTables[] = $tableName;
        echo "✓ $tableName\n";
    }
}

if (empty($userTables)) {
    echo "❌ Brak tabel z użytkownikami\n";
}

echo "\n" . str_repeat("═", 40) . "\n";
echo "SPRAWDZANIE STRUKTURY TABELI UŻYTKOWNIKÓW:\n\n";

// Sprawdź czy istnieje jakaś tabela adminów/użytkowników
$possibleTables = ['admins', 'users', 'admin_users', 'staff', 'employees'];
foreach ($possibleTables as $table) {
    try {
        $stmt = db()->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "✓ Znaleziono tabelę: $table\n";

            $stmt = db()->query("DESCRIBE $table");
            while ($row = $stmt->fetch()) {
                echo "  - {$row['Field']} ({$row['Type']})\n";
            }

            $stmt = db()->query("SELECT COUNT(*) as cnt FROM $table");
            $count = $stmt->fetch()['cnt'];
            echo "  Liczba rekordów: $count\n\n";
        }
    } catch (Exception $e) {
        // Tabela nie istnieje
    }
}
