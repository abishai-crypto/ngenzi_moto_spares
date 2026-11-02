<?php
/**
 * NGENZI MOTO SPARE PARTS
 * AuditLog Model
 * Records audit trails for sensitive business, financial, inventory, and user operations.
 */

namespace App\Models;

use App\Core\Database;
use PDO;
use Exception;

class AuditLog {

    /**
     * Log an action in the system audit trail
     */
    public static function log(?int $userId, string $action, string $module, ?int $recordId = null, $oldValues = null, $newValues = null, ?string $details = null): bool {
        try {
            $pdo = Database::getConnection();
            if ($userId !== null) {
                $uChk = $pdo->prepare("SELECT id FROM `users` WHERE id = ? LIMIT 1");
                $uChk->execute([$userId]);
                if (!$uChk->fetchColumn()) {
                    $userId = null;
                }
            }
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'CLI / System';

            $oldText = ($oldValues !== null) ? (is_string($oldValues) ? $oldValues : json_encode($oldValues, JSON_UNESCAPED_UNICODE)) : null;
            $newText = ($newValues !== null) ? (is_string($newValues) ? $newValues : json_encode($newValues, JSON_UNESCAPED_UNICODE)) : null;

            if ($details !== null) {
                $newText = ($newText !== null) ? $newText . " | Details: " . $details : $details;
            }

            $stmt = $pdo->prepare("
                INSERT INTO `audit_logs` (`user_id`, `action`, `module`, `record_id`, `old_values`, `new_values`, `ip_address`, `user_agent`)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            return $stmt->execute([
                $userId,
                $action,
                $module,
                $recordId,
                $oldText,
                $newText,
                $ipAddress,
                $userAgent
            ]);
        } catch (Exception $e) {
            error_log("AuditLog Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get recent logs
     */
    public static function getRecent(int $limit = 50): array {
        try {
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare("
                SELECT a.*, u.full_name, u.username
                FROM `audit_logs` a
                LEFT JOIN `users` u ON a.user_id = u.id
                ORDER BY a.created_at DESC
                LIMIT ?
            ");
            $stmt->bindValue(1, $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Fetch filtered audit log records for administrative inspection
     */
    public static function all(array $filters = [], int $page = 1, int $perPage = 25): array {
        try {
            $pdo = Database::getConnection();
            $offset = max(0, ($page - 1) * $perPage);
            
            $sql = "
                SELECT a.*, u.full_name, u.username, r.display_name AS role_display
                FROM `audit_logs` a
                LEFT JOIN `users` u ON a.user_id = u.id
                LEFT JOIN `roles` r ON u.role_id = r.id
                WHERE 1=1
            ";
            $params = [];

            if (!empty($filters['user_id'])) {
                $sql .= " AND a.user_id = ?";
                $params[] = (int)$filters['user_id'];
            }

            if (!empty($filters['module'])) {
                $sql .= " AND a.module = ?";
                $params[] = trim($filters['module']);
            }

            if (!empty($filters['action'])) {
                $sql .= " AND a.action = ?";
                $params[] = trim($filters['action']);
            }

            if (!empty($filters['record_id'])) {
                $sql .= " AND a.record_id = ?";
                $params[] = (int)$filters['record_id'];
            }

            if (!empty($filters['date_from'])) {
                $sql .= " AND DATE(a.created_at) >= ?";
                $params[] = trim($filters['date_from']);
            }

            if (!empty($filters['date_to'])) {
                $sql .= " AND DATE(a.created_at) <= ?";
                $params[] = trim($filters['date_to']);
            }

            if (!empty($filters['search'])) {
                $term = "%" . trim($filters['search']) . "%";
                $sql .= " AND (
                    a.action LIKE ? 
                    OR a.module LIKE ? 
                    OR a.ip_address LIKE ? 
                    OR a.new_values LIKE ? 
                    OR a.old_values LIKE ? 
                    OR u.username LIKE ? 
                    OR u.full_name LIKE ?
                )";
                $params = array_merge($params, [$term, $term, $term, $term, $term, $term, $term]);
            }

            $sql .= " ORDER BY a.id DESC LIMIT " . (int)$perPage . " OFFSET " . (int)$offset;

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log("AuditLog::all error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Count matching audit log records for pagination
     */
    public static function count(array $filters = []): int {
        try {
            $pdo = Database::getConnection();
            $sql = "
                SELECT COUNT(*) 
                FROM `audit_logs` a
                LEFT JOIN `users` u ON a.user_id = u.id
                WHERE 1=1
            ";
            $params = [];

            if (!empty($filters['user_id'])) {
                $sql .= " AND a.user_id = ?";
                $params[] = (int)$filters['user_id'];
            }

            if (!empty($filters['module'])) {
                $sql .= " AND a.module = ?";
                $params[] = trim($filters['module']);
            }

            if (!empty($filters['action'])) {
                $sql .= " AND a.action = ?";
                $params[] = trim($filters['action']);
            }

            if (!empty($filters['record_id'])) {
                $sql .= " AND a.record_id = ?";
                $params[] = (int)$filters['record_id'];
            }

            if (!empty($filters['date_from'])) {
                $sql .= " AND DATE(a.created_at) >= ?";
                $params[] = trim($filters['date_from']);
            }

            if (!empty($filters['date_to'])) {
                $sql .= " AND DATE(a.created_at) <= ?";
                $params[] = trim($filters['date_to']);
            }

            if (!empty($filters['search'])) {
                $term = "%" . trim($filters['search']) . "%";
                $sql .= " AND (
                    a.action LIKE ? 
                    OR a.module LIKE ? 
                    OR a.ip_address LIKE ? 
                    OR a.new_values LIKE ? 
                    OR a.old_values LIKE ? 
                    OR u.username LIKE ? 
                    OR u.full_name LIKE ?
                )";
                $params = array_merge($params, [$term, $term, $term, $term, $term, $term, $term]);
            }

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return (int)$stmt->fetchColumn();

        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Find single audit record by ID
     */
    public static function find(int $id): ?array {
        try {
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare("
                SELECT a.*, u.full_name, u.username, u.email, r.display_name AS role_display
                FROM `audit_logs` a
                LEFT JOIN `users` u ON a.user_id = u.id
                LEFT JOIN `roles` r ON u.role_id = r.id
                WHERE a.id = ?
                LIMIT 1
            ");
            $stmt->execute([$id]);
            $res = $stmt->fetch(PDO::FETCH_ASSOC);
            return $res ?: null;
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Get distinct list of audited modules for filter dropdown
     */
    public static function getModules(): array {
        try {
            $pdo = Database::getConnection();
            $stmt = $pdo->query("SELECT DISTINCT `module` FROM `audit_logs` WHERE `module` IS NOT NULL AND `module` != '' ORDER BY `module` ASC");
            return $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Get distinct list of audited actions for filter dropdown
     */
    public static function getActions(): array {
        try {
            $pdo = Database::getConnection();
            $stmt = $pdo->query("SELECT DISTINCT `action` FROM `audit_logs` WHERE `action` IS NOT NULL AND `action` != '' ORDER BY `action` ASC");
            return $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
        } catch (Exception $e) {
            return [];
        }
    }
}
