<?php
// /pages/staff/settings/users-edit.php
$db = db();

$user_id = (int)($_GET['user_id'] ?? 0);
$success_message = '';
$error_message = '';

// Sprawdź czy użytkownik istnieje
if ($user_id <= 0) {
    echo '<div class="alert alert-danger">Nieprawidłowy ID użytkownika!</div>';
    return;
}

$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo '<div class="alert alert-danger">Nie znaleziono użytkownika!</div>';
    return;
}

// Obsługa zapisywania
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_user'])) {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $role = $_POST['role'] ?? 'client';
    $job_title = trim($_POST['job_title'] ?? '');
    $is_active = isset($_POST['is_active']) ? 1 : 0;
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
    if (!in_array($role, ['client', 'staff', 'admin'])) {
        $errors[] = 'Nieprawidłowa rola';
    }
    
    // Sprawdź czy email nie jest zajęty przez innego użytkownika
    if (empty($errors)) {
        $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $user_id]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = 'Email jest już zajęty przez innego użytkownika';
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
    
    // Zapisz zmiany
    if (empty($errors)) {
        try {
            if (!empty($new_password)) {
                // Z nowym hasłem
                $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $db->prepare("
                    UPDATE users 
                    SET first_name = ?, last_name = ?, email = ?, phone = ?, role = ?, job_title = ?, is_active = ?, password_hash = ?, updated_at = NOW() 
                    WHERE id = ?
                ");
                $stmt->execute([$first_name, $last_name, $email, $phone, $role, $job_title, $is_active, $password_hash, $user_id]);
            } else {
                // Bez zmiany hasła
                $stmt = $db->prepare("
                    UPDATE users 
                    SET first_name = ?, last_name = ?, email = ?, phone = ?, role = ?, job_title = ?, is_active = ?, updated_at = NOW() 
                    WHERE id = ?
                ");
                $stmt->execute([$first_name, $last_name, $email, $phone, $role, $job_title, $is_active, $user_id]);
            }
            
            $success_message = "Dane użytkownika zostały zaktualizowane!";
            
            // Odśwież dane użytkownika
            $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            $errors[] = 'Błąd podczas zapisywania: ' . $e->getMessage();
        }
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-1">Edycja użytkownika</h5>
        <p class="text-muted mb-0">Edytuj dane użytkownika #<?= $user['id'] ?></p>
    </div>
    <a href="<?= $BASE ?>/index.php?page=dashboard-staff&section=settings&settings_section=users&settings_subsection=list#pane-settings" 
       class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Powrót do listy
    </a>
</div>

<?php if ($success_message): ?>
    <div class="alert alert-success alert-dismissible auto-fade" id="successAlert">
        <i class="bi bi-check-circle"></i>
        <?= htmlspecialchars($success_message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
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
                <h6 class="mb-0"><i class="bi bi-person-circle"></i> Dane osobowe</h6>
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
                        <label class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control" 
                               value="<?= htmlspecialchars($user['email']) ?>" required>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">Telefon</label>
                        <input type="tel" name="phone" class="form-control" 
                               value="<?= htmlspecialchars($user['phone']) ?>">
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">Rola <span class="text-danger">*</span></label>
                        <select name="role" class="form-select" required>
                            <option value="client" <?= $user['role'] === 'client' ? 'selected' : '' ?>>Klient</option>
                            <option value="staff" <?= $user['role'] === 'staff' ? 'selected' : '' ?>>Pracownik</option>
                            <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Administrator</option>
                        </select>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">Stanowisko</label>
                        <input type="text" name="job_title" class="form-control" 
                               value="<?= htmlspecialchars($user['job_title']) ?>">
                    </div>
                    
                    <div class="col-12">
                        <div class="form-check">
                            <input type="checkbox" name="is_active" class="form-check-input" id="is_active" 
                                   <?= $user['is_active'] ? 'checked' : '' ?>>
                            <label class="form-check-label" for="is_active">
                                Konto aktywne
                            </label>
                        </div>
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
                            <button type="submit" name="save_user" class="btn btn-primary">
                                <i class="bi bi-check-lg"></i> Zapisz zmiany
                            </button>
                            <a href="<?= $BASE ?>/index.php?page=dashboard-staff&section=settings&settings_section=users&settings_subsection=list#pane-settings" 
                               class="btn btn-outline-secondary">
                                <i class="bi bi-x-lg"></i> Anuluj
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
                <h6 class="mb-0"><i class="bi bi-info-circle"></i> Informacje o koncie</h6>
            </div>
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <div class="avatar-circle bg-primary text-white me-3">
                        <?php 
                        $initials = '';
                        if ($user['first_name']) $initials .= strtoupper(substr($user['first_name'], 0, 1));
                        if ($user['last_name']) $initials .= strtoupper(substr($user['last_name'], 0, 1));
                        if (empty($initials)) $initials = strtoupper(substr($user['email'], 0, 1));
                        echo $initials;
                        ?>
                    </div>
                    <div>
                        <div class="fw-semibold"><?= htmlspecialchars(trim($user['first_name'] . ' ' . $user['last_name']) ?: $user['email']) ?></div>
                        <small class="text-muted">ID: #<?= $user['id'] ?></small>
                    </div>
                </div>
                
                <hr>
                
                <div class="row g-2">
                    <div class="col-6">
                        <small class="text-muted">Status:</small>
                        <div>
                            <span class="badge <?= $user['is_active'] ? 'bg-success' : 'bg-danger' ?>">
                                <?= $user['is_active'] ? 'Aktywny' : 'Nieaktywny' ?>
                            </span>
                        </div>
                    </div>
                    <div class="col-6">
                        <small class="text-muted">Rola:</small>
                        <div>
                            <span class="badge <?= $user['role'] === 'admin' ? 'bg-danger' : ($user['role'] === 'staff' ? 'bg-primary' : 'bg-info') ?>">
                                <?= ucfirst($user['role']) ?>
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