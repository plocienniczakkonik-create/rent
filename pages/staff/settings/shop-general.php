<?php
// /pages/staff/settings/shop-general.php

$db = db();

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
            'default_language' => $_POST['default_language'] ?? 'pl',
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
            
            // Godziny pracy
            'business_hours_enabled' => isset($_POST['business_hours_enabled']) ? '1' : '0',
            'business_hours_monday' => $_POST['business_hours_monday'] ?? '',
            'business_hours_tuesday' => $_POST['business_hours_tuesday'] ?? '',
            'business_hours_wednesday' => $_POST['business_hours_wednesday'] ?? '',
            'business_hours_thursday' => $_POST['business_hours_thursday'] ?? '',
            'business_hours_friday' => $_POST['business_hours_friday'] ?? '',
            'business_hours_saturday' => $_POST['business_hours_saturday'] ?? '',
            'business_hours_sunday' => $_POST['business_hours_sunday'] ?? '',
            
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
            
            // SEO i marketing
            'site_title' => $_POST['site_title'] ?? '',
            'meta_description' => $_POST['meta_description'] ?? '',
            'meta_keywords' => $_POST['meta_keywords'] ?? '',
            'google_analytics_id' => $_POST['google_analytics_id'] ?? '',
            'facebook_pixel_id' => $_POST['facebook_pixel_id'] ?? ''
        ];
        
        foreach ($settings_to_save as $key => $value) {
            $stmt->execute([$key, $value]);
        }
        
        $db->commit();
        $success_message = "Ustawienia sklepu zostały zapisane!";
        
        // Odśwież ustawienia
        $shop_settings = [];
        $stmt = $db->query("SELECT setting_key, setting_value FROM shop_settings");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $shop_settings[$row['setting_key']] = $row['setting_value'];
        }
        
    } catch (PDOException $e) {
        $db->rollback();
        $error_message = "Błąd podczas zapisywania: " . $e->getMessage();
    }
}

// Wartości domyślne
$defaults = [
    'company_country' => 'Polska',
    'default_currency' => 'PLN',
    'timezone' => 'Europe/Warsaw',
    'default_language' => 'pl',
    'date_format' => 'd.m.Y',
    'time_format' => 'H:i',
    'default_tax_rate' => '23',
    'currency_symbol_position' => 'after',
    'decimal_places' => '2',
    'thousand_separator' => ' ',
    'decimal_separator' => ',',
    'min_rental_hours' => '24',
    'max_rental_days' => '30',
    'advance_booking_days' => '365',
    'cancellation_hours' => '24'
];

// Połącz z wartościami domyślnymi
foreach ($defaults as $key => $default_value) {
    if (!isset($shop_settings[$key])) {
        $shop_settings[$key] = $default_value;
    }
}

