<?php
if (!$candidate) {
    echo '<div class="card"><div class="card-body"><p style="color:var(--muted);">Aucun profil pour le moment. Commencez par remplir vos informations.</p></div></div>';
    return;
}
$c = $candidate;
$pct = (int)($profileProgress ?? 0);
$skillsRaw = $c['competences_techniques_raw'] ?? '';
$skillsList = array_values(array_filter(array_map('trim', preg_split('/[\s,;|\/]+/', $skillsRaw))));
$langsRaw = $c['competences_langues_raw'] ?? '';
$langsList = array_values(array_filter(array_map('trim', preg_split('/[\s,;|\/]+/', $langsRaw))));
$bestMatch = $matchingJobs[0] ?? null;
$bestScore = $bestMatch ? round((float)$bestMatch['score'] * 100) : 0;
$bestTitle = $bestMatch ? ($bestMatch['job_title'] ?? '') : '';
$formationsList = [];
if (!empty($c['formations_json'])) {
    $decoded = json_decode($c['formations_json'], true);
    if (is_array($decoded)) $formationsList = $decoded;
}
$experiencesList = [];
if (!empty($c['experiences_json'])) {
    $decoded = json_decode($c['experiences_json'], true);
    if (is_array($decoded)) $experiencesList = $decoded;
}
$documents = $documents ?? [];
$suggestedJobs = $suggestedJobs ?? [];
$appliedJobIds = $appliedJobIds ?? [];
?>

<div class="two-col">
<!-- ── LEFT COLUMN ── -->
<div>

<!-- Progress card -->
<div class="card" style="margin-bottom:20px;">
    <div class="card-header">
        <div class="card-title"><div class="ci" style="background:var(--ca-l);">📊</div>Complétude du profil</div>
        <span style="font-family:var(--font-h);font-size:20px;font-weight:700;color:var(--ca);"><?= $pct ?>%</span>
    </div>
    <div class="card-body" style="padding:16px 22px;">
        <div class="prog-bar"><div class="prog-fill" style="width:<?= min(100,$pct) ?>%"></div></div>
        <p style="font-size:12px;color:var(--muted);margin-top:10px;">Plus vous complétez votre profil, plus votre score de matching IA sera précis.</p>
    </div>
</div>

<!-- AI matches -->
<?php if (!empty($matchingJobs)): ?>
<div class="card" style="margin-bottom:20px;">
    <div class="card-header">
        <div class="card-title"><div class="ci" style="background:var(--ca-l);">🧠</div>Vos meilleures correspondances IA</div>
        <a href="/candidate/results" class="btn btn-outline-ca btn-sm">Voir tout</a>
    </div>
    <div class="card-body" style="padding:14px 18px;">
        <?php foreach (array_slice($matchingJobs, 0, 4) as $mj):
            $scorePct = round((float)($mj['score'] ?? 0) * 100);
        ?>
        <a href="/candidate/jobs/<?= (int)($mj['job_id'] ?? 0) ?>/apply" style="display:flex;align-items:center;gap:14px;padding:10px 0;border-bottom:1px solid var(--border);text-decoration:none;color:inherit;">
            <div style="font-family:var(--font-h);font-size:22px;font-weight:700;color:var(--ca);min-width:46px;"><?= $scorePct ?>%</div>
            <div>
                <div style="font-weight:600;font-size:13px;"><?= htmlspecialchars($mj['job_title'] ?? '') ?></div>
                <div style="font-size:11px;color:var(--muted);">Voir l'offre →</div>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Profile form -->
