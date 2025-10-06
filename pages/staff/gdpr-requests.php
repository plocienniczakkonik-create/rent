<?php
// Panel admina: lista żądań RODO/GDPR
require_once dirname(__DIR__, 3) . '/includes/db.php';
$db = db();
$requests = $db->query('SELECT gr.*, u.email FROM gdpr_requests gr LEFT JOIN users u ON gr.user_id = u.id ORDER BY gr.requested_at DESC')->fetchAll(PDO::FETCH_ASSOC);
?>
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
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>