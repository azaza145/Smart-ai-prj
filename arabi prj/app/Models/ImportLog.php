<?php

namespace App\Models;

use App\Core\DB;

class ImportLog
{
    public static function create(string $filePath): int
    {
        $pdo = DB::getInstance();
        $stmt = $pdo->prepare("INSERT INTO import_logs (file_path, status) VALUES (?, 'running')");
        $stmt->execute([$filePath]);
        return (int) $pdo->lastInsertId();
    }

    public static function complete(int $id, int $processed, int $inserted, int $updated, int $failed, string $errorLog = ''): void
    {
        $pdo = DB::getInstance();
        $stmt = $pdo->prepare("UPDATE import_logs SET rows_processed = ?, rows_inserted = ?, rows_updated = ?, rows_failed = ?, error_log = ?, status = ?, completed_at = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->execute([$processed, $inserted, $updated, $failed, $errorLog, $failed > 0 ? 'completed' : 'completed', $id]);
    }

    public static function fail(int $id, string $error): void
    {
        $pdo = DB::getInstance();
        $pdo->prepare("UPDATE import_logs SET status = 'failed', error_log = ?, completed_at = CURRENT_TIMESTAMP WHERE id = ?")->execute([$error, $id]);
    }

    public static function last(): ?array
    {
        $pdo = DB::getInstance();
        $stmt = $pdo->query("SELECT * FROM import_logs ORDER BY started_at DESC LIMIT 1");
        return $stmt->fetch() ?: null;
    }
}
