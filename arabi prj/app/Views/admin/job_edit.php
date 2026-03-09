<div class="card" style="max-width:700px;">
    <div class="card-header"><div class="card-title"><div class="ci" style="background:var(--am-l);">💼</div>Modifier le poste</div></div>
    <div class="card-body">
        <form method="post" action="/admin/jobs/<?= (int)$job['id'] ?>">
            <?= \App\Core\Csrf::field() ?>
            <div class="fg">
                <div class="field ff"><label>Intitulé</label><input type="text" name="title" value="<?= htmlspecialchars($job['title']) ?>" required></div>
                <div class="field"><label>Département</label><input type="text" name="department" value="<?= htmlspecialchars($job['department'] ?? '') ?>"></div>
                <div class="field ff"><label>Description</label><textarea name="description" rows="4"><?= htmlspecialchars($job['description'] ?? '') ?></textarea></div>
                <div class="field ff"><label>Exigences</label><textarea name="requirements" rows="4"><?= htmlspecialchars($job['requirements'] ?? '') ?></textarea></div>
            </div>
            <div style="display:flex;gap:8px;margin-top:16px;">
                <button type="submit" class="btn btn-re">Enregistrer</button>
                <a href="/admin/jobs" class="btn btn-ghost">Annuler</a>
            </div>
        </form>
    </div>
</div>
