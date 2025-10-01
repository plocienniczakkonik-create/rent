<?php
function current_user()
{
    return $_SESSION['user'] ?? null;
}
function is_logged_in()
{
    return isset($_SESSION['user']);
}
function require_role($role)
{
    if (!is_logged_in() || $_SESSION['user']['role'] !== $role) {
        header('Location: index.php?page=login');
        exit;
    }
}
