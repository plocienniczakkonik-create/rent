<?php
// Najbliższe terminy przeglądów i serwisów
require_once dirname(dirname(__DIR__)) . '/includes/db.php';
$db = db();
$today = date('Y-m-d');

// 5 najbliższych przeglądów technicznych
$inspections = $db->query("SELECT v.id, v.registration_number, v.product_id, v.inspection_date, p.name AS product_name
    FROM vehicles v
    JOIN products p ON p.id = v.product_id
    WHERE v.inspection_date IS NOT NULL AND v.inspection_date >= '$today'
    ORDER BY v.inspection_date ASC
    LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);

function days_left($date) {
    $now = new DateTime();
    $target = new DateTime($date);
    $diff = $now->diff($target);
    return $diff->invert ? 0 : $diff->days;
}

// 5 najbliższych ubezpieczeń
$insurances = $db->query("SELECT v.id, v.registration_number, v.product_id, v.insurance_expiry_date, p.name AS product_name
    FROM vehicles v
    JOIN products p ON p.id = v.product_id
    WHERE v.insurance_expiry_date IS NOT NULL AND v.insurance_expiry_date >= '$today'
    ORDER BY v.insurance_expiry_date ASC
    LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="row g-4">
    <div class="col-md-6">
        <div class="card h-100 shadow-sm">
            <div class="card-header d-flex align-items-center gap-2">
                <i class="bi bi-calendar-check text-primary fs-5"></i>
                <span class="fw-semibold">Najbliższe przeglądy techniczne</span>
            </div>
            <div class="card-body p-0">
                <table class="table mb-0 align-middle">
                    <thead>
                        <tr>
                            <th>Pojazd</th>
                            <th>Nr rej.</th>
                            <th>Data przeglądu</th>
                            <th>Dni</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($inspections as $i): ?>
                        <tr>
                            <td><?= htmlspecialchars($i['product_name']) ?></td>
                            <td><?= htmlspecialchars($i['registration_number']) ?></td>
                            <td><?= htmlspecialchars($i['inspection_date']) ?></td>
                            <td><?= days_left($i['inspection_date']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (!$inspections): ?>
                        <tr><td colspan="3" class="text-muted text-center">Brak nadchodzących przeglądów.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card h-100 shadow-sm">
            <div class="card-header d-flex align-items-center gap-2">
                <i class="bi bi-shield-check text-success fs-5"></i>
                <span class="fw-semibold">Najbliższe ubezpieczenia</span>
            </div>
            <div class="card-body p-0">
                <table class="table mb-0 align-middle">
                    <thead>
                        <tr>
                            <th>Pojazd</th>
                            <th>Nr rej.</th>
                            <th>Data ubezpieczenia</th>
                            <th>Dni</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($insurances as $i): ?>
                        <tr>
                            <td><?= htmlspecialchars($i['product_name']) ?></td>
                            <td><?= htmlspecialchars($i['registration_number']) ?></td>
                            <td><?= htmlspecialchars($i['insurance_expiry_date']) ?></td>
                            <td><?= days_left($i['insurance_expiry_date']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (!$insurances): ?>
                        <tr><td colspan="3" class="text-muted text-center">Brak nadchodzących ubezpieczeń.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
