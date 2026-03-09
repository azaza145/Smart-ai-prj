<?php

namespace App\Models;

use App\Core\DB;

class PipelineLog
{
    public static function start(string $type, ?int $jobId = null): int
    {
        $pdo = DB::getInstance();
        $stmt = $pdo->prepare("INSERT INTO pipeline_logs (type, job_id, status) VALUES (?, ?, 'running')");
        $stmt->execute([$type, $jobId]);
        return (int) $pdo->lastInsertId();
    }

    public static function complete(int $id, int $rowsAffected = 0): void
    {
        $pdo = DB::getInstance();
        $pdo->prepare("UPDATE pipeline_logs SET rows_affected = ?, status = 'completed', completed_at = CURRENT_TIMESTAMP WHERE id = ?")->execute([$rowsAffected, $id]);
    }

    public static function fail(int $id, string $message): void
    {
        $pdo = DB::getInstance();
        $pdo->prepare("UPDATE pipeline_logs SET status = 'failed', error_message = ?, completed_at = CURRENT_TIMESTAMP WHERE id = ?")->execute([$message, $id]);
    }

    public static function lastNormalization(): ?array
    {
        $pdo = DB::getInstance();
        $stmt = $pdo->query("SELECT * FROM pipeline_logs WHERE type = 'normalization' ORDER BY started_at DESC LIMIT 1");
        return $stmt->fetch() ?: null;
    }

    public static function lastRecommendation(?int $jobId = null): ?array
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
