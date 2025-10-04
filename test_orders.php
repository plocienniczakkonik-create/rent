<?php
require_once 'includes/db.php';
require_once 'pages/staff/_helpers.php'; // Dodajemy staff helpers

echo "=== Sprawdzenie tabeli reservations ===\n";
try {
    $result = db()->query('SELECT COUNT(*) FROM reservations');
    echo 'Tabela reservations istnieje, liczba rekordów: ' . $result->fetchColumn() . "\n";
} catch (Exception $e) {
    echo 'BŁĄD z tabelą reservations: ' . $e->getMessage() . "\n";
}

echo "\n=== Test section-orders.php indywidualnie ===\n";
try {
    ob_start();
    include 'pages/staff/section-orders.php';
    $output = ob_get_contents();
    ob_end_clean();
    echo "section-orders.php załadowany, długość: " . strlen($output) . " znaków\n";
    if (strpos($output, 'Fatal error') !== false || strpos($output, 'Error') !== false) {
        echo "BŁĄD w section-orders.php!\n";
        echo substr($output, 0, 500) . "...\n";
    }
} catch (Exception $e) {
    echo "BŁĄD w section-orders.php: " . $e->getMessage() . "\n";
}
