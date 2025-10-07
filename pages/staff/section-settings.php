// --- USTAWIENIA BANERA COOKIES/RODO ---
if ($settings_section === 'gdpr' && $settings_subsection === 'banner') {
    $db = db();
    $msg = null;
    // Obsługa zapisu
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $privacy_url = trim($_POST['privacy_policy_url'] ?? '');
        $banner_text = trim($_POST['banner_text'] ?? '');
        $db->prepare("REPLACE INTO settings (setting_key, setting_value) VALUES (?, ?), (?, ?)")
            ->execute(['privacy_policy_url', $privacy_url, 'cookie_banner_text', $banner_text]);
        $msg = '<div class="alert alert-success">Zapisano ustawienia banera cookies/RODO.</div>';
    }
    // Pobierz aktualne wartości
    $privacy_url = $db->query("SELECT setting_value FROM settings WHERE setting_key = 'privacy_policy_url'")->fetchColumn() ?: '/rental/index.php?page=privacy-policy';
    $banner_text = $db->query("SELECT setting_value FROM settings WHERE setting_key = 'cookie_banner_text'")->fetchColumn() ?: 'Ta strona korzysta z plików cookies w celach opisanych w <a href=\"/rental/index.php?page=privacy-policy\">Polityce prywatności</a>.';
    echo $msg;
    ?>
    <div class="card mb-4"><div class="card-header"><b>Ustawienia banera cookies/RODO</b></div>
    <div class="card-body">
    <form method="post">
        <div class="mb-3">
            <label for="privacy_policy_url" class="form-label">Adres URL polityki prywatności</label>
            <input type="text" class="form-control" id="privacy_policy_url" name="privacy_policy_url" value="<?= htmlspecialchars($privacy_url) ?>" required>
        </div>
        <div class="mb-3">
            <label for="banner_text" class="form-label">Treść banera cookies (HTML dozwolony)</label>
            <textarea class="form-control" id="banner_text" name="banner_text" rows="3" required><?= htmlspecialchars($banner_text) ?></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Zapisz ustawienia</button>
    </form>
    </div></div>
    <?php
    return;
}
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
    ],
    'gdpr' => [
        'title' => 'RODO / GDPR',
        'icon' => 'bi-shield-lock',
        'subsections' => [
            'consents' => __('gdpr_consents', 'admin', 'Zgody użytkowników'),
            'requests' => __('gdpr_requests', 'admin', 'Żądania RODO'),
            'audit' => __('gdpr_audit', 'admin', 'Historia audytu RODO'),
            'banner' => 'Baner cookies/RODO'
        ]
    ],
];

// Walidacja sekcji i podsekcji
$debug_raw_section = $_GET['settings_section'] ?? '(brak)';
$debug_raw_subsection = $_GET['settings_subsection'] ?? '(brak)';
if (!isset($sections[$settings_section])) {
    echo '<div style="background:#f8d7da;color:#721c24;padding:8px 16px;margin-bottom:12px;border-radius:6px;font-weight:bold;">DEBUG: Nieprawidłowa sekcja: ' . htmlspecialchars($debug_raw_section) . '</div>';
    $settings_section = 'users';
}
$subsections_map = array_flip($sections[$settings_section]['subsections']);
// Akceptuj zarówno klucz jak i label
if (!isset($sections[$settings_section]['subsections'][$settings_subsection])) {
    if (isset($subsections_map[$settings_subsection])) {
        $settings_subsection = $subsections_map[$settings_subsection];
    }
}
if (!isset($sections[$settings_section]['subsections'][$settings_subsection])) {
    echo '<div style="background:#f8d7da;color:#721c24;padding:8px 16px;margin-bottom:12px;border-radius:6px;font-weight:bold;">DEBUG: Nieprawidłowa podsekcja: ' . htmlspecialchars($debug_raw_subsection) . '</div>';
    echo '<div class="alert alert-danger">Nieprawidłowa podsekcja ustawień: ' . htmlspecialchars($settings_subsection) . '</div>';
    return;
}

// Funkcja do generowania linków
function settings_link(string $section, string $subsection, string $label, string $current_section, string $current_subsection): string
{
    $BASE = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
    $active = ($section === $current_section && $subsection === $current_subsection) ? 'active' : '';
    // For GDPR tabs, always force full reload with anchor
    $force_reload = ($section === 'gdpr');
    $href = $BASE . '/index.php?page=dashboard-staff&section=settings&settings_section=' . $section . '&settings_subsection=' . $subsection . '#pane-settings';
    // Wszystkie sekcje mają ten sam styl (grafitowy, cienki)
    return '<a href="' . htmlspecialchars($href) . '" class="list-group-item list-group-item-action ' . $active . '" style="color:#343a40;font-weight:400;">' . htmlspecialchars($label) . '</a>';
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
                        // Usunięto debug banner

                        // Ładowanie odpowiedniej podsekcji
                        $gdpr_map = [
                            'consents' => 'gdpr-consents.php',
                            'requests' => 'gdpr-requests.php',
                            'audit' => 'gdpr-audit.php'
                        ];
                        if ($settings_section === 'gdpr' && isset($gdpr_map[$settings_subsection])) {
                            $subsection_file = __DIR__ . '/settings/' . $gdpr_map[$settings_subsection];
                        } else {
                            $subsection_file = __DIR__ . "/settings/{$settings_section}-{$settings_subsection}.php";
                        }
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