<form method="post" action="/candidate/profile" id="profile-form" enctype="multipart/form-data">
    <?= \App\Core\Csrf::field() ?>
    <input type="hidden" name="competences_techniques_raw" id="input-skills" value="<?= htmlspecialchars($skillsRaw) ?>">
    <input type="hidden" name="competences_langues_raw" id="input-langs" value="<?= htmlspecialchars($langsRaw) ?>">

    <!-- Personal info -->
    <div class="card" style="margin-bottom:16px;">
        <div class="card-header">
            <div class="card-title"><div class="ci" style="background:var(--ca-l);">👤</div>Informations personnelles</div>
            <div style="display:flex;gap:8px;">
                <a href="/candidate/profile/generate-cv" class="btn btn-outline-ca btn-sm">📄 Générer CV PDF</a>
                <button type="submit" class="btn btn-ca btn-sm">Enregistrer</button>
            </div>
        </div>
        <div class="card-body">
            <div class="fg">
                <div class="field"><label>Prénom</label><input type="text" name="prenom" value="<?= htmlspecialchars($c['prenom'] ?? '') ?>" placeholder="Votre prénom"></div>
                <div class="field"><label>Nom</label><input type="text" name="nom" value="<?= htmlspecialchars($c['nom'] ?? '') ?>" placeholder="Votre nom"></div>
                <div class="field"><label>Email</label><input type="email" name="email_contact" value="<?= htmlspecialchars($c['email_contact'] ?? '') ?>" placeholder="contact@email.com"></div>
                <div class="field"><label>Téléphone</label><input type="text" name="telephone" value="<?= htmlspecialchars($c['telephone'] ?? '') ?>" placeholder="+212 6XX XXX XXX"></div>
                <div class="field"><label>Ville</label><input type="text" name="ville" value="<?= htmlspecialchars($c['ville'] ?? '') ?>" placeholder="Casablanca"></div>
                <div class="field"><label>Nationalité</label><input type="text" name="nationalite" value="<?= htmlspecialchars($c['nationalite'] ?? '') ?>" placeholder="Marocaine"></div>
                <div class="field ff"><label>Poste actuel / Objectif</label><input type="text" name="poste_actuel" value="<?= htmlspecialchars($c['poste_actuel'] ?? '') ?>" placeholder="Ex: Développeur Full Stack"></div>
                <div class="field"><label>Années d'expérience</label><input type="number" name="experience_annees" value="<?= htmlspecialchars($c['experience_annees'] ?? '') ?>" min="0" max="60" placeholder="0"></div>
                <div class="field"><label>Niveau d'études</label>
                    <select name="niveau_etudes">
                        <?php foreach (['', 'Bac', 'Bac+2', 'Bac+3', 'Bac+5', 'Bac+8', 'Autre'] as $niv) { ?>
                        <option value="<?= htmlspecialchars($niv) ?>" <?= ($c['niveau_etudes'] ?? '') === $niv ? 'selected' : '' ?>><?= $niv ?: '— Sélectionner —' ?></option>
                        <?php } ?>
                    </select>
                </div>
                <div class="field ff"><label>Résumé / Présentation</label><textarea name="resume" rows="3" placeholder="Décrivez-vous en quelques lignes…"><?= htmlspecialchars($c['resume'] ?? '') ?></textarea></div>
            </div>
        </div>
    </div>

    <!-- Skills -->
    <div class="card" style="margin-bottom:16px;">
        <div class="card-header">
            <div class="card-title"><div class="ci" style="background:var(--ca-l);">⚡</div>Compétences techniques</div>
        </div>
        <div class="card-body">
            <div class="field">
                <label>Compétences (séparées par des virgules)</label>
                <input type="text" id="skills-input" value="<?= htmlspecialchars($skillsRaw) ?>" placeholder="PHP, JavaScript, Python, MySQL…">
            </div>
            <div id="skills-tags" style="display:flex;flex-wrap:wrap;gap:6px;margin-top:10px;">
                <?php foreach ($skillsList as $sk): ?>
                <span class="tag tg" style="cursor:pointer;" onclick="removeSkill('<?= htmlspecialchars(addslashes($sk)) ?>')"><?= htmlspecialchars($sk) ?> ×</span>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Languages -->
    <div class="card" style="margin-bottom:16px;">
        <div class="card-header">
            <div class="card-title"><div class="ci" style="background:var(--ca-l);">🌍</div>Langues</div>
        </div>
        <div class="card-body">
            <div class="field">
                <label>Langues maîtrisées</label>
                <input type="text" id="langs-input" value="<?= htmlspecialchars($langsRaw) ?>" placeholder="Arabe, Français, Anglais…">
            </div>
        </div>
    </div>

    <!-- Education -->
    <div class="card" style="margin-bottom:16px;">
        <div class="card-header">
            <div class="card-title"><div class="ci" style="background:var(--ca-l);">🎓</div>Formations</div>
        </div>
        <div class="card-body">
            <textarea name="formations_raw" rows="4" style="width:100%;background:var(--bg);border:1.5px solid var(--border-2);border-radius:var(--r-sm);padding:10px 12px;font-family:var(--font-b);font-size:13px;color:var(--text);outline:none;resize:vertical;" placeholder="Ex : Master Informatique — ENSIAS 2018-2020&#10;Licence Maths — Université Hassan II 2015-2018"><?= htmlspecialchars($c['formations_raw'] ?? '') ?></textarea>
        </div>
    </div>

    <!-- Experience -->
    <div class="card" style="margin-bottom:16px;">
        <div class="card-header">
            <div class="card-title"><div class="ci" style="background:var(--ca-l);">💼</div>Expériences professionnelles</div>
        </div>
        <div class="card-body">
            <textarea name="experiences_raw" rows="4" style="width:100%;background:var(--bg);border:1.5px solid var(--border-2);border-radius:var(--r-sm);padding:10px 12px;font-family:var(--font-b);font-size:13px;color:var(--text);outline:none;resize:vertical;" placeholder="Ex : Développeur Senior — OCP 2020-2024&#10;Stage — Maroc Telecom Été 2019"><?= htmlspecialchars($c['experiences_raw'] ?? '') ?></textarea>
        </div>
    </div>

    <!-- CV Upload -->
    <div class="card" style="margin-bottom:16px;">
        <div class="card-header">
            <div class="card-title"><div class="ci" style="background:var(--ca-l);">📎</div>CV PDF</div>
        </div>
        <div class="card-body">
            <?php if (!empty($documents)): ?>
            <?php foreach ($documents as $doc): ?>
            <div class="upload-done" style="margin-bottom:12px;">
                <span>📄</span>
                <div style="flex:1;">
                    <div style="font-weight:600;font-size:13px;"><?= htmlspecialchars($doc['original_name'] ?? 'CV.pdf') ?></div>
                    <div style="font-size:11px;color:var(--muted);">Déposé le <?= date('d/m/Y', strtotime($doc['uploaded_at'] ?? 'now')) ?></div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
            <form method="post" action="/candidate/upload-cv" enctype="multipart/form-data" style="margin-top:12px;">
                <?= \App\Core\Csrf::field() ?>
                <div class="upload-zone" onclick="document.getElementById('cv-file').click()">
                    <div style="font-size:24px;margin-bottom:8px;">⬆</div>
                    <div style="font-size:13px;font-weight:600;color:var(--text);">Déposer un CV PDF</div>
                    <div style="font-size:12px;color:var(--muted);margin-top:4px;">L'IA extraira automatiquement vos informations</div>
                </div>
                <input type="file" id="cv-file" name="cv" accept=".pdf" style="display:none;" onchange="this.form.submit()">
            </form>
        </div>
    </div>

    <div style="display:flex;gap:10px;margin-top:4px;">
        <button type="submit" class="btn btn-ca" style="flex:1;justify-content:center;">Enregistrer le profil</button>
        <a href="/candidate/profile/generate-cv" class="btn btn-outline-ca">📄 Générer CV PDF</a>
    </div>
