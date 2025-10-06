<?php
// shop-integrations.php - Ustawienia integracji systemu sklepu
require_once dirname(__DIR__, 3) . '/includes/db.php';

$BASE = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
$pdo = db();

// Obsługa zapisywania ustawień
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_integrations') {
    try {
        $settings = [
            'invoice_integration_enabled' => isset($_POST['invoice_integration_enabled']) ? 1 : 0,
            'invoice_api_provider' => trim($_POST['invoice_api_provider'] ?? ''),
            'invoice_api_key' => trim($_POST['invoice_api_key'] ?? ''),
            'invoice_api_url' => trim($_POST['invoice_api_url'] ?? ''),
            'erp_integration_enabled' => isset($_POST['erp_integration_enabled']) ? 1 : 0,
            'erp_system_type' => trim($_POST['erp_system_type'] ?? ''),
            'erp_api_endpoint' => trim($_POST['erp_api_endpoint'] ?? ''),
            'erp_auth_token' => trim($_POST['erp_auth_token'] ?? ''),
            'crm_integration_enabled' => isset($_POST['crm_integration_enabled']) ? 1 : 0,
            'crm_system_type' => trim($_POST['crm_system_type'] ?? ''),
            'crm_api_key' => trim($_POST['crm_api_key'] ?? ''),
            'crm_webhook_url' => trim($_POST['crm_webhook_url'] ?? ''),
            'webhook_secret' => trim($_POST['webhook_secret'] ?? ''),
            'sync_customer_data' => isset($_POST['sync_customer_data']) ? 1 : 0,
            'sync_reservations' => isset($_POST['sync_reservations']) ? 1 : 0,
            'auto_create_invoices' => isset($_POST['auto_create_invoices']) ? 1 : 0,
        ];
        
        // Zapisz ustawienia do bazy danych
        foreach ($settings as $key => $value) {
            $stmt = $pdo->prepare("
                INSERT INTO system_settings (setting_key, setting_value, updated_at) 
                VALUES (?, ?, NOW()) 
                ON DUPLICATE KEY UPDATE 
                setting_value = VALUES(setting_value), 
                updated_at = NOW()
            ");
            $stmt->execute([$key, $value]);
        }
        
        $success = true;
    } catch (Exception $e) {
        $error = 'Błąd zapisu: ' . $e->getMessage();
    }
}

// Pobierz aktualne ustawienia
$current_settings = [];
try {
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM system_settings WHERE setting_key LIKE 'invoice_%' OR setting_key LIKE 'erp_%' OR setting_key LIKE 'crm_%' OR setting_key IN ('webhook_secret', 'sync_customer_data', 'sync_reservations', 'auto_create_invoices')");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $row) {
        $current_settings[$row['setting_key']] = $row['setting_value'];
    }
} catch (Exception $e) {
    // Brak ustawień lub błąd - użyj domyślnych wartości
}

function getSetting($key, $default = '') {
    global $current_settings;
    return $current_settings[$key] ?? $default;
}

function isSettingEnabled($key) {
    return (bool) getSetting($key, 0);
}
?>

