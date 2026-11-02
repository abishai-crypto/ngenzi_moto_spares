<?php
/**
 * NGENZI MOTO SPARE PARTS
 * Database Configuration
 * Dar es Salaam, Tanzania
 */

use App\Core\Env;

// Ensure Env class is loaded if not autoloaded yet
if (!class_exists('App\Core\Env')) {
    require_once __DIR__ . '/../core/Env.php';
}

Env::init();

return [
    'host'     => (string)Env::get('DB_HOST', '127.0.0.1'),
    'port'     => (string)Env::get('DB_PORT', '3307'),
    'dbname'   => (string)Env::get('DB_DATABASE', 'ngenzi_moto_spare'),
    'username' => (string)Env::get('DB_USERNAME', 'root'),
    'password' => (string)Env::get('DB_PASSWORD', ''),
    'charset'  => (string)Env::get('DB_CHARSET', 'utf8mb4'),
    'options'  => [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]
];
