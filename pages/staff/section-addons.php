<?php
// Sekcja panelu dodatków w dashboardzie-staff
require_once '../../includes/db.php';
$addons = $pdo->query('SELECT * FROM addons ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);
?>
<section class="dashboard-section">
    <h2>Dodatki</h2>
    <a href="../addon-form.php" class="btn btn-success mb-3">Dodaj dodatek</a>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Nazwa</th>
                <th>Typ rozliczenia</th>
                <th>Cena</th>
                <th>Jednostka</th>
                <th>Aktywny</th>
                <th>Akcje</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($addons as $addon): ?>
            <tr>
                <td><?= htmlspecialchars($addon['name']) ?></td>
                <td><?= htmlspecialchars($addon['type']) ?></td>
                <td><?= number_format($addon['price'], 2) ?></td>
                <td><?= htmlspecialchars($addon['unit']) ?></td>
                <td><?= $addon['active'] ? 'Tak' : 'Nie' ?></td>
                <td>
                    <a href="../addon-form.php?id=<?= $addon['id'] ?>" class="btn btn-sm btn-primary">Edytuj</a>
                    <a href="../addon-delete.php?id=<?= $addon['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Usunąć dodatek?')">Usuń</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</section>
