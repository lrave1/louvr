<?php
namespace App\Middleware;

use App\Database;

/**
 * Rate limiter for login attempts. Uses login_attempts table.
 */
class RateLimiter
{
    /**
     * Check if the given email/IP is rate limited.
     * Returns true if too many failed attempts in the window.
     */
    public static function isLimited(Database $db, string $email, string $ip, int $maxAttempts, int $windowSeconds): bool
    {
        $since = date('Y-m-d H:i:s', time() - $windowSeconds);
        $count = $db->fetchColumn(
            'SELECT COUNT(*) FROM login_attempts
             WHERE (email = :email OR ip_address = :ip)
             AND successful = 0
             AND attempted_at > :since',
            [':email' => $email, ':ip' => $ip, ':since' => $since]
        );
        return (int)$count >= $maxAttempts;
    }

    /** Record a login attempt */
    public static function record(Database $db, string $email, string $ip, bool $successful): void
    {
        $db->insert(
            'INSERT INTO login_attempts (email, ip_address, attempted_at, successful)
             VALUES (:email, :ip, :at, :ok)',
            [
                ':email' => $email,
                ':ip'    => $ip,
                ':at'    => date('Y-m-d H:i:s'),
                ':ok'    => $successful ? 1 : 0,
            ]
        );
    }

    /** Clean old attempts (housekeeping) */
    public static function cleanup(Database $db, int $windowSeconds): void
    {
        $before = date('Y-m-d H:i:s', time() - $windowSeconds * 2);
        $db->execute('DELETE FROM login_attempts WHERE attempted_at < :before', [':before' => $before]);
    }
}
