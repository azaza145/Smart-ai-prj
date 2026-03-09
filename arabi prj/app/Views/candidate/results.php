<?php if (empty($scores)) { ?>
<div class="card">
    <div class="card-body">
        <p style="color:var(--muted);margin-bottom:12px;">Aucun score IA pour le moment. Les recruteurs lancent l'analyse par poste ; dès qu'un classement inclut votre profil, vos scores apparaîtront ici.</p>
        <a href="/candidate/jobs" class="btn btn-ca">Voir les offres disponibles</a>
    </div>
</div>
<?php } else { ?>
<div class="card">
    <div class="card-header"><div class="card-title"><div class="ci" style="background:var(--ca-l);">🎯</div>Score de correspondance par poste</div></div>
    <div class="card-body" style="padding:0;">
        <?php foreach ($scores as $r) {
            $scorePct = round((float)$r['score'] * 100);
            $scoreColor = $scorePct >= 85 ? 'var(--ca)' : ($scorePct >= 70 ? 'var(--re)' : 'var(--muted)');
        ?>
        <div style="display:flex;align-items:center;gap:12px;padding:14px 22px;border-bottom:1px solid var(--border);">
            <div style="flex:1;">
                <div style="font-size:14px;font-weight:600;"><?= htmlspecialchars($r['job_title'] ?? '') ?></div>
                <?php if (!empty($r['job_department'])) { ?><div style="font-size:12px;color:var(--muted);"><?= htmlspecialchars($r['job_department']) ?></div><?php } ?>
                <div class="prog-bar" style="margin-top:8px;"><div class="prog-fill" style="width:<?= min(100, $scorePct) ?>%;background:<?= $scoreColor ?>;"></div></div>
            </div>
            <div style="font-family:var(--font-h);font-size:18px;font-weight:700;color:<?= $scoreColor ?>;"><?= $scorePct ?>%</div>
            <a href="/candidate/jobs/<?= (int)$r['job_id'] ?>/apply" class="btn btn-outline-ca" style="padding:5px 12px;font-size:12px;">Voir l'offre</a>
        </div>
        <?php } ?>
    </div>
</div>
<p style="margin-top:16px;"><a href="/candidate/jobs" class="btn btn-outline-ca">Voir tous les postes</a></p>
<?php } ?>
