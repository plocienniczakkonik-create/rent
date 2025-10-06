<?php
// /pages/staff/section-settings.php
require_once dirname(__DIR__, 2) . '/includes/db.php';
require_once dirname(__DIR__, 2) . '/includes/_helpers.php';

$BASE = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';

// Pobierz aktualną sekcję i podsekcję
$settings_section = $_GET['settings_section'] ?? 'users';
$settings_subsection = $_GET['settings_subsection'] ?? 'list';

// Definicja dostępnych sekcji
$sections = [
    'users' => [
        'title' => __('users', 'admin', 'Użytkownicy'),
        'icon' => 'bi-people',
        'subsections' => [
            'list' => __('all_users', 'admin', 'Wszyscy użytkownicy'),
            'add' => __('add_user', 'admin', 'Dodaj użytkownika')
        ] + (isset($_GET['user_id']) && (int)$_GET['user_id'] > 0 ? [
            'edit' => __('edit_user', 'admin', 'Edytuj użytkownika')
        ] : [])
    ],
    'account' => [
        'title' => __('account', 'admin', 'Konto'),
        'icon' => 'bi-person-circle',
        'subsections' => [
            'profile' => __('account_settings', 'admin', 'Ustawienia konta')
        ]
    ],
    'payments' => [
        'title' => __('payments', 'admin', 'Płatności'),
        'icon' => 'bi-credit-card',
        'subsections' => [
            'general' => __('general_settings', 'admin', 'Ustawienia ogólne'),
            'gateways' => __('payment_gateways', 'admin', 'Bramki płatności')
        ]
    ],
    'shop' => [
        'title' => __('shop', 'admin', 'Sklep'),
        'icon' => 'bi-shop',
        'subsections' => [
            'general' => __('general_settings', 'admin', 'Ustawienia ogólne'),
            'integrations' => __('integrations', 'admin', 'Integracje')
        ]
    ],
    'theme' => [
        'title' => __('theme_appearance', 'admin', 'Wygląd i kolory'),
        'icon' => 'bi-palette',
        'subsections' => [
            'colors' => __('theme_colors', 'admin', 'Kolory i motyw'),
            'branding' => __('theme_branding', 'admin', 'Branding')
        ]
    ],
    'email' => [
        'title' => __('email', 'admin', 'Email'),
        'icon' => 'bi-envelope',
        'subsections' => [
            'templates' => __('email_templates', 'admin', 'Szablony emaili'),
            'smtp' => __('smtp_configuration', 'admin', 'Konfiguracja SMTP')
        ]
    ]
];

// Walidacja sekcji i podsekcji
if (!isset($sections[$settings_section])) {
    $settings_section = 'users';
}
if (!isset($sections[$settings_section]['subsections'][$settings_subsection])) {
    $settings_subsection = array_key_first($sections[$settings_section]['subsections']);
}

// Funkcja do generowania linków
function settings_link(string $section, string $subsection, string $label, string $current_section, string $current_subsection): string
{
    $BASE = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
    $active = ($section === $current_section && $subsection === $current_subsection) ? 'active' : '';
    $href = $BASE . '/index.php?page=dashboard-staff&section=settings&settings_section=' . $section . '&settings_subsection=' . $subsection . '#pane-settings';

    return '<a href="' . htmlspecialchars($href) . '" class="list-group-item list-group-item-action ' . $active . '">' . htmlspecialchars($label) . '</a>';
}
?>

<style>
    .settings-container {
        display: flex;
        gap: 1.5rem;
        min-height: 600px;
    }

    .settings-sidebar {
        width: 260px;
        flex-shrink: 0;
        position: relative;
        z-index: 10;
    }

    .settings-sidebar .list-group {
        position: relative;
        z-index: 11;
    }

    .settings-sidebar .list-group-item {
        pointer-events: auto !important;
        cursor: pointer !important;
        position: relative;
        z-index: 12;
    }

    .settings-content {
        flex: 1;
        min-width: 0;
        overflow-x: auto;
        position: relative;
        z-index: 5;
    }

    .settings-section-title {
        font-size: 0.875rem;
        font-weight: 700;
        color: #343a40;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 0.5rem;
        padding: 0.75rem 1rem 0.25rem;
    }

    .settings-subsection {
        font-size: 0.875rem;
        padding-left: 2rem;
    }

    .list-group-item.active {
        background-color: #f8f9fa;
        border-color: #dee2e6;
        border-top: 0px;
        border-left: 4px solid var(--color-primary);
        color: #343a40;
        pointer-events: auto !important;
        position: relative;
    }

    .list-group-item.active::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 4px;
        height: 100%;
        background: linear-gradient(45deg, var(--color-primary), var(--color-primary-dark));
        border-radius: 4px 0 0 4px;
    }

    .list-group-item:hover:not(.active) {
        background-color: #f8f9fa;
        pointer-events: auto !important;
    }

    .list-group-item a,
    .settings-sidebar a,
    .settings-sidebar .list-group-item {
        pointer-events: auto !important;
        cursor: pointer !important;
        text-decoration: none !important;
        display: block !important;
        position: relative !important;
        z-index: 15 !important;
    }

    @media (max-width: 768px) {
        .settings-container {
            flex-direction: column;
        }

        .settings-sidebar {
            width: 100%;
        }

        .settings-content {
            overflow-x: auto;
        }
    }

    /* Szersze tabele w ustawieniach */
    .card-body .table-responsive {
        margin: -1rem -1rem 0;
        padding: 1rem;
    }

    .table-responsive .table {
        margin-bottom: 0;
    }
