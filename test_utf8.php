<?php
require_once '../includes/db.php';

// Ustawienie kodowania UTF-8
$db = db();
$db->exec("SET NAMES utf8mb4 COLLATE utf8mb4_polish_ci");

echo "<meta charset='utf-8'>";
echo "<h2>Test polskich znaków w bazie danych</h2>";

// Test incydentów
$stmt = $db->prepare("SELECT incident_id, damage_desc FROM vehicle_incidents WHERE damage_desc LIKE '%ó%' OR damage_desc LIKE '%ą%' OR damage_desc LIKE '%gwóźdź%' LIMIT 5");
$stmt->execute();
$incidents = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h3>Incydenty z polskimi znakami:</h3>";
foreach ($incidents as $incident) {
    echo "ID: " . $incident['incident_id'] . " - " . $incident['damage_desc'] . "<br>";
}

// Test serwisów
$stmt = $db->prepare("SELECT service_id, issues_found FROM vehicle_services WHERE issues_found LIKE '%ó%' OR issues_found LIKE '%ą%' LIMIT 5");
$stmt->execute();
$services = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h3>Serwisy z polskimi znakami:</h3>";
foreach ($services as $service) {
    echo "ID: " . $service['service_id'] . " - " . $service['issues_found'] . "<br>";
}

echo "<br><strong>Test bezpośredni:</strong><br>";
echo "Przebita opona - gwóźdź<br>";
echo "Wymiana oleju i filtrów<br>";
echo "Stłuczka na parkingu<br>";
