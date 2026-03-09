<?php

namespace App\Models;

use App\Core\DB;

class PasswordReset
{
    private const TOKEN_VALID_HOURS = 2;

    public static function create(string $email): ?string
    {
        $pdo = DB::getInstance();
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+' . self::TOKEN_VALID_HOURS . ' hours'));
        $pdo->prepare("INSERT INTO password_reset_tokens (email, token, expires_at) VALUES (?, ?, ?)")
            ->execute([$email, $token, $expires]);
        return $token;
    }

    public static function findByToken(string $token): ?array
    {
        $pdo = DB::getInstance();
        $stmt = $pdo->prepare("SELECT * FROM password_reset_tokens WHERE token = ? AND expires_at > NOW() ORDER BY id DESC LIMIT 1");
        $stmt->execute([$token]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function deleteByToken(string $token): void
    {
        $pdo = DB::getInstance();
        $pdo->prepare("DELETE FROM password_reset_tokens WHERE token = ?")->execute([$token]);
    }

    public static function deleteExpired(): void
    {
        DB::getInstance()->exec("DELETE FROM password_reset_tokens WHERE expires_at <= NOW()");
    }
}
