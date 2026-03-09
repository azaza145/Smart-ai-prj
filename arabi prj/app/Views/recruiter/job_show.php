<?php $appCount = \App\Models\Application::countByJob((int)$job['id']); ?>
<div class="card">
    <div class="card-header"><div class="card-title"><div class="ci" style="background:var(--am-l);">💼</div><?= htmlspecialchars($job['title']) ?></div><div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;"><a href="/recruiter/jobs/<?= (int)$job['id'] ?>/edit" class="btn btn-ghost" style="padding:5px 12px;font-size:12px;">Modifier</a><form method="post" action="/recruiter/jobs/<?= (int)$job['id'] ?>/duplicate" style="display:inline;"><?= \App\Core\Csrf::field() ?><button type="submit" class="btn btn-ghost" style="padding:5px 12px;font-size:12px;">Dupliquer</button></form><a href="/recruiter/jobs/<?= (int)$job['id'] ?>/applications" class="btn btn-outline-re" style="padding:5px 12px;font-size:12px;">Candidatures (<?= $appCount ?>)</a></div></div>
    <div class="card-body">
        <?php if (!empty($job['type_contrat']) || !empty($job['department'])) { ?><p style="font-size:12px;color:var(--muted);margin-bottom:8px;"><?= !empty($job['type_contrat']) ? htmlspecialchars($job['type_contrat']) : '' ?><?= !empty($job['type_contrat']) && !empty($job['department']) ? ' · ' : '' ?><?= !empty($job['department']) ? 'Département : ' . htmlspecialchars($job['department']) : '' ?></p><?php } ?>
        <?php if (!empty($job['skills_raw'])) { $skillsArr = array_filter(array_map('trim', preg_split('/[\s,;]+/', $job['skills_raw']))); ?><p style="font-size:12px;margin-bottom:8px;"><strong style="color:var(--muted);">Compétences :</strong> <?= htmlspecialchars(implode(', ', $skillsArr)) ?></p><?php } ?>
        <?php if (!empty($job['description'])) { ?><div style="margin-bottom:12px;font-size:13px;"><?= nl2br(htmlspecialchars($job['description'])) ?></div><?php } ?>
        <?php if (!empty($job['requirements'])) { ?><p style="margin-bottom:0;"><strong style="font-size:11px;text-transform:uppercase;color:var(--muted);">Exigences</strong><br><span style="font-size:13px;"><?= nl2br(htmlspecialchars($job['requirements'])) ?></span></p><?php } ?>
    </div>
</div>

<div class="ai-banner" style="margin-bottom:20px;">
    <div style="display:flex;align-items:center;gap:13px;">
        <div class="ai-icon-w">🧠</div>
        <div>
            <div class="ai-t">Générer les recommandations IA</div>
            <div class="ai-s">L'analyse TF-IDF + similarité cosinus classe les candidats selon ce poste.</div>
        </div>
    </div>
    <form method="post" action="/recruiter/jobs/<?= (int)$job['id'] ?>/recommend" style="display:flex;align-items:center;gap:10px;">
        <?= \App\Core\Csrf::field() ?>
        <input type="number" name="top_k" value="200" min="1" max="5000" style="width:80px;padding:8px 10px;border:1.5px solid var(--border);border-radius:7px;font-size:13px;">
        <button type="submit" class="btn btn-re">▶ Lancer l'analyse</button>
    </form>
</div>

<?php if ($lastRun) { ?>
    <p style="font-size:12px;color:var(--muted);margin-bottom:16px;">Dernière exécution : <?= htmlspecialchars($lastRun['started_at']) ?> — <?= htmlspecialchars($lastRun['status']) ?>, <?= (int)$lastRun['rows_affected'] ?> candidats</p>
<?php } ?>

<a href="/recruiter/jobs/<?= (int)$job['id'] ?>/results" class="btn btn-outline-re">Voir le classement des candidats →</a>