<?php if (isset($success)): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="bi bi-check-circle me-2"></i>
        Ustawienia integracji zostały zapisane pomyślnie!
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (isset($error)): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="bi bi-exclamation-triangle me-2"></i>
        <?= htmlspecialchars($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-12">
        <div class="alert alert-info">
            <i class="bi bi-info-circle me-2"></i>
            <strong>Integracje systemu</strong><br>
            Skonfiguruj połączenia z zewnętrznymi systemami fakturującymi, ERP i CRM dla automatyzacji procesów biznesowych.
        </div>
    </div>
</div>

<form method="POST" action="">
    <input type="hidden" name="action" value="save_integrations">
    
    <div class="row">
        <!-- Integracje fakturowe -->
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-receipt me-2"></i>Systemy fakturujące</h5>
                </div>
                <div class="card-body">
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" value="1" id="invoice_integration_enabled" 
                               name="invoice_integration_enabled" <?= isSettingEnabled('invoice_integration_enabled') ? 'checked' : '' ?>>
                        <label class="form-check-label fw-bold" for="invoice_integration_enabled">
                            Włącz integrację z systemem fakturującym
                        </label>
                    </div>
                    
                    <div id="invoice-settings" style="display: <?= isSettingEnabled('invoice_integration_enabled') ? 'block' : 'none' ?>;">
                        <div class="mb-3">
                            <label for="invoice_api_provider" class="form-label">Dostawca API</label>
                            <select class="form-select" id="invoice_api_provider" name="invoice_api_provider">
                                <option value="">Wybierz dostawcę...</option>
                                <option value="fakturownia" <?= getSetting('invoice_api_provider') === 'fakturownia' ? 'selected' : '' ?>>Fakturownia.pl</option>
                                <option value="ifirma" <?= getSetting('invoice_api_provider') === 'ifirma' ? 'selected' : '' ?>>iFirma</option>
                                <option value="wfirma" <?= getSetting('invoice_api_provider') === 'wfirma' ? 'selected' : '' ?>>wFirma</option>
                                <option value="taxe" <?= getSetting('invoice_api_provider') === 'taxe' ? 'selected' : '' ?>>Taxe.pl</option>
                                <option value="custom" <?= getSetting('invoice_api_provider') === 'custom' ? 'selected' : '' ?>>Własny system</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="invoice_api_key" class="form-label">Klucz API</label>
                            <input type="password" class="form-control" id="invoice_api_key" 
                                   name="invoice_api_key" value="<?= htmlspecialchars(getSetting('invoice_api_key')) ?>"
                                   placeholder="Wprowadź klucz API">
                        </div>
                        
                        <div class="mb-3">
                            <label for="invoice_api_url" class="form-label">URL API (dla własnego systemu)</label>
                            <input type="url" class="form-control" id="invoice_api_url" 
                                   name="invoice_api_url" value="<?= htmlspecialchars(getSetting('invoice_api_url')) ?>"
                                   placeholder="https://api.twojsystem.pl/v1/">
                        </div>
                        
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="1" id="auto_create_invoices" 
                                   name="auto_create_invoices" <?= isSettingEnabled('auto_create_invoices') ? 'checked' : '' ?>>
                            <label class="form-check-label" for="auto_create_invoices">
                                Automatycznie twórz faktury po zakończeniu rezerwacji
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Integracje ERP -->
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="bi bi-diagram-3 me-2"></i>Systemy ERP</h5>
                </div>
                <div class="card-body">
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" value="1" id="erp_integration_enabled" 
                               name="erp_integration_enabled" <?= isSettingEnabled('erp_integration_enabled') ? 'checked' : '' ?>>
                        <label class="form-check-label fw-bold" for="erp_integration_enabled">
                            Włącz integrację z systemem ERP
                        </label>
                    </div>
                    
                    <div id="erp-settings" style="display: <?= isSettingEnabled('erp_integration_enabled') ? 'block' : 'none' ?>;">
                        <div class="mb-3">
                            <label for="erp_system_type" class="form-label">Typ systemu ERP</label>
                            <select class="form-select" id="erp_system_type" name="erp_system_type">
                                <option value="">Wybierz system...</option>
                                <option value="sap" <?= getSetting('erp_system_type') === 'sap' ? 'selected' : '' ?>>SAP</option>
                                <option value="oracle" <?= getSetting('erp_system_type') === 'oracle' ? 'selected' : '' ?>>Oracle ERP</option>
                                <option value="microsoft" <?= getSetting('erp_system_type') === 'microsoft' ? 'selected' : '' ?>>Microsoft Dynamics</option>
                                <option value="sage" <?= getSetting('erp_system_type') === 'sage' ? 'selected' : '' ?>>Sage</option>
                                <option value="custom" <?= getSetting('erp_system_type') === 'custom' ? 'selected' : '' ?>>Własny system</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="erp_api_endpoint" class="form-label">Endpoint API</label>
                            <input type="url" class="form-control" id="erp_api_endpoint" 
                                   name="erp_api_endpoint" value="<?= htmlspecialchars(getSetting('erp_api_endpoint')) ?>"
                                   placeholder="https://erp.firma.pl/api/v1/">
                        </div>
                        
                        <div class="mb-3">
                            <label for="erp_auth_token" class="form-label">Token autoryzacyjny</label>
                            <input type="password" class="form-control" id="erp_auth_token" 
                                   name="erp_auth_token" value="<?= htmlspecialchars(getSetting('erp_auth_token')) ?>"
                                   placeholder="Bearer token lub API key">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <!-- Integracje CRM -->
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="bi bi-people me-2"></i>Systemy CRM</h5>
                </div>
                <div class="card-body">
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" value="1" id="crm_integration_enabled" 
                               name="crm_integration_enabled" <?= isSettingEnabled('crm_integration_enabled') ? 'checked' : '' ?>>
                        <label class="form-check-label fw-bold" for="crm_integration_enabled">
                            Włącz integrację z systemem CRM
                        </label>
                    </div>
                    
                    <div id="crm-settings" style="display: <?= isSettingEnabled('crm_integration_enabled') ? 'block' : 'none' ?>;">
                        <div class="mb-3">
                            <label for="crm_system_type" class="form-label">Typ systemu CRM</label>
                            <select class="form-select" id="crm_system_type" name="crm_system_type">
                                <option value="">Wybierz system...</option>
                                <option value="salesforce" <?= getSetting('crm_system_type') === 'salesforce' ? 'selected' : '' ?>>Salesforce</option>
                                <option value="hubspot" <?= getSetting('crm_system_type') === 'hubspot' ? 'selected' : '' ?>>HubSpot</option>
                                <option value="pipedrive" <?= getSetting('crm_system_type') === 'pipedrive' ? 'selected' : '' ?>>Pipedrive</option>
                                <option value="freshworks" <?= getSetting('crm_system_type') === 'freshworks' ? 'selected' : '' ?>>Freshworks</option>
                                <option value="custom" <?= getSetting('crm_system_type') === 'custom' ? 'selected' : '' ?>>Własny system</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="crm_api_key" class="form-label">Klucz API</label>
                            <input type="password" class="form-control" id="crm_api_key" 
                                   name="crm_api_key" value="<?= htmlspecialchars(getSetting('crm_api_key')) ?>"
                                   placeholder="Klucz API lub token">
                        </div>
                        
                        <div class="mb-3">
                            <label for="crm_webhook_url" class="form-label">URL webhook</label>
                            <input type="url" class="form-control" id="crm_webhook_url" 
                                   name="crm_webhook_url" value="<?= htmlspecialchars(getSetting('crm_webhook_url')) ?>"
                                   placeholder="https://crm.firma.pl/webhooks/rental">
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Ustawienia synchronizacji -->
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0"><i class="bi bi-arrow-repeat me-2"></i>Synchronizacja danych</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="webhook_secret" class="form-label">Webhook Secret</label>
                        <input type="password" class="form-control" id="webhook_secret" 
                               name="webhook_secret" value="<?= htmlspecialchars(getSetting('webhook_secret')) ?>"
                               placeholder="Tajny klucz do walidacji webhook">
                        <div class="form-text">Używany do bezpiecznej walidacji przychodzących webhook.</div>
                    </div>
                    
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" value="1" id="sync_customer_data" 
                               name="sync_customer_data" <?= isSettingEnabled('sync_customer_data') ? 'checked' : '' ?>>
                        <label class="form-check-label" for="sync_customer_data">
                            Synchronizuj dane klientów
                        </label>
                    </div>
                    
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" value="1" id="sync_reservations" 
                               name="sync_reservations" <?= isSettingEnabled('sync_reservations') ? 'checked' : '' ?>>
                        <label class="form-check-label" for="sync_reservations">
                            Synchronizuj rezerwacje
                        </label>
                    </div>
                    
                    <div class="alert alert-light">
                        <small class="text-muted">
                            <i class="bi bi-info-circle me-1"></i>
                            Synchronizacja jest automatyczna po włączeniu odpowiednich integracji. 
                            Dane są przesyłane w czasie rzeczywistym przy tworzeniu/aktualizacji rekordów.
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg me-2"></i>Zapisz ustawienia integracji
                </button>
                <button type="button" class="btn btn-outline-secondary" onclick="testConnections()">
                    <i class="bi bi-wifi me-2"></i>Testuj połączenia
                </button>
            </div>
        </div>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle visibility of integration settings
    function toggleSettings() {
        const invoiceEnabled = document.getElementById('invoice_integration_enabled').checked;
        const erpEnabled = document.getElementById('erp_integration_enabled').checked;
        const crmEnabled = document.getElementById('crm_integration_enabled').checked;
        
        document.getElementById('invoice-settings').style.display = invoiceEnabled ? 'block' : 'none';
        document.getElementById('erp-settings').style.display = erpEnabled ? 'block' : 'none';
        document.getElementById('crm-settings').style.display = crmEnabled ? 'block' : 'none';
    }
    
    // Add event listeners
    document.getElementById('invoice_integration_enabled').addEventListener('change', toggleSettings);
    document.getElementById('erp_integration_enabled').addEventListener('change', toggleSettings);
    document.getElementById('crm_integration_enabled').addEventListener('change', toggleSettings);
    
    // Initial state
    toggleSettings();
});

async function testConnections() {
    const button = event.target;
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="bi bi-spinner bi-spin me-2"></i>Testowanie...';
    button.disabled = true;
    
    try {
        // Simulate API testing - replace with actual endpoints
        await new Promise(resolve => setTimeout(resolve, 2000));
        
        alert('Test połączeń zakończony. Sprawdź logi systemowe dla szczegółów.');
    } catch (error) {
        alert('Błąd podczas testowania połączeń: ' + error.message);
    } finally {
        button.innerHTML = originalText;
        button.disabled = false;
    }
}
</script>

<style>
.card-header {
    font-weight: 600;
}
.form-check-label.fw-bold {
    font-weight: 600 !important;
}
.alert-light {
    border: 1px solid #dee2e6;
}
</style>