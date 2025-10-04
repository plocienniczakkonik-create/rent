<?php
// /pages/staff/settings/users-add.php

// Obsługa dodawania użytkownika
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $db = db();
    
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = $_POST['role'] ?? 'client';
    $status = $_POST['status'] ?? 'active';
    $send_email = isset($_POST['send_welcome_email']);
    
    $errors = [];
    
    // Walidacja
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = __('provide_valid_email', 'admin', 'Podaj prawidłowy adres email');
    }
    if (!in_array($role, ['admin', 'staff', 'client'])) {
        $errors[] = __('invalid_role', 'admin', 'Nieprawidłowa rola');
    }
    if (!in_array($status, ['active', 'inactive', 'pending'])) {
        $errors[] = __('invalid_status', 'admin', 'Nieprawidłowy status');
    }
    
    // Sprawdź czy email już istnieje
    if (empty($errors)) {
        $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = __('email_already_exists', 'admin', 'Użytkownik o takim emailu już istnieje');
        }
    }
    
    // Dodaj użytkownika
    if (empty($errors)) {
        $password = bin2hex(random_bytes(8)); // Generuj tymczasowe hasło
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $is_active = ($status === 'active') ? 1 : 0;
        
        try {
            $stmt = $db->prepare("
                INSERT INTO users (first_name, last_name, email, password_hash, role, is_active) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$first_name, $last_name, $email, $password_hash, $role, $is_active]);
            
            $success_message = __('user_created_successfully', 'admin', 'Użytkownik został utworzony pomyślnie. Tymczasowe hasło') . ": <strong>$password</strong>";
            
            if ($send_email) {
                $success_message .= "<br>" . __('welcome_email_sent', 'admin', 'Email powitalny zostanie wysłany (funkcja w budowie).');
            }
            
            // Wyczyść formularz
            $_POST = [];
            
        } catch (PDOException $e) {
            $errors[] = __('error_creating_user', 'admin', 'Błąd podczas tworzenia użytkownika') . ': ' . $e->getMessage();
        }
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-1"><?= __('add_new_user', 'admin', 'Dodaj nowego użytkownika') ?></h5>
        <p class="text-muted mb-0"><?= __('create_new_account', 'admin', 'Utwórz nowe konto w systemie') ?></p>
    </div>
    <a href="<?= $BASE ?>/index.php?page=dashboard-staff&section=settings&settings_section=users&settings_subsection=list#pane-settings" 
       class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> <?= __('back_to_list', 'admin', 'Powrót do listy') ?>
    </a>
</div>

