<?php // expects: $staff, $BASE 
?>
<div class="card">
    <div class="card-header">
        <h2 class="h6 mb-0">Ustawienia konta</h2>
    </div>
    <div class="card-body">
        <form method="post" action="pages/staff-save.php" class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Imię</label>
                <input type="text" name="first_name" class="form-control" value="<?= htmlspecialchars($staff['first_name'] ?? '') ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Nazwisko</label>
                <input type="text" name="last_name" class="form-control" value="<?= htmlspecialchars($staff['last_name'] ?? '') ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Stanowisko</label>
                <input type="text" name="job_title" class="form-control" value="<?= htmlspecialchars($staff['job_title'] ?? '') ?>" placeholder="np. Obsługa klienta / Administrator">
            </div>
            <div class="col-12">
                <label class="form-label">Zmiana hasła</label>
                <input type="password" name="new_password" class="form-control" placeholder="Pozostaw puste, aby nie zmieniać">
            </div>
            <div class="col-12 d-flex gap-2">
                <button class="btn btn-primary" type="submit">Zapisz</button>
                <a class="btn btn-outline-secondary" href="<?= $BASE ?>/index.php?page=dashboard-staff">Anuluj</a>
            </div>
        </form>
        <p class="text-muted small mb-0 mt-2">
            Zapisywanie ustawień dorobimy w kolejnym kroku (endpoint <code>pages/staff-save.php</code>).
        </p>
    </div>
</div>