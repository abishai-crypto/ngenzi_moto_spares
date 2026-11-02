<?php
/**
 * NGENZI MOTO SPARE PARTS
 * Enhanced User Model (Phase 3)
 */

namespace App\Models;

use App\Core\Database;
use App\Core\Helper;
use PDO;
use Exception;

class User {
    /**
     * Fetch users with optional search and filters
     */
    public static function all(?string $search = null, ?string $role = null, ?string $status = null): array {
        $pdo = Database::getConnection();
        $sql = "
            SELECT u.id, u.role_id, u.username, u.full_name, u.email, u.phone, u.status, u.last_login, u.created_at,
                   r.name as role_name, r.display_name as role_display
            FROM `users` u
            JOIN `roles` r ON u.role_id = r.id
            WHERE 1=1
        ";
        $params = [];

        if (!empty($search)) {
            $sql .= " AND (u.full_name LIKE ? OR u.username LIKE ? OR u.phone LIKE ? OR u.email LIKE ?)";
            $term = "%{$search}%";
            $params = array_merge($params, [$term, $term, $term, $term]);
        }

        if (!empty($role)) {
            $sql .= " AND r.name = ?";
            $params[] = $role;
        }

        if (!empty($status)) {
            $sql .= " AND u.status = ?";
            $params[] = $status;
        }

        $sql .= " ORDER BY u.id ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Find user by ID with role details
     */
    public static function find(int $id): ?array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT u.*, r.name as role_name, r.display_name as role_display
            FROM `users` u
            JOIN `roles` r ON u.role_id = r.id
            WHERE u.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Find user by username or email
     */
    public static function findByUsername(string $username): ?array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT u.*, r.name as role_name, r.display_name as role_display
            FROM `users` u
            JOIN `roles` r ON u.role_id = r.id
            WHERE u.username = ? OR u.email = ?
            LIMIT 1
        ");
        $stmt->execute([$username, $username]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Create a new user account
     */
    public static function create(array $data): int {
        $pdo = Database::getConnection();

        // Validation: Unique username
        if (self::findByUsername($data['username'])) {
            throw new Exception("Jina la mtumiaji '{$data['username']}' tayari linatumika. Chagua jingine.");
        }

        // Validate password confirmation
        if (strlen($data['password']) < 6) {
            throw new Exception("Nenosiri lazima liwe na herufi zisizopungua 6.");
        }

        $hashedPassword = password_hash($data['password'], PASSWORD_BCRYPT);
        $status = $data['status'] ?? 'active';

        $stmt = $pdo->prepare("
            INSERT INTO `users` (`role_id`, `username`, `full_name`, `email`, `phone`, `password`, `status`)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $data['role_id'],
            $data['username'],
            $data['full_name'],
            !empty($data['email']) ? $data['email'] : null,
            !empty($data['phone']) ? $data['phone'] : null,
            $hashedPassword,
            $status
        ]);

        $newUserId = (int)$pdo->lastInsertId();

        Helper::logAudit('USER_CREATED', 'USERS', $newUserId, null, [
            'username'  => $data['username'],
            'full_name' => $data['full_name'],
            'role_id'   => $data['role_id'],
            'status'    => $status
        ]);

        return $newUserId;
    }

    /**
     * Update user details and role with safeguard for owner
     */
    public static function update(int $id, array $data): bool {
        $pdo = Database::getConnection();
        $existing = self::find($id);
        if (!$existing) {
            throw new Exception("Mtumiaji hakupatikana.");
        }

        // Safeguard: Protect last active owner role
        if ($existing['role_name'] === 'super_admin' && (int)$data['role_id'] !== (int)$existing['role_id']) {
            if (self::countActiveOwners() <= 1) {
                throw new Exception("Huwezi kubadilisha jukumu la Mmiliki pekee aliyebaki kwenye mfumo!");
            }
        }

        // Check if email already used by another user
        if (!empty($data['email'])) {
            $checkEmail = $pdo->prepare("SELECT id FROM `users` WHERE email = ? AND id != ?");
            $checkEmail->execute([$data['email'], $id]);
            if ($checkEmail->fetch()) {
                throw new Exception("Barua pepe '{$data['email']}' inatumiwa tayari na mtumiaji mwingine.");
            }
        }

        $stmt = $pdo->prepare("
            UPDATE `users`
            SET `role_id` = ?, `full_name` = ?, `email` = ?, `phone` = ?, `status` = ?
            WHERE `id` = ?
        ");
        $success = $stmt->execute([
            $data['role_id'],
            $data['full_name'],
            !empty($data['email']) ? $data['email'] : null,
            !empty($data['phone']) ? $data['phone'] : null,
            $data['status'],
            $id
        ]);

        if ($success) {
            // Check if role changed
            if ((int)$existing['role_id'] !== (int)$data['role_id']) {
                Helper::logAudit('ROLE_CHANGED', 'USERS', $id, ['role_id' => $existing['role_id']], ['role_id' => $data['role_id']]);
            }
            Helper::logAudit('USER_UPDATED', 'USERS', $id, $existing, $data);
        }

        return $success;
    }

    /**
     * Toggle user status (active / inactive) with last owner safeguard
     */
    public static function toggleStatus(int $id): string {
        $pdo = Database::getConnection();
        $user = self::find($id);
        if (!$user) {
            throw new Exception("Mtumiaji hakupatikana.");
        }

        $newStatus = ($user['status'] === 'active') ? 'inactive' : 'active';

        // Safeguard: Do not allow deactivating the last active owner!
        if ($user['role_name'] === 'super_admin' && $newStatus === 'inactive') {
            if (self::countActiveOwners() <= 1) {
                throw new Exception("Huwezi kumzima Mmiliki (Owner) pekee aliyebaki kwenye mfumo!");
            }
        }

        $stmt = $pdo->prepare("UPDATE `users` SET `status` = ? WHERE `id` = ?");
        $stmt->execute([$newStatus, $id]);

        $action = ($newStatus === 'active') ? 'USER_ACTIVATED' : 'USER_DEACTIVATED';
        Helper::logAudit($action, 'USERS', $id, ['status' => $user['status']], ['status' => $newStatus]);

        return $newStatus;
    }

    /**
     * Admin resets a user's password
     */
    public static function resetPassword(int $id, string $newPassword): bool {
        if (strlen($newPassword) < 6) {
            throw new Exception("Nenosiri jipya lazima liwe na herufi zisizopungua 6.");
        }

        $pdo = Database::getConnection();
        $user = self::find($id);
        if (!$user) {
            throw new Exception("Mtumiaji hakupatikana.");
        }

        $hash = password_hash($newPassword, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("UPDATE `users` SET `password` = ? WHERE `id` = ?");
        $success = $stmt->execute([$hash, $id]);

        if ($success) {
            Helper::logAudit('PASSWORD_RESET', 'USERS', $id, null, [
                'target_username' => $user['username'],
                'message'         => "Nenosiri la mtumiaji {$user['username']} limewekwa upya na msimamizi"
            ]);
        }

        return $success;
    }

    /**
     * User changes their own password
     */
    public static function changePassword(int $id, string $currentPassword, string $newPassword): bool {
        if (strlen($newPassword) < 6) {
            throw new Exception("Nenosiri jipya lazima liwe na herufi zisizopungua 6.");
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT password, username FROM `users` WHERE id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($currentPassword, $user['password'])) {
            throw new Exception("Nenosiri la sasa uliloweka sio sahihi.");
        }

        $hash = password_hash($newPassword, PASSWORD_BCRYPT);
        $update = $pdo->prepare("UPDATE `users` SET `password` = ? WHERE `id` = ?");
        $success = $update->execute([$hash, $id]);

        if ($success) {
            Helper::logAudit('PASSWORD_CHANGED', 'USERS', $id, null, [
                'username' => $user['username'],
                'message'  => "Mtumiaji amebadilisha nenosiri lake mwenyewe"
            ]);
        }

        return $success;
    }

    /**
     * Count active owners
     */
    public static function countActiveOwners(): int {
        $pdo = Database::getConnection();
        $stmt = $pdo->query("
            SELECT COUNT(*) 
            FROM `users` u
            JOIN `roles` r ON u.role_id = r.id
            WHERE r.name = 'super_admin' AND u.status = 'active'
        ");
        return (int)$stmt->fetchColumn();
    }

    /**
     * Fetch all roles
     */
    public static function roles(): array {
        $pdo = Database::getConnection();
        return $pdo->query("SELECT * FROM `roles` ORDER BY id ASC")->fetchAll();
    }
}
