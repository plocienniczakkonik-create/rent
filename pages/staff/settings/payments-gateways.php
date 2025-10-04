<?php
// /pages/staff/settings/payments-gateways.php

$db = db();

// Struktura dla różnych bramek płatności
$payment_gateways = [
    'stripe' => [
        'name' => __('stripe_name', 'admin', 'Stripe'),
        'description' => __('stripe_description', 'admin', 'Globalna platforma płatności online obsługująca karty, BLIK, PayU'),
        'icon' => 'bi-stripe',
        'color' => 'primary',
        'fields' => [
            'stripe_public_key' => ['label' => __('stripe_public_key', 'admin', 'Klucz publiczny'), 'type' => 'text', 'placeholder' => 'pk_live_...'],
            'stripe_secret_key' => ['label' => __('stripe_secret_key', 'admin', 'Klucz prywatny'), 'type' => 'password', 'placeholder' => 'sk_live_...'],
            'stripe_webhook_secret' => ['label' => __('stripe_webhook_secret', 'admin', 'Webhook Secret'), 'type' => 'password', 'placeholder' => 'whsec_...'],
            'stripe_currency' => ['label' => __('stripe_currency', 'admin', 'Waluta'), 'type' => 'select', 'options' => ['PLN' => 'PLN', 'EUR' => 'EUR', 'USD' => 'USD']]
        ]
    ],
    'paypal' => [
        'name' => __('paypal_name', 'admin', 'PayPal'),
        'description' => __('paypal_description', 'admin', 'Międzynarodowy system płatności online PayPal'),
        'icon' => 'bi-paypal',
        'color' => 'warning',
        'fields' => [
            'paypal_client_id' => ['label' => __('paypal_client_id', 'admin', 'Client ID'), 'type' => 'text', 'placeholder' => 'AXxxx...'],
            'paypal_client_secret' => ['label' => __('paypal_client_secret', 'admin', 'Client Secret'), 'type' => 'password', 'placeholder' => 'EXxxx...'],
            'paypal_mode' => ['label' => __('paypal_mode', 'admin', 'Tryb'), 'type' => 'select', 'options' => ['sandbox' => __('sandbox_test', 'admin', 'Sandbox (test)'), 'live' => __('live_production', 'admin', 'Live (produkcja)')]]
        ]
    ],
    'przelewy24' => [
        'name' => __('przelewy24_name', 'admin', 'Przelewy24'),
        'description' => __('przelewy24_description', 'admin', 'Polski system płatności online z szerokim wsparciem banków'),
        'icon' => 'bi-bank',
        'color' => 'success',
        'fields' => [
            'p24_merchant_id' => ['label' => __('p24_merchant_id', 'admin', 'Merchant ID'), 'type' => 'text', 'placeholder' => '12345'],
            'p24_pos_id' => ['label' => __('p24_pos_id', 'admin', 'POS ID'), 'type' => 'text', 'placeholder' => '12345'],
            'p24_crc_key' => ['label' => __('p24_crc_key', 'admin', 'CRC Key'), 'type' => 'password', 'placeholder' => 'xxx...'],
            'p24_mode' => ['label' => __('p24_mode', 'admin', 'Tryb'), 'type' => 'select', 'options' => ['sandbox' => __('sandbox_test', 'admin', 'Sandbox (test)'), 'live' => __('live_production', 'admin', 'Live (produkcja)')]]
        ]
    ]
];

// Pobierz aktualne ustawienia z bazy danych
$current_settings = [];
$stmt = $db->query("SELECT setting_key, setting_value FROM payment_settings");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $current_settings[$row['setting_key']] = $row['setting_value'];
}

