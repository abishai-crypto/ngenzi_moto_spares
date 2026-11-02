<?php
/**
 * NGENZI MOTO SPARE PARTS
 * Garage Model — Phase 7
 */

namespace App\Models;

use App\Core\Database;
use App\Core\Auth;
use PDO;
use Exception;

class Garage {

    /**
     * Generate unique garage code: GAR-000001
     */
    public static function generateCode(): string {
        $pdo = Database::getConnection();
        $stmt = $pdo->query("SELECT COUNT(*) FROM `garages`");
        $count = (int)$stmt->fetchColumn() + 1;

        do {
            $code = sprintf("GAR-%06d", $count);
            $check = $pdo->prepare("SELECT id FROM `garages` WHERE garage_code = ? LIMIT 1");
            $check->execute([$code]);
            if (!$check->fetchColumn()) {
                return $code;
            }
            $count++;
        } while (true);
    }

    /**
     * Fetch list of garages with search, filters & pagination
     */
    public static function all(array $filters = [], int $page = 1, int $perPage = 20): array {
        $pdo = Database::getConnection();
        $where = ["1=1"];
        $params = [];

        if (!empty($filters['search'])) {
            $term = "%{$filters['search']}%";
            $where[] = "(g.garage_name LIKE ? OR g.garage_code LIKE ? OR g.owner_name LIKE ? OR g.phone LIKE ? OR g.location LIKE ? OR g.area LIKE ?)";
            $params = array_merge($params, [$term, $term, $term, $term, $term, $term]);
        }

        if (!empty($filters['garage_type'])) {
            $where[] = "g.garage_type LIKE ?";
            $params[] = "%{$filters['garage_type']}%";
        }

        if (!empty($filters['status'])) {
            $where[] = "g.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['area'])) {
            $where[] = "(g.area LIKE ? OR g.location LIKE ?)";
            $params = array_merge($params, ["%{$filters['area']}%", "%{$filters['area']}%"]);
        }

        $whereClause = implode(" AND ", $where);
        $offset = ($page - 1) * $perPage;

        $sql = "
            SELECT g.*,
                   c.customer_code, c.current_balance as customer_balance,
                   COUNT(DISTINCT m.id) as mechanics_count
            FROM `garages` g
            LEFT JOIN `customers` c ON g.customer_id = c.id
            LEFT JOIN `mechanics` m ON g.id = m.garage_id AND m.status = 'active'
            WHERE {$whereClause}
            GROUP BY g.id
            ORDER BY g.created_at DESC, g.id DESC
            LIMIT {$perPage} OFFSET {$offset}
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Count garages matching filters for pagination
     */
    public static function count(array $filters = []): int {
        $pdo = Database::getConnection();
        $where = ["1=1"];
        $params = [];

        if (!empty($filters['search'])) {
            $term = "%{$filters['search']}%";
            $where[] = "(garage_name LIKE ? OR garage_code LIKE ? OR owner_name LIKE ? OR phone LIKE ? OR location LIKE ? OR area LIKE ?)";
            $params = array_merge($params, [$term, $term, $term, $term, $term, $term]);
        }

        if (!empty($filters['garage_type'])) {
            $where[] = "garage_type LIKE ?";
            $params[] = "%{$filters['garage_type']}%";
        }

        if (!empty($filters['status'])) {
            $where[] = "status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['area'])) {
            $where[] = "(area LIKE ? OR location LIKE ?)";
            $params = array_merge($params, ["%{$filters['area']}%", "%{$filters['area']}%"]);
        }

        $whereClause = implode(" AND ", $where);
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM `garages` WHERE {$whereClause}");
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Find single garage by ID
     */
    public static function find(int $id): ?array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT g.*,
                   c.customer_code, c.name as customer_name, c.current_balance as customer_balance,
                   u.full_name as created_by_name
            FROM `garages` g
            LEFT JOIN `customers` c ON g.customer_id = c.id
            LEFT JOIN `users` u ON g.created_by = u.id
            WHERE g.id = ?
        ");
        $stmt->execute([$id]);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        return $res ?: null;
    }

    /**
     * Find garage by unique code
     */
    public static function findByCode(string $code): ?array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM `garages` WHERE garage_code = ? LIMIT 1");
        $stmt->execute([trim($code)]);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        return $res ?: null;
    }

