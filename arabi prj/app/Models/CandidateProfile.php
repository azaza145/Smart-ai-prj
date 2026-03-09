<?php

namespace App\Models;

use App\Core\DB;

class CandidateProfile
{
    public static function get(int $candidateId): ?array
    {
        $pdo = DB::getInstance();
        $stmt = $pdo->prepare("SELECT * FROM candidate_profiles WHERE candidate_id = ?");
        $stmt->execute([$candidateId]);
        $row = $stmt->fetch();
        if ($row) {
            foreach (['skills_norm', 'languages_norm', 'languages_level_norm', 'education_norm', 'experience_norm'] as $j) {
                if (isset($row[$j]) && is_string($row[$j])) {
                    $row[$j] = json_decode($row[$j], true) ?? [];
                }
            }
        }
        return $row ?: null;
    }

    public static function upsert(int $candidateId, array $norm): void
    {
        $pdo = DB::getInstance();
        $jsonFields = ['skills_norm', 'languages_norm', 'languages_level_norm', 'education_norm', 'experience_norm'];
        $values = [];
        foreach ($jsonFields as $f) {
            $values[$f] = isset($norm[$f]) ? (is_string($norm[$f]) ? $norm[$f] : json_encode($norm[$f])) : null;
        }
        $stmt = $pdo->prepare("INSERT INTO candidate_profiles (candidate_id, skills_norm, languages_norm, languages_level_norm, education_norm, experience_norm) VALUES (?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE skills_norm = VALUES(skills_norm), languages_norm = VALUES(languages_norm), languages_level_norm = VALUES(languages_level_norm), education_norm = VALUES(education_norm), experience_norm = VALUES(experience_norm), updated_at = CURRENT_TIMESTAMP");
        $stmt->execute([
            $candidateId,
            $values['skills_norm'],
            $values['languages_norm'],
            $values['languages_level_norm'],
            $values['education_norm'],
            $values['experience_norm'],
        ]);
    }

    public static function allForRecommend(): array
    {
        $pdo = DB::getInstance();
        $stmt = $pdo->query("
            SELECT c.id, c.nom, c.prenom, c.email, c.ville, c.poste_actuel, c.entreprise_actuelle,
                   c.experience_annees, c.experience_detail_raw, c.projets_raw, c.certifications_raw,
                   c.competences_techniques_raw, c.competences_langues_raw, c.diplome, c.universite,
                   c.education_niveau, p.skills_norm, p.education_norm, p.languages_norm, p.experience_norm
            FROM candidates c
            LEFT JOIN candidate_profiles p ON p.candidate_id = c.id
        ");
        return $stmt->fetchAll();
    }
}
