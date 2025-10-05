<?php
require_once 'includes/theme-config.php';

echo "=== TEST SYSTEMU KOLORÓW BRANDOWYCH ===\n\n";

// Test 1: Sprawdź domyślne kolory
echo "1. Domyślne kolory:\n";
$colors = ThemeConfig::getAllColors();
foreach (['primary', 'secondary', 'success', 'warning', 'danger', 'info'] as $color) {
    echo "   $color: " . ThemeConfig::getColor($color) . "\n";
}

// Test 2: Sprawdź gradienty
echo "\n2. Gradienty:\n";
echo "   primary: " . ThemeConfig::getGradient('primary') . "\n";

// Test 3: Sprawdź CSS Variables
echo "\n3. CSS Variables:\n";
$css = ThemeConfig::generateCSSVariables();
echo substr($css, 0, 300) . "...\n";

// Test 4: Sprawdź customowe ustawienia
echo "\n4. Testowanie zapisywania customowych kolorów:\n";
$testSettings = [
    'colors' => [
        'primary' => '#8b5cf6',  // fioletowy
        'secondary' => '#64748b'
    ]
];

if (ThemeConfig::saveCustomSettings($testSettings)) {
    echo "   ✓ Zapisano testowe ustawienia\n";
    echo "   primary po zmianie: " . ThemeConfig::getColor('primary') . "\n";

    // Przywróć domyślne
    ThemeConfig::saveCustomSettings([]);
    echo "   ✓ Przywrócono domyślne ustawienia\n";
    echo "   primary po przywróceniu: " . ThemeConfig::getColor('primary') . "\n";
} else {
    echo "   ✗ Błąd podczas zapisywania\n";
}

echo "\n=== KONIEC TESTU ===\n";
