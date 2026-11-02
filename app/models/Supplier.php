<?php
/**
 * NGENZI MOTO SPARE PARTS
 * Supplier Model
 */

namespace App\Models;

use App\Core\Database;
use PDO;
use Exception;

class Supplier {

    public static function all(string $status = 'active'): array {
        $pdo = Database::getConnection();
        if ($status === 'all') {
            $stmt = $pdo->query("SELECT * FROM `suppliers` ORDER BY name ASC");
        } else {
            $stmt = $pdo->prepare("SELECT * FROM `suppliers` WHERE status = ? ORDER BY name ASC");
            $stmt->execute([$status]);
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function find(int $id): ?array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM `suppliers` WHERE id = ?");
        $stmt->execute([$id]);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        return $res ?: null;
    }

    public static function generateCode(): string {
        $pdo = Database::getConnection();
        $stmt = $pdo->query("SELECT COUNT(*) FROM `suppliers`");
        $cnt = (int)$stmt->fetchColumn() + 1;
        do {
            $code = sprintf("SUP-%04d", $cnt);
            $chk = $pdo->prepare("SELECT id FROM `suppliers` WHERE supplier_code = ?");
            $chk->execute([$code]);
            if (!$chk->fetch()) return $code;
            $cnt++;
        } while (true);
    }

    public static function create(array $data): int {
        $pdo = Database::getConnection();
        $code = !empty($data['supplier_code']) ? $data['supplier_code'] : self::generateCode();
        $stmt = $pdo->prepare("
            INSERT INTO `suppliers` (supplier_code, name, contact_person, phone, email, address, notes, status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $code,
            $data['name'],
            $data['contact_person'] ?? null,
            $data['phone'] ?? '',
            $data['email'] ?? null,
            $data['address'] ?? null,
            $data['notes'] ?? null,
            $data['status'] ?? 'active'
        ]);
        return (int)$pdo->lastInsertId();
    }
}
