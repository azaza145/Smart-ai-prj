<!-- AI Banner -->
<div class="ai-banner" style="margin-bottom:24px;">
    <div style="display:flex;align-items:center;gap:14px;">
        <div class="ai-icon-w">🧠</div>
        <div>
            <div class="ai-t">Recommandations IA par poste</div>
            <div class="ai-s">Lancez l'analyse pour scorer automatiquement tous les candidats par rapport à un poste</div>
        </div>
    </div>
    <div class="ai-stats">
        <div>
            <div class="aiv"><?= count($jobs) ?></div>
            <div class="ail">Postes</div>
        </div>
        <div>
            <div class="aiv"><?= array_sum(array_values($appCounts ?? [])) ?></div>
            <div class="ail">Candidatures</div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <div class="card-title"><div class="ci" style="background:var(--re-l);">🎯</div>Analyses IA disponibles</div>
        <a href="/recruiter/jobs/create" class="btn btn-re btn-sm">+ Nouveau poste</a>
    </div>

    <?php if (empty($jobs)): ?>
    <div class="card-body" style="text-align:center;padding:48px;">
        <div style="font-size:40px;margin-bottom:12px;">🧠</div>
        <h3 style="font-family:var(--font-h);font-size:20px;margin-bottom:8px;">Aucun poste</h3>
        <p style="color:var(--muted);font-size:13px;margin-bottom:20px;">Publiez une offre pour pouvoir lancer l'analyse IA.</p>
        <a href="/recruiter/jobs/create" class="btn btn-re">+ Publier une offre</a>
    </div>
    <?php else: ?>

    <div style="overflow-x:auto;">
        <table class="tbl">
            <thead>
                <tr>
                    <th>Poste</th>
                    <th>Département</th>
                    <th>Candidatures</th>
                    <th>Dernière analyse</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($jobs as $j):
                    $cnt = (int)($appCounts[(int)$j['id']] ?? 0);
                    $lastRun = $lastRuns[(int)$j['id']] ?? null;
                ?>
                <tr>
                    <td>
                        <a href="/recruiter/jobs/<?= (int)$j['id'] ?>" style="font-weight:600;color:var(--text);text-decoration:none;"><?= htmlspecialchars($j['title']) ?></a>
                    </td>
                    <td style="font-size:12px;color:var(--muted);"><?= htmlspecialchars($j['department'] ?? '') ?></td>
                    <td>
                        <a href="/recruiter/jobs/<?= (int)$j['id'] ?>/applications" style="font-weight:600;color:var(--re);text-decoration:none;"><?= $cnt ?></a>
                    </td>
                    <td style="font-size:12px;color:var(--muted);">
                        <?php if ($lastRun): ?>
                        <span class="tag tg" style="margin-right:4px;">✓</span>
                        <?= htmlspecialchars(date('d/m/Y H:i', strtotime($lastRun['started_at']))) ?>
                        — <?= $lastRun['rows_affected'] ?> candidats scorés
                        <?php else: ?>
                        <span class="tag tm">Non lancée</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="abtns">
                            <form method="post" action="/recruiter/jobs/<?= (int)$j['id'] ?>/recommend" style="display:inline;">
                                <?= \App\Core\Csrf::field() ?>
                                <input type="hidden" name="top_k" value="200">
                                <button type="submit" class="btn btn-re btn-sm">▶ Lancer l'analyse</button>
                            </form>
                            <a href="/recruiter/jobs/<?= (int)$j['id'] ?>/results" class="btn btn-outline-re btn-sm">Classement →</a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php endif; ?>
</div>
