<?php
// Deprecated: addons are managed via Dictionaries (dict_terms with slug 'addon').
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/auth/auth.php';
require_staff();
$BASE = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
header('Location: ' . $BASE . '/index.php?page=dashboard-staff&kind=addon#pane-dicts');
exit;
