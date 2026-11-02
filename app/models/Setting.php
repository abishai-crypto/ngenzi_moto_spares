<?php
/**
 * NGENZI MOTO SPARE PARTS
 * Setting Model
 * Dar es Salaam, Tanzania
 */

namespace App\Models;

use App\Core\Database;
use PDO;

class Setting {
    private static array $cache = [];

    /**
     * Get a setting by key with optional default
     */
    public static function get(string $key, ?string $default = null): ?string {
        if (isset(self::$cache[$key])) {
            return self::$cache[$key];
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT setting_value FROM `settings` WHERE setting_key = ? LIMIT 1");
        $stmt->execute([$key]);
        $val = $stmt->fetchColumn();

        if ($val !== false) {
            self::$cache[$key] = $val;
            return $val;
        }

        return $default;
    }

    /**
     * Set/update a setting value
     */
    public static function set(string $key, ?string $value, ?int $userId = null, ?string $description = null): bool {
        $pdo = Database::getConnection();

        // Check if exists
        $stmtCheck = $pdo->prepare("SELECT id FROM `settings` WHERE setting_key = ?");
        $stmtCheck->execute([$key]);
        $exists = $stmtCheck->fetchColumn();

        if ($exists) {
            $stmtUpdate = $pdo->prepare("
                UPDATE `settings` 
                SET setting_value = ?, updated_by = ?, updated_at = NOW() 
                WHERE setting_key = ?
            ");
            $success = $stmtUpdate->execute([$value ?? '', $userId, $key]);
        } else {
            $stmtInsert = $pdo->prepare("
                INSERT INTO `settings` (setting_key, setting_value, description, updated_by) 
                VALUES (?, ?, ?, ?)
            ");
            $success = $stmtInsert->execute([$key, $value ?? '', $description, $userId]);
        }

        if ($success) {
            self::$cache[$key] = $value;
        }

        return $success;
    }

    /**
     * Update multiple settings in one batch
     */
    public static function updateMany(array $settings, ?int $userId = null): int {
        $updated = 0;
        foreach ($settings as $key => $val) {
            if (self::set($key, (string)$val, $userId)) {
                $updated++;
            }
        }
        return $updated;
    }

    /**
     * Get all settings as raw rows
     */
    public static function all(): array {
        $pdo = Database::getConnection();
        return $pdo->query("SELECT * FROM `settings` ORDER BY setting_key ASC")->fetchAll();
    }

    /**
     * Get all settings as an associative key-value map
     */
    public static function allAssoc(): array {
        $pdo = Database::getConnection();
        $rows = $pdo->query("SELECT setting_key, setting_value FROM `settings`")->fetchAll(PDO::FETCH_KEY_PAIR);
        return $rows ?: [];
    }

    /**
     * Clear in-memory cache
     */
    public static function clearCache(): void {
        self::$cache = [];
    }
}
