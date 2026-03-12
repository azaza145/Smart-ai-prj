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
        return 'https://api.dicebear.com/7.x/bottts/svg?seed=' . urlencode($randomSeed) . '&backgroundColor=' . $avatarBgColor . '&radius=50&size=160';
    } else {
        // Animal style
        return 'https://api.dicebear.com/7.x/lorelei/svg?seed=' . urlencode($randomSeed) . '&backgroundColor=' . $avatarBgColor . '&radius=50&size=160';
    }
}

$c = $candidate ?? [];
$candidateId = (int)($c['id'] ?? 0);
$profile = isset($profile) && is_array($profile) ? $profile : \App\Models\Candidate::getProfile($candidateId);
$profile = \App\Services\CandidateProfileSchema::normalizeCandidateProfile($profile);
$name = trim(($c['prenom'] ?? '') . ' ' . ($c['nom'] ?? ''));
$fullName = trim($profile['full_name'] ?? '') !== '' ? $profile['full_name'] : $name;
$jobTitle = trim($profile['job_title'] ?? '') !== '' ? $profile['job_title'] : ($c['poste_actuel'] ?? '—');
$init = strtoupper(mb_substr($c['prenom'] ?? 'X', 0, 1) . mb_substr($c['nom'] ?? 'X', 0, 1));
if (strlen($init) < 2) $init = $init . 'X';
$avatarColor = getUniqueAvatarColor($candidateId);
$avatarUrl = getAvatarUrl($candidateId, $fullName ?: $name, $c['prenom'] ?? '');
$displayScore = $recommendation ? (isset($recommendation['score_pct']) ? round($recommendation['score_pct'], 1) : round(($recommendation['score'] ?? 0) * 100, 1)) : 0;
$scorePct = (int) $displayScore;
$rankDisplay = $recommendation ? (int)($recommendation['ranking'] ?? $recommendation['rank'] ?? 0) : 0;

$contact = $profile['contact'] ?? [];
$email = trim($contact['email'] ?? '') !== '' ? $contact['email'] : ($c['email'] ?? '');
$phone = trim($contact['phone'] ?? '') !== '' ? $contact['phone'] : ($c['telephone'] ?? '');
$city = trim($contact['city'] ?? '') !== '' ? $contact['city'] : ($c['ville'] ?? '');
$address = trim($contact['address'] ?? '');
$addressLine = $address !== '' ? ($city !== '' ? $address . ', ' . $city : $address) : $city;

$experience = $profile['experience'] ?? [];
$education = $profile['education'] ?? [];
$skills = $profile['skills'] ?? [];
$languages = $profile['languages'] ?? [];
$projects = $profile['projects'] ?? [];
$certifications = $profile['certifications'] ?? [];
$availability = trim($profile['availability'] ?? '') !== '' ? $profile['availability'] : ($c['disponibilite'] ?? '—');
$salary = trim($profile['salary_expectation'] ?? '') !== '' ? $profile['salary_expectation'] : ($c['pretention_salaire'] ?? '—');
$age = isset($c['age']) && (int)$c['age'] > 0 ? (int)$c['age'] : null;
$expYears = isset($c['experience_annees']) && (int)$c['experience_annees'] >= 0 ? (int)$c['experience_annees'] : null;
$company = trim($c['entreprise_actuelle'] ?? '') !== '' ? $c['entreprise_actuelle'] : null;

$emptyPh = \App\Services\CandidateProfileSchema::EMPTY_PLACEHOLDER;

$latestCv = $latestCv ?? null;
$basePathForCv = dirname(__DIR__, 3);
$hasValidPdf = false;
$pdfUrl = null;
if ($latestCv && !empty($latestCv['file_path'])) {
    $fullPath = \App\Models\Cv::resolveFullPath($basePathForCv, $latestCv['file_path']);
    if ($fullPath) { $hasValidPdf = true; $pdfUrl = '/recruiter/candidates/' . (int)$c['id'] . '/cv/' . (int)$latestCv['id']; }
}
if (!$hasValidPdf && !empty($cvs)) {
    foreach ($cvs as $cv) {
        if (!empty($cv['file_path']) && \App\Models\Cv::resolveFullPath($basePathForCv, $cv['file_path'])) {
            $hasValidPdf = true; $pdfUrl = '/recruiter/candidates/' . (int)$c['id'] . '/cv/' . (int)$cv['id']; break;
        }
    }
}
?>

