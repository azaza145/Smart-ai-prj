<?php

namespace App\Models;

use App\Core\DB;

class User
{
    public static function findByEmail(string $email): ?array
    {
        $pdo = DB::getInstance();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND status = 'active'");
        $stmt->execute([$email]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function find(int $id): ?array
    {
        $pdo = DB::getInstance();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function all(string $role = null): array
    {
        $pdo = DB::getInstance();
        if ($role) {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE role = ? ORDER BY name");
            $stmt->execute([$role]);
        } else {
            $stmt = $pdo->query("SELECT * FROM users ORDER BY name");
        }
        return $stmt->fetchAll();
    }

    public static function create(array $data): int
    {
        $pdo = DB::getInstance();
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password_hash, role, status) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            $data['name'],
            $data['email'],
            password_hash($data['password'], PASSWORD_DEFAULT),
            $data['role'] ?? 'candidate',
            $data['status'] ?? 'active',
        ]);
        return (int) $pdo->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $allowed = ['name', 'email', 'role', 'status'];
        $updates = [];
        $params = [];
        foreach ($allowed as $k) {
            if (array_key_exists($k, $data)) {
                $updates[] = "{$k} = ?";
                $params[] = $data[$k];
            }
        }
        if (isset($data['password']) && $data['password'] !== '') {
            $updates[] = "password_hash = ?";
            $params[] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        if (empty($updates)) {
            return;
        }
        $params[] = $id;
        $pdo = DB::getInstance();
        $pdo->prepare("UPDATE users SET " . implode(', ', $updates) . " WHERE id = ?")->execute($params);
    }

    public static function count(): int
    {
        $pdo = DB::getInstance();
        return (int) $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    }
}
