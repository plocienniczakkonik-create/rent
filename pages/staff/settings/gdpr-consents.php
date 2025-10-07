<?php
require_once dirname(__DIR__, 3) . '/includes/db.php';
$db = db();
$consents = $db->query('SELECT uc.*, u.email FROM user_consents uc LEFT JOIN users u ON uc.user_id = u.id ORDER BY uc.given_at DESC')->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="card">
    <div class="card-header" style="background: var(--gradient-primary); color: #fff;">
        <h3 class="h6 mb-0"><i class="bi bi-shield-lock me-2"></i>Zgody użytkowników (RODO/GDPR)</h3>
    </div>
    <div class="card-body">
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Użytkownik</th>
                    <th>Typ zgody</th>
                    <th>Treść zgody</th>
                    <th>Data udzielenia</th>
                    <th>Data cofnięcia</th>
                    <th>IP</th>
                    <th>Źródło</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($consents as $c): ?>
                    <tr>
                        <td><?= $c['id'] ?></td>
                        <td><?= htmlspecialchars($c['email']) ?></td>
                        <td><?php
                            $typeMap = [
                                'privacy_policy' => 'Polityka prywatności',
                                'marketing' => 'Zgoda marketingowa',
                                'terms' => 'Regulamin',
                                'profiling' => 'Profilowanie',
                            ];
                            echo $typeMap[$c['consent_type']] ?? htmlspecialchars(ucfirst(str_replace('_', ' ', $c['consent_type'])));
                            ?></td>
                        <td><?php
                            $text = $c['consent_text'];
                            if (mb_detect_encoding($text, 'UTF-8', true) === false) {
                                $text = mb_convert_encoding($text, 'UTF-8', 'ISO-8859-2,Windows-1250,ASCII');
                            }
                            echo htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                            ?></td>
                        <td><?= $c['given_at'] ?></td>
                        <td><?= $c['revoked_at'] ?></td>
                        <td><?= $c['ip_address'] ?></td>
                        <td><?php
                            $sourceMap = [
                                'register_form' => 'Formularz rejestracji',
                                'profile_edit' => 'Edycja profilu',
                                'admin_panel' => 'Panel administratora',
                            ];
                            echo $sourceMap[$c['source']] ?? htmlspecialchars(ucfirst(str_replace('_', ' ', $c['source'])));
                            ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>