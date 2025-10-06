<?php
// Formularz żądań RODO/GDPR dla użytkownika
require_once dirname(__DIR__) . '/includes/db.php';
$db = db();
$user_id = $_SESSION['user_id'] ?? 1; // demo/test

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['request_type'] ?? '';
    $details = $_POST['details'] ?? '';
    if (in_array($type, ['access', 'erase', 'rectify', 'export'])) {
        $db->prepare('INSERT INTO gdpr_requests (user_id, request_type, status, details, requested_at) VALUES (?, ?, "new", ?, NOW())')
            ->execute([$user_id, $type, $details]);
        echo '<div class="alert alert-success">Żądanie zostało zarejestrowane!</div>';
    }
}
// Pobierz historię żądań
$requests = $db->query('SELECT * FROM gdpr_requests WHERE user_id = ' . (int)$user_id . ' ORDER BY requested_at DESC')->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="card mt-4">
    <div class="card-header" style="background: var(--gradient-primary); color: #fff;">
        <h3 class="h6 mb-0"><i class="bi bi-file-earmark-lock me-2"></i>Moje żądania RODO/GDPR</h3>
    </div>
    <div class="card-body">
        <form method="POST" class="mb-4">
            <div class="mb-3">
                <label for="request_type" class="form-label">Typ żądania</label>
                <select name="request_type" id="request_type" class="form-select" required>
                    <option value="">Wybierz...</option>
                    <option value="access">Dostęp do danych</option>
                    <option value="erase">Usunięcie danych</option>
                    <option value="rectify">Korekta danych</option>
                    <option value="export">Eksport danych</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="details" class="form-label">Szczegóły żądania (opcjonalnie)</label>
                <textarea name="details" id="details" class="form-control" rows="2"></textarea>
            </div>
            <button type="submit" class="btn btn-gradient-primary" style="background: var(--gradient-primary); color: #fff; border-radius: 8px; border: none;">Wyślij żądanie</button>
        </form>
        <h5 class="mb-3">Historia żądań</h5>
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>Typ</th>
                    <th>Status</th>
                    <th>Data żądania</th>
                    <th>Data realizacji</th>
                    <th>Szczegóły</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($requests as $r): ?>
                    <tr>
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