<?php

/**
 * Zarządzanie opłatami lokalizacyjnymi - Staff Panel
 */

// Bezpośrednie ładowanie - uproszczone
$rootDir = dirname(dirname(dirname(__DIR__)));
require_once $rootDir . '/includes/config.php';
require_once $rootDir . '/includes/db.php';
require_once $rootDir . '/auth/auth.php';
require_once $rootDir . '/includes/_helpers.php';
require_once $rootDir . '/classes/LocationFeeManager.php';
require_once $rootDir . '/classes/FleetManager.php';

$pdo = db();
$locationFeeManager = new LocationFeeManager($pdo);
$fleetManager = new FleetManager($pdo);

/**
 * Import opłat lokalizacyjnych z pliku CSV
 */
function importLocationFeesFromCSV($file, $locationFeeManager, $pdo)
{
    try {
        // Sprawdź rozmiar pliku (max 5MB)
        if ($file['size'] > 5 * 1024 * 1024) {
            return ['success' => false, 'error' => 'Plik jest za duży (maksymalnie 5MB).'];
        }

        // Sprawdź rozszerzenie
        $fileInfo = pathinfo($file['name']);
        if (strtolower($fileInfo['extension']) !== 'csv') {
            return ['success' => false, 'error' => 'Nieprawidłowy format pliku. Dozwolone tylko pliki CSV.'];
        }

        // Pobierz mapę lokalizacji (nazwa -> ID)
        $locationMap = [];
        $locations = $pdo->query("SELECT id, name, city FROM locations WHERE is_active = 1")->fetchAll();
        foreach ($locations as $location) {
            $key = strtolower($location['name'] . ' (' . $location['city'] . ')');
            $locationMap[$key] = $location['id'];
            // Dodaj również samą nazwę jako klucz
            $locationMap[strtolower($location['name'])] = $location['id'];
        }

        // Otwórz plik CSV
        $handle = fopen($file['tmp_name'], 'r');
        if (!$handle) {
            return ['success' => false, 'error' => 'Nie można otworzyć pliku CSV.'];
        }

        $imported = 0;
        $skipped = 0;
        $errors = [];
        $line = 0;

        // Sprawdź nagłówek
        $header = fgetcsv($handle, 1000, ',');
        $line++;

        if (!$header || count($header) < 3) {
            fclose($handle);
            return ['success' => false, 'error' => 'Nieprawidłowy format nagłówka. Oczekiwane kolumny: pickup_location, return_location, fee_amount, fee_type (opcjonalne)'];
        }

        // Spodziewane nagłówki
        $expectedHeaders = ['pickup_location', 'return_location', 'fee_amount'];
        $headerMap = [];

        foreach ($header as $index => $col) {
            $col = strtolower(trim($col));
            if (in_array($col, $expectedHeaders) || $col === 'fee_type') {
                $headerMap[$col] = $index;
            }
        }

        if (count(array_intersect($expectedHeaders, array_keys($headerMap))) < 3) {
            fclose($handle);
            return ['success' => false, 'error' => 'Brakuje wymaganych kolumn: pickup_location, return_location, fee_amount'];
        }

        // Przetwarzaj wiersze
        while (($data = fgetcsv($handle, 1000, ',')) !== FALSE) {
            $line++;

            if (count($data) < 3) {
                $errors[] = "Linia $line: Zbyt mało kolumn";
                $skipped++;
                continue;
            }

            $pickupName = trim($data[$headerMap['pickup_location']]);
            $returnName = trim($data[$headerMap['return_location']]);
            $feeAmount = trim($data[$headerMap['fee_amount']]);
            $feeType = isset($headerMap['fee_type']) ? trim($data[$headerMap['fee_type']]) : 'fixed';

            // Walidacja danych
            if (empty($pickupName) || empty($returnName) || empty($feeAmount)) {
                $errors[] = "Linia $line: Brakuje wymaganych danych";
                $skipped++;
                continue;
            }

            // Znajdź ID lokalizacji
            $pickupKey = strtolower($pickupName);
            $returnKey = strtolower($returnName);

            $pickupId = $locationMap[$pickupKey] ?? null;
            $returnId = $locationMap[$returnKey] ?? null;

            if (!$pickupId) {
                $errors[] = "Linia $line: Nie znaleziono lokalizacji odbioru '$pickupName'";
                $skipped++;
                continue;
            }

            if (!$returnId) {
                $errors[] = "Linia $line: Nie znaleziono lokalizacji zwrotu '$returnName'";
                $skipped++;
                continue;
            }

            // Walidacja kwoty
            $amount = floatval($feeAmount);
            if ($amount <= 0) {
                $errors[] = "Linia $line: Nieprawidłowa kwota '$feeAmount'";
                $skipped++;
                continue;
            }

            // Walidacja typu
            if (!in_array($feeType, ['fixed', 'per_km', 'per_day'])) {
                $feeType = 'fixed';
            }

            // Sprawdź czy ta sama lokalizacja
            if ($pickupId === $returnId) {
                $errors[] = "Linia $line: Lokalizacja odbioru i zwrotu nie może być taka sama";
                $skipped++;
                continue;
            }

            // Dodaj opłatę
            $result = $locationFeeManager->setLocationFee($pickupId, $returnId, $amount, $feeType);
            if ($result['success']) {
                $imported++;
            } else {
                $errors[] = "Linia $line: " . $result['error'];
                $skipped++;
            }
        }

        fclose($handle);

        $message = "Import zakończony. Zaimportowano: $imported tras, pominięto: $skipped.";
        if (!empty($errors)) {
            $message .= " Błędy: " . implode('; ', array_slice($errors, 0, 5));
            if (count($errors) > 5) {
                $message .= " (i " . (count($errors) - 5) . " więcej)";
            }
        }

        return ['success' => true, 'message' => $message];
    } catch (Exception $e) {
        return ['success' => false, 'error' => 'Błąd podczas importu: ' . $e->getMessage()];
    }
}

