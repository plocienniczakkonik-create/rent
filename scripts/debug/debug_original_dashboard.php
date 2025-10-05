<?php
// debug_original_dashboard.php - sprawdzenie oryginalnego dashboard
session_start();

// Auto-login jako staff
require_once 'includes/db.php';
$stmt = db()->prepare('SELECT id FROM users WHERE email = ? AND role = ?');
$stmt->execute(['test2@example.com', 'staff']);
$user_id = $stmt->fetchColumn();
if ($user_id) {
    $_SESSION['user_id'] = $user_id;
}

echo "=== DEBUG Oryginalnego Dashboard ===\n";

// Spróbujmy załadować dashboard-staff.php i przechwycić błędy
ob_start();
try {
    include 'pages/dashboard-staff.php';
    $output = ob_get_contents();
    echo "Dashboard załadowany pomyślnie. Długość: " . strlen($output) . " znaków\n";

    // Sprawdźmy czy są błędy PHP w output
    if (strpos($output, 'Fatal error') !== false) {
        echo "ZNALEZIONO FATAL ERROR!\n";
        $lines = explode("\n", $output);
        foreach ($lines as $line) {
            if (strpos($line, 'Fatal error') !== false || strpos($line, 'Warning') !== false || strpos($line, 'Error') !== false) {
                echo "BŁĄD: $line\n";
            }
        }
    }

    // Sprawdźmy czy sekcje są includowane
    $sections = [
        'section-products.php' => 'Produkty',
        'section-promos.php' => 'Promocje',
        'section-reports.php' => 'Raporty',
        'section-dicts.php' => 'Słowniki',
        'section-upcoming.php' => 'Najbliższe',
        'section-settings.php' => 'Ustawienia'
    ];

    foreach ($sections as $file => $name) {
        if (strpos($output, $file) !== false) {
            echo "✓ $name - include wykonany\n";
        } else {
            echo "✗ $name - include NIE wykonany\n";
        }
    }

    // Sprawdźmy zawartość sekcji
    if (strpos($output, 'pane-promos') !== false) {
        echo "✓ Panel promocji znaleziony\n";
    } else {
        echo "✗ Panel promocji NIE znaleziony\n";
    }

    if (strpos($output, 'Promocje') !== false) {
        echo "✓ Tekst 'Promocje' znaleziony\n";
    } else {
        echo "✗ Tekst 'Promocje' NIE znaleziony\n";
    }
} catch (Exception $e) {
    echo "BŁĄD: " . $e->getMessage() . "\n";
} catch (ParseError $e) {
    echo "BŁĄD SKŁADNI: " . $e->getMessage() . "\n";
} catch (Error $e) {
    echo "BŁĄD PHP: " . $e->getMessage() . "\n";
} finally {
    ob_end_clean();
}
