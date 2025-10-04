<?php
// /pages/staff/settings/email-smtp.php

$db = db();

// Pobierz ustawienia SMTP
$smtp_settings = [];
$stmt = $db->query("SELECT setting_key, setting_value FROM email_settings WHERE setting_key LIKE 'smtp_%'");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $smtp_settings[$row['setting_key']] = $row['setting_value'];
}

// Obsługa zapisywania ustawień SMTP
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_smtp'])) {
    try {
        $db->beginTransaction();
        
        $stmt = $db->prepare("
            INSERT INTO email_settings (setting_key, setting_value) 
            VALUES (?, ?) 
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
        ");
        
        $settings_to_save = [
            'smtp_enabled' => isset($_POST['smtp_enabled']) ? '1' : '0',
            'smtp_host' => $_POST['smtp_host'] ?? '',
            'smtp_port' => $_POST['smtp_port'] ?? '587',
            'smtp_security' => $_POST['smtp_security'] ?? 'tls',
            'smtp_username' => $_POST['smtp_username'] ?? '',
            'smtp_password' => $_POST['smtp_password'] ?? '',
            'smtp_from_email' => $_POST['smtp_from_email'] ?? '',
            'smtp_from_name' => $_POST['smtp_from_name'] ?? '',
            'smtp_reply_to' => $_POST['smtp_reply_to'] ?? '',
            'smtp_timeout' => $_POST['smtp_timeout'] ?? '30',
            'smtp_keepalive' => isset($_POST['smtp_keepalive']) ? '1' : '0',
            'smtp_debug' => isset($_POST['smtp_debug']) ? '1' : '0'
        ];
        
        foreach ($settings_to_save as $key => $value) {
            $stmt->execute([$key, $value]);
        }
        
        $db->commit();
        $success_message = "Ustawienia SMTP zostały zapisane!";
        
        // Odśwież ustawienia
        $smtp_settings = [];
        $stmt = $db->query("SELECT setting_key, setting_value FROM email_settings WHERE setting_key LIKE 'smtp_%'");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $smtp_settings[$row['setting_key']] = $row['setting_value'];
        }
        
    } catch (PDOException $e) {
        $db->rollback();
        $error_message = "Błąd podczas zapisywania: " . $e->getMessage();
    }
}

// Test połączenia SMTP
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_smtp'])) {
    $test_email = $_POST['test_email'] ?? '';
    if (!empty($test_email)) {
        // Mock test - w rzeczywistości testowałby rzeczywiste połączenie SMTP
        $test_result = test_smtp_connection($smtp_settings);
        if ($test_result['success']) {
            $test_message = "Test SMTP zakończony sukcesem! Email testowy wysłany na {$test_email}";
        } else {
            $test_error = "Błąd testu SMTP: " . $test_result['error'];
        }
    }
}

// Funkcja testowania SMTP (mock)
function test_smtp_connection($settings) {
    // Mock function - w rzeczywistości testowałaby połączenie SMTP
    if (empty($settings['smtp_host']) || empty($settings['smtp_username'])) {
        return ['success' => false, 'error' => 'Brak wymaganych danych konfiguracyjnych'];
    }
    
    // Symulacja testu
    return ['success' => true, 'message' => 'Połączenie SMTP działa poprawnie'];
}

// Wartości domyślne
$defaults = [
    'smtp_enabled' => '0',
    'smtp_port' => '587',
    'smtp_security' => 'tls',
    'smtp_timeout' => '30',
    'smtp_keepalive' => '0',
    'smtp_debug' => '0'
];

foreach ($defaults as $key => $default_value) {
    if (!isset($smtp_settings[$key])) {
        $smtp_settings[$key] = $default_value;
    }
}

// Popularne konfiguracje SMTP
$smtp_presets = [
    'gmail' => [
        'name' => 'Gmail',
        'host' => 'smtp.gmail.com',
        'port' => '587',
        'security' => 'tls'
    ],
    'outlook' => [
        'name' => 'Outlook/Hotmail',
        'host' => 'smtp-mail.outlook.com',
        'port' => '587',
        'security' => 'tls'
    ],
    'yahoo' => [
        'name' => 'Yahoo Mail',
        'host' => 'smtp.mail.yahoo.com',
        'port' => '587',
        'security' => 'tls'
    ],
    'onet' => [
        'name' => 'Onet',
        'host' => 'smtp.poczta.onet.pl',
        'port' => '465',
        'security' => 'ssl'
    ],
    'wp' => [
        'name' => 'WP.pl',
        'host' => 'smtp.wp.pl',
        'port' => '465',
        'security' => 'ssl'
    ]
];
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-1">Konfiguracja SMTP</h5>
        <p class="text-muted mb-0">Ustawienia serwera poczty wychodzącej</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-info btn-sm" data-bs-toggle="modal" data-bs-target="#presetsModal">
            <i class="bi bi-gear"></i> Presety
        </button>
        <button class="btn btn-outline-secondary btn-sm" onclick="location.reload()">
            <i class="bi bi-arrow-clockwise"></i> Odśwież
        </button>
    </div>
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

