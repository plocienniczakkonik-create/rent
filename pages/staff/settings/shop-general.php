<?php
// Zawartość dla shop-general w dashboard-staff
require_once dirname(__DIR__, 3) . '/includes/db.php';
require_once dirname(__DIR__, 3) . '/includes/i18n.php';

// Initialize i18n if not already done
if (!class_exists('i18n') || !method_exists('i18n', 'getAdminLanguage')) {
    i18n::init();
}

$db = db();

// Pobierz realne lokalizacje z bazy danych
$locations = [];
try {
    $stmt = $db->query("SELECT id, name, city FROM locations WHERE is_active = 1 ORDER BY name");
    $locations = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // W przypadku błędu, lokalizacje zostaną puste
}

// Pobierz ustawienia sklepu
$shop_settings = [];
$stmt = $db->query("SELECT setting_key, setting_value FROM shop_settings");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $shop_settings[$row['setting_key']] = $row['setting_value'];
}

// Obsługa zapisywania ustawień
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_shop_settings'])) {
    try {
        $db->beginTransaction();

        $stmt = $db->prepare("
            INSERT INTO shop_settings (setting_key, setting_value) 
            VALUES (?, ?) 
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
        ");

        $settings_to_save = [
            // Informacje biznesowe
            'company_name' => $_POST['company_name'] ?? '',
            'company_address' => $_POST['company_address'] ?? '',
            'company_city' => $_POST['company_city'] ?? '',
            'company_postal_code' => $_POST['company_postal_code'] ?? '',
            'company_country' => $_POST['company_country'] ?? 'Polska',
            'company_phone' => $_POST['company_phone'] ?? '',
            'company_email' => $_POST['company_email'] ?? '',
            'company_website' => $_POST['company_website'] ?? '',
            'company_nip' => $_POST['company_nip'] ?? '',
            'company_regon' => $_POST['company_regon'] ?? '',

            // Ustawienia regionalne
            'default_currency' => $_POST['default_currency'] ?? 'PLN',
            'timezone' => $_POST['timezone'] ?? 'Europe/Warsaw',
            'date_format' => $_POST['date_format'] ?? 'd.m.Y',
            'time_format' => $_POST['time_format'] ?? 'H:i',

            // Podatki i ceny
            'default_tax_rate' => $_POST['default_tax_rate'] ?? '23',
            'tax_included_in_prices' => isset($_POST['tax_included_in_prices']) ? '1' : '0',
            'show_prices_with_tax' => isset($_POST['show_prices_with_tax']) ? '1' : '0',
            'currency_symbol_position' => $_POST['currency_symbol_position'] ?? 'after',
            'decimal_places' => $_POST['decimal_places'] ?? '2',
            'thousand_separator' => $_POST['thousand_separator'] ?? ' ',
            'decimal_separator' => $_POST['decimal_separator'] ?? ',',

            // System zarządzania flotą
            'fleet_management_enabled' => isset($_POST['fleet_management_enabled']) ? '1' : '0',
            'fleet_default_location' => $_POST['fleet_default_location'] ?? '',
            'fleet_auto_update_location' => isset($_POST['fleet_auto_update_location']) ? '1' : '0',
            'fleet_require_location_selection' => isset($_POST['fleet_require_location_selection']) ? '1' : '0',

            // System kaucji zwrotnych
            'deposits_enabled' => isset($_POST['deposits_enabled']) ? '1' : '0',
            'global_deposit_override' => isset($_POST['global_deposit_override']) ? '1' : '0',
            'default_deposit_type' => $_POST['default_deposit_type'] ?? 'fixed',
            'default_deposit_value' => $_POST['default_deposit_value'] ?? '200',
            'deposits_include_in_payment' => isset($_POST['deposits_include_in_payment']) ? '1' : '0',

            // Ustawienia rezerwacji
            'min_rental_hours' => $_POST['min_rental_hours'] ?? '24',
            'max_rental_days' => $_POST['max_rental_days'] ?? '30',
            'advance_booking_days' => $_POST['advance_booking_days'] ?? '365',
            'cancellation_hours' => $_POST['cancellation_hours'] ?? '24',
            'auto_confirmation' => isset($_POST['auto_confirmation']) ? '1' : '0',

            // Powiadomienia
            'notification_new_booking' => isset($_POST['notification_new_booking']) ? '1' : '0',
            'notification_cancellation' => isset($_POST['notification_cancellation']) ? '1' : '0',
            'notification_payment' => isset($_POST['notification_payment']) ? '1' : '0',
            'notification_email' => $_POST['notification_email'] ?? '',
        ];

        foreach ($settings_to_save as $key => $value) {
            $stmt->execute([$key, $value]);
        }

        $db->commit();
        $success_message = __('settings_saved_successfully', 'admin', 'Ustawienia zostały zapisane pomyślnie.');

        // Odśwież ustawienia
        $shop_settings = array_merge($shop_settings, $settings_to_save);
    } catch (Exception $e) {
        $db->rollBack();
        $error_message = __('error_saving_settings', 'admin', 'Wystąpił błąd podczas zapisywania ustawień.') . ' ' . $e->getMessage();
    }
}

