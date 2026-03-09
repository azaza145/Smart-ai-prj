<?php
/**
 * Aperçu CV structuré — design RecruteIA (FR/EN), profil normalisé uniquement.
 * Variables: $profile (canonical), $c (candidate), $cvExtractedText (optionnel), $cvFileName.
 */
$profile = $profile ?? [];
$profile = \App\Services\CandidateProfileSchema::normalizeCandidateProfile($profile);
$contact = $profile['contact'] ?? [];
$emptyPh = \App\Services\CandidateProfileSchema::EMPTY_PLACEHOLDER;

function _cvDisp($v) {
    return \App\Services\CandidateProfileSchema::displayValue($v);
}

$fullName = trim($profile['full_name'] ?? '') !== '' ? $profile['full_name'] : _cvDisp($profile['full_name'] ?? null);
$jobTitle = trim($profile['job_title'] ?? '') !== '' ? $profile['job_title'] : _cvDisp($profile['job_title'] ?? null);
$summary = trim($profile['summary'] ?? '') !== '' ? $profile['summary'] : _cvDisp($profile['summary'] ?? null);
$availability = trim($profile['availability'] ?? '') !== '' ? $profile['availability'] : _cvDisp($profile['availability'] ?? null);
$email = trim($contact['email'] ?? '');
$phone = trim($contact['phone'] ?? '');
$address = trim($contact['address'] ?? '');
$city = trim($contact['city'] ?? '');
$linkedin = trim($contact['linkedin'] ?? '');
$addressLine = $address !== '' ? $address : ($city !== '' ? $city : $emptyPh);
if ($address !== '' && $city !== '') {
    $addressLine = $address . ', ' . $city;
} elseif ($city !== '') {
    $addressLine = $city;
}

