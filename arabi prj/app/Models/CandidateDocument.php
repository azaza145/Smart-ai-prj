<?php

namespace App\Models;

use App\Core\DB;

class CandidateDocument
{
    public static function findByCandidate(int $candidateId): array
    {
        $pdo = DB::getInstance();
        $stmt = $pdo->prepare("SELECT * FROM candidate_documents WHERE candidate_id = ? ORDER BY doc_type, created_at DESC");
        $stmt->execute([$candidateId]);
        return $stmt->fetchAll();
    }

    public static function create(int $candidateId, string $filePath, string $originalName, string $docType = 'preuve'): int
    {
        $pdo = DB::getInstance();
        $stmt = $pdo->prepare("INSERT INTO candidate_documents (candidate_id, file_path, original_name, doc_type) VALUES (?, ?, ?, ?)");
        $stmt->execute([$candidateId, $filePath, $originalName, $docType]);
        return (int) $pdo->lastInsertId();
    }

    public static function find(int $id): ?array
    {
        $pdo = DB::getInstance();
        $stmt = $pdo->prepare("SELECT * FROM candidate_documents WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function delete(int $id, int $candidateId): bool
    {
        $doc = self::find($id);
        if (!$doc || (int) $doc['candidate_id'] !== $candidateId) {
            return false;
        }
        $pdo = DB::getInstance();
        $pdo->prepare("DELETE FROM candidate_documents WHERE id = ? AND candidate_id = ?")->execute([$id, $candidateId]);
        return true;
    }
}
