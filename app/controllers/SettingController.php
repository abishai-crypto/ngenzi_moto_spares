<?php
/**
 * NGENZI MOTO SPARE PARTS
 * Settings & System Health Controller
 * Dar es Salaam, Tanzania
 */

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Env;
use App\Core\Helper;
use App\Core\Logger;
use App\Models\AuditLog;
use App\Models\Backup;
use App\Models\Setting;
use PDO;

class SettingController extends Controller {

    /**
     * Settings Dashboard / Business Profile Editor
     */
    public function index(): void {
        Auth::requirePermission('settings.view');

        $settings = Setting::allAssoc();

        $this->render('settings/index', [
            'pageTitle' => 'Mipangilio ya Mfumo & Wasifu wa Biashara',
            'settings' => $settings,
            'canEdit' => Auth::hasPermission('settings.edit')
        ]);
    }

    /**
     * Update System Settings
     */
    public function update(): void {
        Auth::requirePermission('settings.edit');
        Helper::validateCsrf();

        $allowedKeys = [
            'business_name', 'business_phone', 'business_email', 'business_address',
            'tax_number', 'target_margin', 'invoice_prefix', 'receipt_footer',
            'invoice_footer', 'default_currency', 'currency_symbol', 'low_stock_threshold',
            'dead_stock_days'
        ];

        $updates = [];
        foreach ($allowedKeys as $key) {
            if (isset($_POST[$key])) {
                $updates[$key] = trim((string)$_POST[$key]);
            }
        }

        if (!empty($updates)) {
            $userId = Auth::id();
            $oldSettings = Setting::allAssoc();

            Setting::updateMany($updates, $userId);

            AuditLog::log($userId, 'UPDATE_SETTINGS', 'SETTINGS', null, $oldSettings, $updates);
            Logger::info("System settings updated by user #{$userId}", ['keys' => array_keys($updates)]);

            $_SESSION['flash_success'] = "Mipangilio ya biashara imesasishwa kwa mafanikio!";
        } else {
            $_SESSION['flash_error'] = "Hakuna mabadiliko yaliyopokelewa.";
        }

        $this->redirect('/index.php?route=settings');
    }

    /**
     * System Health Diagnostics & Status Matrix
     */
    public function health(): void {
        Auth::requirePermission('system.health');

        // Database Diagnostics
        $dbStatus = 'OK';
        $dbVersion = 'Unknown';
        $dbLatencyMs = 0;
        try {
            $t1 = microtime(true);
            $pdo = Database::getConnection();
            $dbVersion = $pdo->query("SELECT VERSION()")->fetchColumn();
            $dbLatencyMs = round((microtime(true) - $t1) * 1000, 2);
        } catch (\Exception $e) {
            $dbStatus = 'ERROR: ' . $e->getMessage();
        }

        // Required PHP Extensions Check
        $extensionsRequired = ['pdo', 'pdo_mysql', 'mbstring', 'openssl', 'json', 'ctype'];
        $extensionsStatus = [];
        $allExtensionsOk = true;
        foreach ($extensionsRequired as $ext) {
            $loaded = extension_loaded($ext);
            $extensionsStatus[$ext] = $loaded;
            if (!$loaded) {
                $allExtensionsOk = false;
            }
        }

        // Storage Writability Check
        $rootDir = dirname(__DIR__, 2);
        $storageDir = $rootDir . '/storage';
        $backupDir = Backup::getBackupDir();
        $logDir = $rootDir . '/storage/logs';

        $storageChecks = [
            'storage_root' => ['path' => 'storage', 'writable' => is_writable($storageDir)],
            'storage_backups' => ['path' => 'storage/backups', 'writable' => is_writable($backupDir)],
            'storage_logs' => ['path' => 'storage/logs', 'writable' => is_writable($logDir)],
        ];

        // Disk space
        $freeDisk = @disk_free_space($rootDir);
        $totalDisk = @disk_total_space($rootDir);
        $diskUsagePercent = ($totalDisk && $freeDisk) ? round((($totalDisk - $freeDisk) / $totalDisk) * 100, 1) : 0;

        // Security Diagnostics
        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
        $sessionCookieParams = session_get_cookie_params();

        $securityMatrix = [
            'https' => $isHttps,
            'debug_mode' => Env::isDebug(),
            'environment' => Env::environment(),
            'session_httponly' => (bool)$sessionCookieParams['httponly'],
            'session_samesite' => $sessionCookieParams['samesite'] ?? 'Lax',
            'htaccess_storage_protected' => file_exists($storageDir . '/.htaccess'),
            'htaccess_root_protected' => file_exists($rootDir . '/.htaccess'),
        ];

        $appConfig = require dirname(__DIR__) . '/config/app.php';
        $dbConfig = require dirname(__DIR__) . '/config/database.php';

        $this->render('settings/health', [
            'pageTitle' => 'Ukaguzi wa Afya ya Mfumo (System Health Diagnostics)',
            'appVersion' => Setting::get('app_version', 'v1.0.0'),
            'environment' => Env::environment(),
            'debugMode' => Env::isDebug(),
            'timezone' => date_default_timezone_get(),
            'currentTime' => date('Y-m-d H:i:s T'),
            'phpVersion' => PHP_VERSION,
            'serverSoftware' => $_SERVER['SERVER_SOFTWARE'] ?? 'PHP CLI / Built-in Server',
            'dbStatus' => $dbStatus,
            'dbVersion' => $dbVersion,
            'dbHost' => $dbConfig['host'],
            'dbPort' => $dbConfig['port'],
            'dbDatabase' => $dbConfig['dbname'],
            'dbLatencyMs' => $dbLatencyMs,
            'extensionsStatus' => $extensionsStatus,
            'allExtensionsOk' => $allExtensionsOk,
            'storageChecks' => $storageChecks,
            'freeDiskFormatted' => $freeDisk ? Backup::formatBytes($freeDisk) : 'N/A',
            'totalDiskFormatted' => $totalDisk ? Backup::formatBytes($totalDisk) : 'N/A',
            'diskUsagePercent' => $diskUsagePercent,
            'securityMatrix' => $securityMatrix
        ]);
    }
}
