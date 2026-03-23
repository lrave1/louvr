<?php
namespace App\Middleware;

/**
 * CSRF protection. Generates and validates tokens per session.
 */
class CsrfMiddleware
{
    /** Generate a CSRF token and store in session */
    public static function generateToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /** Return a hidden input field with the CSRF token */
    public static function field(): string
    {
        $token = htmlspecialchars(self::generateToken(), ENT_QUOTES, 'UTF-8');
        return '<input type="hidden" name="_csrf_token" value="' . $token . '">';
    }

    /** Validate CSRF token from POST data */
    public static function validate(): bool
    {
        $token = $_POST['_csrf_token'] ?? '';
        $sessionToken = $_SESSION['csrf_token'] ?? '';
        if (empty($token) || empty($sessionToken)) {
            return false;
        }
        return hash_equals($sessionToken, $token);
    }

    /** Validate and abort with 403 if invalid */
    public static function enforce(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !self::validate()) {
            http_response_code(403);
            echo 'Invalid CSRF token';
            exit;
        }
    }
}
