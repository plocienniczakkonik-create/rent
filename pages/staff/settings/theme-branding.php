<?php
// /pages/staff/settings/theme-branding.php

require_once dirname(__DIR__, 3) . '/includes/theme-config.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_branding'])) {
    try {
        // Map form keys to ThemeConfig keys
        $settings = [
            'use_logo' => isset($_POST['show_logo']),
            'logo_path' => $_POST['logo_url'] ?? '',
            'logo_alt' => $_POST['logo_alt'] ?? '',
            'brand_text' => $_POST['brand_text'] ?? 'WYPOŻYCZALNIA'
        ];

        if (ThemeConfig::saveCustomSettings(['branding' => $settings])) {
            $success_message = __('branding_settings_saved', 'admin', 'Ustawienia brandingu zostały zapisane!');
        } else {
            $error_message = __('branding_save_error', 'admin', 'Błąd podczas zapisywania ustawień brandingu.');
        }
    } catch (Exception $e) {
        $error_message = __('error', 'admin', 'Błąd') . ': ' . $e->getMessage();
    }
}

// Get current branding settings
$current_branding_raw = ThemeConfig::getBranding();

// Map ThemeConfig keys to form keys and add defaults
$current_branding = [
    'show_logo' => $current_branding_raw['use_logo'] ?? false,
    'logo_url' => $current_branding_raw['logo_path'] ?? '',
    'logo_alt' => $current_branding_raw['logo_alt'] ?? '',
    'brand_text' => $current_branding_raw['brand_text'] ?? 'WYPOŻYCZALNIA'
];
?>

