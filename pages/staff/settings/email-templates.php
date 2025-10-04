<?php
// /pages/staff/settings/email-templates.php

$db = db();
i18n::init();

// Pobierz aktualny język lub ustaw domyślny
$current_language = $_GET['lang'] ?? 'pl';
if (!in_array($current_language, ['pl', 'en'])) {
    $current_language = 'pl';
}

// Pobierz szablony email dla wybranego języka
$templates = [];
$stmt = $db->prepare("SELECT * FROM email_templates WHERE language = ? ORDER BY template_key");
$stmt->execute([$current_language]);
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $templates[$row['template_key']] = $row;
}

// Obsługa zapisywania szablonu
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_template'])) {
    $template_key = $_POST['template_key'] ?? '';
    $language = $_POST['language'] ?? $current_language;
    $template_name = $_POST['template_name'] ?? '';
    $subject = $_POST['subject'] ?? '';
    $content = $_POST['content'] ?? '';
    $variables = $_POST['variables'] ?? '';
    $enabled = isset($_POST['enabled']) ? 1 : 0;
    
    if (!empty($template_key) && !empty($template_name)) {
        try {
            $stmt = $db->prepare("
                INSERT INTO email_templates (template_key, language, template_name, subject, content, variables, enabled) 
                VALUES (?, ?, ?, ?, ?, ?, ?) 
                ON DUPLICATE KEY UPDATE 
                template_name = VALUES(template_name),
                subject = VALUES(subject),
                content = VALUES(content),
                variables = VALUES(variables),
                enabled = VALUES(enabled)
            ");
            
            $stmt->execute([$template_key, $language, $template_name, $subject, $content, $variables, $enabled]);
            $success_message = __('template_saved_success', 'admin', "Szablon został zapisany!");
            
            // Odśwież templates
            $templates = [];
            $stmt = $db->prepare("SELECT * FROM email_templates WHERE language = ? ORDER BY template_key");
            $stmt->execute([$current_language]);
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $templates[$row['template_key']] = $row;
            }
            
        } catch (PDOException $e) {
            $error_message = __('saving_error', 'admin', 'Błąd podczas zapisywania') . ": " . $e->getMessage();
        }
    } else {
        $error_message = __('template_key_name_required', 'admin', 'Klucz szablonu i nazwa są wymagane!');
    }
}

// Obsługa usuwania szablonu
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_template'])) {
    $template_key = $_POST['template_key'] ?? '';
    
    if (!empty($template_key)) {
        try {
            $stmt = $db->prepare("DELETE FROM email_templates WHERE template_key = ?");
            $stmt->execute([$template_key]);
            $success_message = __('template_deleted_success', 'admin', 'Szablon został usunięty!');
            
            // Odśwież templates
            $templates = [];
            $stmt = $db->query("SELECT * FROM email_templates ORDER BY template_key");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $templates[$row['template_key']] = $row;
            }
            
            // Przekieruj na pierwszy dostępny szablon
            if (count($templates) > 0) {
                $current_template = array_key_first($templates);
                // Przekieruj na nowy URL z poprawnym edit parametrem
                $redirect_url = settings_url($current_template);
                echo "<script>
                    setTimeout(function() {
                        window.location.href = '$redirect_url';
                    }, 2000);
                </script>";
            } else {
                $current_template = 'booking_confirmation';
            }
            
        } catch (PDOException $e) {
            $error_message = __('delete_error_prefix', 'admin', 'Błąd podczas usuwania') . ": " . $e->getMessage();
        }
    } else {
        $error_message = __('cannot_delete_template', 'admin', 'Nie można usunąć szablonu - brak klucza!');
    }
}

