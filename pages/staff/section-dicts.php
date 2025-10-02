<?php
// /pages/staff/section-dicts.php
require_once dirname(__DIR__, 2) . '/includes/db.php';
require_once dirname(__DIR__, 2) . '/auth/auth.php';
require_once dirname(__DIR__, 2) . '/includes/config.php';
require_staff();

$BASE = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';

function panel_url(array $params = []): string {
    $base = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
    $q = http_build_query(array_merge(['page' => 'dashboard-staff', 'tab' => 'dicts'], $params));
    return $base . '/index.php?' . $q . '#pane-dicts';
}

$validKinds = [
    'location'  => 'Lokalizacje',
    'car_class' => 'Klasa samochodu',
    'car_type'  => 'Typ samochodu',     // ⬅️ NOWE
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
if (in_array($kind, ['car_class','car_type'], true)) {
    $isHier = false;
}

// termy
$terms = [];
if ($dictType) {
    $stmt = $pdo->prepare("
        SELECT id, parent_id, name, slug, sort_order, status
        FROM dict_terms
        WHERE dict_type_id = :t
        ORDER BY sort_order ASC, name ASC
    ");
    $stmt->execute([':t' => $dictType['id']]);
    $terms = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$byId = [];
foreach ($terms as $t) $byId[(int)$t['id']] = $t;

function isActiveRow(array $row): bool {
    return ($row['status'] ?? '') === 'active';
}
?>
<div class="card shadow-sm">
  <div class="card-header d-flex justify-content-between align-items-center">
    <div>
      <strong>Słowniki</strong>
      <span class="text-muted">/ <?= htmlspecialchars($validKinds[$kind]) ?></span>
    </div>
    <div class="d-flex gap-2">
      <a class="btn btn-outline-secondary btn-sm" href="<?= panel_url(['kind' => 'location']) ?>">Lokalizacje</a>
      <a class="btn btn-outline-secondary btn-sm" href="<?= panel_url(['kind' => 'car_class']) ?>">Klasa samochodu</a>
      <a class="btn btn-outline-secondary btn-sm" href="<?= panel_url(['kind' => 'car_type']) ?>">Typ samochodu</a> <!-- ⬅️ NOWE -->
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
      (function () {
        try {
          var url = new URL(window.location.href);
          var had = false;
          ['msg','err'].forEach(function(k){ if (url.searchParams.has(k)) { url.searchParams.delete(k); had = true; }});
          if (had) {
            var qs = url.searchParams.toString();
            var newUrl = url.pathname + (qs ? '?' + qs : '') + url.hash;
            history.replaceState(null, '', newUrl);
            setTimeout(function(){
              document.querySelectorAll('.js-flash').forEach(function(el){
                if (window.bootstrap && bootstrap.Alert) bootstrap.Alert.getOrCreateInstance(el).close();
                else el.remove();
              });
            }, 4000);
          }
        } catch(e) {}
      })();
    </script>

    <div class="d-flex justify-content-between align-items-center mb-3">
      <h6 class="mb-0">Pozycje</h6>
      <button class="btn btn-primary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#dictAddForm">
        + Dodaj pozycję
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
            <label class="form-label">Nazwa *</label>
            <input type="text" name="name" class="form-control" required maxlength="128">
          </div>
          <div class="col-md-3">
            <label class="form-label">Slug (opcjonalnie)</label>
            <input type="text" name="slug" class="form-control" maxlength="128" placeholder="auto-z-nazwy">
          </div>
          <?php if ($isHier): ?>
          <div class="col-md-3">
            <label class="form-label">Nadrzędny (opcjonalnie)</label>
            <select name="parent_id" class="form-select">
              <option value="">— brak —</option>
              <?php foreach ($terms as $p): ?>
                <option value="<?= (int)$p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <?php endif; ?>
          <div class="col-md-2">
            <label class="form-label">Sort</label>
            <input type="number" name="sort_order" class="form-control" value="0" step="1">
          </div>
          <div class="col-md-2">
            <label class="form-label">Status</label>
            <select name="status" class="form-select">
              <option value="active">active</option>
              <option value="archived">archived</option>
            </select>
          </div>
          <div class="col-12 d-flex gap-2">
            <button type="submit" class="btn btn-primary btn-sm">Zapisz</button>
            <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="collapse" data-bs-target="#dictAddForm">Anuluj</button>
          </div>
        </div>
      </form>
    </div>

    <!-- LISTA -->
    <?php if (!$terms): ?>
      <p class="text-muted mb-0">Brak pozycji.</p>
    <?php else: ?>
      <div class="table-responsive">
        <table class="table align-middle table-sm">
          <thead>
            <tr>
              <th style="width:42px;">ID</th>
              <?php if ($isHier): ?><th style="width:180px;">Nadrzędny</th><?php endif; ?>
              <th>Nazwa</th>
              <th style="width:200px;">Slug</th>
              <th style="width:90px;">Sort</th>
              <th style="width:140px;">Status</th>
              <th style="width:210px;" class="text-end">Akcje</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($terms as $row): ?>
            <tr>
              <td class="text-muted"><?= (int)$row['id'] ?></td>
              <?php if ($isHier): ?>
                <td class="text-muted">
                  <?php $pid = $row['parent_id']; echo $pid && isset($byId[(int)$pid]) ? htmlspecialchars($byId[(int)$pid]['name']) : '—'; ?>
                </td>
              <?php endif; ?>
              <td><?= htmlspecialchars($row['name']) ?></td>
              <td><code><?= htmlspecialchars($row['slug']) ?></code></td>
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
                  <button class="btn btn-outline-primary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#editRow<?= (int)$row['id'] ?>">Edytuj</button>
                  <form method="post" action="<?= $BASE ?>/pages/staff/dicts-delete.php" onsubmit="return confirm('Usunąć pozycję?');" class="d-inline">
                    <?= csrf_field(); ?>
                    <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                    <input type="hidden" name="kind" value="<?= htmlspecialchars($kind) ?>">
                    <button type="submit" class="btn btn-outline-danger btn-sm">Usuń</button>
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
                    <label class="form-label">Nazwa *</label>
                    <input type="text" name="name" value="<?= htmlspecialchars($row['name']) ?>" class="form-control form-control-sm" required maxlength="128">
                  </div>
                  <div class="col-md-3">
                    <label class="form-label">Slug</label>
                    <input type="text" name="slug" value="<?= htmlspecialchars($row['slug']) ?>" class="form-control form-control-sm" maxlength="128">
                  </div>

                  <?php if ($isHier): ?>
                  <div class="col-md-3">
                    <label class="form-label">Nadrzędny</label>
                    <select name="parent_id" class="form-select form-select-sm">
                      <option value="">— brak —</option>
                      <?php foreach ($terms as $p): ?>
                        <option value="<?= (int)$p['id'] ?>" <?= ((string)$p['id'] === (string)$row['parent_id']) ? 'selected' : '' ?>><?= htmlspecialchars($p['name']) ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <?php endif; ?>

                  <div class="col-md-2">
                    <label class="form-label">Sort</label>
                    <input type="number" name="sort_order" value="<?= (int)$row['sort_order'] ?>" class="form-control form-control-sm" step="1">
                  </div>
                  <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select form-select-sm">
                      <option value="active"   <?= $row['status']==='active'   ? 'selected' : '' ?>>active</option>
                      <option value="archived" <?= $row['status']==='archived' ? 'selected' : '' ?>>archived</option>
                    </select>
                  </div>

                  <div class="col-12 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm">Zapisz zmiany</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="collapse" data-bs-target="#editRow<?= (int)$row['id'] ?>">Zamknij edycję</button>
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
      Podsekcje:
      <a href="<?= panel_url(['kind' => 'location']) ?>">Lokalizacje</a> •
      <a href="<?= panel_url(['kind' => 'car_class']) ?>">Klasa samochodu</a> •
      <a href="<?= panel_url(['kind' => 'car_type']) ?>">Typ samochodu</a>
    </p>
  </div>
</div>
