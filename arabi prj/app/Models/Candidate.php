<?php

namespace App\Models;

use App\Core\DB;
use App\Services\CandidateProfileSchema;

class Candidate
{
    public static function find(int $id): ?array
    {
        $pdo = DB::getInstance();
        $stmt = $pdo->prepare("SELECT * FROM candidates WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function findByEmail(string $email): ?array
    {
        $pdo = DB::getInstance();
        $stmt = $pdo->prepare("SELECT * FROM candidates WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch() ?: null;
    }

    public static function findByUserId(int $userId): ?array
    {
        $pdo = DB::getInstance();
        $stmt = $pdo->prepare("SELECT * FROM candidates WHERE user_id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetch() ?: null;
    }

    /** CSV column name => DB column name (so every CSV row maps automatically to CV form fields) */
    private static function csvToDbMap(): array
    {
        return [
            'id' => 'csv_source_id',
            'nom' => 'nom',
            'prenom' => 'prenom',
            'email' => 'email',
            'telephone' => 'telephone',
            'age' => 'age',
            'ville' => 'ville',
            'experience_annees' => 'experience_annees',
            'poste_actuel' => 'poste_actuel',
            'entreprise_actuelle' => 'entreprise_actuelle',
            'education_niveau' => 'education_niveau',
            'diplome' => 'diplome',
            'universite' => 'universite',
            'annee_diplome' => 'annee_diplome',
            'competences_techniques' => 'competences_techniques_raw',
            'competences_langues' => 'competences_langues_raw',
            'langues_niveau' => 'langues_niveau_raw',
            'experience_detail' => 'experience_detail_raw',
            'projets' => 'projets_raw',
            'certifications' => 'certifications_raw',
            'disponibilite' => 'disponibilite',
            'pretention_salaire' => 'pretention_salaire',
        ];
    }

    public static function upsertByEmail(array $row): void
    {
        $pdo = DB::getInstance();
        $existing = self::findByEmail($row['email'] ?? '');
        $values = [];
        foreach (self::csvToDbMap() as $csvCol => $dbCol) {
            $val = $row[$csvCol] ?? $row[$dbCol] ?? null;
            if ($val !== null && $val !== '') {
                if ($dbCol === 'experience_annees' && is_numeric($val)) {
                    $val = (int) $val;
                }
                if ($dbCol === 'age' && is_numeric($val)) {
                    $val = (int) $val;
                }
            }
            if ($dbCol === 'csv_source_id' && ($val === null || $val === '')) {
                $val = null;
            }
            $values[$dbCol] = $val;
        }
        if (isset($row['id']) && ($row['id'] !== '' && $row['id'] !== null)) {
            $values['csv_source_id'] = is_numeric($row['id']) ? (int) $row['id'] : $row['id'];
        }
        $values['nom'] = $values['nom'] ?? '';
        $values['prenom'] = $values['prenom'] ?? '';
        $values['email'] = trim($values['email'] ?? '');

        if ($existing) {
            $set = [];
            $params = [];
            foreach ($values as $k => $v) {
                if ($k === 'email') continue;
                $set[] = "{$k} = ?";
                $params[] = $v;
            }
            $params[] = $existing['id'];
            $pdo->prepare("UPDATE candidates SET " . implode(', ', $set) . " WHERE id = ?")->execute($params);
        } else {
            $cols = array_keys($values);
            $placeholders = array_fill(0, count($cols), '?');
            $pdo->prepare("INSERT INTO candidates (" . implode(',', $cols) . ") VALUES (" . implode(',', $placeholders) . ")")
                ->execute(array_values($values));
        }
    }

    public static function paginate(int $page = 1, int $perPage = 20, array $filters = []): array
    {
        $pdo = DB::getInstance();
        $where = ['1=1'];
        $params = [];
        if (!empty($filters['ville'])) {
            $where[] = "ville = ?";
            $params[] = $filters['ville'];
        }
        if (isset($filters['experience_min'])) {
            $where[] = "experience_annees >= ?";
            $params[] = $filters['experience_min'];
        }
        if (isset($filters['experience_max'])) {
            $where[] = "experience_annees <= ?";
            $params[] = $filters['experience_max'];
        }
        $whereSql = implode(' AND ', $where);

        $countSql = "SELECT COUNT(*) FROM candidates WHERE {$whereSql}";
        $stmtCount = $pdo->prepare($countSql);
        $stmtCount->execute($params);
        $total = (int) $stmtCount->fetchColumn();

        $offset = ($page - 1) * $perPage;
        $sql = "SELECT * FROM candidates WHERE {$whereSql} ORDER BY id LIMIT {$perPage} OFFSET {$offset}";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $items = $stmt->fetchAll();

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => (int) ceil($total / $perPage),
        ];
    }

    public static function count(): int
    {
        $pdo = DB::getInstance();
        return (int) $pdo->query("SELECT COUNT(*) FROM candidates")->fetchColumn();
    }

    public static function getDistinctVilles(): array
    {
        $pdo = DB::getInstance();
        $stmt = $pdo->query("SELECT DISTINCT ville FROM candidates WHERE ville IS NOT NULL AND ville != '' ORDER BY ville");
        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }

    public static function updateProfile(int $candidateId, int $userId, array $data): void
    {
        $pdo = DB::getInstance();
        $c = self::find($candidateId);
        if (!$c || (int)($c['user_id'] ?? 0) !== $userId) {
            return;
        }
        $allowed = [
            'nom', 'prenom', 'email', 'telephone', 'age', 'ville', 'experience_annees', 'poste_actuel',
            'entreprise_actuelle', 'education_niveau', 'diplome', 'universite', 'annee_diplome',
            'competences_techniques_raw', 'competences_langues_raw', 'langues_niveau_raw',
            'experience_detail_raw', 'projets_raw', 'certifications_raw', 'disponibilite', 'pretention_salaire',
            'formations_json', 'experiences_json',
        ];
        // Ne mettre à jour que les colonnes qui existent (formations_json/experiences_json ajoutées par migration)
        $existingColumns = self::getCandidateTableColumns($pdo);
        $allowed = array_filter($allowed, static fn($col) => in_array($col, $existingColumns, true));

        $updates = [];
        $params = [];
        foreach ($allowed as $k) {
            if (array_key_exists($k, $data)) {
                $updates[] = "{$k} = ?";
                $params[] = $data[$k];
            }
        }
        if (empty($updates)) return;
        $params[] = $candidateId;
        $pdo->prepare("UPDATE candidates SET " . implode(', ', $updates) . " WHERE id = ?")->execute($params);
    }

    /**
     * Remplit uniquement les champs vides du candidat à partir des données parsées du CV (CV → champs).
     * @return list<string> Noms des champs mis à jour
     */
    public static function fillEmptyFromParsedCv(int $candidateId, array $parsed): array
    {
        $c = self::find($candidateId);
        if (!$c) {
            return [];
        }
        $allowed = [
            'email', 'telephone', 'ville', 'competences_techniques_raw', 'competences_langues_raw',
            'poste_actuel', 'entreprise_actuelle', 'experience_annees', 'education_niveau', 'diplome',
            'universite', 'annee_diplome', 'experience_detail_raw', 'projets_raw', 'certifications_raw',
            'disponibilite', 'pretention_salaire', 'langues_niveau_raw',
        ];
        $existingColumns = self::getCandidateTableColumns(DB::getInstance());
        $allowed = array_filter($allowed, static fn($col) => in_array($col, $existingColumns, true));

        $updates = [];
        $params = [];
        $updatedKeys = [];
        foreach ($allowed as $col) {
            if (!array_key_exists($col, $parsed) || $parsed[$col] === null || $parsed[$col] === '') {
                continue;
            }
            $current = $c[$col] ?? null;
            if ($current === null || trim((string)$current) === '') {
                $updates[] = "{$col} = ?";
                $params[] = $parsed[$col];
                $updatedKeys[] = $col;
            }
        }
        if (empty($updates)) {
            return [];
        }
        $params[] = $candidateId;
        $pdo = DB::getInstance();
        $pdo->prepare("UPDATE candidates SET " . implode(', ', $updates) . " WHERE id = ?")->execute($params);
        return $updatedKeys;
    }

    /**
     * Get canonical profile for this candidate (single source of truth for preview + PDF).
     * Uses profile_json if set, otherwise builds from DB row.
     */
    public static function getProfile(int $candidateId): array
    {
        $c = self::find($candidateId);
        if (!$c) {
            return CandidateProfileSchema::normalizeCandidateProfile(CandidateProfileSchema::emptyProfile());
        }
        $columns = self::getCandidateTableColumns(DB::getInstance());
        if (in_array('profile_json', $columns, true) && !empty($c['profile_json'])) {
            $decoded = json_decode($c['profile_json'], true);
            if (is_array($decoded)) {
                return CandidateProfileSchema::normalizeCandidateProfile($decoded);
            }
        }
        return CandidateProfileSchema::normalizeCandidateProfile(
            CandidateProfileSchema::mapDbRowToProfile($c)
        );
    }

    /**
     * Save canonical profile: store profile_json and sync flat columns.
     */
    public static function saveProfile(int $candidateId, array $profile): void
    {
        $profile = CandidateProfileSchema::normalizeCandidateProfile($profile);
        $pdo = DB::getInstance();
        $columns = self::getCandidateTableColumns($pdo);
        $row = CandidateProfileSchema::profileToDbRow($profile);

        $set = [];
        $params = [];
        if (in_array('profile_json', $columns, true)) {
            $set[] = 'profile_json = ?';
            $params[] = json_encode($profile, JSON_UNESCAPED_UNICODE);
        }
        // Get current candidate to preserve email if not provided
        $current = self::find($candidateId);
        $currentEmail = $current['email'] ?? '';
        
        $allowed = ['nom', 'prenom', 'email', 'telephone', 'ville', 'poste_actuel', 'entreprise_actuelle', 'experience_annees', 'education_niveau', 'diplome', 'universite', 'annee_diplome', 'competences_techniques_raw', 'competences_langues_raw', 'langues_niveau_raw', 'experience_detail_raw', 'projets_raw', 'certifications_raw', 'disponibilite', 'pretention_salaire', 'formations_json', 'experiences_json'];
        foreach ($allowed as $col) {
            if (!in_array($col, $columns, true)) {
                continue;
            }
            $val = $row[$col] ?? null;
            
            // For email: if null or empty, preserve existing email to avoid NOT NULL constraint violation
            if ($col === 'email') {
                if ($val === null || $val === '') {
                    $val = $currentEmail !== '' ? $currentEmail : null;
                }
                // If still null, skip updating email field (don't include it in UPDATE)
                if ($val === null || $val === '') {
                    continue;
                }
            }
            
            if ($col === 'experience_annees' && $val !== null) {
                $val = (int) $val;
            }
            $set[] = "{$col} = ?";
            $params[] = $val;
        }
        if (empty($set)) {
            return;
        }
        $newEmail = $row['email'] ?? null;
        // Only check email uniqueness if a new email is provided
        if ($newEmail !== null && $newEmail !== '' && $newEmail !== $currentEmail) {
            $existing = self::findByEmail($newEmail);
            if ($existing && (int) ($existing['id'] ?? 0) !== $candidateId) {
                throw new \RuntimeException('Cet email est déjà utilisé par un autre candidat. Veuillez corriger l\'email dans le CV ou dans le profil.');
            }
        }
        $params[] = $candidateId;
        try {
            $pdo->prepare('UPDATE candidates SET ' . implode(', ', $set) . ' WHERE id = ?')->execute($params);
        } catch (\PDOException $e) {
            $code = $e->getCode();
            $msg = $e->getMessage();
            if ((int) $code === 23000 || strpos($msg, '1062') !== false || strpos($msg, 'Duplicate entry') !== false) {
                throw new \RuntimeException('Cet email est déjà utilisé par un autre candidat. Veuillez corriger l\'email dans le CV ou dans le profil.', 0, $e);
            }
            throw $e;
        }
    }

    /**
     * Apply an already-normalized canonical profile (e.g. from Ollama). Overwrite or fill-empty then save.
     * @return list<string> Keys updated
     */
    public static function applyCanonicalProfile(int $candidateId, array $canonicalProfile, bool $overwrite): array
    {
        $current = self::getProfile($candidateId);
        $incoming = CandidateProfileSchema::normalizeCandidateProfile($canonicalProfile);
        $merged = $overwrite ? self::mergeProfileOverwrite($current, $incoming) : self::mergeProfileFillEmpty($current, $incoming);
        self::saveProfile($candidateId, $merged);
        return self::diffProfileKeys($current, $merged);
    }

    /**
     * Merge parsed CV data into existing profile (fill only empty fields), then save.
     * @return list<string> Keys updated
     */
    public static function mergeParsedIntoProfile(int $candidateId, array $parsedData): array
    {
        $current = self::getProfile($candidateId);
        $fromParsed = CandidateProfileSchema::mapParsedCvToProfile($parsedData);
        $merged = self::mergeProfileFillEmpty($current, $fromParsed);
        self::saveProfile($candidateId, $merged);
        return self::diffProfileKeys($current, $merged);
    }

    /**
     * Replace existing profile with parsed CV data (écraser avant de remplir). Non-empty parsed fields overwrite current.
     * @return list<string> Keys updated
     */
    public static function mergeParsedIntoProfileOverwrite(int $candidateId, array $parsedData): array
    {
        $current = self::getProfile($candidateId);
        $fromParsed = CandidateProfileSchema::mapParsedCvToProfile($parsedData);
        $merged = self::mergeProfileOverwrite($current, $fromParsed);
        self::saveProfile($candidateId, $merged);
        return self::diffProfileKeys($current, $merged);
    }

    private static function mergeProfileOverwrite(array $current, array $incoming): array
    {
        $out = $current;
        if (trim($incoming['full_name'] ?? '') !== '') {
            $out['full_name'] = $incoming['full_name'];
        }
        if (trim($incoming['job_title'] ?? '') !== '') {
            $out['job_title'] = $incoming['job_title'];
        }
        foreach (['email', 'phone', 'city', 'address', 'linkedin'] as $k) {
            if (trim($incoming['contact'][$k] ?? '') !== '') {
                $out['contact'][$k] = $incoming['contact'][$k];
            }
        }
        if (trim($incoming['summary'] ?? '') !== '') {
            $out['summary'] = $incoming['summary'];
        }
        if (trim($incoming['availability'] ?? '') !== '') {
            $out['availability'] = $incoming['availability'];
        }
        if (trim($incoming['salary_expectation'] ?? '') !== '') {
            $out['salary_expectation'] = $incoming['salary_expectation'];
        }
        if (count($incoming['experience']) > 0) {
            $out['experience'] = $incoming['experience'];
        }
        if (count($incoming['education']) > 0) {
            $out['education'] = $incoming['education'];
        }
        if (count($incoming['skills']) > 0) {
            $out['skills'] = $incoming['skills'];
        }
        if (count($incoming['languages']) > 0) {
            $out['languages'] = $incoming['languages'];
        }
        if (count($incoming['projects']) > 0) {
            $out['projects'] = $incoming['projects'];
        }
        if (count($incoming['certifications']) > 0) {
            $out['certifications'] = $incoming['certifications'];
        }
        if (count($incoming['hobbies'] ?? []) > 0) {
            $out['hobbies'] = $incoming['hobbies'];
        }
        return CandidateProfileSchema::normalizeCandidateProfile($out);
    }

    private static function mergeProfileFillEmpty(array $current, array $incoming): array
    {
        $out = $current;
        if (trim($incoming['full_name'] ?? '') !== '' && trim($current['full_name'] ?? '') === '') {
            $out['full_name'] = $incoming['full_name'];
        }
        if (trim($incoming['job_title'] ?? '') !== '' && trim($current['job_title'] ?? '') === '') {
            $out['job_title'] = $incoming['job_title'];
        }
        foreach (['email', 'phone', 'city', 'address', 'linkedin'] as $k) {
            if (trim($incoming['contact'][$k] ?? '') !== '' && trim($current['contact'][$k] ?? '') === '') {
                $out['contact'][$k] = $incoming['contact'][$k];
            }
        }
        if (trim($incoming['summary'] ?? '') !== '' && trim($current['summary'] ?? '') === '') {
            $out['summary'] = $incoming['summary'];
        }
        if (trim($incoming['availability'] ?? '') !== '' && trim($current['availability'] ?? '') === '') {
            $out['availability'] = $incoming['availability'];
        }
        if (trim($incoming['salary_expectation'] ?? '') !== '' && trim($current['salary_expectation'] ?? '') === '') {
            $out['salary_expectation'] = $incoming['salary_expectation'];
        }
        if (count($incoming['experience']) > 0 && count($current['experience']) === 0) {
            $out['experience'] = $incoming['experience'];
        }
        if (count($incoming['education']) > 0 && count($current['education']) === 0) {
            $out['education'] = $incoming['education'];
        }
        if (count($incoming['skills']) > 0 && count($current['skills']) === 0) {
            $out['skills'] = $incoming['skills'];
        }
        if (count($incoming['languages']) > 0 && count($current['languages']) === 0) {
            $out['languages'] = $incoming['languages'];
        }
        if (count($incoming['projects']) > 0 && count($current['projects']) === 0) {
            $out['projects'] = $incoming['projects'];
        }
        if (count($incoming['certifications']) > 0 && count($current['certifications']) === 0) {
            $out['certifications'] = $incoming['certifications'];
        }
        if (count($incoming['hobbies'] ?? []) > 0 && count($current['hobbies'] ?? []) === 0) {
            $out['hobbies'] = $incoming['hobbies'];
        }
        return CandidateProfileSchema::normalizeCandidateProfile($out);
    }

    private static function diffProfileKeys(array $before, array $after): array
    {
        $keys = [];
        if (($after['full_name'] ?? '') !== ($before['full_name'] ?? '')) {
            $keys[] = 'full_name';
        }
        if (($after['job_title'] ?? '') !== ($before['job_title'] ?? '')) {
            $keys[] = 'job_title';
        }
        foreach (['email', 'phone', 'city'] as $k) {
            if (($after['contact'][$k] ?? '') !== ($before['contact'][$k] ?? '')) {
                $keys[] = 'contact.' . $k;
            }
        }
        if (($after['summary'] ?? '') !== ($before['summary'] ?? '')) {
            $keys[] = 'summary';
        }
        if (count($after['experience'] ?? []) !== count($before['experience'] ?? [])) {
            $keys[] = 'experience';
        }
        if (count($after['education'] ?? []) !== count($before['education'] ?? [])) {
            $keys[] = 'education';
        }
        if (count($after['skills'] ?? []) !== count($before['skills'] ?? [])) {
            $keys[] = 'skills';
        }
        if (count($after['languages'] ?? []) !== count($before['languages'] ?? [])) {
            $keys[] = 'languages';
        }
        return $keys;
    }

    /** @return list<string> */
    private static function getCandidateTableColumns(\PDO $pdo): array
    {
        static $cache = null;
        if ($cache !== null) {
            return $cache;
        }
        $stmt = $pdo->query("SHOW COLUMNS FROM candidates");
        $cache = array_column($stmt->fetchAll(\PDO::FETCH_ASSOC), 'Field');
        return $cache;
    }

    /** Inscription : lie un candidat existant (même email) au user ou en crée un nouveau. */
    public static function createFromRegistration(int $userId, array $data): int
    {
        $email = trim($data['email'] ?? '');
        $existing = $email !== '' ? self::findByEmail($email) : null;
        if ($existing) {
            $pdo = DB::getInstance();
            $pdo->prepare("UPDATE candidates SET user_id = ?, prenom = COALESCE(NULLIF(TRIM(prenom), ''), ?), updated_at = CURRENT_TIMESTAMP WHERE id = ?")
                ->execute([$userId, $data['prenom'] ?? $data['name'] ?? '', $existing['id']]);
            return (int) $existing['id'];
        }
        $pdo = DB::getInstance();
        $stmt = $pdo->prepare("INSERT INTO candidates (user_id, nom, prenom, email, telephone, age, ville, experience_annees, poste_actuel, entreprise_actuelle, education_niveau, diplome, universite, annee_diplome, competences_techniques_raw, competences_langues_raw, langues_niveau_raw, experience_detail_raw, projets_raw, certifications_raw, disponibilite, pretention_salaire) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->execute([
            $userId,
            $data['nom'] ?? '',
            $data['prenom'] ?? '',
            $data['email'] ?? '',
            $data['telephone'] ?? null,
            isset($data['age']) ? (int)$data['age'] : null,
            $data['ville'] ?? null,
            isset($data['experience_annees']) ? (int)$data['experience_annees'] : null,
            $data['poste_actuel'] ?? null,
            $data['entreprise_actuelle'] ?? null,
            $data['education_niveau'] ?? null,
            $data['diplome'] ?? null,
            $data['universite'] ?? null,
            $data['annee_diplome'] ?? null,
            $data['competences_techniques_raw'] ?? null,
            $data['competences_langues_raw'] ?? null,
            $data['langues_niveau_raw'] ?? null,
            $data['experience_detail_raw'] ?? null,
            $data['projets_raw'] ?? null,
            $data['certifications_raw'] ?? null,
            $data['disponibilite'] ?? null,
            $data['pretention_salaire'] ?? null,
        ]);
        return (int) $pdo->lastInsertId();
    }
}