// Obsługa dodawania nowego szablonu
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_new_template'])) {
    $new_template_key = trim($_POST['new_template_key'] ?? '');
    $new_template_name = trim($_POST['new_template_name'] ?? '');
    
    if (!empty($new_template_key) && !empty($new_template_name)) {
        // Sprawdź czy klucz już istnieje
        if (isset($templates[$new_template_key])) {
            $error_message = __('template_exists_error', 'admin', 'Szablon o takim kluczu już istnieje!');
        } else {
            try {
                $stmt = $db->prepare("
                    INSERT INTO email_templates (template_key, template_name, subject, content, variables, enabled) 
                    VALUES (?, ?, ?, ?, ?, 1)
                ");
                
                $default_subject = "Nowy szablon - {$new_template_name}";
                $default_content = "<h2>" . __('new_email_template', 'admin', 'Nowy szablon email') . "</h2>\n<p>" . __('dear_customer', 'admin', 'Szanowny/a {customer_name},') . "</p>\n<p>" . __('content_to_fill', 'admin', 'Treść do uzupełnienia...') . "</p>\n<p>" . __('best_regards', 'admin', 'Pozdrawiamy,<br>{company_name}') . "</p>";
                $default_variables = "{customer_name}, {company_name}";
                
                $stmt->execute([$new_template_key, $new_template_name, $default_subject, $default_content, $default_variables]);
                $success_message = str_replace('{name}', $new_template_name, __('new_template_created', 'admin', 'Nowy szablon \'{name}\' został utworzony!'));
                
                // Odśwież templates
                $templates = [];
                $stmt = $db->query("SELECT * FROM email_templates ORDER BY template_key");
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $templates[$row['template_key']] = $row;
                }
                
                // Przełącz na nowy szablon
                $current_template = $new_template_key;
                
                // Przekieruj na nowy URL z poprawnym edit parametrem
                $redirect_url = settings_url($current_template);
                echo "<script>
                    setTimeout(function() {
                        window.location.href = '$redirect_url';
                    }, 2000);
                </script>";
                
            } catch (PDOException $e) {
                $error_message = __('template_creation_error', 'admin', 'Błąd podczas tworzenia szablonu') . ": " . $e->getMessage();
            }
        }
    } else {
        $error_message = __('new_template_key_required', 'admin', 'Klucz i nazwa nowego szablonu są wymagane!');
    }
}

// Test wysyłki szablonu
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_template'])) {
    $template_key = $_POST['template_key'] ?? '';
    $test_email = $_POST['test_email'] ?? '';
    
    if (!empty($template_key) && !empty($test_email) && isset($templates[$template_key])) {
        // Mock test - w rzeczywistości wysłałby email
        $test_message = str_replace(['{name}', '{email}'], [$templates[$template_key]['template_name'], $test_email], __('test_email_sent', 'admin', 'Test email dla szablonu \'{name}\' został wysłany na {email}'));
    }
}

// Aktualny szablon do edycji
if (!isset($current_template)) {
    $current_template = $_GET['edit'] ?? 'booking_confirmation';
}

// Funkcja do generowania linków w panelu ustawień
function settings_url($edit_template = null) {
    global $BASE;
    $page = $_GET['page'] ?? 'dashboard-staff';
    $section = $_GET['section'] ?? 'settings';
    $settings_section = $_GET['settings_section'] ?? 'email';
    $settings_subsection = $_GET['settings_subsection'] ?? 'templates';
    
    $url = $BASE . "/index.php?page={$page}&section={$section}&settings_section={$settings_section}&settings_subsection={$settings_subsection}";
    
    if ($edit_template) {
        $url .= "&edit={$edit_template}";
    }
    
    // Dodaj hash dla sekcji
    $url .= "#pane-settings";
    
    return $url;
}

