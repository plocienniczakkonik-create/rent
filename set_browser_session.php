<?php
session_start();
$_SESSION['user_id'] = 3; // Staff user test2@example.com

echo "Sesja ustawiona dla przeglądarki.\n";
echo "User ID: " . $_SESSION['user_id'] . "\n";
echo "Teraz odwiedź: http://localhost/rental/index.php?page=dashboard-staff\n";
