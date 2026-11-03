<?php
/**
 * Database configuration.
 * Defaults match a fresh XAMPP install (user "root", empty password).
 * Override with environment variables if needed.
 */
return [
    'host'     => getenv('GYM_DB_HOST') ?: '127.0.0.1',
    'port'     => getenv('GYM_DB_PORT') ?: '3306',
    'database' => getenv('GYM_DB_NAME') ?: 'gym_management',
    'username' => getenv('GYM_DB_USER') ?: 'root',
    'password' => getenv('GYM_DB_PASS') !== false ? getenv('GYM_DB_PASS') : '',
    'charset'  => 'utf8mb4',
];
