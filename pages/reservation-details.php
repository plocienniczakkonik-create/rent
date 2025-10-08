<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

$BASE = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    echo '<div class="container py-4"><div class="alert alert-danger">Brak identyfikatora rezerwacji.</div></div>';
    return;
}

$pdo = db();
$stmt = $pdo->prepare('SELECT * FROM reservations WHERE id = ?');
$stmt->execute([$id]);
$res = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$res) {
    echo '<div class="container py-4"><div class="alert alert-warning">Nie znaleziono rezerwacji.</div></div>';
    return;
}

$addons = [];
$q = $pdo->prepare('SELECT * FROM reservation_addons WHERE reservation_id = ? ORDER BY id ASC');
$q->execute([$id]);
$addons = $q->fetchAll(PDO::FETCH_ASSOC) ?: [];

// Oblicz sumę dodatków
$addonsTotal = array_sum(array_column($addons, 'line_total'));

$fmt = fn($n) => number_format((float)$n, 2, ',', ' ');
?>

<div class="container py-4">
    <div class="card mb-4">
        <div class="card-header text-white d-flex align-items-center" style="background: var(--gradient-primary); border: none;">
            <h2 class="mb-0 d-flex align-items-center">
                <i class="fas fa-clipboard-list me-3"></i>
                Rezerwacja #<?= (int)$res['id'] ?>
            </h2>
            <a href="<?= $BASE ?>/index.php" class="btn btn-theme btn-secondary ms-auto">Powrót</a>
        </div>
        <div class="card-body">
            <div class="row g-4">
                <div class="col-12 col-lg-7">
                    <div class="mb-4" style="border:1px solid #444c56; border-radius:0.5rem;">
                        <h5 class="mb-3 d-flex align-items-center" style="padding-top:0.75rem; padding-left:1rem;">
                            <i class="fas fa-car me-2"></i>
                            Szczegóły pojazdu
                        </h5>
                        <ul class="list-unstyled mb-0" style="padding-left:1rem; padding-bottom:0.75rem;">
                            <li><strong>Nazwa:</strong> <?= htmlspecialchars((string)$res['product_name']) ?></li>
                            <li><strong>SKU:</strong> <?= htmlspecialchars((string)$res['sku']) ?></li>
                        </ul>
                    </div>
                    <div class="mb-4" style="border:1px solid #444c56; border-radius:0.5rem;">
                        <h5 class="mb-3 d-flex align-items-center" style="padding-top:0.75rem; padding-left:1rem;">
                            <i class="fas fa-calendar-alt me-2"></i>
                            Terminy i miejsca
                        </h5>
                        <ul class="list-unstyled mb-0" style="padding-left:1rem; padding-bottom:0.75rem;">
                            <li><strong>Odbiór:</strong> <?= htmlspecialchars((string)$res['pickup_location']) ?>, <?= htmlspecialchars((string)$res['pickup_at']) ?></li>
                            <li><strong>Zwrot:</strong> <?= htmlspecialchars((string)$res['dropoff_location']) ?>, <?= htmlspecialchars((string)$res['return_at']) ?></li>
                            <li><strong>Liczba dni:</strong> <?= (int)$res['rental_days'] ?></li>
                        </ul>
                    </div>
                    <div class="mb-4" style="border:1px solid #444c56; border-radius:0.5rem;">
                        <h5 class="mb-3 d-flex align-items-center" style="padding-top:0.75rem; padding-left:1rem;">
                            <i class="fas fa-dollar-sign me-2"></i>
                            Rozliczenie
                        </h5>
                        <div class="table-responsive" style="padding-left:1rem; padding-bottom:0.75rem;">
                            <table class="table align-middle mb-0">
                                <tbody>
                                    <tr>
                                        <td>Cena bazowa za dzień</td>
                                        <td class="text-end"><?= $fmt($res['price_per_day_base']) ?> PLN</td>
                                    </tr>
                                    <?php if ((int)$res['promo_applied'] === 1 && (float)$res['price_per_day_final'] < (float)$res['price_per_day_base']): ?>
                                        <tr>
                                            <td>Cena promocyjna za dzień <?= $res['promo_label'] ? '(' . htmlspecialchars((string)$res['promo_label']) . ')' : '' ?></td>
                                            <td class="text-end text-danger"><?= $fmt($res['price_per_day_final']) ?> PLN</td>
                                        </tr>
                                    <?php endif; ?>
                                    <tr>
                                        <td>Ilość dni</td>
                                        <td class="text-end"><?= (int)$res['rental_days'] ?></td>
                                    </tr>
                                    <?php if ($addons): ?>
                                        <tr>
                                            <td colspan="2"><strong>Dodatkowe usługi</strong></td>
                                        </tr>
                                        <?php foreach ($addons as $a): ?>
                                            <tr>
                                                <td>
                                                    <?= htmlspecialchars((string)$a['name']) ?>
                                                    <span class="text-muted small">(<?= htmlspecialchars((string)$a['charge_type']) ?>)</span>
                                                    <?php if ((int)$a['quantity'] > 1): ?>
                                                        × <?= (int)$a['quantity'] ?>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-end">
                                                    <?= $fmt($a['line_total']) ?> PLN
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                        <tr class="border-top">
                                            <td colspan="2" class="pt-3"></td>
                                        </tr>
                                    <?php endif; ?>
                                    <tr>
                                        <td><strong>Łącznie za wynajem</strong></td>
                                        <td class="text-end">
                                            <strong><?= $fmt($res['price_per_day_final']) ?> × <?= (int)$res['rental_days'] ?> = <?= $fmt($res['price_per_day_final'] * $res['rental_days']) ?> PLN</strong>
                                        </td>
                                    </tr>
                                    <?php if ($addons): ?>
                                        <tr>
                                            <td><strong>Suma dodatkowych usług</strong></td>
                                            <td class="text-end"><strong><?= $fmt($addonsTotal) ?> PLN</strong></td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th>Suma</th>
                                        <th class="text-end"><?= $fmt($res['total_gross']) ?> PLN</th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-lg-5">
                    <div class="mb-4" style="border:1px solid #444c56; border-radius:0.5rem;">
                        <h5 class="mb-3 d-flex align-items-center" style="padding-top:0.75rem; padding-left:1rem;">
                            <i class="fas fa-user me-2"></i>
                            Dane klienta
                        </h5>
                        <ul class="list-unstyled mb-0" style="padding-left:1rem; padding-bottom:0.75rem;">
                            <li><strong>Imię i nazwisko:</strong> <?= htmlspecialchars((string)$res['customer_name']) ?></li>
                            <li><strong>E-mail:</strong> <?= htmlspecialchars((string)$res['customer_email']) ?></li>
                            <li><strong>Telefon:</strong> <?= htmlspecialchars((string)$res['customer_phone']) ?></li>
                            <li><strong>Płatność:</strong> <?= htmlspecialchars((string)$res['payment_method']) ?></li>
                            <li><strong>Status:</strong> <span class="badge bg-secondary"><?= htmlspecialchars((string)$res['status']) ?></span></li>
                            <li><strong>Utworzono:</strong> <?= htmlspecialchars((string)$res['created_at']) ?></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>