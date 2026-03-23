<?php
namespace App;

/**
 * Database schema creation and seeding.
 * Called on first run to initialise the database.
 */
class Schema
{
    public static function migrate(Database $db): void
    {
        $pdo = $db->pdo();

        $pdo->exec('CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            email TEXT NOT NULL UNIQUE,
            phone TEXT DEFAULT "",
            role TEXT NOT NULL DEFAULT "rep" CHECK(role IN ("admin","rep")),
            password_hash TEXT NOT NULL,
            api_key TEXT UNIQUE,
            is_active INTEGER NOT NULL DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )');

        $pdo->exec('CREATE TABLE IF NOT EXISTS leads (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            customer_name TEXT NOT NULL,
            customer_email TEXT DEFAULT "",
            customer_phone TEXT DEFAULT "",
            address TEXT DEFAULT "",
            suburb TEXT DEFAULT "",
            state TEXT DEFAULT "",
            postcode TEXT DEFAULT "",
            property_type TEXT DEFAULT "residential" CHECK(property_type IN ("residential","commercial")),
            products_interested TEXT DEFAULT "",
            source TEXT DEFAULT "phone" CHECK(source IN ("web_form","phone","referral","other")),
            notes TEXT DEFAULT "",
            status TEXT DEFAULT "new" CHECK(status IN ("new","assigned","booked","quoted","won","lost")),
            assigned_to INTEGER REFERENCES users(id) ON DELETE SET NULL,
            appointment_date TEXT,
            appointment_time TEXT,
            appointment_duration INTEGER DEFAULT 60,
            quoted_amount REAL,
            created_by INTEGER REFERENCES users(id) ON DELETE SET NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )');

        $pdo->exec('CREATE TABLE IF NOT EXISTS lead_history (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            lead_id INTEGER NOT NULL REFERENCES leads(id) ON DELETE CASCADE,
            user_id INTEGER REFERENCES users(id) ON DELETE SET NULL,
            action TEXT NOT NULL,
            old_value TEXT,
            new_value TEXT,
            note TEXT,
            ip_address TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )');

        $pdo->exec('CREATE TABLE IF NOT EXISTS login_attempts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            email TEXT NOT NULL,
            ip_address TEXT NOT NULL,
            attempted_at DATETIME NOT NULL,
            successful INTEGER NOT NULL DEFAULT 0
        )');

        $pdo->exec('CREATE TABLE IF NOT EXISTS settings (
            key TEXT PRIMARY KEY,
            value TEXT NOT NULL DEFAULT ""
        )');

        // Migration: remove CHECK constraints to allow configurable values
        self::migrateRemoveCheckConstraints($pdo);

        // Indexes for performance
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_leads_status ON leads(status)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_leads_assigned ON leads(assigned_to)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_leads_created ON leads(created_at)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_lead_history_lead ON lead_history(lead_id)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_login_attempts_email ON login_attempts(email, attempted_at)');
    }

    private static function migrateRemoveCheckConstraints(\PDO $pdo): void
    {
        // Check if migration already applied
        $result = $pdo->query("SELECT sql FROM sqlite_master WHERE type='table' AND name='leads'");
        $sql = $result->fetchColumn();
        if ($sql && !str_contains($sql, 'CHECK')) {
            return; // Already migrated
        }
        if (!$sql) {
            return; // Table doesn't exist yet (fresh install handled above)
        }

        // Recreate leads table without CHECK constraints
        $pdo->exec('BEGIN TRANSACTION');
        try {
            $pdo->exec('ALTER TABLE leads RENAME TO leads_old');
            $pdo->exec('CREATE TABLE leads (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                customer_name TEXT NOT NULL,
                customer_email TEXT DEFAULT "",
                customer_phone TEXT DEFAULT "",
                address TEXT DEFAULT "",
                suburb TEXT DEFAULT "",
                state TEXT DEFAULT "",
                postcode TEXT DEFAULT "",
                property_type TEXT DEFAULT "Residential",
                products_interested TEXT DEFAULT "",
                source TEXT DEFAULT "Phone",
                notes TEXT DEFAULT "",
                status TEXT DEFAULT "New",
                assigned_to INTEGER REFERENCES users(id) ON DELETE SET NULL,
                appointment_date TEXT,
                appointment_time TEXT,
                appointment_duration INTEGER DEFAULT 60,
                quoted_amount REAL,
                created_by INTEGER REFERENCES users(id) ON DELETE SET NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )');
            $pdo->exec('INSERT INTO leads SELECT * FROM leads_old');
            $pdo->exec('DROP TABLE leads_old');
            $pdo->exec('COMMIT');
        } catch (\Exception $e) {
            $pdo->exec('ROLLBACK');
        }
    }

