<?php

namespace App\Core;

class Auth
{
    private const SESSION_KEY = 'smartrecruit_user';

    public static function login(array $user): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION[self::SESSION_KEY] = [
            'id' => (int) $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role'],
        ];
        session_regenerate_id(true);
    }

    public static function logout(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        unset($_SESSION[self::SESSION_KEY]);
        session_regenerate_id(true);
    }

    public static function user(): ?array
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return $_SESSION[self::SESSION_KEY] ?? null;
    }

    public static function id(): ?int
    {
        $u = self::user();
        return $u ? (int) $u['id'] : null;
    }

    public static function role(): ?string
    {
        $u = self::user();
        return $u ? $u['role'] : null;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function isAdmin(): bool
    {
        return self::role() === 'admin';
    }

    public static function isRecruiter(): bool
    {
        return self::role() === 'recruiter';
    }

    public static function isCandidate(): bool
    {
        return self::role() === 'candidate';
    }
}
