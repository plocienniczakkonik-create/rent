<?php
// /pages/staff/settings/users-list.php
require_once dirname(dirname(dirname(__DIR__))) . '/includes/i18n.php';

// Initialize i18n if not already done
if (!class_exists('i18n') || !method_exists('i18n', 'getAdminLanguage')) {
    i18n::init();
}

$db = db();

// Obsługa akcji POST
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $user_id = (int)($_POST['user_id'] ?? 0);

    if ($user_id > 0) {
        try {
            switch ($action) {
                case 'toggle_status':
                    $stmt = $db->prepare("UPDATE users SET is_active = NOT is_active WHERE id = ?");
                    $stmt->execute([$user_id]);
                    $success_message = __('user_status_changed', 'admin', 'Status użytkownika został zmieniony!');
                    break;

                case 'delete_user':
                    // Sprawdź czy to nie aktualny użytkownik
                    $current_user_id = $_SESSION['user_id'] ?? 0;
                    if ($user_id == $current_user_id) {
                        $error_message = __('cannot_delete_own_account', 'admin', 'Nie możesz usunąć swojego własnego konta!');
                        break;
                    }

                    $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
                    $stmt->execute([$user_id]);
                    $success_message = __('user_deleted_successfully', 'admin', 'Użytkownik został usunięty!');
                    break;

                default:
                    $error_message = __('unknown_action', 'admin', 'Nieznana akcja!');
            }
        } catch (PDOException $e) {
            $error_message = __('error', 'admin', 'Błąd') . ': ' . $e->getMessage();
        }
    } else {
        $error_message = __('invalid_user_id', 'admin', 'Nieprawidłowy ID użytkownika!');
    }
}

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
function user_sort_link(string $column, string $label): string
{
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
function user_role_badge(string $role): string
{
    return match ($role) {
        'admin' => 'bg-danger',
        'staff' => 'bg-primary',
        'client' => 'bg-info',
        default => 'bg-secondary'
    };
}
?>

<?php if ($success_message): ?>
    <div class="alert alert-success alert-dismissible auto-fade" id="successAlert">
        <i class="bi bi-check-circle"></i>
        <?= htmlspecialchars($success_message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($error_message): ?>
    <div class="alert alert-danger">
        <i class="bi bi-exclamation-triangle"></i>
        <?= htmlspecialchars($error_message) ?>
    </div>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-1"><?= __('user_management', 'admin', 'Zarządzanie użytkownikami') ?></h5>
        <p class="text-muted mb-0"><?= __('all_users_list', 'admin', 'Lista wszystkich użytkowników w systemie') ?></p>
    </div>
    <a href="<?= $BASE ?>/index.php?page=dashboard-staff&section=settings&settings_section=users&settings_subsection=add#pane-settings"
        class="btn btn-primary">
        <i class="bi bi-person-plus"></i> <?= __('add_user', 'admin', 'Dodaj użytkownika') ?>
    </a>
</div>

<div class="table-responsive">
    <table class="table table-hover align-middle">
        <thead>
            <tr>
                <th><?= user_sort_link('id', 'ID') ?></th>
                <th><?= user_sort_link('first_name', __('user', 'admin', 'Użytkownik')) ?></th>
                <th><?= user_sort_link('email', 'Email') ?></th>
                <th><?= user_sort_link('role', __('role', 'admin', 'Rola')) ?></th>
                <th><?= user_sort_link('is_active', __('status', 'admin', 'Status')) ?></th>
                <th><?= __('last_login', 'admin', 'Ostatnie logowanie') ?></th>
                <th class="text-end"><?= __('actions', 'admin', 'Akcje') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($users)): ?>
                <tr>
                    <td colspan="7" class="text-center py-4 text-muted">
                        <i class="bi bi-people fs-1 d-block mb-2"></i>
                        <?= __('no_users_in_system', 'admin', 'Brak użytkowników w systemie') ?>
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
                                <?= $user['is_active'] ? __('active', 'admin', 'Aktywny') : __('inactive', 'admin', 'Nieaktywny') ?>
                            </span>
                        </td>
                        <td>
                            <small class="text-muted"><?= __('no_data', 'admin', 'Brak danych') ?></small>
                        </td>
                        <td class="text-end">
                            <div class="btn-group btn-group-sm">
                                <a href="<?= $BASE ?>/index.php?page=dashboard-staff&section=settings&settings_section=users&settings_subsection=edit&user_id=<?= $user['id'] ?>#pane-settings"
                                    class="btn btn-outline-primary" title="<?= __('edit_user', 'admin', 'Edytuj użytkownika') ?>">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <button class="btn btn-outline-secondary" title="<?= __('activity_history', 'admin', 'Historia aktywności (wkrótce)') ?>" disabled>
                                    <i class="bi bi-clock-history"></i>
                                </button>
                            </div>
                            <div class="btn-group btn-group-sm ms-1">
                                <form method="POST" style="display: inline;" onsubmit="return confirm('<?= __('confirm_change_status', 'admin', 'Czy na pewno chcesz zmienić status tego użytkownika?') ?>')">
                                    <input type="hidden" name="action" value="toggle_status">
                                    <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                    <?php if ($user['is_active']): ?>
                                        <button type="submit" class="btn btn-outline-warning" title="<?= __('block_user', 'admin', 'Zablokuj użytkownika') ?>">
                                            <i class="bi bi-lock"></i>
                                        </button>
                                    <?php else: ?>
                                        <button type="submit" class="btn btn-outline-success" title="<?= __('unblock_user', 'admin', 'Odblokuj użytkownika') ?>">
                                            <i class="bi bi-unlock"></i>
                                        </button>
                                    <?php endif; ?>
                                </form>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('<?= __('confirm_delete_user', 'admin', 'Czy na pewno chcesz usunąć tego użytkownika? Ta operacja jest nieodwracalna!') ?>')">
                                    <input type="hidden" name="action" value="delete_user">
                                    <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                    <button type="submit" class="btn btn-outline-danger" title="<?= __('delete_user', 'admin', 'Usuń użytkownika') ?>">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
            <?php endforeach;
            endif; ?>
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

    .table td,
    .table th {
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

    .btn-group-sm>.btn {
        padding: 0.25rem 0.375rem;
        font-size: 0.875rem;
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