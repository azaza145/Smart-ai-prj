<?php

namespace App\Models;

use App\Core\DB;

class Cv
{
    public static function findByCandidate(int $candidateId): array
    {
        $pdo = DB::getInstance();
        $stmt = $pdo->prepare("SELECT * FROM cvs WHERE candidate_id = ? ORDER BY uploaded_at DESC");
        $stmt->execute([$candidateId]);
        return $stmt->fetchAll();
    }

    public static function create(int $candidateId, string $filePath, string $originalName, ?string $extractedText = null): int
    {
        $pdo = DB::getInstance();
        $stmt = $pdo->prepare("INSERT INTO cvs (candidate_id, file_path, original_name, extracted_text) VALUES (?, ?, ?, ?)");
        $stmt->execute([$candidateId, $filePath, $originalName, $extractedText]);
        return (int) $pdo->lastInsertId();
    }

    public static function updateExtractedText(int $id, string $text): void
    {
        $pdo = DB::getInstance();
        $pdo->prepare("UPDATE cvs SET extracted_text = ? WHERE id = ?")->execute([$text, $id]);
    }

    /** Dernier CV déposé (pour ré-extraction avec OCR côté recruteur). */
    public static function getLatest(int $candidateId): ?array
    {
        $pdo = DB::getInstance();
        $stmt = $pdo->prepare("SELECT id, file_path, extracted_text FROM cvs WHERE candidate_id = ? ORDER BY uploaded_at DESC LIMIT 1");
        $stmt->execute([$candidateId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function getLatestExtractedText(int $candidateId): ?string
    {
        $row = self::getLatest($candidateId);
        if (!$row || ($row['extracted_text'] ?? '') === '') {
            return null;
        }
        return $row['extracted_text'];
    }
}