$education = $profile['education'] ?? [];
$experience = $profile['experience'] ?? [];
$skills = $profile['skills'] ?? [];
$languages = $profile['languages'] ?? [];
$hobbies = $profile['hobbies'] ?? [];
$candidateId = (int)($c['id'] ?? 0);
$skillsCount = count($skills);
?>
<div class="cv-struct" id="cv-struct-preview">
  <!-- LANG BAR -->
  <div class="cv-lang-bar">
    <div class="cv-lang-left">
      <div class="cv-lang-logo">Recrute<em>IA</em></div>
      <div class="cv-lang-sep"></div>
      <span class="cv-lang-label">Extraction CV structurée</span>
    </div>
    <div class="cv-lang-right">
      <span class="cv-lang-txt">LANGUE</span>
      <div class="cv-lang-btns">
        <button type="button" class="cv-lbtn active" data-lang="fr">🇫🇷 FR</button>
        <button type="button" class="cv-lbtn" data-lang="en">🇬🇧 EN</button>
      </div>
    </div>
  </div>

  <!-- HERO -->
  <div class="cv-hero">
    <div class="cv-hero-inner">
      <div class="cv-hero-left">
        <div class="cv-hero-eyebrow">
          <span class="fr-text">Profil extrait du CV</span>
          <span class="en-text">Extracted CV Profile</span>
        </div>
        <div class="cv-hero-name"><?= htmlspecialchars($fullName !== '' ? $fullName : '—') ?></div>
        <div class="cv-hero-title"><?= htmlspecialchars($jobTitle !== '' ? $jobTitle : '—') ?></div>
        <div class="cv-hero-contact">
          <?php if ($addressLine !== '' && $addressLine !== $emptyPh) { ?>
          <div class="cv-hc-item"><span class="cv-hc-icon">📍</span><?= htmlspecialchars($addressLine) ?></div>
          <?php } ?>
          <?php if ($phone !== '') { ?>
          <div class="cv-hc-item"><span class="cv-hc-icon">📞</span><?= htmlspecialchars($phone) ?></div>
          <?php } ?>
          <?php if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) { ?>
          <div class="cv-hc-item"><span class="cv-hc-icon">✉️</span><a href="mailto:<?= htmlspecialchars($email) ?>"><?= htmlspecialchars($email) ?></a></div>
          <?php } ?>
          <?php if ($linkedin !== '') { ?>
          <div class="cv-hc-item"><span class="cv-hc-icon">🔗</span><a href="<?= htmlspecialchars($linkedin) ?>" target="_blank" rel="noopener"><?= htmlspecialchars(preg_replace('#^https?://(www\.)?#i', '', $linkedin)) ?> ↗</a></div>
          <?php } ?>
        </div>
      </div>
      <div class="cv-hero-right">
        <?php if ($availability !== '' && $availability !== $emptyPh) { ?>
        <div class="cv-availability-badge">
          <span class="cv-avail-dot"></span>
          <span class="fr-text"><?= htmlspecialchars($availability) ?></span>
          <span class="en-text"><?= htmlspecialchars($availability) ?></span>
        </div>
        <?php } ?>
        <div class="cv-exp-tag">
          <span class="fr-text">Profil normalisé</span>
          <span class="en-text">Normalised profile</span>
        </div>
        <?php if ($skillsCount > 0) { ?>
        <div class="cv-exp-tag cv-exp-tag-ok">
          ✓ <span class="fr-text"><?= $skillsCount ?> compétence(s) extraite(s)</span>
          <span class="en-text"><?= $skillsCount ?> skill(s) extracted</span>
        </div>
        <?php } ?>
      </div>
    </div>
  </div>

  <!-- MAIN -->
  <div class="cv-main">
    <div class="cv-grid-2">

      <div class="cv-col-left">
        <!-- PROFIL -->
        <?php if ($summary !== '' && $summary !== $emptyPh) { ?>
        <div class="cv-section">
          <div class="cv-section-title">
            <span class="fr-text">Profil</span>
            <span class="en-text">Profile</span>
          </div>
          <div class="cv-card">
            <div class="cv-card-inner">
              <p class="cv-summary-text"><?= nl2br(htmlspecialchars($summary)) ?></p>
            </div>
          </div>
        </div>
        <?php } ?>

        <!-- FORMATION -->
        <div class="cv-section">
          <div class="cv-section-title">
            <span class="fr-text">Formation</span>
            <span class="en-text">Education</span>
          </div>
          <div class="cv-card">
            <div class="cv-timeline">
              <?php if (count($education) > 0) { foreach ($education as $i => $ed) {
                $deg = trim($ed['degree'] ?? '');
                $school = trim($ed['school'] ?? '');
                $year = trim($ed['year'] ?? '');
                $details = trim($ed['details'] ?? '');
                $isFirst = $i === 0;
              ?>
              <div class="cv-tl-item">
                <div class="cv-tl-left">
                  <div class="cv-tl-dot <?= $isFirst ? 'filled' : 'muted' ?>"></div>
                  <?php if ($i < count($education) - 1) { ?><div class="cv-tl-line"></div><?php } ?>
                </div>
                <div class="cv-tl-body">
                  <?php if ($year !== '') { ?><div class="cv-tl-meta <?= $isFirst ? '' : 'muted' ?>"><?= htmlspecialchars($year) ?></div><?php } ?>
                  <div class="cv-tl-title"><?= htmlspecialchars($deg !== '' ? $deg : '—') ?></div>
                  <?php if ($school !== '') { ?><div class="cv-tl-org"><?= htmlspecialchars($school) ?></div><?php } ?>
                  <?php if ($details !== '') { ?>
                  <ul class="cv-tl-bullets">
                    <li><?= nl2br(htmlspecialchars($details)) ?></li>
                  </ul>
                  <?php } ?>
                </div>
              </div>
              <?php } } else { ?>
              <div class="cv-tl-item">
                <div class="cv-tl-left"><div class="cv-tl-dot muted"></div></div>
                <div class="cv-tl-body"><div class="cv-tl-title"><?= htmlspecialchars($emptyPh) ?></div></div>
              </div>
              <?php } ?>
            </div>
          </div>
        </div>

        <!-- EXPÉRIENCE -->
        <div class="cv-section">
          <div class="cv-section-title">
            <span class="fr-text">Expérience Professionnelle</span>
            <span class="en-text">Work Experience</span>
          </div>
          <div class="cv-card">
            <div class="cv-timeline">
              <?php if (count($experience) > 0) { foreach ($experience as $i => $e) {
                $title = trim($e['title'] ?? '');
                $company = trim($e['company'] ?? '');
                $duration = trim($e['duration'] ?? '');
                $desc = trim($e['description'] ?? '');
                $isFirst = $i === 0;
              ?>
              <div class="cv-tl-item">
                <div class="cv-tl-left">
                  <div class="cv-tl-dot <?= $isFirst ? 'filled green' : 'muted' ?>"></div>
                  <?php if ($i < count($experience) - 1) { ?><div class="cv-tl-line"></div><?php } ?>
                </div>
                <div class="cv-tl-body">
                  <?php if ($duration !== '') { ?><div class="cv-tl-meta <?= $isFirst ? 'green' : 'muted' ?>"><?= htmlspecialchars($duration) ?></div><?php } ?>
                  <div class="cv-tl-title"><?= htmlspecialchars($title !== '' ? $title : '—') ?></div>
                  <?php if ($company !== '') { ?><div class="cv-tl-org"><?= htmlspecialchars($company) ?></div><?php } ?>
                  <?php if ($desc !== '') { ?>
                  <ul class="cv-tl-bullets">
                    <li><?= nl2br(htmlspecialchars(mb_substr($desc, 0, 500) . (mb_strlen($desc) > 500 ? '…' : ''))) ?></li>
                  </ul>
                  <?php } ?>
                </div>
              </div>
              <?php } } else { ?>
              <div class="cv-tl-item">
                <div class="cv-tl-left"><div class="cv-tl-dot muted"></div></div>
                <div class="cv-tl-body"><div class="cv-tl-title"><?= htmlspecialchars($emptyPh) ?></div></div>
              </div>
              <?php } ?>
            </div>
          </div>
        </div>

        <!-- COMPÉTENCES -->
        <div class="cv-section">
          <div class="cv-section-title">
            <span class="fr-text">Compétences Techniques</span>
            <span class="en-text">Technical Skills</span>
          </div>
          <div class="cv-card">
            <div class="cv-card-inner">
              <div class="cv-skill-cat">
                <div class="cv-skill-chips">
                  <?php foreach ($skills as $s) { ?><span class="cv-chip cv-chip-blue"><?= htmlspecialchars($s) ?></span><?php } ?>
                  <?php if (count($skills) === 0) { ?><span class="cv-chip cv-chip-muted"><?= htmlspecialchars($emptyPh) ?></span><?php } ?>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="cv-col-right">
        <!-- CONTACT -->
        <div class="cv-scard">
          <div class="cv-scard-header">📇 <span class="fr-text">Contact</span><span class="en-text">Contact</span></div>
          <div class="cv-scard-body" style="padding:0;">
            <?php if ($addressLine !== '' && $addressLine !== $emptyPh) { ?>
            <div class="cv-contact-item">
              <div class="cv-contact-icon">📍</div>
              <div>
                <div class="cv-contact-label"><span class="fr-text">Adresse</span><span class="en-text">Address</span></div>
                <div class="cv-contact-value"><?= htmlspecialchars($addressLine) ?></div>
              </div>
            </div>
            <?php } ?>
            <?php if ($phone !== '') { ?>
            <div class="cv-contact-item">
              <div class="cv-contact-icon">📞</div>
              <div>
                <div class="cv-contact-label"><span class="fr-text">Téléphone</span><span class="en-text">Phone</span></div>
                <div class="cv-contact-value"><?= htmlspecialchars($phone) ?></div>
              </div>
            </div>
            <?php } ?>
            <?php if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) { ?>
            <div class="cv-contact-item">
              <div class="cv-contact-icon">✉️</div>
              <div>
                <div class="cv-contact-label">Email</div>
                <div class="cv-contact-value"><a href="mailto:<?= htmlspecialchars($email) ?>"><?= htmlspecialchars($email) ?></a></div>
              </div>
            </div>
            <?php } ?>
            <?php if ($linkedin !== '') { ?>
            <div class="cv-contact-item">
              <div class="cv-contact-icon">🔗</div>
              <div>
                <div class="cv-contact-label">LinkedIn</div>
                <div class="cv-contact-value"><a href="<?= htmlspecialchars($linkedin) ?>" target="_blank" rel="noopener">linkedin.com ↗</a></div>
              </div>
            </div>
            <?php } ?>
            <?php if ($addressLine === $emptyPh && $phone === '' && ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) && $linkedin === '') { ?>
            <div class="cv-contact-item">
              <div class="cv-contact-value" style="color:var(--cv-muted2);"><?= htmlspecialchars($emptyPh) ?></div>
            </div>
            <?php } ?>
          </div>
        </div>

        <!-- LANGUES -->
        <div class="cv-scard">
          <div class="cv-scard-header">🌐 <span class="fr-text">Langues</span><span class="en-text">Languages</span></div>
          <div class="cv-scard-body">
            <?php if (count($languages) > 0) { foreach ($languages as $lang) {
              $lang = trim($lang);
              if ($lang === '') continue;
            ?>
            <div class="cv-lang-item">
              <div class="cv-lang-row">
                <span class="cv-lang-name"><?= htmlspecialchars($lang) ?></span>
              </div>
              <div class="cv-lang-bar"><div class="cv-lang-fill" style="width:85%;"></div></div>
            </div>
            <?php } } else { ?>
            <div class="cv-contact-value" style="color:var(--cv-muted2);"><?= htmlspecialchars($emptyPh) ?></div>
            <?php } ?>
          </div>
        </div>

        <!-- LOISIRS -->
        <?php if (count($hobbies) > 0) { ?>
        <div class="cv-scard">
          <div class="cv-scard-header">✦ <span class="fr-text">Loisirs</span><span class="en-text">Interests</span></div>
          <div class="cv-scard-body">
            <div class="cv-hobby-grid">
              <?php foreach ($hobbies as $h) { $h = trim($h); if ($h === '') continue; ?>
              <div class="cv-hobby"><?= htmlspecialchars($h) ?></div>
              <?php } ?>
            </div>
          </div>
        </div>
        <?php } ?>

        <!-- DISPONIBILITÉ -->
        <?php if ($availability !== '' && $availability !== $emptyPh) { ?>
        <div class="cv-scard">
          <div class="cv-scard-header">📅 <span class="fr-text">Disponibilité</span><span class="en-text">Availability</span></div>
          <div class="cv-scard-body">
            <div style="text-align:center;padding:8px 0;">
              <div style="font-size:18px;font-weight:700;color:var(--cv-ink);margin-bottom:4px;"><?= htmlspecialchars($availability) ?></div>
              <div class="cv-chip cv-chip-green" style="margin-top:8px;display:inline-flex;"><span class="fr-text">Profil à jour</span><span class="en-text">Profile up to date</span></div>
            </div>
          </div>
        </div>
        <?php } ?>
      </div>
    </div>
  </div>

  <!-- ACTIONS BAR -->
  <div class="cv-actions-bar">
    <div class="cv-actions-left">
      <form method="post" action="/recruiter/candidates/<?= $candidateId ?>/fill-from-cv" id="cv-prefill-form" style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
        <?= \App\Core\Csrf::field() ?>
        <label class="cv-check-row">
          <input type="checkbox" name="overwrite" value="1"/>
          <span class="fr-text">Écraser les champs existants</span>
          <span class="en-text">Overwrite existing fields</span>
        </label>
      </form>
      <div class="cv-actions-note">
        <span class="fr-text">CV structuré à partir du profil normalisé. Les champs vides apparaissent avec « — ».</span>
        <span class="en-text">Profile structured from normalised CV. Empty fields appear as « — ».</span>
      </div>
    </div>
    <div class="cv-actions-btns">
      <a href="/recruiter/candidates/<?= $candidateId ?>/cv-pdf" class="cv-btn cv-btn-ghost cv-btn-sm" target="_blank">
        <span class="fr-text">⬇ Exporter PDF</span>
        <span class="en-text">⬇ Export PDF</span>
      </a>
      <button type="button" class="cv-btn cv-btn-ghost cv-btn-sm" id="cv-copy-data-btn">
        <span class="fr-text">📋 Copier les données</span>
        <span class="en-text">📋 Copy data</span>
      </button>
      <button type="submit" form="cv-prefill-form" class="cv-btn cv-btn-blue">
        ✦ <span class="fr-text">Pré-remplir le profil</span>
        <span class="en-text">Pre-fill profile</span>
      </button>
    </div>
  </div>
