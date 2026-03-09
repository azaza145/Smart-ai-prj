<div class="card">
    <div class="card-header"><div class="card-title"><div class="ci" style="background:var(--am-l);">💼</div>Créer un poste</div></div>
    <div class="card-body">
        <form method="post" action="/admin/jobs" style="display:flex;gap:12px;align-items:end;flex-wrap:wrap;">
            <?= \App\Core\Csrf::field() ?>
            <div class="field" style="min-width:200px;"><label>Intitulé</label><input type="text" name="title" placeholder="Ex: Développeur Full Stack" required></div>
            <div class="field" style="min-width:140px;"><label>Département</label><input type="text" name="department" placeholder="Tech, RH…"></div>
            <button type="submit" class="btn btn-ad">Créer</button>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header"><div class="card-title"><div class="ci" style="background:var(--re-l);">📋</div>Postes</div></div>
    <div style="overflow-x:auto;">
        <table class="tbl">
            <thead><tr><th>Intitulé</th><th>Département</th><th>Créé le</th><th>Actions</th></tr></thead>
            <tbody>
                <?php foreach ($jobs as $j) { ?>
                <tr>
                    <td><span style="font-weight:600;"><?= htmlspecialchars($j['title']) ?></span></td>
                    <td style="color:var(--muted);font-size:12px;"><?= htmlspecialchars($j['department'] ?? '') ?></td>
                    <td style="font-size:12px;color:var(--muted);"><?= htmlspecialchars($j['created_at']) ?></td>
                    <td>
                        <div class="abtns" style="flex-wrap:wrap;">
                            <a href="/admin/jobs/<?= (int)$j['id'] ?>" class="ibtn" style="text-decoration:none;">✏️</a>
                            <form method="post" action="/admin/jobs/<?= (int)$j['id'] ?>/recommend" style="display:inline;">
                                <?= \App\Core\Csrf::field() ?>
                                <button type="submit" class="btn btn-outline-ca" style="padding:5px 12px;font-size:12px;">Recommandations IA</button>
                            </form>
                            <form method="post" action="/admin/jobs/<?= (int)$j['id'] ?>/delete" style="display:inline;" onsubmit="return confirm('Supprimer ce poste ?');">
                                <?= \App\Core\Csrf::field() ?>
                                <button type="submit" class="btn btn-ghost" style="color:var(--er);font-size:12px;">Supprimer</button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>
