<?php
/**
 * NGENZI MOTO SPARE PARTS
 * Mechanic Model — Phase 7
 */

namespace App\Models;

use App\Core\Database;
use App\Core\Auth;
use PDO;
use Exception;

class Mechanic {

    /**
     * Generate unique mechanic code: MECH-000001
     */
    public static function generateCode(): string {
        $pdo = Database::getConnection();
        $stmt = $pdo->query("SELECT COUNT(*) FROM `mechanics`");
        $count = (int)$stmt->fetchColumn() + 1;

        do {
            $code = sprintf("MECH-%06d", $count);
            $check = $pdo->prepare("SELECT id FROM `mechanics` WHERE mechanic_code = ? LIMIT 1");
            $check->execute([$code]);
            if (!$check->fetchColumn()) {
                return $code;
            }
            $count++;
        } while (true);
    }

    /**
     * Fetch list of mechanics with search, filters & pagination
     */
    public static function all(array $filters = [], int $page = 1, int $perPage = 20): array {
        $pdo = Database::getConnection();
        $where = ["1=1"];
        $params = [];

        if (!empty($filters['search'])) {
            $term = "%{$filters['search']}%";
            $where[] = "(m.name LIKE ? OR m.mechanic_code LIKE ? OR m.phone LIKE ? OR m.alternative_phone LIKE ? OR m.area LIKE ? OR m.specialization LIKE ?)";
            $params = array_merge($params, [$term, $term, $term, $term, $term, $term]);
        }

        if (!empty($filters['garage_id'])) {
            $where[] = "m.garage_id = ?";
            $params[] = (int)$filters['garage_id'];
        }

        if (!empty($filters['specialization'])) {
            $where[] = "m.specialization LIKE ?";
            $params[] = "%{$filters['specialization']}%";
        }

        if (!empty($filters['status'])) {
            $where[] = "m.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['area'])) {
            $where[] = "m.area LIKE ?";
            $params[] = "%{$filters['area']}%";
        }

        $whereClause = implode(" AND ", $where);
        $offset = ($page - 1) * $perPage;

        $sql = "
            SELECT m.*,
                   g.garage_name, g.garage_code, g.location as garage_location,
                   c.customer_code, c.name as customer_name,
                   (SELECT COUNT(*) FROM `sales` WHERE mechanic_id = m.id AND sale_status != 'cancelled') as total_sales_count,
                   (SELECT COALESCE(SUM(total_amount), 0.00) FROM `sales` WHERE mechanic_id = m.id AND sale_status != 'cancelled') as total_sales_amount
            FROM `mechanics` m
            LEFT JOIN `garages` g ON m.garage_id = g.id
            LEFT JOIN `customers` c ON m.customer_id = c.id
            WHERE {$whereClause}
            ORDER BY m.created_at DESC, m.id DESC
            LIMIT {$perPage} OFFSET {$offset}
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Count mechanics matching filters for pagination
     */
    public static function count(array $filters = []): int {
        $pdo = Database::getConnection();
        $where = ["1=1"];
        $params = [];

        if (!empty($filters['search'])) {
            $term = "%{$filters['search']}%";
            $where[] = "(m.name LIKE ? OR m.mechanic_code LIKE ? OR m.phone LIKE ? OR m.alternative_phone LIKE ? OR m.area LIKE ? OR m.specialization LIKE ?)";
            $params = array_merge($params, [$term, $term, $term, $term, $term, $term]);
        }

        if (!empty($filters['garage_id'])) {
            $where[] = "m.garage_id = ?";
            $params[] = (int)$filters['garage_id'];
        }

        if (!empty($filters['specialization'])) {
            $where[] = "m.specialization LIKE ?";
            $params[] = "%{$filters['specialization']}%";
        }

        if (!empty($filters['status'])) {
            $where[] = "m.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['area'])) {
            $where[] = "m.area LIKE ?";
            $params[] = "%{$filters['area']}%";
        }

        $whereClause = implode(" AND ", $where);
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM `mechanics` m WHERE {$whereClause}");
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Find single mechanic by ID
     */
    public static function find(int $id): ?array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT m.*,
                   g.garage_name, g.garage_code, g.phone as garage_phone, g.location as garage_location,
                   c.customer_code, c.name as customer_name, c.current_balance as customer_balance,
                   u.full_name as created_by_name
            FROM `mechanics` m
            LEFT JOIN `garages` g ON m.garage_id = g.id
            LEFT JOIN `customers` c ON m.customer_id = c.id
            LEFT JOIN `users` u ON m.created_by = u.id
            WHERE m.id = ?
        ");
        $stmt->execute([$id]);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        return $res ?: null;
    }

    /**
     * Find mechanic by unique code
     */
    public static function findByCode(string $code): ?array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM `mechanics` WHERE mechanic_code = ? LIMIT 1");
        $stmt->execute([trim($code)]);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        return $res ?: null;
    }

