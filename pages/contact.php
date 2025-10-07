<?php
// /pages/contact.php

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

// Initialize i18n
require_once __DIR__ . '/../includes/i18n.php';
i18n::init();

// Handle form submission
$success = false;
$error = false;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF protection
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        $error = true;
        $errors[] = 'Invalid CSRF token';
    } else {
        // Validate form data
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $subject = trim($_POST['subject'] ?? '');
        $message = trim($_POST['message'] ?? '');

        if (empty($name)) {
            $errors[] = i18n::__('name_required', 'frontend');
        }
        if (empty($email)) {
            $errors[] = i18n::__('email_required', 'frontend');
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = i18n::__('email_invalid', 'frontend');
        }
        if (empty($subject)) {
            $errors[] = i18n::__('subject_required', 'frontend');
        }
        if (empty($message)) {
            $errors[] = i18n::__('message_required', 'frontend');
        }

        // Sprawdź zgodę na politykę prywatności
        if (empty($_POST['privacy_consent'])) {
            $errors[] = 'Musisz zaakceptować politykę prywatności.';
        }

        if (empty($errors)) {
            // Zapisz zgodę do bazy user_consents
            try {
                $db = db();
                $stmt = $db->prepare('INSERT INTO user_consents (user_id, consent_type, consent_text, given_at, ip_address, source) VALUES (NULL, :type, :text, NOW(), :ip, :source)');
                $stmt->execute([
                    ':type' => 'privacy_policy',
                    ':text' => 'Akceptacja polityki prywatności przez formularz kontaktowy',
                    ':ip' => $_SERVER['REMOTE_ADDR'] ?? '',
                    ':source' => 'contact_form'
                ]);
            } catch (Exception $e) {
                // Możesz logować błąd
            }
            $success = true;
            // ...existing code...
        } else {
            $error = true;
        }
    }
}

// Generate CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<style>
    .contact-hero {
        background: #f8f9fa;
        min-height: 300px;
        display: flex;
        align-items: center;
        color: #212529;
        position: relative;
        overflow: hidden;
    }

    .contact-hero::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="black" opacity="0.05"/><circle cx="75" cy="75" r="1" fill="black" opacity="0.05"/><circle cx="50" cy="10" r="0.5" fill="black" opacity="0.08"/><circle cx="10" cy="60" r="0.5" fill="black" opacity="0.08"/><circle cx="90" cy="40" r="0.5" fill="black" opacity="0.08"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
        opacity: 0.3;
    }

    .contact-hero .container {
        position: relative;
        z-index: 2;
    }

    .contact-hero h1,
    .contact-hero .lead {
        color: #212529;
    }

    .contact-form {
        background: white;
        border-radius: 20px;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        padding: 40px;
        margin-top: -80px;
        position: relative;
        z-index: 3;
        margin-bottom: 30px;
    }

    .faq-section {
        background: white;
        border-radius: 20px;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        padding: 40px;
        position: relative;
        z-index: 3;
    }

    .contact-info {
        background: #f8f9fa;
        border-radius: 20px;
        padding: 40px;
        height: fit-content;
        position: sticky;
        top: 20px;
    }

    .faq-item {
        border: 1px solid #e9ecef;
        border-radius: 15px;
        margin-bottom: 15px;
        overflow: hidden;
        transition: all 0.3s ease;
    }

    .faq-item:hover {
        border-color: var(--color-primary);
        box-shadow: 0 5px 15px rgba(102, 126, 234, 0.1);
    }

    .faq-question {
        background: #f8f9fa;
        padding: 20px;
        cursor: pointer;
        margin: 0;
        font-weight: 600;
        color: #333;
        display: flex;
        justify-content: space-between;
        align-items: center;
        transition: all 0.3s ease;
    }

    .faq-question:hover {
        background: #e9ecef;
    }

    .faq-question.active {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }

    .faq-answer {
        padding: 0 20px;
        max-height: 0;
        overflow: hidden;
        transition: all 0.3s ease;
        background: white;
    }

    .faq-answer.active {
        padding: 20px;
        max-height: 200px;
    }

    .faq-icon {
        font-size: 20px;
        transition: transform 0.3s ease;
    }

    .faq-icon.active {
        transform: rotate(180deg);
    }

    .contact-info-item {
        display: flex;
        align-items: center;
        margin-bottom: 30px;
        padding: 20px;
        background: white;
        border-radius: 15px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .contact-info-item:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    }

    .contact-info-item:last-child {
        margin-bottom: 0;
    }

    .contact-icon {
        width: 60px;
        height: 60px;
        background: var(--gradient-primary);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 24px;
        margin-right: 20px;
        flex-shrink: 0;
    }

    .form-floating {
        margin-bottom: 20px;
    }

    .form-floating label {
        color: #6c757d;
    }

    .form-control {
        border: 2px solid #e9ecef;
        border-radius: 15px;
        padding: 15px 20px;
        font-size: 16px;
        transition: border-color 0.3s ease, box-shadow 0.3s ease;
    }

    .form-control:focus {
        border-color: var(--color-primary);
        box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
    }

    .btn-primary {
        background: var(--gradient-primary);
        border: none;
        border-radius: 50px;
        padding: 15px 40px;
        font-weight: 600;
        font-size: 16px;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
        background: linear-gradient(135deg, #5a67d8 0%, #6b46c1 100%);
    }

    .social-links {
        display: flex;
        gap: 15px;
        margin-top: 20px;
    }

    .social-link {
        width: 50px;
        height: 50px;
        background: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        color: var(--color-primary);
        font-size: 20px;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }

    .social-link:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        color: #5a67d8;
    }

    .alert {
        border-radius: 15px;
        padding: 20px;
        margin-bottom: 30px;
        border: none;
    }

    .alert-success {
        background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        color: white;
    }

    .alert-danger {
        background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
        color: white;
    }

    @media (max-width: 768px) {

        .contact-form,
        .faq-section {
            padding: 30px 20px;
            margin-top: -60px;
        }

        .contact-info {
            padding: 30px 20px;
            margin-top: 30px;
            position: static;
        }

        .contact-info-item {
            padding: 15px;
        }

        .contact-icon {
            width: 50px;
            height: 50px;
            font-size: 20px;
            margin-right: 15px;
        }

        .faq-question,
        .faq-answer {
            padding: 15px;
        }
    }
