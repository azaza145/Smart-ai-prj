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

$c = $candidate;
$candidateId = (int)($c['id'] ?? 0);
$profile = isset($profile) && is_array($profile) ? \App\Models\Candidate::getProfile($candidateId) : \App\Models\Candidate::getProfile($candidateId);
$profile = \App\Services\CandidateProfileSchema::normalizeCandidateProfile($profile);
$name = trim(($c['prenom'] ?? '') . ' ' . ($c['nom'] ?? ''));
$fullName = trim($profile['full_name'] ?? '') !== '' ? $profile['full_name'] : $name;
$jobTitle = trim($profile['job_title'] ?? '') !== '' ? $profile['job_title'] : ($c['poste_actuel'] ?? '—');
$init = strtoupper(mb_substr($c['prenom'] ?? 'X', 0, 1) . mb_substr($c['nom'] ?? 'X', 0, 1));
if (strlen($init) < 2) $init = $init . 'X';
$avatarColor = getUniqueAvatarColor($candidateId);
$avatarUrl = getAvatarUrl($candidateId, $fullName ?: $name, $c['prenom'] ?? '');
$appCount = count($applications ?? []);
$statusLabels = ['submitted' => 'Envoyée', 'viewed' => 'Consultée', 'shortlisted' => 'Shortlist', 'rejected' => 'Refusée'];

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
?>

<a href="/recruiter/candidates" class="btn btn-ghost" style="margin-bottom:20px;">← Tous les candidats</a>

<!-- CV Viewer -->
<?php 
$latestCv = $latestCv ?? null;
if ($latestCv && !empty($latestCv['file_path'])) {
    $basePath = dirname(__DIR__, 2);
    $fullPath = $basePath . '/' . $latestCv['file_path'];
    if (is_file($fullPath)) {
        $pdfUrl = '/recruiter/candidates/' . (int)$c['id'] . '/cv/' . (int)$latestCv['id'];
?>
<div class="card" style="margin-bottom:20px;">
    <div class="card-header">
        <div class="card-title"><div class="ci" style="background:var(--ca-l);">📄</div>CV Original</div>
        <div style="display:flex;gap:8px;">
            <?php if (!empty($cvs)) { foreach ($cvs as $cv) { ?>
            <a href="/recruiter/candidates/<?= (int)$c['id'] ?>/cv/<?= (int)$cv['id'] ?>" class="btn btn-outline-re btn-sm" style="padding:4px 10px;font-size:11px;">📥 <?= htmlspecialchars($cv['original_name']) ?></a>
            <?php } } ?>
        </div>
    </div>
    <div class="card-body" style="padding:0;">
        <iframe src="<?= htmlspecialchars($pdfUrl) ?>" style="width:100%;height:800px;border:none;display:block;" type="application/pdf"></iframe>
    </div>
</div>
<?php 
    } else {
?>
<div class="card" style="margin-bottom:20px;">
    <div class="card-body" style="text-align:center;padding:40px;">
        <p style="color:var(--muted);">Aucun CV disponible pour ce candidat.</p>
    </div>
</div>
<?php 
    }
} else if (!empty($cvs)) {
?>
<div class="card" style="margin-bottom:20px;">
    <div class="card-header">
        <div class="card-title"><div class="ci" style="background:var(--ca-l);">📄</div>CV déposé</div>
    </div>
    <div class="card-body">
        <?php foreach ($cvs as $cv) { ?>
        <a href="/recruiter/candidates/<?= (int)$c['id'] ?>/cv/<?= (int)$cv['id'] ?>" class="btn btn-outline-re" style="padding:6px 14px;font-size:12px;margin-right:8px;margin-bottom:6px;">📥 <?= htmlspecialchars($cv['original_name']) ?></a>
        <span style="font-size:11px;color:var(--muted);"><?= htmlspecialchars($cv['uploaded_at']) ?></span>
        <?php } ?>
    </div>
</div>
<?php } else { ?>
<div class="card" style="margin-bottom:20px;">
    <div class="card-body" style="text-align:center;padding:40px;">
        <p style="color:var(--muted);">Aucun CV disponible pour ce candidat.</p>
    </div>
</div>
<?php } ?>

