<?php

namespace App\Models;

use App\Core\DB;

class Recommendation
{
    public static function saveBatch(int $jobId, array $ranked): void
    {
        $pdo = DB::getInstance();
        $pdo->beginTransaction();
        try {
            $pdo->prepare("DELETE FROM recommendations WHERE job_id = ?")->execute([$jobId]);
            $stmt = $pdo->prepare("INSERT INTO recommendations (job_id, candidate_id, score, ranking) VALUES (?, ?, ?, ?)");
            $ranking = 1;
            foreach ($ranked as $row) {
                $stmt->execute([
                    $jobId,
                    $row['candidate_id'],
                    $row['score'],
                    $ranking++,
                ]);
            }
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function getByJobPaginated(int $jobId, int $page = 1, int $perPage = 20, array $filters = []): array
    {
        $pdo = DB::getInstance();
        $where = ["r.job_id = ?"];
        $params = [$jobId];
        if (!empty($filters['ville'])) {
            $where[] = "c.ville = ?";
            $params[] = $filters['ville'];
        }
        if (isset($filters['min_score'])) {
            $where[] = "r.score >= ?";
            $params[] = $filters['min_score'];
        }
        if (isset($filters['experience_min'])) {
            $where[] = "c.experience_annees >= ?";
            $params[] = $filters['experience_min'];
        }
        if (isset($filters['experience_max'])) {
            $where[] = "c.experience_annees <= ?";
            $params[] = $filters['experience_max'];
        }
        $whereSql = implode(' AND ', $where);

        $countSql = "SELECT COUNT(*) FROM recommendations r JOIN candidates c ON r.candidate_id = c.id WHERE {$whereSql}";
        $stmtCount = $pdo->prepare($countSql);
        $stmtCount->execute($params);
        $total = (int) $stmtCount->fetchColumn();

        $offset = ($page - 1) * $perPage;
        $sql = "SELECT r.*, c.nom, c.prenom, c.email, c.ville, c.poste_actuel
                FROM recommendations r
                JOIN candidates c ON r.candidate_id = c.id
                WHERE {$whereSql}
                ORDER BY r.ranking
                LIMIT {$perPage} OFFSET {$offset}";
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

    /** @return array<int, array> Recommendations for a candidate (job_id, job_title, score, ranking) */
    public static function getByCandidate(int $candidateId): array
    {
        $pdo = DB::getInstance();
        $stmt = $pdo->prepare("
            SELECT r.job_id, r.score, r.ranking, j.title as job_title, j.department as job_department
            FROM recommendations r
            JOIN jobs j ON j.id = r.job_id
            WHERE r.candidate_id = ?
            ORDER BY r.score DESC, r.ranking ASC
        ");
        $stmt->execute([$candidateId]);
        return $stmt->fetchAll();
    }

    public static function lastRun(int $jobId = null): ?array
    {
        $pdo = DB::getInstance();
        if ($jobId) {
            $stmt = $pdo->prepare("SELECT * FROM pipeline_logs WHERE type = 'recommendation' AND job_id = ? ORDER BY started_at DESC LIMIT 1");
            $stmt->execute([$jobId]);
        } else {
            $stmt = $pdo->query("SELECT * FROM pipeline_logs WHERE type = 'recommendation' ORDER BY started_at DESC LIMIT 1");
        }
        return $stmt->fetch() ?: null;
    }
}
