<?php

/**
 * Konfiguracja motywu - kolory, logo, branding
 */

class ThemeConfig
{
    // Domyślne kolory systemu
    private static $colors = [
        'primary' => '#6366f1',           // Fioletowy primary
        'primary_dark' => '#4f46e5',      // Ciemniejszy fioletowy
        'primary_light' => '#a5b4fc',     // Jaśniejszy fioletowy
        'secondary' => '#64748b',         // Szary secondary
        'success' => '#22c55e',           // Zielony success
        'warning' => '#f59e0b',           // Pomarańczowy warning
        'danger' => '#ef4444',            // Czerwony danger
        'info' => '#3b82f6',              // Niebieski info
        'light' => '#f8fafc',             // Jasny
        'dark' => '#1e293b',              // Ciemny
        'white' => '#ffffff',             // Biały
        'black' => '#000000'              // Czarny
    ];

    // Konfiguracja gradientów
    private static $gradients = [
        'primary' => 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
        'secondary' => 'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)',
        'success' => 'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)',
        'info' => 'linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)'
    ];

    // Konfiguracja brandingu
    private static $branding = [
        'use_logo' => false,              // true = logo, false = tekst
        'logo_path' => '/assets/img/logo.png',
        'brand_text' => 'CORONA',
        'logo_alt' => 'CORONA Logo'
    ];

    // Ścieżka do pliku z customowymi ustawieniami
    private static $config_file = __DIR__ . '/../config/theme-settings.json';

    /**
     * Pobiera kolor według klucza
     */
    public static function getColor($key)
    {
        $custom_colors = self::loadCustomSettings()['colors'] ?? [];
        return $custom_colors[$key] ?? self::$colors[$key] ?? '#000000';
    }

    /**
     * Pobiera gradient według klucza
     */
    public static function getGradient($key)
    {
        $custom_gradients = self::loadCustomSettings()['gradients'] ?? [];
        return $custom_gradients[$key] ?? self::$gradients[$key] ?? 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)';
    }

    /**
     * Pobiera ustawienia brandingu
     */
    public static function getBranding($key = null)
    {
        $custom_branding = self::loadCustomSettings()['branding'] ?? [];
        $branding = array_merge(self::$branding, $custom_branding);

        if ($key) {
            return $branding[$key] ?? null;
        }

        return $branding;
    }

    /**
     * Generuje CSS variables dla kolorów
     */
    public static function generateCSSVariables()
    {
        $custom_settings = self::loadCustomSettings();
        $colors = array_merge(self::$colors, $custom_settings['colors'] ?? []);
        $gradients = array_merge(self::$gradients, $custom_settings['gradients'] ?? []);

        $css = ":root {\n";

        // Kolory
        foreach ($colors as $key => $value) {
            $css .= "  --color-{$key}: {$value};\n";
        }

        // Gradienty
        foreach ($gradients as $key => $value) {
            $css .= "  --gradient-{$key}: {$value};\n";
        }

        $css .= "}\n";

        return $css;
    }

    /**
     * Renderuje brand (logo lub tekst) - obsługuje URLs i lokalne pliki
     */
    public static function renderBrand($class = '', $link = true)
    {
        $branding = self::getBranding();
        $brand_content = '';

        if ($branding['use_logo'] && !empty($branding['logo_path'])) {
            // Check if it's an external URL or local path
            if (filter_var($branding['logo_path'], FILTER_VALIDATE_URL)) {
                // External URL
                $brand_content = '<img src="' . htmlspecialchars($branding['logo_path']) . '" alt="' . htmlspecialchars($branding['logo_alt']) . '" class="brand-logo">';
            } else {
                // Local file path
                if (file_exists(dirname(__DIR__) . $branding['logo_path'])) {
                    $brand_content = '<img src="' . BASE_URL . $branding['logo_path'] . '" alt="' . htmlspecialchars($branding['logo_alt']) . '" class="brand-logo">';
                } else {
                    // Fallback to text if file doesn't exist
                    $brand_content = htmlspecialchars($branding['brand_text']);
                }
            }
        } else {
            $brand_content = htmlspecialchars($branding['brand_text']);
        }

        if ($link) {
            return '<a href="' . BASE_URL . '" class="brand-link ' . $class . '">' . $brand_content . '</a>';
        } else {
            return '<span class="brand-text ' . $class . '">' . $brand_content . '</span>';
        }
    }

    /**
     * Ładuje customowe ustawienia z pliku
     */
    private static function loadCustomSettings()
    {
        if (file_exists(self::$config_file)) {
            $json = file_get_contents(self::$config_file);
            return json_decode($json, true) ?? [];
        }
        return [];
    }

    /**
     * Zapisuje customowe ustawienia do pliku
     */
    public static function saveCustomSettings($settings)
    {
        $dir = dirname(self::$config_file);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        return file_put_contents(self::$config_file, json_encode($settings, JSON_PRETTY_PRINT));
    }

    /**
     * Pobiera wszystkie dostępne kolory
     */
    public static function getAllColors()
    {
        $custom_settings = self::loadCustomSettings();
        return array_merge(self::$colors, $custom_settings['colors'] ?? []);
    }

    /**
     * Pobiera wszystkie dostępne gradienty
     */
    public static function getAllGradients()
    {
        $custom_settings = self::loadCustomSettings();
        return array_merge(self::$gradients, $custom_settings['gradients'] ?? []);
    }
}

// Automatyczne includowanie CSS variables
function theme_generate_css_variables()
{
    return ThemeConfig::generateCSSVariables();
}

// Helper function do renderowania brandingu
function theme_render_brand($class = '', $link = true)
{
    return ThemeConfig::renderBrand($class, $link);
}