// Dostępne kraje
$countries = [
    'Polska',
    'Niemcy',
    'Czechy',
    'Słowacja',
    'Litwa',
    'Łotwa',
    'Estonia',
    'Ukraina',
    'Białoruś',
    'Rosja',
    'Francja',
    'Hiszpania',
    'Włochy',
    'Wielka Brytania'
];

// Dostępne waluty
$currencies = [
    'PLN' => 'PLN - Polski złoty',
    'EUR' => 'EUR - Euro',
    'USD' => 'USD - Dolar amerykański',
    'GBP' => 'GBP - Funt brytyjski',
    'CZK' => 'CZK - Korona czeska',
    'UAH' => 'UAH - Hrywna ukraińska'
];

// Strefy czasowe
$timezones = [
    'Europe/Warsaw' => 'Europa/Warszawa (GMT+1)',
    'Europe/Berlin' => 'Europa/Berlin (GMT+1)',
    'Europe/Prague' => 'Europa/Praga (GMT+1)',
    'Europe/London' => 'Europa/Londyn (GMT+0)',
    'Europe/Paris' => 'Europa/Paryż (GMT+1)',
    'UTC' => 'UTC (GMT+0)'
];
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-1"><?= __('shop_general_settings', 'admin', 'Ustawienia sklepu') ?></h5>
        <p class="text-muted mb-0"><?= __('shop_configuration', 'admin', 'Konfiguracja biznesowa i regionalna') ?></p>
    </div>
</div>

<?php if (isset($success_message)): ?>
    <div class="alert alert-success alert-dismissible auto-fade" id="successAlert">
        <i class="bi bi-check-circle"></i>
        <?= $success_message ?>
    </div>
<?php endif; ?>

<?php if (isset($error_message)): ?>
    <div class="alert alert-danger">
        <i class="bi bi-exclamation-triangle"></i>
        <?= $error_message ?>
    </div>
<?php endif; ?>

