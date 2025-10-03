<?php
// Form for creating/editing an addon
require_once '../includes/db.php';
$edit = false;
$addon = [
    'id' => '',
    'name' => '',
    'type' => '',
    'price' => '',
    'unit' => '',
    'active' => 1
];
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $pdo->prepare('SELECT * FROM addons WHERE id = ?');
    $stmt->execute([$id]);
    $addon = $stmt->fetch(PDO::FETCH_ASSOC);
    $edit = true;
}
?>
<form method="post" action="addon-save.php">
    <input type="hidden" name="id" value="<?= htmlspecialchars($addon['id']) ?>">
    <div class="mb-3">
        <label for="name" class="form-label">Nazwa dodatku</label>
        <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($addon['name']) ?>" required>
    </div>
    <div class="mb-3">
        <label for="type" class="form-label">Typ rozliczenia</label>
        <select class="form-select" id="type" name="type" required>
            <option value="fixed" <?= $addon['type']=='fixed'?'selected':'' ?>>Stała kwota</option>
            <option value="per_day" <?= $addon['type']=='per_day'?'selected':'' ?>>Za dzień</option>
            <option value="per_rental" <?= $addon['type']=='per_rental'?'selected':'' ?>>Za wynajem</option>
        </select>
    </div>
    <div class="mb-3">
        <label for="price" class="form-label">Cena</label>
        <input type="number" step="0.01" class="form-control" id="price" name="price" value="<?= htmlspecialchars($addon['price']) ?>" required>
    </div>
    <div class="mb-3">
        <label for="unit" class="form-label">Jednostka</label>
        <input type="text" class="form-control" id="unit" name="unit" value="<?= htmlspecialchars($addon['unit']) ?>">
    </div>
    <div class="form-check mb-3">
        <input class="form-check-input" type="checkbox" id="active" name="active" value="1" <?= $addon['active']?'checked':'' ?>>
        <label class="form-check-label" for="active">Aktywny</label>
    </div>
    <button type="submit" class="btn btn-primary"><?= $edit ? 'Zapisz zmiany' : 'Dodaj dodatek' ?></button>
</form>
