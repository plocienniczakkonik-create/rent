<?php
// /pages/staff/settings/payments-general.php

$db = db();

// Pobierz ogólne ustawienia płatności
$general_settings = [];
$stmt = $db->query("SELECT setting_key, setting_value FROM payment_settings WHERE setting_key LIKE 'general_%'");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $general_settings[$row['setting_key']] = $row['setting_value'];
}

// Obsługa zapisywania
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_general'])) {
    try {
        $db->beginTransaction();
        
        $stmt = $db->prepare("
            INSERT INTO payment_settings (setting_key, setting_value) 
            VALUES (?, ?) 
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
        ");
        
        $settings_to_save = [
            'general_currency' => $_POST['currency'] ?? 'PLN',
            'general_min_deposit' => $_POST['min_deposit'] ?? '100',
            'general_deposit_type' => $_POST['deposit_type'] ?? 'fixed',
            'general_deposit_percentage' => $_POST['deposit_percentage'] ?? '20',
            'general_auto_refund' => isset($_POST['auto_refund']) ? '1' : '0',
            'general_refund_days' => $_POST['refund_days'] ?? '7',
            'general_payment_timeout' => $_POST['payment_timeout'] ?? '15',
            'general_receipt_email' => isset($_POST['receipt_email']) ? '1' : '0',
            'general_invoice_enabled' => isset($_POST['invoice_enabled']) ? '1' : '0',
            'general_tax_rate' => $_POST['tax_rate'] ?? '23',
            'general_rounding' => $_POST['rounding'] ?? '0.01'
        ];
        
        foreach ($settings_to_save as $key => $value) {
            $stmt->execute([$key, $value]);
        }
        
        $db->commit();
        $success_message = __('payment_settings_saved', 'admin', 'Ogólne ustawienia płatności zostały zapisane!');
        
        // Odśwież ustawienia
        $general_settings = [];
        $stmt = $db->query("SELECT setting_key, setting_value FROM payment_settings WHERE setting_key LIKE 'general_%'");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $general_settings[$row['setting_key']] = $row['setting_value'];
        }
        
    } catch (PDOException $e) {
        $db->rollback();
        $error_message = __('saving_error', 'admin', 'Błąd podczas zapisywania') . ": " . $e->getMessage();
    }
}

// Wartości domyślne
$defaults = [
    'general_currency' => 'PLN',
    'general_min_deposit' => '100',
    'general_deposit_type' => 'fixed',
    'general_deposit_percentage' => '20',
    'general_auto_refund' => '0',
    'general_refund_days' => '7',
    'general_payment_timeout' => '15',
    'general_receipt_email' => '1',
    'general_invoice_enabled' => '0',
    'general_tax_rate' => '23',
    'general_rounding' => '0.01'
];

