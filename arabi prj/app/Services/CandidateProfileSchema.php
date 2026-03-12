<?php

namespace App\Services;

/**
 * Canonical candidate profile schema — single source of truth for:
 * - Recruiter CV preview
 * - Export to PDF
 * - Pre-fill from parsed CV or form
 *
 * All display and export use this structure; missing values are "—".
 */
class CandidateProfileSchema
{
    public const EMPTY_PLACEHOLDER = '—';

    /** City/address values that are parser noise, not real places. */
    private const CONTACT_PLACEHOLDER_WORDS = [
        'lecture', 'reading', 'lire', 'sport', 'music', 'cinema', 'voyage', '—', '-',
        'formation', 'experience', 'compétences', 'competences', 'langues', 'loisirs',
    ];

    /** Skills that are section titles or stopwords — never show as skills. */
    private const SKILL_BLACKLIST = [
        'formation', 'experience', 'expérience', 'compétences', 'competences', 'langues', 'loisirs',
        'stage', 'fin', 'souhaite', 'participer', 'développer', 'cycle', 'diplôme', 'diplome',
        'licence', 'master', 'bac', 'sup', 'mti', 'oan', 'ans', 'projet', 'projets',
        'technologies', 'utilisées', 'utilisees', 'solution', 'besoins', 'fonctionnels', 'techniques',
        'implémentation', 'implementation', 'plateforme', 'gestion', 'événements', 'evenements',
        '—', '-', 'et', 'de', 'du', 'la', 'le', 'les', 'en', 'au', 'aux', 'par', 'pour',
        'formationlicence', 'formation licence',
        'développement', 'developpement', 'programmation', 'réseaux', 'reseaux',
        'sécurité', 'securite', 'systèmes', 'systemes', 'supervision', 'virtualisation',
        'bases de données', 'bases de donnees', 'soft skills', 'soft', 'résolution', 'resolution',
        'organisation', 'communication', 'full-stack', 'full stack', 'boot',
    ];

    /** Education degree/school values that are section headers — drop entry. */
    private const EDUCATION_HEADER_NOISE = [
        'expérience professionnelle', 'experience professionnelle', 'formation', 'formations',
        'compétences', 'competences', 'langues', 'loisirs', 'certifications', 'projets',
    ];

    /**
     * Return empty canonical profile structure.
     */
    public static function emptyProfile(): array
    {
        return [
            'full_name' => '',
            'job_title' => '',
            'contact' => [
                'email' => '',
                'phone' => '',
                'city' => '',
                'address' => '',
                'linkedin' => '',
            ],
            'summary' => '',
            'experience' => [],
            'education' => [],
            'skills' => [],
            'languages' => [],
            'projects' => [],
            'certifications' => [],
            'hobbies' => [],
            'availability' => '',
            'salary_expectation' => '',
        ];
    }

