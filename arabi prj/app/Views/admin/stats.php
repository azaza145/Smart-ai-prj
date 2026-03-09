<!-- Stats grid -->
<div class="five-col" style="margin-bottom:24px;">
    <div class="stat-card">
        <div class="si" style="background:var(--ca-l);">👥</div>
        <div class="sv"><?= number_format($stats['candidates']) ?></div>
        <div class="sl">Candidats</div>
    </div>
    <div class="stat-card">
        <div class="si" style="background:var(--re-l);">💼</div>
        <div class="sv"><?= number_format($stats['jobs']) ?></div>
        <div class="sl">Postes</div>
    </div>
    <div class="stat-card">
        <div class="si" style="background:var(--am-l);">📋</div>
        <div class="sv"><?= number_format($stats['applications'] ?? 0) ?></div>
        <div class="sl">Candidatures</div>
    </div>
    <div class="stat-card">
        <div class="si" style="background:var(--ad-l);">👤</div>
        <div class="sv"><?= number_format($stats['users']) ?></div>
        <div class="sl">Utilisateurs</div>
    </div>
    <div class="stat-card">
        <div class="si" style="background:var(--g-pale);">🤝</div>
        <div class="sv"><?= number_format($stats['recruiters'] ?? 0) ?></div>
        <div class="sl">Recruteurs</div>
    </div>
</div>

<!-- Pipeline status -->
<div class="card" style="margin-bottom:20px;">
    <div class="card-header">
        <div class="card-title"><div class="ci" style="background:var(--am-l);">⚙️</div>État du pipeline IA</div>
    </div>
    <div class="card-body">
        <div class="fg" style="gap:16px;margin-bottom:20px;">
            <div>
                <div style="font-size:11px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px;">Dernier import CSV</div>
                <div style="font-size:13px;">
                    <?= $stats['last_import']
                        ? date('d/m/Y H:i', strtotime($stats['last_import']['started_at'])) . ' — ' . $stats['last_import']['rows_processed'] . ' traités, ' . $stats['last_import']['rows_inserted'] . ' insérés, ' . $stats['last_import']['rows_failed'] . ' erreurs'
                        : '<span style="color:var(--muted2);">Jamais</span>' ?>
                </div>
            </div>
            <div>
                <div style="font-size:11px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px;">Dernière normalisation</div>
                <div style="font-size:13px;">
                    <?= $stats['last_normalization']
                        ? date('d/m/Y H:i', strtotime($stats['last_normalization']['started_at'])) . ' (' . htmlspecialchars($stats['last_normalization']['status']) . ')'
                        : '<span style="color:var(--muted2);">Jamais</span>' ?>
                </div>
            </div>
            <div>
                <div style="font-size:11px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px;">Dernière recommandation</div>
                <div style="font-size:13px;">
                    <?= $stats['last_recommendation']
                        ? date('d/m/Y H:i', strtotime($stats['last_recommendation']['started_at']))
                        : '<span style="color:var(--muted2);">Jamais</span>' ?>
                </div>
            </div>
        </div>
        <form method="post" action="/admin/run-normalization">
            <?= \App\Core\Csrf::field() ?>
            <button type="submit" class="btn btn-ad">⚙️ Lancer la normalisation</button>
        </form>
    </div>
</div>

<!-- Users + activity -->
<div class="two-col" style="margin-bottom:20px;">
    <div class="card">
        <div class="card-header">
            <div class="card-title"><div class="ci" style="background:var(--ad-l);">👤</div>Utilisateurs récents</div>
            <a href="/admin/users" class="btn btn-ghost btn-sm">Voir tout →</a>
        </div>
        <div style="overflow-x:auto;">
            <table class="tbl">
                <thead><tr><th>Utilisateur</th><th>Rôle</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php foreach ($users ?? [] as $u):
                        $init = strtoupper(mb_substr($u['name'] ?? 'U', 0, 1) . mb_substr(preg_replace('/\s+/', '', $u['name'] ?? ''), 1, 1));
                        if (strlen($init) < 2) $init = $init . 'X';
                        $roleClass = ($u['role'] ?? '') === 'admin' ? 'tp' : (($u['role'] ?? '') === 'recruiter' ? 'tb' : 'tg');
                    ?>
                    <tr>
                        <td>
                            <div class="u-info">
                                <div class="u-av" style="background:var(--ad);"><?= htmlspecialchars($init) ?></div>
                                <div>
                                    <div class="u-name"><?= htmlspecialchars($u['name'] ?? '') ?></div>
                                    <div class="u-sub"><?= htmlspecialchars($u['email'] ?? '') ?></div>
                                </div>
                            </div>
                        </td>
                        <td><span class="tag <?= $roleClass ?>"><?= htmlspecialchars($u['role'] ?? '') ?></span></td>
                        <td><a href="/admin/users/<?= (int)($u['id'] ?? 0) ?>" class="btn btn-ghost btn-sm">Modifier</a></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($users ?? [])): ?>
                    <tr><td colspan="3" style="text-align:center;color:var(--muted);padding:20px;">Aucun utilisateur.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <div class="card-title"><div class="ci" style="background:var(--am-l);">📊</div>Activité récente</div>
        </div>
        <div>
            <?php if (empty($activities ?? [])): ?>
            <div class="card-body"><p style="color:var(--muted);font-size:13px;">Aucune activité récente.</p></div>
            <?php else: ?>
            <?php foreach ($activities as $a): ?>
            <div class="feed-item">
                <div class="fdot" style="background:<?= $a['bg'] ?? 'var(--bg)' ?>;"><?= $a['icon'] ?? '•' ?></div>
                <div>
                    <div class="fm"><?= htmlspecialchars($a['msg'] ?? '') ?></div>
                    <div class="ft"><?= htmlspecialchars($a['time'] ?? '') ?></div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Top cities + skills -->
<div class="two-col">
    <div class="card">
        <div class="card-header">
            <div class="card-title"><div class="ci" style="background:var(--ca-l);">📍</div>Top villes</div>
        </div>
        <div class="card-body">
            <ul style="list-style:none;padding:0;margin:0;">
                <?php foreach (array_slice($villeCounts ?? [], 0, 10, true) as $ville => $cnt): ?>
                <li style="display:flex;justify-content:space-between;align-items:center;padding:8px 0;font-size:13px;border-bottom:1px solid var(--border);">
                    <span>📍 <?= htmlspecialchars($ville) ?></span>
                    <span class="tag tg"><?= $cnt ?></span>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <div class="card-title"><div class="ci" style="background:var(--re-l);">⚡</div>Top compétences</div>
        </div>
        <div class="card-body">
            <ul style="list-style:none;padding:0;margin:0;">
                <?php foreach (array_slice($topSkills ?? [], 0, 15, true) as $skill => $cnt): ?>
                <li style="display:flex;justify-content:space-between;align-items:center;padding:8px 0;font-size:13px;border-bottom:1px solid var(--border);">
                    <span><?= htmlspecialchars($skill) ?></span>
                    <span class="tag tb"><?= $cnt ?></span>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>
