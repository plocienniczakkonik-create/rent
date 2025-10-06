<?php
// includes/gdpr_helpers.php
// Helpery do eksportu i anonimizacji danych użytkownika
require_once __DIR__ . '/db.php';

function gdpr_export_user($user_id)
{
    $db = db();
    $user = $db->prepare('SELECT * FROM users WHERE id = ?')->execute([$user_id]);
    $user = $db->query('SELECT * FROM users WHERE id = ' . (int)$user_id)->fetch(PDO::FETCH_ASSOC);
    $reservations = $db->query('SELECT * FROM reservations WHERE user_id = ' . (int)$user_id)->fetchAll(PDO::FETCH_ASSOC);
    $messages = $db->query("SELECT * FROM contact_messages WHERE email = " . $db->quote($user['email']))->fetchAll(PDO::FETCH_ASSOC);
    $consents = $db->query('SELECT * FROM user_consents WHERE user_id = ' . (int)$user_id)->fetchAll(PDO::FETCH_ASSOC);
    return ['user' => $user, 'reservations' => $reservations, 'messages' => $messages, 'consents' => $consents];
}

function gdpr_erase_user($user_id)
{
    $db = db();
    $user = $db->query('SELECT * FROM users WHERE id = ' . (int)$user_id)->fetch(PDO::FETCH_ASSOC);
    if (!$user) return false;
    // Anonimizacja
    $db->prepare('UPDATE users SET first_name = NULL, last_name = NULL, email = CONCAT("anon_", id, "@example.com"), phone = NULL, is_active = 0 WHERE id = ?')->execute([$user_id]);
    // Odłącz rezerwacje
    $db->prepare('UPDATE reservations SET user_id = NULL WHERE user_id = ?')->execute([$user_id]);
    // Anonimizuj wiadomości
    $db->prepare('UPDATE contact_messages SET email = CONCAT("anon_", ?, "@example.com") WHERE email = ?')->execute([$user_id, $user['email']]);
    // Zaktualizuj status żądań
    $db->prepare('UPDATE gdpr_requests SET status = "completed", processed_at = NOW() WHERE user_id = ? AND status <> "completed"')->execute([$user_id]);
    // Zapisz log audytu
    $db->prepare('INSERT INTO gdpr_audit (user_id, action, details, created_at) VALUES (?, ?, ?, NOW())')->execute([$user_id, 'erase', 'Anonimizacja użytkownika via admin']);
    return true;
}
