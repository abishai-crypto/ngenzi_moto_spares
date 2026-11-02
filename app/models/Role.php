<?php
/**
 * NGENZI MOTO SPARE PARTS
 * Role Model
 */

namespace App\Models;

use App\Core\Database;
use App\Core\Helper;
use PDO;

class Role {
    public static function all(): array {
        $pdo = Database::getConnection();
        return $pdo->query("SELECT * FROM `roles` ORDER BY id ASC")->fetchAll();
    }

    public static function find(int $id): ?array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM `roles` WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function findByName(string $name): ?array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM `roles` WHERE name = ? LIMIT 1");
        $stmt->execute([$name]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Get permission IDs assigned to this role
     */
    public static function getPermissionIds(int $roleId): array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT permission_id FROM `role_permissions` WHERE role_id = ?");
        $stmt->execute([$roleId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Update permissions for a role (Permission Matrix)
     */
    public static function syncPermissions(int $roleId, array $permissionIds): bool {
        $pdo = Database::getConnection();
        $pdo->beginTransaction();
        try {
            $role = self::find($roleId);
            $oldPerms = self::getPermissionIds($roleId);

            // Delete old permissions
            $del = $pdo->prepare("DELETE FROM `role_permissions` WHERE role_id = ?");
            $del->execute([$roleId]);

            // Insert new permissions
            $ins = $pdo->prepare("INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (?, ?)");
            foreach ($permissionIds as $pid) {
                $ins->execute([$roleId, (int)$pid]);
            }

            $pdo->commit();

            Helper::logAudit('PERMISSION_CHANGED', 'ROLES', $roleId, $oldPerms, [
                'role_name'       => $role['name'] ?? '',
                'new_permissions' => $permissionIds
            ]);

            return true;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
