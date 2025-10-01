<?php
// /pages/search-results.php

require_once __DIR__ . '/includes/search.php';
$SEARCH = run_search($_GET);

// ten action sprawi, że pozostaniemy na stronie wyników przy każdym submit
$SEARCH_FORM_ACTION = (defined('BASE_URL') && BASE_URL !== '')
    ? rtrim(BASE_URL, '/') . '/index.php?page=search-results'
    : 'index.php?page=search-results';
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
        <?php include __DIR__ . '/../components/product-grid.php'; ?>
    </div>
</main>

<!-- Tymczasowy TEST: jeśli to zobaczysz wycentrowane i z paddingiem,
     to znaczy że SCSS nie był skompilowany/załadowany — możesz usunąć po weryfikacji -->
<style>
    /* TEST tylko na tej stronie */
    #search-results-shell .search-hero {
        padding: 72px 0 24px 0;
        background: #f3f5f2;
    }

    #search-results-shell .search-hero .page-title {
        text-align: center;
        margin: 0 0 16px;
    }

    #search-results-shell .search-hero .search-panel {
        max-width: 1100px;
        margin-inline: auto;
    }

    #search-results-shell .after-form {
        background: #f3f5f2;
        padding-top: 16px;
    }

    #search-results-shell section#offer {
        padding-top: 0;
    }
</style>