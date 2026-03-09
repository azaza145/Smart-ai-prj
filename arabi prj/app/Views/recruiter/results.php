<?php
// Generate unique avatar color based on candidate ID
function getUniqueAvatarColor(int $candidateId): string {
    $colors = [
        ['#1a8c5c', '#22c07a'], ['#1a6b4a', '#2a9966'], ['#0b4d2e', '#1a8c5c'],
        ['#2563eb', '#3b82f6'], ['#7c3aed', '#8b5cf6'], ['#dc2626', '#ef4444'],
        ['#ea580c', '#f97316'], ['#0891b2', '#06b6d4'], ['#059669', '#10b981'],
        ['#d97706', '#f59e0b'], ['#be185d', '#ec4899'], ['#4338ca', '#6366f1'],
    ];
    $index = abs($candidateId) % count($colors);
    return 'linear-gradient(135deg, ' . $colors[$index][0] . ', ' . $colors[$index][1] . ')';
}

function getAvatarUrl(int $candidateId, string $name, string $prenom = ''): string {
    $avatarColor = getUniqueAvatarColor($candidateId);
    preg_match('/#([0-9a-fA-F]{6})/', $avatarColor, $colorMatches);
    $avatarBgColor = $colorMatches[1] ?? '1a8c5c';
    
    // Create unique seed based on candidate ID and name
    $hash = md5($candidateId . '_' . $name . '_' . $prenom);
    $randomSeed = substr($hash, 0, 16) . '_' . $candidateId;
    
    // Alternate between technology (bottts) and animals (lorelei) based on candidate ID
    $isTechnology = ($candidateId % 2 === 0);
    
    if ($isTechnology) {
        // Technology/robot style
        return 'https://api.dicebear.com/7.x/bottts/svg?seed=' . urlencode($randomSeed) . '&backgroundColor=' . $avatarBgColor . '&radius=50&size=80';
    } else {
        // Animal style
        return 'https://api.dicebear.com/7.x/lorelei/svg?seed=' . urlencode($randomSeed) . '&backgroundColor=' . $avatarBgColor . '&radius=50&size=80';
    }
}

$recommendations_table_missing = $recommendations_table_missing ?? false;
?>
<?php if ($recommendations_table_missing): ?>
<div class="alert-recruteia alert-info" style="border-left:4px solid var(--re);">
    <strong>⚠ Table des recommandations absente.</strong> Exécutez le script SQL : <code>scripts/create_recommendations_table_only.sql</code>
</div>
<?php endif; ?>

<!-- Filter bar -->
<div class="card" style="margin-bottom:16px;">
    <div class="card-body">
        <form method="get" action="/recruiter/jobs/<?= (int)$job['id'] ?>/results">
            <div class="filter-row" style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;">
                <div class="field" style="min-width:140px;"><label>Ville</label>
                    <select name="ville">
                        <option value="">Toutes</option>
                        <?php foreach ($villes as $v): ?>
                        <option value="<?= htmlspecialchars($v) ?>" <?= ($filters['ville'] ?? '') === $v ? 'selected' : '' ?>><?= htmlspecialchars($v) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field" style="min-width:100px;"><label>Score min (0–1)</label><input type="number" name="min_score" step="0.01" min="0" max="1" placeholder="0.5" value="<?= $filters['min_score'] !== null ? htmlspecialchars((string)$filters['min_score']) : '' ?>"></div>
                <div class="field" style="min-width:90px;"><label>Exp. min</label><input type="number" name="experience_min" placeholder="—" value="<?= $filters['experience_min'] !== null ? (int)$filters['experience_min'] : '' ?>"></div>
                <div class="field" style="min-width:90px;"><label>Exp. max</label><input type="number" name="experience_max" placeholder="—" value="<?= $filters['experience_max'] !== null ? (int)$filters['experience_max'] : '' ?>"></div>
                <div class="field" style="align-self:flex-end;"><button type="submit" class="btn btn-re">Filtrer</button></div>
            </div>
        </form>
    </div>
</div>

