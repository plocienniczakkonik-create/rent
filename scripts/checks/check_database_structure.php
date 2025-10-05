<?php
// Sprawdzenie struktury bazy danych
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';

// Inicializacja PDO
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

echo "<h1>Sprawdzenie struktury bazy danych</h1>\n";

// Lista wszystkich tabel
echo "<h2>Dostępne tabele:</h2>\n";
$stmt = $pdo->query("SHOW TABLES");
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
echo "<ul>\n";
foreach ($tables as $table) {
    echo "<li>{$table}</li>\n";
}
echo "</ul>\n";

// Sprawdź tabele Fleet Management
echo "<h2>Tabele Fleet Management:</h2>\n";
$fleet_tables = ['locations', 'vehicle_location_history', 'deposits', 'location_fees'];
foreach ($fleet_tables as $table) {
    if (in_array($table, $tables)) {
        echo "<p>✓ {$table} - istnieje</p>\n";

        // Pokaż liczbę rekordów
        $stmt = $pdo->query("SELECT COUNT(*) FROM {$table}");
        $count = $stmt->fetchColumn();
        echo "<p>&nbsp;&nbsp;&nbsp;Liczba rekordów: {$count}</p>\n";
    } else {
        echo "<p>✗ {$table} - nie istnieje</p>\n";
    }
}

// Sprawdź tabele główne
echo "<h2>Tabele główne aplikacji:</h2>\n";
$main_tables = ['products', 'dict_terms', 'reservations'];
foreach ($main_tables as $table) {
    if (in_array($table, $tables)) {
        echo "<p>✓ {$table} - istnieje</p>\n";

        // Pokaż liczbę rekordów
        $stmt = $pdo->query("SELECT COUNT(*) FROM {$table}");
        $count = $stmt->fetchColumn();
        echo "<p>&nbsp;&nbsp;&nbsp;Liczba rekordów: {$count}</p>\n";

        // Dla products pokaż pojazdy
        if ($table === 'products') {
            $stmt = $pdo->query("SELECT COUNT(*) FROM {$table} WHERE category = 'vehicle'");
            $vehicle_count = $stmt->fetchColumn();
            echo "<p>&nbsp;&nbsp;&nbsp;Pojazdy: {$vehicle_count}</p>\n";
        }
    } else {
        echo "<p>✗ {$table} - nie istnieje</p>\n";
    }
}

// Sprawdź konfigurację
echo "<h2>Tabele konfiguracji:</h2>\n";
$config_tables = ['shop_general', 'config'];
foreach ($config_tables as $table) {
    if (in_array($table, $tables)) {
        echo "<p>✓ {$table} - istnieje</p>\n";

        $stmt = $pdo->query("SELECT COUNT(*) FROM {$table}");
        $count = $stmt->fetchColumn();
        echo "<p>&nbsp;&nbsp;&nbsp;Liczba rekordów: {$count}</p>\n";
    } else {
        echo "<p>✗ {$table} - nie istnieje</p>\n";
    }
}
