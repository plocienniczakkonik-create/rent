<?php
// /pages/staff/section-dicts.php
require_once dirname(__DIR__, 2) . '/includes/db.php';
require_once dirname(__DIR__, 2) . '/includes/config.php';
// NOTE: _helpers.php already included in parent dashboard-staff.php
// require_staff() already called in parent dashboard-staff.php

$BASE = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';

function panel_url(array $params = []): string
{
    $base = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
    $q = http_build_query(array_merge(['page' => 'dashboard-staff', 'tab' => 'dicts'], $params));
    return $base . '/index.php?' . $q . '#pane-dicts';
}

$validKinds = [
    'location'  => __('locations', 'admin', 'Lokalizacje'),
    'car_class' => __('car_class', 'admin', 'Klasa samochodu'),
    'car_type'  => __('car_type', 'admin', 'Typ samochodu'),
    'addon'     => __('addons', 'admin', 'Dodatki'),
];

$kind = $_GET['kind'] ?? 'location';
if (!isset($validKinds[$kind])) $kind = 'location';

$pdo = db();

// typ i hierarchia
$isHier = false;
$dictType = null;
$stmt = $pdo->prepare("SELECT id, slug, name, is_hierarchical FROM dict_types WHERE slug = :s LIMIT 1");
$stmt->execute([':s' => $kind]);
$dictType = $stmt->fetch(PDO::FETCH_ASSOC);
if ($dictType) $isHier = (bool)$dictType['is_hierarchical'];

// ⬇️ Dla „Klasa samochodu” i „Typ samochodu” wymuszamy brak hierarchii w UI
if (in_array($kind, ['car_class', 'car_type'], true)) {
    $isHier = false;
}

