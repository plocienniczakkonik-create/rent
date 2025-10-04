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
        $errors[] = 'Podaj prawidłowy adres email';
    }
    if (!in_array($role, ['admin', 'staff', 'client'])) {
        $errors[] = 'Nieprawidłowa rola';
    }
    if (!in_array($status, ['active', 'inactive', 'pending'])) {
        $errors[] = 'Nieprawidłowy status';
    }
    
    // Sprawdź czy email już istnieje
    if (empty($errors)) {
        $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = 'Użytkownik o takim emailu już istnieje';
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
            
            $success_message = "Użytkownik został utworzony pomyślnie. Tymczasowe hasło: <strong>$password</strong>";
            
            if ($send_email) {
                $success_message .= "<br>Email powitalny zostanie wysłany (funkcja w budowie).";
            }
            
            // Wyczyść formularz
            $_POST = [];
            
        } catch (PDOException $e) {
            $errors[] = 'Błąd podczas tworzenia użytkownika: ' . $e->getMessage();
        }
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-1">Dodaj nowego użytkownika</h5>
        <p class="text-muted mb-0">Utwórz nowe konto w systemie</p>
    </div>
    <a href="<?= $BASE ?>/index.php?page=dashboard-staff&section=settings&settings_section=users&settings_subsection=list#pane-settings" 
       class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Powrót do listy
    </a>
</div>

<?php if (isset($success_message)): ?>
    <div class="alert alert-success">
        <i class="bi bi-check-circle"></i>
        <?= $success_message ?>
    </div>
<?php endif; ?>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <i class="bi bi-exclamation-triangle"></i>
        <strong>Błędy walidacji:</strong>
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
                <h6 class="mb-0"><i class="bi bi-person-plus"></i> Dane użytkownika</h6>
            </div>
            <div class="card-body">
                <form method="POST" class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Nazwa użytkownika <span class="text-danger">*</span></label>
                        <input type="text" name="username" class="form-control" 
                               value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" 
                               placeholder="np. jankowalski" required>
                        <div class="form-text">Unikalna nazwa do logowania</div>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">Adres email <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control" 
                               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" 
                               placeholder="jan@example.com" required>
                        <div class="form-text">Adres email dla komunikacji</div>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">Rola <span class="text-danger">*</span></label>
                        <select name="role" class="form-select" required>
                            <option value="client" <?= ($_POST['role'] ?? 'client') === 'client' ? 'selected' : '' ?>>
                                Użytkownik - podstawowe uprawnienia
                            </option>
                            <option value="staff" <?= ($_POST['role'] ?? '') === 'staff' ? 'selected' : '' ?>>
                                Pracownik - panel administracyjny
                            </option>
                            <option value="admin" <?= ($_POST['role'] ?? '') === 'admin' ? 'selected' : '' ?>>
                                Administrator - pełne uprawnienia
                            </option>
                        </select>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">Status konta</label>
                        <select name="status" class="form-select">
                            <option value="active" <?= ($_POST['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>
                                Aktywne - może się logować
                            </option>
                            <option value="pending" <?= ($_POST['status'] ?? '') === 'pending' ? 'selected' : '' ?>>
                                Oczekuje - wymaga aktywacji
                            </option>
                            <option value="inactive" <?= ($_POST['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>
                                Nieaktywne - nie może się logować
                            </option>
                        </select>
                    </div>
                    
                    <div class="col-12">
                        <div class="form-check">
                            <input type="checkbox" name="send_welcome_email" id="send_welcome_email" 
                                   class="form-check-input" 
                                   <?= isset($_POST['send_welcome_email']) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="send_welcome_email">
                                Wyślij email powitalny z danymi do logowania
                            </label>
                            <div class="form-text">Email będzie zawierał nazwę użytkownika i tymczasowe hasło</div>
                        </div>
                    </div>
                    
                    <div class="col-12">
                        <hr>
                        <div class="d-flex gap-2">
                            <button type="submit" name="add_user" class="btn btn-primary">
                                <i class="bi bi-person-plus"></i> Utwórz użytkownika
                            </button>
                            <a href="<?= $BASE ?>/index.php?page=dashboard-staff&section=settings&settings_section=users&settings_subsection=list#pane-settings" 
                               class="btn btn-outline-secondary">
                                Anuluj
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
                <h6 class="mb-0"><i class="bi bi-info-circle"></i> Informacje</h6>
            </div>
            <div class="card-body">
                <h6>Role użytkowników:</h6>
                <ul class="list-unstyled">
                    <li><span class="badge bg-info">Użytkownik</span> - może składać zamówienia, przeglądać historię</li>
                    <li><span class="badge bg-primary">Pracownik</span> - dostęp do panelu administracyjnego</li>
                    <li><span class="badge bg-danger">Administrator</span> - pełne uprawnienia systemu</li>
                </ul>
                
                <hr>
                
                <h6>Hasło tymczasowe:</h6>
                <p class="small text-muted">
                    System automatycznie wygeneruje bezpieczne hasło tymczasowe. 
                    Użytkownik powinien je zmienić przy pierwszym logowaniu.
                </p>
                
                <hr>
                
                <h6>Email powitalny:</h6>
                <p class="small text-muted">
                    Funkcja wysyłania emaili zostanie zaimplementowana w konfiguracji SMTP.
                </p>
            </div>
        </div>
    </div>
</div>