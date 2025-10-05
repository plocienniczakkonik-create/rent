<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/theme-config.php';
require_once __DIR__ . '/../includes/i18n.php';
i18n::init();

$BASE = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';

// Handle form submission
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $settings = [];

    // Branding settings
    if (isset($_POST['save_branding'])) {
        $settings['branding'] = [
            'use_logo' => $_POST['use_logo'] === '1',
            'logo_path' => $_POST['logo_path'] ?? '/assets/img/logo.png',
            'brand_text' => $_POST['brand_text'] ?? 'CORONA',
            'logo_alt' => $_POST['logo_alt'] ?? 'Logo'
        ];

        if (ThemeConfig::saveCustomSettings($settings)) {
            $message = 'Ustawienia brandingu zostały zapisane pomyślnie!';
            $messageType = 'success';
        } else {
            $message = 'Błąd podczas zapisywania ustawień.';
            $messageType = 'danger';
        }
    }

    // Color settings
    if (isset($_POST['save_colors'])) {
        $settings['colors'] = [
            'primary' => $_POST['color_primary'] ?? '#667eea',
            'primary_dark' => $_POST['color_primary_dark'] ?? '#5a67d8',
            'primary_light' => $_POST['color_primary_light'] ?? '#a5b4fc',
            'secondary' => $_POST['color_secondary'] ?? '#64748b',
            'success' => $_POST['color_success'] ?? '#22c55e',
            'warning' => $_POST['color_warning'] ?? '#f59e0b',
            'danger' => $_POST['color_danger'] ?? '#ef4444',
            'info' => $_POST['color_info'] ?? '#3b82f6'
        ];

        $settings['gradients'] = [
            'primary' => $_POST['gradient_primary'] ?? 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)'
        ];

        if (ThemeConfig::saveCustomSettings($settings)) {
            $message = 'Kolory zostały zapisane pomyślnie!';
            $messageType = 'success';
        } else {
            $message = 'Błąd podczas zapisywania kolorów.';
            $messageType = 'danger';
        }
    }
}

// Get current settings
$currentBranding = ThemeConfig::getBranding();
$currentColors = ThemeConfig::getAllColors();
?>

