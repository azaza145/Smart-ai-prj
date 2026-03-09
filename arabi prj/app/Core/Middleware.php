<?php

namespace App\Core;

class Middleware
{
    public static function guest(): callable
    {
        return function (callable $next) {
            if (Auth::check()) {
                $role = Auth::role();
                if ($role === 'admin') {
                    header('Location: /admin/stats');
                } elseif ($role === 'recruiter') {
                    header('Location: /recruiter/jobs');
                } else {
                    header('Location: /candidate/profile');
                }
                return;
            }
            return $next();
        };
    }

    public static function auth(): callable
    {
        return function (callable $next) {
            if (!Auth::check()) {
                header('Location: /login');
                return;
            }
            return $next();
        };
    }

    public static function role(string ...$roles): callable
    {
        return function (callable $next) use ($roles) {
            if (!Auth::check()) {
                header('Location: /login');
                return;
            }
            if (!in_array(Auth::role(), $roles, true)) {
                http_response_code(403);
                echo '403 Forbidden';
                return;
            }
            return $next();
        };
    }

    public static function admin(): callable
    {
        return self::role('admin');
    }

    public static function recruiter(): callable
    {
        return self::role('admin', 'recruiter');
    }

    public static function candidate(): callable
    {
        return self::role('candidate');
    }
}
