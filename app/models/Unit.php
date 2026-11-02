<?php
/**
 * NGENZI MOTO SPARE PARTS
 * Unit Model (Phase 4)
 */

namespace App\Models;

use App\Core\Database;
use App\Core\Helper;
use PDO;
use Exception;

class Unit {
    public static function all(): array {
        $pdo = Database::getConnection();
        return $pdo->query("SELECT * FROM `units` ORDER BY id ASC")->fetchAll();
    }

    public static function find(int $id): ?array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM `units` WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function findByName(string $name): ?array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM `units` WHERE name = ? OR short_name = ? OR name LIKE ? OR short_name LIKE ? LIMIT 1");
        $like = "%{$name}%";
        $stmt->execute([$name, $name, $like, $like]);
        return $stmt->fetch() ?: null;
    }

    public static function create(array $data): int {
        $pdo = Database::getConnection();
        $name = trim($data['name'] ?? '');
        $shortName = trim($data['short_name'] ?? '');

        if (empty($name) || empty($shortName)) {
            throw new Exception("Jina la kipimo na kifupisho chake vinahitajika.");
        }

        if (self::findByName($name)) {
            throw new Exception("Kipimo cha '{$name}' tayari kipo.");
        }

        $stmt = $pdo->prepare("INSERT INTO `units` (`name`, `short_name`) VALUES (?, ?)");
        $stmt->execute([$name, $shortName]);
        $newId = (int)$pdo->lastInsertId();

        Helper::logAudit('UNIT_CREATED', 'UNITS', $newId, null, [
            'name'       => $name,
            'short_name' => $shortName
        ]);

        return $newId;
    }

    public static function update(int $id, array $data): bool {
        $pdo = Database::getConnection();
        $unit = self::find($id);
        if (!$unit) {
            throw new Exception("Kipimo hakikupatikana.");
        }

        $name = trim($data['name'] ?? '');
        $shortName = trim($data['short_name'] ?? '');

        if (empty($name) || empty($shortName)) {
            throw new Exception("Jina la kipimo na kifupisho chake vinahitajika.");
        }

        $stmt = $pdo->prepare("UPDATE `units` SET `name` = ?, `short_name` = ? WHERE `id` = ?");
        $success = $stmt->execute([$name, $shortName, $id]);

        if ($success) {
            Helper::logAudit('UNIT_UPDATED', 'UNITS', $id, $unit, [
                'name'       => $name,
                'short_name' => $shortName
            ]);
        }

        return $success;
    }
}
