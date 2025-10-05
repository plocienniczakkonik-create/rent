<?php
// Initialize i18n
require_once dirname(__DIR__) . '/includes/i18n.php';
i18n::init();

// Include theme-config for branding
require_once dirname(__DIR__) . '/includes/theme-config.php';
?>
<footer class="site-footer border-top">
    <div class="container">
        <div class="row align-items-center gy-3 py-4">

            <!-- lewa: copyright -->
            <div class="col-12 col-md-4 text-center text-md-start">
                <small class="mb-0 d-block">
                    &copy; <?= date('Y') ?> Lucyfher
                </small>
            </div>

            <!-- środek: „badge" z literą / logo -->
            <div class="col-12 col-md-4 text-center">
                <?= theme_render_brand('brand-badge', true) ?>
            </div>

            <!-- prawa: nawigacja -->
            <div class="col-12 col-md-4">
                <ul class="footer-nav nav justify-content-center justify-content-md-end gap-3">
                    <li class="nav-item"><a class="nav-link px-0" href="<?= BASE_URL ?>/index.php?page=terms"><?= i18n::__('footer_terms', 'frontend') ?></a></li>
                    <li class="nav-item"><a class="nav-link px-0" href="<?= BASE_URL ?>/index.php?page=privacy-policy"><?= i18n::__('footer_privacy', 'frontend') ?></a></li>
                    <li class="nav-item"><a class="nav-link px-0" href="<?= BASE_URL ?>/index.php?page=contact#faq"><?= i18n::__('footer_faq', 'frontend') ?></a></li>
                    <li class="nav-item"><a class="nav-link px-0" href="<?= BASE_URL ?>/index.php?page=contact"><?= i18n::__('footer_contact', 'frontend') ?></a></li>
                </ul>
            </div>

        </div>
    </div>
</footer>