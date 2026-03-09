<!-- Stats strip -->
<div class="three-col" style="margin-bottom:24px;">
    <div class="stat-card">
        <div class="si" style="background:var(--re-l);">💼</div>
        <div class="sv" style="color:var(--re);"><?= number_format((int)$jobsCount) ?></div>
        <div class="sl">Postes publiés</div>
    </div>
    <div class="stat-card">
        <div class="si" style="background:var(--ca-l);">👥</div>
        <div class="sv"><?= number_format((int)$candidatesCount) ?></div>
        <div class="sl">Candidats dans la base</div>
    </div>
    <div class="stat-card">
        <div class="si" style="background:var(--am-l);">📋</div>
        <div class="sv"><?= number_format((int)$applicationsCount) ?></div>
        <div class="sl">Candidatures reçues</div>
    </div>
</div>

<!-- AI banner -->
<div class="ai-banner">
    <div style="display:flex;align-items:center;gap:14px;">
        <div class="ai-icon-w">🧠</div>
        <div>
            <div class="ai-t">Moteur IA de recommandation</div>
            <div class="ai-s">Lancez l'analyse sur un poste pour obtenir le classement des meilleurs candidats</div>
        </div>
    </div>
    <a href="/recruiter/recommendations" class="btn btn-re">Voir les recommandations →</a>
</div>

<!-- Quick actions -->
<div class="two-col">
    <div class="card">
        <div class="card-header">
            <div class="card-title"><div class="ci" style="background:var(--re-l);">💼</div>Postes actifs</div>
            <a href="/recruiter/jobs/create" class="btn btn-re btn-sm">+ Nouveau poste</a>
        </div>
        <div style="padding:8px 0;">
            <?php foreach (array_slice($recentJobs ?? [], 0, 6) as $j):
                $cnt = (int)($appCounts[(int)$j['id']] ?? 0);
            ?>
            <div style="display:flex;align-items:center;gap:12px;padding:12px 18px;border-bottom:1px solid var(--border);">
                <div style="flex:1;">
                    <a href="/recruiter/jobs/<?= (int)$j['id'] ?>" style="font-weight:600;font-size:13px;color:var(--text);text-decoration:none;"><?= htmlspecialchars($j['title']) ?></a>
                    <div style="font-size:11px;color:var(--muted);"><?= htmlspecialchars($j['department'] ?? '') ?></div>
                </div>
                <a href="/recruiter/jobs/<?= (int)$j['id'] ?>/applications" style="font-weight:700;color:var(--re);font-size:13px;text-decoration:none;"><?= $cnt ?> candidature<?= $cnt > 1 ? 's' : '' ?></a>
                <a href="/recruiter/jobs/<?= (int)$j['id'] ?>/results" class="btn btn-outline-re btn-sm">IA</a>
            </div>
            <?php endforeach; ?>
            <?php if (empty($recentJobs ?? [])): ?>
            <div style="padding:24px 18px;text-align:center;color:var(--muted);font-size:13px;">
                Aucun poste. <a href="/recruiter/jobs/create" style="color:var(--re);">Publier la première offre →</a>
            </div>
            <?php endif; ?>
        </div>
        <?php if (count($recentJobs ?? []) > 0): ?>
        <div style="padding:12px 18px;border-top:1px solid var(--border);">
            <a href="/recruiter/jobs" style="font-size:12px;color:var(--re);">Voir tous les postes →</a>
        </div>
        <?php endif; ?>
    </div>

    <div class="card">
        <div class="card-header">
            <div class="card-title"><div class="ci" style="background:var(--am-l);">📬</div>Candidatures récentes</div>
        </div>
        <div style="padding:8px 0;">
            <?php foreach (array_slice($recentApplications ?? [], 0, 8) as $a):
                $statusLabels = ['submitted' => ['label' => 'Envoyée', 'class' => 'ta'], 'viewed' => ['label' => 'Vue', 'class' => 'tb'], 'shortlisted' => ['label' => 'Shortlist', 'class' => 'tg'], 'rejected' => ['label' => 'Refusée', 'class' => 'tr']];
                $st = $statusLabels[$a['status'] ?? 'submitted'] ?? $statusLabels['submitted'];
            ?>
            <div class="feed-item">
                <div class="fdot" style="background:var(--ca-l);">👤</div>
                <div style="flex:1;">
                    <div class="fm"><?= htmlspecialchars($a['candidate_name'] ?? 'Candidat') ?></div>
                    <div class="ft"><?= htmlspecialchars($a['job_title'] ?? '') ?> — <?= date('d/m', strtotime($a['applied_at'] ?? $a['created_at'] ?? 'now')) ?></div>
                </div>
                <span class="tag <?= $st['class'] ?>"><?= $st['label'] ?></span>
            </div>
            <?php endforeach; ?>
            <?php if (empty($recentApplications ?? [])): ?>
            <div style="padding:24px 18px;text-align:center;color:var(--muted);font-size:13px;">Aucune candidature récente.</div>
            <?php endif; ?>
        </div>
    </div>
</div>
