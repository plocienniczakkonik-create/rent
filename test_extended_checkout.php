<?php
require_once 'includes/config.php';
require_once 'includes/db.php';

$pdo = db();

echo "=== TEST ROZSZERZONEGO FORMULARZA CHECKOUT ===\n\n";

// Sprawdź strukture tabeli reservations
echo "1. Sprawdzenie struktury tabeli reservations:\n";
$stmt = $pdo->query("DESCRIBE reservations");
$columns = [];
while ($row = $stmt->fetch()) {
    $columns[] = $row['Field'];
    if (strpos($row['Field'], 'billing_') === 0 || strpos($row['Field'], 'customer_') === 0) {
        echo "   ✅ {$row['Field']}: {$row['Type']}\n";
    }
}

echo "\n2. Test walidacji numeru telefonu:\n";
$testPhones = [
    '+48123456789' => true,
    '123456789' => false,
    '+1234567890' => true,
    '+' => false,
    '+48 123 456 789' => false, // spacje nie są dozwolone w regex
];

foreach ($testPhones as $phone => $shouldBeValid) {
    $isValid = preg_match('/^\+[1-9]\d{1,14}$/', $phone);
    $result = $isValid ? 'VALID' : 'INVALID';
    $expected = $shouldBeValid ? 'VALID' : 'INVALID';
    $status = ($isValid == $shouldBeValid) ? '✅' : '❌';
    echo "   {$status} '{$phone}' -> {$result} (oczekiwano: {$expected})\n";
}

echo "\n3. Test symulacji danych checkout:\n";
$testData = [
    'customer_name' => 'Jan Kowalski',
    'customer_email' => 'jan.kowalski@test.pl',
    'customer_phone' => '+48123456789',
    'billing_address' => 'ul. Testowa 123',
    'billing_city' => 'Warszawa',
    'billing_postcode' => '00-001',
    'billing_country' => 'PL'
];

$errors = [];
if ($testData['customer_name'] === '' || $testData['customer_email'] === '' || $testData['customer_phone'] === '') {
    $errors[] = 'Uzupełnij dane kontaktowe.';
}
if ($testData['billing_address'] === '' || $testData['billing_city'] === '' || $testData['billing_country'] === '') {
    $errors[] = 'Uzupełnij adres rozliczeniowy.';
}
if (!preg_match('/^\+[1-9]\d{1,14}$/', $testData['customer_phone'])) {
    $errors[] = 'Numer telefonu musi zawierać kod kraju (np. +48123456789).';
}

if (empty($errors)) {
    echo "   ✅ Wszystkie walidacje przeszły pomyślnie\n";
    echo "   Dane testowe:\n";
    foreach ($testData as $key => $value) {
        echo "     - {$key}: {$value}\n";
    }
} else {
    echo "   ❌ Błędy walidacji:\n";
    foreach ($errors as $error) {
        echo "     - {$error}\n";
    }
}

echo "\n4. Sprawdzenie czy kolumny istnieją w tabeli:\n";
$requiredColumns = ['customer_phone', 'billing_address', 'billing_city', 'billing_postcode', 'billing_country'];
foreach ($requiredColumns as $col) {
    if (in_array($col, $columns)) {
        echo "   ✅ {$col}\n";
    } else {
        echo "   ❌ Brak kolumny: {$col}\n";
    }
}

echo "\n=== Test rozszerzonego checkout zakończony ===\n";
