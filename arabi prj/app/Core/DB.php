<?php

namespace App\Core;

use PDO;
use PDOException;

class DB
{
    private static ?PDO $instance = null;

    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            $host = $_ENV['DB_HOST'] ?? 'mysql';
            $port = $_ENV['DB_PORT'] ?? '3306';
            $name = $_ENV['DB_NAME'] ?? 'smartrecruit';
            $user = $_ENV['DB_USER'] ?? 'smartrecruit';
            $pass = $_ENV['DB_PASS'] ?? 'smartrecruit_secret';
            $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";
            try {
                self::$instance = new PDO($dsn, $user, $pass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);
            } catch (PDOException $e) {
                throw new \RuntimeException('Database connection failed: ' . $e->getMessage());
            }
        }
        return self::$instance;
    }

    public static function loadEnv(string $path): void
    {
        if (!is_file($path)) {
            return;
        }
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) {
                continue;
            }
            if (strpos($line, '=') !== false) {
                [$name, $value] = explode('=', $line, 2);
                $name = trim($name);
                $value = trim($value, " \t\n\r\0\x0B\"'");
                if (!array_key_exists($name, $_ENV)) {
                    $_ENV[$name] = $value;
                    putenv("{$name}={$value}");
                }
            }
        }
    }
}
