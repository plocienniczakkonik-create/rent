<?php
require_once __DIR__ . '/includes/config.php';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Włącz kaucje dla przykładowych produktów
    $stmt = $pdo->prepare("UPDATE products SET deposit_enabled = 1, deposit_type = 'fixed', deposit_amount = 200.00 WHERE id = 1");
    $stmt->execute();

    $stmt = $pdo->prepare("UPDATE products SET deposit_enabled = 1, deposit_type = 'percentage', deposit_amount = 10.00 WHERE id = 2");
    $stmt->execute();

    echo "Włączono kaucje dla produktów:\n";
    echo "- Produkt 1: Kaucja stała 200 PLN\n";
    echo "- Produkt 2: Kaucja 10% wartości wynajmu\n";

    // Sprawdź ustawienia
    $stmt = $pdo->query("SELECT id, name, deposit_enabled, deposit_type, deposit_amount FROM products WHERE deposit_enabled = 1");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "\nProdukty z włączoną kaucją:\n";
    foreach ($products as $product) {
        echo "- {$product['name']} (ID: {$product['id']}): {$product['deposit_type']} - {$product['deposit_amount']}\n";
    }
} catch (Exception $e) {
    echo "Błąd: " . $e->getMessage() . "\n";
}