<!-- Top Bar: Rank, Score, Application Status -->
<div class="card" style="margin-bottom:20px;">
    <div class="card-body" style="display:flex;flex-wrap:wrap;gap:16px;align-items:center;">
        <a href="/recruiter/jobs/<?= (int)$job['id'] ?>/results" class="btn btn-ghost">← Retour au classement</a>
        <?php if ($application) { ?>
        <form method="post" action="/recruiter/jobs/<?= (int)$job['id'] ?>/applications/<?= (int)$application['id'] ?>/status" style="display:inline;">
            <?= \App\Core\Csrf::field() ?>
            <select name="status" onchange="this.form.submit()" style="font-size:12px;padding:6px 12px;border-radius:6px;border:1.5px solid var(--border);">
                <option value="submitted" <?= ($application['status'] ?? '') === 'submitted' ? 'selected' : '' ?>>Envoyée</option>
                <option value="viewed" <?= ($application['status'] ?? '') === 'viewed' ? 'selected' : '' ?>>Consultée</option>
                <option value="shortlisted" <?= ($application['status'] ?? '') === 'shortlisted' ? 'selected' : '' ?>>Shortlist</option>
                <option value="rejected" <?= ($application['status'] ?? '') === 'rejected' ? 'selected' : '' ?>>Refusée</option>
            </select>
        </form>
        <?php } ?>
        <?php if ($recommendation) { ?>
        <span class="tag tg">Rang <?= $rankDisplay ?></span>
        <div class="score-row" style="align-items:center;gap:10px;">
            <div class="score-bar" style="min-width:100px;"><div class="score-fill" style="width:<?= max(2, min(100, $scorePct)) ?>%;background:var(--ca);"></div></div>
            <span style="font-weight:700;color:var(--ca);"><?= $scorePct ?>%</span>
            <span style="font-size:11px;color:var(--muted);">Score IA</span>
        </div>
        <?php } ?>
        <div style="display:flex;gap:8px;margin-left:auto;flex-wrap:wrap;align-items:center;">
            <?php if ($hasValidPdf) { ?>
            <form method="post" action="/recruiter/candidates/<?= (int)$c['id'] ?>/fill-from-cv" style="display:inline;">
                <?= \App\Core\Csrf::field() ?>
                <input type="hidden" name="overwrite" value="1"/>
                <button type="submit" class="btn btn-outline-re" style="padding:6px 14px;font-size:12px;" title="Réextraire les infos (expérience, formation, compétences) depuis le PDF avec l’IA">🔄 Re-extraire le CV</button>
            </form>
            <?php } ?>
            <a href="/recruiter/candidates/<?= (int)$c['id'] ?>/cv-pdf" class="btn btn-re" style="padding:6px 14px;font-size:12px;">📥 PDF</a>
            <button onclick="window.print()" class="btn btn-outline-re" style="padding:6px 14px;font-size:12px;">🖨️ Imprimer</button>
        </div>
    </div>
</div>

