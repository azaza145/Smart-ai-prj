<?php

namespace App\Models;

use App\Core\DB;

class Application
{
    public static function create(int $jobId, int $candidateId, ?string $coverLetter = null): int
    {
        $pdo = DB::getInstance();
        $stmt = $pdo->prepare("INSERT INTO applications (job_id, candidate_id, cover_letter, status) VALUES (?, ?, ?, 'submitted')");
        $stmt->execute([$jobId, $candidateId, $coverLetter]);
        return (int) $pdo->lastInsertId();
    }

    public static function findByJobAndCandidate(int $jobId, int $candidateId): ?array
    {
        $pdo = DB::getInstance();
        $stmt = $pdo->prepare("SELECT * FROM applications WHERE job_id = ? AND candidate_id = ?");
        $stmt->execute([$jobId, $candidateId]);
        return $stmt->fetch() ?: null;
    }

    /** @return array<int, array> */
    public static function findByCandidate(int $candidateId): array
    {
        $pdo = DB::getInstance();
        $stmt = $pdo->prepare("
            SELECT a.*, j.title as job_title, j.department as job_department
            FROM applications a
            JOIN jobs j ON j.id = a.job_id
            WHERE a.candidate_id = ?
            ORDER BY a.created_at DESC
        ");
        $stmt->execute([$candidateId]);
        return $stmt->fetchAll();
    }

    public static function updateStatus(int $id, string $status): void
    {
        $pdo = DB::getInstance();
        $stmt = $pdo->prepare("UPDATE applications SET status = ? WHERE id = ?");
        $stmt->execute([$status, $id]);
    }

    public static function countByJob(int $jobId): int
    {
        $pdo = DB::getInstance();
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM applications WHERE job_id = ?");
        $stmt->execute([$jobId]);
        return (int) $stmt->fetchColumn();
    }

    public static function getByJob(int $jobId): array
    {
        $pdo = DB::getInstance();
        $stmt = $pdo->prepare("
            SELECT a.*, c.prenom, c.nom, c.email, c.ville, c.poste_actuel
            FROM applications a
            JOIN candidates c ON c.id = a.candidate_id
            WHERE a.job_id = ?
            ORDER BY a.created_at DESC
        ");
        $stmt->execute([$jobId]);
        return $stmt->fetchAll();
    }
}
