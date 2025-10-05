<?php
// /pages/staff/settings/theme-colors.php

require_once dirname(__DIR__, 3) . '/includes/theme-config.php';

// Initialize theme config
$theme_config = new ThemeConfig();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_theme'])) {
    try {
        $settings = [
            'primary_color' => $_POST['primary_color'] ?? '#667eea',
            'secondary_color' => $_POST['secondary_color'] ?? '#764ba2',
            'success_color' => $_POST['success_color'] ?? '#198754',
            'warning_color' => $_POST['warning_color'] ?? '#ffc107',
            'danger_color' => $_POST['danger_color'] ?? '#dc3545',
            'info_color' => $_POST['info_color'] ?? '#0dcaf0',
            'gradient_enabled' => isset($_POST['gradient_enabled']),
            'gradient_start' => $_POST['gradient_start'] ?? '#667eea',
            'gradient_end' => $_POST['gradient_end'] ?? '#764ba2',
            'gradient_direction' => $_POST['gradient_direction'] ?? '45deg'
        ];

        if ($theme_config->saveCustomSettings($settings)) {
            $success_message = __('theme_settings_saved', 'admin', 'Ustawienia motywu zostały zapisane!');
        } else {
            $error_message = __('theme_save_error', 'admin', 'Błąd podczas zapisywania ustawień motywu.');
        }
    } catch (Exception $e) {
        $error_message = __('error', 'admin', 'Błąd') . ': ' . $e->getMessage();
    }
}

// Get current settings
$current_colors = ThemeConfig::getAllColors();
$current_gradients = ThemeConfig::getAllGradients();
$current_branding = ThemeConfig::getBranding();

// Default values
$defaults = [
    'primary_color' => '#667eea',
    'secondary_color' => '#764ba2',
    'success_color' => '#198754',
    'warning_color' => '#ffc107',
    'danger_color' => '#dc3545',
    'info_color' => '#0dcaf0',
    'gradient_start' => '#667eea',
    'gradient_end' => '#764ba2',
    'gradient_direction' => '45deg',
    'gradient_enabled' => true
];

// Merge with fallbacks
$current_settings = array_merge($defaults, $current_colors, $current_gradients, [
    'gradient_enabled' => !empty($current_gradients['gradient_start'])
]);
?>

<style>
    .gradient-preview {
        width: 100%;
        height: 60px;
        border-radius: 8px;
        border: 2px solid #e9ecef;
        margin-top: 10px;
    }

    .card {
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        border: 1px solid #e9ecef;
    }

    .card-body {
        padding: 1rem;
    }
</style>