    /**
     * Create new mechanic
     */
    public static function create(array $data): int {
        $pdo = Database::getConnection();

        $code = !empty($data['mechanic_code']) ? trim($data['mechanic_code']) : self::generateCode();

        $existing = self::findByCode($code);
        if ($existing) {
            throw new Exception("Kodi ya fundi '{$code}' tayari ipo.");
        }

        $name = trim($data['name']);
        $phone = trim($data['phone']);
        $altPhone = !empty($data['alternative_phone']) ? trim($data['alternative_phone']) : null;
        $area = !empty($data['area']) ? trim($data['area']) : null;
        $garageId = !empty($data['garage_id']) ? (int)$data['garage_id'] : null;
        $customerId = !empty($data['customer_id']) ? (int)$data['customer_id'] : null;
        $specialization = !empty($data['specialization']) ? trim($data['specialization']) : null;
        $experience = !empty($data['years_of_experience']) ? (int)$data['years_of_experience'] : null;
        $notes = !empty($data['notes']) ? trim($data['notes']) : null;
        $status = $data['status'] ?? 'active';
        $userId = $data['created_by'] ?? Auth::id() ?? null;

        // Auto-create customer profile if requested and not linked
        if (empty($customerId) && !empty($data['create_customer_profile'])) {
            $customerId = Customer::create([
                'name'          => $name,
                'phone'         => $phone,
                'alternative_phone' => $altPhone,
                'area'          => $area,
                'customer_type' => 'mechanic',
                'notes'         => "Akaunti imeunganishwa moja kwa moja na Fundi #{$code}",
                'created_by'    => $userId
            ]);
        }

        $stmt = $pdo->prepare("
            INSERT INTO `mechanics` (
                mechanic_code, customer_id, garage_id, name, phone, 
                alternative_phone, area, specialization, years_of_experience, 
                notes, status, created_by, created_at
            ) VALUES (
                ?, ?, ?, ?, ?, 
                ?, ?, ?, ?, 
                ?, ?, ?, NOW()
            )
        ");

        $stmt->execute([
            $code, $customerId, $garageId, $name, $phone,
            $altPhone, $area, $specialization, $experience,
            $notes, $status, $userId
        ]);

        $mechanicId = (int)$pdo->lastInsertId();

        // Audit log
        $stmtAudit = $pdo->prepare("
            INSERT INTO `audit_logs` (user_id, action, module, record_id, new_values, created_at)
            VALUES (?, 'MECHANIC_CREATED', 'mechanics', ?, ?, NOW())
        ");
        $stmtAudit->execute([
            $userId, 
            $mechanicId, 
            json_encode([
                'mechanic_code'  => $code,
                'name'           => $name,
                'phone'          => $phone,
                'garage_id'      => $garageId,
                'specialization' => $specialization
            ])
        ]);

        return $mechanicId;
    }

    /**
     * Update mechanic details
     */
    public static function update(int $id, array $data): bool {
        $pdo = Database::getConnection();
        $existing = self::find($id);
        if (!$existing) {
            throw new Exception("Fundi hakupatikana.");
        }

        $code = !empty($data['mechanic_code']) ? trim($data['mechanic_code']) : $existing['mechanic_code'];
        if ($code !== $existing['mechanic_code']) {
            $check = self::findByCode($code);
            if ($check && $check['id'] !== $id) {
                throw new Exception("Kodi ya fundi '{$code}' tayari ipo.");
            }
        }

        $stmt = $pdo->prepare("
            UPDATE `mechanics` SET
                mechanic_code = ?,
                garage_id = ?,
                customer_id = ?,
                name = ?,
                phone = ?,
                alternative_phone = ?,
                area = ?,
                specialization = ?,
                years_of_experience = ?,
                notes = ?,
                status = ?,
                updated_at = NOW()
            WHERE id = ?
        ");

        $userId = Auth::id() ?? null;
        $garageId = !empty($data['garage_id']) ? (int)$data['garage_id'] : null;
        $customerId = !empty($data['customer_id']) ? (int)$data['customer_id'] : $existing['customer_id'];

        $res = $stmt->execute([
            $code,
            $garageId,
            $customerId,
            trim($data['name']),
            trim($data['phone']),
            !empty($data['alternative_phone']) ? trim($data['alternative_phone']) : null,
            !empty($data['area']) ? trim($data['area']) : null,
            !empty($data['specialization']) ? trim($data['specialization']) : null,
            !empty($data['years_of_experience']) ? (int)$data['years_of_experience'] : null,
            !empty($data['notes']) ? trim($data['notes']) : null,
            $data['status'] ?? $existing['status'],
            $id
        ]);

        if ($res) {
            $stmtAudit = $pdo->prepare("
                INSERT INTO `audit_logs` (user_id, action, module, record_id, old_values, new_values, created_at)
                VALUES (?, 'MECHANIC_UPDATED', 'mechanics', ?, ?, ?, NOW())
            ");
            $stmtAudit->execute([
                $userId,
                $id,
                json_encode(['name' => $existing['name'], 'garage_id' => $existing['garage_id']]),
                json_encode(['name' => $data['name'], 'garage_id' => $garageId])
            ]);
        }

        return $res;
    }

    /**
     * Change mechanic status
     */
    public static function setStatus(int $id, string $status, ?int $userId = null): bool {
        $pdo = Database::getConnection();
        $allowed = ['active', 'inactive', 'blocked'];
        if (!in_array($status, $allowed)) {
            throw new Exception("Hadhi ya fundi si sahihi: {$status}");
        }

        $userId = $userId ?? Auth::id() ?? null;
        $stmt = $pdo->prepare("UPDATE `mechanics` SET status = ?, updated_at = NOW() WHERE id = ?");
        $res = $stmt->execute([$status, $id]);

        if ($res) {
            $action = ($status === 'inactive' || $status === 'blocked') ? 'MECHANIC_DEACTIVATED' : 'MECHANIC_ACTIVATED';
            $stmtAudit = $pdo->prepare("
                INSERT INTO `audit_logs` (user_id, action, module, record_id, new_values, created_at)
                VALUES (?, ?, 'mechanics', ?, ?, NOW())
            ");
            $stmtAudit->execute([$userId, $action, $id, json_encode(['status' => $status])]);
        }

        return $res;
    }

    /**
     * Get all mechanics belonging to a specific garage
     */
    public static function getByGarage(int $garageId): array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT m.*, c.customer_code
            FROM `mechanics` m
            LEFT JOIN `customers` c ON m.customer_id = c.id
            WHERE m.garage_id = ?
            ORDER BY m.name ASC
        ");
        $stmt->execute([$garageId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Dashboard KPIs for mechanics
     */
    public static function getDashboardStats(): array {
        $pdo = Database::getConnection();

        $total = (int)$pdo->query("SELECT COUNT(*) FROM `mechanics`")->fetchColumn();
        $active = (int)$pdo->query("SELECT COUNT(*) FROM `mechanics` WHERE status = 'active'")->fetchColumn();

        // Mechanics by area
        $byArea = $pdo->query("
            SELECT COALESCE(NULLIF(area, ''), 'Haijawekwa') as area_name, COUNT(*) as total
            FROM `mechanics`
            GROUP BY area_name
            ORDER BY total DESC
            LIMIT 6
        ")->fetchAll(PDO::FETCH_ASSOC);

        // Mechanics by garage
        $byGarage = $pdo->query("
            SELECT g.garage_name, COUNT(m.id) as mechanic_count
            FROM `mechanics` m
            JOIN `garages` g ON m.garage_id = g.id
            GROUP BY g.id
            ORDER BY mechanic_count DESC
            LIMIT 6
        ")->fetchAll(PDO::FETCH_ASSOC);

        // Recent mechanics
        $recent = $pdo->query("
            SELECT m.*, g.garage_name
            FROM `mechanics` m
            LEFT JOIN `garages` g ON m.garage_id = g.id
            ORDER BY m.created_at DESC, m.id DESC
            LIMIT 5
        ")->fetchAll(PDO::FETCH_ASSOC);

        return [
            'total_mechanics'  => $total,
            'active_mechanics' => $active,
            'by_area'          => $byArea,
            'by_garage'        => $byGarage,
            'recent_mechanics' => $recent
        ];
    }
}
