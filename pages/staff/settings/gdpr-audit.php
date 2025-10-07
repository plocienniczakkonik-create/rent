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
                        <td><?php
                            $actionMap = [
                                'erase' => 'Anonimizacja',
                                'export' => 'Eksport danych',
                                'access' => 'Dostęp do danych',
                                'rectify' => 'Sprostowanie',
                            ];
                            echo $actionMap[$log['action']] ?? htmlspecialchars(ucfirst(str_replace('_', ' ', $log['action'])));
                            ?></td>
                        <td><?php
                            $details = $log['details'];
                            if (mb_detect_encoding($details, 'UTF-8', true) === false) {
                                $details = mb_convert_encoding($details, 'UTF-8', 'ISO-8859-2,Windows-1250,ASCII');
                            }
                            echo htmlspecialchars($details, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                            ?></td>
                        <td><?= $log['created_at'] ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>