// Dostępne kraje
$countries = [
    'Polska', 'Niemcy', 'Czechy', 'Słowacja', 'Litwa', 'Łotwa', 'Estonia', 
    'Ukraina', 'Białoruś', 'Rosja', 'Francja', 'Hiszpania', 'Włochy', 'Wielka Brytania'
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

// Języki
$languages = [
    'pl' => 'Polski',
    'en' => 'English',
    'de' => 'Deutsch',
    'cs' => 'Čeština',
    'uk' => 'Українська'
];
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-1">Ustawienia sklepu</h5>
        <p class="text-muted mb-0">Konfiguracja biznesowa i regionalna</p>
    </div>
    <button class="btn btn-outline-secondary btn-sm" onclick="location.reload()">
        <i class="bi bi-arrow-clockwise"></i> Odśwież
    </button>
</div>

<?php if (isset($success_message)): ?>
    <div class="alert alert-success">
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
                    <h6 class="mb-0"><i class="bi bi-building"></i> Informacje o firmie</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nazwa firmy</label>
                            <input type="text" name="company_name" class="form-control" 
                                   value="<?= htmlspecialchars($shop_settings['company_name'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email firmy</label>
                            <input type="email" name="company_email" class="form-control" 
                                   value="<?= htmlspecialchars($shop_settings['company_email'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Telefon</label>
                            <input type="text" name="company_phone" class="form-control" 
                                   value="<?= htmlspecialchars($shop_settings['company_phone'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Strona internetowa</label>
                            <input type="url" name="company_website" class="form-control" 
                                   value="<?= htmlspecialchars($shop_settings['company_website'] ?? '') ?>">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Adres</label>
                            <input type="text" name="company_address" class="form-control" 
                                   value="<?= htmlspecialchars($shop_settings['company_address'] ?? '') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Miasto</label>
                            <input type="text" name="company_city" class="form-control" 
                                   value="<?= htmlspecialchars($shop_settings['company_city'] ?? '') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Kod pocztowy</label>
                            <input type="text" name="company_postal_code" class="form-control" 
                                   value="<?= htmlspecialchars($shop_settings['company_postal_code'] ?? '') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Kraj</label>
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
                            <label class="form-label">NIP</label>
                            <input type="text" name="company_nip" class="form-control" 
                                   value="<?= htmlspecialchars($shop_settings['company_nip'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">REGON</label>
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
                    <h6 class="mb-0"><i class="bi bi-globe"></i> Ustawienia regionalne</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Główna waluta</label>
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
                        <label class="form-label">Strefa czasowa</label>
                        <select name="timezone" class="form-select">
                            <?php foreach ($timezones as $tz => $name): ?>
                                <option value="<?= $tz ?>" 
                                        <?= ($shop_settings['timezone'] ?? '') === $tz ? 'selected' : '' ?>>
                                    <?= $name ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Język domyślny</label>
                        <select name="default_language" class="form-select">
                            <?php foreach ($languages as $code => $name): ?>
                                <option value="<?= $code ?>" 
                                        <?= ($shop_settings['default_language'] ?? '') === $code ? 'selected' : '' ?>>
                                    <?= $name ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label">Format daty</label>
                            <select name="date_format" class="form-select">
                                <option value="d.m.Y" <?= ($shop_settings['date_format'] ?? '') === 'd.m.Y' ? 'selected' : '' ?>>dd.mm.yyyy</option>
                                <option value="Y-m-d" <?= ($shop_settings['date_format'] ?? '') === 'Y-m-d' ? 'selected' : '' ?>>yyyy-mm-dd</option>
                                <option value="m/d/Y" <?= ($shop_settings['date_format'] ?? '') === 'm/d/Y' ? 'selected' : '' ?>>mm/dd/yyyy</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Format czasu</label>
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
                    <h6 class="mb-0"><i class="bi bi-calculator"></i> Podatki i ceny</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Domyślna stawka VAT (%)</label>
                        <input type="number" name="default_tax_rate" class="form-control" 
                               value="<?= htmlspecialchars($shop_settings['default_tax_rate'] ?? '') ?>"
                               min="0" max="100" step="0.01">
                    </div>
                    
                    <div class="form-check form-switch mb-3">
                        <input type="checkbox" name="tax_included_in_prices" class="form-check-input" 
                               id="tax_included" <?= ($shop_settings['tax_included_in_prices'] ?? '') === '1' ? 'checked' : '' ?>>
                        <label class="form-check-label" for="tax_included">
                            Ceny zawierają VAT
                        </label>
                    </div>
                    
                    <div class="form-check form-switch mb-3">
                        <input type="checkbox" name="show_prices_with_tax" class="form-check-input" 
                               id="show_with_tax" <?= ($shop_settings['show_prices_with_tax'] ?? '') === '1' ? 'checked' : '' ?>>
                        <label class="form-check-label" for="show_with_tax">
                            Wyświetlaj ceny z VAT
                        </label>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Pozycja symbolu waluty</label>
                        <select name="currency_symbol_position" class="form-select">
                            <option value="before" <?= ($shop_settings['currency_symbol_position'] ?? '') === 'before' ? 'selected' : '' ?>>Przed ceną (€100)</option>
                            <option value="after" <?= ($shop_settings['currency_symbol_position'] ?? '') === 'after' ? 'selected' : '' ?>>Po cenie (100 zł)</option>
                        </select>
                    </div>
                    
                    <div class="row g-2">
                        <div class="col-4">
                            <label class="form-label">Miejsca dziesiętne</label>
                            <select name="decimal_places" class="form-select">
                                <option value="0" <?= ($shop_settings['decimal_places'] ?? '') === '0' ? 'selected' : '' ?>>0</option>
                                <option value="2" <?= ($shop_settings['decimal_places'] ?? '') === '2' ? 'selected' : '' ?>>2</option>
                            </select>
                        </div>
                        <div class="col-4">
                            <label class="form-label">Sep. tysięcy</label>
                            <select name="thousand_separator" class="form-select">
                                <option value=" " <?= ($shop_settings['thousand_separator'] ?? '') === ' ' ? 'selected' : '' ?>>Spacja</option>
                                <option value="," <?= ($shop_settings['thousand_separator'] ?? '') === ',' ? 'selected' : '' ?>>Przecinek</option>
                                <option value="." <?= ($shop_settings['thousand_separator'] ?? '') === '.' ? 'selected' : '' ?>>Kropka</option>
                            </select>
                        </div>
                        <div class="col-4">
                            <label class="form-label">Sep. dziesiętny</label>
                            <select name="decimal_separator" class="form-select">
                                <option value="," <?= ($shop_settings['decimal_separator'] ?? '') === ',' ? 'selected' : '' ?>>Przecinek</option>
                                <option value="." <?= ($shop_settings['decimal_separator'] ?? '') === '.' ? 'selected' : '' ?>>Kropka</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Ustawienia rezerwacji -->
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0"><i class="bi bi-calendar-check"></i> Zasady rezerwacji</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Min. czas wynajmu (godziny)</label>
                        <input type="number" name="min_rental_hours" class="form-control" 
                               value="<?= htmlspecialchars($shop_settings['min_rental_hours'] ?? '') ?>"
                               min="1" max="168">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Max. czas wynajmu (dni)</label>
                        <input type="number" name="max_rental_days" class="form-control" 
                               value="<?= htmlspecialchars($shop_settings['max_rental_days'] ?? '') ?>"
                               min="1" max="365">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Wyprzedzenie rezerwacji (dni)</label>
                        <input type="number" name="advance_booking_days" class="form-control" 
                               value="<?= htmlspecialchars($shop_settings['advance_booking_days'] ?? '') ?>"
                               min="1" max="730">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Anulowanie przed (godziny)</label>
                        <input type="number" name="cancellation_hours" class="form-control" 
                               value="<?= htmlspecialchars($shop_settings['cancellation_hours'] ?? '') ?>"
                               min="1" max="168">
                    </div>
                    
                    <div class="form-check form-switch">
                        <input type="checkbox" name="auto_confirmation" class="form-check-input" 
                               id="auto_confirmation" <?= ($shop_settings['auto_confirmation'] ?? '') === '1' ? 'checked' : '' ?>>
                        <label class="form-check-label" for="auto_confirmation">
                            Automatyczne potwierdzanie
                        </label>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Powiadomienia -->
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0"><i class="bi bi-bell"></i> Powiadomienia</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Email dla powiadomień</label>
                        <input type="email" name="notification_email" class="form-control" 
                               value="<?= htmlspecialchars($shop_settings['notification_email'] ?? '') ?>">
                    </div>
                    
                    <div class="form-check form-switch mb-2">
                        <input type="checkbox" name="notification_new_booking" class="form-check-input" 
                               id="notif_booking" <?= ($shop_settings['notification_new_booking'] ?? '') === '1' ? 'checked' : '' ?>>
                        <label class="form-check-label" for="notif_booking">
                            Nowe rezerwacje
                        </label>
                    </div>
                    
                    <div class="form-check form-switch mb-2">
                        <input type="checkbox" name="notification_cancellation" class="form-check-input" 
                               id="notif_cancel" <?= ($shop_settings['notification_cancellation'] ?? '') === '1' ? 'checked' : '' ?>>
                        <label class="form-check-label" for="notif_cancel">
                            Anulowania
                        </label>
                    </div>
                    
                    <div class="form-check form-switch mb-2">
                        <input type="checkbox" name="notification_payment" class="form-check-input" 
                               id="notif_payment" <?= ($shop_settings['notification_payment'] ?? '') === '1' ? 'checked' : '' ?>>
                        <label class="form-check-label" for="notif_payment">
                            Płatności
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Przyciski akcji -->
    <div class="mt-4 d-flex gap-2">
        <button type="submit" name="save_shop_settings" class="btn btn-primary">
            <i class="bi bi-check-lg"></i> Zapisz wszystkie ustawienia
        </button>
        <button type="button" class="btn btn-outline-secondary" onclick="location.reload()">
            <i class="bi bi-arrow-clockwise"></i> Anuluj zmiany
        </button>
        <button type="button" class="btn btn-outline-info" data-bs-toggle="modal" data-bs-target="#previewModal">
            <i class="bi bi-eye"></i> Podgląd ustawień
        </button>
    </div>
</form>

<!-- Modal podglądu -->
<div class="modal fade" id="previewModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Podgląd ustawień sklepu</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <strong>Firma:</strong> <?= htmlspecialchars($shop_settings['company_name'] ?? 'Nie ustawiono') ?>
                    </div>
                    <div class="col-md-6">
                        <strong>Waluta:</strong> <?= htmlspecialchars($shop_settings['default_currency'] ?? 'PLN') ?>
                    </div>
                    <div class="col-md-6">
                        <strong>Strefa czasowa:</strong> <?= htmlspecialchars($shop_settings['timezone'] ?? 'Europe/Warsaw') ?>
                    </div>
                    <div class="col-md-6">
                        <strong>Język:</strong> <?= htmlspecialchars($languages[$shop_settings['default_language'] ?? 'pl'] ?? 'Polski') ?>
                    </div>
                    <div class="col-md-6">
                        <strong>VAT:</strong> <?= htmlspecialchars($shop_settings['default_tax_rate'] ?? '23') ?>%
                    </div>
                    <div class="col-md-6">
                        <strong>Min. wynajem:</strong> <?= htmlspecialchars($shop_settings['min_rental_hours'] ?? '24') ?> godz.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>