</form>

</div>

<!-- ── RIGHT COLUMN ── -->
<div>

<!-- Suggested jobs sidebar -->
<?php if (!empty($suggestedJobs)): ?>
<div class="card" style="margin-bottom:20px;">
    <div class="card-header">
        <div class="card-title"><div class="ci" style="background:var(--ca-l);">🎯</div>Offres à ne pas manquer</div>
        <a href="/candidate/jobs" class="btn btn-outline-ca btn-sm">Toutes →</a>
    </div>
    <div style="padding:8px 0;">
        <?php foreach (array_slice($suggestedJobs, 0, 6) as $job):
            $applied = in_array((int)$job['id'], $appliedJobIds, true);
        ?>
        <div style="display:flex;align-items:center;gap:10px;padding:12px 18px;border-bottom:1px solid var(--border);">
            <div style="flex:1;">
                <div style="font-weight:600;font-size:13px;"><?= htmlspecialchars($job['title'] ?? 'Poste') ?></div>
                <?php if (!empty($job['department'])): ?>
                <div style="font-size:11px;color:var(--muted);"><?= htmlspecialchars($job['department']) ?></div>
                <?php endif; ?>
            </div>
            <a href="/candidate/jobs/<?= (int)$job['id'] ?>/apply"
               class="btn <?= $applied ? 'btn-ghost' : 'btn-outline-ca' ?> btn-sm">
                <?= $applied ? '✓ Postulé' : 'Postuler' ?>
            </a>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Profile summary card -->
