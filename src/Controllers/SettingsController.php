<?php
namespace App\Controllers;

use App\Auth;
use App\Database;
use App\Middleware\CsrfMiddleware;

class SettingsController
{
    private Database $db;

    public function __construct(Database $db, array $config)
    {
        $this->db = $db;
    }

    public function index(): void
    {
        Auth::requireAdmin();

        // Load settings from DB
        $settings = [];
        $rows = $this->db->fetchAll('SELECT key, value FROM settings');
        foreach ($rows as $row) {
            $settings[$row['key']] = $row['value'];
        }

        require __DIR__ . '/../../templates/settings.php';
    }

    public function update(): void
    {
        Auth::requireAdmin();
        CsrfMiddleware::enforce();

        $keys = ['company_name', 'company_phone', 'company_email', 'default_appointment_duration'];
        foreach ($keys as $key) {
            $value = trim($_POST[$key] ?? '');
            $this->db->execute(
                'INSERT INTO settings (key, value) VALUES (:key, :value)
                 ON CONFLICT(key) DO UPDATE SET value = :value2',
                [':key' => $key, ':value' => $value, ':value2' => $value]
            );
        }

        $_SESSION['toast'] = ['type' => 'success', 'message' => 'Settings saved.'];
        header('Location: /settings');
        exit;
    }
}
