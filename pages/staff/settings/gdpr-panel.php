<?php
// Panel RODO: zakładki Zgody, Żądania, Audyt
$tab = $_GET['gdpr_tab'] ?? 'consents';
$tabs = [
    'consents' => 'Zgody użytkowników',
    'requests' => 'Żądania RODO',
    'audit' => 'Historia audytu'
];
?>
<style>
    .gdpr-tabs {
        display: flex;
        gap: 0.5rem;
        margin-bottom: 1.5rem;
    }
    .gdpr-tab {
        padding: 0.5rem 1.5rem;
        border: none;
        border-radius: 8px 8px 0 0;
        background: #f8f9fa;
        color: #343a40;
        font-weight: 500;
        font-size: 1rem;
        transition: background 0.15s, color 0.15s;
        cursor: pointer;
        text-decoration: none;
        position: relative;
        top: 2px;
    }
    .gdpr-tab.active, .gdpr-tab:focus, .gdpr-tab:hover {
        background: linear-gradient(90deg, var(--color-primary, #6366f1) 0%, var(--color-primary-dark, #4338ca) 100%);
        color: #fff;
        font-weight: 700;
        z-index: 2;
    }
</style>
<div class="gdpr-tabs">
    <?php foreach ($tabs as $key => $label): ?>
        <a class="gdpr-tab<?= $tab === $key ? ' active' : '' ?>" href="<?= $BASE ?>/index.php?page=dashboard-staff&section=settings&settings_section=gdpr&settings_subsection=panel&gdpr_tab=<?= $key ?>#pane-settings"><?= $label ?></a>
    <?php endforeach; ?>
</div>
<div>
    <?php
    if ($tab === 'consents') {
        include __DIR__ . '/gdpr-consents.php';
    } elseif ($tab === 'requests') {
        include __DIR__ . '/gdpr-requests.php';
    } elseif ($tab === 'audit') {
        include __DIR__ . '/gdpr-audit.php';
    }
    ?>
</div>