</style>

<!-- Hero Section -->
<section class="contact-hero">
    <div class="container">
        <div class="row">
            <div class="col-lg-8 mx-auto text-center">
                <h1 class="display-4 fw-bold mb-3"><?= i18n::__('contact_us', 'frontend') ?></h1>
                <p class="lead"><?= i18n::__('contact_subtitle', 'frontend') ?></p>
            </div>
        </div>
    </div>
</section>

<!-- Contact Form and Info -->
<section class="py-5">
    <div class="container">
        <div class="row">
            <!-- Contact Form -->
            <div class="col-lg-8">
                <div class="contact-form">
                    <h2 class="h3 mb-4"><?= i18n::__('get_in_touch', 'frontend') ?></h2>

                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i>
                            <?= i18n::__('message_sent', 'frontend') ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($error && !empty($errors)): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <ul class="mb-0 ps-3">
                                <?php foreach ($errors as $err): ?>
                                    <li><?= htmlspecialchars($err) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form method="POST" id="contactForm">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="name" name="name"
                                        placeholder="<?= i18n::__('your_name', 'frontend') ?>"
                                        value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
                                    <label for="name"><?= i18n::__('your_name', 'frontend') ?></label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="email" class="form-control" id="email" name="email"
                                        placeholder="<?= i18n::__('your_email', 'frontend') ?>"
                                        value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                                    <label for="email"><?= i18n::__('your_email', 'frontend') ?></label>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="tel" class="form-control" id="phone" name="phone"
                                        placeholder="<?= i18n::__('your_phone', 'frontend') ?>"
                                        value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                                    <label for="phone"><?= i18n::__('your_phone', 'frontend') ?> (<?= i18n::__('optional', 'frontend') ?>)</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="subject" name="subject"
                                        placeholder="<?= i18n::__('subject', 'frontend') ?>"
                                        value="<?= htmlspecialchars($_POST['subject'] ?? '') ?>" required>
                                    <label for="subject"><?= i18n::__('subject', 'frontend') ?></label>
                                </div>
                            </div>
                        </div>

                        <div class="form-floating">
                            <textarea class="form-control" id="message" name="message"
                                placeholder="<?= i18n::__('message', 'frontend') ?>"
                                style="height: 120px" required><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>
                            <label for="message"><?= i18n::__('message', 'frontend') ?></label>
                        </div>

                        <div class="form-check my-3">
                            <input class="form-check-input" type="checkbox" id="privacy_consent" name="privacy_consent" value="1" required <?= isset($_POST['privacy_consent']) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="privacy_consent">
                                Akceptuję <a href="privacy-policy" target="_blank">politykę prywatności</a> i wyrażam zgodę na przetwarzanie danych w celu obsługi zapytania.
                            </label>
                        </div>
                        <div class="text-center">
                            <button type="submit" class="btn btn-theme btn-primary">
                                <i class="fas fa-paper-plane me-2"></i>
                                <?= i18n::__('send_message', 'frontend') ?>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- FAQ Section -->
                <div class="faq-section">
                    <h3 class="h4 mb-4"><?= i18n::__('faq_title', 'frontend') ?></h3>

                    <div class="faq-item">
                        <div class="faq-question" data-faq="1">
                            <span><?= i18n::__('faq_q1', 'frontend') ?></span>
                            <i class="fas fa-chevron-down faq-icon"></i>
                        </div>
                        <div class="faq-answer" data-faq-answer="1">
                            <p class="mb-0"><?= i18n::__('faq_a1', 'frontend') ?></p>
                        </div>
                    </div>

                    <div class="faq-item">
                        <div class="faq-question" data-faq="2">
                            <span><?= i18n::__('faq_q2', 'frontend') ?></span>
                            <i class="fas fa-chevron-down faq-icon"></i>
                        </div>
                        <div class="faq-answer" data-faq-answer="2">
                            <p class="mb-0"><?= i18n::__('faq_a2', 'frontend') ?></p>
                        </div>
                    </div>

                    <div class="faq-item">
                        <div class="faq-question" data-faq="3">
                            <span><?= i18n::__('faq_q3', 'frontend') ?></span>
                            <i class="fas fa-chevron-down faq-icon"></i>
                        </div>
                        <div class="faq-answer" data-faq-answer="3">
                            <p class="mb-0"><?= i18n::__('faq_a3', 'frontend') ?></p>
                        </div>
                    </div>

                    <div class="faq-item">
                        <div class="faq-question" data-faq="4">
                            <span><?= i18n::__('faq_q4', 'frontend') ?></span>
                            <i class="fas fa-chevron-down faq-icon"></i>
                        </div>
                        <div class="faq-answer" data-faq-answer="4">
                            <p class="mb-0"><?= i18n::__('faq_a4', 'frontend') ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contact Info -->
            <div class="col-lg-4">
                <div class="contact-info">
                    <h3 class="h4 mb-4"><?= i18n::__('company_info', 'frontend') ?></h3>

                    <div class="contact-info-item">
                        <div class="contact-icon">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <div>
                            <h5 class="mb-1"><?= i18n::__('address', 'frontend') ?></h5>
                            <p class="mb-0 text-muted">ul. Przykładowa 123<br>00-001 Warszawa</p>
                        </div>
                    </div>

                    <div class="contact-info-item">
                        <div class="contact-icon">
                            <i class="fas fa-phone"></i>
                        </div>
                        <div>
                            <h5 class="mb-1"><?= i18n::__('phone', 'frontend') ?></h5>
                            <p class="mb-0 text-muted">+48 123 456 789</p>
                        </div>
                    </div>

                    <div class="contact-info-item">
                        <div class="contact-icon">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <div>
                            <h5 class="mb-1"><?= i18n::__('email', 'frontend') ?></h5>
                            <p class="mb-0 text-muted">kontakt@corona-rental.pl</p>
                        </div>
                    </div>

                    <div class="contact-info-item">
                        <div class="contact-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div>
                            <h5 class="mb-1"><?= i18n::__('business_hours', 'frontend') ?></h5>
                            <p class="mb-1 text-muted"><?= i18n::__('monday_friday', 'frontend') ?>: 8:00 - 18:00</p>
                            <p class="mb-1 text-muted"><?= i18n::__('saturday', 'frontend') ?>: 9:00 - 14:00</p>
                            <p class="mb-0 text-muted"><?= i18n::__('sunday', 'frontend') ?>: <?= i18n::__('closed', 'frontend') ?></p>
                        </div>
                    </div>

                    <div class="mt-4">
                        <h5 class="mb-3"><?= i18n::__('follow_us', 'frontend') ?></h5>
                        <div class="social-links">
                            <a href="#" class="social-link" title="Facebook">
                                <i class="fab fa-facebook-f"></i>
                            </a>
                            <a href="#" class="social-link" title="Instagram">
                                <i class="fab fa-instagram"></i>
                            </a>
                            <a href="#" class="social-link" title="Twitter">
                                <i class="fab fa-twitter"></i>
                            </a>
                            <a href="#" class="social-link" title="LinkedIn">
                                <i class="fab fa-linkedin-in"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
    // Add FontAwesome for icons
    if (!document.querySelector('link[href*="fontawesome"]')) {
        const link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css';
        document.head.appendChild(link);
    }

    // Form enhancement
    document.getElementById('contactForm').addEventListener('submit', function(e) {
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;

        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i><?= i18n::__("sending", "frontend") ?>';
        submitBtn.disabled = true;

        // Re-enable after a delay (in case of validation errors)
        setTimeout(() => {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }, 3000);
    });

    // FAQ Accordion functionality
    document.querySelectorAll('.faq-question').forEach(question => {
        question.addEventListener('click', function() {
            const faqId = this.getAttribute('data-faq');
            const answer = document.querySelector(`[data-faq-answer="${faqId}"]`);
            const icon = this.querySelector('.faq-icon');

            // Close all other FAQs
            document.querySelectorAll('.faq-question').forEach(q => {
                if (q !== this) {
                    q.classList.remove('active');
                    const otherIcon = q.querySelector('.faq-icon');
                    otherIcon.classList.remove('active');
                    const otherId = q.getAttribute('data-faq');
                    const otherAnswer = document.querySelector(`[data-faq-answer="${otherId}"]`);
                    otherAnswer.classList.remove('active');
                }
            });

            // Toggle current FAQ
            this.classList.toggle('active');
            icon.classList.toggle('active');
            answer.classList.toggle('active');
        });
    });

    // Add smooth animations on scroll
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, observerOptions);

    // Observe contact info items and FAQ items
    document.querySelectorAll('.contact-info-item, .faq-item').forEach((item, index) => {
        item.style.opacity = '0';
        item.style.transform = 'translateY(30px)';
        item.style.transition = `opacity 0.6s ease ${index * 0.1}s, transform 0.6s ease ${index * 0.1}s`;
        observer.observe(item);
    });
</script>