</div>

<!-- TOAST -->
<div class="cv-toast" id="cv-toast">
  <span id="cv-toast-icon">✅</span>
  <span id="cv-toast-msg"></span>
</div>

<style>
.cv-struct{--cv-bg:#f5f3ee;--cv-bg2:#fff;--cv-bg3:#f0ede6;--cv-border:#e2ddd4;--cv-ink:#1c1a17;--cv-ink2:#3a3730;--cv-muted:#7a7569;--cv-muted2:#b0ab9e;--cv-blue:#2563eb;--cv-blue-d:#1d4ed8;--cv-blue-l:rgba(37,99,235,.08);--cv-blue-m:rgba(37,99,235,.15);--cv-green:#0a7c52;--cv-green-l:rgba(10,124,82,.09);--cv-r:14px;--cv-font-b:'Segoe UI',system-ui,sans-serif;}
.cv-struct *,.cv-struct *::before,.cv-struct *::after{box-sizing:border-box;}
.cv-lang-bar{background:var(--cv-ink);padding:8px 24px;display:flex;align-items:center;justify-content:space-between;}
.cv-lang-left,.cv-lang-right{display:flex;align-items:center;gap:10px;}
.cv-lang-logo{font-size:16px;color:#fff;}.cv-lang-logo em{color:#7dd3fc;font-style:italic;}
.cv-lang-sep{width:1px;height:16px;background:rgba(255,255,255,.2);}
.cv-lang-label{font-size:11px;color:rgba(255,255,255,.5);font-weight:500;}
.cv-lang-txt{font-size:11px;color:rgba(255,255,255,.4);font-weight:500;}
.cv-lang-btns{display:flex;gap:4px;}
.cv-lbtn{padding:4px 12px;border-radius:6px;font-size:11px;font-weight:700;cursor:pointer;border:none;background:rgba(255,255,255,.1);color:rgba(255,255,255,.5);}
.cv-lbtn:hover{background:rgba(255,255,255,.2);color:#fff;}
.cv-lbtn.active{background:var(--cv-blue);color:#fff;}
.cv-hero{background:var(--cv-bg2);border-bottom:1px solid var(--cv-border);padding:36px 40px 0;}
.cv-hero-inner{max-width:940px;margin:0 auto;display:flex;align-items:flex-end;justify-content:space-between;gap:24px;}
.cv-hero-left{padding-bottom:32px;flex:1;}
.cv-hero-eyebrow{font-size:10px;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:var(--cv-blue);margin-bottom:10px;}
.cv-hero-name{font-size:40px;line-height:1.05;color:var(--cv-ink);margin-bottom:6px;font-family:Georgia,serif;}
.cv-hero-title{font-size:15px;color:var(--cv-muted);margin-bottom:14px;}
.cv-hero-contact{display:flex;flex-wrap:wrap;gap:6px 16px;}
.cv-hc-item{display:flex;align-items:center;gap:5px;font-size:12px;color:var(--cv-muted);}
.cv-hc-item a{color:var(--cv-blue);text-decoration:none;}
.cv-hc-icon{width:20px;height:20px;border-radius:5px;background:var(--cv-blue-l);display:inline-flex;align-items:center;justify-content:center;font-size:10px;}
.cv-hero-right{padding-bottom:32px;display:flex;flex-direction:column;align-items:flex-end;gap:8px;}
.cv-availability-badge{background:var(--cv-green-l);border:1px solid rgba(10,124,82,.2);border-radius:100px;padding:6px 14px;display:flex;align-items:center;gap:6px;font-size:12px;font-weight:600;color:var(--cv-green);}
.cv-avail-dot{width:7px;height:7px;border-radius:50%;background:var(--cv-green);}
.cv-exp-tag{background:var(--cv-blue-l);border:1px solid var(--cv-blue-m);border-radius:8px;padding:5px 12px;font-size:11px;font-weight:700;color:var(--cv-blue);}
.cv-exp-tag-ok{background:var(--cv-green-l);color:var(--cv-green);border-color:rgba(10,124,82,.2);}
.cv-main{max-width:940px;margin:0 auto;padding:28px 40px 60px;}
.cv-grid-2{display:grid;grid-template-columns:1fr 280px;gap:20px;align-items:start;}
.cv-section{margin-bottom:20px;}
.cv-section-title{font-size:10px;font-weight:800;letter-spacing:2px;text-transform:uppercase;color:var(--cv-muted);margin-bottom:14px;display:flex;align-items:center;gap:8px;}
.cv-section-title::after{content:'';flex:1;height:1px;background:var(--cv-border);}
.cv-card{background:var(--cv-bg2);border:1px solid var(--cv-border);border-radius:var(--cv-r);overflow:hidden;margin-bottom:14px;}
.cv-card-inner{padding:18px 20px;}
.cv-summary-text{font-size:13px;line-height:1.75;color:var(--cv-ink2);border-left:3px solid var(--cv-blue);padding-left:14px;}
.cv-timeline{display:flex;flex-direction:column;gap:0;}
.cv-tl-item{display:flex;gap:14px;padding:16px 20px;border-bottom:1px solid var(--cv-border);}
.cv-tl-item:last-child{border-bottom:none;}
.cv-tl-left{display:flex;flex-direction:column;align-items:center;width:10px;flex-shrink:0;padding-top:5px;}
.cv-tl-dot{width:10px;height:10px;border-radius:50%;border:2px solid var(--cv-blue);background:var(--cv-bg2);}
.cv-tl-dot.filled{background:var(--cv-blue);}
.cv-tl-dot.green{border-color:var(--cv-green);background:var(--cv-green);}
.cv-tl-dot.muted{border-color:var(--cv-muted2);}
.cv-tl-line{flex:1;width:2px;background:var(--cv-border);margin-top:2px;}
.cv-tl-item:last-child .cv-tl-line{display:none;}
.cv-tl-meta{font-size:10px;font-weight:700;letter-spacing:.8px;text-transform:uppercase;color:var(--cv-blue);margin-bottom:4px;}
.cv-tl-meta.green{color:var(--cv-green);}
.cv-tl-meta.muted{color:var(--cv-muted2);}
.cv-tl-title{font-size:14px;font-weight:700;color:var(--cv-ink);margin-bottom:2px;}
.cv-tl-org{font-size:12px;color:var(--cv-muted);font-weight:500;margin-bottom:8px;}
.cv-tl-bullets{list-style:none;padding:0;margin:0;}
.cv-tl-bullets li{font-size:12px;color:var(--cv-muted);line-height:1.5;margin-bottom:3px;}
.cv-chip{padding:3px 10px;border-radius:6px;font-size:11px;font-weight:600;display:inline-flex;}
.cv-chip-blue{background:var(--cv-blue-l);color:var(--cv-blue-d);border:1px solid var(--cv-blue-m);}
.cv-chip-green{background:var(--cv-green-l);color:var(--cv-green);border:1px solid rgba(10,124,82,.15);}
.cv-chip-muted{background:var(--cv-bg3);color:var(--cv-muted);border:1px solid var(--cv-border);}
.cv-skill-chips{display:flex;flex-wrap:wrap;gap:5px;}
.cv-skill-cat{margin-bottom:0;}
.cv-scard{background:var(--cv-bg2);border:1px solid var(--cv-border);border-radius:var(--cv-r);margin-bottom:14px;overflow:hidden;}
.cv-scard-header{padding:11px 16px;border-bottom:1px solid var(--cv-border);font-size:10px;font-weight:800;letter-spacing:1.8px;text-transform:uppercase;color:var(--cv-muted);background:var(--cv-bg3);}
.cv-scard-body{padding:14px 16px;}
.cv-contact-item{display:flex;align-items:flex-start;gap:9px;padding:10px 16px;border-bottom:1px solid var(--cv-border);}
.cv-contact-item:last-child{border-bottom:none;}
.cv-contact-icon{width:28px;height:28px;border-radius:8px;background:var(--cv-blue-l);display:flex;align-items:center;justify-content:center;font-size:12px;flex-shrink:0;}
.cv-contact-label{font-size:10px;font-weight:700;letter-spacing:.6px;text-transform:uppercase;color:var(--cv-muted2);margin-bottom:2px;}
.cv-contact-value{font-size:12px;color:var(--cv-ink);font-weight:500;}
.cv-contact-value a{color:var(--cv-blue);text-decoration:none;}
.cv-lang-item{margin-bottom:12px;}
.cv-lang-row{display:flex;justify-content:space-between;align-items:center;margin-bottom:5px;}
.cv-lang-name{font-size:13px;font-weight:600;color:var(--cv-ink);}
.cv-lang-bar{height:4px;background:var(--cv-border);border-radius:4px;overflow:hidden;}
.cv-lang-fill{height:100%;border-radius:4px;background:linear-gradient(90deg,var(--cv-blue),#60a5fa);}
.cv-hobby-grid{display:flex;flex-wrap:wrap;gap:6px;}
.cv-hobby{display:flex;align-items:center;gap:5px;background:var(--cv-bg3);border:1px solid var(--cv-border);border-radius:8px;padding:5px 10px;font-size:11px;font-weight:600;color:var(--cv-ink2);}
.cv-actions-bar{background:var(--cv-bg2);border-top:1px solid var(--cv-border);padding:14px 40px;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;}
.cv-actions-left{display:flex;align-items:center;gap:10px;}
.cv-check-row{display:flex;align-items:center;gap:7px;font-size:12px;font-weight:500;color:var(--cv-muted);cursor:pointer;}
.cv-check-row input[type=checkbox]{width:14px;height:14px;accent-color:var(--cv-blue);cursor:pointer;}
.cv-actions-note{font-size:11px;color:var(--cv-muted2);max-width:320px;line-height:1.4;}
.cv-actions-btns{display:flex;gap:8px;}
.cv-btn{display:inline-flex;align-items:center;gap:6px;padding:9px 18px;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;border:none;font-family:inherit;text-decoration:none;white-space:nowrap;}
.cv-btn-blue{background:var(--cv-blue);color:#fff;}
.cv-btn-blue:hover{background:var(--cv-blue-d);}
.cv-btn-ghost{background:transparent;color:var(--cv-muted);border:1px solid var(--cv-border);}
.cv-btn-ghost:hover{background:var(--cv-bg3);color:var(--cv-ink2);}
.cv-btn-sm{padding:6px 14px;font-size:12px;}
.cv-toast{position:fixed;bottom:80px;left:50%;transform:translateX(-50%) translateY(40px);background:var(--cv-ink);color:#fff;padding:10px 18px;border-radius:100px;font-size:13px;display:flex;align-items:center;gap:8px;z-index:900;opacity:0;transition:transform .3s,opacity .25s;}
.cv-toast.show{transform:translateX(-50%) translateY(0);opacity:1;}
.fr-text{display:block;}.en-text{display:none;}
.cv-struct.lang-en .fr-text{display:none;}
.cv-struct.lang-en .en-text{display:block;}
</style>

<script>
(function(){
  var root = document.getElementById('cv-struct-preview');
  if (!root) return;
  root.querySelectorAll('.cv-lbtn').forEach(function(btn){
    btn.addEventListener('click', function(){
      var lang = this.getAttribute('data-lang');
      root.classList.toggle('lang-en', lang === 'en');
      root.querySelectorAll('.cv-lbtn').forEach(function(b){ b.classList.toggle('active', b.getAttribute('data-lang') === lang); });
    });
  });
  var toast = document.getElementById('cv-toast');
  window.cvShowToast = function(icon, msg){
    if (!toast) return;
    toast.querySelector('#cv-toast-icon').textContent = icon || '✅';
    toast.querySelector('#cv-toast-msg').textContent = msg || '';
    toast.classList.add('show');
    clearTimeout(window._cvToastT);
    window._cvToastT = setTimeout(function(){ toast.classList.remove('show'); }, 3000);
  };
  var copyBtn = document.getElementById('cv-copy-data-btn');
  if (copyBtn) {
    copyBtn.addEventListener('click', function(){
      var name = root.querySelector('.cv-hero-name');
      var title = root.querySelector('.cv-hero-title');
      var contact = root.querySelector('.cv-hero-contact');
      var text = (name ? name.textContent : '') + '\n' + (title ? title.textContent : '') + '\n' + (contact ? contact.innerText : '');
      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text).then(function(){ cvShowToast('📋', 'Données copiées'); });
      } else { cvShowToast('📋', 'Données copiées'); }
    });
  }
})();
</script>
