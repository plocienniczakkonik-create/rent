<?php
$BASE = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
require_once dirname(__DIR__, 2) . '/includes/db.php';
require_once dirname(__DIR__, 2) . '/includes/_helpers.php';
$settings_section = $_GET['settings_section'] ?? 'users';
$settings_subsection = $_GET['settings_subsection'] ?? 'list';

$sections = [
	'users' => [
		'title' => __('users', 'admin', 'Użytkownicy'),
		'icon' => 'bi-people',
		'subsections' => [
			'list' => __('all_users', 'admin', 'Wszyscy użytkownicy'),
			'add' => __('add_user', 'admin', 'Dodaj użytkownika')
		] + (isset($_GET['user_id']) && (int)$_GET['user_id'] > 0 ? [
			'edit' => __('edit_user', 'admin', 'Edytuj użytkownika')
		] : [])
	],
	'account' => [
		'title' => __('account', 'admin', 'Konto'),
		'icon' => 'bi-person-circle',
		'subsections' => [
			'profile' => __('account_settings', 'admin', 'Ustawienia konta')
		]
	],
	'payments' => [
		'title' => __('payments', 'admin', 'Płatności'),
		'icon' => 'bi-credit-card',
		'subsections' => [
			'general' => __('general_settings', 'admin', 'Ustawienia ogólne'),
			'gateways' => __('payment_gateways', 'admin', 'Bramki płatności')
		]
	],
	'shop' => [
		'title' => __('shop', 'admin', 'Sklep'),
		'icon' => 'bi-shop',
		'subsections' => [
			'general' => __('general_settings', 'admin', 'Ustawienia ogólne'),
			'integrations' => __('integrations', 'admin', 'Integracje')
		]
	],
	'theme' => [
		'title' => __('theme_appearance', 'admin', 'Wygląd i kolory'),
		'icon' => 'bi-palette',
		'subsections' => [
			'colors' => __('theme_colors', 'admin', 'Kolory i motyw'),
			'branding' => __('theme_branding', 'admin', 'Branding')
		]
	],
	'email' => [
		'title' => __('email', 'admin', 'Email'),
		'icon' => 'bi-envelope',
		'subsections' => [
			'templates' => __('email_templates', 'admin', 'Szablony emaili'),
			'smtp' => __('smtp_configuration', 'admin', 'Konfiguracja SMTP')
		]
	],
	'gdpr' => [
		'title' => 'RODO / GDPR',
		'icon' => 'bi-shield-lock',
		'subsections' => [
			'consents' => __('gdpr_consents', 'admin', 'Zgody użytkowników'),
			'requests' => __('gdpr_requests', 'admin', 'Żądania RODO'),
			'audit' => __('gdpr_audit', 'admin', 'Historia audytu RODO'),
			'banner' => 'Baner cookies/RODO'
		]
	],
];


function settings_link($section, $subsection, $label, $current_section, $current_subsection) {
	$BASE = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
	$active = ($section === $current_section && $subsection === $current_subsection) ? 'active' : '';
	$href = $BASE . '/index.php?page=dashboard-staff&section=settings&settings_section=' . $section . '&settings_subsection=' . $subsection . '#pane-settings';
	return '<a href="' . htmlspecialchars($href) . '" class="list-group-item list-group-item-action ' . $active . '">' . htmlspecialchars($label) . '</a>';
}

?>
<style>
	.settings-container {
		display: flex;
		gap: 1.5rem;
		min-height: 600px;
	}
	.settings-sidebar {
		width: 260px;
		flex-shrink: 0;
		position: relative;
		z-index: 10;
	}
	.settings-sidebar .list-group {
		position: relative;
		z-index: 11;
	}
	.settings-sidebar .list-group-item {
		pointer-events: auto !important;
		cursor: pointer !important;
		position: relative;
		z-index: 12;
		border: none;
		border-radius: 0;
		background: none;
		color: #343a40;
		font-size: 1rem;
		padding: 0.5rem 1.25rem;
		transition: background 0.15s;
	}
	.settings-section-title {
		font-size: 0.875rem;
		font-weight: 700;
		color: #343a40;
		text-transform: uppercase;
		letter-spacing: 0.5px;
		margin-bottom: 0.5rem;
		padding: 0.75rem 1rem 0.25rem;
	}
	.list-group-item.active {
		background: linear-gradient(90deg, var(--color-primary, #6366f1) 0%, var(--color-primary-dark, #4338ca) 100%);
		color: #fff !important;
		font-weight: 600;
		border-left: 4px solid var(--color-primary, #6366f1);
		border-radius: 0 4px 4px 0;
		position: relative;
	}
	.list-group-item.active::after {
		content: '';
		position: absolute;
		top: 0;
		left: 0;
		width: 4px;
		height: 100%;
		background: var(--color-primary, #6366f1);
		border-radius: 4px 0 0 4px;
	}
	.list-group-item:hover:not(.active) {
		background-color: #f8f9fa;
		color: #343a40;
	}
	@media (max-width: 768px) {
		.settings-container {
			flex-direction: column;
		}
		.settings-sidebar {
			width: 100%;
		}
	}
</style>
<div class="card section-settings">
	<div class="card-header d-flex align-items-center justify-content-between" style="background: var(--gradient-primary, linear-gradient(90deg,#6366f1,#4338ca)); color: white; border-bottom: 1px solid var(--color-primary-dark,#4338ca);">
		<h2 class="h6 mb-0">
			<i class="<?= $sections[$settings_section]['icon'] ?> me-2"></i>
			<?= $sections[$settings_section]['title'] ?>
		</h2>
	</div>
	<div class="card-body">
		<div class="settings-container">
			<!-- Boczne menu -->
			<div class="settings-sidebar">
				<div class="list-group">
					<?php foreach ($sections as $section_key => $section_data): ?>
						<div class="settings-section-title">
							<i class="<?= $section_data['icon'] ?>"></i> <?= $section_data['title'] ?>
						</div>
						<?php foreach ($section_data['subsections'] as $subsection_key => $subsection_title): ?>
							<?= settings_link($section_key, $subsection_key, $subsection_title, $settings_section, $settings_subsection) ?>
						<?php endforeach; ?>
					<?php endforeach; ?>
				</div>
			</div>
			<!-- Główna zawartość -->
			<div class="settings-content" style="flex:1;min-width:0;overflow-x:auto;position:relative;z-index:5;">
				<div class="card">
					<div class="card-body">
						<?php
						$section_file = __DIR__ . '/settings/' . $settings_section . '-' . $settings_subsection . '.php';
						if (file_exists($section_file)) {
							include $section_file;
						} else {
							echo '<div class="alert alert-info">Wybierz sekcję ustawień z menu po lewej.</div>';
						}
						?>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>