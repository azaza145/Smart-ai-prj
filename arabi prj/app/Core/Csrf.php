<?php

namespace App\Core;

class Csrf
{
    private const TOKEN_KEY = 'smartrecruit_csrf';

    public static function token(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (empty($_SESSION[self::TOKEN_KEY])) {
            $_SESSION[self::TOKEN_KEY] = bin2hex(random_bytes(32));
        }
        return $_SESSION[self::TOKEN_KEY];
    }

    public static function field(): string
    {
        $t = self::token();
        return '<input type="hidden" name="_token" value="' . htmlspecialchars($t) . '">';
    }

    public static function verify(): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $token = $_POST['_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        return $token !== '' && hash_equals($_SESSION[self::TOKEN_KEY] ?? '', $token);
    }
}
