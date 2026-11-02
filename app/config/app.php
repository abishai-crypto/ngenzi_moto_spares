<?php
/**
 * NGENZI MOTO SPARE PARTS
 * Application Configuration
 * Dar es Salaam, Tanzania
 */

use App\Core\Env;

// Ensure Env class is loaded if not autoloaded yet
if (!class_exists('App\Core\Env')) {
    require_once __DIR__ . '/../core/Env.php';
}

Env::init();

return [
    'app_name'        => (string)Env::get('APP_NAME', 'NGENZI MOTO SPARE PARTS'),
    'app_env'         => (string)Env::get('APP_ENV', 'development'),
    'app_debug'       => (bool)Env::get('APP_DEBUG', true),
    'app_url'         => (string)Env::get('APP_URL', 'http://localhost:8000'),
    'timezone'        => (string)Env::get('APP_TIMEZONE', 'Africa/Dar_es_Salaam'),
    'app_tagline'     => 'Mfumo Rasmi wa Usimamizi wa Vipuri vya Pikipiki na Bajaji',
    'location'        => 'Dar es Salaam, Tanzania',
    'currency'        => (string)Env::get('CURRENCY_SYMBOL', 'TSh'),
    'currency_symbol' => (string)Env::get('CURRENCY_SYMBOL', 'TSh'),
    'currency_code'   => (string)Env::get('DEFAULT_CURRENCY', 'TZS'),
    'target_margin'   => 0.30,   // 30% Target Gross Profit Margin
    'margin_warning'  => 0.20,   // Warning threshold: between 20% and 30%
    'margin_critical' => 0.20,   // Critical threshold: below 20%
    'low_stock_threshold' => 5,
    'dead_stock_days' => 60,
    'invoice_prefix'  => 'NGZ-',
    'session_lifetime'=> 86400, // 24 hours
];
