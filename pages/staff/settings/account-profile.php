<?php
// /pages/staff/settings/account-profile.php

// Pobierz dane aktualnego użytkownika
$db = db();
$current_user_id = $_SESSION['user_id'] ?? 1; // fallback dla testów

$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$current_user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo '<div class="alert alert-danger">Nie znaleziono danych użytkownika.</div>';
    return;
}

// Obsługa aktualizacji profilu
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $job_title = trim($_POST['job_title'] ?? '');
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    $errors = [];
    
    // Walidacja
    if (empty($first_name)) {
        $errors[] = 'Imię jest wymagane';
    }
    if (empty($last_name)) {
        $errors[] = 'Nazwisko jest wymagane';
    }
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Podaj prawidłowy adres email';
    }
    
    // Sprawdź czy email nie jest zajęty przez innego użytkownika
    if (empty($errors)) {
        $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $current_user_id]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = 'Email jest już zajęty';
        }
    }
    
    // Walidacja hasła
    if (!empty($new_password)) {
        if (strlen($new_password) < 6) {
            $errors[] = 'Hasło musi mieć co najmniej 6 znaków';
        }
        if ($new_password !== $confirm_password) {
            $errors[] = 'Hasła nie są identyczne';
        }
    }
    
    // Aktualizacja danych
    if (empty($errors)) {
        try {
            if (!empty($new_password)) {
                // Z nowym hasłem
                $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $db->prepare("
                    UPDATE users 
                    SET first_name = ?, last_name = ?, email = ?, phone = ?, job_title = ?, password_hash = ?, updated_at = NOW() 
                    WHERE id = ?
                ");
                $stmt->execute([$first_name, $last_name, $email, $phone, $job_title, $password_hash, $current_user_id]);
            } else {
                // Bez zmiany hasła
                $stmt = $db->prepare("
                    UPDATE users 
                    SET first_name = ?, last_name = ?, email = ?, phone = ?, job_title = ?, updated_at = NOW() 
                    WHERE id = ?
                ");
                $stmt->execute([$first_name, $last_name, $email, $phone, $job_title, $current_user_id]);
            }
            
            $success_message = "Profil został zaktualizowany pomyślnie!";
            
            // Odśwież dane użytkownika
            $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$current_user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            $errors[] = 'Błąd podczas aktualizacji: ' . $e->getMessage();
        }
    }
}

// Helper functions for badges
function user_role_badge($role) {
    switch($role) {
        case 'admin': return 'bg-danger';
        case 'staff': return 'bg-warning';
        case 'client': return 'bg-primary';
        default: return 'bg-secondary';
    }
}

function user_status_badge($status) {
    return $status ? 'bg-success' : 'bg-danger';
}
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-1">Ustawienia konta</h5>
        <p class="text-muted mb-0">Zarządzaj swoimi danymi osobowymi i preferencjami</p>
    </div>
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
                <h6 class="mb-0"><i class="bi bi-person-circle"></i> Informacje osobiste</h6>
            </div>
            <div class="card-body">
                <form method="POST" class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Imię <span class="text-danger">*</span></label>
                        <input type="text" name="first_name" class="form-control" 
                               value="<?= htmlspecialchars($user['first_name']) ?>" required>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">Nazwisko <span class="text-danger">*</span></label>
                        <input type="text" name="last_name" class="form-control" 
                               value="<?= htmlspecialchars($user['last_name']) ?>" required>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">Adres email <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control" 
                               value="<?= htmlspecialchars($user['email']) ?>" required>
                        <div class="form-text">Adres do komunikacji i powiadomień</div>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">Telefon</label>
                        <input type="tel" name="phone" class="form-control" 
                               value="<?= htmlspecialchars($user['phone']) ?>">
                    </div>
                    
                    <div class="col-12">
                        <label class="form-label">Stanowisko</label>
                        <input type="text" name="job_title" class="form-control" 
                               value="<?= htmlspecialchars($user['job_title']) ?>">
                    </div>
                    
                    <div class="col-12">
                        <hr>
                        <h6><i class="bi bi-shield-lock"></i> Zmiana hasła</h6>
                        <p class="text-muted small">Pozostaw puste aby nie zmieniać hasła</p>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">Nowe hasło</label>
                        <input type="password" name="new_password" class="form-control" 
                               placeholder="Minimum 6 znaków">
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">Potwierdź hasło</label>
                        <input type="password" name="confirm_password" class="form-control" 
                               placeholder="Powtórz nowe hasło">
                    </div>
                    
                    <div class="col-12">
                        <hr>
                        <div class="d-flex gap-2">
                            <button type="submit" name="update_profile" class="btn btn-primary">
                                <i class="bi bi-check-lg"></i> Zapisz zmiany
                            </button>
                            <button type="reset" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-clockwise"></i> Przywróć
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-info-circle"></i> Informacje o koncie</h6>
            </div>
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <div class="avatar-circle bg-primary text-white me-3">
                        <?= strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)) ?>
                    </div>
                    <div>
                        <div class="fw-semibold"><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></div>
                        <small class="text-muted">ID: #<?= $user['id'] ?></small>
                    </div>
                </div>
                
                <hr>
                
                <div class="row g-2">
                    <div class="col-6">
                        <small class="text-muted">Rola:</small>
                        <div>
                            <span class="badge <?= user_role_badge($user['role']) ?>">
                                <?= ucfirst($user['role']) ?>
                            </span>
                        </div>
                    </div>
                    <div class="col-6">
                        <small class="text-muted">Status:</small>
                        <div>
                            <span class="badge <?= user_status_badge($user['is_active']) ?>">
                                <?= $user['is_active'] ? 'Aktywny' : 'Nieaktywny' ?>
                            </span>
                        </div>
                    </div>
                    <div class="col-6">
                        <small class="text-muted">Utworzone:</small>
                        <div><?= date('d.m.Y', strtotime($user['created_at'])) ?></div>
                    </div>
                    <div class="col-6">
                        <small class="text-muted">Ostatnia aktualizacja:</small>
                        <div>
                            <?php if ($user['updated_at']): ?>
                                <?= date('d.m.Y H:i', strtotime($user['updated_at'])) ?>
                            <?php else: ?>
                                Nigdy
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <?php if ($user['job_title']): ?>
                    <hr>
                    <div>
                        <small class="text-muted">Stanowisko:</small>
                        <div><?= htmlspecialchars($user['job_title']) ?></div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-shield-check"></i> Bezpieczeństwo</h6>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <button class="btn btn-outline-primary btn-sm" disabled>
                        <i class="bi bi-clock-history"></i> Historia aktywności
                    </button>
                    <button class="btn btn-outline-secondary btn-sm" disabled>
                        <i class="bi bi-phone"></i> Uwierzytelnianie 2FA
                    </button>
                    <button class="btn btn-outline-warning btn-sm" disabled>
                        <i class="bi bi-key"></i> Sesje aktywne
                    </button>
                </div>
                <p class="text-muted small mt-2 mb-0">
                    Funkcje bezpieczeństwa będą dostępne w kolejnych wersjach.
                </p>
            </div>
        </div>
    </div>
</div>

<style>
.avatar-circle {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    font-weight: 600;
}
</style>