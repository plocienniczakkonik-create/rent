<?php
// Przekierowanie z przycisku "Zarezerwuj" na szczegóły produktu z formularzem rezerwacji
$sku = $_GET['sku'] ?? null;
if ($sku) {
    header('Location: product-details.php?sku=' . urlencode($sku));
    exit;
}
// Jeśli brak SKU, wróć na stronę główną
header('Location: index.php');
exit;