<!DOCTYPE html>
<html lang="pl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel zarządzania motywem - CORONA</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <!-- System kolorów -->
    <link rel="stylesheet" href="<?= $BASE ?>/assets/css/theme-system.css">

    <style>
        <?= ThemeConfig::generateCSSVariables() ?>.color-preview {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            border: 2px solid #e0e0e0;
            display: inline-block;
            margin-left: 10px;
        }

        .gradient-preview {
            width: 100%;
            height: 60px;
            border-radius: 8px;
            border: 2px solid #e0e0e0;
            margin-top: 10px;
        }

        .brand-preview {
            padding: 20px;
            background: #f8f9fa;
            border-radius: 12px;
            text-align: center;
            margin-top: 15px;
        }

        .color-section {
            border: 1px solid #e0e0e0;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
        }
    </style>
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="<?= $BASE ?>/index.php">
                <i class="fas fa-arrow-left me-2"></i>
                Powrót do strony
            </a>
            <span class="navbar-text">
                <i class="fas fa-palette me-2"></i>
                Panel zarządzania motywem
            </span>
        </div>
    </nav>

    <div class="container py-5">
        <div class="row">
            <div class="col-12">
                <h1 class="mb-4">
                    <i class="fas fa-cogs me-3 text-primary"></i>
                    Zarządzanie motywem strony
                </h1>

                <?php if ($message): ?>
                    <div class="alert alert-<?= $messageType ?> alert-dismissible fade show">
                        <?= htmlspecialchars($message) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="row">
            <!-- BRANDING SECTION -->
            <div class="col-12 col-lg-6">
                <div class="color-section">
                    <h3>
                        <i class="fas fa-font me-2 text-info"></i>
                        Branding i Logo
                    </h3>

                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Typ brandingu</label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="use_logo" value="0"
                                    id="use_text" <?= !$currentBranding['use_logo'] ? 'checked' : '' ?>>
                                <label class="form-check-label" for="use_text">
                                    Używaj tekstu
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="use_logo" value="1"
                                    id="use_logo" <?= $currentBranding['use_logo'] ? 'checked' : '' ?>>
                                <label class="form-check-label" for="use_logo">
                                    Używaj logo (obraz)
                                </label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="brand_text" class="form-label">Tekst brandingu</label>
                            <input type="text" class="form-control" id="brand_text" name="brand_text"
                                value="<?= htmlspecialchars($currentBranding['brand_text']) ?>">
                        </div>

                        <div class="mb-3">
                            <label for="logo_path" class="form-label">Ścieżka do logo</label>
                            <input type="text" class="form-control" id="logo_path" name="logo_path"
                                value="<?= htmlspecialchars($currentBranding['logo_path']) ?>">
                            <div class="form-text">Np. /assets/img/logo.png</div>
                        </div>

                        <div class="mb-3">
                            <label for="logo_alt" class="form-label">Alt text dla logo</label>
                            <input type="text" class="form-control" id="logo_alt" name="logo_alt"
                                value="<?= htmlspecialchars($currentBranding['logo_alt']) ?>">
                        </div>

                        <div class="brand-preview">
                            <h5>Podgląd:</h5>
                            <?= ThemeConfig::renderBrand('', false) ?>
                        </div>

                        <button type="submit" name="save_branding" class="btn btn-theme btn-info w-100 mt-3">
                            <i class="fas fa-save me-2"></i>
                            Zapisz ustawienia brandingu
                        </button>
                    </form>
                </div>
            </div>

            <!-- COLORS SECTION -->
            <div class="col-12 col-lg-6">
                <div class="color-section">
                    <h3>
                        <i class="fas fa-palette me-2 text-primary"></i>
                        Kolory motywu
                    </h3>

                    <form method="POST">
                        <div class="mb-3">
                            <label for="color_primary" class="form-label">
                                Kolor główny (Primary)
                                <span class="color-preview" style="background-color: <?= $currentColors['primary'] ?>"></span>
                            </label>
                            <input type="color" class="form-control form-control-color" id="color_primary"
                                name="color_primary" value="<?= $currentColors['primary'] ?>">
                        </div>

                        <div class="mb-3">
                            <label for="color_secondary" class="form-label">
                                Kolor drugorzędny (Secondary)
                                <span class="color-preview" style="background-color: <?= $currentColors['secondary'] ?>"></span>
                            </label>
                            <input type="color" class="form-control form-control-color" id="color_secondary"
                                name="color_secondary" value="<?= $currentColors['secondary'] ?>">
                        </div>

                        <div class="mb-3">
                            <label for="color_success" class="form-label">
                                Kolor sukcesu (Success)
                                <span class="color-preview" style="background-color: <?= $currentColors['success'] ?>"></span>
                            </label>
                            <input type="color" class="form-control form-control-color" id="color_success"
                                name="color_success" value="<?= $currentColors['success'] ?>">
                        </div>

                        <div class="mb-3">
                            <label for="color_warning" class="form-label">
                                Kolor ostrzeżenia (Warning)
                                <span class="color-preview" style="background-color: <?= $currentColors['warning'] ?>"></span>
                            </label>
                            <input type="color" class="form-control form-control-color" id="color_warning"
                                name="color_warning" value="<?= $currentColors['warning'] ?>">
                        </div>

                        <div class="mb-3">
                            <label for="color_danger" class="form-label">
                                Kolor błędu (Danger)
                                <span class="color-preview" style="background-color: <?= $currentColors['danger'] ?>"></span>
                            </label>
                            <input type="color" class="form-control form-control-color" id="color_danger"
                                name="color_danger" value="<?= $currentColors['danger'] ?>">
                        </div>

                        <div class="mb-3">
                            <label for="gradient_primary" class="form-label">Gradient główny</label>
                            <input type="text" class="form-control" id="gradient_primary" name="gradient_primary"
                                value="<?= htmlspecialchars(ThemeConfig::getGradient('primary')) ?>">
                            <div class="gradient-preview" style="background: <?= ThemeConfig::getGradient('primary') ?>"></div>
                        </div>

                        <button type="submit" name="save_colors" class="btn btn-theme btn-primary w-100 mt-3">
                            <i class="fas fa-save me-2"></i>
                            Zapisz kolory motywu
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- PREVIEW SECTION -->
        <div class="row mt-5">
            <div class="col-12">
                <div class="color-section">
                    <h3>
                        <i class="fas fa-eye me-2 text-success"></i>
                        Podgląd buttonów
                    </h3>

                    <div class="d-flex flex-wrap gap-3 mt-3">
                        <button class="btn btn-theme btn-primary">Primary Button</button>
                        <button class="btn btn-theme btn-secondary">Secondary Button</button>
                        <button class="btn btn-theme btn-success">Success Button</button>
                        <button class="btn btn-theme btn-warning">Warning Button</button>
                        <button class="btn btn-theme btn-danger">Danger Button</button>
                        <button class="btn btn-theme btn-info">Info Button</button>
                        <button class="btn btn-theme btn-light">Light Button</button>
                        <button class="btn btn-theme btn-dark">Dark Button</button>
                    </div>

                    <div class="d-flex flex-wrap gap-3 mt-3">
                        <button class="btn btn-theme btn-primary btn-sm">Small</button>
                        <button class="btn btn-theme btn-primary">Normal</button>
                        <button class="btn btn-theme btn-primary btn-lg">Large</button>
                        <button class="btn btn-theme btn-primary btn-xl">Extra Large</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Live preview updates
        document.addEventListener('DOMContentLoaded', function() {
            const colorInputs = document.querySelectorAll('input[type="color"]');
            colorInputs.forEach(input => {
                input.addEventListener('change', function() {
                    const preview = this.parentElement.querySelector('.color-preview');
                    if (preview) {
                        preview.style.backgroundColor = this.value;
                    }
                });
            });

            // Gradient preview update
            const gradientInput = document.getElementById('gradient_primary');
            if (gradientInput) {
                gradientInput.addEventListener('input', function() {
                    const preview = this.parentElement.querySelector('.gradient-preview');
                    if (preview) {
                        try {
                            preview.style.background = this.value;
                        } catch (e) {
                            console.log('Invalid gradient syntax');
                        }
                    }
                });
            }
        });
    </script>
</body>

</html>