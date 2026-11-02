<?php
/**
 * NGENZI MOTO SPARE PARTS
 * Environment Configuration Loader (.env)
 * Dar es Salaam, Tanzania
 */

namespace App\Core;

class Env {
    private static bool $loaded = false;
    private static array $variables = [];

    /**
     * Load environment variables from .env file
     */
    public static function init(?string $filePath = null): void {
        if (self::$loaded && $filePath === null) {
            return;
        }

        $path = $filePath ?: dirname(__DIR__, 2) . '/.env';

        if (file_exists($path) && is_readable($path)) {
            $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                $line = trim($line);
                // Skip comments
                if (empty($line) || strpos($line, '#') === 0 || strpos($line, ';') === 0) {
                    continue;
                }

                // Parse KEY=VALUE
                if (strpos($line, '=') !== false) {
                    list($key, $value) = explode('=', $line, 2);
                    $key = trim($key);
                    $value = trim($value);

                    // Remove wrapping quotes if present
                    if (
                        (strpos($value, '"') === 0 && substr($value, -1) === '"') ||
                        (strpos($value, "'") === 0 && substr($value, -1) === "'")
                    ) {
                        $value = substr($value, 1, -1);
                    }

                    // Cast special types
                    $lowerVal = strtolower($value);
                    if ($lowerVal === 'true' || $lowerVal === '(true)') {
                        $value = true;
                    } elseif ($lowerVal === 'false' || $lowerVal === '(false)') {
                        $value = false;
                    } elseif ($lowerVal === 'null' || $lowerVal === '(null)') {
                        $value = null;
                    } elseif (is_numeric($value) && !preg_match('/^0[0-9]+/', $value)) {
                        $value = strpos($value, '.') !== false ? (float)$value : (int)$value;
                    }

                    self::$variables[$key] = $value;
                    $_ENV[$key] = $value;
                    if (is_scalar($value)) {
                        putenv("{$key}={$value}");
                    }
                }
            }
        }

        self::$loaded = true;
    }

    /**
     * Get an environment variable with optional default
     */
    public static function get(string $key, $default = null) {
        if (!self::$loaded) {
            self::init();
        }

        if (array_key_exists($key, self::$variables)) {
            return self::$variables[$key];
        }

        if (isset($_ENV[$key])) {
            return $_ENV[$key];
        }

        $envVal = getenv($key);
        if ($envVal !== false) {
            return $envVal;
        }

        return $default;
    }

    /**
     * Check if application is in debug mode
     */
    public static function isDebug(): bool {
        return (bool)self::get('APP_DEBUG', false);
    }

    /**
     * Get active environment name (production, development, testing)
     */
    public static function environment(): string {
        return (string)self::get('APP_ENV', 'production');
    }
}
