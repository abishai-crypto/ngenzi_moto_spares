<?php
/**
 * NGENZI MOTO SPARE PARTS
 * StockCount Model (Phase 5)
 * Physical Stock Count & Reconciliation Engine
 */

namespace App\Models;

use App\Core\Database;
use App\Core\Auth;
use PDO;
use Exception;

class StockCount {

    /**
     * Generate unique sequential count number, e.g. SC-00001
     */
    public static function generateCountNo(): string {
        $pdo = Database::getConnection();
        $stmt = $pdo->query("SELECT COUNT(*) FROM `stock_counts`");
        $count = (int)$stmt->fetchColumn() + 1;

        do {
            $code = sprintf("SC-%05d", $count);
            $chk = $pdo->prepare("SELECT id FROM `stock_counts` WHERE count_no = ?");
            $chk->execute([$code]);
            if (!$chk->fetch()) {
                return $code;
            }
            $count++;
        } while (true);
    }

    /**
     * Create a new physical count session
     */
    public static function createSession(array $data, int $userId): int {
        $pdo = Database::getConnection();
        $countNo = !empty($data['count_no']) ? $data['count_no'] : self::generateCountNo();
        $countDate = !empty($data['count_date']) ? $data['count_date'] : date('Y-m-d');
        $notes = $data['notes'] ?? null;
        $status = $data['status'] ?? 'draft';

        $stmt = $pdo->prepare("
            INSERT INTO `stock_counts` (count_no, count_date, status, notes, conducted_by, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$countNo, $countDate, $status, $notes, $userId]);
        $sessionId = (int)$pdo->lastInsertId();

        $stmtAudit = $pdo->prepare("
            INSERT INTO `audit_logs` (user_id, action, module, record_id, new_values, created_at)
            VALUES (?, 'CREATE_STOCK_COUNT', 'inventory', ?, ?, NOW())
        ");
        $stmtAudit->execute([$userId, $sessionId, json_encode(['count_no' => $countNo, 'date' => $countDate])]);

        return $sessionId;
    }

    /**
     * Add counted items to a session
     */
    public static function addItems(int $stockCountId, array $items): void {
        $pdo = Database::getConnection();

        $stmtProd = $pdo->prepare("SELECT id, current_stock, buying_price FROM `products` WHERE id = ?");
        $stmtInsert = $pdo->prepare("
            INSERT INTO `stock_count_items` (
                stock_count_id, product_id, system_quantity, physical_quantity, 
                variance, variance_cost, reason
            ) VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        foreach ($items as $item) {
            $productId = (int)$item['product_id'];
            $physicalQty = (int)$item['physical_quantity'];
            $reason = $item['reason'] ?? null;

            $stmtProd->execute([$productId]);
            $prod = $stmtProd->fetch(PDO::FETCH_ASSOC);
            if (!$prod) continue;

            $systemQty = (int)$prod['current_stock'];
            $variance = $physicalQty - $systemQty;
            $varianceCost = $variance * (float)$prod['buying_price'];

            $stmtInsert->execute([
                $stockCountId,
                $productId,
                $systemQty,
                $physicalQty,
                $variance,
                $varianceCost,
                $reason
            ]);
        }
    }

    /**
     * Submit physical count for review
     */
    public static function submitCount(int $stockCountId, int $userId): bool {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            UPDATE `stock_counts` 
            SET status = 'submitted' 
            WHERE id = ? AND status IN ('draft', 'in_progress', 'pending')
        ");
        $res = $stmt->execute([$stockCountId]);
        if ($res) {
            $stmtAudit = $pdo->prepare("
                INSERT INTO `audit_logs` (user_id, action, module, record_id, new_values, created_at)
                VALUES (?, 'SUBMIT_STOCK_COUNT', 'inventory', ?, ?, NOW())
            ");
            $stmtAudit->execute([$userId, $stockCountId, json_encode(['status' => 'submitted'])]);
        }
        return $res;
    }

    /**
     * Approve physical count:
     * Applies inventory adjustments atomically for all items with variance.
     */
    public static function approveCount(int $stockCountId, int $approverId): array {
        $pdo = Database::getConnection();
        $pdo->beginTransaction();

        try {
            // Lock stock count row
            $stmt = $pdo->prepare("SELECT * FROM `stock_counts` WHERE id = ? FOR UPDATE");
            $stmt->execute([$stockCountId]);
            $count = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$count) {
                throw new Exception("Ukaguzi wa stoo haukupatikana.");
            }

            if ($count['status'] === 'approved') {
                throw new Exception("Ukaguzi huu wa stoo tayari ulishathibitishwa (Already approved).");
            }

            // Fetch items
            $stmtItems = $pdo->prepare("
                SELECT sci.*, p.name as product_name, p.buying_price as buying_cost, p.sku
                FROM `stock_count_items` sci
                JOIN `products` p ON sci.product_id = p.id
                WHERE sci.stock_count_id = ?
            ");
            $stmtItems->execute([$stockCountId]);
            $items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

            $adjustmentsMade = 0;
            $countNo = $count['count_no'] ?: "SC-" . str_pad($count['id'], 5, '0', STR_PAD_LEFT);

            foreach ($items as $item) {
                $variance = (int)$item['variance'];
                if ($variance !== 0) {
                    $note = "Ukaguzi wa Stoo #{$countNo}: Tofauti ya vipande {$variance} (Mfumo: {$item['system_quantity']}, Ukaguzi: {$item['physical_quantity']})";
                    if (!empty($item['reason'])) {
                        $note .= " | Sababu: {$item['reason']}";
                    }

                    // Record movement with lock and updates
                    Inventory::recordMovement(
                        (int)$item['product_id'],
                        'stock_count_adjustment',
                        $variance,
                        'stock_count',
                        $stockCountId,
                        $note,
                        (float)$item['buying_cost'],
                        $approverId,
                        $pdo
                    );
                    $adjustmentsMade++;
                }
            }

            // Mark approved
            $stmtApprove = $pdo->prepare("
                UPDATE `stock_counts` 
                SET status = 'approved', approved_by = ?, notes = CONCAT(COALESCE(notes, ''), '\nImeidhinishwa na User #', ?)
                WHERE id = ?
            ");
            $stmtApprove->execute([$approverId, $approverId, $stockCountId]);

            // Audit log
            $stmtAudit = $pdo->prepare("
                INSERT INTO `audit_logs` (user_id, action, module, record_id, new_values, created_at)
                VALUES (?, 'APPROVE_STOCK_COUNT', 'inventory', ?, ?, NOW())
            ");
            $stmtAudit->execute([
                $approverId, 
                $stockCountId, 
                json_encode(['count_no' => $countNo, 'adjustments_made' => $adjustmentsMade])
            ]);

            $pdo->commit();

            return [
                'success'          => true,
                'stock_count_id'   => $stockCountId,
                'count_no'         => $countNo,
                'adjustments_made' => $adjustmentsMade
            ];

        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Reject a physical count session
     */
    public static function rejectCount(int $stockCountId, int $userId, string $reason): bool {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            UPDATE `stock_counts` 
            SET status = 'rejected', 
                notes = CONCAT(COALESCE(notes, ''), '\n[IMEKATALIWA]: ', ?)
            WHERE id = ?
        ");
        $res = $stmt->execute([$reason, $stockCountId]);
        if ($res) {
            $stmtAudit = $pdo->prepare("
                INSERT INTO `audit_logs` (user_id, action, module, record_id, new_values, created_at)
                VALUES (?, 'REJECT_STOCK_COUNT', 'inventory', ?, ?, NOW())
            ");
            $stmtAudit->execute([$userId, $stockCountId, json_encode(['reason' => $reason])]);
        }
        return $res;
    }

    /**
     * Find single session with details
     */
    public static function find(int $id): ?array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT sc.*, 
                   u1.full_name as conducted_by_name, u1.username as conducted_by_username,
                   u2.full_name as approved_by_name, u2.username as approved_by_username
            FROM `stock_counts` sc
            LEFT JOIN `users` u1 ON sc.conducted_by = u1.id
            LEFT JOIN `users` u2 ON sc.approved_by = u2.id
            WHERE sc.id = ?
        ");
        $stmt->execute([$id]);
        $count = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$count) return null;

        // Fetch items
        $stmtItems = $pdo->prepare("
            SELECT sci.*, 
                   p.name as product_name, p.sku as product_sku, 
                   c.name as category_name, b.name as brand_name, u.short_name as unit_short
            FROM `stock_count_items` sci
            JOIN `products` p ON sci.product_id = p.id
            JOIN `categories` c ON p.category_id = c.id
            LEFT JOIN `brands` b ON p.brand_id = b.id
            JOIN `units` u ON p.unit_id = u.id
            WHERE sci.stock_count_id = ?
            ORDER BY p.name ASC
        ");
        $stmtItems->execute([$id]);
        $count['items'] = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

        // Calculate summary
        $totalItems = count($count['items']);
        $totalDiscrepancies = 0;
        $netVarianceQty = 0;
        $netVarianceCost = 0.00;

        foreach ($count['items'] as $it) {
            if ((int)$it['variance'] !== 0) {
                $totalDiscrepancies++;
            }
            $netVarianceQty += (int)$it['variance'];
            $netVarianceCost += (float)$it['variance_cost'];
        }

        $count['total_items'] = $totalItems;
        $count['total_discrepancies'] = $totalDiscrepancies;
        $count['net_variance_qty'] = $netVarianceQty;
        $count['net_variance_cost'] = $netVarianceCost;

        return $count;
    }

    /**
     * Get all stock count sessions
     */
    public static function all(array $filters = []): array {
        $pdo = Database::getConnection();

        $where = ["1=1"];
        $params = [];

        if (!empty($filters['status'])) {
            $where[] = "sc.status = ?";
            $params[] = $filters['status'];
        }

        $whereClause = implode(" AND ", $where);

        $sql = "
            SELECT sc.*, 
                   u1.full_name as conducted_by_name,
                   u2.full_name as approved_by_name,
                   (SELECT COUNT(*) FROM `stock_count_items` WHERE stock_count_id = sc.id) as items_count,
                   (SELECT COUNT(*) FROM `stock_count_items` WHERE stock_count_id = sc.id AND variance != 0) as discrepancies_count,
                   (SELECT COALESCE(SUM(variance_cost), 0) FROM `stock_count_items` WHERE stock_count_id = sc.id) as total_variance_cost
            FROM `stock_counts` sc
            LEFT JOIN `users` u1 ON sc.conducted_by = u1.id
            LEFT JOIN `users` u2 ON sc.approved_by = u2.id
            WHERE {$whereClause}
            ORDER BY sc.count_date DESC, sc.id DESC
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
