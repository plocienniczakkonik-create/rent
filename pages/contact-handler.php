<?php
// /pages/contact-handler.php

require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/i18n.php';

// Initialize i18n
i18n::init();

// Only handle POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/index.php?page=contact');
    exit;
}

// CSRF protection
if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
    $_SESSION['contact_error'] = 'Invalid CSRF token';
    header('Location: ' . BASE_URL . '/index.php?page=contact');
    exit;
}

$errors = [];
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$subject = trim($_POST['subject'] ?? '');
$message = trim($_POST['message'] ?? '');

// Validation
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

if (!empty($errors)) {
    $_SESSION['contact_errors'] = $errors;
    $_SESSION['contact_form_data'] = $_POST;
    header('Location: ' . BASE_URL . '/index.php?page=contact');
    exit;
}

try {
    // Save contact message to database
    $pdo = db();
    $stmt = $pdo->prepare("
        INSERT INTO contact_messages (name, email, phone, subject, message, created_at) 
        VALUES (?, ?, ?, ?, ?, NOW())
    ");

    $stmt->execute([$name, $email, $phone, $subject, $message]);

    // Set success message
    $_SESSION['contact_success'] = i18n::__('message_sent', 'frontend');

    // Clear form data
    unset($_SESSION['contact_form_data']);

    // Here you could also send email notification
    // mail($email, $subject, $message) or use proper email service

} catch (Exception $e) {
    error_log('Contact form error: ' . $e->getMessage());
    $_SESSION['contact_error'] = i18n::__('message_error', 'frontend');
}

header('Location: ' . BASE_URL . '/index.php?page=contact');
exit;
