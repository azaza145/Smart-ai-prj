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

    /** Récupère un CV par id et candidate_id (vérification d'appartenance). */
    public static function findByIdAndCandidate(int $id, int $candidateId): ?array
    {
        $pdo = DB::getInstance();
        $stmt = $pdo->prepare("SELECT * FROM cvs WHERE id = ? AND candidate_id = ?");
        $stmt->execute([$id, $candidateId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** Supprime un CV de la base (le fichier doit être supprimé séparément). */
    public static function delete(int $id): void
    {
        $pdo = DB::getInstance();
        $pdo->prepare("DELETE FROM cvs WHERE id = ?")->execute([$id]);
    }

    /** Réattribue les CV d'un autre candidat vers le candidat connecté (même email, ex. après import CSV). */
    public static function reassignToCandidate(int $fromCandidateId, int $toCandidateId): int
    {
        if ($fromCandidateId === $toCandidateId) {
            return 0;
        }
        $pdo = DB::getInstance();
        $stmt = $pdo->prepare("UPDATE cvs SET candidate_id = ? WHERE candidate_id = ?");
        $stmt->execute([$toCandidateId, $fromCandidateId]);
        return $stmt->rowCount();
    }

    /** Dernier CV déposé (pour ré-extraction avec OCR côté recruteur). */
    public static function getLatest(int $candidateId): ?array
    {
        $pdo = DB::getInstance();
        $stmt = $pdo->prepare("SELECT * FROM cvs WHERE candidate_id = ? ORDER BY uploaded_at DESC LIMIT 1");
        $stmt->execute([$candidateId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function markExtractionAttempted(int $id): void
    {
        $pdo = DB::getInstance();
        try {
            $pdo->prepare("UPDATE cvs SET extraction_attempted_at = NOW() WHERE id = ?")->execute([$id]);
        } catch (\PDOException $e) {
            if (strpos($e->getMessage(), 'Unknown column') === false) {
                throw $e;
            }
        }
    }

    public static function getLatestExtractedText(int $candidateId): ?string
    {
        $row = self::getLatest($candidateId);
        if (!$row || ($row['extracted_text'] ?? '') === '') {
            return null;
        }
        return $row['extracted_text'];
    }

    /** Résout le chemin complet du fichier CV (compatible Windows/Docker). */
    public static function resolveFullPath(string $basePath, string $filePath): ?string
    {
        $relPath = trim(str_replace('\\', '/', (string) $filePath));
        if ($relPath === '') {
            return null;
        }
        $relPath = ltrim($relPath, '/');
        $candidates = [
            $basePath . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relPath),
            $basePath . '/' . $relPath,
            rtrim($basePath, '/\\') . '/' . $relPath,
        ];
        foreach (array_unique($candidates) as $p) {
            if (is_file($p)) {
                return $p;
            }
        }
        return null;
    }
}