// Sprawdź uprawnienia staff
try {
    require_staff();
} catch (Exception $e) {
    header('Location: ../../../index.php?page=login');
    exit;
}

$pageTitle = 'Zarządzanie opłatami lokalizacyjnymi';
$currentNav = 'settings';

// Obsługa akcji
$action = $_GET['action'] ?? 'list';
$message = '';
$error = '';

if ($_POST) {
    switch ($_POST['action'] ?? '') {
        case 'add_fee':
            $pickupId = (int)($_POST['pickup_location_id'] ?? 0);
            $returnId = (int)($_POST['return_location_id'] ?? 0);
            $amount = (float)($_POST['fee_amount'] ?? 0);
            $type = $_POST['fee_type'] ?? 'fixed';

            $result = $locationFeeManager->setLocationFee($pickupId, $returnId, $amount, $type);
            if ($result['success']) {
                $message = $result['message'];
            } else {
                $error = $result['error'];
            }
            break;

        case 'import_csv':
            if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {
                $result = importLocationFeesFromCSV($_FILES['csv_file'], $locationFeeManager, $pdo);
                if ($result['success']) {
                    $message = $result['message'];
                } else {
                    $error = $result['error'];
                }
            } else {
                $error = 'Błąd podczas przesyłania pliku CSV.';
            }
            break;

        case 'update_fee':
            $pickupId = (int)($_POST['pickup_location_id'] ?? 0);
            $returnId = (int)($_POST['return_location_id'] ?? 0);
            $amount = (float)($_POST['fee_amount'] ?? 0);
            $type = $_POST['fee_type'] ?? 'fixed';

            $result = $locationFeeManager->updateLocationFee($pickupId, $returnId, $amount, $type);
            if ($result['success']) {
                $message = $result['message'];
            } else {
                $error = $result['error'];
            }
            break;

        case 'delete_fee':
            $feeId = (int)($_POST['fee_id'] ?? 0);
            if ($feeId > 0) {
                $stmt = $pdo->prepare("UPDATE location_fees SET is_active = 0 WHERE id = ?");
                if ($stmt->execute([$feeId])) {
                    $message = 'Opłata została usunięta';
                } else {
                    $error = 'Błąd podczas usuwania opłaty';
                }
            }
            break;
    }
}

// Pobierz lokalizacje
$locations = $fleetManager->getActiveLocations();

