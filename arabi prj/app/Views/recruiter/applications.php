<?php
$statusLabels = [
    'submitted' => ['label' => 'Envoyée', 'class' => 'tag ta'],
    'viewed' => ['label' => 'Consultée', 'class' => 'tag tb'],
    'shortlisted' => ['label' => 'Shortlist', 'class' => 'tag tg'],
    'rejected' => ['label' => 'Refusée', 'class' => 'tag tr'],
];
?>
<div class="card" style="margin-bottom:16px;">
    <div class="card-body">
        <a href="/recruiter/jobs/<?= (int)$job['id'] ?>" class="btn btn-ghost" style="margin-bottom:12px;">← Retour au poste</a>
        <a href="/recruiter/jobs/<?= (int)$job['id'] ?>/results" class="btn btn-outline-re" style="margin-bottom:12px;">Voir le classement IA</a>
    </div>
</div>

<div class="card">
    <div class="card-header"><div class="card-title"><div class="ci" style="background:var(--ca-l);">📋</div>Candidatures reçues — <?= htmlspecialchars($job['title']) ?></div><span style="font-size:11px;color:var(--muted);"><?= count($applications) ?> candidature(s)</span></div>
    <?php if (empty($applications)) { ?>
    <div class="card-body"><p style="color:var(--muted);">Aucune candidature pour ce poste.</p></div>
    <?php } else { ?>
    <div style="overflow-x:auto;">
        <table class="tbl">
            <thead><tr><th>Candidat</th><th>Ville</th><th>Poste actuel</th><th>Date</th><th>Statut</th><th>Actions</th></tr></thead>
            <tbody>
                <?php foreach ($applications as $app) {
                    $st = $statusLabels[$app['status'] ?? 'submitted'] ?? $statusLabels['submitted'];
                    $name = trim(($app['prenom'] ?? '') . ' ' . ($app['nom'] ?? ''));
                ?>
                <tr>
                    <td>
                        <div class="u-name"><?= htmlspecialchars($name) ?></div>
                        <div class="u-sub"><?= htmlspecialchars($app['email'] ?? '') ?></div>
                    </td>
                    <td style="font-size:12px;color:var(--muted);"><?= htmlspecialchars($app['ville'] ?? '') ?></td>
                    <td style="font-size:12px;"><?= htmlspecialchars($app['poste_actuel'] ?? '') ?></td>
                    <td style="font-size:12px;color:var(--muted);"><?= date('d/m/Y H:i', strtotime($app['created_at'])) ?></td>
                    <td><span class="<?= $st['class'] ?>"><?= $st['label'] ?></span></td>
                    <td>
                        <div style="display:flex;gap:6px;align-items:center;flex-wrap:wrap;">
                            <form method="post" action="/recruiter/jobs/<?= (int)$job['id'] ?>/applications/<?= (int)$app['id'] ?>/status" style="display:inline;">
                                <?= \App\Core\Csrf::field() ?>
                                <select name="status" onchange="this.form.submit()" style="font-size:12px;padding:4px 8px;border-radius:6px;border:1.5px solid var(--border);">
                                    <option value="submitted" <?= ($app['status'] ?? '') === 'submitted' ? 'selected' : '' ?>>Envoyée</option>
                                    <option value="viewed" <?= ($app['status'] ?? '') === 'viewed' ? 'selected' : '' ?>>Consultée</option>
                                    <option value="shortlisted" <?= ($app['status'] ?? '') === 'shortlisted' ? 'selected' : '' ?>>Shortlist</option>
                                    <option value="rejected" <?= ($app['status'] ?? '') === 'rejected' ? 'selected' : '' ?>>Refusée</option>
                                </select>
                            </form>
                            <a href="/recruiter/jobs/<?= (int)$job['id'] ?>/candidates/<?= (int)$app['candidate_id'] ?>" class="btn btn-outline-re" style="padding:5px 12px;font-size:12px;">Profil</a>
                        </div>
                    </td>
                </tr>
                <?php if (!empty($app['cover_letter'])) { ?>
                <tr><td colspan="6" style="font-size:12px;color:var(--muted);padding-left:20px;border-top:none;"><strong>Lettre de motivation :</strong> <?= nl2br(htmlspecialchars(mb_substr($app['cover_letter'], 0, 300))) ?><?= mb_strlen($app['cover_letter']) > 300 ? '…' : '' ?></td></tr>
                <?php } ?>
                <?php } ?>
            </tbody>
        </table>
    </div>
    <?php } ?>
</div>