// Domyślne szablony
$default_templates = [
    'booking_confirmation' => [
        'template_name' => 'Potwierdzenie rezerwacji',
        'subject' => 'Potwierdzenie rezerwacji #{booking_id}',
        'content' => '<h2>Dziękujemy za rezerwację!</h2>
<p>Szanowny/a {customer_name},</p>
<p>Potwierdzamy Państwa rezerwację na następujące parametry:</p>
<ul>
<li><strong>Numer rezerwacji:</strong> #{booking_id}</li>
<li><strong>Pojazd:</strong> {vehicle_name}</li>
<li><strong>Data od:</strong> {date_from}</li>
<li><strong>Data do:</strong> {date_to}</li>
<li><strong>Całkowity koszt:</strong> {total_price}</li>
</ul>
<p>W razie pytań prosimy o kontakt.</p>
<p>Pozdrawiamy,<br>{company_name}</p>',
        'variables' => '{customer_name}, {booking_id}, {vehicle_name}, {date_from}, {date_to}, {total_price}, {company_name}'
    ],
    'booking_cancellation' => [
        'template_name' => 'Anulowanie rezerwacji',
        'subject' => 'Anulowanie rezerwacji #{booking_id}',
        'content' => '<h2>Anulowanie rezerwacji</h2>
<p>Szanowny/a {customer_name},</p>
<p>Informujemy, że rezerwacja #{booking_id} została anulowana.</p>
<p><strong>Szczegóły anulowanej rezerwacji:</strong></p>
<ul>
<li><strong>Pojazd:</strong> {vehicle_name}</li>
<li><strong>Data od:</strong> {date_from}</li>
<li><strong>Data do:</strong> {date_to}</li>
<li><strong>Powód anulowania:</strong> {cancellation_reason}</li>
</ul>
<p>Kwota {refund_amount} zostanie zwrócona na Państwa konto w ciągu {refund_days} dni roboczych.</p>
<p>Pozdrawiamy,<br>{company_name}</p>',
        'variables' => '{customer_name}, {booking_id}, {vehicle_name}, {date_from}, {date_to}, {cancellation_reason}, {refund_amount}, {refund_days}, {company_name}'
    ],
    'payment_confirmation' => [
        'template_name' => 'Potwierdzenie płatności',
        'subject' => 'Płatność potwierdzona - rezerwacja #{booking_id}',
        'content' => '<h2>Płatność potwierdzona</h2>
<p>Szanowny/a {customer_name},</p>
<p>Potwierdzamy otrzymanie płatności za rezerwację #{booking_id}.</p>
<p><strong>Szczegóły płatności:</strong></p>
<ul>
<li><strong>Kwota:</strong> {payment_amount}</li>
<li><strong>Metoda płatności:</strong> {payment_method}</li>
<li><strong>Data płatności:</strong> {payment_date}</li>
<li><strong>Status:</strong> Opłacone</li>
</ul>
<p>Rezerwacja jest potwierdzona i oczekuje na realizację.</p>
<p>Pozdrawiamy,<br>{company_name}</p>',
        'variables' => '{customer_name}, {booking_id}, {payment_amount}, {payment_method}, {payment_date}, {company_name}'
    ],
    'reminder' => [
        'template_name' => 'Przypomnienie o rezerwacji',
        'subject' => 'Przypomnienie - rezerwacja #{booking_id} jutro',
        'content' => '<h2>Przypomnienie o rezerwacji</h2>
<p>Szanowny/a {customer_name},</p>
<p>Przypominamy o Państwa jutrzejszej rezerwacji:</p>
<ul>
<li><strong>Numer rezerwacji:</strong> #{booking_id}</li>
<li><strong>Pojazd:</strong> {vehicle_name}</li>
<li><strong>Odbiór:</strong> {pickup_date} o {pickup_time}</li>
<li><strong>Miejsce odbioru:</strong> {pickup_location}</li>
</ul>
<p>Prosimy o punktualne przybycie z dokumentem tożsamości i prawem jazdy.</p>
<p>Pozdrawiamy,<br>{company_name}</p>',
        'variables' => '{customer_name}, {booking_id}, {vehicle_name}, {pickup_date}, {pickup_time}, {pickup_location}, {company_name}'
    ],
    'google_review_request' => [
        'template_name' => 'Prośba o opinię Google',
        'subject' => 'Podziel się opinią o naszych usługach 🌟',
        'content' => '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
    <h2 style="color: #4285f4;">Dziękujemy za skorzystanie z naszych usług! 🚗</h2>
    
    <p>Szanowny/a {customer_name},</p>
    
    <p>Dziękujemy za wynajem pojazdu <strong>{vehicle_name}</strong> w terminie {date_from} - {date_to}.</p>
    
    <p>Jeśli jesteś zadowolony/a z naszych usług, bardzo prosimy o pozostawienie opinii na Google. 
    Twoja opinia pomoże innym klientom w dokonaniu wyboru i motywuje nas do dalszego doskonalenia naszej oferty.</p>
    
    <div style="text-align: center; margin: 30px 0;">
        <a href="{google_review_link}" 
           style="background-color: #4285f4; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; font-size: 16px; font-weight: bold;">
            ⭐ Oceń nas na Google ⭐
        </a>
    </div>
    
    <p style="font-size: 14px; color: #666;">
        <strong>Dlaczego Twoja opinia jest ważna?</strong><br>
        • Pomaga innym klientom w wyborze<br>
        • Pozwala nam się rozwijać i poprawiać jakość usług<br>
        • Zajmuje tylko 2 minuty Twojego czasu<br>
    </p>
    
    <hr style="border: none; border-top: 1px solid #eee; margin: 20px 0;">
    
    <p style="font-size: 12px; color: #999;">
        Jeśli masz jakiekolwiek uwagi lub sugestie, skontaktuj się z nami bezpośrednio pod adresem {contact_email} lub telefonicznie {contact_phone}.
    </p>
    
    <p>Z poważaniem,<br>
    <strong>{company_name}</strong><br>
    <em>Zespół obsługi klienta</em></p>
</div>',
        'variables' => '{customer_name}, {vehicle_name}, {date_from}, {date_to}, {google_review_link}, {contact_email}, {contact_phone}, {company_name}'
    ]
];

