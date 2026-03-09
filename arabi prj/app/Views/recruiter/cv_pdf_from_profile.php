<?php
/**
 * Structured CV PDF — generated ONLY from canonical profile (single source of truth).
 * Used by recruiter Export to PDF. Never from raw uploaded CV.
 */
$profile = $profile ?? [];
$profile = \App\Services\CandidateProfileSchema::normalizeCandidateProfile($profile);
$contact = $profile['contact'] ?? [];
$name = \App\Services\CandidateProfileSchema::displayValue($profile['full_name'] ?? null);
$jobTitle = \App\Services\CandidateProfileSchema::displayValue($profile['job_title'] ?? null);
$init = 'CV';
if (preg_match('/^(\p{L})\p{L}*\s+(\p{L})/u', $profile['full_name'] ?? '', $m)) {
    $init = strtoupper($m[1] . $m[2]);
}
$roleLine = $jobTitle;
$skills = $profile['skills'] ?? [];
$langs = $profile['languages'] ?? [];
$accent = '#1a6b4a';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>CV — <?= htmlspecialchars($name) ?></title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; line-height: 1.4; color: #1c1a17; }
        table { width: 100%; border-collapse: collapse; }
        .sidebar { width: 28%; background: <?= $accent ?>; color: #fff; padding: 24px 18px; vertical-align: top; }
        .main { width: 72%; padding: 28px 32px; vertical-align: top; background: #fff; }
        .sb-avatar { width: 64px; height: 64px; border-radius: 50%; background: rgba(255,255,255,0.25); text-align: center; line-height: 64px; font-size: 22px; font-weight: bold; margin: 0 auto 12px; border: 2px solid rgba(255,255,255,0.4); }
        .sb-name { font-size: 16px; font-weight: bold; text-align: center; margin-bottom: 2px; }
        .sb-role { font-size: 9px; text-align: center; opacity: 0.9; margin-bottom: 16px; }
        .sb-divider { height: 1px; background: rgba(255,255,255,0.25); margin: 12px 0; }
        .sb-section-title { font-size: 8px; font-weight: bold; letter-spacing: 1.2px; text-transform: uppercase; opacity: 0.7; margin-bottom: 6px; }
        .sb-contact-item { font-size: 9px; margin-bottom: 5px; opacity: 0.95; }
        .sb-skill-row { margin-bottom: 6px; }
        .sb-skill-name { font-size: 9px; display: inline-block; width: 60%; }
        .sb-skill-bar { display: inline-block; width: 35%; height: 3px; background: rgba(255,255,255,0.2); border-radius: 2px; overflow: hidden; vertical-align: middle; }
        .sb-skill-fill { height: 100%; background: rgba(255,255,255,0.8); border-radius: 2px; }
        .sb-lang-item { font-size: 9px; margin-bottom: 4px; }
        .cv-name-main { font-size: 22px; font-weight: bold; color: #1c1a17; margin-bottom: 4px; }
        .cv-role-main { font-size: 11px; color: <?= $accent ?>; font-weight: 600; margin-bottom: 18px; }
        .cv-section { margin-bottom: 16px; }
        .cv-section-title { font-size: 9px; font-weight: bold; letter-spacing: 1.5px; text-transform: uppercase; color: <?= $accent ?>; border-bottom: 2px solid <?= $accent ?>; padding-bottom: 5px; margin-bottom: 10px; }
        .cv-item { margin-bottom: 10px; padding-left: 10px; border-left: 2px solid #e0e0e0; }
        .cv-item-title { font-weight: bold; font-size: 11px; color: #1c1a17; }
        .cv-item-company { font-size: 10px; color: <?= $accent ?>; font-weight: 600; margin: 2px 0; }
        .cv-item-desc { font-size: 10px; color: #555; line-height: 1.45; }
        .cv-item-date { font-size: 9px; color: #777; }
        .cv-chip { display: inline-block; padding: 3px 10px; border-radius: 12px; font-size: 9px; font-weight: 600; background: #e8f5ee; color: <?= $accent ?>; border: 1px solid rgba(26,107,74,0.2); margin: 2px 4px 2px 0; }
    </style>
</head>
<body>
<table>
<tr>
<td class="sidebar">
    <div class="sb-avatar"><?= htmlspecialchars($init) ?></div>
    <div class="sb-name"><?= htmlspecialchars($name) ?></div>
    <div class="sb-role"><?= htmlspecialchars($roleLine) ?></div>
    <div class="sb-divider"></div>
    <div class="sb-section-title">CONTACT</div>
    <?php if (\App\Services\CandidateProfileSchema::displayValue($contact['email'] ?? null) !== \App\Services\CandidateProfileSchema::EMPTY_PLACEHOLDER) { ?><div class="sb-contact-item">&#9993; <?= htmlspecialchars($contact['email']) ?></div><?php } ?>
    <?php if (\App\Services\CandidateProfileSchema::displayValue($contact['phone'] ?? null) !== \App\Services\CandidateProfileSchema::EMPTY_PLACEHOLDER) { ?><div class="sb-contact-item">&#9742; <?= htmlspecialchars($contact['phone']) ?></div><?php } ?>
    <?php if (\App\Services\CandidateProfileSchema::displayValue($contact['city'] ?? null) !== \App\Services\CandidateProfileSchema::EMPTY_PLACEHOLDER) { ?><div class="sb-contact-item">&#9873; <?= htmlspecialchars($contact['city']) ?></div><?php } ?>
    <?php if (trim((string)($contact['linkedin'] ?? '')) !== '') { ?><div class="sb-contact-item"><a href="<?= htmlspecialchars($contact['linkedin']) ?>" style="color:inherit;">LinkedIn</a></div><?php } ?>
    <?php if (count($skills) > 0) { ?>
    <div class="sb-divider"></div>
    <div class="sb-section-title">COMPÉTENCES</div>
    <?php foreach (array_slice($skills, 0, 8) as $i => $s) { $w = max(40, 85 - $i * 8); ?>
    <div class="sb-skill-row"><span class="sb-skill-name"><?= htmlspecialchars($s) ?></span><span class="sb-skill-bar"><span class="sb-skill-fill" style="width:<?= $w ?>%;"></span></span></div>
    <?php } ?>
    <?php } ?>
    <?php if (count($langs) > 0) { ?>
    <div class="sb-divider"></div>
    <div class="sb-section-title">LANGUES</div>
    <?php foreach (array_slice($langs, 0, 5) as $l) { ?><div class="sb-lang-item"><?= htmlspecialchars($l) ?></div><?php } ?>
    <?php } ?>
</td>
<td class="main">
    <div class="cv-name-main"><?= htmlspecialchars($name) ?></div>
    <div class="cv-role-main"><?= htmlspecialchars($roleLine) ?></div>

    <?php if (count($profile['experience'] ?? []) > 0) { ?>
    <div class="cv-section">
        <div class="cv-section-title">EXPÉRIENCE</div>
        <?php foreach ($profile['experience'] as $e) {
            $title = \App\Services\CandidateProfileSchema::displayValue($e['title'] ?? null);
            $company = \App\Services\CandidateProfileSchema::displayValue($e['company'] ?? null);
            $duration = \App\Services\CandidateProfileSchema::displayValue($e['duration'] ?? null);
            $desc = \App\Services\CandidateProfileSchema::displayValue($e['description'] ?? null);
        ?>
        <div class="cv-item">
            <div class="cv-item-title"><?= htmlspecialchars($title) ?></div>
            <div class="cv-item-company"><?= htmlspecialchars($company) ?><?= $duration !== \App\Services\CandidateProfileSchema::EMPTY_PLACEHOLDER ? ' · ' . htmlspecialchars($duration) : '' ?></div>
            <?php if ($desc !== \App\Services\CandidateProfileSchema::EMPTY_PLACEHOLDER) { ?><div class="cv-item-desc"><?= nl2br(htmlspecialchars($desc)) ?></div><?php } ?>
        </div>
        <?php } ?>
    </div>
    <?php } ?>

    <?php if (count($profile['education'] ?? []) > 0) { ?>
    <div class="cv-section">
        <div class="cv-section-title">FORMATION</div>
        <?php foreach ($profile['education'] as $ed) {
            $degree = \App\Services\CandidateProfileSchema::displayValue($ed['degree'] ?? null);
            $school = \App\Services\CandidateProfileSchema::displayValue($ed['school'] ?? null);
            $year = \App\Services\CandidateProfileSchema::displayValue($ed['year'] ?? null);
        ?>
        <div class="cv-item">
            <div class="cv-item-title"><?= htmlspecialchars($degree) ?></div>
            <div class="cv-item-company"><?= htmlspecialchars($school) ?><?= $year !== \App\Services\CandidateProfileSchema::EMPTY_PLACEHOLDER ? ' · ' . htmlspecialchars($year) : '' ?></div>
            <?php $details = \App\Services\CandidateProfileSchema::displayValue($ed['details'] ?? null); if ($details !== \App\Services\CandidateProfileSchema::EMPTY_PLACEHOLDER) { ?><div class="cv-item-desc"><?= nl2br(htmlspecialchars($details)) ?></div><?php } ?>
        </div>
        <?php } ?>
    </div>
    <?php } ?>

    <?php if (count($profile['projects'] ?? []) > 0) { ?>
    <div class="cv-section">
        <div class="cv-section-title">PROJETS</div>
        <div class="cv-item"><div class="cv-item-desc"><?= nl2br(htmlspecialchars(implode("\n", $profile['projects']))) ?></div></div>
    </div>
    <?php } ?>

    <?php if (count($profile['certifications'] ?? []) > 0) { ?>
    <div class="cv-section">
        <div class="cv-section-title">CERTIFICATIONS</div>
        <p><?php foreach (array_slice($profile['certifications'], 0, 8) as $cert) { ?><span class="cv-chip"><?= htmlspecialchars($cert) ?></span><?php } ?></p>
    </div>
    <?php } ?>
</td>
</tr>
</table>
</body>
</html>
