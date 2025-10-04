<?php
// final_test.php - ostateczny test panelu pracownika
session_start();

// Auto-login
require_once 'includes/db.php';
$stmt = db()->prepare('SELECT id FROM users WHERE email = ? AND role = ?');
$stmt->execute(['test2@example.com', 'staff']);
$user_id = $stmt->fetchColumn();
if ($user_id) {
    $_SESSION['user_id'] = $user_id;
}

?>
<!DOCTYPE html>
<html lang="pl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Panel Pracownika</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .debug {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            padding: 1rem;
            margin: 1rem 0;
        }

        .success {
            color: #198754;
        }

        .error {
            color: #dc3545;
        }
    </style>
</head>

<body>
    <div class="container-xl py-4">
        <h1>🔧 Test Panel Pracownika</h1>

        <div class="debug">
            <h3>Status logowania:</h3>
            <?php
            require_once 'auth/auth.php';
            try {
                $user = current_user();
                if ($user) {
                    echo "<p class='success'>✓ Zalogowany jako: {$user['email']} (rola: {$user['role']})</p>";
                    if ($user['role'] === 'staff') {
                        echo "<p class='success'>✓ Ma uprawnienia staff</p>";
                    } else {
                        echo "<p class='error'>✗ Brak uprawnień staff</p>";
                    }
                } else {
                    echo "<p class='error'>✗ Nie zalogowany</p>";
                }
            } catch (Exception $e) {
                echo "<p class='error'>✗ Błąd: {$e->getMessage()}</p>";
            }
            ?>
        </div>

        <div class="debug">
            <h3>Test ładowania sekcji:</h3>
            <?php
            require_once 'includes/db.php';

            // Przygotowanie danych
            require_once 'pages/staff/_helpers.php'; // Dodajemy helpers

            $BASE = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';

            $products = db()->query("SELECT id, name, sku, price, stock, status, category FROM products ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
            $productNameById = [];
            $productNameBySku = [];
            $classLabel = [];
            foreach ($products as $p) {
                $productNameById[(int)$p['id']] = (string)$p['name'];
                $productNameBySku[(string)$p['sku']] = (string)$p['name'];
                if (!empty($p['category'])) {
                    $code = (string)$p['category'];
                    $classLabel[$code] = 'Klasa ' . strtoupper($code);
                }
            }
            $promos = db()->query("SELECT id, name, code, is_active, scope_type, scope_value, valid_from, valid_to, min_days, discount_type, discount_val FROM promotions ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
            $reports = ['revenue_today' => 457.00, 'orders_today' => 6, 'top_product' => 'Toyota Corolla'];

            echo "<p>Produkty: " . count($products) . " ✓</p>";
            echo "<p>Promocje: " . count($promos) . " ✓</p>";
            ?>
        </div>

        <h2>🎯 Panel Pracownika (rzeczywisty)</h2>

        <ul class="nav nav-pills mb-3" id="testTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="test-tab-products" data-bs-toggle="pill" data-bs-target="#test-pane-products" type="button" role="tab">Produkty</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="test-tab-promos" data-bs-toggle="pill" data-bs-target="#test-pane-promos" type="button" role="tab">Promocje</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="test-tab-reports" data-bs-toggle="pill" data-bs-target="#test-pane-reports" type="button" role="tab">Raporty</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="test-tab-dicts" data-bs-toggle="pill" data-bs-target="#test-pane-dicts" type="button" role="tab">Słowniki</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="test-tab-upcoming" data-bs-toggle="pill" data-bs-target="#test-pane-upcoming" type="button" role="tab">Najbliższe</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="test-tab-settings" data-bs-toggle="pill" data-bs-target="#test-pane-settings" type="button" role="tab">Ustawienia</button>
            </li>
        </ul>

        <div class="tab-content" id="testTabsContent">
            <div class="tab-pane fade show active" id="test-pane-products" role="tabpanel">
                <div class="debug mb-3">
                    <strong>Sekcja: Produkty</strong>
                </div>
                <?php include 'pages/staff/section-products.php'; ?>
            </div>

            <div class="tab-pane fade" id="test-pane-promos" role="tabpanel">
                <div class="debug mb-3">
                    <strong>Sekcja: Promocje</strong>
                </div>
                <?php include 'pages/staff/section-promos.php'; ?>
            </div>

            <div class="tab-pane fade" id="test-pane-reports" role="tabpanel">
                <div class="debug mb-3">
                    <strong>Sekcja: Raporty</strong>
                </div>
                <?php include 'pages/staff/section-reports.php'; ?>
            </div>

            <div class="tab-pane fade" id="test-pane-dicts" role="tabpanel">
                <div class="debug mb-3">
                    <strong>Sekcja: Słowniki</strong>
                </div>
                <?php include 'pages/staff/section-dicts.php'; ?>
            </div>

            <div class="tab-pane fade" id="test-pane-upcoming" role="tabpanel">
                <div class="debug mb-3">
                    <strong>Sekcja: Najbliższe terminy</strong>
                </div>
                <?php include 'pages/staff/section-upcoming.php'; ?>
            </div>

            <div class="tab-pane fade" id="test-pane-settings" role="tabpanel">
                <div class="debug mb-3">
                    <strong>Sekcja: Ustawienia</strong>
                </div>
                <?php
                $staff = $user; // Przekazujemy dane staff do sekcji ustawień
                include 'pages/staff/section-settings.php';
                ?>
            </div>
        </div>

        <div class="debug mt-4">
            <h3>Linki testowe:</h3>
            <p><a href="index.php?page=dashboard-staff" class="btn btn-primary">Oryginalny Panel Pracownika</a></p>
            <p><a href="index.php?page=login" class="btn btn-secondary">Strona logowania</a></p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🔧 Test dashboard załadowany');

            // Test przełączania zakładek
            document.querySelectorAll('[data-bs-toggle="pill"]').forEach(function(tab) {
                tab.addEventListener('shown.bs.tab', function(event) {
                    console.log('📋 Przełączono na zakładkę:', event.target.getAttribute('data-bs-target'));
                });
            });
        });
    </script>
</body>

</html>