<!-- Results -->
<div class="card">
    <div class="card-header">
        <div class="card-title">
            <div class="ci" style="background:var(--re-l);">🎯</div>
            Classement IA — <?= htmlspecialchars($job['title']) ?>
        </div>
        <div style="display:flex;gap:8px;align-items:center;">
            <span style="font-size:11px;color:var(--muted);"><?= $total ?> candidat(s)</span>
            <form method="post" action="/recruiter/jobs/<?= (int)$job['id'] ?>/recommend" style="display:inline;">
                <?= \App\Core\Csrf::field() ?>
                <input type="hidden" name="top_k" value="200">
                <button type="submit" class="btn btn-re btn-sm">▶ Relancer</button>
            </form>
        </div>
    </div>

    <?php if (empty($items)): ?>
    <div class="card-body" style="text-align:center;padding:36px;">
        <p style="color:var(--muted);">Aucun résultat. Lancez l'analyse IA d'abord.</p>
    </div>
    <?php else: ?>

    <div style="overflow-x:auto;">
        <table class="tbl">
            <thead>
                <tr>
                    <th style="width:50px;">Rang</th>
                    <th>Candidat</th>
                    <th>Ville</th>
                    <th>Poste actuel</th>
                    <th>Score IA</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $r):
                    $name = trim(($r['prenom'] ?? '') . ' ' . ($r['nom'] ?? ''));
                    $init = strtoupper(mb_substr($r['prenom'] ?? 'X', 0, 1) . mb_substr($r['nom'] ?? 'X', 0, 1));
                    $candidateId = (int)($r['candidate_id'] ?? 0);
                    $avatarColor = getUniqueAvatarColor($candidateId);
                    $avatarUrl = getAvatarUrl($candidateId, $name, $r['prenom'] ?? '');
                    $scorePct = round((float)$r['score'] * 100);
                    $rank = (int)($r['ranking'] ?? $r['rank'] ?? 0);
                    $scoreColor = $scorePct >= 85 ? 'var(--ca)' : ($scorePct >= 70 ? 'var(--re)' : 'var(--muted)');
                ?>
                <tr>
                    <td>
                        <div style="font-family:var(--font-h);font-size:18px;font-weight:700;color:<?= $rank <= 3 ? 'var(--ca)' : 'var(--muted)' ?>;">
                            <?= $rank <= 3 ? ['🥇','🥈','🥉'][$rank-1] : '#' . $rank ?>
                        </div>
                    </td>
                    <td>
                        <div class="u-info">
                            <div style="width:40px;height:40px;border-radius:8px;background:#fff;display:flex;align-items:center;justify-content:center;border:2px solid var(--border);box-shadow:var(--shadow);overflow:hidden;flex-shrink:0;position:relative;">
                                <img src="<?= htmlspecialchars($avatarUrl) ?>" alt="<?= htmlspecialchars($name) ?>" style="width:100%;height:100%;object-fit:cover;border-radius:8px;display:block;" onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
                                <div style="display:none;width:100%;height:100%;align-items:center;justify-content:center;background:<?= htmlspecialchars($avatarColor) ?>;font-size:14px;font-weight:700;color:#fff;border-radius:8px;"><?= htmlspecialchars($init) ?></div>
                            </div>
                            <div>
                                <div class="u-name"><?= htmlspecialchars($name ?: 'Candidat') ?></div>
                                <?php if (!empty($r['niveau_etudes'])): ?>
                                <div class="u-sub"><?= htmlspecialchars($r['niveau_etudes']) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </td>
                    <td style="font-size:12px;color:var(--muted);"><?= htmlspecialchars($r['ville'] ?? '—') ?></td>
                    <td style="font-size:12px;"><?= htmlspecialchars(mb_substr($r['poste_actuel'] ?? '', 0, 30)) ?></td>
                    <td style="min-width:160px;">
                        <div class="score-row">
                            <div class="score-bar"><div class="score-fill" style="width:<?= min(100,$scorePct) ?>%;background:<?= $scoreColor ?>;"></div></div>
                            <div class="score-v" style="color:<?= $scoreColor ?>;"><?= $scorePct ?>%</div>
                        </div>
                    </td>
                    <td>
                        <a href="/recruiter/jobs/<?= (int)$job['id'] ?>/candidates/<?= (int)$r['candidate_id'] ?>" class="btn btn-outline-re btn-sm">Profil →</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if ($total_pages > 1): ?>
    <div class="pagination-recruteia">
        <?php for ($i = 1; $i <= min($total_pages, 20); $i++):
            $q = $_GET;
            $q['page'] = $i;
            $url = '/recruiter/jobs/' . (int)$job['id'] . '/results?' . http_build_query($q);
        ?>
            <?php if ($i === $page): ?>
            <span class="active"><?= $i ?></span>
            <?php else: ?>
            <a href="<?= htmlspecialchars($url) ?>"><?= $i ?></a>
            <?php endif; ?>
        <?php endfor; ?>
    </div>
    <p style="font-size:12px;color:var(--muted);padding:0 22px 12px;">Page <?= $page ?> sur <?= $total_pages ?></p>
    <?php endif; ?>

    <?php endif; ?>
</div>
