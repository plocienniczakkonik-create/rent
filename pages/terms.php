<?php
// /pages/terms.php

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

// Initialize i18n
require_once __DIR__ . '/../includes/i18n.php';
i18n::init();

?>

<style>
    .terms-hero {
        background: #f8f9fa;
        min-height: 40vh;
        display: flex;
        align-items: center;
        color: #212529;
        position: relative;
        overflow: hidden;
        margin-bottom: -100px;
    }

    .terms-hero::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="black" opacity="0.05"/><circle cx="75" cy="75" r="1" fill="black" opacity="0.05"/><circle cx="50" cy="10" r="0.5" fill="black" opacity="0.08"/><circle cx="10" cy="60" r="0.5" fill="black" opacity="0.08"/><circle cx="90" cy="40" r="0.5" fill="black" opacity="0.08"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
        opacity: 0.3;
    }

    .terms-hero .container {
        position: relative;
        z-index: 2;
    }

    .terms-hero h1,
    .terms-hero .lead {
        color: #212529;
    }

    .terms-content {
        background: white;
        border-radius: 20px;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        padding: 60px;
        margin-top: 50px;
        position: relative;
        z-index: 3;
        margin-bottom: 50px;
    }

    .terms-section {
        padding: 80px 0;
        background: #f8f9fa;
    }

    .terms-section h2 {
        color: #212529;
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 30px;
        position: relative;
        padding-bottom: 15px;
    }

    .terms-section h2::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        width: 60px;
        height: 3px;
        background: #007bff;
        border-radius: 2px;
    }

    .terms-section h3 {
        color: #495057;
        font-size: 1.4rem;
        font-weight: 600;
        margin-top: 40px;
        margin-bottom: 20px;
    }

    .terms-section p {
        color: #6c757d;
        line-height: 1.8;
        margin-bottom: 20px;
    }

    .terms-section ol,
    .terms-section ul {
        color: #6c757d;
        line-height: 1.8;
        margin-bottom: 25px;
    }

    .terms-section ol li,
    .terms-section ul li {
        margin-bottom: 12px;
        padding-left: 10px;
    }

    .warning-box {
        background: #fff3cd;
        border-left: 4px solid #ffc107;
        padding: 25px;
        margin: 30px 0;
        border-radius: 8px;
    }

    .warning-box p {
        margin-bottom: 0;
        color: #856404;
        font-weight: 500;
    }

    .info-box {
        background: #d1ecf1;
        border-left: 4px solid #17a2b8;
        padding: 25px;
        margin: 30px 0;
        border-radius: 8px;
    }

    .info-box p {
        margin-bottom: 0;
        color: #0c5460;
        font-weight: 500;
    }

    .contact-box {
        background: var(--gradient-primary);
        color: white;
        padding: 40px;
        border-radius: 15px;
        text-align: center;
        margin-top: 50px;
    }

    .contact-box h3 {
        color: white;
        margin-bottom: 20px;
    }

    .contact-box p {
        color: rgba(255, 255, 255, 0.9);
        margin-bottom: 20px;
    }

    .btn-contact {
        background: white;
        color: #007bff;
        padding: 12px 30px;
        border-radius: 50px;
        font-weight: 600;
        text-decoration: none;
        display: inline-block;
        transition: all 0.3s ease;
    }

    .btn-contact:hover {
        background: #f8f9fa;
        color: #6610f2;
        text-decoration: none;
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
    }

    @media (max-width: 768px) {
        .terms-content {
            padding: 40px 30px;
            margin-top: 30px;
        }

        .terms-section h2 {
            font-size: 1.6rem;
        }
    }
</style>

<!-- Hero Section -->
<section class="terms-hero">
    <div class="container">
        <div class="row">
            <div class="col-lg-8 mx-auto text-center">
                <h1 class="display-4 fw-bold mb-4"><?= i18n::__('terms_title', 'frontend') ?></h1>
                <p class="lead"><?= i18n::__('terms_subtitle', 'frontend') ?></p>
            </div>
        </div>
    </div>
</section>

