<?php
require 'includes/db.php';
$users = db()->query('SELECT id, email, role, is_active FROM users WHERE role="staff"')->fetchAll();
echo "=== STAFF USERS ===\n";
foreach ($users as $u) {
    echo $u['id'] . ': ' . $u['email'] . ' (' . $u['role'] . ') active:' . $u['is_active'] . "\n";
}
echo "=== END ===\n";