</style>

<div class="card section-settings">
    <div class="card-header d-flex align-items-center justify-content-between" style="background: var(--gradient-primary); color: white; border-bottom: 1px solid var(--color-primary-dark);">
        <h2 class="h6 mb-0"><i class="bi bi-gear me-2"></i><?= __('system_settings', 'admin', 'Ustawienia systemu') ?></h2>
    </div>
    <div class="card-body">
        <div class="settings-container">
            <!-- Boczne menu -->
            <div class="settings-sidebar">
                <div class="list-group">
                    <?php foreach ($sections as $section_key => $section_data): ?>
                        <div class="settings-section-title">
                            <i class="<?= $section_data['icon'] ?>"></i> <?= $section_data['title'] ?>
                        </div>
                        <?php foreach ($section_data['subsections'] as $subsection_key => $subsection_title): ?>
                            <?= settings_link($section_key, $subsection_key, $subsection_title, $settings_section, $settings_subsection) ?>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Główna zawartość -->
            <div class="settings-content">
                <div class="card">
                    <div class="card-header">
                        <h3 class="h6 mb-0">
                            <i class="<?= $sections[$settings_section]['icon'] ?>"></i>
                            <?= $sections[$settings_section]['title'] ?> - <?= $sections[$settings_section]['subsections'][$settings_subsection] ?>
                        </h3>
                    </div>
                    <div class="card-body">
                        <?php
                        // Ładowanie odpowiedniej podsekcji
                        $subsection_file = __DIR__ . "/settings/{$settings_section}-{$settings_subsection}.php";
                        if (file_exists($subsection_file)) {
                            include $subsection_file;
                        } else {
                            echo '<div class="alert alert-info">';
                            echo '<h5>' . __('under_construction', 'admin', 'W trakcie budowy') . '</h5>';
                            echo '<p>' . __('section', 'admin', 'Sekcja') . ' <strong>' . htmlspecialchars($sections[$settings_section]['subsections'][$settings_subsection]) . '</strong> ' . __('section_under_implementation', 'admin', 'jest obecnie w trakcie implementacji.') . '</p>';
                            echo '<p class="mb-0">' . __('planned_features', 'admin', 'Planowane funkcje') . ':</p>';
                            echo '<ul class="mb-0">';

                            // Opis funkcji dla każdej sekcji
                            $descriptions = [
                                'users-list' => ['Lista wszystkich użytkowników', 'Zarządzanie rolami i uprawnieniami', 'Historia logowań', 'Blokowanie/odblokowywanie kont'],
                                'users-add' => ['Formularz dodawania nowego użytkownika', 'Nadawanie ról (admin/staff/user)', 'Generowanie hasła tymczasowego', 'Wysyłanie emaila powitalnego'],
                                'account-profile' => ['Edycja danych osobowych', 'Zmiana hasła', 'Ustawienia preferencji', 'Historia aktywności'],
                                'payments-general' => ['Ustawienia waluty i VAT', 'Konfiguracja kaucji', 'Zasady zwrotów', 'Limity czasowe płatności'],
                                'payments-gateways' => ['Konfiguracja Stripe', 'Konfiguracja PayPal', 'Konfiguracja Przelewy24', 'Testowanie połączeń'],
                                'shop-general' => ['Ustawienia waluty', 'Strefa czasowa', 'Język domyślny', 'Konfiguracja podatków'],
                                'email-templates' => ['Szablon nowego zamówienia', 'Szablon anulowania', 'Szablon potwierdzenia', 'Edytor WYSIWYG'],
                                'email-smtp' => ['Konfiguracja serwera SMTP', 'Test wysyłki emaili', 'Ustawienia SSL/TLS', 'Historia wysłanych emaili']
                            ];

                            $current_key = $settings_section . '-' . $settings_subsection;
                            if (isset($descriptions[$current_key])) {
                                foreach ($descriptions[$current_key] as $desc) {
                                    echo '<li>' . htmlspecialchars($desc) . '</li>';
                                }
                            }
                            echo '</ul>';
                            echo '</div>';
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>