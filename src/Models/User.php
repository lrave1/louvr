<?php
namespace App\Models;

use App\Database;

class User
{
    public static function findById(Database $db, int $id): ?array
    {
        return $db->fetch('SELECT * FROM users WHERE id = :id', [':id' => $id]);
    }

    public static function findByEmail(Database $db, string $email): ?array
    {
        return $db->fetch('SELECT * FROM users WHERE email = :email', [':email' => $email]);
    }

    public static function findByApiKey(Database $db, string $key): ?array
    {
        return $db->fetch(
            'SELECT * FROM users WHERE api_key = :key AND is_active = 1',
            [':key' => $key]
        );
    }

    public static function all(Database $db): array
    {
        return $db->fetchAll('SELECT * FROM users ORDER BY name ASC');
    }

    public static function activeReps(Database $db): array
    {
        return $db->fetchAll(
            'SELECT * FROM users WHERE role = :role AND is_active = 1 ORDER BY name ASC',
            [':role' => 'rep']
        );
    }

    public static function create(Database $db, array $data): int
    {
        return $db->insert(
            'INSERT INTO users (name, email, phone, role, password_hash, api_key, is_active, created_at, updated_at)
             VALUES (:name, :email, :phone, :role, :hash, :api_key, 1, datetime("now"), datetime("now"))',
            [
                ':name'    => $data['name'],
                ':email'   => $data['email'],
                ':phone'   => $data['phone'] ?? '',
                ':role'    => $data['role'] ?? 'rep',
                ':hash'    => password_hash($data['password'], PASSWORD_BCRYPT),
                ':api_key' => bin2hex(random_bytes(32)),
            ]
        );
    }

    public static function update(Database $db, int $id, array $data): void
    {
        $fields = [];
        $params = [':id' => $id];

        foreach (['name', 'email', 'phone', 'role', 'states', 'postcodes'] as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = :$field";
                $params[":$field"] = $data[$field];
            }
        }

        if (!empty($data['password'])) {
            $fields[] = 'password_hash = :hash';
            $params[':hash'] = password_hash($data['password'], PASSWORD_BCRYPT);
        }

        if (isset($data['is_active'])) {
            $fields[] = 'is_active = :is_active';
            $params[':is_active'] = (int)$data['is_active'];
        }

        $fields[] = 'updated_at = datetime("now")';
        $db->execute('UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = :id', $params);
    }

    public static function regenerateApiKey(Database $db, int $id): string
    {
        $key = bin2hex(random_bytes(32));
        $db->execute(
            'UPDATE users SET api_key = :key, updated_at = datetime("now") WHERE id = :id',
            [':key' => $key, ':id' => $id]
        );
        return $key;
    }

    /**
     * Find matching reps for a lead based on state and postcode.
     * Returns array of matching rep IDs, sorted by best match.
     */
    public static function matchRepsForLead(Database $db, string $state, string $postcode): array
    {
        $reps = self::activeReps($db);
        $matches = [];

        $state = strtoupper(trim($state));
        $postcode = trim($postcode);

        foreach ($reps as $rep) {
            $repStates = array_map('trim', array_map('strtoupper', explode(',', $rep['states'] ?? '')));
            $repPostcodes = trim($rep['postcodes'] ?? '');

            $stateMatch = !empty($state) && in_array($state, $repStates);
            $postcodeMatch = false;

            if (!empty($postcode) && !empty($repPostcodes)) {
                // Parse postcode ranges: "4000-4179, 4500, 4600-4610"
                $ranges = array_map('trim', explode(',', $repPostcodes));
                foreach ($ranges as $range) {
                    if (str_contains($range, '-')) {
                        [$low, $high] = array_map('trim', explode('-', $range, 2));
                        if ($postcode >= $low && $postcode <= $high) {
                            $postcodeMatch = true;
                            break;
                        }
                    } elseif ($postcode === $range) {
                        $postcodeMatch = true;
                        break;
                    }
                }
            }

            if ($stateMatch || $postcodeMatch) {
                $score = ($stateMatch ? 1 : 0) + ($postcodeMatch ? 2 : 0);
                $matches[] = ['rep' => $rep, 'score' => $score];
            }
        }

        // Sort by score descending (postcode match > state match)
        usort($matches, fn($a, $b) => $b['score'] <=> $a['score']);
        return array_column($matches, 'rep');
    }

    /** Rep performance stats */
    public static function repStats(Database $db, int $userId): array
    {
        $total = (int)$db->fetchColumn(
            'SELECT COUNT(*) FROM leads WHERE assigned_to = :id',
            [':id' => $userId]
        );
        $won = (int)$db->fetchColumn(
            'SELECT COUNT(*) FROM leads WHERE assigned_to = :id AND status = :s',
            [':id' => $userId, ':s' => 'won']
        );
        $quoted = (int)$db->fetchColumn(
            'SELECT COALESCE(SUM(quoted_amount), 0) FROM leads WHERE assigned_to = :id AND status = :s',
            [':id' => $userId, ':s' => 'won']
        );
        return [
            'total_leads'     => $total,
            'won'             => $won,
            'conversion_rate' => $total > 0 ? round(($won / $total) * 100, 1) : 0,
            'total_revenue'   => $quoted,
        ];
    }
}
