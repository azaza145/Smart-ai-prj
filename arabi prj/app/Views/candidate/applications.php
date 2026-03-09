<?php
$statusLabels = [
    'submitted'   => ['label' => 'Envoyée',    'class' => 'ta', 'icon' => '📤'],
    'viewed'      => ['label' => 'Consultée',  'class' => 'tb', 'icon' => '👁'],
    'shortlisted' => ['label' => 'Shortlist',  'class' => 'tg', 'icon' => '⭐'],
    'rejected'    => ['label' => 'Refusée',    'class' => 'tr', 'icon' => '✕'],
    'interview'   => ['label' => 'Entretien',  'class' => 'tp', 'icon' => '🗓'],
    'accepted'    => ['label' => 'Acceptée',   'class' => 'tg', 'icon' => '✓'],
    'pending'     => ['label' => 'En attente', 'class' => 'ta', 'icon' => '⏳'],
];
?>

<?php if (empty($applications)): ?>
<div class="card">
    <div class="card-body" style="text-align:center;padding:48px;">
        <div style="font-size:40px;margin-bottom:12px;">📋</div>
        <h3 style="font-family:var(--font-h);font-size:20px;margin-bottom:8px;">Aucune candidature</h3>
        <p style="color:var(--muted);font-size:13px;margin-bottom:20px;">Vous n'avez pas encore postulé à une offre.</p>
        <a href="/candidate/jobs" class="btn btn-ca">Voir les offres →</a>
    </div>
</div>
<?php else: ?>

<!-- Status legend -->
<div class="card" style="margin-bottom:16px;">
    <div class="card-body" style="display:flex;flex-wrap:wrap;gap:10px;padding:14px 22px;">
        <?php foreach ($statusLabels as $key => $st): ?>
        <span class="tag <?= $st['class'] ?>"><?= $st['icon'] ?> <?= $st['label'] ?></span>
        <?php endforeach; ?>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <div class="card-title"><div class="ci" style="background:var(--ca-l);">📋</div>Mes candidatures</div>
        <span style="font-size:11px;color:var(--muted);"><?= count($applications) ?> candidature(s)</span>
    </div>
    <div style="overflow-x:auto;">
        <table class="tbl">
            <thead>
                <tr>
                    <th>Offre</th>
                    <th>Entreprise</th>
                    <th>Date</th>
                    <th>Statut</th>
                    <th>Score IA</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($applications as $app):
                    $st = $statusLabels[$app['status'] ?? 'submitted'] ?? $statusLabels['submitted'];
                    $score = isset($app['score']) ? round((float)$app['score'] * 100) : null;
                ?>
                <tr>
                    <td>
                        <div style="font-weight:600;font-size:13px;"><?= htmlspecialchars($app['job_title'] ?? $app['title'] ?? 'Offre') ?></div>
                        <?php if (!empty($app['job_department'])): ?>
                        <div style="font-size:11px;color:var(--muted);"><?= htmlspecialchars($app['job_department']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td style="font-size:12px;color:var(--muted);"><?= htmlspecialchars($app['company_name'] ?? '—') ?></td>
                    <td style="font-size:12px;color:var(--muted);"><?= date('d/m/Y', strtotime($app['created_at'] ?? $app['applied_at'] ?? 'now')) ?></td>
                    <td><span class="tag <?= $st['class'] ?>"><?= $st['icon'] ?> <?= $st['label'] ?></span></td>
                    <td>
                        <?php if ($score !== null): ?>
                        <div class="score-row">
                            <div class="score-bar"><div class="score-fill" style="width:<?= $score ?>%;background:var(--ca);"></div></div>
                            <span class="score-v" style="color:var(--ca);"><?= $score ?>%</span>
                        </div>
                        <?php else: ?>
                        <span style="color:var(--muted2);font-size:12px;">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="/candidate/jobs/<?= (int)($app['job_id'] ?? $app['job_offer_id'] ?? 0) ?>/apply" class="btn btn-ghost btn-sm">Voir</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div style="margin-top:16px;">
    <a href="/candidate/jobs" class="btn btn-outline-ca">Voir toutes les offres →</a>
</div>

<?php endif; ?>
