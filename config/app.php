<?php
/**
 * Application configuration.
 * Environment variables override defaults for production deployment.
 */
return [
    'app_name'    => 'Louvr',
    'app_url'     => getenv('APP_URL') ?: 'http://localhost:8080',
    'debug'       => (bool)(getenv('APP_DEBUG') ?: true),

    // Database - SQLite for local dev, swap DSN for MySQL on Azure
    'db' => [
        'driver' => getenv('DB_DRIVER') ?: 'sqlite',
        'path'   => getenv('DB_PATH') ?: __DIR__ . '/../data/louvr.db',
        'host'   => getenv('DB_HOST') ?: '127.0.0.1',
        'name'   => getenv('DB_NAME') ?: 'louvr',
        'user'   => getenv('DB_USER') ?: '',
        'pass'   => getenv('DB_PASS') ?: '',
    ],

    // Session
    'session' => [
        'lifetime' => 7200, // 2 hours
        'name'     => 'louvr_session',
    ],

    // Rate limiting
    'rate_limit' => [
        'login_max_attempts' => 5,
        'login_window'       => 900, // 15 minutes
    ],

    // Pagination
    'per_page' => 20,
];