    /**
     * Keep only a valid email (avoid "email@domain.comLinkedin" or similar concatenations).
     * Extracts first email-like substring, then validates with filter_var before returning.
     */
    public static function cleanEmail(string $raw): string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return '';
        }
        if (preg_match('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', $raw, $m)) {
            $candidate = $m[0];
            return filter_var($candidate, FILTER_VALIDATE_EMAIL) ? $candidate : '';
        }
        return '';
    }

    /** Keep only a valid LinkedIn profile URL. */
    private static function cleanLinkedInUrl(string $raw): string
    {
        $raw = trim($raw);
        if ($raw === '' || !preg_match('#https?://(?:www\.)?linkedin\.com/[^\s\)\]"\'<>]+#i', $raw, $m)) {
            return '';
        }
        return trim($m[0]);
    }

    /** Extract a single clear year or range from jumbled date strings (e.g. "20232021-202320212024" → "2021-2024"). */
    public static function sanitizeEducationYear(string $raw): string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return '';
        }
        if (preg_match('/^(19|20)\d{2}$/u', $raw)) {
            return $raw;
        }
        if (preg_match('/^(19|20)\d{2}\s*[–\-]\s*(19|20)\d{2}$/u', $raw)) {
            return preg_replace('/\s+/', ' ', $raw);
        }
        if (!preg_match_all('/\b(19\d{2}|20\d{2})\b/u', $raw, $m)) {
            return $raw;
        }
        $years = array_map('intval', array_unique($m[1]));
        sort($years);
        $min = (int) min($years);
        $max = (int) max($years);
        return $min === $max ? (string) $min : $min . '-' . $max;
    }

    /**
     * Map parsed CV data (from Python extractor: flat "parsed" or "structured") into canonical profile.
     * Fills only provided keys; does not overwrite with empty.
     */
    public static function mapParsedCvToProfile(array $parsedData): array
    {
        $profile = self::emptyProfile();

        // Python "structured" format (personal_info, experience[], education[], skills{}, languages[], etc.)
        $pi = $parsedData['personal_info'] ?? null;
        if (is_array($pi)) {
            $name = trim($pi['full_name'] ?? '');
            if ($name !== '') {
                $profile['full_name'] = $name;
            }
            if (!empty($pi['title'])) {
                $profile['job_title'] = trim((string) $pi['title']);
            }
            if (!empty($pi['email'])) {
                $profile['contact']['email'] = self::cleanEmail((string) $pi['email']);
            }
            if (!empty($pi['phone'])) {
                $profile['contact']['phone'] = trim((string) $pi['phone']);
            }
            if (!empty($pi['address'])) {
                $profile['contact']['address'] = trim((string) $pi['address']);
                if (empty($profile['contact']['city']) && preg_match('/,\s*([A-Za-zÀ-ÿ\-]+)\s*$/u', $pi['address'], $m)) {
                    $profile['contact']['city'] = trim($m[1]);
                }
            }
            if (!empty($pi['linkedin'])) {
                $profile['contact']['linkedin'] = self::cleanLinkedInUrl((string) $pi['linkedin']);
            }
        }

        if (!empty($parsedData['summary'])) {
            $profile['summary'] = trim((string) $parsedData['summary']);
        }

        if (isset($parsedData['experience']) && is_array($parsedData['experience'])) {
            $profile['experience'] = [];
            foreach ($parsedData['experience'] as $e) {
                $profile['experience'][] = [
                    'title' => trim((string) ($e['title'] ?? '')),
                    'company' => trim((string) ($e['company'] ?? '')),
                    'location' => trim((string) ($e['location'] ?? '')),
                    'duration' => trim((string) ($e['period'] ?? $e['duration'] ?? '')),
                    'description' => trim((string) ($e['description'] ?? '')),
                ];
            }
        }

        if (isset($parsedData['education']) && is_array($parsedData['education'])) {
            $profile['education'] = [];
            foreach ($parsedData['education'] as $ed) {
                $profile['education'][] = [
                    'degree' => trim((string) ($ed['degree'] ?? '')),
                    'school' => trim((string) ($ed['school'] ?? '')),
                    'year' => trim((string) ($ed['year'] ?? '')),
                    'details' => trim((string) ($ed['details'] ?? '')),
                ];
            }
        }

        if (isset($parsedData['skills'])) {
            if (is_array($parsedData['skills'])) {
                $flat = [];
                if (isset($parsedData['skills']['programming'])) {
                    $flat = array_merge($flat, (array) $parsedData['skills']['programming']);
                }
                if (isset($parsedData['skills']['databases'])) {
                    $flat = array_merge($flat, (array) $parsedData['skills']['databases']);
                }
                if (isset($parsedData['skills']['systems'])) {
                    $flat = array_merge($flat, (array) $parsedData['skills']['systems']);
                }
                if (isset($parsedData['skills']['network'])) {
                    $flat = array_merge($flat, (array) $parsedData['skills']['network']);
                }
                if (isset($parsedData['skills']['security'])) {
                    $flat = array_merge($flat, (array) $parsedData['skills']['security']);
                }
                if (isset($parsedData['skills']['soft_skills'])) {
                    $flat = array_merge($flat, (array) $parsedData['skills']['soft_skills']);
                }
                $profile['skills'] = array_values(array_filter(array_map('trim', $flat)));
            }
        }

        if (isset($parsedData['languages']) && is_array($parsedData['languages'])) {
            $profile['languages'] = array_values(array_filter(array_map('trim', $parsedData['languages'])));
        }

        if (isset($parsedData['projects']) && is_array($parsedData['projects'])) {
            $profile['projects'] = array_values(array_filter(array_map('trim', $parsedData['projects'])));
        }

        if (isset($parsedData['certifications']) && is_array($parsedData['certifications'])) {
            $profile['certifications'] = array_values(array_filter(array_map('trim', $parsedData['certifications'])));
        }

        if (!empty($parsedData['availability'])) {
            $profile['availability'] = trim((string) $parsedData['availability']);
        }
        if (!empty($parsedData['salary_expectation'])) {
            $profile['salary_expectation'] = trim((string) $parsedData['salary_expectation']);
        }

        // Flat "parsed" format (DB column names from Python to_flat_parsed)
        if (empty($profile['full_name']) && (!empty($parsedData['nom']) || !empty($parsedData['prenom']))) {
            $profile['full_name'] = trim(($parsedData['prenom'] ?? '') . ' ' . ($parsedData['nom'] ?? ''));
        }
        if (empty($profile['contact']['email']) && !empty($parsedData['email'])) {
            $profile['contact']['email'] = self::cleanEmail((string) $parsedData['email']);
        }
        if (empty($profile['contact']['phone']) && !empty($parsedData['telephone'])) {
            $profile['contact']['phone'] = trim((string) $parsedData['telephone']);
        }
        if (empty($profile['contact']['city']) && !empty($parsedData['ville'])) {
            $profile['contact']['city'] = trim((string) $parsedData['ville']);
        }
        if (empty($profile['job_title']) && !empty($parsedData['poste_actuel'])) {
            $profile['job_title'] = trim((string) $parsedData['poste_actuel']);
        }
        if (empty($profile['summary']) && !empty($parsedData['disponibilite'])) {
            $profile['summary'] = trim((string) $parsedData['disponibilite']);
        }
        if (empty($profile['salary_expectation']) && !empty($parsedData['pretention_salaire'])) {
            $profile['salary_expectation'] = trim((string) $parsedData['pretention_salaire']);
        }

        if (empty($profile['experience']) && (!empty($parsedData['poste_actuel']) || !empty($parsedData['experience_detail_raw']))) {
            $profile['experience'] = [
                [
                    'title' => trim((string) ($parsedData['poste_actuel'] ?? '')),
                    'company' => trim((string) ($parsedData['entreprise_actuelle'] ?? '')),
                    'location' => '',
                    'duration' => !empty($parsedData['experience_annees']) ? (int) $parsedData['experience_annees'] . ' an(s)' : '',
                    'description' => trim((string) ($parsedData['experience_detail_raw'] ?? '')),
                ],
            ];
        }

        if (empty($profile['education']) && (!empty($parsedData['education_niveau']) || !empty($parsedData['diplome']) || !empty($parsedData['universite']))) {
            $profile['education'] = [
                [
                    'degree' => trim((string) ($parsedData['diplome'] ?? $parsedData['education_niveau'] ?? '')),
                    'school' => trim((string) ($parsedData['universite'] ?? '')),
                    'year' => trim((string) ($parsedData['annee_diplome'] ?? '')),
                ],
            ];
        }

        if (empty($profile['skills']) && !empty($parsedData['competences_techniques_raw'])) {
            $profile['skills'] = array_values(array_filter(array_map('trim', preg_split('/[\s,;|\/\n]+/', $parsedData['competences_techniques_raw']))));
        }
        if (empty($profile['languages']) && (!empty($parsedData['competences_langues_raw']) || !empty($parsedData['langues_niveau_raw']))) {
            $langs = !empty($parsedData['competences_langues_raw'])
                ? preg_split('/[\s,;|\/\n]+/', $parsedData['competences_langues_raw'])
                : preg_split('/[\n,;]+/', $parsedData['langues_niveau_raw'] ?? '');
            $profile['languages'] = array_values(array_filter(array_map('trim', $langs)));
        }
        if (empty($profile['projects']) && !empty($parsedData['projets_raw'])) {
            $profile['projects'] = array_values(array_filter(array_map('trim', explode("\n", $parsedData['projets_raw']))));
        }
        if (empty($profile['certifications']) && !empty($parsedData['certifications_raw'])) {
            $profile['certifications'] = array_values(array_filter(array_map('trim', preg_split('/[\n,;]+/', $parsedData['certifications_raw']))));
        }

        return $profile;
    }

    /**
     * Map form POST data (candidate profile form) into canonical profile.
     */
    public static function mapFormToProfile(array $post): array
    {
        $profile = self::emptyProfile();

        $prenom = trim((string) ($post['prenom'] ?? ''));
        $nom = trim((string) ($post['nom'] ?? ''));
        $profile['full_name'] = trim($prenom . ' ' . $nom);

        $profile['job_title'] = trim((string) ($post['poste_actuel'] ?? ''));
        $profile['contact']['email'] = trim((string) ($post['email'] ?? ''));
        $profile['contact']['phone'] = trim((string) ($post['telephone'] ?? ''));
        $profile['contact']['city'] = trim((string) ($post['ville'] ?? ''));
        $profile['contact']['address'] = trim((string) ($post['ville'] ?? '')); // form may not have full address

        $profile['summary'] = trim((string) ($post['disponibilite'] ?? ''));
        $profile['availability'] = trim((string) ($post['disponibilite'] ?? ''));
        $profile['salary_expectation'] = trim((string) ($post['pretention_salaire'] ?? ''));

        $profile['experience'] = [];
        $experiencesJson = $post['experiences_json'] ?? '';
        if ($experiencesJson !== '') {
            $decoded = json_decode($experiencesJson, true);
            if (is_array($decoded)) {
                foreach ($decoded as $e) {
                    $profile['experience'][] = [
                        'title' => trim((string) ($e['poste'] ?? $e['title'] ?? '')),
                        'company' => trim((string) ($e['entreprise'] ?? $e['company'] ?? '')),
                        'location' => trim((string) ($e['location'] ?? '')),
                        'duration' => isset($e['annees']) ? (int) $e['annees'] . ' an(s)' : trim((string) ($e['duration'] ?? '')),
                        'description' => trim((string) ($e['description'] ?? '')),
                    ];
                }
            }
        }
        if (empty($profile['experience']) && ($profile['job_title'] !== '' || !empty($post['entreprise_actuelle']) || !empty($post['experience_detail_raw']))) {
            $profile['experience'] = [
                [
                    'title' => $profile['job_title'] ?: trim((string) ($post['poste_actuel'] ?? '')),
                    'company' => trim((string) ($post['entreprise_actuelle'] ?? '')),
                    'location' => '',
                    'duration' => isset($post['experience_annees']) && $post['experience_annees'] !== '' ? (int) $post['experience_annees'] . ' an(s)' : '',
                    'description' => trim((string) ($post['experience_detail_raw'] ?? '')),
                ],
            ];
        }

        $profile['education'] = [];
        $formationsJson = $post['formations_json'] ?? '';
        if ($formationsJson !== '') {
            $decoded = json_decode($formationsJson, true);
            if (is_array($decoded)) {
                foreach ($decoded as $f) {
                    $profile['education'][] = [
                        'degree' => trim((string) ($f['diplome'] ?? $f['niveau'] ?? $f['degree'] ?? '')),
                        'school' => trim((string) ($f['universite'] ?? $f['school'] ?? '')),
                        'year' => trim((string) ($f['annee'] ?? $f['year'] ?? '')),
                    ];
                }
            }
        }
        if (empty($profile['education']) && (!empty($post['education_niveau']) || !empty($post['diplome']) || !empty($post['universite']))) {
            $profile['education'] = [
                [
                    'degree' => trim((string) ($post['diplome'] ?? $post['education_niveau'] ?? '')),
                    'school' => trim((string) ($post['universite'] ?? '')),
                    'year' => trim((string) ($post['annee_diplome'] ?? '')),
                ],
            ];
        }

        $skillsRaw = trim((string) ($post['competences_techniques_raw'] ?? ''));
        $profile['skills'] = $skillsRaw !== '' ? array_values(array_filter(array_map('trim', preg_split('/[\s,;|\/\n]+/', $skillsRaw)))) : [];

        $langsRaw = trim((string) ($post['competences_langues_raw'] ?? $post['langues_niveau_raw'] ?? ''));
        $profile['languages'] = $langsRaw !== '' ? array_values(array_filter(array_map('trim', preg_split('/[\s,;|\/\n]+/', $langsRaw)))) : [];

        $projRaw = trim((string) ($post['projets_raw'] ?? ''));
        $profile['projects'] = $projRaw !== '' ? array_values(array_filter(array_map('trim', explode("\n", $projRaw)))) : [];

        $certRaw = trim((string) ($post['certifications_raw'] ?? ''));
        $profile['certifications'] = $certRaw !== '' ? array_values(array_filter(array_map('trim', preg_split('/[\n,;]+/', $certRaw)))) : [];

        return $profile;
    }

    /**
     * Build canonical profile from DB row (candidates table).
     */
    public static function mapDbRowToProfile(array $row): array
    {
        $profile = self::emptyProfile();

        $profile['full_name'] = trim(($row['prenom'] ?? '') . ' ' . ($row['nom'] ?? ''));
        $profile['job_title'] = trim((string) ($row['poste_actuel'] ?? ''));
        $profile['contact']['email'] = trim((string) ($row['email'] ?? ''));
        $profile['contact']['phone'] = trim((string) ($row['telephone'] ?? ''));
        $profile['contact']['city'] = trim((string) ($row['ville'] ?? ''));
        $profile['contact']['address'] = trim((string) ($row['ville'] ?? ''));

        $profile['summary'] = trim((string) ($row['disponibilite'] ?? ''));
        $profile['availability'] = trim((string) ($row['disponibilite'] ?? ''));
        $profile['salary_expectation'] = trim((string) ($row['pretention_salaire'] ?? ''));

        $profile['experience'] = [];
        $expJson = $row['experiences_json'] ?? null;
        if ($expJson && is_string($expJson)) {
            $decoded = json_decode($expJson, true);
            if (is_array($decoded)) {
                foreach ($decoded as $e) {
                    $profile['experience'][] = [
                        'title' => trim((string) ($e['poste'] ?? $e['title'] ?? '')),
                        'company' => trim((string) ($e['entreprise'] ?? $e['company'] ?? '')),
                        'location' => trim((string) ($e['location'] ?? '')),
                        'duration' => isset($e['annees']) ? (int) $e['annees'] . ' an(s)' : trim((string) ($e['duration'] ?? '')),
                        'description' => trim((string) ($e['description'] ?? '')),
                    ];
                }
            }
        }
        if (empty($profile['experience']) && ($profile['job_title'] !== '' || !empty($row['entreprise_actuelle']) || !empty($row['experience_detail_raw']))) {
            $profile['experience'] = [
                [
                    'title' => $profile['job_title'] ?: trim((string) ($row['poste_actuel'] ?? '')),
                    'company' => trim((string) ($row['entreprise_actuelle'] ?? '')),
                    'location' => '',
                    'duration' => !empty($row['experience_annees']) ? (int) $row['experience_annees'] . ' an(s)' : '',
                    'description' => trim((string) ($row['experience_detail_raw'] ?? '')),
                ],
            ];
        }

        $profile['education'] = [];
        $formJson = $row['formations_json'] ?? null;
        if ($formJson && is_string($formJson)) {
            $decoded = json_decode($formJson, true);
            if (is_array($decoded)) {
                foreach ($decoded as $f) {
                    $profile['education'][] = [
                        'degree' => trim((string) ($f['diplome'] ?? $f['niveau'] ?? $f['degree'] ?? '')),
                        'school' => trim((string) ($f['universite'] ?? $f['school'] ?? '')),
                        'year' => trim((string) ($f['annee'] ?? $f['year'] ?? '')),
                        'details' => trim((string) ($f['details'] ?? '')),
                    ];
                }
            }
        }
        if (empty($profile['education']) && (!empty($row['education_niveau']) || !empty($row['diplome']) || !empty($row['universite']))) {
            $profile['education'] = [
                [
                    'degree' => trim((string) ($row['diplome'] ?? $row['education_niveau'] ?? '')),
                    'school' => trim((string) ($row['universite'] ?? '')),
                    'year' => trim((string) ($row['annee_diplome'] ?? '')),
                ],
            ];
        }

        $sr = trim((string) ($row['competences_techniques_raw'] ?? ''));
        $profile['skills'] = $sr !== '' ? array_values(array_filter(array_map('trim', preg_split('/[\s,;|\/\n]+/', $sr)))) : [];

        $lr = trim((string) ($row['competences_langues_raw'] ?? $row['langues_niveau_raw'] ?? ''));
        $profile['languages'] = $lr !== '' ? array_values(array_filter(array_map('trim', preg_split('/[\s,;|\/\n]+/', $lr)))) : [];

        $pr = trim((string) ($row['projets_raw'] ?? ''));
        $profile['projects'] = $pr !== '' ? array_values(array_filter(array_map('trim', explode("\n", $pr)))) : [];

        $cr = trim((string) ($row['certifications_raw'] ?? ''));
        $profile['certifications'] = $cr !== '' ? array_values(array_filter(array_map('trim', preg_split('/[\n,;]+/', $cr)))) : [];

        return $profile;
    }

    /**
     * Normalize profile: ensure all keys exist, trim strings, replace empty with EMPTY_PLACEHOLDER for display.
     */
    public static function normalizeCandidateProfile(array $profile): array
    {
        $empty = self::emptyProfile();
        $out = array_merge($empty, $profile);
        $out['contact'] = array_merge($empty['contact'], $out['contact'] ?? []);

        $out['full_name'] = trim((string) ($out['full_name'] ?? ''));
        $out['job_title'] = trim((string) ($out['job_title'] ?? ''));
        $out['summary'] = trim((string) ($out['summary'] ?? ''));
        $out['availability'] = trim((string) ($out['availability'] ?? ''));
        $out['salary_expectation'] = trim((string) ($out['salary_expectation'] ?? ''));
        foreach (array_keys($empty['contact']) as $k) {
            $out['contact'][$k] = trim((string) ($out['contact'][$k] ?? ''));
        }
        // Nettoyer l'email : éviter "email@domain.comLinkedin" (concaténation du texte suivant)
        $out['contact']['email'] = self::cleanEmail((string) ($out['contact']['email'] ?? ''));
        $out['contact']['linkedin'] = self::cleanLinkedInUrl((string) ($out['contact']['linkedin'] ?? ''));
        // Clear city/address when they are parser noise (e.g. "Lecture")
        foreach (['city', 'address'] as $contactKey) {
            $v = $out['contact'][$contactKey] ?? '';
            $vLower = mb_strtolower($v);
            if ($vLower !== '' && in_array($vLower, self::CONTACT_PLACEHOLDER_WORDS, true)) {
                $out['contact'][$contactKey] = '';
            }
        }
        // Éviter doublon Ville / Adresse : si adresse = ville, laisser adresse vide
        $city = trim((string) ($out['contact']['city'] ?? ''));
        $addr = trim((string) ($out['contact']['address'] ?? ''));
        if ($addr !== '' && $city !== '' && mb_strtolower($addr) === mb_strtolower($city)) {
            $out['contact']['address'] = '';
        }

        $out['experience'] = isset($out['experience']) && is_array($out['experience']) ? $out['experience'] : [];
        $degreeLikeTitles = ['cycle ingénieur', 'cycle ingenieur', 'licence', 'diplôme', 'diplome', 'baccalauréat', 'baccalaureat', 'technicien spécialisé', 'technicien specialise'];
        foreach ($out['experience'] as $i => $e) {
            $desc = trim((string) ($e['description'] ?? ''));
            if (mb_strlen($desc) > 1500) {
                $desc = mb_substr($desc, 0, 1500) . '…';
            }
            $out['experience'][$i] = [
                'title' => trim((string) ($e['title'] ?? '')),
                'company' => trim((string) ($e['company'] ?? '')),
                'location' => trim((string) ($e['location'] ?? '')),
                'duration' => trim((string) ($e['duration'] ?? '')),
                'description' => $desc,
            ];
        }
        // Drop experience entries that are empty, only noise, or actually education (titre = diplôme/formation)
        $sectionTitleNoise = ['professionnelle', 'professionnel'];
        $out['experience'] = array_values(array_filter($out['experience'], static function (array $e) use ($degreeLikeTitles, $sectionTitleNoise) {
            $t = $e['title'] ?? '';
            $c = $e['company'] ?? '';
            $d = $e['description'] ?? '';
            $dur = $e['duration'] ?? '';
            $noiseOnly = preg_match('/^[\s@\-–—]*$/u', $t . $c . $d . $dur);
            if ($noiseOnly || ($t === '' && $c === '' && $d === '' && $dur === '')) {
                return false;
            }
            $tLower = mb_strtolower($t);
            foreach ($degreeLikeTitles as $deg) {
                if (str_starts_with($tLower, $deg) || $tLower === $deg) {
                    return false;
                }
            }
            if (in_array($tLower, $sectionTitleNoise, true)) {
                return false;
            }
            if (preg_match('/^(exp[eé]rience|professional(le)?|emploi|formation)(\s|$)/iu', $t)) {
                return false;
            }
            return true;
        }));

        $out['education'] = isset($out['education']) && is_array($out['education']) ? $out['education'] : [];
        foreach ($out['education'] as $i => $ed) {
            $yearRaw = trim((string) ($ed['year'] ?? ''));
            $out['education'][$i] = [
                'degree' => trim((string) ($ed['degree'] ?? '')),
                'school' => trim((string) ($ed['school'] ?? '')),
                'year' => self::sanitizeEducationYear($yearRaw),
                'details' => trim((string) ($ed['details'] ?? '')),
            ];
        }
        // Drop education entries where degree/school is a section header or looks like merged junk (dates/companies)
        $companyNoise = ['onee', 'bank al-maghrib', 'bank al maghrib'];
        $out['education'] = array_values(array_filter($out['education'], static function (array $ed) use ($companyNoise) {
            $degree = trim((string) ($ed['degree'] ?? ''));
            $school = trim((string) ($ed['school'] ?? ''));
            $degreeLower = mb_strtolower($degree);
            $schoolLower = mb_strtolower($school);
            foreach (self::EDUCATION_HEADER_NOISE as $noise) {
                if ($degreeLower === $noise || $schoolLower === $noise || str_starts_with($degreeLower, $noise . ' ') || str_starts_with($schoolLower, $noise . ' ')) {
                    return false;
                }
            }
            foreach ($companyNoise as $co) {
                if ($schoolLower === $co || str_contains($schoolLower, $co)) {
                    return false;
                }
            }
            if (preg_match('/^\d[\d\s\-]+$/u', $degree) || preg_match('/^\d[\d\s\-]+$/u', $school)) {
                return false;
            }
            return $degree !== '' || $school !== '' || trim((string) ($ed['year'] ?? '')) !== '';
        }));

        $out['skills'] = isset($out['skills']) && is_array($out['skills']) ? array_values(array_filter(array_map('trim', $out['skills']))) : [];
        // Filter out blacklisted/stopword skills (exact match)
        $out['skills'] = array_values(array_filter($out['skills'], static function ($s) {
            $lower = mb_strtolower($s);
            if ($lower === '' || mb_strlen($s) > 80) {
                return false;
            }
            return !in_array($lower, self::SKILL_BLACKLIST, true);
        }));
        // Dédupliquer les compétences (insensible à la casse) : garder une seule occurrence
        $seenLower = [];
        $out['skills'] = array_values(array_filter($out['skills'], static function ($s) use (&$seenLower) {
            $lower = mb_strtolower($s);
            if (isset($seenLower[$lower])) {
                return false;
            }
            $seenLower[$lower] = true;
            return true;
        }));
        // Drop skill fragments (too short or pure digits)
        $out['skills'] = array_values(array_filter($out['skills'], static fn ($s) =>
            mb_strlen($s) >= 2 && !preg_match('/^\d+$/', $s)
        ));
        $out['languages'] = isset($out['languages']) && is_array($out['languages']) ? array_values(array_filter(array_map('trim', $out['languages']))) : [];
        $out['projects'] = isset($out['projects']) && is_array($out['projects']) ? array_values(array_filter(array_map('trim', $out['projects']))) : [];
        $out['certifications'] = isset($out['certifications']) && is_array($out['certifications']) ? array_values(array_filter(array_map('trim', $out['certifications']))) : [];
        $out['hobbies'] = isset($out['hobbies']) && is_array($out['hobbies']) ? array_values(array_filter(array_map('trim', $out['hobbies']))) : [];

        return $out;
    }

    /**
     * For display/PDF: return value or EMPTY_PLACEHOLDER.
     */
    public static function displayValue(?string $value): string
    {
        return ($value !== null && trim($value) !== '') ? trim($value) : self::EMPTY_PLACEHOLDER;
    }

    /** Max lengths for candidates table VARCHAR columns (avoid "Data too long"). */
    private const DB_MAX = [
        'nom' => 255,
        'prenom' => 255,
        'email' => 255,
        'telephone' => 50,
        'ville' => 255,
        'poste_actuel' => 255,
        'entreprise_actuelle' => 255,
        'education_niveau' => 255,
        'diplome' => 255,
        'universite' => 255,
        'annee_diplome' => 50,
        'disponibilite' => 255,
        'pretention_salaire' => 50,
    ];

    private static function truncate(?string $value, int $maxLen): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }
        return mb_strlen($value) > $maxLen ? mb_substr($value, 0, $maxLen) : $value;
    }

    /**
     * Convert canonical profile to flat DB row (for saving to candidates table).
     * Truncates string values to column max lengths to avoid SQLSTATE[22001].
     */
    public static function profileToDbRow(array $profile): array
    {
        $profile = self::normalizeCandidateProfile($profile);
        $parts = preg_split('/\s+/u', trim($profile['full_name']), 2);
        $prenom = $parts[0] ?? '';
        $nom = $parts[1] ?? '';

        $email = self::cleanEmail((string) ($profile['contact']['email'] ?? ''));
        $row = [
            'nom' => $nom,
            'prenom' => $prenom,
            'email' => $email !== '' ? $email : null,
            'telephone' => $profile['contact']['phone'] ?: null,
            'ville' => $profile['contact']['city'] ?: null,
            'poste_actuel' => $profile['job_title'] ?: null,
            'disponibilite' => $profile['availability'] ?: null,
            'pretention_salaire' => $profile['salary_expectation'] ?: null,
            'competences_techniques_raw' => count($profile['skills']) > 0 ? implode(', ', $profile['skills']) : null,
            'competences_langues_raw' => count($profile['languages']) > 0 ? implode(', ', $profile['languages']) : null,
            'langues_niveau_raw' => count($profile['languages']) > 0 ? implode(', ', $profile['languages']) : null,
            'experience_detail_raw' => null,
            'experience_annees' => null,
            'entreprise_actuelle' => null,
            'education_niveau' => null,
            'diplome' => null,
            'universite' => null,
            'annee_diplome' => null,
            'projets_raw' => count($profile['projects']) > 0 ? implode("\n", $profile['projects']) : null,
            'certifications_raw' => count($profile['certifications']) > 0 ? implode(', ', $profile['certifications']) : null,
            'formations_json' => null,
            'experiences_json' => null,
        ];

        if (count($profile['experience']) > 0) {
            $first = $profile['experience'][0];
            $row['poste_actuel'] = $first['title'] ?: $row['poste_actuel'];
            $row['entreprise_actuelle'] = $first['company'] ?: null;
            $lines = [];
            foreach ($profile['experience'] as $e) {
                $lines[] = trim($e['title'] . ' @ ' . $e['company'] . ' ' . $e['duration'] . "\n" . $e['description']);
            }
            $row['experience_detail_raw'] = implode("\n\n", $lines);
            $years = 0;
            foreach ($profile['experience'] as $e) {
                if (preg_match('/(\d+)\s*an/s', $e['duration'], $m)) {
                    $years += (int) $m[1];
                }
            }
            $row['experience_annees'] = $years > 0 ? $years : null;
        }
        if (count($profile['education']) > 0) {
            $first = $profile['education'][0];
            $row['education_niveau'] = $first['degree'] ?: null;
            $row['diplome'] = $first['degree'] ?: null;
            $row['universite'] = $first['school'] ?: null;
            $row['annee_diplome'] = $first['year'] ?: null;
        }

        $formations = [];
        foreach ($profile['education'] as $ed) {
            $formations[] = [
                'niveau' => $ed['degree'],
                'diplome' => $ed['degree'],
                'universite' => $ed['school'],
                'annee' => $ed['year'],
                'details' => $ed['details'] ?? '',
            ];
        }
        $row['formations_json'] = count($formations) > 0 ? json_encode($formations, JSON_UNESCAPED_UNICODE) : null;

        $experiences = [];
        foreach ($profile['experience'] as $e) {
            $annees = 0;
            if (preg_match('/(\d+)\s*an/s', $e['duration'], $m)) {
                $annees = (int) $m[1];
            }
            $experiences[] = ['poste' => $e['title'], 'entreprise' => $e['company'], 'annees' => $annees, 'description' => $e['description']];
        }
        $row['experiences_json'] = count($experiences) > 0 ? json_encode($experiences, JSON_UNESCAPED_UNICODE) : null;

        foreach (self::DB_MAX as $col => $maxLen) {
            if (array_key_exists($col, $row) && $row[$col] !== null && is_string($row[$col])) {
                $row[$col] = self::truncate($row[$col], $maxLen);
            }
        }
        if ($row['experience_detail_raw'] !== null && is_string($row['experience_detail_raw']) && mb_strlen($row['experience_detail_raw']) > 8000) {
            $row['experience_detail_raw'] = mb_substr($row['experience_detail_raw'], 0, 8000);
        }
        if ($row['competences_techniques_raw'] !== null && is_string($row['competences_techniques_raw']) && mb_strlen($row['competences_techniques_raw']) > 2000) {
            $row['competences_techniques_raw'] = mb_substr($row['competences_techniques_raw'], 0, 2000);
        }
        if ($row['competences_langues_raw'] !== null && is_string($row['competences_langues_raw']) && mb_strlen($row['competences_langues_raw']) > 1000) {
            $row['competences_langues_raw'] = mb_substr($row['competences_langues_raw'], 0, 1000);
        }
        if ($row['langues_niveau_raw'] !== null && is_string($row['langues_niveau_raw']) && mb_strlen($row['langues_niveau_raw']) > 1000) {
            $row['langues_niveau_raw'] = mb_substr($row['langues_niveau_raw'], 0, 1000);
        }

        return $row;
    }
}
