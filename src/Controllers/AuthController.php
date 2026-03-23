<?php
namespace App\Controllers;

use App\Auth;
use App\Database;
use App\Models\User;
use App\Middleware\RateLimiter;
use App\Middleware\CsrfMiddleware;

class AuthController
{
    private Database $db;
    private array $config;

    public function __construct(Database $db, array $config)
    {
        $this->db = $db;
        $this->config = $config;
    }

    public function loginForm(): void
    {
        if (Auth::check()) {
            header('Location: /');
            exit;
        }
        $error = $_SESSION['login_error'] ?? null;
        unset($_SESSION['login_error']);
        require __DIR__ . '/../../templates/login.php';
    }

    public function login(): void
    {
        CsrfMiddleware::enforce();

        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $ip = Auth::ip();
        $rl = $this->config['rate_limit'];

        // Rate limit check
        if (RateLimiter::isLimited($this->db, $email, $ip, $rl['login_max_attempts'], $rl['login_window'])) {
            $_SESSION['login_error'] = 'Too many login attempts. Please wait 15 minutes.';
            header('Location: /login');
            exit;
        }

        $user = User::findByEmail($this->db, $email);

        if ($user && $user['is_active'] && password_verify($password, $user['password_hash'])) {
            RateLimiter::record($this->db, $email, $ip, true);
            Auth::login($user);
            header('Location: /');
            exit;
        }

        RateLimiter::record($this->db, $email, $ip, false);
        $_SESSION['login_error'] = 'Invalid email or password.';
        header('Location: /login');
        exit;
    }

    public function logout(): void
    {
        Auth::logout();
        header('Location: /login');
        exit;
    }
}
