<?php
/**
 * Internationalization (i18n) Helper Functions
 * Multi-language support system
 */

require_once __DIR__ . '/lang-config.php';

class i18n {
    private static $loaded_translations = [];
    private static $current_admin_language = null;
    private static $current_frontend_language = null;
    
    /**
     * Initialize language system
     */
    public static function init() {
        global $default_admin_language, $default_frontend_language;
        
        // Start session if not started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Set admin language from user profile or session
        if (isset($_SESSION['user_id']) && is_numeric($_SESSION['user_id'])) {
            self::$current_admin_language = self::getUserLanguage($_SESSION['user_id']);
        } else {
            self::$current_admin_language = $_SESSION['admin_language'] ?? $default_admin_language;
        }
        
        // Set frontend language from session or default
        self::$current_frontend_language = $_SESSION['frontend_language'] ?? $default_frontend_language;
        
        // Load translations
        self::loadTranslations('admin', self::$current_admin_language);
        self::loadTranslations('frontend', self::$current_frontend_language);
        self::loadTranslations('client', self::$current_frontend_language);
    }
    
    /**
     * Get translation for a key
     */
    public static function __($key, $context = 'admin', $default = null) {
        $language = ($context === 'admin') ? self::$current_admin_language : self::$current_frontend_language;
        
        if (!isset(self::$loaded_translations[$context][$language])) {
            self::loadTranslations($context, $language);
        }
        
        return self::$loaded_translations[$context][$language][$key] ?? $default ?? $key;
    }
    
    /**
     * Load translations for specific context and language
     */
    private static function loadTranslations($context, $language) {
        $file = __DIR__ . "/lang/{$language}/{$context}.php";
        
        if (file_exists($file)) {
            $translations = include $file;
            self::$loaded_translations[$context][$language] = $translations;
        } else {
            self::$loaded_translations[$context][$language] = [];
        }
    }
    
    /**
     * Get user's preferred language from database
     */
    private static function getUserLanguage($user_id) {
        global $default_admin_language;
        
        try {
            require_once __DIR__ . '/db.php';
            $pdo = db();
            $stmt = $pdo->prepare("SELECT preferred_language FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $result = $stmt->fetch();
            
            return $result['preferred_language'] ?? $default_admin_language;
        } catch (Exception $e) {
            return $default_admin_language;
        }
    }
    
    /**
     * Set admin language for current session
     */
    public static function setAdminLanguage($language) {
        global $available_languages;
        
        if (isset($available_languages[$language]) && $available_languages[$language]['enabled']) {
            self::$current_admin_language = $language;
            $_SESSION['admin_language'] = $language;
            
            // Update user preference in database if logged in
            if (isset($_SESSION['user_id'])) {
                self::updateUserLanguage($_SESSION['user_id'], $language);
            }
            
            // Reload admin translations
            self::loadTranslations('admin', $language);
            return true;
        }
        return false;
    }
    
    /**
     * Set frontend language for current session
     */
    public static function setFrontendLanguage($language) {
        global $available_languages;
        
        if (isset($available_languages[$language]) && $available_languages[$language]['enabled']) {
            self::$current_frontend_language = $language;
            $_SESSION['frontend_language'] = $language;
            
            // Reload frontend and client translations
            self::loadTranslations('frontend', $language);
            self::loadTranslations('client', $language);
            return true;
        }
        return false;
    }
    
    /**
     * Update user language preference in database
     */
    private static function updateUserLanguage($user_id, $language) {
        try {
            require_once __DIR__ . '/db.php';
            $pdo = db();
            $stmt = $pdo->prepare("UPDATE users SET preferred_language = ? WHERE id = ?");
            $stmt->execute([$language, $user_id]);
        } catch (Exception $e) {
            // Silently fail - not critical
        }
    }
    
    /**
     * Get current admin language
     */
    public static function getAdminLanguage() {
        return self::$current_admin_language;
    }
    
    /**
     * Get current frontend language
     */
    public static function getFrontendLanguage() {
        return self::$current_frontend_language;
    }
    
    /**
     * Get available languages for context
     */
    public static function getAvailableLanguages() {
        global $available_languages;
        return array_filter($available_languages, function($lang) {
            return $lang['enabled'];
        });
    }
    
    /**
     * Generate language switcher HTML
     */
    public static function renderLanguageSwitcher($context = 'admin', $current_page = '') {
        $languages = self::getAvailableLanguages();
        $current_lang = ($context === 'admin') ? self::$current_admin_language : self::$current_frontend_language;
        
        $html = '<div class="dropdown">';
        $html .= '<button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">';
        $html .= '<i class="bi bi-translate"></i> ' . $languages[$current_lang]['flag'] . ' ' . $languages[$current_lang]['name'];
        $html .= '</button>';
        $html .= '<ul class="dropdown-menu">';
        
        foreach ($languages as $code => $lang) {
            $active = ($code === $current_lang) ? 'active' : '';
            $html .= '<li><a class="dropdown-item ' . $active . '" href="?set_language=' . $code . '&context=' . $context . '&return=' . urlencode($current_page) . '">';
            $html .= $lang['flag'] . ' ' . $lang['name'];
            $html .= '</a></li>';
        }
        
        $html .= '</ul></div>';
        return $html;
    }
}

/**
 * Global translation function
 */
function __($key, $context = 'admin', $default = null) {
    return i18n::__($key, $context, $default);
}

/**
 * Handle language switching requests
 */
if (isset($_GET['set_language']) && isset($_GET['context'])) {
    $language = $_GET['set_language'];
    $context = $_GET['context'];
    $return_url = $_GET['return'] ?? $_SERVER['HTTP_REFERER'] ?? '';
    
    if ($context === 'admin') {
        i18n::setAdminLanguage($language);
    } else {
        i18n::setFrontendLanguage($language);
    }
    
    // Redirect back to the page
    if ($return_url) {
        // Remove language parameters from return URL
        $return_url = preg_replace('/[?&]set_language=[^&]*/', '', $return_url);
        $return_url = preg_replace('/[?&]context=[^&]*/', '', $return_url);
        $return_url = preg_replace('/[?&]return=[^&]*/', '', $return_url);
        header('Location: ' . $return_url);
        exit;
    }
}
?>