<form method="POST">
    <div class="row g-4">
        <!-- Informacje o firmie -->
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0"><i class="bi bi-building"></i> <?= __('business_information', 'admin', 'Informacje o firmie') ?></h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label"><?= __('company_name', 'admin', 'Nazwa firmy') ?></label>
                            <input type="text" name="company_name" class="form-control"
                                value="<?= htmlspecialchars($shop_settings['company_name'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><?= __('company_email', 'admin', 'Email firmy') ?></label>
                            <input type="email" name="company_email" class="form-control"
                                value="<?= htmlspecialchars($shop_settings['company_email'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><?= __('company_phone', 'admin', 'Telefon') ?></label>
                            <input type="text" name="company_phone" class="form-control"
                                value="<?= htmlspecialchars($shop_settings['company_phone'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><?= __('company_website', 'admin', 'Strona internetowa') ?></label>
                            <input type="url" name="company_website" class="form-control"
                                value="<?= htmlspecialchars($shop_settings['company_website'] ?? '') ?>">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label"><?= __('company_address', 'admin', 'Adres') ?></label>
                            <input type="text" name="company_address" class="form-control"
                                value="<?= htmlspecialchars($shop_settings['company_address'] ?? '') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label"><?= __('company_city', 'admin', 'Miasto') ?></label>
                            <input type="text" name="company_city" class="form-control"
                                value="<?= htmlspecialchars($shop_settings['company_city'] ?? '') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label"><?= __('company_postal_code', 'admin', 'Kod pocztowy') ?></label>
                            <input type="text" name="company_postal_code" class="form-control"
                                value="<?= htmlspecialchars($shop_settings['company_postal_code'] ?? '') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label"><?= __('company_country', 'admin', 'Kraj') ?></label>
                            <select name="company_country" class="form-select">
                                <?php foreach ($countries as $country): ?>
                                    <option value="<?= $country ?>"
                                        <?= ($shop_settings['company_country'] ?? '') === $country ? 'selected' : '' ?>>
                                        <?= $country ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><?= __('company_nip', 'admin', 'NIP') ?></label>
                            <input type="text" name="company_nip" class="form-control"
                                value="<?= htmlspecialchars($shop_settings['company_nip'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><?= __('company_regon', 'admin', 'REGON') ?></label>
                            <input type="text" name="company_regon" class="form-control"
                                value="<?= htmlspecialchars($shop_settings['company_regon'] ?? '') ?>">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ustawienia regionalne -->
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0"><i class="bi bi-globe"></i> <?= __('regional_settings', 'admin', 'Ustawienia regionalne') ?></h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label"><?= __('default_currency', 'admin', 'Główna waluta') ?></label>
                        <select name="default_currency" class="form-select">
                            <?php foreach ($currencies as $code => $name): ?>
                                <option value="<?= $code ?>"
                                    <?= ($shop_settings['default_currency'] ?? '') === $code ? 'selected' : '' ?>>
                                    <?= $name ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label"><?= __('timezone', 'admin', 'Strefa czasowa') ?></label>
                        <select name="timezone" class="form-select">
                            <?php foreach ($timezones as $tz => $name): ?>
                                <option value="<?= $tz ?>"
                                    <?= ($shop_settings['timezone'] ?? '') === $tz ? 'selected' : '' ?>>
                                    <?= $name ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label"><?= __('date_format', 'admin', 'Format daty') ?></label>
                            <select name="date_format" class="form-select">
                                <option value="d.m.Y" <?= ($shop_settings['date_format'] ?? '') === 'd.m.Y' ? 'selected' : '' ?>>dd.mm.yyyy</option>
                                <option value="Y-m-d" <?= ($shop_settings['date_format'] ?? '') === 'Y-m-d' ? 'selected' : '' ?>>yyyy-mm-dd</option>
                                <option value="m/d/Y" <?= ($shop_settings['date_format'] ?? '') === 'm/d/Y' ? 'selected' : '' ?>>mm/dd/yyyy</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label"><?= __('time_format', 'admin', 'Format czasu') ?></label>
                            <select name="time_format" class="form-select">
                                <option value="H:i" <?= ($shop_settings['time_format'] ?? '') === 'H:i' ? 'selected' : '' ?>>24h</option>
                                <option value="g:i A" <?= ($shop_settings['time_format'] ?? '') === 'g:i A' ? 'selected' : '' ?>>12h AM/PM</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Podatki i formatowanie cen -->
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0"><i class="bi bi-calculator"></i> <?= __('taxes_and_prices', 'admin', 'Podatki i ceny') ?></h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label"><?= __('default_tax_rate', 'admin', 'Domyślna stawka VAT (%)') ?></label>
                        <input type="number" name="default_tax_rate" class="form-control"
                            value="<?= htmlspecialchars($shop_settings['default_tax_rate'] ?? '') ?>"
                            min="0" max="100" step="0.01">
                    </div>

                    <div class="form-check form-switch mb-3">
                        <input type="checkbox" name="tax_included_in_prices" class="form-check-input"
                            id="tax_included" <?= ($shop_settings['tax_included_in_prices'] ?? '') === '1' ? 'checked' : '' ?>>
                        <label class="form-check-label" for="tax_included">
                            <?= __('tax_included_in_prices', 'admin', 'Ceny zawierają VAT') ?>
                        </label>
                    </div>

                    <div class="form-check form-switch mb-3">
                        <input type="checkbox" name="show_prices_with_tax" class="form-check-input"
                            id="show_with_tax" <?= ($shop_settings['show_prices_with_tax'] ?? '') === '1' ? 'checked' : '' ?>>
                        <label class="form-check-label" for="show_with_tax">
                            <?= __('show_prices_with_vat', 'admin', 'Wyświetlaj ceny z VAT') ?>
                        </label>
                    </div>

                    <div class="mb-3">
                        <label class="form-label"><?= __('currency_position', 'admin', 'Pozycja symbolu waluty') ?></label>
                        <select name="currency_symbol_position" class="form-select">
                            <option value="before" <?= ($shop_settings['currency_symbol_position'] ?? '') === 'before' ? 'selected' : '' ?>><?= __('currency_before_price', 'admin', 'Przed ceną (€100)') ?></option>
                            <option value="after" <?= ($shop_settings['currency_symbol_position'] ?? '') === 'after' ? 'selected' : '' ?>><?= __('currency_after_price', 'admin', 'Po cenie (100 zł)') ?></option>
                        </select>
                    </div>

                    <div class="row g-2">
                        <div class="col-4">
                            <label class="form-label"><?= __('decimal_places', 'admin', 'Miejsca dziesiętne') ?></label>
                            <select name="decimal_places" class="form-select">
                                <option value="0" <?= ($shop_settings['decimal_places'] ?? '') === '0' ? 'selected' : '' ?>>0</option>
                                <option value="2" <?= ($shop_settings['decimal_places'] ?? '') === '2' ? 'selected' : '' ?>>2</option>
                            </select>
                        </div>
                        <div class="col-4">
                            <label class="form-label"><?= __('thousand_separator', 'admin', 'Sep. tysięcy') ?></label>
                            <select name="thousand_separator" class="form-select">
                                <option value=" " <?= ($shop_settings['thousand_separator'] ?? '') === ' ' ? 'selected' : '' ?>><?= __('space', 'admin', 'Spacja') ?></option>
                                <option value="," <?= ($shop_settings['thousand_separator'] ?? '') === ',' ? 'selected' : '' ?>>,</option>
                                <option value="." <?= ($shop_settings['thousand_separator'] ?? '') === '.' ? 'selected' : '' ?>>.</option>
                            </select>
                        </div>
                        <div class="col-4">
                            <label class="form-label"><?= __('decimal_separator', 'admin', 'Sep. dziesiętny') ?></label>
                            <select name="decimal_separator" class="form-select">
                                <option value="," <?= ($shop_settings['decimal_separator'] ?? '') === ',' ? 'selected' : '' ?>>,</option>
                                <option value="." <?= ($shop_settings['decimal_separator'] ?? '') === '.' ? 'selected' : '' ?>>.</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Zarządzanie flotą -->
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0"><i class="bi bi-geo-alt-fill"></i> <?= __('fleet_management', 'admin', 'Zarządzanie flotą') ?></h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="fleet_management_enabled"
                                    name="fleet_management_enabled" <?= ($shop_settings['fleet_management_enabled'] ?? '0') == '1' ? 'checked' : '' ?>>
                                <label class="form-check-label" for="fleet_management_enabled">
                                    <strong><?= __('enable_fleet_management', 'admin', 'Włącz zarządzanie flotą') ?></strong>
                                </label>
                            </div>
                            <div class="form-text"><?= __('fleet_management_description', 'admin', 'Umożliwia śledzenie lokalizacji pojazdów i kontrolowanie dostępności w różnych miastach') ?></div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label"><?= __('default_fleet_location', 'admin', 'Domyślna lokalizacja') ?></label>
                            <select class="form-select" name="fleet_default_location">
                                <option value=""><?= __('select_location', 'admin', 'Wybierz lokalizację') ?></option>
                                <?php foreach ($locations as $location): ?>
                                    <option value="<?= $location['id'] ?>" <?= ($shop_settings['fleet_default_location'] ?? '') == $location['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($location['name']) ?> (<?= htmlspecialchars($location['city']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="fleet_auto_update_location"
                                    name="fleet_auto_update_location" <?= ($shop_settings['fleet_auto_update_location'] ?? '1') == '1' ? 'checked' : '' ?>>
                                <label class="form-check-label" for="fleet_auto_update_location">
                                    <?= __('auto_update_vehicle_location', 'admin', 'Automatycznie aktualizuj lokalizację pojazdu') ?>
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="fleet_require_location_selection"
                                    name="fleet_require_location_selection" <?= ($shop_settings['fleet_require_location_selection'] ?? '1') == '1' ? 'checked' : '' ?>>
                                <label class="form-check-label" for="fleet_require_location_selection">
                                    <?= __('require_location_selection', 'admin', 'Wymagaj wyboru lokalizacji przy rezerwacji') ?>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Opłaty lokalizacyjne -->
        <div class="col-12" id="location-fees">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="bi bi-geo-alt-fill"></i> <?= __('location_fees', 'admin', 'Opłaty lokalizacyjne') ?></h6>
                    <a href="<?= $BASE ?>/location-fees.php" class="btn btn-outline-primary btn-sm ms-2 location-fees-manage-btn" style="border-width:1px;">
                        <i class="bi bi-gear"></i> <?= __('manage_location_fees', 'admin', 'Zarządzaj opłatami') ?>
                    </a>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle"></i>
                                <strong><?= __('location_fees_info', 'admin', 'Informacja o opłatach lokalizacyjnych') ?></strong><br>
                                <?= __('location_fees_description', 'admin', 'System automatycznie zarządza opłatami za różne trasy między lokalizacjami. Opłaty są symetryczne - trasa A→B ma taką samą cenę jak B→A.') ?>
                            </div>
                        </div>

                        <?php
                        // Pobierz podstawowe statystyki opłat lokalizacyjnych
                        try {
                            $locationFeesStats = $db->query("
                                SELECT 
                                    COUNT(*) as total_routes,
                                    MIN(fee_amount) as min_fee,
                                    MAX(fee_amount) as max_fee,
                                    AVG(fee_amount) as avg_fee
                                FROM location_fees 
                                WHERE is_active = 1
                            ")->fetch();

                            $totalLocations = $db->query("SELECT COUNT(*) FROM locations WHERE is_active = 1")->fetchColumn();
                        ?>
                            <div class="col-md-3">
                                <div class="text-center p-3 bg-light rounded">
                                    <h4 class="text-primary mb-1"><?= (int)$totalLocations ?></h4>
                                    <small class="text-muted"><?= __('active_locations', 'admin', 'Aktywne lokalizacje') ?></small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center p-3 bg-light rounded">
                                    <h4 class="text-success mb-1"><?= (int)$locationFeesStats['total_routes'] ?></h4>
                                    <small class="text-muted"><?= __('configured_routes', 'admin', 'Skonfigurowane trasy') ?></small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center p-3 bg-light rounded">
                                    <h4 class="text-info mb-1"><?= number_format($locationFeesStats['min_fee'] ?? 0, 0, ',', ' ') ?> PLN</h4>
                                    <small class="text-muted"><?= __('min_fee', 'admin', 'Minimalna opłata') ?></small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center p-3 bg-light rounded">
                                    <h4 class="text-warning mb-1"><?= number_format($locationFeesStats['max_fee'] ?? 0, 0, ',', ' ') ?> PLN</h4>
                                    <small class="text-muted"><?= __('max_fee', 'admin', 'Maksymalna opłata') ?></small>
                                </div>
                            </div>

                            <?php if ($locationFeesStats['total_routes'] == 0): ?>
                                <div class="col-12">
                                    <div class="alert alert-warning">
                                        <i class="bi bi-exclamation-triangle"></i>
                                        <?= __('no_location_fees_configured', 'admin', 'Nie skonfigurowano jeszcze opłat lokalizacyjnych. Użyj przycisku "Zarządzaj opłatami" aby dodać pierwsze trasy.') ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                        <?php
                        } catch (Exception $e) {
                            echo '<div class="col-12"><div class="alert alert-danger"><i class="bi bi-exclamation-triangle"></i> ' .
                                __('error_loading_location_fees', 'admin', 'Błąd ładowania opłat lokalizacyjnych') . ': ' . htmlspecialchars($e->getMessage()) . '</div></div>';
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- System kaucji zwrotnych -->
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0"><i class="bi bi-shield-check"></i> <?= __('deposit_system', 'admin', 'System kaucji zwrotnych') ?></h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="deposits_enabled"
                                    name="deposits_enabled" <?= ($shop_settings['deposits_enabled'] ?? '0') == '1' ? 'checked' : '' ?>>
                                <label class="form-check-label" for="deposits_enabled">
                                    <strong><?= __('enable_deposit_system', 'admin', 'Włącz system kaucji') ?></strong>
                                </label>
                            </div>
                            <div class="form-text"><?= __('deposit_system_description', 'admin', 'Automatyczne zarządzanie kaucjami zwrotnymi przy rezerwacjach') ?></div>
                        </div>

                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="global_deposit_override"
                                    name="global_deposit_override" <?= ($shop_settings['global_deposit_override'] ?? '0') == '1' ? 'checked' : '' ?>>
                                <label class="form-check-label" for="global_deposit_override">
                                    <strong><?= __('global_deposit_override', 'admin', 'Ustaw globalnie dla wszystkich pojazdów') ?></strong>
                                </label>
                            </div>
                            <div class="form-text"><?= __('global_deposit_description', 'admin', 'Jeśli zaznaczone, ustawienia kaucji będą stosowane do wszystkich pojazdów. Indywidualne ustawienia w kartach pojazdów będą miały wyższy priorytet.') ?></div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label"><?= __('default_deposit_type', 'admin', 'Typ kaucji') ?></label>
                            <select class="form-select" name="default_deposit_type" id="deposit_type_select">
                                <option value="fixed" <?= ($shop_settings['default_deposit_type'] ?? '') === 'fixed' ? 'selected' : '' ?>><?= __('fixed_amount', 'admin', 'Stała kwota (PLN)') ?></option>
                                <option value="percentage" <?= ($shop_settings['default_deposit_type'] ?? '') === 'percentage' ? 'selected' : '' ?>><?= __('percentage_of_rental', 'admin', 'Procent od wartości zamówienia (%)') ?></option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" id="deposit_value_label">
                                <?= ($shop_settings['default_deposit_type'] ?? '') === 'percentage' ? 'Procent kaucji (%)' : 'Kwota kaucji (PLN)' ?>
                            </label>
                            <input type="number" class="form-control" name="default_deposit_value" id="deposit_value_input"
                                value="<?= htmlspecialchars($shop_settings['default_deposit_value'] ?? ($shop_settings['default_deposit_type'] === 'percentage' ? '10' : '200')) ?>"
                                min="0" step="0.01" required>
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="deposits_include_in_payment"
                                    name="deposits_include_in_payment" <?= ($shop_settings['deposits_include_in_payment'] ?? '1') == '1' ? 'checked' : '' ?>>
                                <label class="form-check-label" for="deposits_include_in_payment">
                                    <?= __('include_deposit_in_payment', 'admin', 'Wliczaj kaucję w płatność online') ?>
                                </label>
                            </div>
                            <div class="form-text"><?= __('deposit_payment_description', 'admin', 'Jeśli wyłączone, kaucja będzie pokazywana tylko informacyjnie') ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ustawienia rezerwacji -->
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0"><i class="bi bi-calendar-check"></i> <?= __('reservation_settings', 'admin', 'Ustawienia rezerwacji') ?></h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label"><?= __('min_rental_hours', 'admin', 'Min. czas wynajmu (godziny)') ?></label>
                            <input type="number" name="min_rental_hours" class="form-control"
                                value="<?= htmlspecialchars($shop_settings['min_rental_hours'] ?? '24') ?>"
                                min="1" step="1">
                        </div>
                        <div class="col-6">
                            <label class="form-label"><?= __('max_rental_days', 'admin', 'Max. czas wynajmu (dni)') ?></label>
                            <input type="number" name="max_rental_days" class="form-control"
                                value="<?= htmlspecialchars($shop_settings['max_rental_days'] ?? '30') ?>"
                                min="1" step="1">
                        </div>
                        <div class="col-6">
                            <label class="form-label"><?= __('advance_booking_days', 'admin', 'Wyprzedzenie rezerwacji (dni)') ?></label>
                            <input type="number" name="advance_booking_days" class="form-control"
                                value="<?= htmlspecialchars($shop_settings['advance_booking_days'] ?? '365') ?>"
                                min="1" step="1">
                        </div>
                        <div class="col-6">
                            <label class="form-label"><?= __('cancellation_hours', 'admin', 'Anulowanie przed (godziny)') ?></label>
                            <input type="number" name="cancellation_hours" class="form-control"
                                value="<?= htmlspecialchars($shop_settings['cancellation_hours'] ?? '24') ?>"
                                min="1" step="1">
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="auto_confirmation"
                                    name="auto_confirmation" <?= ($shop_settings['auto_confirmation'] ?? '0') == '1' ? 'checked' : '' ?>>
                                <label class="form-check-label" for="auto_confirmation">
                                    <?= __('auto_confirm_bookings', 'admin', 'Automatyczne potwierdzanie rezerwacji') ?>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Powiadomienia -->
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0"><i class="bi bi-bell"></i> <?= __('notifications', 'admin', 'Powiadomienia') ?></h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label"><?= __('notification_email', 'admin', 'Email dla powiadomień') ?></label>
                        <input type="email" name="notification_email" class="form-control"
                            value="<?= htmlspecialchars($shop_settings['notification_email'] ?? '') ?>">
                    </div>

                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="notification_new_booking"
                            name="notification_new_booking" <?= ($shop_settings['notification_new_booking'] ?? '1') == '1' ? 'checked' : '' ?>>
                        <label class="form-check-label" for="notification_new_booking">
                            <?= __('notify_new_bookings', 'admin', 'Nowe rezerwacje') ?>
                        </label>
                    </div>

                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="notification_cancellation"
                            name="notification_cancellation" <?= ($shop_settings['notification_cancellation'] ?? '1') == '1' ? 'checked' : '' ?>>
                        <label class="form-check-label" for="notification_cancellation">
                            <?= __('notify_cancellations', 'admin', 'Anulowania') ?>
                        </label>
                    </div>

                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="notification_payment"
                            name="notification_payment" <?= ($shop_settings['notification_payment'] ?? '1') == '1' ? 'checked' : '' ?>>
                        <label class="form-check-label" for="notification_payment">
                            <?= __('notify_payments', 'admin', 'Płatności') ?>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Przycisk zapisu -->
        <div class="col-12">
            <div class="d-flex justify-content-end">
                <button type="submit" name="save_shop_settings" class="btn btn-primary">
                    <i class="bi bi-check-lg"></i> <?= __('save_settings', 'admin', 'Zapisz ustawienia') ?>
                </button>
            </div>
        </div>
    </div>
</form>

<style>
    .auto-fade {
        transition: opacity 0.5s ease-out;
    }
</style>

<script>
    // Auto-fade success alerts
    document.addEventListener('DOMContentLoaded', function() {
        const successAlert = document.getElementById('successAlert');
        if (successAlert) {
            setTimeout(function() {
                successAlert.style.opacity = '0';
                setTimeout(function() {
                    successAlert.style.display = 'none';
                }, 500);
            }, 3000);
        }

        // Jeśli to był POST request (zapisywanie ustawień), usuń hash z URL
        // żeby po zapisaniu nie przewijało do kotwicy
        const urlParams = new URLSearchParams(window.location.search);
        if (window.location.hash && document.referrer.includes('location-fees.php')) {
            // Usuń hash tylko jeśli przyszliśmy z location-fees
            // ale nie usuwaj go przy normalnym ładowaniu strony
        } else if (window.location.hash === '#location-fees' && successAlert) {
            // Jeśli pokazuje się alert sukcesu (po POST) i mamy hash location-fees, usuń go
            history.replaceState(null, null, window.location.pathname + window.location.search);
        }

        // Obsługa dynamicznej zmiany typu kaucji
        const depositTypeSelect = document.getElementById('deposit_type_select');
        const depositValueLabel = document.getElementById('deposit_value_label');
        const depositValueInput = document.getElementById('deposit_value_input');

        if (depositTypeSelect && depositValueLabel && depositValueInput) {
            depositTypeSelect.addEventListener('change', function() {
                const type = this.value;

                if (type === 'percentage') {
                    depositValueLabel.textContent = 'Procent kaucji (%)';
                    depositValueInput.setAttribute('max', '100');
                    depositValueInput.setAttribute('step', '1');
                    depositValueInput.value = '10';
                } else {
                    depositValueLabel.textContent = 'Kwota kaucji (PLN)';
                    depositValueInput.removeAttribute('max');
                    depositValueInput.setAttribute('step', '10');
                    depositValueInput.value = '200';
                }
            });
        }
    });
</script>