<?php
namespace App\Models;

use App\Database;

class LoginAttempt
{
    /** Get recent failed attempts for display (admin) */
    public static function recent(Database $db, int $limit = 50): array
    {
        return $db->fetchAll(
            'SELECT * FROM login_attempts ORDER BY attempted_at DESC LIMIT :limit',
            [':limit' => $limit]
        );
    }
}
