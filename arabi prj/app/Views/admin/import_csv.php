<!-- CSV Import -->
<div class="ai-banner" style="margin-bottom:20px;">
    <div style="display:flex;align-items:center;gap:14px;">
        <div class="ai-icon-w">📊</div>
        <div>
            <div class="ai-t">Import CSV de candidats</div>
            <div class="ai-s">Importez jusqu'à 5 000 profils candidats depuis un fichier CSV standardisé</div>
        </div>
    </div>
</div>

<div class="two-col">

<div>
    <!-- Upload -->
    <div class="card" style="margin-bottom:20px;">
        <div class="card-header">
            <div class="card-title"><div class="ci" style="background:var(--am-l);">📁</div>Importer un fichier CSV</div>
        </div>
        <div class="card-body">
            <form method="post" action="/admin/import-csv" enctype="multipart/form-data">
                <?= \App\Core\Csrf::field() ?>
                <div class="upload-zone" onclick="document.getElementById('csv-file').click()">
                    <div style="font-size:32px;margin-bottom:10px;">📊</div>
                    <div style="font-size:14px;font-weight:600;color:var(--text);">Cliquez pour sélectionner un fichier CSV</div>
                    <div style="font-size:12px;color:var(--muted);margin-top:6px;">Format : dataset_cvs_5000.csv</div>
                </div>
                <input type="file" id="csv-file" name="csv_file" accept=".csv" style="display:none;" onchange="this.form.submit()">
            </form>
            <?php if (!empty($importResult)): ?>
            <div class="alert-recruteia alert-success" style="margin-top:16px;">
                ✓ Import terminé : <?= (int)$importResult['inserted'] ?> insérés, <?= (int)$importResult['updated'] ?> mis à jour, <?= (int)$importResult['failed'] ?> erreurs
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Normalization -->
    <div class="card">
        <div class="card-header">
            <div class="card-title"><div class="ci" style="background:var(--ad-l);">⚙️</div>Normalisation des profils</div>
        </div>
        <div class="card-body">
            <p style="font-size:13px;color:var(--muted);margin-bottom:16px;">La normalisation nettoie et structure les données pour améliorer la précision du matching IA.</p>
            <form method="post" action="/admin/run-normalization">
                <?= \App\Core\Csrf::field() ?>
                <button type="submit" class="btn btn-ad">⚙️ Lancer la normalisation</button>
            </form>
            <?php if (!empty($stats['last_normalization'])): ?>
            <p style="font-size:12px;color:var(--muted);margin-top:12px;">
                Dernière : <?= date('d/m/Y H:i', strtotime($stats['last_normalization']['started_at'])) ?> — <?= htmlspecialchars($stats['last_normalization']['status']) ?>
            </p>
            <?php endif; ?>
        </div>
    </div>
</div>

<div>
    <!-- Import logs -->
    <div class="card">
        <div class="card-header">
            <div class="card-title"><div class="ci" style="background:var(--am-l);">📋</div>Historique des imports</div>
        </div>
        <div style="overflow-x:auto;">
            <table class="tbl">
                <thead><tr><th>Date</th><th>Traités</th><th>Insérés</th><th>MAJ</th><th>Erreurs</th></tr></thead>
                <tbody>
                    <?php foreach ($importLogs ?? [] as $log): ?>
                    <tr>
                        <td style="font-size:12px;color:var(--muted);"><?= date('d/m/Y H:i', strtotime($log['started_at'])) ?></td>
                        <td><?= (int)$log['rows_processed'] ?></td>
                        <td><span class="tag tg"><?= (int)$log['rows_inserted'] ?></span></td>
                        <td><span class="tag tb"><?= (int)$log['rows_updated'] ?></span></td>
                        <td><?= (int)$log['rows_failed'] > 0 ? '<span class="tag tr">' . (int)$log['rows_failed'] . '</span>' : '—' ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($importLogs ?? [])): ?>
                    <tr><td colspan="5" style="text-align:center;color:var(--muted);padding:20px;">Aucun import.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- CSV format help -->
    <div class="card" style="margin-top:20px;">
        <div class="card-header">
            <div class="card-title"><div class="ci" style="background:var(--g-pale);">ℹ️</div>Format CSV attendu</div>
        </div>
        <div class="card-body">
            <p style="font-size:13px;color:var(--muted);margin-bottom:12px;">Colonnes requises dans le CSV :</p>
            <div style="display:flex;flex-wrap:wrap;gap:5px;">
                <?php foreach (['nom','prenom','email','ville','poste_actuel','experience_annees','competences_techniques','langues','niveau_etudes'] as $col): ?>
                <code style="background:var(--bg);border:1px solid var(--border-2);border-radius:4px;padding:2px 8px;font-size:11px;color:var(--g-dark);"><?= $col ?></code>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

</div>