// termy
$terms = [];
if ($dictType) {
    // Sortowanie słowników
    $dictOrder = '';
    if ($section === 'dicts' && !empty($sort)) {
        $dictOrder = match ($sort) {
            'id' => "ORDER BY id $dir",
            'name' => "ORDER BY name $dir",
            'slug' => "ORDER BY slug $dir",
            'sort' => "ORDER BY sort_order $dir",
            'status' => "ORDER BY status $dir",
            'price' => "ORDER BY price $dir",
            'charge_type' => "ORDER BY charge_type $dir",
            default => "ORDER BY sort_order ASC, name ASC"
        };
    } else {
        $dictOrder = "ORDER BY sort_order ASC, name ASC";
    }

    if ($kind === 'addon') {
        $stmt = $pdo->prepare("
      SELECT id, parent_id, name, slug, sort_order, status, price, charge_type
      FROM dict_terms
      WHERE dict_type_id = :t
      $dictOrder
    ");
    } else {
        $stmt = $pdo->prepare("
      SELECT id, parent_id, name, slug, sort_order, status
      FROM dict_terms
      WHERE dict_type_id = :t
      $dictOrder
    ");
    }
    $stmt->execute([':t' => $dictType['id']]);
    $terms = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$byId = [];
foreach ($terms as $t) $byId[(int)$t['id']] = $t;

function isActiveRow(array $row): bool
{
    return ($row['status'] ?? '') === 'active';
}
?>
<div class="card section-dicts">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div>
            <strong><?= __('dictionaries', 'admin', 'Słowniki') ?></strong>
            <span class="text-muted">/ <?= htmlspecialchars($validKinds[$kind]) ?></span>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-secondary btn-sm" href="<?= panel_url(['kind' => 'location']) ?>"><?= __('locations', 'admin', 'Lokalizacje') ?></a>
            <a class="btn btn-outline-secondary btn-sm" href="<?= panel_url(['kind' => 'car_class']) ?>"><?= __('car_class', 'admin', 'Klasa samochodu') ?></a>
            <a class="btn btn-outline-secondary btn-sm" href="<?= panel_url(['kind' => 'car_type']) ?>"><?= __('car_type', 'admin', 'Typ samochodu') ?></a>
            <a class="btn btn-outline-secondary btn-sm" href="<?= panel_url(['kind' => 'addon']) ?>"><?= __('addons', 'admin', 'Dodatki') ?></a>
        </div>
    </div>

    <div class="card-body">

        <?php if (!empty($_GET['msg'])): ?>
            <div class="alert alert-success alert-dismissible fade show js-flash" role="alert">
                <?= htmlspecialchars((string)$_GET['msg']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        <?php if (!empty($_GET['err'])): ?>
            <div class="alert alert-danger alert-dismissible fade show js-flash" role="alert">
                <?= htmlspecialchars((string)$_GET['err']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <script>
            (function() {
                try {
                    var url = new URL(window.location.href);
                    var had = false;
                    ['msg', 'err'].forEach(function(k) {
                        if (url.searchParams.has(k)) {
                            url.searchParams.delete(k);
                            had = true;
                        }
                    });
                    if (had) {
                        var qs = url.searchParams.toString();
                        var newUrl = url.pathname + (qs ? '?' + qs : '') + url.hash;
                        history.replaceState(null, '', newUrl);
                        setTimeout(function() {
                            document.querySelectorAll('.js-flash').forEach(function(el) {
                                if (window.bootstrap && bootstrap.Alert) bootstrap.Alert.getOrCreateInstance(el).close();
                                else el.remove();
                            });
                        }, 4000);
                    }
                } catch (e) {}
            })();
        </script>

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="mb-0"><?= __('positions', 'admin', 'Pozycje') ?></h6>
            <button class="btn btn-primary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#dictAddForm">
                + <?= __('add_position', 'admin', 'Dodaj pozycję') ?>
            </button>
        </div>

        <!-- ADD -->
        <div class="collapse mb-3" id="dictAddForm">
            <form method="post" action="<?= $BASE ?>/pages/staff/dicts-save.php">
                <?= csrf_field(); ?>
                <input type="hidden" name="action" value="create">
                <input type="hidden" name="kind" value="<?= htmlspecialchars($kind) ?>">
                <div class="row g-2">
                    <div class="col-md-4">
                        <label class="form-label"><?= __('name', 'admin', 'Nazwa') ?> *</label>
                        <input type="text" name="name" class="form-control" required maxlength="128">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label"><?= __('slug', 'admin', 'Slug') ?> (<?= __('optional', 'admin', 'opcjonalnie') ?>)</label>
                        <input type="text" name="slug" class="form-control" maxlength="128" placeholder="<?= __('auto_from_name', 'admin', 'auto-z-nazwy') ?>">
                    </div>
                    <?php if ($isHier): ?>
                        <div class="col-md-3">
                            <label class="form-label"><?= __('parent', 'admin', 'Nadrzędny') ?> (<?= __('optional', 'admin', 'opcjonalnie') ?>)</label>
                            <select name="parent_id" class="form-select">
                                <option value=""><?= __('none', 'admin', '— brak —') ?></option>
                                <?php foreach ($terms as $p): ?>
                                    <option value="<?= (int)$p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>
                    <?php if ($kind === 'addon'): ?>
                        <div class="col-md-2">
                            <label class="form-label"><?= __('amount_pln', 'admin', 'Kwota (w zł)') ?></label>
                            <input type="number" name="price" class="form-control" step="0.01" min="0" value="0">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label"><?= __('billing_method', 'admin', 'Sposób naliczania') ?></label>
                            <select name="charge_type" class="form-select">
                                <option value="per_day"><?= __('per_day', 'admin', 'Za każdy dzień') ?></option>
                                <option value="once"><?= __('once', 'admin', 'Jednorazowo') ?></option>
                            </select>
                        </div>
                    <?php else: ?>
                        <div class="col-md-2">
                            <label class="form-label"><?= __('sort', 'admin', 'Sort') ?></label>
                            <input type="number" name="sort_order" class="form-control" value="0" step="1">
                        </div>
                    <?php endif; ?>
                    <div class="col-md-2">
                        <label class="form-label"><?= __('status', 'admin', 'Status') ?></label>
                        <select name="status" class="form-select">
                            <option value="active">active</option>
                            <option value="archived">archived</option>
                        </select>
                    </div>
                    <div class="col-12 d-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-sm"><?= __('save', 'admin', 'Zapisz') ?></button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="collapse" data-bs-target="#dictAddForm"><?= __('cancel', 'admin', 'Anuluj') ?></button>
                    </div>
                </div>
            </form>
        </div>

        <!-- LISTA -->
        <?php if (!$terms): ?>
            <p class="text-muted mb-0"><?= __('no_positions', 'admin', 'Brak pozycji.') ?></p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table align-middle table-sm">
                    <thead>
                        <tr>
                            <th style="width:42px;"><?= sort_link_dashboard('dicts', 'id', 'ID') ?></th>
                            <?php if ($isHier): ?><th style="width:180px;"><?= __('parent', 'admin', 'Nadrzędny') ?></th><?php endif; ?>
                            <th><?= sort_link_dashboard('dicts', 'name', __('name', 'admin', 'Nazwa')) ?></th>
                            <th style="width:200px;"><?= sort_link_dashboard('dicts', 'slug', __('slug', 'admin', 'Slug')) ?></th>
                            <?php if ($kind === 'addon'): ?>
                                <th style="width:100px;"><?= sort_link_dashboard('dicts', 'price', __('price', 'admin', 'Cena')) ?></th>
                                <th style="width:120px;"><?= sort_link_dashboard('dicts', 'charge_type', __('billing_method', 'admin', 'Sposób naliczania')) ?></th>
                            <?php endif; ?>
                            <th style="width:90px;"><?= sort_link_dashboard('dicts', 'sort', __('sort', 'admin', 'Sort')) ?></th>
                            <th style="width:140px;"><?= sort_link_dashboard('dicts', 'status', __('status', 'admin', 'Status')) ?></th>
                            <th style="width:210px;" class="text-end"><?= __('actions', 'admin', 'Akcje') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($terms as $row): ?>
                            <tr>
                                <td class="text-muted"><?= (int)$row['id'] ?></td>
                                <?php if ($isHier): ?>
                                    <td class="text-muted">
                                        <?php $pid = $row['parent_id'];
                                        echo $pid && isset($byId[(int)$pid]) ? htmlspecialchars($byId[(int)$pid]['name']) : '—'; ?>
                                    </td>
                                <?php endif; ?>
                                <td><?= htmlspecialchars($row['name']) ?></td>
                                <td><code><?= htmlspecialchars($row['slug']) ?></code></td>
                                <?php if ($kind === 'addon'): ?>
                                    <td><?= isset($row['price']) ? number_format($row['price'], 2) : '' ?></td>
                                    <td><?php
                                        if (isset($row['charge_type'])) {
                                            if ($row['charge_type'] === 'per_day') echo __('per_day', 'admin', 'Za każdy dzień');
                                            elseif ($row['charge_type'] === 'once') echo __('once', 'admin', 'Jednorazowo');
                                            else echo '';
                                        } else {
                                            echo '';
                                        }
                                        ?></td>
                                <?php endif; ?>
                                <td><?= (int)$row['sort_order'] ?></td>
                                <td>
                                    <?php if (isActiveRow($row)): ?>
                                        <span class="badge text-bg-success">active</span>
                                    <?php else: ?>
                                        <span class="badge text-bg-secondary"><?= htmlspecialchars($row['status']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <div class="d-inline-flex gap-2">
                                        <button class="btn btn-outline-primary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#editRow<?= (int)$row['id'] ?>"><?= __('edit', 'admin', 'Edytuj') ?></button>
                                        <form method="post" action="<?= $BASE ?>/pages/staff/dicts-delete.php" onsubmit="return confirm('<?= __('confirm_delete_position', 'admin', 'Usunąć pozycję?') ?>');" class="d-inline">
                                            <?= csrf_field(); ?>
                                            <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                                            <input type="hidden" name="kind" value="<?= htmlspecialchars($kind) ?>">
                                            <button type="submit" class="btn btn-outline-danger btn-sm"><?= __('delete', 'admin', 'Usuń') ?></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>

                            <tr class="collapse bg-body-tertiary" id="editRow<?= (int)$row['id'] ?>">
                                <td colspan="<?= $isHier ? 7 : 6 ?>">
                                    <form class="row g-2 align-items-end" method="post" action="<?= $BASE ?>/pages/staff/dicts-save.php">
                                        <?= csrf_field(); ?>
                                        <input type="hidden" name="action" value="update">
                                        <input type="hidden" name="kind" value="<?= htmlspecialchars($kind) ?>">
                                        <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">

                                        <div class="col-md-4">
                                            <label class="form-label"><?= __('name', 'admin', 'Nazwa') ?> *</label>
                                            <input type="text" name="name" value="<?= htmlspecialchars($row['name']) ?>" class="form-control form-control-sm" required maxlength="128">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label"><?= __('slug', 'admin', 'Slug') ?></label>
                                            <input type="text" name="slug" value="<?= htmlspecialchars($row['slug']) ?>" class="form-control form-control-sm" maxlength="128">
                                        </div>
                                        <?php if ($isHier): ?>
                                            <div class="col-md-3">
                                                <label class="form-label"><?= __('parent', 'admin', 'Nadrzędny') ?></label>
                                                <select name="parent_id" class="form-select form-select-sm">
                                                    <option value=""><?= __('none', 'admin', '— brak —') ?></option>
                                                    <?php foreach ($terms as $p): ?>
                                                        <option value="<?= (int)$p['id'] ?>" <?= ((string)$p['id'] === (string)$row['parent_id']) ? 'selected' : '' ?>><?= htmlspecialchars($p['name']) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        <?php endif; ?>
                                        <?php if ($kind === 'addon'): ?>
                                            <div class="col-md-2">
                                                <label class="form-label"><?= __('amount_pln', 'admin', 'Kwota (w zł)') ?></label>
                                                <input type="number" name="price" value="<?= htmlspecialchars($row['price'] ?? '') ?>" class="form-control form-control-sm" step="0.01" min="0">
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label"><?= __('billing_method', 'admin', 'Sposób naliczania') ?></label>
                                                <select name="charge_type" class="form-select form-select-sm">
                                                    <option value="per_day" <?= ($row['charge_type'] ?? '') === 'per_day' ? 'selected' : '' ?>><?= __('per_day', 'admin', 'Za każdy dzień') ?></option>
                                                    <option value="once" <?= ($row['charge_type'] ?? '') === 'once' ? 'selected' : '' ?>><?= __('once', 'admin', 'Jednorazowo') ?></option>
                                                </select>
                                            </div>
                                        <?php else: ?>
                                            <div class="col-md-2">
                                                <label class="form-label"><?= __('sort', 'admin', 'Sort') ?></label>
                                                <input type="number" name="sort_order" value="<?= (int)$row['sort_order'] ?>" class="form-control form-control-sm" step="1">
                                            </div>
                                        <?php endif; ?>
                                        <div class="col-md-2">
                                            <label class="form-label"><?= __('status', 'admin', 'Status') ?></label>
                                            <select name="status" class="form-select form-select-sm">
                                                <option value="active" <?= $row['status'] === 'active'   ? 'selected' : '' ?>>active</option>
                                                <option value="archived" <?= $row['status'] === 'archived' ? 'selected' : '' ?>>archived</option>
                                            </select>
                                        </div>
                                        <div class="col-12 d-flex gap-2">
                                            <button type="submit" class="btn btn-primary btn-sm"><?= __('save_changes', 'admin', 'Zapisz zmiany') ?></button>
                                            <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="collapse" data-bs-target="#editRow<?= (int)$row['id'] ?>"><?= __('close_edit', 'admin', 'Zamknij edycję') ?></button>
                                        </div>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <hr>
        <p class="small text-muted mb-0">
            <?= __('subsections', 'admin', 'Podsekcje') ?>:
            <a href="<?= panel_url(['kind' => 'location']) ?>"><?= __('locations', 'admin', 'Lokalizacje') ?></a> •
            <a href="<?= panel_url(['kind' => 'car_class']) ?>"><?= __('car_class', 'admin', 'Klasa samochodu') ?></a> •
            <a href="<?= panel_url(['kind' => 'car_type']) ?>"><?= __('car_type', 'admin', 'Typ samochodu') ?></a>
        </p>
    </div>
</div>