<style>
.logo-preview {
    max-width: 200px;
    max-height: 80px;
    border: 2px dashed #dee2e6;
    border-radius: 8px;
    padding: 10px;
    background: #f8f9fa;
    display: none;
}
.logo-preview img {
    max-width: 100%;
    max-height: 60px;
    object-fit: contain;
}
.brand-preview {
    padding: 15px;
    background: #f8f9fa;
    border-radius: 8px;
    border: 1px solid #dee2e6;
    margin-top: 15px;
}
.form-check {
    padding: 15px;
    background: #f8f9fa;
    border-radius: 8px;
    border: 1px solid #dee2e6;
}
.form-check.form-switch {
    padding: 16px;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-radius: 10px;
    border: 2px solid #e9ecef;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 12px;
}
.form-check.form-switch:hover {
    border-color: var(--color-primary, #6366f1);
    box-shadow: 0 2px 8px rgba(99, 102, 241, 0.1);
}
.form-check-input {
    width: 2em !important;
    height: 1em !important;
    border-radius: 1em;
    background-color: #dee2e6;
    border: 2px solid #adb5bd;
    cursor: pointer;
    transition: all 0.3s ease;
    margin: 0 !important;
    flex-shrink: 0;
}
.form-check-input:checked {
    background-color: var(--color-primary, #6366f1) !important;
    border-color: var(--color-primary, #6366f1) !important;
    box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.2);
}
.form-check-input:focus {
    box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.3);
}
.form-check-label {
    font-weight: 500;
    margin: 0;
    cursor: pointer;
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
    <div class="row">
        <div class="col-lg-8">
            <!-- Ustawienia logo -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-image"></i> <?= __('logo_settings', 'admin', 'Ustawienia logo') ?>
                    </h5>
                </div>
                <div class="card-body">
                    <div class="p-3 mb-4 border rounded" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);">
                        <div class="d-flex align-items-center gap-3">
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input" type="checkbox" id="show_logo" name="show_logo" 
                                       <?= $current_branding['show_logo'] ? 'checked' : '' ?>>
                                <label class="form-check-label fw-semibold" for="show_logo">
                                    <?= __('show_logo', 'admin', 'Wyświetlaj logo zamiast tekstu') ?>
                                </label>
                            </div>
                        </div>
                        <div class="form-text mt-2 ms-0">
                            <?= __('show_logo_description', 'admin', 'Gdy włączone, w nagłówku strony będzie pokazywane logo zamiast tekstu') ?>
                        </div>
                    </div>
                    
                    <div id="logo_settings" style="<?= $current_branding['show_logo'] ? '' : 'display: none;' ?>">
                        <div class="mb-3">
                            <label for="logo_url" class="form-label">
                                <?= __('logo_url', 'admin', 'URL do logo') ?>
                            </label>
                            <input type="url" class="form-control" id="logo_url" name="logo_url" 
                                   value="<?= htmlspecialchars($current_branding['logo_url']) ?>"
                                   placeholder="https://example.com/logo.png">
                            <div class="form-text">
                                <?= __('logo_url_help', 'admin', 'Wklej link do obrazka logo. Zalecany rozmiar: 200x80px lub mniejszy.') ?>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="logo_alt" class="form-label">
                                <?= __('logo_alt_text', 'admin', 'Tekst alternatywny logo') ?>
                            </label>
                            <input type="text" class="form-control" id="logo_alt" name="logo_alt" 
                                   value="<?= htmlspecialchars($current_branding['logo_alt']) ?>"
                                   placeholder="<?= __('logo_alt_placeholder', 'admin', 'Nazwa firmy - logo') ?>">
                            <div class="form-text">
                                <?= __('logo_alt_help', 'admin', 'Tekst wyświetlany gdy logo się nie załaduje oraz dla czytników ekranu.') ?>
                            </div>
                        </div>
                        
                        <div class="logo-preview" id="logo_preview">
                            <div class="text-center text-muted" id="logo_placeholder">
                                <i class="bi bi-image fs-3"></i><br>
                                <?= __('logo_preview', 'admin', 'Podgląd logo') ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Ustawienia tekstu brandingu -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-type"></i> <?= __('brand_text_settings', 'admin', 'Ustawienia tekstu brandingu') ?>
                    </h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="brand_text" class="form-label">
                            <?= __('brand_text', 'admin', 'Tekst brandingu') ?>
                        </label>
                        <input type="text" class="form-control" id="brand_text" name="brand_text" 
                               value="<?= htmlspecialchars($current_branding['brand_text']) ?>"
                               placeholder="WYPOŻYCZALNIA">
                        <div class="form-text">
                            <?= __('brand_text_help', 'admin', 'Tekst wyświetlany w nagłówku gdy logo jest wyłączone.') ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <!-- Podgląd brandingu -->
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="bi bi-eye"></i> <?= __('brand_preview', 'admin', 'Podgląd brandingu') ?>
                    </h6>
                </div>
                <div class="card-body">
                    <div class="brand-preview">
                        <div id="brand_preview_content">
                            <?= ThemeConfig::renderBrand('', false) ?>
                        </div>
                    </div>
                    <div class="mt-3">
                        <small class="text-muted">
                            <?= __('brand_preview_help', 'admin', 'Tak będzie wyglądać branding w nagłówku strony.') ?>
                        </small>
                    </div>
                </div>
            </div>
            
            <!-- Wskazówki -->
            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="bi bi-lightbulb"></i> <?= __('tips', 'admin', 'Wskazówki') ?>
                    </h6>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2">
                            <i class="bi bi-check text-success"></i>
                            <?= __('tip_logo_size', 'admin', 'Zalecany rozmiar logo: 200x80px') ?>
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-check text-success"></i>
                            <?= __('tip_logo_format', 'admin', 'Użyj PNG z przezroczystym tłem') ?>
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-check text-success"></i>
                            <?= __('tip_logo_hosting', 'admin', 'Logo może być hostowane zewnętrznie') ?>
                        </li>
                        <li class="mb-0">
                            <i class="bi bi-check text-success"></i>
                            <?= __('tip_brand_text', 'admin', 'Tekst brandingu jest zawsze dostępny jako fallback') ?>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    
    <div class="d-flex justify-content-between align-items-center mt-4">
        <button type="submit" name="save_branding" class="btn btn-primary">
            <i class="bi bi-check-lg"></i> <?= __('save_settings', 'admin', 'Zapisz ustawienia') ?>
        </button>
        
        <button type="button" class="btn btn-outline-secondary" id="reset_branding_btn">
            <i class="bi bi-arrow-clockwise"></i> <?= __('reset_defaults', 'admin', 'Przywróć domyślne') ?>
        </button>
    </div>
</form>

<script>
function toggleLogoSettings() {
    const enabled = document.getElementById('show_logo');
    const settings = document.getElementById('logo_settings');
    if (enabled && settings) {
        settings.style.display = enabled.checked ? 'block' : 'none';
        
        if (enabled.checked) {
            updateLogoPreview();
        }
        updateBrandPreview();
    }
}

function updateLogoPreview() {
    const urlInput = document.getElementById('logo_url');
    const altInput = document.getElementById('logo_alt');
    const preview = document.getElementById('logo_preview');
    const placeholder = document.getElementById('logo_placeholder');
    
    if (!urlInput || !preview || !placeholder) return;
    
    const url = urlInput.value;
    const alt = altInput ? altInput.value || 'Logo' : 'Logo';
    
    if (url) {
        preview.style.display = 'block';
        preview.innerHTML = `<img src="${url}" alt="${alt}" onerror="this.style.display='none'; document.getElementById('logo_placeholder').style.display='block';">`;
        placeholder.style.display = 'none';
    } else {
        preview.style.display = 'none';
        placeholder.style.display = 'block';
    }
}

function updateBrandPreview() {
    const showLogoInput = document.getElementById('show_logo');
    const logoUrlInput = document.getElementById('logo_url');
    const logoAltInput = document.getElementById('logo_alt');
    const brandTextInput = document.getElementById('brand_text');
    const preview = document.getElementById('brand_preview_content');
    
    if (!preview) return;
    
    const showLogo = showLogoInput ? showLogoInput.checked : false;
    const logoUrl = logoUrlInput ? logoUrlInput.value : '';
    const logoAlt = logoAltInput ? logoAltInput.value || 'Logo' : 'Logo';
    const brandText = brandTextInput ? brandTextInput.value || 'WYPOŻYCZALNIA' : 'WYPOŻYCZALNIA';
    
    if (showLogo && logoUrl) {
        preview.innerHTML = `<img src="${logoUrl}" alt="${logoAlt}" style="max-height: 40px; object-fit: contain;">`;
    } else {
        preview.innerHTML = `<span class="fw-bold">${brandText}</span>`;
    }
}

function resetToDefaults() {
    if (confirm('<?= __('confirm_reset_branding', 'admin', 'Czy na pewno chcesz przywrócić domyślne ustawienia brandingu?') ?>')) {
        const elements = {
            'show_logo': false,
            'logo_url': '',
            'logo_alt': '',
            'brand_text': 'WYPOŻYCZALNIA'
        };
        
        Object.keys(elements).forEach(id => {
            const element = document.getElementById(id);
            if (element) {
                if (element.type === 'checkbox') {
                    element.checked = elements[id];
                } else {
                    element.value = elements[id];
                }
            }
        });
        
        toggleLogoSettings();
        updateBrandPreview();
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    try {
        updateLogoPreview();
        updateBrandPreview();
    } catch (e) {
        console.warn('Error initializing branding preview:', e);
    }
    
    // Add event listeners
    const showLogoCheckbox = document.getElementById('show_logo');
    if (showLogoCheckbox) {
        showLogoCheckbox.addEventListener('change', function() {
            try {
                toggleLogoSettings();
            } catch (e) {
                console.warn('Error toggling logo settings:', e);
            }
        });
    }
    
    const logoUrlInput = document.getElementById('logo_url');
    if (logoUrlInput) {
        logoUrlInput.addEventListener('input', function() {
            try {
                updateLogoPreview();
            } catch (e) {
                console.warn('Error updating logo preview:', e);
            }
        });
        logoUrlInput.addEventListener('change', function() {
            try {
                updateBrandPreview();
            } catch (e) {
                console.warn('Error updating brand preview:', e);
            }
        });
    }
    
    const logoAltInput = document.getElementById('logo_alt');
    if (logoAltInput) {
        logoAltInput.addEventListener('input', function() {
            try {
                updateBrandPreview();
            } catch (e) {
                console.warn('Error updating brand preview:', e);
            }
        });
    }
    
    const brandTextInput = document.getElementById('brand_text');
    if (brandTextInput) {
        brandTextInput.addEventListener('input', function() {
            try {
                updateBrandPreview();
            } catch (e) {
                console.warn('Error updating brand preview:', e);
            }
        });
    }
    
    // Add event listener for reset button
    const resetBtn = document.getElementById('reset_branding_btn');
    if (resetBtn) {
        resetBtn.addEventListener('click', function() {
            try {
                resetToDefaults();
            } catch (e) {
                console.warn('Error resetting branding:', e);
            }
        });
    }
});
</script>