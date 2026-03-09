<?php
/**
 * Recruiter CV preview — rendered ONLY from canonical profile (single source of truth).
 * Profile must already be normalized (Candidate::getProfile() does this).
 * Uses CandidateProfileSchema::displayValue for missing/empty fields.
 */
$profile = $profile ?? [];
$contact = $profile['contact'] ?? [];
$name = \App\Services\CandidateProfileSchema::displayValue($profile['full_name'] ?? '');
$jobTitle = \App\Services\CandidateProfileSchema::displayValue($profile['job_title'] ?? '');
$init = 'CV';
if (preg_match('/^(\p{L})\p{L}*\s+(\p{L})/u', $profile['full_name'] ?? '', $m)) {
    $init = strtoupper($m[1] . $m[2]);
}
?>
<div class="cv-view-recruiter">
    <header class="cv-view-header">
        <div class="cv-view-avatar"><?= htmlspecialchars($init) ?></div>
        <div>
            <h1 class="cv-view-name"><?= htmlspecialchars($name) ?></h1>
            <p class="cv-view-role"><?= htmlspecialchars($jobTitle) ?></p>
        </div>
    </header>

    <section class="cv-view-section">
        <h2 class="cv-view-section-title">Contact</h2>
        <ul class="cv-view-list">
            <li><strong>Email :</strong> <?= htmlspecialchars(\App\Services\CandidateProfileSchema::displayValue($contact['email'] ?? null)) ?></li>
            <li><strong>Téléphone :</strong> <?= htmlspecialchars(\App\Services\CandidateProfileSchema::displayValue($contact['phone'] ?? null)) ?></li>
            <li><strong>Ville :</strong> <?= htmlspecialchars(\App\Services\CandidateProfileSchema::displayValue($contact['city'] ?? null)) ?></li>
            <?php if (\App\Services\CandidateProfileSchema::displayValue($contact['address'] ?? null) !== \App\Services\CandidateProfileSchema::EMPTY_PLACEHOLDER) { ?>
            <li><strong>Adresse :</strong> <?= htmlspecialchars(\App\Services\CandidateProfileSchema::displayValue($contact['address'] ?? null)) ?></li>
            <?php } ?>
        </ul>
    </section>

    <?php if (\App\Services\CandidateProfileSchema::displayValue($profile['summary'] ?? null) !== \App\Services\CandidateProfileSchema::EMPTY_PLACEHOLDER) { ?>
    <section class="cv-view-section">
        <h2 class="cv-view-section-title">Résumé</h2>
        <p class="cv-view-text"><?= nl2br(htmlspecialchars(\App\Services\CandidateProfileSchema::displayValue($profile['summary'] ?? null))) ?></p>
    </section>
    <?php } ?>

    <section class="cv-view-section">
        <h2 class="cv-view-section-title">Expérience</h2>
        <?php
        $experiences = $profile['experience'] ?? [];
        if (count($experiences) > 0) {
            foreach ($experiences as $e) {
                $title = \App\Services\CandidateProfileSchema::displayValue($e['title'] ?? null);
                $company = \App\Services\CandidateProfileSchema::displayValue($e['company'] ?? null);
                $duration = \App\Services\CandidateProfileSchema::displayValue($e['duration'] ?? null);
                $desc = \App\Services\CandidateProfileSchema::displayValue($e['description'] ?? null);
        ?>
        <div class="cv-view-item">
            <div class="cv-view-item-title"><?= htmlspecialchars($title) ?></div>
            <div class="cv-view-item-meta"><?= htmlspecialchars($company) ?><?= $duration !== \App\Services\CandidateProfileSchema::EMPTY_PLACEHOLDER ? ' · ' . htmlspecialchars($duration) : '' ?></div>
            <?php if ($desc !== \App\Services\CandidateProfileSchema::EMPTY_PLACEHOLDER) { ?><div class="cv-view-item-desc"><?= nl2br(htmlspecialchars($desc)) ?></div><?php } ?>
        </div>
        <?php }
        } else { ?>
        <div class="cv-view-item"><div class="cv-view-text" style="color:var(--muted);"><?= \App\Services\CandidateProfileSchema::EMPTY_PLACEHOLDER ?></div></div>
        <?php } ?>
    </section>

    <section class="cv-view-section">
        <h2 class="cv-view-section-title">Formation</h2>
        <?php
        $education = $profile['education'] ?? [];
        if (count($education) > 0) {
            foreach ($education as $ed) {
                $degree = \App\Services\CandidateProfileSchema::displayValue($ed['degree'] ?? null);
                $school = \App\Services\CandidateProfileSchema::displayValue($ed['school'] ?? null);
                $year = \App\Services\CandidateProfileSchema::displayValue($ed['year'] ?? null);
        ?>
        <div class="cv-view-item">
            <div class="cv-view-item-title"><?= htmlspecialchars($degree) ?></div>
            <div class="cv-view-item-meta"><?= htmlspecialchars($school) ?><?= $year !== \App\Services\CandidateProfileSchema::EMPTY_PLACEHOLDER ? ' · ' . htmlspecialchars($year) : '' ?></div>
            <?php $details = \App\Services\CandidateProfileSchema::displayValue($ed['details'] ?? null); if ($details !== \App\Services\CandidateProfileSchema::EMPTY_PLACEHOLDER) { ?>
            <div class="cv-view-item-desc"><?= nl2br(htmlspecialchars($details)) ?></div>
            <?php } ?>
        </div>
        <?php }
        } else { ?>
        <div class="cv-view-item"><div class="cv-view-text" style="color:var(--muted);"><?= \App\Services\CandidateProfileSchema::EMPTY_PLACEHOLDER ?></div></div>
        <?php } ?>
    </section>

    <section class="cv-view-section">
        <h2 class="cv-view-section-title">Compétences</h2>
        <div class="cv-view-chips">
            <?php
            $skills = $profile['skills'] ?? [];
            if (count($skills) > 0) {
                foreach ($skills as $s) {
                    ?><span class="cv-view-chip"><?= htmlspecialchars($s) ?></span><?php
                }
            } else {
                ?><span class="cv-view-text" style="color:var(--muted);"><?= \App\Services\CandidateProfileSchema::EMPTY_PLACEHOLDER ?></span><?php
            }
            ?>
        </div>
    </section>

    <section class="cv-view-section">
        <h2 class="cv-view-section-title">Langues</h2>
        <p class="cv-view-text"><?= count($profile['languages'] ?? []) > 0 ? htmlspecialchars(implode(', ', $profile['languages'])) : \App\Services\CandidateProfileSchema::EMPTY_PLACEHOLDER ?></p>
    </section>

    <section class="cv-view-section">
        <h2 class="cv-view-section-title">Projets</h2>
        <div class="cv-view-item-desc"><?= count($profile['projects'] ?? []) > 0 ? nl2br(htmlspecialchars(implode("\n", $profile['projects']))) : '<span style="color:var(--muted);">' . \App\Services\CandidateProfileSchema::EMPTY_PLACEHOLDER . '</span>' ?></div>
    </section>

    <section class="cv-view-section">
        <h2 class="cv-view-section-title">Certifications</h2>
        <div class="cv-view-chips">
            <?php
            $certs = $profile['certifications'] ?? [];
            if (count($certs) > 0) {
                foreach ($certs as $cert) {
                    ?><span class="cv-view-chip"><?= htmlspecialchars($cert) ?></span><?php
                }
            } else {
                ?><span class="cv-view-text" style="color:var(--muted);"><?= \App\Services\CandidateProfileSchema::EMPTY_PLACEHOLDER ?></span><?php
            }
            ?>
        </div>
    </section>

    <section class="cv-view-section">
        <h2 class="cv-view-section-title">Disponibilité & prétentions</h2>
        <ul class="cv-view-list">
            <li><strong>Disponibilité :</strong> <?= htmlspecialchars(\App\Services\CandidateProfileSchema::displayValue($profile['availability'] ?? null)) ?></li>
            <li><strong>Prétention salariale :</strong> <?= htmlspecialchars(\App\Services\CandidateProfileSchema::displayValue($profile['salary_expectation'] ?? null)) ?></li>
        </ul>
    </section>
</div>
