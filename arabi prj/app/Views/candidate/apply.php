<?php $jobId = (int)$job['id']; ?>
<div class="card" style="max-width:640px;">
    <div class="card-header"><div class="card-title"><?= htmlspecialchars($job['title']) ?></div></div>
    <div class="card-body">
        <?php if (!empty($job['department'])) { ?><p style="font-size:13px;color:var(--muted);margin-bottom:12px;"><?= htmlspecialchars($job['department']) ?></p><?php } ?>
        <?php if (!empty($job['description'])) { ?><div style="font-size:13px;line-height:1.6;margin-bottom:20px;white-space:pre-wrap;"><?= htmlspecialchars($job['description']) ?></div><?php } ?>
        <?php if (!empty($job['requirements'])) { ?><div style="font-size:12px;color:var(--muted);margin-bottom:20px;"><strong>Profil recherché :</strong><br><?= nl2br(htmlspecialchars($job['requirements'])) ?></div><?php } ?>

        <form method="post" action="/candidate/jobs/<?= $jobId ?>/apply">
            <?= \App\Core\Csrf::field() ?>
            <div class="field" style="margin-bottom:16px;">
                <label>Lettre de motivation (optionnel)</label>
                <textarea name="cover_letter" rows="5" placeholder="Présentez-vous et expliquez votre intérêt pour ce poste…"><?= htmlspecialchars($_POST['cover_letter'] ?? '') ?></textarea>
            </div>
            <p style="font-size:12px;color:var(--muted);margin-bottom:16px;">Vos informations de profil (coordonnées, expérience, compétences) seront transmises avec cette candidature.</p>
            <div style="display:flex;gap:10px;">
                <button type="submit" class="btn btn-ca">Envoyer ma candidature</button>
                <a href="/candidate/jobs" class="btn btn-ghost">Annuler</a>
            </div>
        </form>
    </div>
</div>
