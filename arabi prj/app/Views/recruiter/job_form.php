<?php
$job = $job ?? null;
$templates = $templates ?? [];
$prefill = $prefill ?? null;
$typeContratOptions = $typeContratOptions ?? [];
$titleVal = $job ? ($job['title'] ?? '') : ($prefill['title'] ?? '');
$skillsVal = $job ? ($job['skills_raw'] ?? '') : ($prefill['skills'] ?? '');
$typeContratVal = $job ? ($job['type_contrat'] ?? '') : ($prefill['type_contrat'] ?? '');
$skillsList = array_values(array_filter(array_map('trim', preg_split('/[\s,;|\/]+/', $skillsVal))));
?>
<div class="card job-form-card" style="max-width:720px;">
    <div class="card-header">
        <div class="card-title"><div class="ci" style="background:var(--re-l);">💼</div><?= $job ? 'Modifier le poste' : 'Publier une offre' ?></div>
    </div>
    <div class="card-body">
        <?php if (!$job && !empty($templates)) { ?>
        <div class="job-templates">
            <div class="job-templates-label">Partir d'un modèle (1 clic)</div>
            <div class="job-templates-list">
                <?php foreach ($templates as $key => $t) { ?>
                <a href="/recruiter/jobs/create?template=<?= urlencode($key) ?>" class="job-template-pill"><?= htmlspecialchars($t['title']) ?></a>
                <?php } ?>
            </div>
        </div>
        <?php } ?>

        <form method="post" action="<?= htmlspecialchars($formAction) ?>" id="job-form">
            <?= \App\Core\Csrf::field() ?>
            <div class="fg job-form-fields">
                <div class="field ff"><label>Intitulé du poste <span class="required">*</span></label><input type="text" name="title" id="job-title" value="<?= htmlspecialchars($titleVal) ?>" placeholder="Ex: Développeur Full Stack" required></div>
                <div class="field" style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                    <div><label>Département</label><input type="text" name="department" value="<?= htmlspecialchars($job ? ($job['department'] ?? '') : '') ?>" placeholder="Tech, RH…"></div>
                    <?php if (!empty($typeContratOptions)) { ?>
                    <div><label>Type de contrat</label><select name="type_contrat"><option value="">— Choisir —</option><?php foreach ($typeContratOptions as $tc) { ?><option value="<?= htmlspecialchars($tc) ?>"<?= $typeContratVal === $tc ? ' selected' : '' ?>><?= htmlspecialchars($tc) ?></option><?php } ?></select></div>
                    <?php } ?>
                </div>
                <div class="field ff">
                    <label>Compétences recherchées <span style="color:var(--muted);">(séparées par virgules)</span></label>
                    <div class="skills-wrap job-skills-wrap" id="job-skills-wrap" onclick="document.getElementById('job-skills-input').focus()">
                        <?php foreach ($skillsList as $s) { ?><span class="sk-tag job-sk-tag"><?= htmlspecialchars($s) ?><span class="rm" onclick="removeJobSkill(this, event)" role="button" aria-label="Retirer">×</span></span><?php } ?>
                        <input type="text" class="sk-input" id="job-skills-input" placeholder="Ajouter une compétence, Entrée pour valider" autocomplete="off"/>
                    </div>
                    <input type="hidden" name="skills_raw" id="job-skills-raw" value="<?= htmlspecialchars($skillsVal) ?>">
                    <div style="font-size:11px;color:var(--muted);margin-top:4px;">Ces compétences sont utilisées par le moteur IA pour le matching candidats.</div>
                </div>
                <div class="field ff"><label>Description (optionnel)</label><textarea name="description" rows="2" placeholder="Missions, contexte…"><?= htmlspecialchars($job ? ($job['description'] ?? '') : '') ?></textarea></div>
                <div class="field ff"><label>Profil recherché / Exigences (optionnel)</label><textarea name="requirements" rows="2" placeholder="Compétences, expérience…"><?= htmlspecialchars($job ? ($job['requirements'] ?? '') : '') ?></textarea></div>
            </div>
            <div class="form-actions" style="display:flex;gap:8px;margin-top:16px;flex-wrap:wrap;">
                <button type="submit" class="btn btn-re"><?= htmlspecialchars($submitLabel ?? 'Publier') ?></button>
                <a href="<?= $job ? '/recruiter/jobs/' . (int)$job['id'] : '/recruiter/jobs' ?>" class="btn btn-ghost">Annuler</a>
            </div>
        </form>
    </div>
</div>

<script>
(function(){
  function syncJobSkills(){
    var tags = [];
    document.querySelectorAll('#job-skills-wrap .job-sk-tag').forEach(function(t){
      var txt = t.textContent.replace(/\s*×\s*$/,'').trim();
      if(txt) tags.push(txt);
    });
    document.getElementById('job-skills-raw').value = tags.join(', ');
  }
  window.removeJobSkill = function(el, ev){
    if (ev) { ev.preventDefault(); ev.stopPropagation(); }
    if (el && el.parentElement) { el.parentElement.remove(); syncJobSkills(); }
  };
  var inp = document.getElementById('job-skills-input');
  if(inp){
    inp.addEventListener('keydown', function(e){
      if((e.key === 'Enter' || e.key === ',') && this.value.trim()){
        e.preventDefault();
        var span = document.createElement('span');
        span.className = 'sk-tag job-sk-tag';
        span.innerHTML = this.value.trim() + ' <span class="rm" onclick="removeJobSkill(this, event)" role="button" aria-label="Retirer">×</span>';
        document.getElementById('job-skills-wrap').insertBefore(span, this);
        this.value = '';
        syncJobSkills();
      }
    });
  }
  document.getElementById('job-form').addEventListener('submit', syncJobSkills);
})();
</script>