// Pobierz wszystkie opłaty
$stmt = $pdo->query("
    SELECT lf.*, 
           l1.name as pickup_name, l1.city as pickup_city,
           l2.name as return_name, l2.city as return_city
    FROM location_fees lf
    JOIN locations l1 ON lf.pickup_location_id = l1.id
    JOIN locations l2 ON lf.return_location_id = l2.id
    WHERE lf.is_active = 1
    ORDER BY l1.name, l2.name
");
$fees = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Proste HTML zamiast include
?>
<!DOCTYPE html>
<html lang="pl">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Zarządzanie opłatami lokalizacyjnymi - Panel Staff</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>

<body>
    <div class="staff-container">
        <?php include __DIR__ . '/../_helpers.php'; ?>

        <div class="container-fluid py-4">
            <div class="row">
                <div class="col-12">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h1 class="h3 mb-0"><?= htmlspecialchars($pageTitle) ?></h1>
                        <a href="../../../index.php?page=dashboard-staff&section=settings&settings_section=shop&settings_subsection=general#location-fees" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left"></i> Powrót do ustawień
                        </a>
                    </div>

                    <?php if ($message): ?>
                        <div class="alert alert-success alert-dismissible fade show">
                            <?= htmlspecialchars($message) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <?= htmlspecialchars($error) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <div class="row">
                        <!-- Lewa kolumna: Dodaj + Import -->
                        <div class="col-md-6">
                            <!-- Formularz dodawania -->
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="bi bi-plus-circle"></i> Dodaj nową opłatę
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <form method="post">
                                        <input type="hidden" name="action" value="add_fee">

                                        <div class="mb-3">
                                            <label class="form-label">Lokalizacja odbioru</label>
                                            <select class="form-select" name="pickup_location_id" required>
                                                <option value="">Wybierz...</option>
                                                <?php foreach ($locations as $loc): ?>
                                                    <option value="<?= $loc['id'] ?>">
                                                        <?= htmlspecialchars($loc['name']) ?> (<?= htmlspecialchars($loc['city']) ?>)
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Lokalizacja zwrotu</label>
                                            <select class="form-select" name="return_location_id" required>
                                                <option value="">Wybierz...</option>
                                                <?php foreach ($locations as $loc): ?>
                                                    <option value="<?= $loc['id'] ?>">
                                                        <?= htmlspecialchars($loc['name']) ?> (<?= htmlspecialchars($loc['city']) ?>)
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Kwota opłaty</label>
                                            <div class="input-group">
                                                <input type="number" class="form-control" name="fee_amount"
                                                    min="0" step="0.01" required>
                                                <span class="input-group-text">PLN</span>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Typ opłaty</label>
                                            <select class="form-select" name="fee_type">
                                                <option value="fixed">Stała kwota</option>
                                                <option value="per_km">Za kilometr</option>
                                                <option value="per_day">Za dzień</option>
                                            </select>
                                        </div>

                                        <button type="submit" class="btn btn-primary">
                                            <i class="bi bi-plus"></i> Dodaj opłatę
                                        </button>
                                    </form>
                                </div>
                            </div>

                            <!-- Import CSV -->
                            <div class="card mt-3">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="bi bi-file-earmark-spreadsheet"></i> Import CSV
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="alert alert-info">
                                        <h6><i class="bi bi-info-circle"></i> Format pliku CSV:</h6>
                                        <ul class="mb-2">
                                            <li><strong>pickup_location</strong> - nazwa lokalizacji odbioru</li>
                                            <li><strong>return_location</strong> - nazwa lokalizacji zwrotu</li>
                                            <li><strong>fee_amount</strong> - kwota opłaty (np. 150.00)</li>
                                            <li><strong>fee_type</strong> - typ: fixed/per_km/per_day (opcjonalne)</li>
                                        </ul>
                                        <small class="text-muted">
                                            Przykład: pickup_location,return_location,fee_amount,fee_type<br>
                                            Warszawa Centrum,Kraków Główny,150.00,fixed
                                        </small>
                                    </div>

                                    <form method="post" enctype="multipart/form-data">
                                        <input type="hidden" name="action" value="import_csv">

                                        <div class="mb-3">
                                            <label class="form-label">Wybierz plik CSV</label>
                                            <input type="file" class="form-control" name="csv_file" accept=".csv" required>
                                            <div class="form-text">Maksymalny rozmiar: 5MB</div>
                                        </div>

                                        <div class="d-flex justify-content-between">
                                            <button type="button" class="btn btn-outline-info btn-sm" onclick="downloadSampleCSV()">
                                                <i class="bi bi-download"></i> Przykład CSV
                                            </button>
                                            <button type="submit" class="btn btn-success">
                                                <i class="bi bi-upload"></i> Importuj
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Prawa kolumna: Lista opłat -->
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="bi bi-list"></i> Aktualne opłaty lokalizacyjne
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($fees)): ?>
                                        <div class="text-center text-muted py-4">
                                            <i class="bi bi-inbox display-4"></i>
                                            <p class="mt-2">Brak zdefiniowanych opłat lokalizacyjnych</p>
                                        </div>
                                    <?php else: ?>
                                        <div class="table-responsive">
                                            <table class="table table-striped">
                                                <thead>
                                                    <tr>
                                                        <th>Trasa</th>
                                                        <th>Opłata</th>
                                                        <th>Typ</th>
                                                        <th>Dodano</th>
                                                        <th width="150">Akcje</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($fees as $fee): ?>
                                                        <tr>
                                                            <td>
                                                                <strong><?= htmlspecialchars($fee['pickup_name']) ?></strong>
                                                                <br><small class="text-muted"><?= htmlspecialchars($fee['pickup_city']) ?></small>
                                                                <br><i class="bi bi-arrow-down text-primary"></i>
                                                                <br><strong><?= htmlspecialchars($fee['return_name']) ?></strong>
                                                                <br><small class="text-muted"><?= htmlspecialchars($fee['return_city']) ?></small>
                                                            </td>
                                                            <td>
                                                                <span class="badge bg-success">
                                                                    <?= number_format($fee['fee_amount'], 2, ',', ' ') ?> PLN
                                                                </span>
                                                            </td>
                                                            <td>
                                                                <?php
                                                                $typeLabels = [
                                                                    'fixed' => 'Stała',
                                                                    'per_km' => 'Za km',
                                                                    'per_day' => 'Za dzień'
                                                                ];
                                                                echo $typeLabels[$fee['fee_type']] ?? $fee['fee_type'];
                                                                ?>
                                                            </td>
                                                            <td>
                                                                <small class="text-muted">
                                                                    <?= date('d.m.Y H:i', strtotime($fee['created_at'])) ?>
                                                                </small>
                                                            </td>
                                                            <td>
                                                                <button class="btn btn-sm btn-outline-primary"
                                                                    onclick="editFee(<?= $fee['id'] ?>, <?= $fee['pickup_location_id'] ?>, <?= $fee['return_location_id'] ?>, <?= $fee['fee_amount'] ?>, '<?= $fee['fee_type'] ?>')">
                                                                    <i class="bi bi-pencil"></i>
                                                                </button>
                                                                <form method="post" class="d-inline"
                                                                    onsubmit="return confirm('Czy na pewno chcesz usunąć tę opłatę?')">
                                                                    <input type="hidden" name="action" value="delete_fee">
                                                                    <input type="hidden" name="fee_id" value="<?= $fee['id'] ?>">
                                                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                                                        <i class="bi bi-trash"></i>
                                                                    </button>
                                                                </form>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal edycji -->
    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edytuj opłatę lokalizacyjną</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="post">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update_fee">
                        <input type="hidden" name="pickup_location_id" id="edit_pickup_id">
                        <input type="hidden" name="return_location_id" id="edit_return_id">

                        <div class="mb-3">
                            <label class="form-label">Trasa</label>
                            <input type="text" class="form-control" id="edit_route" readonly>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Kwota opłaty</label>
                            <div class="input-group">
                                <input type="number" class="form-control" name="fee_amount"
                                    id="edit_amount" min="0" step="0.01" required>
                                <span class="input-group-text">PLN</span>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Typ opłaty</label>
                            <select class="form-select" name="fee_type" id="edit_type">
                                <option value="fixed">Stała kwota</option>
                                <option value="per_km">Za kilometr</option>
                                <option value="per_day">Za dzień</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anuluj</button>
                        <button type="submit" class="btn btn-primary">Zapisz zmiany</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editFee(id, pickupId, returnId, amount, type) {
            // Znajdź nazwy lokalizacji
            const pickupSelect = document.querySelector('select[name="pickup_location_id"]');
            const returnSelect = document.querySelector('select[name="return_location_id"]');

            const pickupName = pickupSelect.querySelector(`option[value="${pickupId}"]`).textContent;
            const returnName = returnSelect.querySelector(`option[value="${returnId}"]`).textContent;

            document.getElementById('edit_pickup_id').value = pickupId;
            document.getElementById('edit_return_id').value = returnId;
            document.getElementById('edit_route').value = `${pickupName} → ${returnName}`;
            document.getElementById('edit_amount').value = amount;
            document.getElementById('edit_type').value = type;

            new bootstrap.Modal(document.getElementById('editModal')).show();
        }

        function downloadSampleCSV() {
            // Utwórz przykładowy CSV
            const csvContent = `pickup_location,return_location,fee_amount,fee_type
Warszawa Centrum,Kraków Główny,150.00,fixed
Warszawa Centrum,Gdańsk Port,200.00,fixed
Kraków Główny,Wrocław Rynek,180.00,fixed
Poznań Plaza,Gdańsk Port,220.00,fixed
Wrocław Rynek,Poznań Plaza,75.00,fixed`;

            // Utwórz blob i link do pobrania
            const blob = new Blob([csvContent], {
                type: 'text/csv;charset=utf-8;'
            });
            const link = document.createElement('a');
            const url = URL.createObjectURL(blob);

            link.setAttribute('href', url);
            link.setAttribute('download', 'location_fees_example.csv');
            link.style.visibility = 'hidden';

            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    </script>
</body>

</html>