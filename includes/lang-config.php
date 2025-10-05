<?php

/**
 * Language Configuration
 * Centralized language settings and available languages
 */

// Available languages
$available_languages = [
    'pl' => [
        'name' => 'Polski',
        'flag' => '🇵🇱',
        'enabled' => true
    ],
    'en' => [
        'name' => 'English',
        'flag' => '🇺🇸',
        'enabled' => true
    ],
    'de' => [
        'name' => 'Deutsch',
        'flag' => '🇩🇪',
        'enabled' => false // Future implementation
    ],
    'cs' => [
        'name' => 'Čeština',
        'flag' => '🇨🇿',
        'enabled' => false // Future implementation
    ],
    'uk' => [
        'name' => 'Українська',
        'flag' => '🇺🇦',
        'enabled' => false // Future implementation
    ]
];

// Default languages
$default_admin_language = 'pl';
$default_frontend_language = 'pl';

// Language contexts
$language_contexts = [
    'admin',    // Staff/Admin panel
    'client',   // Client dashboard
    'frontend'  // Public website
];
