<?php
session_start();

echo "<h3>Debug informacji o języku</h3>";
echo "<p><strong>Session ID:</strong> " . session_id() . "</p>";
echo "<p><strong>Frontend Language Session:</strong> " . ($_SESSION['frontend_language'] ?? 'BRAK') . "</p>";
echo "<p><strong>Admin Language Session:</strong> " . ($_SESSION['admin_language'] ?? 'BRAK') . "</p>";

require_once __DIR__ . '/includes/i18n.php';
i18n::init();

echo "<p><strong>Current Frontend Language:</strong> " . i18n::getFrontendLanguage() . "</p>";
echo "<p><strong>Current Admin Language:</strong> " . i18n::getAdminLanguage() . "</p>";

echo "<h4>Dostępne języki:</h4>";
$languages = i18n::getAvailableLanguages();
foreach ($languages as $code => $lang) {
    echo "<p>$code: {$lang['name']} ({$lang['flag']}) - " . ($lang['enabled'] ? 'WŁĄCZONY' : 'WYŁĄCZONY') . "</p>";
}

echo "<h4>Test przełącznika:</h4>";
echo i18n::renderLanguageSwitcher('frontend', '/rental/debug_language.php');

echo "<hr>";
echo "<h4>Test tłumaczenia:</h4>";
echo "<p>Test klucza 'nav_home': " . i18n::__('nav_home', 'frontend', 'HOME') . "</p>";
echo "<p>Test klucza 'car_rental_corona': " . i18n::__('car_rental_corona', 'frontend', 'Corona Car Rental') . "</p>";
