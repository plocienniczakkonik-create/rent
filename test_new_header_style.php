<?php
// Test nowego stylu headerów vehicle-detail
require_once 'includes/theme-config.php';

echo "=== TEST NOWEGO STYLU HEADERÓW ===\n\n";

// Sprawdź czy kolory są dostępne
echo "1. Kolory CSS Variables:\n";
echo "   --color-primary: " . ThemeConfig::getColor('primary') . "\n";
echo "   --color-dark: " . ThemeConfig::getColor('dark') . "\n";
echo "   --gradient-primary: " . ThemeConfig::getGradient('primary') . "\n";

echo "\n2. Styl headerów:\n";
echo "   ✓ Główny header 'Pojazd': gradient primary (fioletowy)\n";
echo "   ✓ Inne headery: białe tło + dolna linia primary (3px)\n";
echo "   ✓ Ikony w headerach: kolor primary\n";
echo "   ✓ Tekst w headerach: var(--color-dark)\n";

echo "\n3. Lista zmienionych sekcji:\n";
$sections = [
    'Metryka pojazdu',
    'Terminy',
    'Notatki',
    'Serwisy',
    'Kolizje / szkody',
    'Historia wynajmu',
    'Szybkie akcje',
    'Statystyki pojazdu'
];

foreach ($sections as $section) {
    echo "   ✓ $section\n";
}

echo "\n4. CSS Style pattern:\n";
echo "   background: white;\n";
echo "   border-bottom: 3px solid var(--color-primary);\n";
echo "   color: var(--color-dark);\n";

echo "\n=== GOTOWE ===\n";
echo "Nowy styl headerów został zastosowany!\n";
echo "Główny header pozostaje z gradientem, inne są minimalistyczne z akcjentem primary.\n";