<?php if (isset($success_message)): ?>
    <div class="alert alert-success alert-dismissible auto-fade" id="successAlert">
        <i class="bi bi-check-circle"></i>
        <?= $success_message ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <i class="bi bi-exclamation-triangle"></i>
        <strong><?= __('validation_errors', 'admin', 'Błędy walidacji') ?>:</strong>
        <ul class="mb-0 mt-2">
            <?php foreach ($errors as $error): ?>
                <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-person-plus"></i> <?= __('user_data', 'admin', 'Dane użytkownika') ?></h6>
            </div>
            <div class="card-body">
                <form method="POST" class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label"><?= __('first_name', 'admin', 'Imię') ?></label>
                        <input type="text" name="first_name" class="form-control" 
                               value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>" 
                               placeholder="Jan">
                        <div class="form-text"><?= __('optional', 'admin', 'opcjonalne') ?></div>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label"><?= __('last_name', 'admin', 'Nazwisko') ?></label>
                        <input type="text" name="last_name" class="form-control" 
                               value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>" 
                               placeholder="Kowalski">
                        <div class="form-text"><?= __('optional', 'admin', 'opcjonalne') ?></div>
                    </div>
                    
                    <div class="col-md-12">
                        <label class="form-label"><?= __('email_address', 'admin', 'Adres email') ?> <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control" 
                               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" 
                               placeholder="jan@example.com" required>
                        <div class="form-text"><?= __('email_for_communication', 'admin', 'Adres email dla komunikacji') ?></div>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label"><?= __('role', 'admin', 'Rola') ?> <span class="text-danger">*</span></label>
                        <select name="role" class="form-select" required>
                            <option value="client" <?= ($_POST['role'] ?? 'client') === 'client' ? 'selected' : '' ?>>
                                <?= __('client_basic_permissions', 'admin', 'Użytkownik - podstawowe uprawnienia') ?>
                            </option>
                            <option value="staff" <?= ($_POST['role'] ?? '') === 'staff' ? 'selected' : '' ?>>
                                <?= __('staff_admin_panel', 'admin', 'Pracownik - panel administracyjny') ?>
                            </option>
                            <option value="admin" <?= ($_POST['role'] ?? '') === 'admin' ? 'selected' : '' ?>>
                                <?= __('admin_full_permissions', 'admin', 'Administrator - pełne uprawnienia') ?>
                            </option>
                        </select>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label"><?= __('account_status', 'admin', 'Status konta') ?></label>
                        <select name="status" class="form-select">
                            <option value="active" <?= ($_POST['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>
                                <?= __('active_can_login', 'admin', 'Aktywne - może się logować') ?>
                            </option>
                            <option value="pending" <?= ($_POST['status'] ?? '') === 'pending' ? 'selected' : '' ?>>
                                <?= __('pending_activation', 'admin', 'Oczekuje - wymaga aktywacji') ?>
                            </option>
                            <option value="inactive" <?= ($_POST['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>
                                <?= __('inactive_cannot_login', 'admin', 'Nieaktywne - nie może się logować') ?>
                            </option>
                        </select>
                    </div>
                    
                    <div class="col-12">
                        <div class="form-check">
                            <input type="checkbox" name="send_welcome_email" id="send_welcome_email" 
                                   class="form-check-input" 
                                   <?= isset($_POST['send_welcome_email']) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="send_welcome_email">
                                <?= __('send_welcome_email', 'admin', 'Wyślij email powitalny z danymi do logowania') ?>
                            </label>
                            <div class="form-text"><?= __('email_with_credentials', 'admin', 'Email będzie zawierał dane do logowania i tymczasowe hasło') ?></div>
                        </div>
                    </div>
                    
                    <div class="col-12">
                        <hr>
                        <div class="d-flex gap-2">
                            <button type="submit" name="add_user" class="btn btn-primary">
                                <i class="bi bi-person-plus"></i> <?= __('create_user', 'admin', 'Utwórz użytkownika') ?>
                            </button>
                            <a href="<?= $BASE ?>/index.php?page=dashboard-staff&section=settings&settings_section=users&settings_subsection=list#pane-settings" 
                               class="btn btn-outline-secondary">
                                <?= __('cancel', 'admin', 'Anuluj') ?>
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-info-circle"></i> <?= __('information', 'admin', 'Informacje') ?></h6>
            </div>
            <div class="card-body">
                <h6><?= __('user_roles', 'admin', 'Role użytkowników') ?>:</h6>
                <ul class="list-unstyled">
                    <li><span class="badge bg-info"><?= __('user', 'admin', 'Użytkownik') ?></span> - <?= __('can_place_orders', 'admin', 'może składać zamówienia, przeglądać historię') ?></li>
                    <li><span class="badge bg-primary"><?= __('staff', 'admin', 'Pracownik') ?></span> - <?= __('access_admin_panel', 'admin', 'dostęp do panelu administracyjnego') ?></li>
                    <li><span class="badge bg-danger"><?= __('admin', 'admin', 'Administrator') ?></span> - <?= __('full_system_permissions', 'admin', 'pełne uprawnienia systemu') ?></li>
                </ul>
                
                <hr>
                
                <h6><?= __('temporary_password', 'admin', 'Hasło tymczasowe') ?>:</h6>
                <p class="small text-muted">
                    <?= __('auto_generated_password', 'admin', 'System automatycznie wygeneruje bezpieczne hasło tymczasowe. Użytkownik powinien je zmienić przy pierwszym logowaniu.') ?>
                </p>
                
                <hr>
                
                <h6><?= __('welcome_email', 'admin', 'Email powitalny') ?>:</h6>
                <p class="small text-muted">
                    <?= __('email_function_pending', 'admin', 'Funkcja wysyłania emaili zostanie zaimplementowana w konfiguracji SMTP.') ?>
                </p>
            </div>
        </div>
    </div>
</div>

<style>
.auto-fade {
    transition: opacity 0.5s ease-out;
}
</style>

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
</script>