    /**
     * Create new garage
     */
    public static function create(array $data): int {
        $pdo = Database::getConnection();

        $code = !empty($data['garage_code']) ? trim($data['garage_code']) : self::generateCode();
        $existing = self::findByCode($code);
        if ($existing) {
            throw new Exception("Kodi ya gereji '{$code}' tayari ipo.");
        }

        $garageName = trim($data['garage_name']);
        $ownerName = !empty($data['owner_name']) ? trim($data['owner_name']) : null;
        $phone = trim($data['phone']);
        $altPhone = !empty($data['alternative_phone']) ? trim($data['alternative_phone']) : null;
        $email = !empty($data['email']) ? trim($data['email']) : null;
        $location = trim($data['location']);
        $area = !empty($data['area']) ? trim($data['area']) : $location;
        $address = !empty($data['address']) ? trim($data['address']) : null;
        $specialization = !empty($data['specialization']) ? trim($data['specialization']) : null;
        $garageType = !empty($data['garage_type']) ? trim($data['garage_type']) : null;
        $creditLimit = !empty($data['credit_limit']) ? (float)$data['credit_limit'] : 0.00;
        $customerId = !empty($data['customer_id']) ? (int)$data['customer_id'] : null;
        $notes = !empty($data['notes']) ? trim($data['notes']) : null;
        $status = $data['status'] ?? 'active';
        $userId = $data['created_by'] ?? Auth::id() ?? null;

        // Auto-create customer profile if requested and not linked (Prompt Item 23)
        if (empty($customerId) && !empty($data['create_customer_profile'])) {
            $customerId = Customer::create([
                'name'          => $garageName,
                'phone'         => $phone,
                'alternative_phone' => $altPhone,
                'email'         => $email,
                'address'       => $address ?: $location,
                'area'          => $area,
                'customer_type' => 'garage',
                'credit_limit'  => $creditLimit,
                'notes'         => "Akaunti ya mteja imeunganishwa na Gereji #{$code} (Mmiliki: {$ownerName})",
                'created_by'    => $userId
            ]);
        }

        $stmt = $pdo->prepare("
            INSERT INTO `garages` (
                garage_code, garage_name, owner_name, phone, alternative_phone,
                email, location, area, address, specialization,
                garage_type, credit_limit, customer_id, status, notes,
                created_by, created_at
            ) VALUES (
                ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?,
                ?, NOW()
            )
        ");

        $stmt->execute([
            $code, $garageName, $ownerName, $phone, $altPhone,
            $email, $location, $area, $address, $specialization,
            $garageType, $creditLimit, $customerId, $status, $notes,
            $userId
        ]);

        $garageId = (int)$pdo->lastInsertId();

        // Audit log
        $stmtAudit = $pdo->prepare("
            INSERT INTO `audit_logs` (user_id, action, module, record_id, new_values, created_at)
            VALUES (?, 'GARAGE_CREATED', 'garages', ?, ?, NOW())
        ");
        $stmtAudit->execute([
            $userId, 
            $garageId, 
            json_encode([
                'garage_code' => $code,
                'garage_name' => $garageName,
                'phone'       => $phone,
                'location'    => $location
            ])
        ]);

        return $garageId;
    }

    /**
     * Update garage details
     */
    public static function update(int $id, array $data): bool {
        $pdo = Database::getConnection();
        $existing = self::find($id);
        if (!$existing) {
            throw new Exception("Gereji haikupatikana.");
        }

        $code = !empty($data['garage_code']) ? trim($data['garage_code']) : $existing['garage_code'];
        if ($code !== $existing['garage_code']) {
            $check = self::findByCode($code);
            if ($check && $check['id'] !== $id) {
                throw new Exception("Kodi ya gereji '{$code}' tayari ipo.");
            }
        }

        $stmt = $pdo->prepare("
            UPDATE `garages` SET
                garage_code = ?,
                garage_name = ?,
                owner_name = ?,
                phone = ?,
                alternative_phone = ?,
                email = ?,
                location = ?,
                area = ?,
                address = ?,
                specialization = ?,
                garage_type = ?,
                credit_limit = ?,
                customer_id = ?,
                notes = ?,
                status = ?,
                updated_at = NOW()
            WHERE id = ?
        ");

        $userId = Auth::id() ?? null;
        $customerId = !empty($data['customer_id']) ? (int)$data['customer_id'] : $existing['customer_id'];

        $res = $stmt->execute([
            $code,
            trim($data['garage_name']),
            !empty($data['owner_name']) ? trim($data['owner_name']) : null,
            trim($data['phone']),
            !empty($data['alternative_phone']) ? trim($data['alternative_phone']) : null,
            !empty($data['email']) ? trim($data['email']) : null,
            trim($data['location']),
            !empty($data['area']) ? trim($data['area']) : trim($data['location']),
            !empty($data['address']) ? trim($data['address']) : null,
            !empty($data['specialization']) ? trim($data['specialization']) : null,
            !empty($data['garage_type']) ? trim($data['garage_type']) : null,
            isset($data['credit_limit']) ? (float)$data['credit_limit'] : (float)$existing['credit_limit'],
            $customerId,
            !empty($data['notes']) ? trim($data['notes']) : null,
            $data['status'] ?? $existing['status'],
            $id
        ]);

        if ($res) {
            $stmtAudit = $pdo->prepare("
                INSERT INTO `audit_logs` (user_id, action, module, record_id, old_values, new_values, created_at)
                VALUES (?, 'GARAGE_UPDATED', 'garages', ?, ?, ?, NOW())
            ");
            $stmtAudit->execute([
                $userId,
                $id,
                json_encode(['name' => $existing['garage_name'], 'phone' => $existing['phone']]),
                json_encode(['name' => $data['garage_name'], 'phone' => $data['phone']])
            ]);
        }

        return $res;
    }

    /**
     * Change garage status
     */
    public static function setStatus(int $id, string $status, ?int $userId = null): bool {
        $pdo = Database::getConnection();
        $allowed = ['active', 'inactive'];
        if (!in_array($status, $allowed)) {
            throw new Exception("Hali ya gereji si sahihi: {$status}");
        }

        $userId = $userId ?? Auth::id() ?? null;
        $stmt = $pdo->prepare("UPDATE `garages` SET status = ?, updated_at = NOW() WHERE id = ?");
        $res = $stmt->execute([$status, $id]);

        if ($res) {
            $action = ($status === 'inactive') ? 'GARAGE_DEACTIVATED' : 'GARAGE_ACTIVATED';
            $stmtAudit = $pdo->prepare("
                INSERT INTO `audit_logs` (user_id, action, module, record_id, new_values, created_at)
                VALUES (?, ?, 'garages', ?, ?, NOW())
            ");
            $stmtAudit->execute([$userId, $action, $id, json_encode(['status' => $status])]);
        }

        return $res;
    }

    /**
     * Dashboard KPIs for Garages
     */
    public static function getDashboardStats(): array {
        $pdo = Database::getConnection();

        $total = (int)$pdo->query("SELECT COUNT(*) FROM `garages`")->fetchColumn();
        $active = (int)$pdo->query("SELECT COUNT(*) FROM `garages` WHERE status = 'active'")->fetchColumn();

        // Garages by area
        $byArea = $pdo->query("
            SELECT COALESCE(NULLIF(area, ''), location, 'Dar es Salaam') as area_name, COUNT(*) as total
            FROM `garages`
            GROUP BY area_name
            ORDER BY total DESC
            LIMIT 6
        ")->fetchAll(PDO::FETCH_ASSOC);

        // Recent garages
        $recent = $pdo->query("
            SELECT g.*, COUNT(m.id) as mechanics_count
            FROM `garages` g
            LEFT JOIN `mechanics` m ON g.id = m.garage_id AND m.status = 'active'
            GROUP BY g.id
            ORDER BY g.created_at DESC, g.id DESC
            LIMIT 5
        ")->fetchAll(PDO::FETCH_ASSOC);

        return [
            'total_garages'  => $total,
            'active_garages' => $active,
            'by_area'        => $byArea,
            'recent_garages' => $recent
        ];
    }
}