    public static function seed(Database $db): void
    {
        // Only seed if no users exist
        if ($db->fetchColumn('SELECT COUNT(*) FROM users') > 0) {
            return;
        }

        // Admin user
        $adminId = $db->insert(
            'INSERT INTO users (name, email, phone, role, password_hash, api_key, is_active, created_at, updated_at)
             VALUES (:name, :email, :phone, :role, :hash, :key, 1, datetime("now"), datetime("now"))',
            [
                ':name'  => 'Admin',
                ':email' => 'admin@louvr.app',
                ':phone' => '0400 000 001',
                ':role'  => 'admin',
                ':hash'  => password_hash('LouvR2026!', PASSWORD_BCRYPT),
                ':key'   => bin2hex(random_bytes(32)),
            ]
        );

        // Sample reps
        $reps = [
            ['Sarah Mitchell', 'sarah@ublinds.com.au', '0412 345 678'],
            ['James Cooper',   'james@ublinds.com.au', '0423 456 789'],
            ['Emily Watson',   'emily@ublinds.com.au', '0434 567 890'],
        ];
        $repIds = [];
        foreach ($reps as $rep) {
            $repIds[] = $db->insert(
                'INSERT INTO users (name, email, phone, role, password_hash, api_key, is_active, created_at, updated_at)
                 VALUES (:name, :email, :phone, :role, :hash, :key, 1, datetime("now"), datetime("now"))',
                [
                    ':name'  => $rep[0],
                    ':email' => $rep[1],
                    ':phone' => $rep[2],
                    ':role'  => 'rep',
                    ':hash'  => password_hash('LouvR2026!', PASSWORD_BCRYPT),
                    ':key'   => bin2hex(random_bytes(32)),
                ]
            );
        }

        // Sample leads across different statuses
        $leads = [
            [
                'name' => 'Michael Thompson', 'email' => 'michael.t@gmail.com', 'phone' => '0411 222 333',
                'address' => '42 Harbour View Rd', 'suburb' => 'Mosman', 'state' => 'NSW', 'postcode' => '2088',
                'type' => 'residential', 'products' => 'Roller blinds, curtains', 'source' => 'web_form',
                'status' => 'new', 'rep' => null, 'notes' => 'Interested in blockout blinds for bedrooms.',
            ],
            [
                'name' => 'Jennifer Lee', 'email' => 'jlee@outlook.com', 'phone' => '0422 333 444',
                'address' => '8/120 Collins St', 'suburb' => 'Melbourne', 'state' => 'VIC', 'postcode' => '3000',
                'type' => 'commercial', 'products' => 'Venetian blinds, shutters', 'source' => 'phone',
                'status' => 'assigned', 'rep' => $repIds[0], 'notes' => 'Office fitout, 15 windows.',
            ],
            [
                'name' => 'David & Karen Wilson', 'email' => 'wilsons@yahoo.com.au', 'phone' => '0433 444 555',
                'address' => '7 Palm Ave', 'suburb' => 'Surfers Paradise', 'state' => 'QLD', 'postcode' => '4217',
                'type' => 'residential', 'products' => 'Plantation shutters', 'source' => 'referral',
                'status' => 'booked', 'rep' => $repIds[1],
                'notes' => 'Referred by the Johnsons. Whole house shutters.',
                'appt_date' => date('Y-m-d', strtotime('+3 days')), 'appt_time' => '10:00',
            ],
            [
                'name' => 'Rachel Green', 'email' => 'rachel.g@gmail.com', 'phone' => '0444 555 666',
                'address' => '15 King William St', 'suburb' => 'Adelaide', 'state' => 'SA', 'postcode' => '5000',
                'type' => 'residential', 'products' => 'Motorised blinds, awnings', 'source' => 'web_form',
                'status' => 'quoted', 'rep' => $repIds[0], 'quoted_amount' => 4850.00,
                'notes' => 'Wants smart home integration.',
            ],
            [
                'name' => 'Tom Bradley Constructions', 'email' => 'tom@bradleycon.com.au', 'phone' => '0455 666 777',
                'address' => '200 George St', 'suburb' => 'Sydney', 'state' => 'NSW', 'postcode' => '2000',
                'type' => 'commercial', 'products' => 'Roller blinds, vertical blinds', 'source' => 'phone',
                'status' => 'won', 'rep' => $repIds[2], 'quoted_amount' => 12200.00,
                'notes' => 'New apartment block, 40 units. Repeat client.',
            ],
        ];

        foreach ($leads as $l) {
            $leadId = $db->insert(
                'INSERT INTO leads (customer_name, customer_email, customer_phone, address, suburb, state, postcode,
                 property_type, products_interested, source, notes, status, assigned_to, quoted_amount,
                 appointment_date, appointment_time, created_by, created_at, updated_at)
                 VALUES (:name, :email, :phone, :addr, :suburb, :state, :post, :type, :prod, :src, :notes,
                 :status, :rep, :quote, :adate, :atime, :by, datetime("now", "-" || :days || " days"), datetime("now"))',
                [
                    ':name'   => $l['name'],
                    ':email'  => $l['email'],
                    ':phone'  => $l['phone'],
                    ':addr'   => $l['address'],
                    ':suburb' => $l['suburb'],
                    ':state'  => $l['state'],
                    ':post'   => $l['postcode'],
                    ':type'   => $l['type'],
                    ':prod'   => $l['products'],
                    ':src'    => $l['source'],
                    ':notes'  => $l['notes'],
                    ':status' => $l['status'],
                    ':rep'    => $l['rep'],
                    ':quote'  => $l['quoted_amount'] ?? null,
                    ':adate'  => $l['appt_date'] ?? null,
                    ':atime'  => $l['appt_time'] ?? null,
                    ':by'     => $adminId,
                    ':days'   => rand(1, 14),
                ]
            );

            // Add creation history
            $db->insert(
                'INSERT INTO lead_history (lead_id, user_id, action, note, ip_address, created_at)
                 VALUES (:id, :uid, :action, :note, :ip, datetime("now"))',
                [':id' => $leadId, ':uid' => $adminId, ':action' => 'created', ':note' => 'Lead created', ':ip' => '127.0.0.1']
            );
        }

