<?php
require_once dirname(__DIR__, 3) . '/includes/db.php';
$db = db();
$msg = null;
// Obsługa realizacji żądania
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['request_id'])) {
    $request_id = (int)$_POST['request_id'];
    $action = $_POST['action'];
    $row = $db->query('SELECT * FROM gdpr_requests WHERE id = ' . $request_id)->fetch(PDO::FETCH_ASSOC);
    $user_id = $row['user_id'] ?? null;
    if ($user_id) {
        if ($action === 'export') {
            // Eksport danych użytkownika
            $user = $db->query('SELECT * FROM users WHERE id = ' . $user_id)->fetch(PDO::FETCH_ASSOC);
            $reservations = $db->query('SELECT * FROM reservations WHERE user_id = ' . $user_id)->fetchAll(PDO::FETCH_ASSOC);
            $messages = $db->query('SELECT * FROM contact_messages WHERE email = ' . $db->quote($user['email']))->fetchAll(PDO::FETCH_ASSOC);
            $data = ['user' => $user, 'reservations' => $reservations, 'messages' => $messages];
            header('Content-Type: application/json');
            header('Content-Disposition: attachment; filename="gdpr_export_user_' . $user_id . '.json"');
            echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            exit;
        } elseif ($action === 'erase') {
            // Anonimizacja/usunięcie danych użytkownika
            $db->prepare('UPDATE users SET first_name = NULL, last_name = NULL, email = CONCAT("anon_", id, "@example.com"), phone = NULL, is_active = 0 WHERE id = ?')->execute([$user_id]);
            $db->prepare('UPDATE reservations SET user_id = NULL WHERE user_id = ?')->execute([$user_id]);
            $db->prepare('UPDATE contact_messages SET email = CONCAT("anon_", ? , "@example.com") WHERE email = ?')->execute([$user_id, $user['email']]);
            $db->prepare('UPDATE gdpr_requests SET status = "completed", processed_at = NOW() WHERE id = ?')->execute([$request_id]);
            $msg = '<div class="alert alert-success">Dane użytkownika zostały zanonimizowane/usunięte.</div>';
        } elseif ($action === 'delete') {
            $db->prepare('DELETE FROM gdpr_requests WHERE id = ?')->execute([$request_id]);
            $msg = '<div class="alert alert-success">Żądanie usunięte.</div>';
        }
    }
}
// Eksport wszystkich żądań do CSV
if (isset($_POST['export_csv'])) {
    $requests = $db->query('SELECT gr.*, u.email FROM gdpr_requests gr LEFT JOIN users u ON gr.user_id = u.id ORDER BY gr.requested_at DESC')->fetchAll(PDO::FETCH_ASSOC);
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=gdpr_requests.csv');
    $out = fopen('php://output', 'w');
    fputcsv($out, array_keys($requests[0] ?? ['id' => 'ID']));
    foreach ($requests as $row) fputcsv($out, $row);
    fclose($out);
    exit;
}
$requests = $db->query('SELECT gr.*, u.email FROM gdpr_requests gr LEFT JOIN users u ON gr.user_id = u.id ORDER BY gr.requested_at DESC')->fetchAll(PDO::FETCH_ASSOC);
?>
<?php if ($msg) echo $msg; ?>
<form method="post" class="mb-3">
    <button type="submit" name="export_csv" class="btn btn-outline-primary btn-sm">Eksportuj wszystkie żądania do CSV</button>
</form>
<div class="card">
    <div class="card-header" style="background: var(--gradient-primary); color: #fff;">
        <h3 class="h6 mb-0"><i class="bi bi-file-earmark-lock me-2"></i>Żądania RODO/GDPR</h3>
    </div>
    <div class="card-body">
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Użytkownik</th>
                    <th>Typ żądania</th>
                    <th>Status</th>
                    <th>Data żądania</th>
                    <th>Data realizacji</th>
                    <th>Szczegóły</th>
                    <th>Akcje</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($requests as $r): ?>
                    <tr>
                        <td><?= $r['id'] ?></td>
                        <td><?= htmlspecialchars($r['email']) ?></td>
                        <td><?= htmlspecialchars($r['request_type']) ?></td>
                        <td><?= htmlspecialchars($r['status']) ?></td>
                        <td><?= $r['requested_at'] ?></td>
                        <td><?= $r['processed_at'] ?></td>
                        <td><?= htmlspecialchars($r['details']) ?></td>
                        <td>
                            <?php if ($r['status'] === 'new'): ?>
                                <?php if ($r['request_type'] === 'export'): ?>
                                    <form method="POST" style="display:inline">
                                        <input type="hidden" name="request_id" value="<?= $r['id'] ?>">
                                        <input type="hidden" name="action" value="export">
                                        <button type="submit" class="btn btn-sm btn-gradient-primary">Eksportuj dane</button>
                                    </form>
                                <?php endif; ?>
                                <?php if ($r['request_type'] === 'erase'): ?>
                                    <form method="POST" style="display:inline">
                                        <input type="hidden" name="request_id" value="<?= $r['id'] ?>">
                                        <input type="hidden" name="action" value="erase">
                                        <button type="submit" class="btn btn-sm btn-danger">Usuń/Anonimizuj</button>
                                    </form>
                                <?php endif; ?>
                                <form method="POST" style="display:inline">
                                    <input type="hidden" name="request_id" value="<?= $r['id'] ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Usuń żądanie</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>