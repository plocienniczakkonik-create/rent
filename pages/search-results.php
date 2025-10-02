<?php
// /pages/search-results.php

require_once __DIR__ . '/includes/search.php';

// Wyniki (run_search liczy m.in. price_final z promocjami)
$SEARCH   = run_search($_GET);
$products = $SEARCH['products'] ?? [];

// Sort (domyślnie najtańsze po price_final)
$sort = $_GET['sort'] ?? 'price_asc';

usort($products, function(array $a, array $b) use ($sort) {
    $pa = (float)($a['price_final'] ?? $a['price'] ?? 0);
    $pb = (float)($b['price_final'] ?? $b['price'] ?? 0);
    $na = (string)($a['name'] ?? '');
    $nb = (string)($b['name'] ?? '');
    switch ($sort) {
        case 'price_desc': return $pb <=> $pa;
        case 'name_asc':   return strcasecmp($na, $nb);
        case 'name_desc':  return strcasecmp($nb, $na);
        case 'price_asc':
        default:           return $pa <=> $pb;
    }
});
$SEARCH['products'] = $products;

// Action – pozostajemy na tej stronie przy zmianie sortu
$SEARCH_FORM_ACTION = (defined('BASE_URL') && BASE_URL !== '')
    ? rtrim(BASE_URL, '/') . '/index.php?page=search-results'
    : 'index.php?page=search-results';

// Lewa strona paska – prosta metryka (jeśli masz paginację, możesz podstawić zakres)
$results_count = count($products);
$results_left  = $results_count . ' pojazdów';

ob_start();
?>
<form class="search-sortbar" method="get" action="<?= htmlspecialchars($SEARCH_FORM_ACTION) ?>">
  <?php
  // zachowaj wszystkie obecne filtry (oprócz 'sort')
  foreach ($_GET as $k => $v) {
      if ($k === 'sort') continue;
      if (is_array($v)) {
          foreach ($v as $vv) {
              echo '<input type="hidden" name="'.htmlspecialchars($k).'[]" value="'.htmlspecialchars($vv).'">';
          }
      } else {
          echo '<input type="hidden" name="'.htmlspecialchars($k).'" value="'.htmlspecialchars($v).'">';
      }
  }
  ?>
  <label for="sort" class="me-2">Sortuj:</label>
  <select id="sort" name="sort" class="form-select form-select-sm" onchange="this.form.submit()">
    <option value="price_asc"  <?= $sort==='price_asc'  ? 'selected' : '' ?>>Najtańsze</option>
    <option value="price_desc" <?= $sort==='price_desc' ? 'selected' : '' ?>>Najdroższe</option>
    <option value="name_asc"   <?= $sort==='name_asc'   ? 'selected' : '' ?>>Nazwa A–Z</option>
    <option value="name_desc"  <?= $sort==='name_desc'  ? 'selected' : '' ?>>Nazwa Z–A</option>
  </select>
</form>
<?php
$SORTBAR_HTML = ob_get_clean();

// Przekaż do grida HTML paska (ma się wyświetlić POD nagłówkiem „Nasza flota”)
$GRID_TOOLBAR_LEFT  = $results_left;
$GRID_TOOLBAR_RIGHT = $SORTBAR_HTML;
?>

<main id="search-results-shell">
  <section class="search-hero">
    <div class="container-xl">
      <h2 class="page-title">Wyszukaj samochód</h2>
      <div class="search-panel">
        <?php include __DIR__ . '/../components/search-form.php'; ?>
      </div>
    </div>
  </section>

  <div class="after-form">
    <?php
    // komponent kart – sam renderuje nagłówek „Nasza flota”
    // i tuż POD nim pokaże pasek (lewo: metryka, prawo: sort)
    include __DIR__ . '/../components/product-grid.php';
    ?>
  </div>
</main>
