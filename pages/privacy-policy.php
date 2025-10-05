<?php
// /pages/privacy-policy.php

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

// Initialize i18n
require_once __DIR__ . '/../includes/i18n.php';
i18n::init();

?>

<style>
    .privacy-hero {
        background: #f8f9fa;
        min-height: 40vh;
        display: flex;
        align-items: center;
        color: #212529;
        position: relative;
        overflow: hidden;
        margin-bottom: -100px;
    }

    .privacy-hero::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="black" opacity="0.05"/><circle cx="75" cy="75" r="1" fill="black" opacity="0.05"/><circle cx="50" cy="10" r="0.5" fill="black" opacity="0.08"/><circle cx="10" cy="60" r="0.5" fill="black" opacity="0.08"/><circle cx="90" cy="40" r="0.5" fill="black" opacity="0.08"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
        opacity: 0.3;
    }

    .privacy-hero .container {
        position: relative;
        z-index: 2;
    }

    .privacy-hero h1,
    .privacy-hero .lead {
        color: #212529;
    }

    .privacy-content {
        background: white;
        border-radius: 20px;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        padding: 60px;
        margin-top: 50px;
        position: relative;
        z-index: 3;
        margin-bottom: 50px;
    }

    .privacy-section {
        padding: 80px 0;
        background: #f8f9fa;
    }

    .privacy-section h2 {
        color: #212529;
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 30px;
        position: relative;
        padding-bottom: 15px;
    }

    .privacy-section h2::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        width: 60px;
        height: 3px;
        background: var(--color-primary);
        border-radius: 2px;
    }

    .privacy-section h3 {
        color: #495057;
        font-size: 1.4rem;
        font-weight: 600;
        margin-top: 40px;
        margin-bottom: 20px;
    }

    .privacy-section p {
        color: #6c757d;
        line-height: 1.8;
        margin-bottom: 20px;
    }

    .privacy-section ul {
        color: #6c757d;
        line-height: 1.8;
        margin-bottom: 25px;
    }

    .privacy-section ul li {
        margin-bottom: 8px;
        padding-left: 10px;
    }

    .highlight-box {
        background: #e8f5e8;
        border-left: 4px solid var(--color-primary);
        padding: 25px;
        margin: 30px 0;
        border-radius: 8px;
    }

    .highlight-box p {
        margin-bottom: 0;
        color: #495057;
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
        color: var(--color-primary);
        padding: 12px 30px;
        border-radius: 50px;
        font-weight: 600;
        text-decoration: none;
        display: inline-block;
        transition: all 0.3s ease;
    }

    .btn-contact:hover {
        background: #f8f9fa;
        color: var(--color-primary);
        text-decoration: none;
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
    }

    @media (max-width: 768px) {
        .privacy-content {
            padding: 40px 30px;
            margin-top: 30px;
        }

        .privacy-section h2 {
            font-size: 1.6rem;
        }
    }
</style>

<!-- Hero Section -->
<section class="privacy-hero">
    <div class="container">
        <div class="row">
            <div class="col-lg-8 mx-auto text-center">
                <h1 class="display-4 fw-bold mb-4"><?= i18n::__('privacy_policy_title', 'frontend') ?></h1>
                <p class="lead"><?= i18n::__('privacy_policy_subtitle', 'frontend') ?></p>
            </div>
        </div>
    </div>
</section>

<!-- Privacy Policy Content -->
<section class="privacy-section">
    <div class="container">
        <div class="row">
            <div class="col-lg-10 mx-auto">
                <div class="privacy-content">

                    <h2><?= i18n::__('privacy_general_info', 'frontend') ?></h2>
                    <p><?= i18n::__('privacy_general_text', 'frontend') ?></p>

                    <div class="highlight-box">
                        <p><strong><?= i18n::__('privacy_important_note', 'frontend') ?></strong></p>
                    </div>

                    <h3><?= i18n::__('privacy_data_controller', 'frontend') ?></h3>
                    <p><?= i18n::__('privacy_controller_text', 'frontend') ?></p>
                    <ul>
                        <li><strong><?= i18n::__('company_name', 'frontend') ?>:</strong> CORONA Wypożyczalnia Samochodów</li>
                        <li><strong><?= i18n::__('address', 'frontend') ?>:</strong> ul. Przykładowa 123, 00-001 Warszawa</li>
                        <li><strong><?= i18n::__('email', 'frontend') ?>:</strong> kontakt@corona-rental.pl</li>
                        <li><strong><?= i18n::__('phone', 'frontend') ?>:</strong> +48 123 456 789</li>
                    </ul>

                    <h3><?= i18n::__('privacy_data_types', 'frontend') ?></h3>
                    <p><?= i18n::__('privacy_data_types_text', 'frontend') ?></p>
                    <ul>
                        <li><?= i18n::__('privacy_personal_data', 'frontend') ?></li>
                        <li><?= i18n::__('privacy_contact_data', 'frontend') ?></li>
                        <li><?= i18n::__('privacy_document_data', 'frontend') ?></li>
                        <li><?= i18n::__('privacy_payment_data', 'frontend') ?></li>
                        <li><?= i18n::__('privacy_usage_data', 'frontend') ?></li>
                    </ul>

                    <h3><?= i18n::__('privacy_processing_purpose', 'frontend') ?></h3>
                    <p><?= i18n::__('privacy_purpose_text', 'frontend') ?></p>
                    <ul>
                        <li><?= i18n::__('privacy_purpose_rental', 'frontend') ?></li>
                        <li><?= i18n::__('privacy_purpose_communication', 'frontend') ?></li>
                        <li><?= i18n::__('privacy_purpose_legal', 'frontend') ?></li>
                        <li><?= i18n::__('privacy_purpose_marketing', 'frontend') ?></li>
                    </ul>

                    <h3><?= i18n::__('privacy_legal_basis', 'frontend') ?></h3>
                    <p><?= i18n::__('privacy_legal_basis_text', 'frontend') ?></p>

                    <h3><?= i18n::__('privacy_data_retention', 'frontend') ?></h3>
                    <p><?= i18n::__('privacy_retention_text', 'frontend') ?></p>

                    <h3><?= i18n::__('privacy_user_rights', 'frontend') ?></h3>
                    <p><?= i18n::__('privacy_rights_text', 'frontend') ?></p>
                    <ul>
                        <li><?= i18n::__('privacy_right_access', 'frontend') ?></li>
                        <li><?= i18n::__('privacy_right_rectification', 'frontend') ?></li>
                        <li><?= i18n::__('privacy_right_erasure', 'frontend') ?></li>
                        <li><?= i18n::__('privacy_right_restriction', 'frontend') ?></li>
                        <li><?= i18n::__('privacy_right_portability', 'frontend') ?></li>
                        <li><?= i18n::__('privacy_right_objection', 'frontend') ?></li>
                    </ul>

                    <h3><?= i18n::__('privacy_cookies', 'frontend') ?></h3>
                    <p><?= i18n::__('privacy_cookies_text', 'frontend') ?></p>

                    <h3><?= i18n::__('privacy_security', 'frontend') ?></h3>
                    <p><?= i18n::__('privacy_security_text', 'frontend') ?></p>

                    <h3><?= i18n::__('privacy_changes', 'frontend') ?></h3>
                    <p><?= i18n::__('privacy_changes_text', 'frontend') ?></p>

                    <div class="contact-box">
                        <h3><?= i18n::__('privacy_contact_title', 'frontend') ?></h3>
                        <p><?= i18n::__('privacy_contact_text', 'frontend') ?></p>
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