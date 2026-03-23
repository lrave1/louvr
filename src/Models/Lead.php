<?php
namespace App\Models;

use App\Database;

class Lead
{
    // Fallback defaults - overridden by settings table
    public const DEFAULT_STATUSES = ['New', 'Assigned', 'Booked', 'Quoted', 'Won', 'Lost'];
    public const DEFAULT_SOURCES  = ['Web Form', 'Phone', 'Referral', 'Other'];
    public const DEFAULT_PROPERTY_TYPES = ['Residential', 'Commercial'];
    public const DEFAULT_PRODUCTS = ['Roller Blinds', 'Venetian Blinds', 'Plantation Shutters', 'Curtains', 'Motorised Blinds', 'Awnings'];

    public static function getOptions(Database $db): array
    {
        $load = function(string $key, array $default) use ($db): array {
            $val = $db->fetchColumn("SELECT value FROM settings WHERE key = :k", [':k' => $key]);
            if ($val) {
                $decoded = json_decode($val, true);
                if (is_array($decoded) && !empty($decoded)) {
                    return $decoded;
                }
            }
            return $default;
        };

        return [
            'statuses'       => $load('pipeline_statuses', self::DEFAULT_STATUSES),
            'sources'        => $load('lead_sources', self::DEFAULT_SOURCES),
            'property_types' => $load('property_types', self::DEFAULT_PROPERTY_TYPES),
            'products'       => $load('products', self::DEFAULT_PRODUCTS),
        ];
    }

    public static function findById(Database $db, int $id): ?array
    {
        return $db->fetch(
            'SELECT l.*, u.name AS rep_name, c.name AS created_by_name
             FROM leads l
             LEFT JOIN users u ON l.assigned_to = u.id
             LEFT JOIN users c ON l.created_by = c.id
             WHERE l.id = :id',
            [':id' => $id]
        );
    }

    /**
     * Paginated, filtered lead listing.
     * All filters use prepared statements.
     */
    public static function list(Database $db, array $filters = [], int $page = 1, int $perPage = 20): array
    {
        $where = [];
        $params = [];

        if (!empty($filters['status'])) {
            $where[] = 'l.status = :status';
            $params[':status'] = $filters['status'];
        }
        if (!empty($filters['assigned_to'])) {
            $where[] = 'l.assigned_to = :assigned_to';
            $params[':assigned_to'] = (int)$filters['assigned_to'];
        }
        if (!empty($filters['source'])) {
            $where[] = 'l.source = :source';
            $params[':source'] = $filters['source'];
        }
        if (!empty($filters['date_from'])) {
            $where[] = 'l.created_at >= :date_from';
            $params[':date_from'] = $filters['date_from'] . ' 00:00:00';
        }
        if (!empty($filters['date_to'])) {
            $where[] = 'l.created_at <= :date_to';
            $params[':date_to'] = $filters['date_to'] . ' 23:59:59';
        }
        if (!empty($filters['search'])) {
            $where[] = '(l.customer_name LIKE :search OR l.customer_email LIKE :search2 OR l.customer_phone LIKE :search3)';
            $term = '%' . $filters['search'] . '%';
            $params[':search'] = $term;
            $params[':search2'] = $term;
            $params[':search3'] = $term;
        }

        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $total = (int)$db->fetchColumn(
            "SELECT COUNT(*) FROM leads l $whereClause",
            $params
        );

        $offset = ($page - 1) * $perPage;
        $rows = $db->fetchAll(
            "SELECT l.*, u.name AS rep_name
             FROM leads l
             LEFT JOIN users u ON l.assigned_to = u.id
             $whereClause
             ORDER BY l.created_at DESC
             LIMIT :limit OFFSET :offset",
            array_merge($params, [':limit' => $perPage, ':offset' => $offset])
        );

        return [
            'data'       => $rows,
            'total'      => $total,
            'page'       => $page,
            'per_page'   => $perPage,
            'total_pages' => (int)ceil($total / $perPage),
        ];
    }

    public static function create(Database $db, array $data): int
    {
        return $db->insert(
            'INSERT INTO leads (customer_name, customer_email, customer_phone, address, suburb, state, postcode,
             property_type, products_interested, source, notes, status, assigned_to, created_by, created_at, updated_at)
             VALUES (:name, :email, :phone, :address, :suburb, :state, :postcode,
             :property_type, :products, :source, :notes, :status, :assigned_to, :created_by, datetime("now"), datetime("now"))',
            [
                ':name'          => $data['customer_name'],
                ':email'         => $data['customer_email'] ?? '',
                ':phone'         => $data['customer_phone'] ?? '',
                ':address'       => $data['address'] ?? '',
                ':suburb'        => $data['suburb'] ?? '',
                ':state'         => $data['state'] ?? '',
                ':postcode'      => $data['postcode'] ?? '',
                ':property_type' => $data['property_type'] ?? 'residential',
                ':products'      => $data['products_interested'] ?? '',
                ':source'        => $data['source'] ?? 'phone',
                ':notes'         => $data['notes'] ?? '',
                ':status'        => 'new',
                ':assigned_to'   => !empty($data['assigned_to']) ? (int)$data['assigned_to'] : null,
                ':created_by'    => $data['created_by'] ?? null,
            ]
        );
    }

    public static function update(Database $db, int $id, array $data): void
    {
        $fields = [];
        $params = [':id' => $id];
        $allowed = [
            'customer_name', 'customer_email', 'customer_phone', 'address', 'suburb',
            'state', 'postcode', 'property_type', 'products_interested', 'source',
            'notes', 'status', 'assigned_to', 'appointment_date', 'appointment_time',
            'appointment_duration', 'quoted_amount',
        ];

        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "$field = :$field";
                $params[":$field"] = $data[$field];
            }
        }

        if (empty($fields)) return;

        $fields[] = 'updated_at = datetime("now")';
        $db->execute('UPDATE leads SET ' . implode(', ', $fields) . ' WHERE id = :id', $params);
    }

    /** Pipeline summary counts */
    public static function pipelineCounts(Database $db): array
    {
        $options = self::getOptions($db);
        $counts = array_fill_keys($options['statuses'], 0);
        $rows = $db->fetchAll('SELECT status, COUNT(*) as count FROM leads GROUP BY status');
        foreach ($rows as $row) {
            // Match case-insensitively
            foreach ($counts as $key => &$val) {
                if (strtolower($key) === strtolower($row['status'])) {
                    $val = (int)$row['count'];
                    break;
                }
            }
            unset($val);
        }
        return $counts;
    }

    /** Recent leads */
    public static function recent(Database $db, int $limit = 10): array
    {
        return $db->fetchAll(
            'SELECT l.*, u.name AS rep_name FROM leads l
             LEFT JOIN users u ON l.assigned_to = u.id
             ORDER BY l.created_at DESC LIMIT :limit',
            [':limit' => $limit]
        );
    }

    /** Source breakdown */
    public static function sourceCounts(Database $db): array
    {
        return $db->fetchAll('SELECT source, COUNT(*) as count FROM leads GROUP BY source');
    }

    /** Conversion rate */
    public static function conversionRate(Database $db): float
    {
        $total = (int)$db->fetchColumn('SELECT COUNT(*) FROM leads WHERE status IN ("won","lost")');
        if ($total === 0) return 0;
        $won = (int)$db->fetchColumn('SELECT COUNT(*) FROM leads WHERE status = "won"');
        return round(($won / $total) * 100, 1);
    }
}
