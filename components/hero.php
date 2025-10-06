<!-- HERO -->
<header class="position-relative">
    <section class="text-center bg-image hero d-flex flex-column justify-content-center align-items-center search-hero" style="background-image: url('assets/img/header3.webp'); min-height: 480px;">
        <div class="container-xl">
            <h1 class="mb-1 text-white fw-bold page-title" style="text-shadow: 0 2px 12px #000"><?= i18n::__('car_rental_corona', 'frontend') ?> <b style="color: var(--color-primary)"><?= i18n::__('corona_brand', 'frontend') ?></b></h1>
            <div class="search-panel">
                <?php include __DIR__ . '/search-form.php'; ?>
            </div>
        </div>
    </section>
</header>