<?php
/**
 * Louvr - Lead Management System
 * Single entry point. All requests route through here.
 */

// PHP built-in server: serve static files directly
if (php_sapi_name() === 'cli-server') {
    $file = __DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if (is_file($file)) {
        return false;
    }
}

// Autoloader - maps App\ namespace to /src/
spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }
    $relativeClass = substr($class, strlen($prefix));
    $file = __DIR__ . '/../src/' . str_replace('\\', '/', $relativeClass) . '.php';
    if (file_exists($file)) {
        require $file;
    }
});

use App\Auth;
use App\Database;
use App\Router;
use App\Schema;
use App\Middleware\CsrfMiddleware;

// Load config
$config = require __DIR__ . '/../config/app.php';

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.tailwindcss.com; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self';");

// Database init
$db = Database::getInstance($config['db']);
Schema::migrate($db);
Schema::seed($db);

// Start session (skip for API routes)
$uri = strtok($_SERVER['REQUEST_URI'], '?');
$isApi = str_starts_with($uri, '/api/');

if (!$isApi) {
    Auth::startSession($config['session']);
    CsrfMiddleware::generateToken();
}

// Build routes
$router = new Router();

// Auth
$router->get('/login', 'App\\Controllers\\AuthController', 'loginForm');
$router->post('/login', 'App\\Controllers\\AuthController', 'login');
$router->get('/logout', 'App\\Controllers\\AuthController', 'logout');

// Dashboard
$router->get('/', 'App\\Controllers\\DashboardController', 'index');

// Leads
$router->get('/leads', 'App\\Controllers\\LeadController', 'index');
$router->get('/leads/create', 'App\\Controllers\\LeadController', 'create');
$router->post('/leads', 'App\\Controllers\\LeadController', 'store');
$router->get('/leads/{id}', 'App\\Controllers\\LeadController', 'show');
$router->post('/leads/{id}', 'App\\Controllers\\LeadController', 'update');

// Reps (admin)
$router->get('/reps', 'App\\Controllers\\RepController', 'index');
$router->post('/reps', 'App\\Controllers\\RepController', 'store');
$router->post('/reps/{id}', 'App\\Controllers\\RepController', 'update');

// Settings (admin)
$router->get('/settings', 'App\\Controllers\\SettingsController', 'index');
$router->post('/settings', 'App\\Controllers\\SettingsController', 'update');

// API
$router->post('/api/leads', 'App\\Controllers\\ApiController', 'createLead');

// Dispatch
$method = $_SERVER['REQUEST_METHOD'];
$match = $router->dispatch($method, $uri);

if ($match) {
    [$controllerClass, $action, $params] = $match;
    $controller = new $controllerClass($db, $config);
    $controller->$action($params ?? []);
} else {
    http_response_code(404);
    if ($isApi) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Not found']);
    } else {
        echo '<!DOCTYPE html><html><body style="background:#0a0a0f;color:#888;font-family:sans-serif;display:flex;align-items:center;justify-content:center;height:100vh;margin:0"><div style="text-align:center"><h1 style="font-size:4rem;color:#fff;margin:0">404</h1><p>Page not found</p><a href="/" style="color:#3b82f6">Go to Dashboard</a></div></body></html>';
    }
}