// Połącz ustawienia z wartościami domyślnymi
foreach ($defaults as $key => $default_value) {
    if (!isset($general_settings[$key])) {
        $general_settings[$key] = $default_value;
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-1"><?= __('general_payment_settings', 'admin', 'Ogólne ustawienia płatności') ?></h5>
        <p class="text-muted mb-0"><?= __('basic_payment_config', 'admin', 'Podstawowa konfiguracja systemu płatności') ?></p>
    </div>
    <button class="btn btn-outline-secondary btn-sm" onclick="location.reload()">
        <i class="bi bi-arrow-clockwise"></i> <?= __('refresh', 'admin', 'Odśwież') ?>
    </button>
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
        <!-- Waluta i ceny -->
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0"><i class="bi bi-currency-exchange"></i> <?= __('currency_and_prices', 'admin', 'Waluta i ceny') ?></h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label"><?= __('main_currency', 'admin', 'Główna waluta') ?></label>
                        <select name="currency" class="form-select">
                            <option value="PLN" <?= $general_settings['general_currency'] === 'PLN' ? 'selected' : '' ?>><?= __('pln_polish_zloty', 'admin', 'PLN - Polski złoty') ?></option>
                            <option value="EUR" <?= $general_settings['general_currency'] === 'EUR' ? 'selected' : '' ?>><?= __('eur_euro', 'admin', 'EUR - Euro') ?></option>
                            <option value="USD" <?= $general_settings['general_currency'] === 'USD' ? 'selected' : '' ?>><?= __('usd_dollar', 'admin', 'USD - Dolar amerykański') ?></option>
                            <option value="GBP" <?= $general_settings['general_currency'] === 'GBP' ? 'selected' : '' ?>><?= __('gbp_pound', 'admin', 'GBP - Funt brytyjski') ?></option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label"><?= __('vat_rate', 'admin', 'Stawka VAT (%)') ?></label>
                        <input type="number" name="tax_rate" class="form-control" 
                               value="<?= htmlspecialchars($general_settings['general_tax_rate']) ?>"
                               min="0" max="100" step="0.01">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label"><?= __('amount_rounding', 'admin', 'Zaokrąglanie kwot') ?></label>
                        <select name="rounding" class="form-select">
                            <option value="0.01" <?= $general_settings['general_rounding'] === '0.01' ? 'selected' : '' ?>><?= __('to_penny', 'admin', 'Do groszy (0.01)') ?></option>
                            <option value="0.10" <?= $general_settings['general_rounding'] === '0.10' ? 'selected' : '' ?>><?= __('to_10_penny', 'admin', 'Do 10 groszy (0.10)') ?></option>
                            <option value="1.00" <?= $general_settings['general_rounding'] === '1.00' ? 'selected' : '' ?>><?= __('to_zloty', 'admin', 'Do złotówki (1.00)') ?></option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Kaucje -->
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0"><i class="bi bi-shield-check"></i> <?= __('deposits_and_bonds', 'admin', 'Kaucje i depozyty') ?></h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label"><?= __('deposit_type', 'admin', 'Typ kaucji') ?></label>
                        <select name="deposit_type" class="form-select" onchange="toggleDepositFields(this.value)">
                            <option value="fixed" <?= $general_settings['general_deposit_type'] === 'fixed' ? 'selected' : '' ?>><?= __('fixed_amount', 'admin', 'Kwota stała') ?></option>
                            <option value="percentage" <?= $general_settings['general_deposit_type'] === 'percentage' ? 'selected' : '' ?>><?= __('percentage_of_value', 'admin', 'Procent od wartości') ?></option>
                            <option value="none" <?= $general_settings['general_deposit_type'] === 'none' ? 'selected' : '' ?>><?= __('no_deposit', 'admin', 'Brak kaucji') ?></option>
                        </select>
                    </div>
                    
                    <div class="mb-3" id="fixed-deposit">
                        <label class="form-label"><?= __('minimum_deposit', 'admin', 'Minimalna kaucja') ?> (<?= $general_settings['general_currency'] ?>)</label>
                        <input type="number" name="min_deposit" class="form-control" 
                               value="<?= htmlspecialchars($general_settings['general_min_deposit']) ?>"
                               min="0" step="0.01">
                    </div>
                    
                    <div class="mb-3" id="percentage-deposit">
                        <label class="form-label"><?= __('deposit_percentage', 'admin', 'Procent kaucji (%)') ?></label>
                        <input type="number" name="deposit_percentage" class="form-control" 
                               value="<?= htmlspecialchars($general_settings['general_deposit_percentage']) ?>"
                               min="0" max="100" step="1">
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Zwroty -->
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0"><i class="bi bi-arrow-return-left"></i> <?= __('refunds_and_cancellations', 'admin', 'Zwroty i anulowania') ?></h6>
                </div>
                <div class="card-body">
                    <div class="form-check form-switch mb-3">
                        <input type="checkbox" name="auto_refund" class="form-check-input" 
                               id="auto_refund" <?= $general_settings['general_auto_refund'] === '1' ? 'checked' : '' ?>>
                        <label class="form-check-label" for="auto_refund">
                            <?= __('automatic_refunds', 'admin', 'Automatyczne zwroty') ?>
                        </label>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label"><?= __('refund_days_limit', 'admin', 'Limit dni na zwrot') ?></label>
                        <input type="number" name="refund_days" class="form-control" 
                               value="<?= htmlspecialchars($general_settings['general_refund_days']) ?>"
                               min="1" max="365">
                        <div class="form-text"><?= __('refund_days_help', 'admin', 'Liczba dni na zwrot kaucji po zakończeniu wynajmu') ?></div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Timeouty i powiadomienia -->
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0"><i class="bi bi-clock"></i> <?= __('timeouts_and_notifications', 'admin', 'Timeouty i powiadomienia') ?></h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label"><?= __('payment_timeout_minutes', 'admin', 'Timeout płatności (minuty)') ?></label>
                        <input type="number" name="payment_timeout" class="form-control" 
                               value="<?= htmlspecialchars($general_settings['general_payment_timeout']) ?>"
                               min="5" max="120">
                        <div class="form-text"><?= __('payment_timeout_help', 'admin', 'Czas na dokończenie płatności') ?></div>
                    </div>
                    
                    <div class="form-check form-switch mb-3">
                        <input type="checkbox" name="receipt_email" class="form-check-input" 
                               id="receipt_email" <?= $general_settings['general_receipt_email'] === '1' ? 'checked' : '' ?>>
                        <label class="form-check-label" for="receipt_email">
                            <?= __('send_receipt_emails', 'admin', 'Wysyłaj potwierdzenia e-mail') ?>
                        </label>
                    </div>
                    
                    <div class="form-check form-switch mb-3">
                        <input type="checkbox" name="invoice_enabled" class="form-check-input" 
                               id="invoice_enabled" <?= $general_settings['general_invoice_enabled'] === '1' ? 'checked' : '' ?>>
                        <label class="form-check-label" for="invoice_enabled">
                            <?= __('invoice_generation', 'admin', 'Generowanie faktur') ?>
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="mt-4">
        <button type="submit" name="save_general" class="btn btn-primary">
            <i class="bi bi-check-lg"></i> <?= __('save_settings', 'admin', 'Zapisz ustawienia') ?>
        </button>
        <button type="button" class="btn btn-outline-secondary" onclick="location.reload()">
            <i class="bi bi-arrow-clockwise"></i> <?= __('cancel', 'admin', 'Anuluj') ?>
        </button>
    </div>
</form>

<script>
function toggleDepositFields(type) {
    const fixedDeposit = document.getElementById('fixed-deposit');
    const percentageDeposit = document.getElementById('percentage-deposit');
    
    if (type === 'fixed') {
        fixedDeposit.style.display = 'block';
        percentageDeposit.style.display = 'none';
    } else if (type === 'percentage') {
        fixedDeposit.style.display = 'none';
        percentageDeposit.style.display = 'block';
    } else {
        fixedDeposit.style.display = 'none';
        percentageDeposit.style.display = 'none';
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    const depositType = document.querySelector('select[name="deposit_type"]').value;
    toggleDepositFields(depositType);
    
    // Auto-fade success alerts
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

<style>
.auto-fade {
    transition: opacity 0.5s ease-out;
}
</style>