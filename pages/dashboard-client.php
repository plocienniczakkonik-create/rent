<?php
// /pages/dashboard-client.php
require_once dirname(__DIR__) . '/auth/auth.php';
$currentUser = require_auth();

require_once dirname(__DIR__) . '/partials/head.php';
require_once dirname(__DIR__) . '/partials/header.php';

// === SELECT: zamówienia ===
$stmt = db()->prepare("
  SELECT number, status, total_gross, DATE(placed_at) AS date
  FROM orders
  WHERE user_id = ?
  ORDER BY placed_at DESC
  LIMIT 50
");
$stmt->execute([$currentUser['id']]);
$orders = $stmt->fetchAll();

// === SELECT: adresy ===
$stmt = db()->prepare("
  SELECT id, type, full_name, street, city, postal_code, country, is_default
  FROM addresses
  WHERE user_id = ?
  ORDER BY is_default DESC, id DESC
");
$stmt->execute([$currentUser['id']]);
$addresses = $stmt->fetchAll();

// === SELECT: płatności ===
$stmt = db()->prepare("
  SELECT id, provider, label, mask, is_default, DATE(created_at) AS added
  FROM payment_methods
  WHERE user_id = ?
  ORDER BY is_default DESC, id DESC
");
$stmt->execute([$currentUser['id']]);
$payments = $stmt->fetchAll();

// pomocniczo: mapowanie statusów na klasy bootstrap
function order_badge_class(string $status): string
{
    return match ($status) {
        'paid'       => 'text-bg-success',
        'processing' => 'text-bg-info',
        'completed'  => 'text-bg-primary',
        'cancelled'  => 'text-bg-dark',
        'refunded'   => 'text-bg-warning',
        default      => 'text-bg-secondary',
    };
}
?>

<!--
  Centrowanie:
  - d-flex + align-items-center + justify-content-center na <main>
  - min-height: 100vh - navbar (tu przyjęte 72px; zmień jeśli Twój nav ma inną wysokości)
  - padding-top: tyle co nav, żeby nic nie nachodziło
  - wewnątrz wrapper z max-width dla ładnej szpalty
-->
<main class="container-lg py-4 d-flex align-items-center justify-content-center"
    style="min-height: calc(100vh - 72px); padding-top: 72px;"> <!-- <- dostosuj 72px do wysokości nav -->
    <div class="w-100" style="max-width:1100px; margin:0 auto;">
        <!-- Nagłówek -->
        <h1 class="h4 mb-1">Witaj, <?= htmlspecialchars($currentUser['first_name'] ?? $currentUser['email']) ?>!</h1>
        <p class="text-muted mb-3">To jest Twój panel klienta.</p>

        <!-- Kafelki skrótów -->
        <div class="row g-3 mb-3">
            <div class="col-12 col-md-6 col-lg-3">
                <div class="card h-100">
                    <div class="card-body">
                        <h2 class="h6 mb-1">Moje zamówienia</h2>
                        <p class="small text-muted mb-3">Historia i statusy.</p>
                        <a href="#orders" class="btn btn-primary btn-sm">Zobacz</a>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-6 col-lg-3">
                <div class="card h-100">
                    <div class="card-body">
                        <h2 class="h6 mb-1">Adresy</h2>
                        <p class="small text-muted mb-3">Rozliczeniowe i wysyłkowe.</p>
                        <a href="#addresses" class="btn btn-primary btn-sm">Edytuj</a>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-6 col-lg-3">
                <div class="card h-100">
                    <div class="card-body">
                        <h2 class="h6 mb-1">Formy płatności</h2>
                        <p class="small text-muted mb-3">Karty, BLIK, PayPal.</p>
                        <a href="#payments" class="btn btn-primary btn-sm">Zarządzaj</a>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-6 col-lg-3">
                <div class="card h-100">
                    <div class="card-body">
                        <h2 class="h6 mb-1">Ustawienia</h2>
                        <p class="small text-muted mb-3">Dane konta i hasło.</p>
                        <a href="#settings" class="btn btn-primary btn-sm" aria-disabled="true">Wkrótce</a>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-6 col-lg-3">
                <div class="card h-100">
                    <div class="card-body">
                        <h2 class="h6 mb-1">Moje zgody RODO/GDPR</h2>
                        <p class="small text-muted mb-3">Zarządzaj zgodami i żądaniami.</p>
                        <a href="#gdpr" class="btn btn-primary btn-sm">Zarządzaj</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sekcja: Zamówienia -->
        <div class="card mb-3" id="orders">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h3 class="h6 mb-0">Moje zamówienia</h3>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="small text-muted">
                            <tr>
                                <th>Nr</th>
                                <th>Status</th>
                                <th>Kwota</th>
                                <th>Data</th>
                                <th class="text-end">Akcje</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!$orders): ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">Brak zamówień.</td>
                                </tr>
                                <?php else: foreach ($orders as $o): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($o['number']) ?></td>
                                        <td><span class="badge <?= order_badge_class((string)$o['status']) ?>"><?= htmlspecialchars($o['status']) ?></span></td>
                                        <td><?= number_format((float)$o['total_gross'], 2, ',', ' ') ?> PLN</td>
                                        <td><?= htmlspecialchars($o['date']) ?></td>
                                        <td class="text-end">
                                            <a href="index.php?page=order&n=<?= urlencode($o['number']) ?>" class="btn btn-outline-primary btn-sm">Szczegóły</a>
                                        </td>
                                    </tr>
                            <?php endforeach;
                            endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Sekcja: Adresy -->
        <div class="card mb-3" id="addresses">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h3 class="h6 mb-0">Adresy</h3>
                <a class="btn btn-sm btn-primary" href="pages/address-form.php">Dodaj adres</a>
            </div>
            <div class="card-body">
                <?php if (!$addresses): ?>
                    <p class="text-muted mb-0">Nie dodałeś jeszcze żadnych adresów.</p>
                <?php else: ?>
                    <div class="row g-3">
                        <?php foreach ($addresses as $a): ?>
                            <div class="col-12 col-md-6">
                                <div class="border rounded p-3 h-100">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <span class="badge <?= ($a['type'] === 'billing' ? 'text-bg-secondary' : 'text-bg-light') ?>">
                                            <?= $a['type'] === 'billing' ? 'Rozliczeniowy' : 'Wysyłkowy' ?>
                                        </span>
                                        <?php if ((int)$a['is_default'] === 1): ?>
                                            <span class="badge text-bg-success">Domyślny</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="small">
                                        <div><?= htmlspecialchars($a['full_name'] ?: ($currentUser['first_name'] . ' ' . $currentUser['last_name'])) ?></div>
                                        <div><?= htmlspecialchars($a['street']) ?></div>
                                        <div><?= htmlspecialchars($a['postal_code']) ?> <?= htmlspecialchars($a['city']) ?></div>
                                        <div><?= htmlspecialchars($a['country']) ?></div>
                                    </div>
                                    <div class="d-flex gap-2 mt-3">
                                        <a class="btn btn-outline-primary btn-sm" href="pages/address-form.php?id=<?= (int)$a['id'] ?>">Edytuj</a>
                                        <a class="btn btn-outline-danger btn-sm" href="pages/address-delete.php?id=<?= (int)$a['id'] ?>">Usuń</a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Sekcja: Płatności -->
        <div class="card mb-3" id="payments">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h3 class="h6 mb-0">Zapisane formy płatności</h3>
                <a class="btn btn-sm btn-primary" href="pages/payment-form.php">Dodaj metodę</a>
            </div>
            <div class="card-body">
                <?php if (!$payments): ?>
                    <p class="text-muted mb-0">Brak zapisanych metod płatności.</p>
                <?php else: ?>
                    <div class="row g-3">
                        <?php foreach ($payments as $p): ?>
                            <div class="col-12 col-md-6">
                                <div class="border rounded p-3 h-100 d-flex flex-column">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <strong class="text-capitalize small"><?= htmlspecialchars($p['provider']) ?></strong>
                                        <?php if ((int)$p['is_default'] === 1): ?>
                                            <span class="badge text-bg-success">Domyślna</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="small text-muted mb-2">
                                        <?= htmlspecialchars($p['label'] ?: '—') ?> <?= htmlspecialchars($p['mask'] ? '(' . $p['mask'] . ')' : '') ?>
                                    </div>
                                    <div class="mt-auto d-flex gap-2">
                                        <a class="btn btn-outline-primary btn-sm" href="pages/payment-form.php?id=<?= (int)$p['id'] ?>">Edytuj</a>
                                        <a class="btn btn-outline-danger btn-sm" href="pages/payment-delete.php?id=<?= (int)$p['id'] ?>">Usuń</a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Sekcja: Zgody RODO/GDPR -->
        <div class="card mb-3" id="gdpr">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h3 class="h6 mb-0">Moje zgody RODO/GDPR</h3>
            </div>
            <div class="card-body">
                <?php include __DIR__ . '/user-consents.php'; ?>
            </div>
        </div>

        <!-- Sekcja: Żądania RODO/GDPR -->
        <div class="card mb-3" id="gdpr-requests">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h3 class="h6 mb-0">Moje żądania RODO/GDPR</h3>
            </div>
            <div class="card-body">
                <?php include __DIR__ . '/user-gdpr-requests.php'; ?>
            </div>
        </div>

        <!-- Placeholder: Ustawienia -->
        <div class="card mb-3" id="settings">
            <div class="card-header">
                <h3 class="h6 mb-0">Ustawienia konta</h3>
            </div>
            <div class="card-body">
                <p class="text-muted mb-0 small">Sekcja w przygotowaniu (edycja profilu, zmiana hasła).</p>
            </div>
        </div>

    </div><!-- /.content wrapper -->
</main>

<?php require_once dirname(__DIR__) . '/partials/footer.php'; ?>