<!-- CV Viewer : PDF déposé ou CV généré depuis le profil -->
<?php
$cvProfilUrl = '/recruiter/candidates/' . (int)$c['id'] . '/cv-profil';
$cvDisplayUrl = $hasValidPdf ? $pdfUrl : $cvProfilUrl;
$appCount = isset($applications) && is_array($applications) ? count($applications) : 0;
?>
<div class="candidate-detail-grid" style="display:grid;grid-template-columns:minmax(0,1fr) 420px;gap:28px;align-items:start;margin-bottom:24px;">
  <!-- LEFT: Profil candidat (style cartes, pleine largeur) -->
  <div id="cv-content" class="candidate-profile-left" style="min-width:0;">
    <!-- Header coloré -->
    <div class="candidate-detail-hero" style="background:<?= htmlspecialchars($avatarColor) ?>;padding:32px 40px;color:#fff;border-radius:16px 16px 0 0;box-shadow:0 4px 20px rgba(0,0,0,0.12);">
      <div style="display:flex;flex-wrap:wrap;align-items:center;gap:24px;justify-content:space-between;">
        <div style="display:flex;align-items:center;gap:24px;">
          <div style="width:80px;height:80px;border-radius:50%;background:rgba(255,255,255,0.3);border:3px solid rgba(255,255,255,0.5);flex-shrink:0;overflow:hidden;box-shadow:0 4px 12px rgba(0,0,0,0.15);display:flex;align-items:center;justify-content:center;">
            <img src="<?= htmlspecialchars($avatarUrl) ?>" alt="" style="width:100%;height:100%;object-fit:cover;" onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
            <div style="display:none;width:100%;height:100%;align-items:center;justify-content:center;font-size:28px;font-weight:700;"><?= htmlspecialchars($init) ?></div>
          </div>
          <div>
            <h1 style="font-size:28px;font-weight:700;margin:0 0 6px 0;letter-spacing:-0.02em;line-height:1.2;"><?= htmlspecialchars($fullName !== '' ? $fullName : '—') ?></h1>
            <p style="font-size:16px;opacity:0.95;margin:0 0 14px 0;"><?= htmlspecialchars($jobTitle !== '' ? $jobTitle : '—') ?></p>
            <div style="display:flex;flex-wrap:wrap;gap:16px 24px;font-size:14px;">
              <?php if ($email !== '') { ?><span style="display:inline-flex;align-items:center;gap:6px;">✉️ <a href="mailto:<?= htmlspecialchars($email) ?>" style="color:inherit;text-decoration:none;"><?= htmlspecialchars($email) ?></a></span><?php } ?>
              <?php if ($phone !== '') { ?><span style="display:inline-flex;align-items:center;gap:6px;">📞 <?= htmlspecialchars($phone) ?></span><?php } ?>
              <?php if ($addressLine !== '') { ?><span style="display:inline-flex;align-items:center;gap:6px;">📍 <?= htmlspecialchars($addressLine) ?></span><?php } ?>
            </div>
          </div>
        </div>
        <div style="display:flex;flex-direction:column;align-items:flex-end;gap:12px;">
          <div style="display:flex;gap:10px;">
            <a href="/recruiter/candidates/<?= (int)$c['id'] ?>/cv-pdf" class="btn" style="background:rgba(255,255,255,0.25);color:#fff;border:2px solid rgba(255,255,255,0.5);padding:10px 20px;font-size:13px;font-weight:600;border-radius:10px;text-decoration:none;">📥 PDF</a>
            <button type="button" onclick="window.print()" class="btn" style="background:rgba(255,255,255,0.25);color:#fff;border:2px solid rgba(255,255,255,0.5);padding:10px 20px;font-size:13px;font-weight:600;border-radius:10px;cursor:pointer;">🖨️ Imprimer</button>
          </div>
          <div style="font-size:15px;font-weight:700;"><span style="opacity:0.9;"><?= $appCount ?></span> candidature(s)</div>
        </div>
      </div>
    </div>

    <!-- Cartes contenu -->
    <div class="candidate-detail-cards" style="margin-top:0;">
      <!-- Expérience -->
      <div class="candidate-detail-card" style="background:#fff;border-radius:0 0 16px 16px;box-shadow:0 4px 20px rgba(0,0,0,0.08);border:1px solid var(--border);border-top:none;overflow:hidden;">
        <div style="background:var(--ca-l);padding:14px 24px;display:flex;align-items:center;gap:12px;">
          <span style="font-size:22px;">💼</span>
          <h2 style="font-size:13px;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:var(--g-dark);margin:0;">Expérience professionnelle</h2>
        </div>
        <div style="padding:24px;">
          <?php if ($jobTitle !== '—' || $company !== null || $expYears !== null || count($experience) > 0) { ?>
            <?php if ($jobTitle !== '' && $jobTitle !== '—') { ?><div style="font-size:16px;font-weight:700;color:var(--text);margin-bottom:4px;"><?= htmlspecialchars($jobTitle) ?></div><?php } ?>
            <?php if ($company !== null) { ?><div style="font-size:14px;color:var(--ca);font-weight:600;margin-bottom:6px;"><?= htmlspecialchars($company) ?></div><?php } ?>
            <div style="font-size:13px;color:var(--muted);margin-bottom:12px;">
              <?php if ($expYears !== null) { ?>Expérience : <?= $expYears ?> an(s)<?php } ?>
              <?php if ($age !== null) { ?> · Âge : <?= $age ?> ans<?php } ?>
            </div>
            <?php if (count($experience) > 0) { foreach ($experience as $e) {
              $exTitle = trim($e['title'] ?? ''); $exCompany = trim($e['company'] ?? ''); $exDuration = trim($e['duration'] ?? ''); $exDesc = trim($e['description'] ?? '');
            ?>
            <div style="padding:14px 0;border-top:1px solid var(--border);">
              <?php if ($exTitle !== '') { ?><div style="font-weight:700;font-size:14px;"><?= htmlspecialchars($exTitle) ?></div><?php } ?>
              <?php if ($exCompany !== '' || $exDuration !== '') { ?><div style="font-size:13px;color:var(--ca);"><?= htmlspecialchars($exCompany) ?><?= $exDuration !== '' ? ' · ' . htmlspecialchars($exDuration) : '' ?></div><?php } ?>
              <?php if ($exDesc !== '') { ?><div style="font-size:13px;color:var(--text);margin-top:6px;line-height:1.5;"><?= nl2br(htmlspecialchars(mb_substr($exDesc, 0, 400) . (mb_strlen($exDesc) > 400 ? '…' : ''))) ?></div><?php } ?>
            </div>
            <?php } } ?>
          <?php } else { ?><div style="color:var(--muted);font-size:14px;"><?= htmlspecialchars($emptyPh) ?></div><?php } ?>
        </div>
      </div>

      <!-- Langues -->
      <div class="candidate-detail-card" style="background:#fff;border-radius:16px;box-shadow:0 4px 20px rgba(0,0,0,0.08);border:1px solid var(--border);margin-top:20px;overflow:hidden;">
        <div style="background:var(--ca-l);padding:14px 24px;display:flex;align-items:center;gap:12px;">
          <span style="font-size:22px;">🌐</span>
          <h2 style="font-size:13px;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:var(--g-dark);margin:0;">Langues</h2>
        </div>
        <div style="padding:24px;">
          <?php if (count($languages) > 0) { ?>
          <div style="display:flex;flex-direction:column;gap:14px;">
            <?php foreach ($languages as $lang) { ?>
            <div>
              <div style="font-size:14px;font-weight:600;margin-bottom:6px;"><?= htmlspecialchars($lang) ?></div>
              <div style="height:8px;background:var(--border);border-radius:4px;overflow:hidden;"><div style="height:100%;width:85%;background:linear-gradient(90deg,var(--ca),var(--g-mid));border-radius:4px;"></div></div>
            </div>
            <?php } ?>
          </div>
          <?php } else { ?><div style="color:var(--muted);font-size:14px;"><?= htmlspecialchars($emptyPh) ?></div><?php } ?>
        </div>
      </div>

      <!-- Certifications -->
      <?php if (count($certifications) > 0) { ?>
      <div class="candidate-detail-card" style="background:#fff;border-radius:16px;box-shadow:0 4px 20px rgba(0,0,0,0.08);border:1px solid var(--border);margin-top:20px;overflow:hidden;">
        <div style="background:var(--ca-l);padding:14px 24px;display:flex;align-items:center;gap:12px;">
          <span style="font-size:22px;">🏆</span>
          <h2 style="font-size:13px;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:var(--g-dark);margin:0;">Certifications</h2>
        </div>
        <div style="padding:24px;">
          <ul style="margin:0;padding-left:20px;">
            <?php foreach ($certifications as $cert) { ?><li style="font-size:14px;margin-bottom:6px;"><?= htmlspecialchars($cert) ?></li><?php } ?>
          </ul>
        </div>
      </div>
      <?php } ?>

      <!-- Informations complémentaires -->
      <div class="candidate-detail-card" style="background:#fff;border-radius:16px;box-shadow:0 4px 20px rgba(0,0,0,0.08);border:1px solid var(--border);margin-top:20px;overflow:hidden;">
        <div style="background:linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);padding:14px 24px;display:flex;align-items:center;gap:12px;">
          <span style="font-size:22px;">ℹ️</span>
          <h2 style="font-size:13px;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:#1565c0;margin:0;">Informations complémentaires</h2>
        </div>
        <div style="padding:24px;">
          <div style="display:flex;flex-direction:column;gap:14px;">
            <?php if ($availability !== '—' && $availability !== '') { ?>
            <div><div style="font-size:11px;font-weight:700;letter-spacing:0.5px;text-transform:uppercase;color:var(--muted);margin-bottom:4px;">Disponibilité</div><div style="font-size:15px;font-weight:600;"><?= htmlspecialchars($availability) ?></div></div>
            <?php } ?>
            <?php if ($salary !== '—' && $salary !== '') { ?>
            <div><div style="font-size:11px;font-weight:700;letter-spacing:0.5px;text-transform:uppercase;color:var(--muted);margin-bottom:4px;">Prétention salariale</div><div style="font-size:15px;font-weight:600;"><?= htmlspecialchars($salary) ?></div></div>
            <?php } ?>
            <?php if (($availability === '—' || $availability === '') && ($salary === '—' || $salary === '')) { ?><div style="color:var(--muted);font-size:14px;"><?= htmlspecialchars($emptyPh) ?></div><?php } ?>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- RIGHT: CV (iframe) -->
  <div class="card" style="margin-bottom:0;position:sticky;top:24px;">
    <div class="card-header">
      <div class="card-title"><div class="ci" style="background:var(--ca-l);">📄</div>CV</div>
      <div style="display:flex;gap:8px;flex-wrap:wrap;">
        <?php if (!empty($cvs)) { foreach ($cvs as $cv) { ?>
        <a href="/recruiter/candidates/<?= (int)$c['id'] ?>/cv/<?= (int)$cv['id'] ?>" class="btn btn-outline-re btn-sm" style="padding:4px 10px;font-size:11px;">📥 <?= htmlspecialchars($cv['original_name']) ?></a>
        <?php } } ?>
        <a href="/recruiter/candidates/<?= (int)$c['id'] ?>/cv-pdf" class="btn btn-re btn-sm">📥 Télécharger PDF</a>
      </div>
    </div>
    <div class="card-body" style="padding:0;">
      <iframe src="<?= htmlspecialchars($cvDisplayUrl) ?>" style="width:100%;height:780px;border:none;display:block;" title="CV candidat"></iframe>
      <?php if (!$hasValidPdf): ?>
      <div style="padding:8px 12px;background:var(--g-pale);border-top:1px solid var(--border);font-size:11px;color:var(--muted);">CV généré à partir du profil.</div>
      <?php endif; ?>
    </div>
  </div>
</div>

<style>
  .candidate-detail-hero { transition: box-shadow .2s ease; }
  .candidate-detail-hero:hover { box-shadow: 0 8px 28px rgba(0,0,0,0.15); }
  .candidate-detail-card { transition: transform .15s ease, box-shadow .2s ease; }
  .candidate-detail-card:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,0.1); }
  @media (max-width: 1024px) {
    .candidate-detail-grid { grid-template-columns: 1fr !important; }
    .candidate-detail-grid .card { position: static !important; }
  }
</style>
<style media="print">
  @media print {
    .topnav, .btn, a[href], form, .card-header, #cv-content, .candidate-profile-left { display: none !important; }
    body { background: white !important; }
    .card { border: none !important; box-shadow: none !important; page-break-inside: avoid; }
  }
</style>