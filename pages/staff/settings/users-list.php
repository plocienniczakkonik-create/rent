<?php
// /pages/staff/settings/users-list.php
$db = db();

// Sortowanie użytkowników
$allowed_sorts = ['id', 'first_name', 'last_name', 'email', 'role', 'is_active'];
$sort = $_GET['user_sort'] ?? 'id';
$sort = in_array($sort, $allowed_sorts) ? $sort : 'id';
$dir = strtolower($_GET['user_dir'] ?? 'asc');
$dir = in_array($dir, ['asc', 'desc']) ? $dir : 'asc';

// Pobieranie użytkowników
$sql = "SELECT id, first_name, last_name, email, role, is_active, password_hash
        FROM users 
        ORDER BY {$sort} {$dir}";
$users = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);

// Funkcja sortowania dla użytkowników
function user_sort_link(string $column, string $label): string {
    $current_sort = $_GET['user_sort'] ?? 'id';
    $current_dir = $_GET['user_dir'] ?? 'asc';
    $next_dir = ($current_sort === $column && $current_dir === 'asc') ? 'desc' : 'asc';
    
    $active_up = ($current_sort === $column && $current_dir === 'asc');
    $active_down = ($current_sort === $column && $current_dir === 'desc');
    
    $BASE = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
    $href = $BASE . '/index.php?page=dashboard-staff&section=settings&settings_section=users&settings_subsection=list&user_sort=' . $column . '&user_dir=' . $next_dir . '#pane-settings';
    
    return '<a class="th-sort-link" href="' . htmlspecialchars($href) . '">' .
           '<span class="label">' . htmlspecialchars($label) . '</span>' .
           '<span class="chevs">' .
           '<span class="chev ' . ($active_up ? 'active' : '') . '">▲</span>' .
           '<span class="chev ' . ($active_down ? 'active' : '') . '">▼</span>' .
           '</span></a>';
}

// Funkcja do badge roli
function user_role_badge(string $role): string {
    return match($role) {
        'admin' => 'bg-danger',
        'staff' => 'bg-primary',
        'client' => 'bg-info',
        default => 'bg-secondary'
    };
}
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-1">Zarządzanie użytkownikami</h5>
        <p class="text-muted mb-0">Lista wszystkich użytkowników w systemie</p>
    </div>
    <a href="<?= $BASE ?>/index.php?page=dashboard-staff&section=settings&settings_section=users&settings_subsection=add#pane-settings" 
       class="btn btn-primary">
        <i class="bi bi-person-plus"></i> Dodaj użytkownika
    </a>
</div>

<div class="table-responsive">
    <table class="table table-hover align-middle">
        <thead>
            <tr>
                <th><?= user_sort_link('id', 'ID') ?></th>
                <th><?= user_sort_link('first_name', 'Użytkownik') ?></th>
                <th><?= user_sort_link('email', 'Email') ?></th>
                <th><?= user_sort_link('role', 'Rola') ?></th>
                <th><?= user_sort_link('is_active', 'Status') ?></th>
                <th>Ostatnie logowanie</th>
                <th class="text-end">Akcje</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($users)): ?>
                <tr>
                    <td colspan="7" class="text-center py-4 text-muted">
                        <i class="bi bi-people fs-1 d-block mb-2"></i>
                        Brak użytkowników w systemie
                    </td>
                </tr>
            <?php else: foreach ($users as $user): ?>
                <?php 
                $full_name = trim($user['first_name'] . ' ' . $user['last_name']);
                $initials = '';
                if ($user['first_name']) $initials .= strtoupper(substr($user['first_name'], 0, 1));
                if ($user['last_name']) $initials .= strtoupper(substr($user['last_name'], 0, 1));
                if (empty($initials)) $initials = strtoupper(substr($user['email'], 0, 1));
                ?>
                <tr>
                    <td class="fw-bold">#<?= (int)$user['id'] ?></td>
                    <td>
                        <div class="d-flex align-items-center">
                            <div class="avatar-circle bg-primary text-white me-2">
                                <?= $initials ?>
                            </div>
                            <div>
                                <div class="fw-semibold"><?= htmlspecialchars($full_name ?: $user['email']) ?></div>
                                <?php if ($full_name): ?>
                                    <small class="text-muted"><?= htmlspecialchars($user['email']) ?></small>
                                <?php endif; ?>
                            </div>
                        </div>
                    </td>
                    <td><?= htmlspecialchars($user['email']) ?></td>
                    <td>
                        <span class="badge <?= user_role_badge($user['role']) ?>">
                            <?= ucfirst($user['role']) ?>
                        </span>
                    </td>
                    <td>
                        <span class="badge <?= $user['is_active'] ? 'bg-success' : 'bg-danger' ?>">
                            <?= $user['is_active'] ? 'Aktywny' : 'Nieaktywny' ?>
                        </span>
                    </td>
                    <td>
                        <small class="text-muted">Brak danych</small>
                    </td>
                    <td class="text-end">
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-outline-primary" title="Edytuj użytkownika">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn btn-outline-secondary" title="Historia aktywności">
                                <i class="bi bi-clock-history"></i>
                            </button>
                            <?php if ($user['is_active']): ?>
                                <button class="btn btn-outline-warning" title="Zablokuj użytkownika">
                                    <i class="bi bi-lock"></i>
                                </button>
                            <?php else: ?>
                                <button class="btn btn-outline-success" title="Odblokuj użytkownika">
                                    <i class="bi bi-unlock"></i>
                                </button>
                            <?php endif; ?>
                            <button class="btn btn-outline-danger" title="Usuń użytkownika" 
                                    onclick="return confirm('Czy na pewno chcesz usunąć tego użytkownika?')">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>

<style>
.avatar-circle {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.875rem;
    font-weight: 600;
}

.th-sort-link {
    text-decoration: none;
    color: inherit;
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
}

.th-sort-link:hover {
    color: #0d6efd;
}

.chevs {
    display: inline-flex;
    flex-direction: column;
    line-height: 0.7;
}

.chev {
    font-size: 0.65rem;
    opacity: 0.35;
}

.chev.active {
    opacity: 1;
    color: #0d6efd;
}

/* Szersze tabele dla sekcji użytkowników */
.table-responsive {
    overflow-x: auto;
    min-width: 100%;
}

.table {
    min-width: 800px;
    white-space: nowrap;
}

.table td, .table th {
    padding: 0.75rem 0.5rem;
    vertical-align: middle;
}

.avatar-circle {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    font-weight: 600;
    flex-shrink: 0;
}

.btn-group-sm > .btn {
    padding: 0.25rem 0.375rem;
    font-size: 0.875rem;
}
</style>