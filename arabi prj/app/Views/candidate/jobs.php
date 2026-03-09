<?php if (empty($jobs)): ?>
<div class="card">
    <div class="card-body" style="text-align:center;padding:48px;">
        <div style="font-size:40px;margin-bottom:12px;">💼</div>
        <h3 style="font-family:var(--font-h);font-size:20px;margin-bottom:8px;">Aucune offre pour le moment</h3>
        <p style="color:var(--muted);font-size:13px;">Les offres apparaîtront ici dès qu'elles seront publiées.</p>
    </div>
</div>
<?php else: ?>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
    <p style="font-size:13px;color:var(--muted);"><?= count($jobs) ?> offre(s) disponible(s)</p>
</div>

<div class="postes-grid">
    <?php foreach ($jobs as $job):
        $applied = in_array((int)$job['id'], $appliedIds ?? [], true);
    ?>
    <div class="card" style="margin-bottom:0;transition:transform .2s,box-shadow .2s;" onmouseenter="this.style.transform='translateY(-3px)';this.style.boxShadow='var(--shadow-md)';" onmouseleave="this.style.transform='';this.style.boxShadow='';">
        <div class="card-body">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:12px;">
                <div>
                    <h3 style="font-family:var(--font-h);font-size:17px;font-weight:600;margin-bottom:4px;line-height:1.2;"><?= htmlspecialchars($job['title']) ?></h3>
                    <?php if (!empty($job['department'])): ?>
                    <span style="font-size:11px;color:var(--muted);">📁 <?= htmlspecialchars($job['department']) ?></span>
                    <?php endif; ?>
                </div>
                <?php if ($applied): ?>
                <span class="tag tg">✓ Postulé</span>
                <?php endif; ?>
            </div>

            <?php if (!empty($job['type_contrat'])): ?>
            <span class="tag tm" style="margin-bottom:10px;display:inline-block;"><?= htmlspecialchars($job['type_contrat']) ?></span>
            <?php endif; ?>

            <?php if (!empty($job['description'])): ?>
            <p style="font-size:13px;color:var(--muted);margin-bottom:14px;line-height:1.5;">
                <?= nl2br(htmlspecialchars(mb_substr($job['description'], 0, 160))) ?><?= mb_strlen($job['description']) > 160 ? '…' : '' ?>
            </p>
            <?php endif; ?>

            <?php if (!empty($job['skills_raw'])): ?>
            <div style="display:flex;flex-wrap:wrap;gap:4px;margin-bottom:14px;">
                <?php foreach (array_slice(array_filter(array_map('trim', preg_split('/[\s,;]+/', $job['skills_raw']))), 0, 4) as $sk): ?>
                <span class="tag tg"><?= htmlspecialchars($sk) ?></span>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <div style="display:flex;gap:8px;margin-top:auto;">
                <?php if (!$applied): ?>
                <a href="/candidate/jobs/<?= (int)$job['id'] ?>/apply" class="btn btn-ca" style="flex:1;justify-content:center;">Postuler</a>
                <?php endif; ?>
                <a href="/candidate/jobs/<?= (int)$job['id'] ?>/apply" class="btn btn-ghost" style="<?= !$applied ? '' : 'flex:1;justify-content:center;' ?>">
                    <?= $applied ? '👁 Voir ma candidature' : 'Détail' ?>
                </a>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>
