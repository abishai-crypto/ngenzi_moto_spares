<?php
/**
 * NGENZI MOTO SPARE PARTS
 * Inventory Model (Phase 5)
 * Comprehensive Stock Movement, Audit Trail, Valuation & Concurrency Protection Engine
 */

namespace App\Models;

use App\Core\Database;
use App\Core\Auth;
use App\Core\Helper;
use PDO;
use Exception;

class Inventory {

    /**
     * Record an immutable stock movement and update product quantity atomically
     * Uses InnoDB row-level locking (SELECT ... FOR UPDATE) to prevent concurrency races.
     */
    public static function recordMovement(
        int $productId,
        string $type,
        int $quantity,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $notes = null,
        ?float $unitCost = null,
        ?int $userId = null,
        ?PDO $externalPdo = null
    ): array {
        $pdo = $externalPdo ?? Database::getConnection();
        $isOwnTransaction = false;

        if (!$pdo->inTransaction()) {
            $pdo->beginTransaction();
            $isOwnTransaction = true;
        }

        try {
            if ($userId === null) {
                $userId = Auth::id() ?? 1;
            }

            // 1. Concurrency Row Lock: Fetch current product stock with FOR UPDATE
            $stmt = $pdo->prepare("
                SELECT id, name, sku, current_stock, buying_price 
                FROM `products` 
                WHERE id = ? 
                FOR UPDATE
            ");
            $stmt->execute([$productId]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$product) {
                throw new Exception("Kipuri (Product ID #{$productId}) hakikupatikana kwenye mfumo.");
            }

            $qtyBefore = (int)$product['current_stock'];
            $qtyAfter = $qtyBefore + $quantity;

            // 2. Strict Negative Stock Protection
            if ($qtyAfter < 0) {
                $available = max(0, $qtyBefore);
                throw new Exception("Huwezi kutoa idadi hii! Stoo haitoshi (Insufficient stock). Idadi iliyopo sasa: {$available}. Uliomba kutoa: " . abs($quantity) . ".");
            }

            if ($unitCost === null) {
                $unitCost = (float)$product['buying_price'];
            }

            // 3. Update Product stock quantity
            $stmtUpdate = $pdo->prepare("UPDATE `products` SET current_stock = ?, updated_at = NOW() WHERE id = ?");
            $stmtUpdate->execute([$qtyAfter, $productId]);

            // 4. Insert immutable Inventory Transaction record
            $stmtTx = $pdo->prepare("
                INSERT INTO `inventory_transactions` (
                    product_id, transaction_type, quantity, 
                    quantity_before, quantity_after, unit_cost, 
                    reference_type, reference_id, notes, 
                    created_by, created_at
                ) VALUES (
                    ?, ?, ?, 
                    ?, ?, ?, 
                    ?, ?, ?, 
                    ?, NOW()
                )
            ");

            $stmtTx->execute([
                $productId,
                $type,
                $quantity,
                $qtyBefore,
                $qtyAfter,
                $unitCost,
                $referenceType,
                $referenceId,
                $notes,
                $userId
            ]);

            $txId = (int)$pdo->lastInsertId();

            if ($isOwnTransaction) {
                $pdo->commit();
            }

            return [
                'id'              => $txId,
                'product_id'      => $productId,
                'transaction_type'=> $type,
                'quantity'        => $quantity,
                'quantity_before' => $qtyBefore,
                'quantity_after'  => $qtyAfter,
                'unit_cost'       => $unitCost,
                'reference_type'  => $referenceType,
                'reference_id'    => $referenceId,
                'notes'           => $notes,
                'created_by'      => $userId
            ];

        } catch (Exception $e) {
            if ($isOwnTransaction && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Stock Adjustment workflow
     * Supports: adjustment_in (+), adjustment_out (-), damage (-), loss (-), opening_stock
     */
    public static function adjustStock(
        int $productId,
        string $adjustmentType,
        int $quantity,
        string $reason,
        ?string $notes = null,
        ?int $userId = null
    ): array {
        $pdo = Database::getConnection();
        $pdo->beginTransaction();

        try {
            if ($quantity <= 0) {
                throw new Exception("Idadi ya marekebisho lazima iwe kubwa kuliko sifuri (greater than 0).");
            }

            $userId = $userId ?? Auth::id() ?? 1;

            // Map direction
            $signedQuantity = 0;
            $txType = $adjustmentType;

            switch ($adjustmentType) {
                case 'adjustment_in':
                    $signedQuantity = +$quantity;
                    break;

                case 'adjustment_out':
                    $signedQuantity = -$quantity;
                    break;

                case 'damage':
                    $signedQuantity = -$quantity;
                    break;

                case 'loss':
                    $signedQuantity = -$quantity;
                    break;

                case 'opening_stock':
                    $signedQuantity = +$quantity;
                    break;

                default:
                    throw new Exception("Aina ya marekebisho haitambuliki: {$adjustmentType}");
            }

            $fullNotes = trim("Sababu: {$reason}" . ($notes ? " | Maelezo: {$notes}" : ""));

            $result = self::recordMovement(
                $productId,
                $txType,
                $signedQuantity,
                'adjustment',
                null,
                $fullNotes,
                null,
                $userId,
                $pdo
            );

            // Audit log
            $stmtAudit = $pdo->prepare("
                INSERT INTO `audit_logs` (user_id, action, module, record_id, old_values, new_values, created_at)
                VALUES (?, 'STOCK_ADJUSTMENT', 'inventory', ?, ?, ?, NOW())
            ");
            $oldJson = json_encode(['quantity' => $result['quantity_before']]);
            $newJson = json_encode([
                'quantity'        => $result['quantity_after'],
                'adjustment_type' => $adjustmentType,
                'change'          => $signedQuantity,
                'reason'          => $reason,
                'notes'           => $notes
            ]);
            $stmtAudit->execute([$userId, $productId, $oldJson, $newJson]);

            $pdo->commit();
            return $result;

        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Deduct stock for POS/Sales (Phase 8 preparation)
     */
    public static function deductStockForSale(
        int $productId,
        int $quantity,
        ?int $saleId = null,
        ?int $userId = null,
        ?PDO $externalPdo = null
    ): array {
        if ($quantity <= 0) {
            throw new Exception("Idadi ya kuuza lazima iwe zaidi ya sifuri.");
        }

        return self::recordMovement(
            $productId,
            'sale',
            -$quantity,
            'sale',
            $saleId,
            $saleId ? "Mauzo ya risiti #{$saleId}" : "Mauzo ya bidhaa",
            null,
            $userId,
            $externalPdo
        );
    }

    /**
     * Get inventory movement history with filtering & pagination
     */
    public static function getHistory(array $filters = [], int $page = 1, int $perPage = 25): array {
        $pdo = Database::getConnection();

        $where = ["1=1"];
        $params = [];

        if (!empty($filters['product_id'])) {
            $where[] = "t.product_id = ?";
            $params[] = (int)$filters['product_id'];
        }

        if (!empty($filters['search'])) {
            $search = '%' . trim($filters['search']) . '%';
            $where[] = "(p.name LIKE ? OR p.sku LIKE ? OR p.barcode LIKE ?)";
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
        }

        if (!empty($filters['type'])) {
            $where[] = "t.transaction_type = ?";
            $params[] = $filters['type'];
        }

        if (!empty($filters['user_id'])) {
            $where[] = "t.created_by = ?";
            $params[] = (int)$filters['user_id'];
        }

        if (!empty($filters['date_from'])) {
            $where[] = "DATE(t.created_at) >= ?";
            $params[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $where[] = "DATE(t.created_at) <= ?";
            $params[] = $filters['date_to'];
        }

        if (!empty($filters['reference_type'])) {
            $where[] = "t.reference_type = ?";
            $params[] = $filters['reference_type'];
        }

        $whereClause = implode(" AND ", $where);

        // Count total
        $stmtCount = $pdo->prepare("
            SELECT COUNT(*) 
            FROM `inventory_transactions` t
            JOIN `products` p ON t.product_id = p.id
            WHERE {$whereClause}
        ");
        $stmtCount->execute($params);
        $total = (int)$stmtCount->fetchColumn();

        $totalPages = max(1, (int)ceil($total / $perPage));
        $offset = ($page - 1) * $perPage;

        // Fetch records
        $sql = "
            SELECT t.*, 
                   p.name as product_name, p.sku as product_sku, 
                   p.buying_price as current_buying_cost, p.buying_price as buying_cost,
                   p.current_stock as current_stock, p.current_stock as quantity,
                   c.name as category_name,
                   b.name as brand_name,
                   u.short_name as unit_short,
                   usr.full_name as user_name, usr.username as user_username
            FROM `inventory_transactions` t
            JOIN `products` p ON t.product_id = p.id
            JOIN `categories` c ON p.category_id = c.id
            LEFT JOIN `brands` b ON p.brand_id = b.id
            JOIN `units` u ON p.unit_id = u.id
            LEFT JOIN `users` usr ON t.created_by = usr.id
            WHERE {$whereClause}
            ORDER BY t.created_at DESC, t.id DESC
            LIMIT {$perPage} OFFSET {$offset}
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'data'         => $items,
            'total'        => $total,
            'page'         => $page,
            'per_page'     => $perPage,
            'total_pages'  => $totalPages
        ];
    }

    /**
     * Get stock timeline movements for a single product
     */
    public static function getProductHistory(int $productId, int $limit = 50): array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT t.*, 
                   usr.full_name as user_name, usr.username as user_username
            FROM `inventory_transactions` t
            LEFT JOIN `users` usr ON t.created_by = usr.id
            WHERE t.product_id = ?
            ORDER BY t.created_at DESC, t.id DESC
            LIMIT ?
        ");
        $stmt->bindValue(1, $productId, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Dashboard KPIs & Summary statistics
     */
    public static function getDashboardStats(): array {
        $pdo = Database::getConnection();

        // 1. Total active products
        $stmt = $pdo->query("SELECT COUNT(*) FROM `products` WHERE status = 'active'");
        $totalProducts = (int)$stmt->fetchColumn();

        // 2. Total units in stock & total valuation (DECIMAL arithmetic)
        $stmt = $pdo->query("
            SELECT 
                COALESCE(SUM(current_stock), 0) as total_units,
                COALESCE(SUM(current_stock * buying_price), 0.00) as total_cost_value,
                COALESCE(SUM(current_stock * selling_price), 0.00) as total_retail_value
            FROM `products`
            WHERE status = 'active'
        ");
        $stockTotals = $stmt->fetch(PDO::FETCH_ASSOC);

        // 3. Low stock count (current_stock <= min_stock_level AND current_stock > 0)
        $stmt = $pdo->query("
            SELECT COUNT(*) 
            FROM `products` 
            WHERE status = 'active' AND current_stock <= min_stock_level AND current_stock > 0
        ");
        $lowStockCount = (int)$stmt->fetchColumn();

        // 4. Out of stock count (current_stock = 0)
        $stmt = $pdo->query("
            SELECT COUNT(*) 
            FROM `products` 
            WHERE status = 'active' AND current_stock = 0
        ");
        $outOfStockCount = (int)$stmt->fetchColumn();

        // 5. Total adjustments this month
        $stmt = $pdo->query("
            SELECT COUNT(*) 
            FROM `inventory_transactions` 
            WHERE transaction_type IN ('adjustment', 'adjustment_in', 'adjustment_out', 'damage', 'loss', 'stock_count', 'stock_count_adjustment')
              AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
        $adjustmentsCount = (int)$stmt->fetchColumn();

        // 6. Recent 10 transactions
        $stmt = $pdo->query("
            SELECT t.*, p.name as product_name, p.sku as product_sku, usr.full_name as user_name
            FROM `inventory_transactions` t
            JOIN `products` p ON t.product_id = p.id
            LEFT JOIN `users` usr ON t.created_by = usr.id
            ORDER BY t.created_at DESC, t.id DESC
            LIMIT 10
        ");
        $recentTransactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'total_products'     => $totalProducts,
            'total_units'        => (int)$stockTotals['total_units'],
            'total_cost_value'   => (float)$stockTotals['total_cost_value'],
            'total_retail_value' => (float)$stockTotals['total_retail_value'],
            'potential_profit'   => (float)($stockTotals['total_retail_value'] - $stockTotals['total_cost_value']),
            'low_stock_count'    => $lowStockCount,
            'out_of_stock_count' => $outOfStockCount,
            'adjustments_count'  => $adjustmentsCount,
            'recent_movements'   => $recentTransactions
        ];
    }

    /**
     * Get products with Low Stock
     */
    public static function getLowStockProducts(int $limit = 100): array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT p.*, 
                   p.current_stock as quantity,
                   p.buying_price as buying_cost,
                   p.min_stock_level as minimum_stock_level,
                   c.name as category_name, 
                   b.name as brand_name, 
                   u.short_name as unit_short,
                   (
                       SELECT s.name 
                       FROM purchase_items pi 
                       JOIN purchases pu ON pi.purchase_id = pu.id 
                       JOIN suppliers s ON pu.supplier_id = s.id 
                       WHERE pi.product_id = p.id AND pu.status = 'received' 
                       ORDER BY pu.id DESC LIMIT 1
                   ) as last_supplier_name,
                   COALESCE(
                       (
                           SELECT pi.unit_cost 
                           FROM purchase_items pi 
                           JOIN purchases pu ON pi.purchase_id = pu.id 
                           WHERE pi.product_id = p.id AND pu.status = 'received' 
                           ORDER BY pu.id DESC LIMIT 1
                       ),
                       p.buying_price
                   ) as last_purchase_price
            FROM `products` p
            JOIN `categories` c ON p.category_id = c.id
            LEFT JOIN `brands` b ON p.brand_id = b.id
            JOIN `units` u ON p.unit_id = u.id
            WHERE p.status = 'active' AND p.current_stock <= p.min_stock_level AND p.current_stock > 0
            ORDER BY (p.current_stock / p.min_stock_level) ASC, p.current_stock ASC
            LIMIT ?
        ");
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get products that are Out of Stock (current_stock = 0)
     */
    public static function getOutOfStockProducts(int $limit = 100): array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT p.*, 
                   p.current_stock as quantity,
                   p.buying_price as buying_cost,
                   p.min_stock_level as minimum_stock_level,
                   c.name as category_name, 
                   b.name as brand_name, 
                   u.short_name as unit_short,
                   (
                       SELECT s.name 
                       FROM purchase_items pi 
                       JOIN purchases pu ON pi.purchase_id = pu.id 
                       JOIN suppliers s ON pu.supplier_id = s.id 
                       WHERE pi.product_id = p.id AND pu.status = 'received' 
                       ORDER BY pu.id DESC LIMIT 1
                   ) as last_supplier_name,
                   COALESCE(
                       (
                           SELECT pi.unit_cost 
                           FROM purchase_items pi 
                           JOIN purchases pu ON pi.purchase_id = pu.id 
                           WHERE pi.product_id = p.id AND pu.status = 'received' 
                           ORDER BY pu.id DESC LIMIT 1
                       ),
                       p.buying_price
                   ) as last_purchase_price,
                   (
                       SELECT MAX(sa.sale_date) 
                       FROM sale_items si 
                       JOIN sales sa ON si.sale_id = sa.id 
                       WHERE si.product_id = p.id
                   ) as last_sale_date
            FROM `products` p
            JOIN `categories` c ON p.category_id = c.id
            LEFT JOIN `brands` b ON p.brand_id = b.id
            JOIN `units` u ON p.unit_id = u.id
            WHERE p.status = 'active' AND p.current_stock = 0
            ORDER BY p.name ASC
            LIMIT ?
        ");
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Reorder Recommendations
     * Products where current_stock <= reorder_level
     */
    public static function getReorderSuggestions(): array {
        $pdo = Database::getConnection();
        $stmt = $pdo->query("
            SELECT p.*, 
                   p.current_stock as quantity,
                   p.buying_price as buying_cost,
                   p.min_stock_level as minimum_stock_level,
                   c.name as category_name, 
                   b.name as brand_name, 
                   u.short_name as unit_short,
                   (
                       SELECT s.name 
                       FROM purchase_items pi 
                       JOIN purchases pu ON pi.purchase_id = pu.id 
                       JOIN suppliers s ON pu.supplier_id = s.id 
                       WHERE pi.product_id = p.id AND pu.status = 'received' 
                       ORDER BY pu.id DESC LIMIT 1
                   ) as preferred_supplier_name,
                   COALESCE(
                       (
                           SELECT pi.unit_cost 
                           FROM purchase_items pi 
                           JOIN purchases pu ON pi.purchase_id = pu.id 
                           WHERE pi.product_id = p.id AND pu.status = 'received' 
                           ORDER BY pu.id DESC LIMIT 1
                       ),
                       p.buying_price
                   ) as last_purchase_cost,
                   CASE 
                       WHEN p.reorder_level > 0 THEN GREATEST((p.reorder_level * 2) - p.current_stock, p.reorder_level)
                       ELSE GREATEST((p.min_stock_level * 2) - p.current_stock, 10)
                   END as suggested_reorder_qty
            FROM `products` p
            JOIN `categories` c ON p.category_id = c.id
            LEFT JOIN `brands` b ON p.brand_id = b.id
            JOIN `units` u ON p.unit_id = u.id
            WHERE p.status = 'active' AND p.current_stock <= p.reorder_level
            ORDER BY p.current_stock ASC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Inventory Valuation Report
     */
    public static function getValuationReport(?int $categoryId = null, ?int $brandId = null): array {
        $pdo = Database::getConnection();

        $where = ["p.status = 'active'"];
        $params = [];

        if ($categoryId) {
            $where[] = "p.category_id = ?";
            $params[] = $categoryId;
        }

        if ($brandId) {
            $where[] = "p.brand_id = ?";
            $params[] = $brandId;
        }

        $whereClause = implode(" AND ", $where);

        $sql = "
            SELECT p.id, p.sku, p.name, 
                   p.current_stock as quantity, p.current_stock,
                   p.min_stock_level as minimum_stock_level,
                   p.buying_price as buying_cost, p.buying_price, p.selling_price,
                   (p.current_stock * p.buying_price) as total_cost_value,
                   (p.current_stock * p.selling_price) as total_selling_value,
                   ((p.current_stock * p.selling_price) - (p.current_stock * p.buying_price)) as potential_profit,
                   c.name as category_name,
                   b.name as brand_name,
                   u.short_name as unit_short
            FROM `products` p
            JOIN `categories` c ON p.category_id = c.id
            LEFT JOIN `brands` b ON p.brand_id = b.id
            JOIN `units` u ON p.unit_id = u.id
            WHERE {$whereClause}
            ORDER BY total_cost_value DESC
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Compute summary totals with exact arithmetic
        $totalUnits = 0;
        $totalCostVal = 0.00;
        $totalRetailVal = 0.00;

        foreach ($products as $p) {
            $totalUnits += (int)$p['current_stock'];
            $totalCostVal += (float)$p['total_cost_value'];
            $totalRetailVal += (float)$p['total_selling_value'];
        }

        return [
            'products'           => $products,
            'total_products'     => count($products),
            'total_units'        => $totalUnits,
            'total_cost_value'   => $totalCostVal,
            'total_retail_value' => $totalRetailVal,
            'potential_profit'   => ($totalRetailVal - $totalCostVal),
            'overall_margin'     => $totalRetailVal > 0 ? round((($totalRetailVal - $totalCostVal) / $totalRetailVal) * 100, 2) : 0.00
        ];
    }

    /**
     * Stock Movement Analytics
     * Fast-Moving, Slow-Moving, and Dead Stock
     */
    public static function getMovementAnalytics(string $period = '30d'): array {
        $pdo = Database::getConnection();

        $days = 30;
        if ($period === '7d') $days = 7;
        elseif ($period === '90d') $days = 90;

        // 1. Fast Moving: highest sales quantity within selected period
        $stmtFast = $pdo->prepare("
            SELECT p.id, p.sku, p.name, 
                   p.current_stock, p.current_stock as quantity,
                   c.name as category_name, b.name as brand_name, u.short_name as unit_short,
                   COALESCE(ABS(SUM(t.quantity)), 0) as total_sold,
                   COUNT(t.id) as sales_count,
                   MAX(t.created_at) as last_sale_date
            FROM `products` p
            JOIN `inventory_transactions` t ON p.id = t.product_id
            JOIN `categories` c ON p.category_id = c.id
            LEFT JOIN `brands` b ON p.brand_id = b.id
            JOIN `units` u ON p.unit_id = u.id
            WHERE t.transaction_type = 'sale'
              AND t.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
              AND p.status = 'active'
            GROUP BY p.id
            ORDER BY total_sold DESC
            LIMIT 15
        ");
        $stmtFast->execute([$days]);
        $fastMoving = $stmtFast->fetchAll(PDO::FETCH_ASSOC);

        // 2. Slow Moving: sales between 1 and 3 items within selected period
        $stmtSlow = $pdo->prepare("
            SELECT p.id, p.sku, p.name, 
                   p.current_stock, p.current_stock as quantity,
                   c.name as category_name, b.name as brand_name, u.short_name as unit_short,
                   COALESCE(ABS(SUM(t.quantity)), 0) as total_sold,
                   COUNT(t.id) as sales_count,
                   MAX(t.created_at) as last_sale_date
            FROM `products` p
            JOIN `inventory_transactions` t ON p.id = t.product_id
            JOIN `categories` c ON p.category_id = c.id
            LEFT JOIN `brands` b ON p.brand_id = b.id
            JOIN `units` u ON p.unit_id = u.id
            WHERE t.transaction_type = 'sale'
              AND t.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
              AND p.status = 'active'
            GROUP BY p.id
            HAVING total_sold <= 3
            ORDER BY total_sold ASC, p.current_stock DESC
            LIMIT 15
        ");
        $stmtSlow->execute([$days]);
        $slowMoving = $stmtSlow->fetchAll(PDO::FETCH_ASSOC);

        // 3. Dead Stock: positive stock (current_stock > 0) with ZERO sales in the period
        $stmtDead = $pdo->prepare("
            SELECT p.id, p.sku, p.name, 
                   p.current_stock, p.current_stock as quantity,
                   p.buying_price, p.buying_price as buying_cost,
                   (p.current_stock * p.buying_price) as tied_capital,
                   c.name as category_name, b.name as brand_name, u.short_name as unit_short,
                   (
                       SELECT MAX(created_at) 
                       FROM `inventory_transactions` 
                       WHERE product_id = p.id AND transaction_type = 'sale'
                   ) as last_sale_date
            FROM `products` p
            JOIN `categories` c ON p.category_id = c.id
            LEFT JOIN `brands` b ON p.brand_id = b.id
            JOIN `units` u ON p.unit_id = u.id
            WHERE p.status = 'active'
              AND p.current_stock > 0
              AND p.id NOT IN (
                  SELECT DISTINCT product_id 
                  FROM `inventory_transactions` 
                  WHERE transaction_type = 'sale' 
                    AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
              )
            ORDER BY tied_capital DESC
            LIMIT 20
        ");
        $stmtDead->execute([$days]);
        $deadStock = $stmtDead->fetchAll(PDO::FETCH_ASSOC);

        return [
            'period'       => $period,
            'days'         => $days,
            'fast_moving'  => $fastMoving,
            'slow_moving'  => $slowMoving,
            'dead_stock'   => $deadStock
        ];
    }
}
