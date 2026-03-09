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
?>
<!-- Filter bar -->
<div class="card" style="margin-bottom:16px;">
    <div class="card-body">
        <form method="get" action="/recruiter/candidates">
            <div class="filter-row" style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;">
                <div class="field" style="min-width:140px;"><label>Ville</label>
                    <select name="ville">
                        <option value="">Toutes</option>
                        <?php foreach ($villes as $v): ?>
                        <option value="<?= htmlspecialchars($v) ?>" <?= ($filters['ville'] ?? '') === $v ? 'selected' : '' ?>><?= htmlspecialchars($v) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field" style="min-width:100px;"><label>Exp. min (ans)</label><input type="number" name="experience_min" placeholder="—" value="<?= $filters['experience_min'] !== null ? (int)$filters['experience_min'] : '' ?>"></div>
                <div class="field" style="min-width:100px;"><label>Exp. max (ans)</label><input type="number" name="experience_max" placeholder="—" value="<?= $filters['experience_max'] !== null ? (int)$filters['experience_max'] : '' ?>"></div>
                <div class="field" style="min-width:140px;"><label>Compétences</label><input type="text" name="skills" placeholder="PHP, Python…" value="<?= htmlspecialchars($filters['skills'] ?? '') ?>"></div>
                <div class="field" style="align-self:flex-end;"><button type="submit" class="btn btn-re">Filtrer</button></div>
                <?php if (!empty(array_filter($filters ?? []))): ?>
                <div class="field" style="align-self:flex-end;"><a href="/recruiter/candidates" class="btn btn-ghost">Effacer</a></div>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <div class="card-title"><div class="ci" style="background:var(--ca-l);">👥</div>Tous les candidats</div>
        <span style="font-size:11px;color:var(--muted);"><?= $total ?> candidat(s)</span>
    </div>

    <?php if (empty($items)): ?>
    <div class="card-body" style="text-align:center;padding:36px;">
        <p style="color:var(--muted);">Aucun candidat ne correspond aux filtres.</p>
    </div>
    <?php else: ?>

    <div style="overflow-x:auto;">
        <table class="tbl">
            <thead>
                <tr>
                    <th>Candidat</th>
                    <th>Email</th>
                    <th>Ville</th>
                    <th>Poste actuel</th>
                    <th>Expérience</th>
                    <th>Compétences</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $c):
                    $name = trim(($c['prenom'] ?? '') . ' ' . ($c['nom'] ?? ''));
                    $init = strtoupper(mb_substr($c['prenom'] ?? 'X', 0, 1) . mb_substr($c['nom'] ?? 'X', 0, 1));
                    $candidateId = (int)($c['id'] ?? 0);
                    $avatarColor = getUniqueAvatarColor($candidateId);
                    $avatarUrl = getAvatarUrl($candidateId, $name, $c['prenom'] ?? '');
                    $skills = !empty($c['competences_techniques_raw'])
                        ? array_slice(array_filter(array_map('trim', preg_split('/[\s,;]+/', $c['competences_techniques_raw']))), 0, 3)
                        : [];
                ?>
                <tr onclick="window.location='/recruiter/candidates/<?= (int)$c['id'] ?>'" style="cursor:pointer;">
                    <td>
                        <div class="u-info">
                            <div style="width:40px;height:40px;border-radius:8px;background:#fff;display:flex;align-items:center;justify-content:center;border:2px solid var(--border);box-shadow:var(--shadow);overflow:hidden;flex-shrink:0;position:relative;">
                                <img src="<?= htmlspecialchars($avatarUrl) ?>" alt="<?= htmlspecialchars($name) ?>" style="width:100%;height:100%;object-fit:cover;border-radius:8px;display:block;" onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
                                <div style="display:none;width:100%;height:100%;align-items:center;justify-content:center;background:<?= htmlspecialchars($avatarColor) ?>;font-size:14px;font-weight:700;color:#fff;border-radius:8px;"><?= htmlspecialchars($init) ?></div>
                            </div>
                            <div>
                                <div class="u-name"><?= htmlspecialchars($name ?: 'Candidat') ?></div>
                                <?php if (!empty($c['niveau_etudes'])): ?>
                                <div class="u-sub"><?= htmlspecialchars($c['niveau_etudes']) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </td>
                    <td style="font-size:12px;color:var(--muted);"><?= htmlspecialchars($c['email'] ?? '') ?></td>
                    <td style="font-size:12px;"><?= htmlspecialchars($c['ville'] ?? '—') ?></td>
                    <td style="font-size:12px;"><?= htmlspecialchars(mb_substr($c['poste_actuel'] ?? '', 0, 30)) ?></td>
                    <td style="font-size:12px;"><?= isset($c['experience_annees']) ? (int)$c['experience_annees'] . ' an(s)' : '—' ?></td>
                    <td>
                        <div style="display:flex;flex-wrap:wrap;gap:3px;">
                            <?php foreach ($skills as $sk): ?>
                            <span class="tag tg"><?= htmlspecialchars($sk) ?></span>
                            <?php endforeach; ?>
                        </div>
                    </td>
                    <td onclick="event.stopPropagation()">
                        <a href="/recruiter/candidates/<?= (int)$c['id'] ?>" class="btn btn-outline-re btn-sm">Profil →</a>
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
            $url = '/recruiter/candidates?' . http_build_query($q);
        ?>
            <?php if ($i === $page): ?>
            <span class="active"><?= $i ?></span>
            <?php else: ?>
            <a href="<?= htmlspecialchars($url) ?>"><?= $i ?></a>
            <?php endif; ?>
        <?php endfor; ?>
    </div>
    <p style="font-size:12px;color:var(--muted);padding:0 22px 12px;">Page <?= $page ?> sur <?= $total_pages ?> (<?= $total ?> au total)</p>
    <?php endif; ?>

    <?php endif; ?>
</div>
