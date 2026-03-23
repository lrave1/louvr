<?php
namespace App\Controllers;

use App\Auth;
use App\Database;
use App\Models\User;
use App\Middleware\CsrfMiddleware;

class RepController
{
    private Database $db;

    public function __construct(Database $db, array $config)
    {
        $this->db = $db;
    }

    public function index(): void
    {
        Auth::requireAdmin();
        $users = User::all($this->db);
        // Attach stats to each user
        foreach ($users as &$user) {
            $user = array_merge($user, User::repStats($this->db, $user['id']));
        }
        $errors = $_SESSION['rep_errors'] ?? [];
        unset($_SESSION['rep_errors']);

        require __DIR__ . '/../../templates/reps/index.php';
    }

    public function store(): void
    {
        Auth::requireAdmin();
        CsrfMiddleware::enforce();

        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $role = $_POST['role'] ?? 'rep';
        $password = $_POST['password'] ?? '';

        $errors = [];
        if ($name === '') $errors[] = 'Name is required.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required.';
        if (strlen($password) < 8) $errors[] = 'Password must be at least 8 characters.';
        if (!in_array($role, ['admin', 'rep'])) $errors[] = 'Invalid role.';
        if (User::findByEmail($this->db, $email)) $errors[] = 'Email already exists.';

        if ($errors) {
            $_SESSION['rep_errors'] = $errors;
            header('Location: /reps');
            exit;
        }

        User::create($this->db, [
            'name'     => $name,
            'email'    => $email,
            'phone'    => $phone,
            'role'     => $role,
            'password' => $password,
        ]);

        $_SESSION['toast'] = ['type' => 'success', 'message' => 'User created successfully.'];
        header('Location: /reps');
        exit;
    }

    public function update(array $params): void
    {
        Auth::requireAdmin();
        CsrfMiddleware::enforce();

        $id = (int)$params['id'];
        $action = $_POST['action'] ?? 'update';

        if ($action === 'toggle_active') {
            $user = User::findById($this->db, $id);
            if ($user) {
                User::update($this->db, $id, ['is_active' => $user['is_active'] ? 0 : 1]);
                $status = $user['is_active'] ? 'deactivated' : 'activated';
                $_SESSION['toast'] = ['type' => 'success', 'message' => "User $status."];
            }
        } elseif ($action === 'update') {
            $data = [];
            if (!empty($_POST['name'])) $data['name'] = trim($_POST['name']);
            if (!empty($_POST['email'])) $data['email'] = trim($_POST['email']);
            if (isset($_POST['phone'])) $data['phone'] = trim($_POST['phone']);
            if (!empty($_POST['role']) && in_array($_POST['role'], ['admin', 'rep'])) {
                $data['role'] = $_POST['role'];
            }
            if (!empty($_POST['password']) && strlen($_POST['password']) >= 8) {
                $data['password'] = $_POST['password'];
            }
            if (!empty($data)) {
                User::update($this->db, $id, $data);
                $_SESSION['toast'] = ['type' => 'success', 'message' => 'User updated.'];
            }
        } elseif ($action === 'regenerate_api_key') {
            User::regenerateApiKey($this->db, $id);
            $_SESSION['toast'] = ['type' => 'success', 'message' => 'API key regenerated.'];
        }

        header('Location: /reps');
        exit;
    }
}
