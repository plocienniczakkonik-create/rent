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
        $success_message = "Ogólne ustawienia płatności zostały zapisane!";
        
        // Odśwież ustawienia
        $general_settings = [];
        $stmt = $db->query("SELECT setting_key, setting_value FROM payment_settings WHERE setting_key LIKE 'general_%'");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $general_settings[$row['setting_key']] = $row['setting_value'];
        }
        
    } catch (PDOException $e) {
        $db->rollback();
        $error_message = "Błąd podczas zapisywania: " . $e->getMessage();
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
        <h5 class="mb-1">Ogólne ustawienia płatności</h5>
        <p class="text-muted mb-0">Podstawowa konfiguracja systemu płatności</p>
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
        <!-- Waluta i ceny -->
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0"><i class="bi bi-currency-exchange"></i> Waluta i ceny</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Główna waluta</label>
                        <select name="currency" class="form-select">
                            <option value="PLN" <?= $general_settings['general_currency'] === 'PLN' ? 'selected' : '' ?>>PLN - Polski złoty</option>
                            <option value="EUR" <?= $general_settings['general_currency'] === 'EUR' ? 'selected' : '' ?>>EUR - Euro</option>
                            <option value="USD" <?= $general_settings['general_currency'] === 'USD' ? 'selected' : '' ?>>USD - Dolar amerykański</option>
                            <option value="GBP" <?= $general_settings['general_currency'] === 'GBP' ? 'selected' : '' ?>>GBP - Funt brytyjski</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Stawka VAT (%)</label>
                        <input type="number" name="tax_rate" class="form-control" 
                               value="<?= htmlspecialchars($general_settings['general_tax_rate']) ?>"
                               min="0" max="100" step="0.01">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Zaokrąglanie kwot</label>
                        <select name="rounding" class="form-select">
                            <option value="0.01" <?= $general_settings['general_rounding'] === '0.01' ? 'selected' : '' ?>>Do groszy (0.01)</option>
                            <option value="0.10" <?= $general_settings['general_rounding'] === '0.10' ? 'selected' : '' ?>>Do 10 groszy (0.10)</option>
                            <option value="1.00" <?= $general_settings['general_rounding'] === '1.00' ? 'selected' : '' ?>>Do złotówki (1.00)</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Kaucje -->
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0"><i class="bi bi-shield-check"></i> Kaucje i depozyty</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Typ kaucji</label>
                        <select name="deposit_type" class="form-select" onchange="toggleDepositFields(this.value)">
                            <option value="fixed" <?= $general_settings['general_deposit_type'] === 'fixed' ? 'selected' : '' ?>>Kwota stała</option>
                            <option value="percentage" <?= $general_settings['general_deposit_type'] === 'percentage' ? 'selected' : '' ?>>Procent od wartości</option>
                            <option value="none" <?= $general_settings['general_deposit_type'] === 'none' ? 'selected' : '' ?>>Brak kaucji</option>
                        </select>
                    </div>
                    
                    <div class="mb-3" id="fixed-deposit">
                        <label class="form-label">Minimalna kaucja (<?= $general_settings['general_currency'] ?>)</label>
                        <input type="number" name="min_deposit" class="form-control" 
                               value="<?= htmlspecialchars($general_settings['general_min_deposit']) ?>"
                               min="0" step="0.01">
                    </div>
                    
                    <div class="mb-3" id="percentage-deposit">
                        <label class="form-label">Procent kaucji (%)</label>
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
                    <h6 class="mb-0"><i class="bi bi-arrow-return-left"></i> Zwroty i anulowania</h6>
                </div>
                <div class="card-body">
                    <div class="form-check form-switch mb-3">
                        <input type="checkbox" name="auto_refund" class="form-check-input" 
                               id="auto_refund" <?= $general_settings['general_auto_refund'] === '1' ? 'checked' : '' ?>>
                        <label class="form-check-label" for="auto_refund">
                            Automatyczne zwroty
                        </label>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Limit dni na zwrot</label>
                        <input type="number" name="refund_days" class="form-control" 
                               value="<?= htmlspecialchars($general_settings['general_refund_days']) ?>"
                               min="1" max="365">
                        <div class="form-text">Liczba dni na zwrot kaucji po zakończeniu wynajmu</div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Timeouty i powiadomienia -->
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0"><i class="bi bi-clock"></i> Timeouty i powiadomienia</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Timeout płatności (minuty)</label>
                        <input type="number" name="payment_timeout" class="form-control" 
                               value="<?= htmlspecialchars($general_settings['general_payment_timeout']) ?>"
                               min="5" max="120">
                        <div class="form-text">Czas na dokończenie płatności</div>
                    </div>
                    
                    <div class="form-check form-switch mb-3">
                        <input type="checkbox" name="receipt_email" class="form-check-input" 
                               id="receipt_email" <?= $general_settings['general_receipt_email'] === '1' ? 'checked' : '' ?>>
                        <label class="form-check-label" for="receipt_email">
                            Wysyłaj potwierdzenia e-mail
                        </label>
                    </div>
                    
                    <div class="form-check form-switch mb-3">
                        <input type="checkbox" name="invoice_enabled" class="form-check-input" 
                               id="invoice_enabled" <?= $general_settings['general_invoice_enabled'] === '1' ? 'checked' : '' ?>>
                        <label class="form-check-label" for="invoice_enabled">
                            Generowanie faktur
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="mt-4">
        <button type="submit" name="save_general" class="btn btn-primary">
            <i class="bi bi-check-lg"></i> Zapisz ustawienia
        </button>
        <button type="button" class="btn btn-outline-secondary" onclick="location.reload()">
            <i class="bi bi-arrow-clockwise"></i> Anuluj
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
});
</script>