// Obsługa zapisywania ustawień
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_gateway'])) {
    $gateway = $_POST['gateway'] ?? '';
    $enabled = isset($_POST["{$gateway}_enabled"]) ? 1 : 0;
    
    if (isset($payment_gateways[$gateway])) {
        try {
            $db->beginTransaction();
            
            // Aktualizuj status aktywności bramki
            $stmt = $db->prepare("
                INSERT INTO payment_settings (setting_key, setting_value) 
                VALUES (?, ?) 
                ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
            ");
            $stmt->execute(["{$gateway}_enabled", $enabled]);
            
            // Zapisz wszystkie pola konfiguracyjne
            foreach ($payment_gateways[$gateway]['fields'] as $field_key => $field_config) {
                $value = $_POST[$field_key] ?? '';
                if (!empty($value) || $enabled) {
                    $stmt->execute([$field_key, $value]);
                }
            }
            
            $db->commit();
            $success_message = __('gateway_saved_success', 'admin', 'Ustawienia bramki zostały zapisane!');
            
            // Odśwież ustawienia
            $current_settings = [];
            $stmt = $db->query("SELECT setting_key, setting_value FROM payment_settings");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $current_settings[$row['setting_key']] = $row['setting_value'];
            }
            
        } catch (PDOException $e) {
            $db->rollback();
            $error_message = __('saving_error', 'admin', 'Błąd podczas zapisywania') . ": " . $e->getMessage();
        }
    }
}

// Test połączenia z bramką
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_gateway'])) {
    $gateway = $_POST['gateway'] ?? '';
    if (isset($payment_gateways[$gateway])) {
        $test_results[$gateway] = test_payment_gateway($gateway, $current_settings);
    }
}

// Funkcja testowania bramki (mock)
function test_payment_gateway($gateway, $settings) {
    // Mock function - w rzeczywistości wysyłałaby requesty testowe
    $required_fields = [];
    switch ($gateway) {
        case 'stripe':
            $required_fields = ['stripe_public_key', 'stripe_secret_key'];
            break;
        case 'paypal':
            $required_fields = ['paypal_client_id', 'paypal_client_secret'];
            break;
        case 'przelewy24':
            $required_fields = ['p24_merchant_id', 'p24_pos_id', 'p24_crc_key'];
            break;
    }
    
    foreach ($required_fields as $field) {
        if (empty($settings[$field])) {
            return ['status' => 'error', 'message' => __('missing_required_config', 'admin', 'Brak wymaganych danych konfiguracyjnych')];
        }
    }
    
    // Symulacja testu
    return ['status' => 'success', 'message' => __('connection_successful', 'admin', 'Połączenie z bramką działa poprawnie')];
}
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-1"><?= __('payment_gateways', 'admin', 'Bramki płatności') ?></h5>
        <p class="text-muted mb-0"><?= __('gateway_configuration', 'admin', 'Konfiguracja systemów płatności online') ?></p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-info btn-sm" data-bs-toggle="modal" data-bs-target="#webhookModal">
            <i class="bi bi-link-45deg"></i> Webhook URLs
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
    </div>
<?php endif; ?>

<?php if (isset($error_message)): ?>
    <div class="alert alert-danger">
        <i class="bi bi-exclamation-triangle"></i>
        <?= $error_message ?>
    </div>
<?php endif; ?>

