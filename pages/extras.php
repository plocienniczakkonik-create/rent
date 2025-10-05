<?php
// /pages/extras.php

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

// Initialize i18n
require_once __DIR__ . '/../includes/i18n.php';
i18n::init();

?>

<style>
    .extras-hero {
        background: #f8f9fa;
        min-height: 60vh;
        display: flex;
        align-items: center;
        color: #212529;
        position: relative;
        overflow: hidden;
        margin-bottom: -170px;
    }

    .extras-hero::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="black" opacity="0.05"/><circle cx="75" cy="75" r="1" fill="black" opacity="0.05"/><circle cx="50" cy="10" r="0.5" fill="black" opacity="0.08"/><circle cx="10" cy="60" r="0.5" fill="black" opacity="0.08"/><circle cx="90" cy="40" r="0.5" fill="black" opacity="0.08"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
        opacity: 0.3;
    }

    .extras-hero .container {
        position: relative;
        z-index: 2;
    }

    .extras-hero h1,
    .extras-hero .lead,
    .extras-hero p {
        color: #212529;
    }

    .extras-section {
        padding: 80px 0;
        background: #f8f9fa;
    }

    .extras-category {
        background: white;
        border-radius: 20px;
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
        overflow: hidden;
        margin-bottom: 30px;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .extras-category:hover {
        transform: translateY(-5px);
        box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
    }

    .extras-category img {
        width: 100%;
        height: 250px;
        object-fit: cover;
    }

    .extras-category-content {
        padding: 30px;
    }

    .extras-category-title {
        font-size: 1.5rem;
        font-weight: 700;
        color: #212529;
        margin-bottom: 15px;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    .extras-category-desc {
        color: #6c757d;
        line-height: 1.6;
        margin-bottom: 20px;
    }

    .extras-category-price {
        background: var(--color-primary);
        color: white;
        padding: 8px 16px;
        border-radius: 20px;
        font-weight: 600;
        display: inline-block;
        margin-bottom: 15px;
    }

    .pricing-section {
        padding: 80px 0;
        background: white;
    }

    .pricing-card {
        background: white;
        border: 2px solid #e9ecef;
        border-radius: 15px;
        padding: 30px 20px;
        text-align: center;
        margin-bottom: 30px;
        transition: all 0.3s ease;
        height: 100%;
    }

    .pricing-card:hover {
        border-color: var(--color-primary);
        transform: translateY(-5px);
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
    }

    .pricing-card-title {
        font-size: 1.1rem;
        font-weight: 600;
        color: #495057;
        margin-bottom: 20px;
        min-height: 50px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .pricing-card-price {
        font-size: 2.5rem;
        font-weight: 700;
        color: var(--color-primary);
        margin-bottom: 10px;
    }

    .pricing-card-period {
        color: #6c757d;
        font-size: 0.9rem;
    }

    .cta-section {
        background: #f8f9fa;
        padding: 80px 0;
        color: #212529;
        text-align: center;
    }

    .cta-section h2 {
        font-size: 2.5rem;
        margin-bottom: 20px;
        color: #212529;
    }

    .cta-section .lead {
        color: #495057;
    }
</style>

<!-- Hero Section -->
<section class="extras-hero">
    <div class="container">
        <div class="row">
            <div class="col-lg-8 mx-auto text-center">
                <h1 class="display-4 fw-bold mb-4"><?= i18n::__('extras_title', 'frontend') ?></h1>
                <p class="lead fs-5"><?= i18n::__('extras_subtitle', 'frontend') ?></p>
                <p class="fs-6"><?= i18n::__('extras_description', 'frontend') ?></p>
            </div>
        </div>
    </div>
</section>

<!-- Extras Categories Section -->
<section class="extras-section">
    <div class="container">
        <div class="row mb-5">
            <div class="col-12 text-center">
                <h2 class="fw-bold mb-3"><?= i18n::__('available_extras', 'frontend') ?></h2>
                <p class="text-muted"><?= i18n::__('available_extras_desc', 'frontend') ?></p>
            </div>
        </div>

        <div class="row">
            <!-- Child Seats -->
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="extras-category">
                    <img src="<?= BASE_URL ?>/assets/img/child-seat.jpg" alt="<?= i18n::__('child_seats', 'frontend') ?>">
                    <div class="extras-category-content">
                        <h3 class="extras-category-title"><?= i18n::__('child_seats', 'frontend') ?></h3>
                        <p class="extras-category-desc"><?= i18n::__('child_seats_desc', 'frontend') ?></p>
                        <span class="extras-category-price"><?= i18n::__('from', 'frontend') ?> 15 PLN</span>
                        <p class="small text-muted"><?= i18n::__('per_day', 'frontend') ?></p>
                    </div>
                </div>
            </div>

            <!-- Additional Driver -->
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="extras-category">
                    <img src="<?= BASE_URL ?>/assets/img/additional-driver.jpg" alt="<?= i18n::__('additional_driver', 'frontend') ?>">
                    <div class="extras-category-content">
                        <h3 class="extras-category-title"><?= i18n::__('additional_driver', 'frontend') ?></h3>
                        <p class="extras-category-desc"><?= i18n::__('additional_driver_desc', 'frontend') ?></p>
                        <span class="extras-category-price"><?= i18n::__('from', 'frontend') ?> 25 PLN</span>
                        <p class="small text-muted"><?= i18n::__('per_day', 'frontend') ?></p>
                    </div>
                </div>
            </div>

            <!-- Return to Different Location -->
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="extras-category">
                    <img src="<?= BASE_URL ?>/assets/img/different-location.jpg" alt="<?= i18n::__('different_return', 'frontend') ?>">
                    <div class="extras-category-content">
                        <h3 class="extras-category-title"><?= i18n::__('different_return', 'frontend') ?></h3>
                        <p class="extras-category-desc"><?= i18n::__('different_return_desc', 'frontend') ?></p>
                        <span class="extras-category-price"><?= i18n::__('from', 'frontend') ?> 150 PLN</span>
                        <p class="small text-muted"><?= i18n::__('one_time', 'frontend') ?></p>
                    </div>
                </div>
            </div>

            <!-- Foreign Trip -->
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="extras-category">
                    <img src="<?= BASE_URL ?>/assets/img/foreign-trip.jpg" alt="<?= i18n::__('foreign_trip', 'frontend') ?>">
                    <div class="extras-category-content">
                        <h3 class="extras-category-title"><?= i18n::__('foreign_trip', 'frontend') ?></h3>
                        <p class="extras-category-desc"><?= i18n::__('foreign_trip_desc', 'frontend') ?></p>
                        <span class="extras-category-price"><?= i18n::__('from', 'frontend') ?> 99 PLN</span>
                        <p class="small text-muted"><?= i18n::__('per_trip', 'frontend') ?></p>
                    </div>
                </div>
            </div>

            <!-- Damage/Theft Protection -->
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="extras-category">
                    <img src="<?= BASE_URL ?>/assets/img/insurance.jpg" alt="<?= i18n::__('damage_protection', 'frontend') ?>">
                    <div class="extras-category-content">
                        <h3 class="extras-category-title"><?= i18n::__('damage_protection', 'frontend') ?></h3>
                        <p class="extras-category-desc"><?= i18n::__('damage_protection_desc', 'frontend') ?></p>
                        <span class="extras-category-price"><?= i18n::__('from', 'frontend') ?> 45 PLN</span>
                        <p class="small text-muted"><?= i18n::__('per_day', 'frontend') ?></p>
                    </div>
                </div>
            </div>

            <!-- Pick-up/Return Service -->
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="extras-category">
                    <img src="<?= BASE_URL ?>/assets/img/pickup-service.jpg" alt="<?= i18n::__('pickup_service', 'frontend') ?>">
                    <div class="extras-category-content">
                        <h3 class="extras-category-title"><?= i18n::__('pickup_service', 'frontend') ?></h3>
                        <p class="extras-category-desc"><?= i18n::__('pickup_service_desc', 'frontend') ?></p>
                        <span class="extras-category-price"><?= i18n::__('from', 'frontend') ?> 80 PLN</span>
                        <p class="small text-muted"><?= i18n::__('per_service', 'frontend') ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Pricing Section -->
<section class="pricing-section">
    <div class="container">
        <div class="row mb-5">
            <div class="col-12 text-center">
                <h2 class="fw-bold mb-3"><?= i18n::__('extras_pricing', 'frontend') ?></h2>
                <p class="text-muted"><?= i18n::__('extras_pricing_desc', 'frontend') ?></p>
            </div>
        </div>

        <div class="row">
            <!-- Warsaw Airport -->
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="pricing-card">
                    <div class="pricing-card-title"><?= i18n::__('warsaw_airport', 'frontend') ?></div>
                    <div class="pricing-card-price">49 <small>PLN</small></div>
                    <div class="pricing-card-period"><?= i18n::__('per_service', 'frontend') ?></div>
                </div>
            </div>

            <!-- Warsaw Modlin -->
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="pricing-card">
                    <div class="pricing-card-title"><?= i18n::__('warsaw_modlin', 'frontend') ?></div>
                    <div class="pricing-card-price">99 <small>PLN</small></div>
                    <div class="pricing-card-period"><?= i18n::__('per_service', 'frontend') ?></div>
                </div>
            </div>

            <!-- Krakow Balice -->
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="pricing-card">
                    <div class="pricing-card-title"><?= i18n::__('krakow_balice', 'frontend') ?></div>
                    <div class="pricing-card-price">39 <small>PLN</small></div>
                    <div class="pricing-card-period"><?= i18n::__('per_service', 'frontend') ?></div>
                </div>
            </div>

            <!-- Katowice Pyrzowice -->
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="pricing-card">
                    <div class="pricing-card-title"><?= i18n::__('katowice_pyrzowice', 'frontend') ?></div>
                    <div class="pricing-card-price">99 <small>PLN</small></div>
                    <div class="pricing-card-period"><?= i18n::__('per_service', 'frontend') ?></div>
                </div>
            </div>

            <!-- Rzeszow Jasionka -->
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="pricing-card">
                    <div class="pricing-card-title"><?= i18n::__('rzeszow_jasionka', 'frontend') ?></div>
                    <div class="pricing-card-price">49 <small>PLN</small></div>
                    <div class="pricing-card-period"><?= i18n::__('per_service', 'frontend') ?></div>
                </div>
            </div>

            <!-- Wroclaw Strachowice -->
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="pricing-card">
                    <div class="pricing-card-title"><?= i18n::__('wroclaw_strachowice', 'frontend') ?></div>
                    <div class="pricing-card-price">49 <small>PLN</small></div>
                    <div class="pricing-card-period"><?= i18n::__('per_service', 'frontend') ?></div>
                </div>
            </div>

            <!-- Wroclaw-Gdansk-Warsaw -->
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="pricing-card">
                    <div class="pricing-card-title"><?= i18n::__('multi_city_route', 'frontend') ?></div>
                    <div class="pricing-card-price">39 <small>PLN</small></div>
                    <div class="pricing-card-period"><?= i18n::__('per_service', 'frontend') ?></div>
                </div>
            </div>

            <!-- Cross-border -->
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="pricing-card">
                    <div class="pricing-card-title"><?= i18n::__('cross_border_service', 'frontend') ?></div>
                    <div class="pricing-card-price">39 <small>PLN</small></div>
                    <div class="pricing-card-period"><?= i18n::__('per_service', 'frontend') ?></div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="cta-section">
    <div class="container">
        <div class="row">
            <div class="col-lg-8 mx-auto text-center">
                <h2 class="fw-bold mb-3"><?= i18n::__('ready_to_book', 'frontend') ?></h2>
                <p class="lead mb-4"><?= i18n::__('book_car_with_extras', 'frontend') ?></p>
                <a href="<?= BASE_URL ?>/index.php#offer" class="btn btn-theme btn-primary btn-lg"><?= i18n::__('book_now', 'frontend') ?></a>
            </div>
        </div>
    </div>
</section>