<div class="card" style="margin-bottom:20px;">
    <div class="card-header">
        <div class="card-title"><div class="ci" style="background:var(--ca-l);">🧑</div>Résumé du profil</div>
    </div>
    <div class="card-body">
        <div style="display:flex;align-items:center;gap:14px;margin-bottom:18px;">
            <div class="avatar-sm" style="width:50px;height:50px;font-size:18px;background:linear-gradient(135deg,var(--ca),var(--g-light));">
                <?= strtoupper(mb_substr($c['prenom'] ?? 'C', 0, 1) . mb_substr($c['nom'] ?? 'V', 0, 1)) ?>
            </div>
            <div>
                <div style="font-family:var(--font-h);font-size:18px;font-weight:600;"><?= htmlspecialchars(trim(($c['prenom'] ?? '') . ' ' . ($c['nom'] ?? '')) ?: 'Votre nom') ?></div>
                <div style="font-size:12px;color:var(--muted);"><?= htmlspecialchars($c['poste_actuel'] ?? 'Complétez votre profil') ?></div>
            </div>
        </div>

        <?php if (!empty($c['ville'])): ?>
        <div style="display:flex;gap:6px;align-items:center;font-size:13px;margin-bottom:8px;">
            <span>📍</span><span><?= htmlspecialchars($c['ville']) ?></span>
        </div>
        <?php endif; ?>

        <?php if (!empty($c['experience_annees'])): ?>
        <div style="display:flex;gap:6px;align-items:center;font-size:13px;margin-bottom:8px;">
            <span>🏆</span><span><?= (int)$c['experience_annees'] ?> an(s) d'expérience</span>
        </div>
        <?php endif; ?>

        <?php if (!empty($skillsList)): ?>
        <div style="margin-top:14px;">
            <div style="font-size:11px;font-weight:700;letter-spacing:.5px;text-transform:uppercase;color:var(--muted);margin-bottom:8px;">Compétences</div>
            <div style="display:flex;flex-wrap:wrap;gap:5px;">
                <?php foreach (array_slice($skillsList, 0, 8) as $sk): ?>
                <span class="tag tg"><?= htmlspecialchars($sk) ?></span>
                <?php endforeach; ?>
                <?php if (count($skillsList) > 8): ?>
                <span class="tag tm">+<?= count($skillsList) - 8 ?></span>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($bestMatch): ?>
        <div style="margin-top:20px;padding:14px;background:var(--g-pale);border-radius:var(--r-sm);border:1px solid var(--ca);">
            <div style="font-size:11px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px;">Meilleure correspondance IA</div>
            <div style="font-family:var(--font-h);font-size:24px;font-weight:700;color:var(--ca);"><?= $bestScore ?>%</div>
            <div style="font-size:12px;color:var(--g-dark);font-weight:600;"><?= htmlspecialchars($bestTitle) ?></div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Quick links -->
<div class="card">
    <div class="card-header">
        <div class="card-title"><div class="ci" style="background:var(--ca-l);">🔗</div>Actions rapides</div>
    </div>
    <div style="padding:8px 0;">
        <a href="/candidate/jobs" style="display:flex;align-items:center;gap:10px;padding:12px 18px;border-bottom:1px solid var(--border);text-decoration:none;color:var(--text);font-size:13px;transition:background .12s;">
            <span>💼</span><span>Voir toutes les offres</span><span style="margin-left:auto;color:var(--muted);">→</span>
        </a>
        <a href="/candidate/applications" style="display:flex;align-items:center;gap:10px;padding:12px 18px;border-bottom:1px solid var(--border);text-decoration:none;color:var(--text);font-size:13px;">
            <span>📋</span><span>Mes candidatures</span><span style="margin-left:auto;color:var(--muted);">→</span>
        </a>
        <a href="/candidate/results" style="display:flex;align-items:center;gap:10px;padding:12px 18px;text-decoration:none;color:var(--text);font-size:13px;">
            <span>🧠</span><span>Résultats IA détaillés</span><span style="margin-left:auto;color:var(--muted);">→</span>
        </a>
    </div>
</div>

</div>
</div>

<script>
function removeSkill(skill) {
    const input = document.getElementById('skills-input');
    const arr = input.value.split(',').map(s => s.trim()).filter(s => s && s !== skill);
    input.value = arr.join(', ');
    document.getElementById('input-skills').value = input.value;
    location.reload();
}

document.getElementById('skills-input').addEventListener('change', function() {
    document.getElementById('input-skills').value = this.value;
});

document.getElementById('langs-input').addEventListener('change', function() {
    document.getElementById('input-langs').value = this.value;
});

</script>