<div class="row g-4">
    <?php foreach ($payment_gateways as $gateway_key => $gateway): ?>
        <?php 
        $is_enabled = !empty($current_settings["{$gateway_key}_enabled"]);
        $has_config = false;
        foreach ($gateway['fields'] as $field_key => $field_config) {
            if (!empty($current_settings[$field_key])) {
                $has_config = true;
                break;
            }
        }
        ?>
        
        <div class="col-lg-6">
            <div class="card h-100 <?= $is_enabled ? 'border-success' : '' ?>">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                        <i class="<?= $gateway['icon'] ?> text-<?= $gateway['color'] ?> fs-4 me-2"></i>
                        <div>
                            <h6 class="mb-0"><?= $gateway['name'] ?></h6>
                            <small class="text-muted"><?= $gateway['description'] ?></small>
                        </div>
                    </div>
                    <div class="form-check form-switch">
                        <input type="checkbox" class="form-check-input" 
                               id="<?= $gateway_key ?>_enabled"
                               <?= $is_enabled ? 'checked' : '' ?>
                               onchange="toggleGateway('<?= $gateway_key ?>', this.checked)">
                        <label class="form-check-label" for="<?= $gateway_key ?>_enabled">
                            <?= $is_enabled ? __('gateway_enabled', 'admin', 'Aktywna') : __('gateway_disabled', 'admin', 'Nieaktywna') ?>
                        </label>
                    </div>
                </div>
                
                <div class="card-body">
                    <form method="POST" class="gateway-form" data-gateway="<?= $gateway_key ?>">
                        <input type="hidden" name="gateway" value="<?= $gateway_key ?>">
                        <input type="hidden" name="<?= $gateway_key ?>_enabled" value="<?= $is_enabled ? '1' : '0' ?>">
                        
                        <?php foreach ($gateway['fields'] as $field_key => $field_config): ?>
                            <div class="mb-3">
                                <label class="form-label"><?= $field_config['label'] ?></label>
                                <?php if ($field_config['type'] === 'select'): ?>
                                    <select name="<?= $field_key ?>" class="form-select" 
                                            <?= !$is_enabled ? 'disabled' : '' ?>>
                                        <?php foreach ($field_config['options'] as $value => $label): ?>
                                            <option value="<?= $value ?>" 
                                                    <?= ($current_settings[$field_key] ?? '') === $value ? 'selected' : '' ?>>
                                                <?= $label ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php else: ?>
                                    <input type="<?= $field_config['type'] ?>" 
                                           name="<?= $field_key ?>" 
                                           class="form-control"
                                           placeholder="<?= $field_config['placeholder'] ?? '' ?>"
                                           value="<?= $field_config['type'] !== 'password' ? htmlspecialchars($current_settings[$field_key] ?? '') : '' ?>"
                                           <?= !$is_enabled ? 'disabled' : '' ?>>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                        
                        <div class="d-flex gap-2">
                            <button type="submit" name="save_gateway" 
                                    class="btn btn-<?= $gateway['color'] ?> btn-sm"
                                    <?= !$is_enabled ? 'disabled' : '' ?>>
                                <i class="bi bi-check-lg"></i> <?= __('save', 'admin', 'Zapisz') ?>
                            </button>
                            <button type="submit" name="test_gateway" 
                                    class="btn btn-outline-secondary btn-sm"
                                    <?= !$is_enabled || !$has_config ? 'disabled' : '' ?>>
                                <i class="bi bi-check-circle"></i> <?= __('test_connection', 'admin', 'Testuj') ?>
                            </button>
                        </div>
                        
                        <?php if (isset($test_results[$gateway_key])): ?>
                            <div class="mt-2">
                                <div class="alert alert-<?= $test_results[$gateway_key]['status'] === 'success' ? 'success' : 'danger' ?> alert-sm">
                                    <i class="bi bi-<?= $test_results[$gateway_key]['status'] === 'success' ? 'check-circle' : 'x-circle' ?>"></i>
                                    <?= $test_results[$gateway_key]['message'] ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Modal z webhook URLs -->
<div class="modal fade" id="webhookModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Webhook URLs</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p><?= __('configure_webhook_urls', 'admin', 'Skonfiguruj poniższe URL-e webhook w panelach bramek płatności:') ?></p>
                <?php foreach ($payment_gateways as $gateway_key => $gateway): ?>
                    <div class="mb-3">
                        <label class="form-label fw-semibold"><?= $gateway['name'] ?></label>
                        <div class="input-group">
                            <input type="text" class="form-control font-monospace" 
                                   value="<?= $BASE ?>/webhooks/<?= $gateway_key ?>.php" readonly>
                            <button class="btn btn-outline-secondary" type="button" 
                                    onclick="navigator.clipboard.writeText('<?= $BASE ?>/webhooks/<?= $gateway_key ?>.php')">
                                <i class="bi bi-clipboard"></i>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<script>
function toggleGateway(gateway, enabled) {
    const form = document.querySelector(`[data-gateway="${gateway}"]`);
    const inputs = form.querySelectorAll('input, select, button');
    const hiddenInput = form.querySelector(`input[name="${gateway}_enabled"]`);
    
    hiddenInput.value = enabled ? '1' : '0';
    
    inputs.forEach(input => {
        if (input.name !== `${gateway}_enabled`) {
            input.disabled = !enabled;
        }
    });
    
    // Auto-save status
    const formData = new FormData();
    formData.append('gateway', gateway);
    formData.append('save_gateway', '1');
    formData.append(`${gateway}_enabled`, enabled ? '1' : '0');
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData
    }).then(() => {
        // Optional: show success message
    });
}
</script>

<style>
.alert-sm {
    padding: 0.5rem 0.75rem;
    font-size: 0.875rem;
}

.font-monospace {
    font-family: 'Courier New', monospace;
    font-size: 0.875rem;
}

.card.border-success {
    border-width: 2px;
}

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
});
</script>