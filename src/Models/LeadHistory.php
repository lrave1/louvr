<?php
namespace App\Models;

use App\Database;
use App\Auth;

class LeadHistory
{
    /** Record a lead event */
    public static function record(Database $db, int $leadId, string $action, ?string $oldValue = null, ?string $newValue = null, ?string $note = null): int
    {
        return $db->insert(
            'INSERT INTO lead_history (lead_id, user_id, action, old_value, new_value, note, ip_address, created_at)
             VALUES (:lead_id, :user_id, :action, :old, :new, :note, :ip, datetime("now"))',
            [
                ':lead_id' => $leadId,
                ':user_id' => Auth::id(),
                ':action'  => $action,
                ':old'     => $oldValue,
                ':new'     => $newValue,
                ':note'    => $note,
                ':ip'      => Auth::ip(),
            ]
        );
    }

    /** Get full timeline for a lead */
    public static function timeline(Database $db, int $leadId): array
    {
        return $db->fetchAll(
            'SELECT h.*, u.name AS user_name
             FROM lead_history h
             LEFT JOIN users u ON h.user_id = u.id
             WHERE h.lead_id = :id
             ORDER BY h.created_at DESC',
            [':id' => $leadId]
        );
    }
}
