<?php
require_once '../includes/db.php';
$id = isset($_GET['id']) ? intval($_GET['id']) : null;
if ($id) {
    $stmt = $pdo->prepare('DELETE FROM addons WHERE id = ?');
    $stmt->execute([$id]);
}
header('Location: dashboard-staff.php?section=addons');
exit;