<?php 
// Only show form data if NO valid CV file exists
$hasValidCv = false;
$basePath = dirname(__DIR__, 2);
if ($latestCv && !empty($latestCv['file_path'])) {
    $fullPath = $basePath . '/' . $latestCv['file_path'];
    $hasValidCv = is_file($fullPath);
}
// Also check if any CV in the list has a valid file
if (!$hasValidCv && !empty($cvs)) {
    foreach ($cvs as $cv) {
        if (!empty($cv['file_path'])) {
            $fullPath = $basePath . '/' . $cv['file_path'];
            if (is_file($fullPath)) {
                $hasValidCv = true;
                break;
            }
        }
    }
}
// Show form data only if no valid CV file exists
if (!$hasValidCv) {
?>
<!-- Profile Data Section (Form Data) - Only shown when no CV uploaded -->
<div class="card" style="overflow:hidden;" id="cv-content">
    <!-- Hero Section -->
    <div style="background:<?= htmlspecialchars($avatarColor) ?>;padding:32px 40px;color:#fff;">
        <div style="display:flex;align-items:center;gap:24px;flex-wrap:wrap;">
            <div style="width:80px;height:80px;border-radius:50%;background:#fff;display:flex;align-items:center;justify-content:center;border:3px solid rgba(255,255,255,0.5);flex-shrink:0;box-shadow:0 4px 12px rgba(0,0,0,0.15);overflow:hidden;position:relative;">
                <img src="<?= htmlspecialchars($avatarUrl) ?>" alt="<?= htmlspecialchars($fullName) ?>" style="width:100%;height:100%;object-fit:cover;border-radius:50%;display:block;" onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
                <div style="display:none;width:100%;height:100%;align-items:center;justify-content:center;background:rgba(255,255,255,0.3);font-size:32px;font-weight:700;color:#fff;border-radius:50%;"><?= htmlspecialchars($init) ?></div>
            </div>
            <div style="flex:1;min-width:200px;">
                <h1 style="font-size:32px;font-weight:700;margin:0 0 8px 0;font-family:var(--font-h);"><?= htmlspecialchars($fullName !== '' ? $fullName : '—') ?></h1>
                <div style="font-size:16px;opacity:0.95;margin-bottom:12px;"><?= htmlspecialchars($jobTitle !== '' ? $jobTitle : '—') ?></div>
                <div style="display:flex;flex-wrap:wrap;gap:12px 20px;font-size:13px;">
                    <?php if ($email !== '') { ?>
                    <div style="display:flex;align-items:center;gap:6px;">
                        <span>✉️</span>
                        <a href="mailto:<?= htmlspecialchars($email) ?>" style="color:#fff;text-decoration:none;"><?= htmlspecialchars($email) ?></a>
                    </div>
                    <?php } ?>
                    <?php if ($phone !== '') { ?>
                    <div style="display:flex;align-items:center;gap:6px;">
                        <span>📞</span>
                        <span><?= htmlspecialchars($phone) ?></span>
                    </div>
                    <?php } ?>
                    <?php if ($addressLine !== '') { ?>
                    <div style="display:flex;align-items:center;gap:6px;">
                        <span>📍</span>
                        <span><?= htmlspecialchars($addressLine) ?></span>
                    </div>
                    <?php } ?>
                </div>
        </div>
        <div style="text-align:right;display:flex;flex-direction:column;align-items:flex-end;gap:10px;">
                <div style="display:flex;gap:8px;">
                    <a href="/recruiter/candidates/<?= (int)$c['id'] ?>/cv-pdf" class="btn" style="background:rgba(255,255,255,0.2);color:#fff;border:2px solid rgba(255,255,255,0.4);padding:8px 16px;font-size:12px;text-decoration:none;border-radius:6px;font-weight:600;">📥 PDF</a>
                    <button onclick="window.print()" class="btn" style="background:rgba(255,255,255,0.2);color:#fff;border:2px solid rgba(255,255,255,0.4);padding:8px 16px;font-size:12px;border-radius:6px;font-weight:600;cursor:pointer;">🖨️ Imprimer</button>
                </div>
                <div><span style="font-family:var(--font-h);font-size:28px;font-weight:700;color:#fff;"><?= $appCount ?></span> <span style="font-size:11px;opacity:0.9;">candidature(s)</span></div>
        </div>
      </div>
    </div>

    <!-- Profile Content -->
    <div style="padding:32px 40px;">
        <div style="display:grid;grid-template-columns:1fr 300px;gap:32px;align-items:start;">
            <!-- Left Column: Main Sections -->
            <div>
                <!-- Expérience Professionnelle -->
                <div style="margin-bottom:32px;">
                    <div style="display:flex;align-items:center;gap:10px;margin-bottom:16px;">
                        <div style="width:32px;height:32px;border-radius:8px;background:var(--ca-l);display:flex;align-items:center;justify-content:center;font-size:18px;">💼</div>
                        <h2 style="font-size:14px;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:var(--g-dark);margin:0;">Expérience Professionnelle</h2>
                    </div>
                    <div style="background:var(--white);border:2px solid var(--border-2);border-radius:var(--r);padding:20px;box-shadow:var(--shadow);">
                        <?php if ($expYears !== null || $company !== null || !empty($experience)) { ?>
                        <div style="margin-bottom:16px;">
                            <?php if ($jobTitle !== '' && $jobTitle !== '—') { ?>
                            <div style="font-size:16px;font-weight:700;color:var(--text);margin-bottom:4px;"><?= htmlspecialchars($jobTitle) ?></div>
                            <?php } ?>
                            <?php if ($company !== null) { ?>
                            <div style="font-size:13px;color:var(--g-dark);font-weight:700;margin-bottom:4px;"><?= htmlspecialchars($company) ?></div>
                            <?php } ?>
                            <div style="font-size:12px;color:var(--g-dark);font-weight:600;">
                                <?php if ($expYears !== null) { ?>
                                <span>Expérience: <?= $expYears ?> an(s)</span>
                                <?php } ?>
                                <?php if ($age !== null) { ?>
                                <span style="margin-left:12px;">Âge: <?= $age ?> ans</span>
                                <?php } ?>
                            </div>
                            <?php if (!empty($c['experience_detail_raw'])) { ?>
                            <div style="font-size:12px;color:var(--text);margin-top:8px;line-height:1.6;"><?= nl2br(htmlspecialchars($c['experience_detail_raw'])) ?></div>
                            <?php } ?>
                        </div>
                        <?php } ?>
                        <?php if (count($experience) > 0) { ?>
                        <div style="display:flex;flex-direction:column;gap:16px;">
                            <?php foreach ($experience as $i => $e) {
                                $title = trim($e['title'] ?? '');
                                $company = trim($e['company'] ?? '');
                                $duration = trim($e['duration'] ?? '');
                                $desc = trim($e['description'] ?? '');
                            ?>
                            <div style="padding-bottom:16px;border-bottom:2px solid var(--border);<?= $i === count($experience) - 1 ? 'border-bottom:none;padding-bottom:0;' : '' ?>">
                                <?php if ($duration !== '') { ?>
                                <div style="font-size:11px;font-weight:700;letter-spacing:0.8px;text-transform:uppercase;color:var(--g-dark);margin-bottom:6px;"><?= htmlspecialchars($duration) ?></div>
                                <?php } ?>
                                <?php if ($title !== '') { ?>
                                <div style="font-size:15px;font-weight:700;color:var(--text);margin-bottom:4px;"><?= htmlspecialchars($title) ?></div>
                                <?php } ?>
                                <?php if ($company !== '') { ?>
                                <div style="font-size:13px;color:var(--g-dark);font-weight:600;margin-bottom:8px;"><?= htmlspecialchars($company) ?></div>
                                <?php } ?>
                                <?php if ($desc !== '') { ?>
                                <div style="font-size:12px;color:var(--text);line-height:1.6;"><?= nl2br(htmlspecialchars(mb_substr($desc, 0, 500) . (mb_strlen($desc) > 500 ? '…' : ''))) ?></div>
                                <?php } ?>
                            </div>
                            <?php } ?>
                        </div>
                        <?php } else if ($expYears === null && $company === null) { ?>
                        <div style="color:var(--muted);font-size:13px;"><?= htmlspecialchars($emptyPh) ?></div>
                        <?php } ?>
                    </div>
                </div>

                <!-- Formation -->
                <div style="margin-bottom:32px;">
                    <div style="display:flex;align-items:center;gap:10px;margin-bottom:16px;">
                        <div style="width:32px;height:32px;border-radius:8px;background:var(--ca-l);display:flex;align-items:center;justify-content:center;font-size:18px;">🎓</div>
                        <h2 style="font-size:14px;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:var(--g-dark);margin:0;">Formation</h2>
                    </div>
                    <div style="background:var(--white);border:2px solid var(--border-2);border-radius:var(--r);padding:20px;box-shadow:var(--shadow);">
                        <?php if (count($education) > 0) { ?>
                        <div style="display:flex;flex-direction:column;gap:16px;">
                            <?php foreach ($education as $i => $ed) {
                                $deg = trim($ed['degree'] ?? '');
                                $school = trim($ed['school'] ?? '');
                                $year = trim($ed['year'] ?? '');
                            ?>
                            <div style="padding-bottom:16px;border-bottom:2px solid var(--border);<?= $i === count($education) - 1 ? 'border-bottom:none;padding-bottom:0;' : '' ?>">
                                <?php if ($year !== '') { ?>
                                <div style="font-size:11px;font-weight:700;letter-spacing:0.8px;text-transform:uppercase;color:var(--g-dark);margin-bottom:6px;"><?= htmlspecialchars($year) ?></div>
                                <?php } ?>
                                <?php if ($deg !== '') { ?>
                                <div style="font-size:15px;font-weight:700;color:var(--text);margin-bottom:4px;"><?= htmlspecialchars($deg) ?></div>
                                <?php } ?>
                                <?php if ($school !== '') { ?>
                                <div style="font-size:13px;color:var(--g-dark);font-weight:600;"><?= htmlspecialchars($school) ?></div>
                                <?php } ?>
                            </div>
                            <?php } ?>
                        </div>
                        <?php } else if (!empty($c['diplome']) || !empty($c['education_niveau']) || !empty($c['universite'])) { ?>
                        <div>
                            <?php if (!empty($c['annee_diplome'])) { ?>
                            <div style="font-size:11px;font-weight:700;letter-spacing:0.8px;text-transform:uppercase;color:var(--g-dark);margin-bottom:6px;"><?= htmlspecialchars($c['annee_diplome']) ?></div>
                            <?php } ?>
                            <div style="font-size:15px;font-weight:700;color:var(--text);margin-bottom:4px;">
                                <?= htmlspecialchars(trim(($c['diplome'] ?? '') . ' ' . ($c['education_niveau'] ?? ''))) ?: '—' ?>
                            </div>
                            <?php if (!empty($c['universite'])) { ?>
                            <div style="font-size:13px;color:var(--g-dark);font-weight:600;"><?= htmlspecialchars($c['universite']) ?></div>
                            <?php } ?>
                        </div>
                        <?php } else { ?>
                        <div style="color:var(--muted);font-size:13px;"><?= htmlspecialchars($emptyPh) ?></div>
                        <?php } ?>
                    </div>
                </div>

                <!-- Compétences Techniques -->
                <div style="margin-bottom:32px;">
                    <div style="display:flex;align-items:center;gap:10px;margin-bottom:16px;">
                        <div style="width:32px;height:32px;border-radius:8px;background:var(--ca-l);display:flex;align-items:center;justify-content:center;font-size:18px;">⚙️</div>
                        <h2 style="font-size:14px;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:var(--g-dark);margin:0;">Compétences Techniques</h2>
                    </div>
                    <div style="background:var(--white);border:2px solid var(--border-2);border-radius:var(--r);padding:20px;box-shadow:var(--shadow);">
                        <?php if (count($skills) > 0) { ?>
                        <div style="display:flex;flex-wrap:wrap;gap:8px;">
                            <?php foreach ($skills as $skill) { ?>
                            <span style="background:var(--ca-l);color:var(--g-dark);border:2px solid var(--g-mid);padding:6px 14px;border-radius:6px;font-size:12px;font-weight:700;"><?= htmlspecialchars($skill) ?></span>
                            <?php } ?>
                        </div>
                        <?php } else { ?>
                        <div style="color:var(--muted);font-size:13px;"><?= htmlspecialchars($emptyPh) ?></div>
                        <?php } ?>
                    </div>
                </div>
            </div>

            <!-- Right Column: Sidebar -->
            <div>
                <!-- Langues -->
                <div style="margin-bottom:24px;">
                    <div style="background:var(--white);border:2px solid var(--border-2);border-radius:var(--r);overflow:hidden;box-shadow:var(--shadow);">
                        <div style="background:var(--ca-l);padding:12px 16px;border-bottom:2px solid var(--border-2);">
                            <div style="display:flex;align-items:center;gap:8px;">
                                <span style="font-size:16px;">🌐</span>
                                <h3 style="font-size:12px;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:var(--g-dark);margin:0;">Langues</h3>
                            </div>
                        </div>
                        <div style="padding:16px;">
                            <?php if (count($languages) > 0) { ?>
                            <div style="display:flex;flex-direction:column;gap:12px;">
                                <?php foreach ($languages as $lang) { ?>
                                <div>
                                    <div style="font-size:13px;font-weight:700;color:var(--text);margin-bottom:6px;"><?= htmlspecialchars($lang) ?></div>
                                    <div style="height:6px;background:var(--border);border-radius:4px;overflow:hidden;">
                                        <div style="height:100%;width:85%;background:linear-gradient(90deg, var(--g-dark), var(--g-mid));border-radius:4px;"></div>
                                    </div>
                                </div>
                                <?php } ?>
                            </div>
                            <?php } else { ?>
                            <div style="color:var(--muted);font-size:13px;"><?= htmlspecialchars($emptyPh) ?></div>
                            <?php } ?>
                        </div>
                    </div>
                </div>

                <!-- Projets -->
                <?php if (count($projects) > 0) { ?>
                <div style="margin-bottom:24px;">
                    <div style="background:var(--white);border:2px solid var(--border-2);border-radius:var(--r);overflow:hidden;box-shadow:var(--shadow);">
                        <div style="background:var(--ca-l);padding:12px 16px;border-bottom:2px solid var(--border-2);">
                            <div style="display:flex;align-items:center;gap:8px;">
                                <span style="font-size:16px;">🚀</span>
                                <h3 style="font-size:12px;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:var(--g-dark);margin:0;">Projets</h3>
                            </div>
                        </div>
                        <div style="padding:16px;">
                            <div style="display:flex;flex-direction:column;gap:8px;">
                                <?php foreach ($projects as $proj) { ?>
                                <div style="font-size:13px;color:var(--text);font-weight:500;">• <?= htmlspecialchars($proj) ?></div>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php } ?>

                <!-- Certifications -->
                <?php if (count($certifications) > 0) { ?>
                <div style="margin-bottom:24px;">
                    <div style="background:var(--white);border:2px solid var(--border-2);border-radius:var(--r);overflow:hidden;box-shadow:var(--shadow);">
                        <div style="background:var(--ca-l);padding:12px 16px;border-bottom:2px solid var(--border-2);">
                            <div style="display:flex;align-items:center;gap:8px;">
                                <span style="font-size:16px;">🏆</span>
                                <h3 style="font-size:12px;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:var(--g-dark);margin:0;">Certifications</h3>
                            </div>
                        </div>
                        <div style="padding:16px;">
                            <div style="display:flex;flex-direction:column;gap:8px;">
                                <?php foreach ($certifications as $cert) { ?>
                                <div style="font-size:13px;color:var(--text);font-weight:500;">• <?= htmlspecialchars($cert) ?></div>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php } ?>

                <!-- Informations Complémentaires -->
                <div style="background:var(--white);border:2px solid var(--border-2);border-radius:var(--r);overflow:hidden;box-shadow:var(--shadow);">
                    <div style="background:var(--ca-l);padding:12px 16px;border-bottom:2px solid var(--border-2);">
                        <div style="display:flex;align-items:center;gap:8px;">
                            <span style="font-size:16px;">ℹ️</span>
                            <h3 style="font-size:12px;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:var(--g-dark);margin:0;">Informations Complémentaires</h3>
                        </div>
                    </div>
                    <div style="padding:16px;">
                        <div style="display:flex;flex-direction:column;gap:12px;">
                            <?php if ($availability !== '—' && $availability !== '') { ?>
                            <div>
                                <div style="font-size:11px;font-weight:700;letter-spacing:0.6px;text-transform:uppercase;color:var(--g-dark);margin-bottom:4px;">Disponibilité</div>
                                <div style="font-size:14px;font-weight:700;color:var(--text);"><?= htmlspecialchars($availability) ?></div>
                            </div>
                            <?php } ?>
                            <?php if ($salary !== '—' && $salary !== '') { ?>
                            <div>
                                <div style="font-size:11px;font-weight:700;letter-spacing:0.6px;text-transform:uppercase;color:var(--g-dark);margin-bottom:4px;">Prétention Salariale</div>
                                <div style="font-size:14px;font-weight:700;color:var(--text);"><?= htmlspecialchars($salary) ?></div>
                            </div>
                            <?php } ?>
                            <?php if ($availability === '—' && $salary === '—') { ?>
                            <div style="color:var(--muted);font-size:13px;"><?= htmlspecialchars($emptyPh) ?></div>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php } ?>

<!-- Applications Section (Below CV) -->
<div class="card" style="margin-top:20px;">
      <div class="card-header">
        <div class="card-title"><div class="ci" style="background:var(--ca-l);">📋</div>Candidatures</div>
        <span style="font-size:12px;color:var(--muted);"><?= $appCount ?> candidature(s)</span>
      </div>
      <?php if (empty($applications)) { ?>
      <div class="card-body" style="text-align:center;padding:40px 24px;">
        <div style="font-size:32px;margin-bottom:12px;opacity:.4;">📭</div>
        <div style="font-family:var(--font-h);font-size:16px;color:var(--text);margin-bottom:6px;">Aucune candidature</div>
        <div style="font-size:12px;color:var(--muted);max-width:260px;margin:0 auto;">Ce candidat n'a pas encore soumis de candidature.</div>
      </div>
      <?php } else { ?>
      <div style="overflow-x:auto;">
        <table class="tbl">
          <thead><tr><th>Poste</th><th>Département</th><th>Statut</th><th>Actions</th></tr></thead>
          <tbody>
            <?php foreach ($applications as $app) {
              $st = $app['status'] ?? 'submitted';
              $stLabel = $statusLabels[$st] ?? $st;
            ?>
            <tr>
              <td><div style="font-weight:600;"><?= htmlspecialchars($app['job_title'] ?? '') ?></div></td>
              <td style="font-size:12px;color:var(--muted);"><?= htmlspecialchars($app['job_department'] ?? '') ?></td>
              <td><span class="tag <?= $st === 'shortlisted' ? 'tg' : ($st === 'rejected' ? 'tr' : 'ta') ?>"><?= htmlspecialchars($stLabel) ?></span></td>
              <td><a href="/recruiter/jobs/<?= (int)$app['job_id'] ?>/candidates/<?= (int)$c['id'] ?>" class="btn btn-outline-re" style="padding:5px 12px;font-size:12px;">Voir dans le poste</a></td>
            </tr>
            <?php } ?>
          </tbody>
        </table>
      </div>
      <?php } ?>
  </div>

<style media="print">
  @media print {
    .topnav, .btn, a[href], form, .card-header, #cv-content > .card:first-child { display: none !important; }
    #cv-content { border: none !important; box-shadow: none !important; }
    body { background: white !important; }
    .card { border: none !important; box-shadow: none !important; page-break-inside: avoid; }
  }
</style>
