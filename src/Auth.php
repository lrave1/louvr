<?php
namespace App;

/**
 * Authentication and authorisation helper.
 * Manages session-based auth with secure defaults.
 */
class Auth
{
    /** Start a secure session */
    public static function startSession(array $config): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        $secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        session_set_cookie_params([
            'lifetime' => $config['lifetime'],
            'path'     => '/',
            'httponly'  => true,
            'secure'   => $secure,
            'samesite' => 'Strict',
        ]);
        session_name($config['name']);
        session_start();
    }

    /** Log a user in - stores user data in session */
    public static function login(array $user): void
    {
        session_regenerate_id(true);
        $_SESSION['user_id']   = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['logged_in'] = true;
    }

    /** Log out and destroy session */
    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }

    public static function check(): bool
    {
        return session_status() === PHP_SESSION_ACTIVE && !empty($_SESSION['logged_in']);
    }

    public static function id(): ?int
    {
        return session_status() === PHP_SESSION_ACTIVE ? ($_SESSION['user_id'] ?? null) : null;
    }

    public static function name(): ?string
    {
        return session_status() === PHP_SESSION_ACTIVE ? ($_SESSION['user_name'] ?? null) : null;
    }

    public static function role(): ?string
    {
        return session_status() === PHP_SESSION_ACTIVE ? ($_SESSION['user_role'] ?? null) : null;
    }

    public static function isAdmin(): bool
    {
        return self::role() === 'admin';
    }

    /** Require authentication - redirect to login if not authed */
    public static function requireAuth(): void
    {
        if (!self::check()) {
            header('Location: /login');
            exit;
        }
    }

    /** Require admin role */
    public static function requireAdmin(): void
    {
        self::requireAuth();
        if (!self::isAdmin()) {
            http_response_code(403);
            echo 'Forbidden';
            exit;
        }
    }

    /** Get client IP */
    public static function ip(): string
    {
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
}
