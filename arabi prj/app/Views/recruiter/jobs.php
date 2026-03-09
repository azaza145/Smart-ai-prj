<?php
$jobs = $jobs ?? [];
$appCounts = $appCounts ?? [];
$filters = $filters ?? ['title' => '', 'skills' => '', 'type_contrat' => ''];
$typeContratOptions = $typeContratOptions ?? [];
?>

<!-- Filter bar -->
<div class="card" style="margin-bottom:16px;">
    <div class="card-body">
        <form method="get" action="/recruiter/jobs" class="jobs-filter-form">
            <div class="filter-row">
                <div class="field"><label>Intitulé du poste</label><input type="text" name="q" value="<?= htmlspecialchars($filters['title'] ?? '') ?>" placeholder="Développeur, Data…"></div>
                <div class="field"><label>Compétences</label><input type="text" name="skills" value="<?= htmlspecialchars($filters['skills']) ?>" placeholder="PHP, Python…"></div>
                <?php if (!empty($typeContratOptions)): ?>
                <div class="field"><label>Type de contrat</label>
                    <select name="type_contrat">
                        <option value="">Tous</option>
                        <?php foreach ($typeContratOptions as $tc): ?>
                        <option value="<?= htmlspecialchars($tc) ?>"<?= ($filters['type_contrat'] ?? '') === $tc ? ' selected' : '' ?>><?= htmlspecialchars($tc) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                <div class="field" style="align-self:flex-end;"><button type="submit" class="btn btn-re">Filtrer</button></div>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <div class="card-title"><div class="ci" style="background:var(--re-l);">💼</div>Postes</div>
        <a href="/recruiter/jobs/create" class="btn btn-re btn-sm">+ Nouveau poste</a>
    </div>

    <?php if (empty($jobs)): ?>
    <div class="card-body" style="text-align:center;padding:48px;">
        <div style="font-size:40px;margin-bottom:12px;">💼</div>
        <h3 style="font-family:var(--font-h);font-size:20px;margin-bottom:8px;">Aucun poste</h3>
        <p style="color:var(--muted);font-size:13px;margin-bottom:20px;">Publiez votre premier poste pour commencer à recevoir des candidatures.</p>
        <a href="/recruiter/jobs/create" class="btn btn-re">+ Publier une offre</a>
    </div>
    <?php else: ?>

    <div style="overflow-x:auto;">
        <table class="tbl">
            <thead>
                <tr>
                    <th>Intitulé</th>
                    <th>Compétences</th>
                    <th>Contrat</th>
                    <th>Département</th>
                    <th>Candidatures</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($jobs as $j):
                    $cnt = (int)($appCounts[(int)$j['id']] ?? 0);
                    $skillsDisplay = !empty($j['skills_raw'])
                        ? implode(', ', array_slice(array_filter(array_map('trim', preg_split('/[\s,;]+/', $j['skills_raw']))), 0, 3))
                        : '—';
                    if (strlen($skillsDisplay) > 40) $skillsDisplay = substr($skillsDisplay, 0, 37) . '…';
                ?>
                <tr>
                    <td>
                        <a href="/recruiter/jobs/<?= (int)$j['id'] ?>" style="font-weight:600;color:var(--text);text-decoration:none;"><?= htmlspecialchars($j['title']) ?></a>
                        <?php if (!empty($j['status']) && $j['status'] !== 'active'): ?>
                        <span class="tag tr" style="margin-left:6px;"><?= htmlspecialchars($j['status']) ?></span>
                        <?php endif; ?>
                    </td>
                    <td style="font-size:12px;color:var(--muted);"><?= htmlspecialchars($skillsDisplay) ?></td>
                    <td style="font-size:12px;"><?= htmlspecialchars($j['type_contrat'] ?? '—') ?></td>
                    <td style="font-size:12px;color:var(--muted);"><?= htmlspecialchars($j['department'] ?? '') ?></td>
                    <td>
                        <a href="/recruiter/jobs/<?= (int)$j['id'] ?>/applications" style="font-weight:700;color:var(--re);text-decoration:none;"><?= $cnt ?></a>
                    </td>
                    <td>
                        <div class="abtns">
                            <a href="/recruiter/jobs/<?= (int)$j['id'] ?>" class="btn btn-ghost btn-sm">Ouvrir</a>
                            <a href="/recruiter/jobs/<?= (int)$j['id'] ?>/applications" class="btn btn-ghost btn-sm">Candidatures</a>
                            <a href="/recruiter/jobs/<?= (int)$j['id'] ?>/results" class="btn btn-outline-re btn-sm">🧠 IA</a>
                            <form method="post" action="/recruiter/jobs/<?= (int)$j['id'] ?>/duplicate" style="display:inline;">
                                <?= \App\Core\Csrf::field() ?>
                                <button type="submit" class="btn btn-ghost btn-sm" title="Dupliquer">⊕</button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>
