<?php

namespace App\Models;

use App\Core\DB;

class Job
{
    public static function find(int $id): ?array
    {
        $pdo = DB::getInstance();
        $stmt = $pdo->prepare("SELECT j.*, u.name as created_by_name FROM jobs j LEFT JOIN users u ON j.created_by = u.id WHERE j.id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function all(): array
    {
        return self::listWithFilters([]);
    }

    /** @param array{skills?: string, type_contrat?: string, title?: string} $filters */
    public static function listWithFilters(array $filters): array
    {
        $pdo = DB::getInstance();
        $existing = self::getJobColumns($pdo);
        $where = [];
        $params = [];
        if (!empty($filters['title'])) {
            $where[] = " j.title LIKE ? ";
            $params[] = '%' . trim($filters['title']) . '%';
        }
        if (!empty($filters['skills'])) {
            $terms = array_filter(array_map('trim', preg_split('/[\s,;]+/', $filters['skills'])));
            foreach ($terms as $t) {
                if (in_array('skills_raw', $existing, true)) {
                    $where[] = " (j.skills_raw IS NOT NULL AND j.skills_raw != '' AND (j.skills_raw LIKE ? OR j.title LIKE ?)) ";
                } else {
                    $where[] = " (j.title LIKE ?) ";
                }
                $params[] = '%' . $t . '%';
                if (in_array('skills_raw', $existing, true)) {
                    $params[] = '%' . $t . '%';
                }
            }
        }
        if (!empty($filters['type_contrat']) && in_array('type_contrat', $existing, true)) {
            $where[] = " j.type_contrat = ? ";
            $params[] = $filters['type_contrat'];
        }
        $whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';
        $sql = "SELECT j.*, u.name as created_by_name FROM jobs j LEFT JOIN users u ON j.created_by = u.id {$whereSql} ORDER BY j.created_at DESC";
        $stmt = $params ? $pdo->prepare($sql) : $pdo->query($sql);
        if ($params) {
            $stmt->execute($params);
        }
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public static function create(array $data): int
    {
        $pdo = DB::getInstance();
        $cols = ['title', 'department', 'description', 'requirements', 'created_by'];
        $optCols = ['skills_raw', 'type_contrat'];
        $existing = self::getJobColumns($pdo);
        foreach ($optCols as $c) {
            if (in_array($c, $existing, true)) {
                $cols[] = $c;
            }
        }
        $placeholders = implode(', ', array_fill(0, count($cols), '?'));
        $stmt = $pdo->prepare("INSERT INTO jobs (" . implode(', ', $cols) . ") VALUES ({$placeholders})");
        $vals = [
            $data['title'],
            $data['department'] ?? null,
            $data['description'] ?? null,
            $data['requirements'] ?? null,
            $data['created_by'],
        ];
        if (in_array('skills_raw', $cols, true)) {
            $vals[] = $data['skills_raw'] ?? null;
        }
        if (in_array('type_contrat', $cols, true)) {
            $vals[] = $data['type_contrat'] ?? null;
        }
        $stmt->execute($vals);
        return (int) $pdo->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $pdo = DB::getInstance();
        $existing = self::getJobColumns($pdo);
        $allowed = ['title', 'department', 'description', 'requirements'];
        if (in_array('skills_raw', $existing, true)) {
            $allowed[] = 'skills_raw';
        }
        if (in_array('type_contrat', $existing, true)) {
            $allowed[] = 'type_contrat';
        }
        $updates = [];
        $params = [];
        foreach ($allowed as $k) {
            if (array_key_exists($k, $data)) {
                $updates[] = "{$k} = ?";
                $params[] = $data[$k];
            }
        }
        if (empty($updates)) {
            return;
        }
        $params[] = $id;
        $pdo->prepare("UPDATE jobs SET " . implode(', ', $updates) . " WHERE id = ?")->execute($params);
    }

    public static function duplicate(int $id, int $createdBy): ?int
    {
        $job = self::find($id);
        if (!$job) {
            return null;
        }
        $title = trim($job['title'] ?? '');
        if ($title !== '') {
            $title .= ' (copie)';
        }
        return self::create([
            'title' => $title,
            'department' => $job['department'] ?? null,
            'description' => $job['description'] ?? null,
            'requirements' => $job['requirements'] ?? null,
            'skills_raw' => $job['skills_raw'] ?? null,
            'type_contrat' => $job['type_contrat'] ?? null,
            'created_by' => $createdBy,
        ]);
    }

    private static function getJobColumns(\PDO $pdo): array
    {
        static $cache = null;
        if ($cache !== null) {
            return $cache;
        }
        $stmt = $pdo->query("SHOW COLUMNS FROM jobs");
        $cache = array_column($stmt->fetchAll(\PDO::FETCH_ASSOC), 'Field');
        return $cache;
    }

    /** List of distinct contract types for filters */
    public static function getDistinctTypeContrat(): array
    {
        $pdo = DB::getInstance();
        if (!in_array('type_contrat', self::getJobColumns($pdo), true)) {
            return [];
        }
        $stmt = $pdo->query("SELECT DISTINCT type_contrat FROM jobs WHERE type_contrat IS NOT NULL AND type_contrat != '' ORDER BY type_contrat");
        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }

    public static function delete(int $id): void
    {
        $pdo = DB::getInstance();
        $pdo->prepare("DELETE FROM jobs WHERE id = ?")->execute([$id]);
    }

    public static function count(): int
    {
        $pdo = DB::getInstance();
        return (int) $pdo->query("SELECT COUNT(*) FROM jobs")->fetchColumn();
    }
}