// Wypełnij brakujące szablony
foreach ($default_templates as $key => $default) {
    if (!isset($templates[$key])) {
        $templates[$key] = array_merge($default, [
            'template_key' => $key,
            'enabled' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }
}

// Sprawdź czy aktualnie wybrany szablon istnieje
if (!isset($templates[$current_template]) && count($templates) > 0) {
    $current_template = array_key_first($templates);
}
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-1"><?= __('email_templates', 'admin', 'Szablony emaili') ?></h5>
        <p class="text-muted mb-0"><?= __('manage_email_templates', 'admin', 'Zarządzanie szablonami wiadomości automatycznych') ?></p>
    </div>
    <div class="d-flex gap-2 align-items-center">
        <!-- Language Selector -->
        <div class="btn-group" role="group">
            <input type="radio" class="btn-check" name="template_language" id="lang_pl" value="pl" <?= $current_language === 'pl' ? 'checked' : '' ?> onchange="switchLanguage('pl')">
            <label class="btn btn-outline-primary btn-sm" for="lang_pl">
                <i class="bi bi-flag"></i> <?= __('polish_template', 'admin', 'PL') ?>
            </label>
            
            <input type="radio" class="btn-check" name="template_language" id="lang_en" value="en" <?= $current_language === 'en' ? 'checked' : '' ?> onchange="switchLanguage('en')">
            <label class="btn btn-outline-primary btn-sm" for="lang_en">
                <i class="bi bi-flag"></i> <?= __('english_template', 'admin', 'EN') ?>
            </label>
        </div>
        
        <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#addTemplateModal">
            <i class="bi bi-plus-circle"></i> <?= __('add_new_template', 'admin', 'Dodaj szablon') ?>
        </button>
        <button class="btn btn-outline-info btn-sm" data-bs-toggle="modal" data-bs-target="#variablesModal">
            <i class="bi bi-info-circle"></i> <?= __('template_variables', 'admin', 'Zmienne') ?>
        </button>
        <button class="btn btn-outline-secondary btn-sm" onclick="location.reload()">
            <i class="bi bi-arrow-clockwise"></i> <?= __('refresh', 'admin', 'Odśwież') ?>
        </button>
    </div>
</div>

<?php if (isset($success_message)): ?>
    <div class="alert alert-success alert-dismissible auto-fade" id="successAlert">
        <i class="bi bi-check-circle"></i>
        <?= $success_message ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (isset($error_message)): ?>
    <div class="alert alert-danger">
        <i class="bi bi-exclamation-triangle"></i>
        <?= $error_message ?>
    </div>
<?php endif; ?>

<?php if (isset($test_message)): ?>
    <div class="alert alert-info">
        <i class="bi bi-envelope"></i>
        <?= $test_message ?>
    </div>
<?php endif; ?>

<div class="row g-4">
    <!-- Lista szablonów -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><?= __('available_templates', 'admin', 'Dostępne szablony') ?></h6>
            </div>
            <div class="list-group list-group-flush">
                <?php foreach ($templates as $key => $template): ?>
                    <a href="<?= settings_url($key) ?>" 
                       class="list-group-item list-group-item-action d-flex justify-content-between align-items-center <?= $current_template === $key ? 'active' : '' ?>">
                        <div>
                            <div class="fw-semibold"><?= htmlspecialchars($template['template_name']) ?></div>
                            <small class="text-muted"><?= $key ?></small>
                        </div>
                        <div>
                            <?php if ($template['enabled']): ?>
                                <span class="badge bg-success"><?= __('active', 'admin', 'Aktywny') ?></span>
                            <?php else: ?>
                                <span class="badge bg-secondary"><?= __('inactive', 'admin', 'Nieaktywny') ?></span>
                            <?php endif; ?>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    
    <!-- Edytor szablonu -->
    <div class="col-lg-8">
        <?php if (isset($templates[$current_template])): ?>
            <?php $template = $templates[$current_template]; ?>
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><?= __('edit_template', 'admin', 'Edytuj szablon') ?>: <?= htmlspecialchars($template['template_name']) ?></h6>
                    <div class="d-flex align-items-center gap-3">
                        <button class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteTemplateModal" 
                                onclick="setDeleteTemplate('<?= $current_template ?>', '<?= htmlspecialchars($template['template_name']) ?>')">
                            <i class="bi bi-trash"></i> <?= __('delete', 'admin', 'Usuń') ?>
                        </button>
                        <div class="form-check form-switch mb-0">
                            <input type="checkbox" class="form-check-input" id="template-enabled" 
                                   <?= $template['enabled'] ? 'checked' : '' ?>
                                   onchange="toggleTemplate('<?= $current_template ?>', this.checked)">
                            <label class="form-check-label" for="template-enabled"><?= __('active', 'admin', 'Aktywny') ?></label>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="template_key" value="<?= $current_template ?>">
                        
                        <div class="mb-3">
                            <label class="form-label"><?= __('template_name', 'admin', 'Nazwa szablonu') ?></label>
                            <input type="text" name="template_name" class="form-control" 
                                   value="<?= htmlspecialchars($template['template_name']) ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label"><?= __('template_subject', 'admin', 'Temat wiadomości') ?></label>
                            <input type="text" name="subject" class="form-control" 
                                   value="<?= htmlspecialchars($template['subject']) ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label"><?= __('template_content', 'admin', 'Treść wiadomości') ?></label>
                            <textarea name="content" class="form-control" rows="12" required><?= htmlspecialchars($template['content']) ?></textarea>
                            <div class="form-text"><?= __('html_formatting_help', 'admin', 'Użyj HTML dla formatowania. Dostępne zmienne') ?>: <?= htmlspecialchars($template['variables']) ?></div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label"><?= __('template_variables', 'admin', 'Dostępne zmienne') ?></label>
                            <input type="text" name="variables" class="form-control" 
                                   value="<?= htmlspecialchars($template['variables']) ?>">
                            <div class="form-text"><?= __('variables_help', 'admin', 'Lista zmiennych oddzielonych przecinkami') ?></div>
                        </div>
                        
                        <div class="form-check mb-3">
                            <input type="checkbox" name="enabled" class="form-check-input" 
                                   id="enabled" <?= $template['enabled'] ? 'checked' : '' ?>>
                            <label class="form-check-label" for="enabled">
                                <?= __('template_enabled', 'admin', 'Szablon aktywny') ?>
                            </label>
                        </div>
                        
                        <div class="d-flex gap-2">
                            <button type="submit" name="save_template" class="btn btn-primary">
                                <i class="bi bi-check-lg"></i> <?= __('save_template', 'admin', 'Zapisz szablon') ?>
                            </button>
                            <button type="button" class="btn btn-outline-info" data-bs-toggle="modal" data-bs-target="#testModal">
                                <i class="bi bi-envelope"></i> <?= __('test_sending', 'admin', 'Test wysyłki') ?>
                            </button>
                            <button type="button" class="btn btn-outline-secondary" onclick="previewTemplate()">
                                <i class="bi bi-eye"></i> <?= __('preview', 'admin', 'Podgląd') ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal test wysyłki -->
<div class="modal fade" id="testModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?= __('test_template_send', 'admin') ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="template_key" value="<?= $current_template ?>">
                    <div class="mb-3">
                        <label class="form-label"><?= __('test_email_label', 'admin') ?></label>
                        <input type="email" name="test_email" class="form-control" 
                               placeholder="<?= __('test_email_placeholder', 'admin') ?>" required>
                    </div>
                    <div class="alert alert-info">
                        <small><?= __('test_email_notice', 'admin') ?></small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= __('cancel', 'admin') ?></button>
                    <button type="submit" name="test_template" class="btn btn-primary">
                        <i class="bi bi-send"></i> <?= __('send_test', 'admin') ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal zmiennych -->
<div class="modal fade" id="variablesModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?= __('available_variables', 'admin', 'Dostępne zmienne') ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <h6><?= __('customer_section', 'admin', 'Klient') ?>:</h6>
                        <ul class="list-unstyled">
                            <li><code>{customer_name}</code> - <?= __('customer_name_var', 'admin', 'Imię i nazwisko') ?></li>
                            <li><code>{customer_email}</code> - <?= __('customer_email_var', 'admin', 'Email klienta') ?></li>
                            <li><code>{customer_phone}</code> - <?= __('customer_phone_var', 'admin', 'Telefon') ?></li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h6><?= __('booking_section', 'admin', 'Rezerwacja') ?>:</h6>
                        <ul class="list-unstyled">
                            <li><code>{booking_id}</code> - <?= __('booking_id_var', 'admin', 'Numer rezerwacji') ?></li>
                            <li><code>{date_from}</code> - <?= __('date_from_var', 'admin', 'Data rozpoczęcia') ?></li>
                            <li><code>{date_to}</code> - <?= __('date_to_var', 'admin', 'Data zakończenia') ?></li>
                            <li><code>{total_price}</code> - <?= __('total_price_var', 'admin', 'Całkowita cena') ?></li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h6><?= __('vehicle_section', 'admin', 'Pojazd') ?>:</h6>
                        <ul class="list-unstyled">
                            <li><code>{vehicle_name}</code> - <?= __('vehicle_name_var', 'admin', 'Nazwa pojazdu') ?></li>
                            <li><code>{vehicle_brand}</code> - <?= __('vehicle_brand_var', 'admin', 'Marka') ?></li>
                            <li><code>{vehicle_model}</code> - <?= __('vehicle_model_var', 'admin', 'Model') ?></li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h6><?= __('company_section', 'admin', 'Firma') ?>:</h6>
                        <ul class="list-unstyled">
                            <li><code>{company_name}</code> - <?= __('company_name_var', 'admin', 'Nazwa firmy') ?></li>
                            <li><code>{company_email}</code> - <?= __('company_email_var', 'admin', 'Email firmy') ?></li>
                            <li><code>{company_phone}</code> - <?= __('company_phone_var', 'admin', 'Telefon firmy') ?></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal dodawania nowego szablonu -->
<div class="modal fade" id="addTemplateModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?= __('add_new_template', 'admin', 'Dodaj nowy szablon') ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label"><?= __('template_key_label', 'admin', 'Klucz szablonu') ?> <span class="text-danger">*</span></label>
                        <input type="text" name="new_template_key" class="form-control" required
                               placeholder="<?= __('template_key_placeholder', 'admin', 'np. welcome_email') ?>" pattern="[a-z0-9_]+" 
                               title="<?= __('only_lowercase_numbers', 'admin', 'Tylko małe litery, cyfry i podkreślenia') ?>">
                        <div class="form-text"><?= __('lowercase_only_help', 'admin', 'Używaj tylko małych liter, cyfr i podkreśleń (np. welcome_email)') ?></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?= __('template_name_label', 'admin', 'Nazwa szablonu') ?> <span class="text-danger">*</span></label>
                        <input type="text" name="new_template_name" class="form-control" required
                               placeholder="<?= __('template_name_placeholder', 'admin', 'np. Email powitalny') ?>">
                        <div class="form-text"><?= __('friendly_name_help', 'admin', 'Przyjazna nazwa wyświetlana w interfejsie') ?></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anuluj</button>
                    <button type="submit" name="add_new_template" class="btn btn-success">
                        <i class="bi bi-plus-circle"></i> <?= __('create_template', 'admin', 'Utwórz szablon') ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal usuwania szablonu -->
<div class="modal fade" id="deleteTemplateModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?= __('delete_template', 'admin') ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle"></i>
                    <strong><?= __('warning', 'admin') ?></strong> <?= __('operation_irreversible', 'admin') ?>
                </div>
                <p><?= __('confirm_delete_template_msg', 'admin') ?> <strong id="deleteTemplateName"></strong>?</p>
                <p class="text-muted small"><?= __('template_data_deleted', 'admin') ?></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= __('cancel', 'admin') ?></button>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="template_key" id="deleteTemplateKey">
                    <button type="submit" name="delete_template" class="btn btn-danger">
                        <i class="bi bi-trash"></i> <?= __('delete_template', 'admin') ?>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Auto-hide success alerts after 3 seconds with fade effect
document.addEventListener('DOMContentLoaded', function() {
    const successAlert = document.getElementById('successAlert');
    if (successAlert) {
        setTimeout(function() {
            successAlert.style.opacity = '0';
            setTimeout(function() {
                successAlert.style.display = 'none';
            }, 500); // Wait for fade transition to complete
        }, 3000); // Start fade after 3 seconds
    }
});

function toggleTemplate(templateKey, enabled) {
    const formData = new FormData();
    formData.append('template_key', templateKey);
    formData.append('enabled', enabled ? '1' : '0');
    formData.append('save_template', '1');
    
    // Get current form values
    const form = document.querySelector('form');
    if (form) {
        const formDataFromForm = new FormData(form);
        for (let [key, value] of formDataFromForm.entries()) {
            if (key !== 'enabled') {
                formData.append(key, value);
            }
        }
    }
    
    // Use current page URL to maintain navigation context
    const currentUrl = new URL(window.location.href);
    
    fetch(currentUrl.toString(), {
        method: 'POST',
        body: formData
    }).then(response => {
        if (response.ok) {
            location.reload();
        } else {
            console.error('Error toggling template status');
        }
    }).catch(error => {
        console.error('Error:', error);
    });
}

function setDeleteTemplate(templateKey, templateName) {
    document.getElementById('deleteTemplateKey').value = templateKey;
    document.getElementById('deleteTemplateName').textContent = templateName;
}

function previewTemplate() {
    const content = document.querySelector('textarea[name="content"]').value;
    const subject = document.querySelector('input[name="subject"]').value;
    
    const previewWindow = window.open('', '_blank', 'width=800,height=600');
    previewWindow.document.write(`
        <html>
        <head>
            <title><?= __('preview_subject', 'admin') ?> ${subject}</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                .subject { background: #f8f9fa; padding: 10px; border-left: 4px solid #007bff; margin-bottom: 20px; }
            </style>
        </head>
        <body>
            <div class="subject"><strong><?= __('subject_label', 'admin') ?></strong> ${subject}</div>
            <div class="content">${content}</div>
        </body>
        </html>
    `);
}

// Language switching function
function switchLanguage(lang) {
    const currentUrl = new URL(window.location);
    currentUrl.searchParams.set('lang', lang);
    currentUrl.searchParams.delete('edit'); // Reset template selection when switching language
    window.location.href = currentUrl.toString();
}
</script>

<style>
.list-group-item.active {
    background-color: #0d6efd;
    border-color: #0d6efd;
}

.list-group-item:hover:not(.active) {
    background-color: #f8f9fa;
}

code {
    background-color: #f8f9fa;
    padding: 2px 4px;
    border-radius: 3px;
    font-size: 0.875em;
}

.auto-fade {
    transition: opacity 0.5s ease-out;
}
</style>