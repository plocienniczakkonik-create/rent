<?php
// Najbliższe terminy przeglądów i serwisów
require_once dirname(__DIR__, 2) . '/includes/db.php';
$db = db();
$today = date('Y-m-d');

// Sortowanie przeglądów
$inspectionOrder = '';
if ($section === 'upcoming' && !empty($sort) && strpos($sort, 'insp_') === 0) {
    $inspectionOrder = match ($sort) {
        'insp_id' => "ORDER BY v.id $dir",
        'insp_vehicle' => "ORDER BY p.name $dir, v.registration_number $dir",
        'insp_registration' => "ORDER BY v.registration_number $dir",
        'insp_date' => "ORDER BY v.inspection_date $dir",
        default => "ORDER BY v.inspection_date ASC"
    };
} else {
    // Domyślnie sortuj według najbliższych dat (najszybciej kończące się)
    $inspectionOrder = "ORDER BY v.inspection_date ASC";
}

// 10 najbliższych przeglądów technicznych
$inspections = $db->query("SELECT v.id, v.registration_number, v.product_id, v.inspection_date, p.name AS product_name
    FROM vehicles v
    JOIN products p ON p.id = v.product_id
    WHERE v.inspection_date IS NOT NULL AND v.inspection_date >= '$today'
    $inspectionOrder
    LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);

function days_left($date)
{
    $now = new DateTime();
    $target = new DateTime($date);
    $diff = $now->diff($target);
    return $diff->invert ? 0 : $diff->days;
}

// Sortowanie ubezpieczeń  
$insuranceOrder = '';
if ($section === 'upcoming' && !empty($sort) && strpos($sort, 'ins_') === 0) {
    $insuranceOrder = match ($sort) {
        'ins_id' => "ORDER BY v.id $dir",
        'ins_vehicle' => "ORDER BY p.name $dir, v.registration_number $dir",
        'ins_registration' => "ORDER BY v.registration_number $dir",
        'ins_date' => "ORDER BY v.insurance_expiry_date $dir",
        default => "ORDER BY v.insurance_expiry_date ASC"
    };
} else {
    // Domyślnie sortuj według najbliższych dat (najszybciej kończące się)
    $insuranceOrder = "ORDER BY v.insurance_expiry_date ASC";
}

// 10 najbliższych ubezpieczeń
$insurances = $db->query("SELECT v.id, v.registration_number, v.product_id, v.insurance_expiry_date, p.name AS product_name
    FROM vehicles v
    JOIN products p ON p.id = v.product_id
    WHERE v.insurance_expiry_date IS NOT NULL AND v.insurance_expiry_date >= '$today'
    $insuranceOrder
    LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="card section-upcoming">
    <div class="card-header d-flex align-items-center justify-content-between" style="background: var(--gradient-primary); color: white; border-bottom: 1px solid var(--color-primary-dark);">
        <h2 class="h6 mb-0"><i class="bi bi-calendar-event me-2"></i><?= __('upcoming_dates', 'admin', 'Najbliższe terminy') ?></h2>
    </div>
    <div class="card-body">
        <div class="row g-4">
            <div class="col-md-6">
                <div class="card h-100 shadow-sm">
                    <div class="card-header d-flex align-items-center gap-2">
                        <i class="bi bi-calendar-check text-primary fs-5"></i>
                        <span class="fw-semibold">10 <?= __('nearest_inspections', 'admin', 'najbliższych przeglądów technicznych') ?></span>
                    </div>
                    <div class="card-body p-0">
                        <table class="table mb-0 align-middle">
                            <thead>
                                <tr>
                                    <th><?= sort_link_dashboard('upcoming', 'insp_id', 'ID') ?></th>
                                    <th><?= sort_link_dashboard('upcoming', 'insp_vehicle', __('vehicle', 'admin', 'Pojazd')) ?></th>
                                    <th><?= sort_link_dashboard('upcoming', 'insp_registration', __('registration_number', 'admin', 'Nr rej.')) ?></th>
                                    <th><?= sort_link_dashboard('upcoming', 'insp_date', __('inspection_date', 'admin', 'Data przeglądu')) ?></th>
                                    <th><?= __('days', 'admin', 'Dni') ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($inspections as $i): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($i['id']) ?></td>
                                        <td><?= htmlspecialchars($i['product_name']) ?></td>
                                        <td><?= htmlspecialchars($i['registration_number']) ?></td>
                                        <td><?= htmlspecialchars($i['inspection_date']) ?></td>
                                        <td><?= days_left($i['inspection_date']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (!$inspections): ?>
                                    <tr>
                                        <td colspan="5" class="text-muted text-center"><?= __('no_upcoming_inspections', 'admin', 'Brak nadchodzących przeglądów.') ?></td>
                                    </tr>
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
                        <span class="fw-semibold">10 <?= __('nearest_insurances', 'admin', 'najbliższych ubezpieczeń') ?></span>
                    </div>
                    <div class="card-body p-0">
                        <table class="table mb-0 align-middle">
                            <thead>
                                <tr>
                                    <th><?= sort_link_dashboard('upcoming', 'ins_id', 'ID') ?></th>
                                    <th><?= sort_link_dashboard('upcoming', 'ins_vehicle', __('vehicle', 'admin', 'Pojazd')) ?></th>
                                    <th><?= sort_link_dashboard('upcoming', 'ins_registration', __('registration_number', 'admin', 'Nr rej.')) ?></th>
                                    <th><?= sort_link_dashboard('upcoming', 'ins_date', __('insurance_date', 'admin', 'Data ubezpieczenia')) ?></th>
                                    <th><?= __('days', 'admin', 'Dni') ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($insurances as $i): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($i['id']) ?></td>
                                        <td><?= htmlspecialchars($i['product_name']) ?></td>
                                        <td><?= htmlspecialchars($i['registration_number']) ?></td>
                                        <td><?= htmlspecialchars($i['insurance_expiry_date']) ?></td>
                                        <td><?= days_left($i['insurance_expiry_date']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (!$insurances): ?>
                                    <tr>
                                        <td colspan="5" class="text-muted text-center"><?= __('no_upcoming_insurances', 'admin', 'Brak nadchodzących ubezpieczeń.') ?></td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div> <!-- zamyka row g-4 -->
    </div> <!-- zamyka card-body -->
</div> <!-- zamyka card section-upcoming -->