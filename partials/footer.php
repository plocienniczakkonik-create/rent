<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>

<!-- Ładujemy index.js z BASE_URL (działa poprawnie w podkatalogu) -->
<?php $BASE = defined('BASE_URL') ? rtrim(BASE_URL, '/') : ''; ?>
<script src="<?= $BASE ?>/assets/js/index.js" defer></script>