        // Default settings
        $db->execute("INSERT OR IGNORE INTO settings (key, value) VALUES ('company_name', 'UBlinds')");
        $db->execute("INSERT OR IGNORE INTO settings (key, value) VALUES ('company_phone', '')");
        $db->execute("INSERT OR IGNORE INTO settings (key, value) VALUES ('company_email', 'info@ublinds.com.au')");
        $db->execute("INSERT OR IGNORE INTO settings (key, value) VALUES ('default_appointment_duration', '60')");

        // Configurable options (JSON arrays)
        $db->execute("INSERT OR IGNORE INTO settings (key, value) VALUES ('lead_sources', '[\"Web Form\",\"Phone\",\"Referral\",\"Walk-in\",\"Social Media\",\"Other\"]')");
        $db->execute("INSERT OR IGNORE INTO settings (key, value) VALUES ('property_types', '[\"Residential\",\"Commercial\",\"Strata\",\"New Build\"]')");
        $db->execute("INSERT OR IGNORE INTO settings (key, value) VALUES ('products', '[\"Roller Blinds\",\"Venetian Blinds\",\"Vertical Blinds\",\"Plantation Shutters\",\"Curtains\",\"Motorised Blinds\",\"Awnings\",\"Shutters\",\"Other\"]')");
        $db->execute("INSERT OR IGNORE INTO settings (key, value) VALUES ('pipeline_statuses', '[\"New\",\"Assigned\",\"Booked\",\"Quoted\",\"Won\",\"Lost\"]')");
    }
}
