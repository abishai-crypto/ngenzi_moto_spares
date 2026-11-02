<?php
/**
 * NGENZI MOTO SPARE PARTS
 * Purchase Model
 * Handles Purchase Orders & Stock Receiving Integration (Section 5)
 */

namespace App\Models;

use App\Core\Database;
use App\Core\Auth;
use PDO;
use Exception;

class Purchase {

    public static function generatePurchaseNo(): string {
        $pdo = Database::getConnection();
        $stmt = $pdo->query("SELECT COUNT(*) FROM `purchases`");
        $cnt = (int)$stmt->fetchColumn() + 1;
        do {
            $no = sprintf("PO-%05d", $cnt);
            $chk = $pdo->prepare("SELECT id FROM `purchases` WHERE purchase_no = ?");
            $chk->execute([$no]);
            if (!$chk->fetch()) return $no;
            $cnt++;
        } while (true);
    }

    /**
     * Create purchase and receive items into inventory
     */
    public static function create(array $data, array $items, int $userId): int {
        $pdo = Database::getConnection();
        $pdo->beginTransaction();

        try {
            $purchaseNo = !empty($data['purchase_no']) ? $data['purchase_no'] : self::generatePurchaseNo();
            $supplierId = (int)$data['supplier_id'];
            $invoiceNumber = $data['invoice_number'] ?? null;
            $purchaseDate = $data['purchase_date'] ?? date('Y-m-d');
            $status = $data['status'] ?? 'received'; // 'draft', 'ordered', 'received'
            $paymentStatus = $data['payment_status'] ?? 'paid';
            $paymentMethod = $data['payment_method'] ?? 'Cash';
            $notes = $data['notes'] ?? null;

            // Calculate totals
            $subtotal = 0.00;
            foreach ($items as $it) {
                $qty = (int)$it['quantity'];
                $cost = (float)$it['unit_cost'];
                $subtotal += ($qty * $cost);
            }

            $transportCost = (float)($data['transport_cost'] ?? 0.00);
            $otherCosts = (float)($data['other_costs'] ?? 0.00);
            $grandTotal = $subtotal + $transportCost + $otherCosts;
            $paidAmount = (float)($data['paid_amount'] ?? $grandTotal);
            $balanceDue = max(0.00, $grandTotal - $paidAmount);

            $stmt = $pdo->prepare("
                INSERT INTO `purchases` (
                    purchase_no, supplier_id, invoice_number, purchase_date,
                    subtotal, transport_cost, other_costs, grand_total,
                    paid_amount, balance_due, status, payment_status,
                    payment_method, notes, created_by, created_at
                ) VALUES (
                    ?, ?, ?, ?,
                    ?, ?, ?, ?,
                    ?, ?, ?, ?,
                    ?, ?, ?, NOW()
                )
            ");

            $stmt->execute([
                $purchaseNo, $supplierId, $invoiceNumber, $purchaseDate,
                $subtotal, $transportCost, $otherCosts, $grandTotal,
                $paidAmount, $balanceDue, $status, $paymentStatus,
                $paymentMethod, $notes, $userId
            ]);

            $purchaseId = (int)$pdo->lastInsertId();

            // Insert purchase items
            $stmtItem = $pdo->prepare("
                INSERT INTO `purchase_items` (
                    purchase_id, product_id, quantity, unit_cost,
                    transport_cost, other_cost, total_cost, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
            ");

            foreach ($items as $it) {
                $productId = (int)$it['product_id'];
                $qty = (int)$it['quantity'];
                $unitCost = (float)$it['unit_cost'];
                $itemTotal = $qty * $unitCost;

                $stmtItem->execute([
                    $purchaseId, $productId, $qty, $unitCost,
                    0.00, 0.00, $itemTotal
                ]);

                // If status is 'received', increase inventory and record transaction
                if ($status === 'received') {
                    Inventory::recordMovement(
                        $productId,
                        'purchase',
                        $qty,
                        'purchase',
                        $purchaseId,
                        "Manunuzi kutoka kwa Msambazaji #{$purchaseNo}",
                        $unitCost,
                        $userId,
                        $pdo
                    );

                    // Update buying cost if changed
                    $stmtProd = $pdo->prepare("SELECT buying_price FROM `products` WHERE id = ?");
                    $stmtProd->execute([$productId]);
                    $oldCost = (float)$stmtProd->fetchColumn();

                    if ($oldCost !== $unitCost && $unitCost > 0) {
                        $stmtCost = $pdo->prepare("
                            INSERT INTO `product_cost_history` (product_id, old_cost, new_cost, reason, reference_type, reference_id, changed_by, created_at)
                            VALUES (?, ?, ?, 'Manunuzi Mapya', 'purchase', ?, ?, NOW())
                        ");
                        $stmtCost->execute([$productId, $oldCost, $unitCost, $purchaseId, $userId]);

                        $stmtUpCost = $pdo->prepare("UPDATE `products` SET buying_price = ? WHERE id = ?");
                        $stmtUpCost->execute([$unitCost, $productId]);
                    }
                }
            }

            if ($status === 'received') {
                $stmtAudit = $pdo->prepare("
                    INSERT INTO `audit_logs` (user_id, action, module, record_id, new_values, created_at)
                    VALUES (?, 'STOCK_RECEIVING_COMPLETED', 'purchases', ?, ?, NOW())
                ");
                $stmtAudit->execute([$userId, $purchaseId, json_encode(['purchase_no' => $purchaseNo, 'items_count' => count($items)])]);
            }

            $pdo->commit();
            return $purchaseId;

        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Receive an existing draft/ordered purchase
     */
    public static function receive(int $purchaseId, int $userId): bool {
        $pdo = Database::getConnection();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare("SELECT * FROM `purchases` WHERE id = ? FOR UPDATE");
            $stmt->execute([$purchaseId]);
            $purchase = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$purchase) {
                throw new Exception("Manunuzi hayakupatikana.");
            }

            if ($purchase['status'] === 'received') {
                throw new Exception("Manunuzi haya yalikwisha pokelewa stoo.");
            }

            // Get items
            $stmtItems = $pdo->prepare("SELECT * FROM `purchase_items` WHERE purchase_id = ?");
            $stmtItems->execute([$purchaseId]);
            $items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

            foreach ($items as $it) {
                $productId = (int)$it['product_id'];
                $qty = (int)$it['quantity'];
                $unitCost = (float)$it['unit_cost'];

                Inventory::recordMovement(
                    $productId,
                    'purchase',
                    $qty,
                    'purchase',
                    $purchaseId,
                    "Mapokezi ya Manunuzi #{$purchase['purchase_no']}",
                    $unitCost,
                    $userId,
                    $pdo
                );

                // Update product buying cost
                $stmtProd = $pdo->prepare("SELECT buying_price FROM `products` WHERE id = ?");
                $stmtProd->execute([$productId]);
                $oldCost = (float)$stmtProd->fetchColumn();

                if ($oldCost !== $unitCost && $unitCost > 0) {
                    $stmtCost = $pdo->prepare("
                        INSERT INTO `product_cost_history` (product_id, old_cost, new_cost, reason, reference_type, reference_id, changed_by, created_at)
                        VALUES (?, ?, ?, 'Manunuzi Yaliyopokelewa', 'purchase', ?, ?, NOW())
                    ");
                    $stmtCost->execute([$productId, $oldCost, $unitCost, $purchaseId, $userId]);

                    $stmtUpCost = $pdo->prepare("UPDATE `products` SET buying_price = ? WHERE id = ?");
                    $stmtUpCost->execute([$unitCost, $productId]);
                }
            }

            // Update status
            $stmtUp = $pdo->prepare("UPDATE `purchases` SET status = 'received', updated_at = NOW() WHERE id = ?");
            $stmtUp->execute([$purchaseId]);

            $stmtAudit = $pdo->prepare("
                INSERT INTO `audit_logs` (user_id, action, module, record_id, new_values, created_at)
                VALUES (?, 'STOCK_RECEIVING_COMPLETED', 'purchases', ?, ?, NOW())
            ");
            $stmtAudit->execute([$userId, $purchaseId, json_encode(['purchase_no' => $purchase['purchase_no'], 'items_count' => count($items)])]);

            $pdo->commit();
            return true;

        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    public static function find(int $id): ?array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT pu.*, s.name as supplier_name, s.phone as supplier_phone, usr.full_name as created_by_name
            FROM `purchases` pu
            JOIN `suppliers` s ON pu.supplier_id = s.id
            JOIN `users` usr ON pu.created_by = usr.id
            WHERE pu.id = ?
        ");
        $stmt->execute([$id]);
        $purchase = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$purchase) return null;

        $stmtItems = $pdo->prepare("
            SELECT pi.*, p.name as product_name, p.sku as product_sku, u.short_name as unit_short
            FROM `purchase_items` pi
            JOIN `products` p ON pi.product_id = p.id
            JOIN `units` u ON p.unit_id = u.id
            WHERE pi.purchase_id = ?
        ");
        $stmtItems->execute([$id]);
        $purchase['items'] = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

        return $purchase;
    }

    public static function all(array $filters = []): array {
        $pdo = Database::getConnection();
        $sql = "
            SELECT pu.*, s.name as supplier_name, usr.full_name as created_by_name,
                   (SELECT COUNT(*) FROM `purchase_items` WHERE purchase_id = pu.id) as items_count
            FROM `purchases` pu
            JOIN `suppliers` s ON pu.supplier_id = s.id
            JOIN `users` usr ON pu.created_by = usr.id
            ORDER BY pu.purchase_date DESC, pu.id DESC
        ";
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