<!-- Terms Content -->
<section class="terms-section">
    <div class="container">
        <div class="row">
            <div class="col-lg-10 mx-auto">
                <div class="terms-content">

                    <h2><?= i18n::__('terms_general', 'frontend') ?></h2>
                    <p><?= i18n::__('terms_general_text', 'frontend') ?></p>

                    <div class="info-box">
                        <p><strong><?= i18n::__('terms_acceptance_note', 'frontend') ?></strong></p>
                    </div>

                    <h3><?= i18n::__('terms_definitions', 'frontend') ?></h3>
                    <ul>
                        <li><strong><?= i18n::__('terms_def_company', 'frontend') ?>:</strong> <?= i18n::__('terms_def_company_text', 'frontend') ?></li>
                        <li><strong><?= i18n::__('terms_def_client', 'frontend') ?>:</strong> <?= i18n::__('terms_def_client_text', 'frontend') ?></li>
                        <li><strong><?= i18n::__('terms_def_vehicle', 'frontend') ?>:</strong> <?= i18n::__('terms_def_vehicle_text', 'frontend') ?></li>
                        <li><strong><?= i18n::__('terms_def_rental', 'frontend') ?>:</strong> <?= i18n::__('terms_def_rental_text', 'frontend') ?></li>
                    </ul>

                    <h3><?= i18n::__('terms_rental_conditions', 'frontend') ?></h3>
                    <p><?= i18n::__('terms_rental_conditions_text', 'frontend') ?></p>
                    <ol>
                        <li><?= i18n::__('terms_condition_age', 'frontend') ?></li>
                        <li><?= i18n::__('terms_condition_license', 'frontend') ?></li>
                        <li><?= i18n::__('terms_condition_documents', 'frontend') ?></li>
                        <li><?= i18n::__('terms_condition_deposit', 'frontend') ?></li>
                    </ol>

                    <h3><?= i18n::__('terms_responsibilities', 'frontend') ?></h3>
                    <p><?= i18n::__('terms_responsibilities_text', 'frontend') ?></p>

                    <h4><?= i18n::__('terms_client_obligations', 'frontend') ?></h4>
                    <ul>
                        <li><?= i18n::__('terms_obligation_care', 'frontend') ?></li>
                        <li><?= i18n::__('terms_obligation_fuel', 'frontend') ?></li>
                        <li><?= i18n::__('terms_obligation_fines', 'frontend') ?></li>
                        <li><?= i18n::__('terms_obligation_damage', 'frontend') ?></li>
                    </ul>

                    <div class="warning-box">
                        <p><strong><?= i18n::__('terms_important_warning', 'frontend') ?></strong></p>
                    </div>

                    <h3><?= i18n::__('terms_prohibited_use', 'frontend') ?></h3>
                    <p><?= i18n::__('terms_prohibited_text', 'frontend') ?></p>
                    <ul>
                        <li><?= i18n::__('terms_prohibited_racing', 'frontend') ?></li>
                        <li><?= i18n::__('terms_prohibited_commercial', 'frontend') ?></li>
                        <li><?= i18n::__('terms_prohibited_subletting', 'frontend') ?></li>
                        <li><?= i18n::__('terms_prohibited_offroad', 'frontend') ?></li>
                        <li><?= i18n::__('terms_prohibited_substances', 'frontend') ?></li>
                    </ul>

                    <h3><?= i18n::__('terms_payment', 'frontend') ?></h3>
                    <p><?= i18n::__('terms_payment_text', 'frontend') ?></p>

                    <h3><?= i18n::__('terms_cancellation', 'frontend') ?></h3>
                    <p><?= i18n::__('terms_cancellation_text', 'frontend') ?></p>

                    <h3><?= i18n::__('terms_insurance', 'frontend') ?></h3>
                    <p><?= i18n::__('terms_insurance_text', 'frontend') ?></p>

                    <h3><?= i18n::__('terms_liability', 'frontend') ?></h3>
                    <p><?= i18n::__('terms_liability_text', 'frontend') ?></p>

                    <h3><?= i18n::__('terms_force_majeure', 'frontend') ?></h3>
                    <p><?= i18n::__('terms_force_majeure_text', 'frontend') ?></p>

                    <h3><?= i18n::__('terms_disputes', 'frontend') ?></h3>
                    <p><?= i18n::__('terms_disputes_text', 'frontend') ?></p>

                    <h3><?= i18n::__('terms_final_provisions', 'frontend') ?></h3>
                    <p><?= i18n::__('terms_final_text', 'frontend') ?></p>

                    <div class="contact-box">
                        <h3><?= i18n::__('terms_questions_title', 'frontend') ?></h3>
                        <p><?= i18n::__('terms_questions_text', 'frontend') ?></p>
                        <a href="<?= BASE_URL ?>/index.php?page=contact" class="btn-contact"><?= i18n::__('contact_us', 'frontend') ?></a>
                    </div>

                    <p class="text-muted mt-4 small">
                        <strong><?= i18n::__('last_updated', 'frontend') ?>:</strong> <?= date('d.m.Y') ?>
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>