<?php if (isset($success_message)): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle"></i> <?= htmlspecialchars($success_message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (isset($error_message)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle"></i> <?= htmlspecialchars($error_message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<form method="POST" class="needs-validation" novalidate>
    <!-- Kolory podstawowe -->
    <div class="row mb-4">
        <div class="col-12">
            <h5 class="mb-3">
                <i class="bi bi-palette"></i> <?= __('basic_colors', 'admin', 'Kolory podstawowe') ?>
            </h5>
        </div>

        <div class="col-lg-6 mb-3">
            <div class="card">
                <div class="card-body">
                    <label for="primary_color" class="form-label fw-semibold">
                        <?= __('primary_color', 'admin', 'Kolor główny') ?>
                    </label>
                    <div class="d-flex align-items-center gap-3">
                        <input type="color" class="form-control form-control-color" id="primary_color" name="primary_color"
                            value="<?= htmlspecialchars($current_settings['primary_color']) ?>" style="width: 60px; height: 40px;">
                        <span class="color-preview" style="background-color: <?= htmlspecialchars($current_settings['primary_color']) ?>; width: 40px; height: 40px; border-radius: 8px; border: 2px solid #dee2e6;"></span>
                        <small class="text-muted">Wybierz kolor</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6 mb-3">
            <div class="card">
                <div class="card-body">
                    <label for="secondary_color" class="form-label fw-semibold">
                        <?= __('secondary_color', 'admin', 'Kolor drugorzędny') ?>
                    </label>
                    <div class="d-flex align-items-center gap-3">
                        <input type="color" class="form-control form-control-color" id="secondary_color" name="secondary_color"
                            value="<?= htmlspecialchars($current_settings['secondary_color']) ?>" style="width: 60px; height: 40px;">
                        <span class="color-preview" style="background-color: <?= htmlspecialchars($current_settings['secondary_color']) ?>; width: 40px; height: 40px; border-radius: 8px; border: 2px solid #dee2e6;"></span>
                        <small class="text-muted">Wybierz kolor</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6 mb-3">
            <div class="card">
                <div class="card-body">
                    <label for="success_color" class="form-label fw-semibold">
                        <?= __('success_color', 'admin', 'Kolor sukcesu') ?>
                    </label>
                    <div class="d-flex align-items-center gap-3">
                        <input type="color" class="form-control form-control-color" id="success_color" name="success_color"
                            value="<?= htmlspecialchars($current_settings['success_color']) ?>" style="width: 60px; height: 40px;">
                        <span class="color-preview" style="background-color: <?= htmlspecialchars($current_settings['success_color']) ?>; width: 40px; height: 40px; border-radius: 8px; border: 2px solid #dee2e6;"></span>
                        <small class="text-muted">Wybierz kolor</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6 mb-3">
            <div class="card">
                <div class="card-body">
                    <label for="warning_color" class="form-label fw-semibold">
                        <?= __('warning_color', 'admin', 'Kolor ostrzeżenia') ?>
                    </label>
                    <div class="d-flex align-items-center gap-3">
                        <input type="color" class="form-control form-control-color" id="warning_color" name="warning_color"
                            value="<?= htmlspecialchars($current_settings['warning_color']) ?>" style="width: 60px; height: 40px;">
                        <span class="color-preview" style="background-color: <?= htmlspecialchars($current_settings['warning_color']) ?>; width: 40px; height: 40px; border-radius: 8px; border: 2px solid #dee2e6;"></span>
                        <small class="text-muted">Wybierz kolor</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6 mb-3">
            <div class="card">
                <div class="card-body">
                    <label for="danger_color" class="form-label fw-semibold">
                        <?= __('danger_color', 'admin', 'Kolor błędu') ?>
                    </label>
                    <div class="d-flex align-items-center gap-3">
                        <input type="color" class="form-control form-control-color" id="danger_color" name="danger_color"
                            value="<?= htmlspecialchars($current_settings['danger_color']) ?>" style="width: 60px; height: 40px;">
                        <span class="color-preview" style="background-color: <?= htmlspecialchars($current_settings['danger_color']) ?>; width: 40px; height: 40px; border-radius: 8px; border: 2px solid #dee2e6;"></span>
                        <small class="text-muted">Wybierz kolor</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6 mb-3">
            <div class="card">
                <div class="card-body">
                    <label for="info_color" class="form-label fw-semibold">
                        <?= __('info_color', 'admin', 'Kolor informacyjny') ?>
                    </label>
                    <div class="d-flex align-items-center gap-3">
                        <input type="color" class="form-control form-control-color" id="info_color" name="info_color"
                            value="<?= htmlspecialchars($current_settings['info_color']) ?>" style="width: 60px; height: 40px;">
                        <span class="color-preview" style="background-color: <?= htmlspecialchars($current_settings['info_color']) ?>; width: 40px; height: 40px; border-radius: 8px; border: 2px solid #dee2e6;"></span>
                        <small class="text-muted">Wybierz kolor</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <hr>

    <!-- Ustawienia gradientu -->
    <div class="row mb-4">
        <div class="col-12">
            <h5 class="mb-3">
                <i class="bi bi-rainbow"></i> <?= __('gradient_settings', 'admin', 'Ustawienia gradientu') ?>
            </h5>
        </div>

        <div class="col-12 mb-3">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="gradient_enabled" name="gradient_enabled"
                    <?= $current_settings['gradient_enabled'] ? 'checked' : '' ?>>
                <label class="form-check-label" for="gradient_enabled">
                    <?= __('enable_gradient', 'admin', 'Włącz gradient dla kolorów głównych') ?>
                </label>
            </div>
        </div>

        <div id="gradient_settings" style="<?= $current_settings['gradient_enabled'] ? '' : 'display: none;' ?>">
            <div class="col-md-4 mb-3">
                <label for="gradient_start" class="form-label">
                    <?= __('gradient_start', 'admin', 'Kolor początkowy gradientu') ?>
                </label>
                <input type="color" class="form-control form-control-color" id="gradient_start" name="gradient_start"
                    value="<?= htmlspecialchars($current_settings['gradient_start']) ?>">
            </div>

            <div class="col-md-4 mb-3">
                <label for="gradient_end" class="form-label">
                    <?= __('gradient_end', 'admin', 'Kolor końcowy gradientu') ?>
                </label>
                <input type="color" class="form-control form-control-color" id="gradient_end" name="gradient_end"
                    value="<?= htmlspecialchars($current_settings['gradient_end']) ?>">
            </div>

            <div class="col-md-4 mb-3">
                <label for="gradient_direction" class="form-label">
                    <?= __('gradient_direction', 'admin', 'Kierunek gradientu') ?>
                </label>
                <select class="form-select" id="gradient_direction" name="gradient_direction">
                    <option value="45deg" <?= $current_settings['gradient_direction'] === '45deg' ? 'selected' : '' ?>>45° (ukośny)</option>
                    <option value="90deg" <?= $current_settings['gradient_direction'] === '90deg' ? 'selected' : '' ?>>90° (pionowy)</option>
                    <option value="0deg" <?= $current_settings['gradient_direction'] === '0deg' ? 'selected' : '' ?>>0° (poziomy)</option>
                    <option value="135deg" <?= $current_settings['gradient_direction'] === '135deg' ? 'selected' : '' ?>>135° (ukośny odwrócony)</option>
                </select>
            </div>

            <div class="col-12">
                <label class="form-label"><?= __('gradient_preview', 'admin', 'Podgląd gradientu') ?></label>
                <div id="gradient_preview" class="gradient-preview" style="width: 100%; height: 60px; border-radius: 8px; border: 2px solid #e9ecef; margin-top: 10px; background: linear-gradient(<?= htmlspecialchars($current_settings['gradient_direction']) ?>, <?= htmlspecialchars($current_settings['gradient_start']) ?>, <?= htmlspecialchars($current_settings['gradient_end']) ?>);"></div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center">
        <button type="submit" name="save_theme" class="btn btn-primary">
            <i class="bi bi-check-lg"></i> <?= __('save_settings', 'admin', 'Zapisz ustawienia') ?>
        </button>

        <button type="button" class="btn btn-outline-secondary" id="reset_defaults_btn">
            <i class="bi bi-arrow-clockwise"></i> <?= __('reset_defaults', 'admin', 'Przywróć domyślne') ?>
        </button>
    </div>
</form>

<script>
    function updateColorPreview(input, preview) {
        if (preview) {
            preview.style.backgroundColor = input.value;
        }
    }

    function toggleGradientSettings() {
        const enabled = document.getElementById('gradient_enabled');
        const settings = document.getElementById('gradient_settings');
        if (enabled && settings) {
            settings.style.display = enabled.checked ? 'block' : 'none';
            if (enabled.checked) {
                updateGradientPreview();
            }
        }
    }

    function updateGradientPreview() {
        const start = document.getElementById('gradient_start');
        const end = document.getElementById('gradient_end');
        const direction = document.getElementById('gradient_direction');
        const preview = document.getElementById('gradient_preview');

        if (start && end && direction && preview) {
            preview.style.background = `linear-gradient(${direction.value}, ${start.value}, ${end.value})`;
        }
    }

    function resetToDefaults() {
        if (confirm('<?= __('confirm_reset_defaults', 'admin', 'Czy na pewno chcesz przywrócić domyślne ustawienia kolorów?') ?>')) {
            const elements = {
                'primary_color': '#667eea',
                'secondary_color': '#764ba2',
                'success_color': '#198754',
                'warning_color': '#ffc107',
                'danger_color': '#dc3545',
                'info_color': '#0dcaf0',
                'gradient_start': '#667eea',
                'gradient_end': '#764ba2',
                'gradient_direction': '45deg'
            };

            // Update form elements
            Object.keys(elements).forEach(id => {
                const element = document.getElementById(id);
                if (element) {
                    element.value = elements[id];
                }
            });

            const gradientCheckbox = document.getElementById('gradient_enabled');
            if (gradientCheckbox) {
                gradientCheckbox.checked = true;
            }

            // Update previews
            const colors = ['#667eea', '#764ba2', '#198754', '#ffc107', '#dc3545', '#0dcaf0'];
            document.querySelectorAll('.color-preview').forEach((preview, index) => {
                if (index < colors.length) {
                    preview.style.backgroundColor = colors[index];
                }
            });

            toggleGradientSettings();
            updateGradientPreview();
        }
    }

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize gradient preview
        setTimeout(updateGradientPreview, 100);

        // Add event listeners for color inputs
        document.querySelectorAll('input[type="color"]').forEach(input => {
            input.addEventListener('change', function() {
                try {
                    const preview = this.parentElement.querySelector('.color-preview');
                    updateColorPreview(this, preview);

                    // Update gradient preview if it's gradient colors
                    if (this.id === 'gradient_start' || this.id === 'gradient_end') {
                        updateGradientPreview();
                    }
                } catch (e) {
                    console.warn('Error updating color preview:', e);
                }
            });
        });

        // Add event listener for gradient checkbox
        const gradientCheckbox = document.getElementById('gradient_enabled');
        if (gradientCheckbox) {
            gradientCheckbox.addEventListener('change', function() {
                try {
                    toggleGradientSettings();
                } catch (e) {
                    console.warn('Error toggling gradient settings:', e);
                }
            });
        }

        // Add event listener for gradient direction
        const gradientDirection = document.getElementById('gradient_direction');
        if (gradientDirection) {
            gradientDirection.addEventListener('change', function() {
                try {
                    updateGradientPreview();
                } catch (e) {
                    console.warn('Error updating gradient preview:', e);
                }
            });
        }

        // Add event listener for reset button
        const resetBtn = document.getElementById('reset_defaults_btn');
        if (resetBtn) {
            resetBtn.addEventListener('click', function() {
                try {
                    resetToDefaults();
                } catch (e) {
                    console.warn('Error resetting to defaults:', e);
                }
            });
        }
    });
</script>