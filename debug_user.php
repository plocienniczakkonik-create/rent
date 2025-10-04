<?php
require_once 'includes/db.php';
require_once 'auth/auth.php';

echo "=== Aktualny użytkownik ===\n";
$current = current_user();
if ($current) {
    echo "ID: " . $current['id'] . "\n";
    echo "Email: " . $current['email'] . "\n";
    echo "Rola: '" . $current['role'] . "'\n";
    echo "Imię: " . $current['first_name'] . "\n";
    echo "Nazwisko: " . $current['last_name'] . "\n";
    echo "is_active: " . $current['is_active'] . "\n";
} else {
    echo "Brak zalogowanego użytkownika\n";
}

echo "\n=== Session info ===\n";
echo "user_id w session: " . ($_SESSION['user_id'] ?? 'brak') . "\n";
?>