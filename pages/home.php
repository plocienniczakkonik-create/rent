<?php
// /pages/home.php

require_once __DIR__ . '/../includes/config.php';   // jeśli masz
require_once __DIR__ . '/../includes/db.php';

// NIE wołamy tu run_search – to jest strona bez wyników
$SEARCH = null;

// kluczowe: ustaw gdzie ma POST/GET lecieć po kliknięciu "Pokaż samochody"
$SEARCH_FORM_ACTION = 'index.php?page=search-results';

// HERO z wyszukiwarką
include __DIR__ . '/../components/hero.php';
