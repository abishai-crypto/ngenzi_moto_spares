<?php
/**
 * NGENZI MOTO SPARE PARTS
 * Application Logger & Production Error Handler
 * Dar es Salaam, Tanzania
 */

namespace App\Core;

use Throwable;

class Logger {
    private static ?string $logFile = null;
    private static int $maxSizeBytes = 5242880; // 5 MB

    private static array $sensitiveKeys = [
        'password', 'password_confirmation', 'pin', 'token', 'csrf_token',
        'auth_token', 'secret', 'db_password', 'credit_card', 'cvv', 'card_number'
    ];

    /**
     * Get or initialize the log file path
     */
    public static function getLogPath(): string {
        if (self::$logFile === null) {
            $dir = dirname(__DIR__, 2) . '/storage/logs';
            if (!is_dir($dir)) {
                @mkdir($dir, 0750, true);
            }
            self::$logFile = $dir . '/app.log';
        }
        return self::$logFile;
    }

    /**
     * Log a message with error level and context
     */
    public static function log(string $level, string $message, array $context = []): void {
        $logPath = self::getLogPath();
        self::rotateIfNeeded($logPath);

        $sanitizedContext = self::sanitizeContext($context);

        $timestamp = date('Y-m-d H:i:s');
        $userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 'guest';
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $uri = $_SERVER['REQUEST_URI'] ?? 'CLI';
        $method = $_SERVER['REQUEST_METHOD'] ?? 'CLI';

        $contextStr = !empty($sanitizedContext) ? ' | Context: ' . json_encode($sanitizedContext, JSON_UNESCAPED_SLASHES) : '';
        $entry = sprintf(
            "[%s] [%s] [User:%s] [%s %s] [IP:%s] %s%s\n",
            $timestamp,
            strtoupper($level),
            $userId,
            $method,
            $uri,
            $ip,
            $message,
            $contextStr
        );

        @file_put_contents($logPath, $entry, FILE_APPEND | LOCK_EX);
    }

    public static function info(string $message, array $context = []): void {
        self::log('INFO', $message, $context);
    }

    public static function warning(string $message, array $context = []): void {
        self::log('WARNING', $message, $context);
    }

    public static function error(string $message, array $context = []): void {
        self::log('ERROR', $message, $context);
    }

    public static function critical(string $message, array $context = []): void {
        self::log('CRITICAL', $message, $context);
    }

    /**
     * Sanitize array to strip out sensitive passwords, tokens and secrets
     */
    public static function sanitizeContext(array $context): array {
        $clean = [];
        foreach ($context as $k => $v) {
            $kLower = strtolower((string)$k);
            $isSensitive = false;
            foreach (self::$sensitiveKeys as $sk) {
                if (strpos($kLower, $sk) !== false) {
                    $isSensitive = true;
                    break;
                }
            }

            if ($isSensitive) {
                $clean[$k] = '***REDACTED***';
            } elseif (is_array($v)) {
                $clean[$k] = self::sanitizeContext($v);
            } elseif (is_object($v)) {
                $clean[$k] = '[Object ' . get_class($v) . ']';
            } else {
                $clean[$k] = $v;
            }
        }
        return $clean;
    }

    /**
     * Check if log file exceeds limit and rotate
     */
    private static function rotateIfNeeded(string $filePath): void {
        if (file_exists($filePath) && @filesize($filePath) > self::$maxSizeBytes) {
            $rotatedPath = $filePath . '.' . date('Ymd_His') . '.bak';
            @rename($filePath, $rotatedPath);
        }
    }

    /**
     * Register global PHP error and exception handlers
     */
    public static function registerHandlers(): void {
        // PHP Error handler
        set_error_handler(function ($severity, $message, $file, $line) {
            if (!(error_reporting() & $severity)) {
                return false;
            }

            $level = 'ERROR';
            if ($severity === E_WARNING || $severity === E_USER_WARNING) {
                $level = 'WARNING';
            } elseif ($severity === E_NOTICE || $severity === E_USER_NOTICE) {
                $level = 'NOTICE';
            }

            self::log($level, $message, [
                'file' => basename($file),
                'line' => $line
            ]);

            // In production, suppress display of notices/warnings to user
            if (!Env::isDebug()) {
                return true;
            }

            return false;
        });

        // PHP Uncaught Exception handler
        set_exception_handler(function (Throwable $e) {
            self::critical('Uncaught Exception: ' . $e->getMessage(), [
                'code' => $e->getCode(),
                'file' => basename($e->getFile()),
                'line' => $e->getLine(),
                'trace' => Env::isDebug() ? $e->getTraceAsString() : '***Suppressed in Production***'
            ]);

            if (php_sapi_name() === 'cli') {
                echo "\n[ERROR] " . $e->getMessage() . "\n";
                exit(1);
            }

            http_response_code(500);

            if (Env::isDebug()) {
                // Detailed debug display
                echo "<div style='font-family:sans-serif;padding:30px;max-width:800px;margin:40px auto;border-radius:8px;background:#fff5f5;border:1px solid #fed7d7;color:#9b2c2c;'>";
                echo "<h2 style='margin-top:0;'>Developer Exception Notice (DEBUG MODE ACTIVE)</h2>";
                echo "<p><strong>Message:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
                echo "<p><strong>File:</strong> " . htmlspecialchars($e->getFile()) . " (Line: " . $e->getLine() . ")</p>";
                echo "<h4>Stack Trace:</h4>";
                echo "<pre style='background:#2d3748;color:#f7fafc;padding:15px;border-radius:6px;overflow:auto;font-size:13px;'>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
                echo "</div>";
            } else {
                // User-friendly production error display (No secrets, no SQL, no paths)
                echo "<!DOCTYPE html>
<html lang='sw'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Hitilafu ya Mfumo - Ngenzi Moto Spare Parts</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
    <link rel='stylesheet' href='https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css'>
    <style>
        body { background: #0f172a; color: #f8fafc; font-family: system-ui, -apple-system, sans-serif; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; padding: 20px; }
        .error-card { background: #1e293b; border: 1px solid rgba(255,255,255,0.1); border-radius: 16px; padding: 40px; max-width: 540px; text-align: center; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.5); }
        .icon-box { width: 72px; height: 72px; border-radius: 50%; background: rgba(239, 68, 68, 0.15); color: #ef4444; display: inline-flex; align-items: center; justify-content: center; font-size: 32px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class='error-card'>
        <div class='icon-box'><i class='bi bi-exclamation-triangle-fill'></i></div>
        <h3 class='fw-bold mb-2'>Hitilafu ya Muda ya Mfumo</h3>
        <p class='text-secondary mb-4'>Samahani, ombi lako halijaweza kukamilika kwa sasa. Mfumo umerekodi hitilafu hii kwa usalama kwa ajili ya uchunguzi wa kiufundi.</p>
        <div class='d-flex gap-2 justify-content-center'>
            <a href='/index.php?route=dashboard' class='btn btn-primary px-4'>Rudi Mwanzo</a>
            <button onclick='window.history.back()' class='btn btn-outline-secondary px-4'>Rudi Nyuma</button>
        </div>
        <div class='mt-4 pt-3 border-top border-secondary-subtle text-secondary small'>
            Ngenzi Moto Spare Parts &copy; " . date('Y') . " | Dar es Salaam
        </div>
    </div>
</body>
</html>";
            }
            exit;
        });
    }
}