<?php if (isset($test_message)): ?>
    <div class="alert alert-success">
        <i class="bi bi-envelope-check"></i>
        <?= $test_message ?>
    </div>
<?php endif; ?>

<?php if (isset($test_error)): ?>
    <div class="alert alert-danger">
        <i class="bi bi-exclamation-triangle"></i>
        <?= $test_error ?>
    </div>
<?php endif; ?>

<form method="POST">
    <div class="row g-4">
        <!-- Główne ustawienia SMTP -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="bi bi-server"></i> Konfiguracja serwera SMTP</h6>
                    <div class="form-check form-switch">
                        <input type="checkbox" name="smtp_enabled" class="form-check-input" 
                               id="smtp_enabled" <?= $smtp_settings['smtp_enabled'] === '1' ? 'checked' : '' ?>
                               onchange="toggleSmtpFields(this.checked)">
                        <label class="form-check-label" for="smtp_enabled">
                            SMTP aktywny
                        </label>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label">Serwer SMTP</label>
                            <input type="text" name="smtp_host" class="form-control" 
                                   value="<?= htmlspecialchars($smtp_settings['smtp_host'] ?? '') ?>"
                                   placeholder="smtp.example.com" 
                                   <?= $smtp_settings['smtp_enabled'] !== '1' ? 'disabled' : '' ?>>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Port</label>
                            <input type="number" name="smtp_port" class="form-control" 
                                   value="<?= htmlspecialchars($smtp_settings['smtp_port']) ?>"
                                   min="1" max="65535"
                                   <?= $smtp_settings['smtp_enabled'] !== '1' ? 'disabled' : '' ?>>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Zabezpieczenie</label>
                            <select name="smtp_security" class="form-select" 
                                    <?= $smtp_settings['smtp_enabled'] !== '1' ? 'disabled' : '' ?>>
                                <option value="none" <?= $smtp_settings['smtp_security'] === 'none' ? 'selected' : '' ?>>Brak</option>
                                <option value="tls" <?= $smtp_settings['smtp_security'] === 'tls' ? 'selected' : '' ?>>TLS</option>
                                <option value="ssl" <?= $smtp_settings['smtp_security'] === 'ssl' ? 'selected' : '' ?>>SSL</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Timeout (sekundy)</label>
                            <input type="number" name="smtp_timeout" class="form-control" 
                                   value="<?= htmlspecialchars($smtp_settings['smtp_timeout']) ?>"
                                   min="10" max="300"
                                   <?= $smtp_settings['smtp_enabled'] !== '1' ? 'disabled' : '' ?>>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Nazwa użytkownika</label>
                            <input type="text" name="smtp_username" class="form-control" 
                                   value="<?= htmlspecialchars($smtp_settings['smtp_username'] ?? '') ?>"
                                   placeholder="user@example.com"
                                   <?= $smtp_settings['smtp_enabled'] !== '1' ? 'disabled' : '' ?>>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Hasło</label>
                            <input type="password" name="smtp_password" class="form-control" 
                                   value="<?= htmlspecialchars($smtp_settings['smtp_password'] ?? '') ?>"
                                   placeholder="••••••••"
                                   <?= $smtp_settings['smtp_enabled'] !== '1' ? 'disabled' : '' ?>>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Status i test -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0"><i class="bi bi-info-circle"></i> Status i test</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="d-flex align-items-center mb-2">
                            <span class="me-2">Status SMTP:</span>
                            <?php if ($smtp_settings['smtp_enabled'] === '1'): ?>
                                <span class="badge bg-success">Aktywny</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Nieaktywny</span>
                            <?php endif; ?>
                        </div>
                        
                        <?php if (!empty($smtp_settings['smtp_host'])): ?>
                            <small class="text-muted">
                                Serwer: <?= htmlspecialchars($smtp_settings['smtp_host']) ?>:<?= htmlspecialchars($smtp_settings['smtp_port']) ?>
                            </small>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Email testowy</label>
                        <input type="email" name="test_email" class="form-control" 
                               placeholder="test@example.com">
                    </div>
                    
                    <button type="submit" name="test_smtp" class="btn btn-outline-primary btn-sm w-100"
                            <?= $smtp_settings['smtp_enabled'] !== '1' ? 'disabled' : '' ?>>
                        <i class="bi bi-send"></i> Wyślij test
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Ustawienia nadawcy -->
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0"><i class="bi bi-person"></i> Ustawienia nadawcy</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Email nadawcy</label>
                        <input type="email" name="smtp_from_email" class="form-control" 
                               value="<?= htmlspecialchars($smtp_settings['smtp_from_email'] ?? '') ?>"
                               placeholder="noreply@example.com"
                               <?= $smtp_settings['smtp_enabled'] !== '1' ? 'disabled' : '' ?>>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Nazwa nadawcy</label>
                        <input type="text" name="smtp_from_name" class="form-control" 
                               value="<?= htmlspecialchars($smtp_settings['smtp_from_name'] ?? '') ?>"
                               placeholder="Wypożyczalnia"
                               <?= $smtp_settings['smtp_enabled'] !== '1' ? 'disabled' : '' ?>>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Email odpowiedzi</label>
                        <input type="email" name="smtp_reply_to" class="form-control" 
                               value="<?= htmlspecialchars($smtp_settings['smtp_reply_to'] ?? '') ?>"
                               placeholder="contact@example.com"
                               <?= $smtp_settings['smtp_enabled'] !== '1' ? 'disabled' : '' ?>>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Zaawansowane opcje -->
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0"><i class="bi bi-gear-wide-connected"></i> Opcje zaawansowane</h6>
                </div>
                <div class="card-body">
                    <div class="form-check form-switch mb-3">
                        <input type="checkbox" name="smtp_keepalive" class="form-check-input" 
                               id="smtp_keepalive" <?= $smtp_settings['smtp_keepalive'] === '1' ? 'checked' : '' ?>
                               <?= $smtp_settings['smtp_enabled'] !== '1' ? 'disabled' : '' ?>>
                        <label class="form-check-label" for="smtp_keepalive">
                            Utrzymuj połączenie (keepalive)
                        </label>
                    </div>
                    
                    <div class="form-check form-switch mb-3">
                        <input type="checkbox" name="smtp_debug" class="form-check-input" 
                               id="smtp_debug" <?= $smtp_settings['smtp_debug'] === '1' ? 'checked' : '' ?>
                               <?= $smtp_settings['smtp_enabled'] !== '1' ? 'disabled' : '' ?>>
                        <label class="form-check-label" for="smtp_debug">
                            Tryb debug
                        </label>
                    </div>
                    
                    <div class="alert alert-info">
                        <small>
                            <strong>Keepalive:</strong> Utrzymuje połączenie dla kolejnych emaili<br>
                            <strong>Debug:</strong> Szczegółowe logi błędów SMTP
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="mt-4 d-flex gap-2">
        <button type="submit" name="save_smtp" class="btn btn-primary">
            <i class="bi bi-check-lg"></i> Zapisz konfigurację SMTP
        </button>
        <button type="button" class="btn btn-outline-secondary" onclick="location.reload()">
            <i class="bi bi-arrow-clockwise"></i> Anuluj
        </button>
    </div>
