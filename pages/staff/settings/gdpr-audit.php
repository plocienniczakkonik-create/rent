<?php
require_once dirname(__DIR__, 3) . '/includes/db.php';
$db = db();
$audit_logs = $db->query('SELECT ga.*, u.email FROM gdpr_audit ga LEFT JOIN users u ON ga.user_id = u.id ORDER BY ga.created_at DESC')->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="card">
    <div class="card-header" style="background: var(--gradient-primary); color: #fff;">
        <h3 class="h6 mb-0"><i class="bi bi-journal-text me-2"></i>Historia operacji RODO/GDPR (Audyt)</h3>
    </div>
    <div class="card-body">
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Użytkownik</th>
                    <th>Akcja</th>
                    <th>Szczegóły</th>
                    <th>Data operacji</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($audit_logs as $log): ?>
                    <tr>
                        <td><?= $log['id'] ?></td>
                        <td><?= htmlspecialchars($log['email']) ?></td>
                        <td><?= htmlspecialchars($log['action']) ?></td>
                        <td><?= htmlspecialchars($log['details']) ?></td>
                        <td><?= $log['created_at'] ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>