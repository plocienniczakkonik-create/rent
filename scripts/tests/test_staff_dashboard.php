<?php
// test_staff_dashboard.php - test panelu pracownika
session_start();

// Symulujemy zalogowanie jako staff
$_SESSION['user_id'] = 3; // ID użytkownika test2@example.com

echo "=== Test panelu pracownika ===\n";
echo "Sesja user_id: " . ($_SESSION['user_id'] ?? 'brak') . "\n";

// Testujemy funkcje auth
require_once 'auth/auth.php';

try {
    $user = current_user();
    echo "Current user: ";
    if ($user) {
        echo "ID: {$user['id']}, Email: {$user['email']}, Role: {$user['role']}\n";
    } else {
        echo "brak\n";
    }

    echo "Sprawdzamy require_staff()...\n";
    $staff = require_staff();
    echo "require_staff() sukces: ID: {$staff['id']}, Email: {$staff['email']}, Role: {$staff['role']}\n";
} catch (Exception $e) {
    echo "Błąd: " . $e->getMessage() . "\n";
}

echo "\n=== Test include dashboard-staff.php ===\n";
// Sprawdzamy czy dashboard-staff.php ładuje się bez błędów
ob_start();
try {
    include 'pages/dashboard-staff.php';
    $output = ob_get_contents();
    echo "Dashboard-staff.php załadowany pomyślnie. Długość output: " . strlen($output) . " znaków\n";

    // Sprawdzamy czy zawiera nasze sekcje
    if (strpos($output, 'tab-promos') !== false) {
        echo "✓ Zakładka promocje znaleziona\n";
    } else {
        echo "✗ Zakładka promocje NIE znaleziona\n";
    }

    if (strpos($output, 'pane-promos') !== false) {
        echo "✓ Panel promocji znaleziony\n";
    } else {
        echo "✗ Panel promocji NIE znaleziony\n";
    }

    if (strpos($output, 'section-promos.php') !== false) {
        echo "✓ Include section-promos.php wykonany\n";
    } else {
        echo "✗ Include section-promos.php NIE wykonany\n";
    }
} catch (Exception $e) {
    echo "Błąd przy ładowaniu dashboard-staff.php: " . $e->getMessage() . "\n";
} finally {
    ob_end_clean();
}