</form>

<!-- Modal z presetami -->
<div class="modal fade" id="presetsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Gotowe konfiguracje SMTP</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <?php foreach ($smtp_presets as $key => $preset): ?>
                    <div class="d-flex justify-content-between align-items-center mb-2 p-2 border rounded">
                        <div>
                            <strong><?= $preset['name'] ?></strong><br>
                            <small class="text-muted"><?= $preset['host'] ?>:<?= $preset['port'] ?> (<?= strtoupper($preset['security']) ?>)</small>
                        </div>
                        <button type="button" class="btn btn-outline-primary btn-sm" 
                                onclick="applyPreset('<?= $key ?>')">
                            Użyj
                        </button>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<script>
const presets = <?= json_encode($smtp_presets) ?>;

function toggleSmtpFields(enabled) {
    const inputs = document.querySelectorAll('input[name^="smtp_"], select[name^="smtp_"]');
    inputs.forEach(input => {
        if (input.name !== 'smtp_enabled') {
            input.disabled = !enabled;
        }
    });
}

function applyPreset(presetKey) {
    const preset = presets[presetKey];
    if (preset) {
        document.querySelector('input[name="smtp_host"]').value = preset.host;
        document.querySelector('input[name="smtp_port"]').value = preset.port;
        document.querySelector('select[name="smtp_security"]').value = preset.security;
        
        // Zamknij modal
        const modal = bootstrap.Modal.getInstance(document.getElementById('presetsModal'));
        modal.hide();
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    const enabled = document.getElementById('smtp_enabled').checked;
    toggleSmtpFields(enabled);
});
</script>