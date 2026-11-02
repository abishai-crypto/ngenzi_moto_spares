<?php
/**
 * NGENZI MOTO SPARE PARTS
 * Financial & Profit Management Model (Phase 10)
 * Handles Real-time Profit Calculations, COGS (Historical Cost at Sale),
 * Gross & Net Profit, Margin % (Zero-Division Safe), Expense Analysis,
 * Cash Flow vs Revenue, Period Comparisons & Multi-Dimensional Profitability.
 */

namespace App\Models;

use App\Core\Database;
use App\Core\Auth;
use App\Core\Helper;
use PDO;
use Exception;

class Financial {

    /**
     * Resolve date ranges from period strings
     */
    public static function resolveDateRange(string $period = 'this_month', ?string $customStart = null, ?string $customEnd = null): array {
        $today = date('Y-m-d');
        
        switch ($period) {
            case 'today':
                $start = $today;
                $end = $today;
                $prevStart = date('Y-m-d', strtotime('-1 day'));
                $prevEnd = date('Y-m-d', strtotime('-1 day'));
                $label = 'Leo (' . date('d/m/Y') . ')';
                $prevLabel = 'Jana (' . date('d/m/Y', strtotime('-1 day')) . ')';
                break;

            case 'yesterday':
                $start = date('Y-m-d', strtotime('-1 day'));
                $end = date('Y-m-d', strtotime('-1 day'));
                $prevStart = date('Y-m-d', strtotime('-2 days'));
                $prevEnd = date('Y-m-d', strtotime('-2 days'));
                $label = 'Jana (' . date('d/m/Y', strtotime('-1 day')) . ')';
                $prevLabel = 'Juzi (' . date('d/m/Y', strtotime('-2 days')) . ')';
                break;

            case 'this_week':
                $start = date('Y-m-d', strtotime('monday this week'));
                $end = date('Y-m-d', strtotime('sunday this week'));
                $prevStart = date('Y-m-d', strtotime('monday last week'));
                $prevEnd = date('Y-m-d', strtotime('sunday last week'));
                $label = 'Wiki Hii (' . date('d/m', strtotime($start)) . ' - ' . date('d/m/Y', strtotime($end)) . ')';
                $prevLabel = 'Wiki Iliyopita';
                break;

            case 'last_week':
                $start = date('Y-m-d', strtotime('monday last week'));
                $end = date('Y-m-d', strtotime('sunday last week'));
                $prevStart = date('Y-m-d', strtotime('monday -2 weeks'));
                $prevEnd = date('Y-m-d', strtotime('sunday -2 weeks'));
                $label = 'Wiki Iliyopita';
                $prevLabel = 'Wiki 2 Zilizopita';
                break;

            case 'this_month':
                $start = date('Y-m-01');
                $end = date('Y-m-t');
                $prevStart = date('Y-m-01', strtotime('first day of last month'));
                $prevEnd = date('Y-m-t', strtotime('last day of last month'));
                $label = 'Mwezi Huu (' . date('F Y') . ')';
                $prevLabel = 'Mwezi Uliopita (' . date('F Y', strtotime('last month')) . ')';
                break;

            case 'last_month':
                $start = date('Y-m-01', strtotime('first day of last month'));
                $end = date('Y-m-t', strtotime('last day of last month'));
                $prevStart = date('Y-m-01', strtotime('first day of -2 months'));
                $prevEnd = date('Y-m-t', strtotime('last day of -2 months'));
                $label = 'Mwezi Uliopita (' . date('F Y', strtotime('last month')) . ')';
                $prevLabel = 'Miezi 2 Iliyopita';
                break;

            case 'this_quarter':
                $currentMonth = (int)date('n');
                $quarter = ceil($currentMonth / 3);
                $startMonth = ($quarter - 1) * 3 + 1;
                $start = date('Y-' . sprintf('%02d', $startMonth) . '-01');
                $endMonth = $quarter * 3;
                $end = date('Y-' . sprintf('%02d', $endMonth) . '-t');
                
                $prevStartMonth = $startMonth - 3;
                $prevYear = (int)date('Y');
                if ($prevStartMonth < 1) {
                    $prevStartMonth += 12;
                    $prevYear--;
                }
                $prevEndMonth = $prevStartMonth + 2;
                $prevStart = sprintf('%04d-%02d-01', $prevYear, $prevStartMonth);
                $prevEnd = date('Y-m-t', strtotime(sprintf('%04d-%02d-01', $prevYear, $prevEndMonth)));

                $label = "Robo Mwaka Hii (Q{$quarter} " . date('Y') . ")";
                $prevLabel = "Robo Mwaka Iliyopita";
                break;

            case 'this_year':
                $start = date('Y-01-01');
                $end = date('Y-12-31');
                $prevYear = (int)date('Y') - 1;
                $prevStart = "{$prevYear}-01-01";
                $prevEnd = "{$prevYear}-12-31";
                $label = 'Mwaka Huu (' . date('Y') . ')';
                $prevLabel = 'Mwaka Jana (' . $prevYear . ')';
                break;

            case 'custom':
            default:
                $start = !empty($customStart) && strtotime($customStart) ? $customStart : date('Y-m-01');
                $end = !empty($customEnd) && strtotime($customEnd) ? $customEnd : date('Y-m-d');
                $days = (strtotime($end) - strtotime($start)) / 86400;
                $days = max(1, (int)$days);
                $prevEnd = date('Y-m-d', strtotime($start . ' -1 day'));
                $prevStart = date('Y-m-d', strtotime($prevEnd . " -{$days} days"));
                $label = date('d/m/Y', strtotime($start)) . ' - ' . date('d/m/Y', strtotime($end));
                $prevLabel = 'Kipindi Kilichopita (' . date('d/m/Y', strtotime($prevStart)) . ' - ' . date('d/m/Y', strtotime($prevEnd)) . ')';
                break;
        }

        return [
            'period' => $period,
            'start_date' => $start,
            'end_date' => $end,
            'label' => $label,
            'prev_start_date' => $prevStart,
            'prev_end_date' => $prevEnd,
            'prev_label' => $prevLabel
        ];
    }

    /**
     * Get Complete Executive Financial Summary for a specific Date Range
     */
    public static function getExecutiveSummary(string $startDate, string $endDate): array {
        $pdo = Database::getConnection();

        // 1. Completed Sales Revenue & COGS
        // Exclude cancelled sales. Include completed and partially/fully returned sales with proper deductions
        $saleSql = "
            SELECT 
                COUNT(id) AS completed_sales_count,
                COALESCE(SUM(subtotal), 0.00) AS gross_sales,
                COALESCE(SUM(discount_amount), 0.00) AS total_discounts,
                COALESCE(SUM(grand_total), 0.00) AS net_sales_revenue,
                COALESCE(SUM(paid_amount), 0.00) AS sales_cash_collected,
                COALESCE(SUM(balance_due), 0.00) AS accounts_receivable,
                COALESCE(SUM(total_cost), 0.00) AS cogs_from_sales,
                COALESCE(SUM(gross_profit), 0.00) AS gross_profit_from_sales
            FROM `sales`
            WHERE status NOT IN ('cancelled', 'draft')
              AND DATE(sale_date) BETWEEN ? AND ?
        ";
        $saleStmt = $pdo->prepare($saleSql);
        $saleStmt->execute([$startDate, $endDate]);
        $salesMetrics = $saleStmt->fetch(PDO::FETCH_ASSOC);

        $grossSales = (float)$salesMetrics['gross_sales'];
        $discounts = (float)$salesMetrics['total_discounts'];
        $netSales = (float)$salesMetrics['net_sales_revenue'];
        $salesCount = (int)$salesMetrics['completed_sales_count'];
        $salesPaid = (float)$salesMetrics['sales_cash_collected'];
        $accountsReceivable = (float)$salesMetrics['accounts_receivable'];

        // Compute COGS precisely from sale_items to guarantee historical cost preservation
        $cogsSql = "
            SELECT 
                COALESCE(SUM(si.quantity * si.unit_cost), 0.00) AS exact_cogs,
                COALESCE(SUM(si.quantity), 0) AS total_units_sold
            FROM `sale_items` si
            JOIN `sales` s ON si.sale_id = s.id
            WHERE s.status NOT IN ('cancelled', 'draft')
              AND DATE(s.sale_date) BETWEEN ? AND ?
        ";
        $cogsStmt = $pdo->prepare($cogsSql);
        $cogsStmt->execute([$startDate, $endDate]);
        $cogsData = $cogsStmt->fetch(PDO::FETCH_ASSOC);

        $cogs = (float)$cogsData['exact_cogs'];
        $totalUnitsSold = (int)$cogsData['total_units_sold'];

        // Deduct approved returns in the period if Returns table exists
        $refundedAmount = 0.00;
        $restockedCogsRefund = 0.00;
        try {
            $retStmt = $pdo->prepare("
                SELECT 
                    COALESCE(SUM(r.refund_amount), 0.00) AS total_refunded,
                    COALESCE(SUM(ri.quantity * ri.unit_cost), 0.00) AS total_returned_cogs
                FROM `returns` r
                LEFT JOIN `return_items` ri ON r.id = ri.return_id
                WHERE r.status = 'approved'
                  AND DATE(r.created_at) BETWEEN ? AND ?
            ");
            $retStmt->execute([$startDate, $endDate]);
            $retData = $retStmt->fetch(PDO::FETCH_ASSOC);
            if ($retData) {
                $refundedAmount = (float)$retData['total_refunded'];
                $restockedCogsRefund = (float)$retData['total_returned_cogs'];
            }
        } catch (Exception $e) {
            // Returns table might not exist yet or no returns
            $refundedAmount = 0.00;
            $restockedCogsRefund = 0.00;
        }

        $netSalesEffective = max(0.00, $netSales - $refundedAmount);
        $cogsEffective = max(0.00, $cogs - $restockedCogsRefund);
        $grossProfit = $netSalesEffective - $cogsEffective;
        $grossMarginPercent = ($netSalesEffective > 0) ? round(($grossProfit / $netSalesEffective) * 100, 2) : 0.00;

        // 2. Operating Expenses
        $expSql = "
            SELECT 
                COUNT(id) AS expense_count,
                COALESCE(SUM(amount), 0.00) AS total_operating_expenses
            FROM `expenses`
            WHERE status IN ('recorded', 'approved')
              AND expense_date BETWEEN ? AND ?
        ";
        $expStmt = $pdo->prepare($expSql);
        $expStmt->execute([$startDate, $endDate]);
        $expData = $expStmt->fetch(PDO::FETCH_ASSOC);

        $operatingExpenses = (float)$expData['total_operating_expenses'];
        $expenseCount = (int)$expData['expense_count'];

        // 3. Net Profit & Net Margin %
        $netProfit = $grossProfit - $operatingExpenses;
        $netMarginPercent = ($netSalesEffective > 0) ? round(($netProfit / $netSalesEffective) * 100, 2) : 0.00;

        // 4. Actual Cash Flow / Collections
        // Actual payments received across all payment methods in this period
        $paySql = "
            SELECT 
                COALESCE(SUM(amount), 0.00) AS total_payments_received
            FROM `sale_payments`
            WHERE DATE(created_at) BETWEEN ? AND ?
        ";
        $payStmt = $pdo->prepare($paySql);
        $payStmt->execute([$startDate, $endDate]);
        $totalPaymentsReceived = (float)$payStmt->fetchColumn();

        // 5. Current Inventory Valuation (Asset, not expense!)
        $invSql = "
            SELECT 
                COALESCE(SUM(current_stock), 0) AS total_stock_units,
                COALESCE(SUM(current_stock * buying_price), 0.00) AS total_inventory_value,
                COALESCE(SUM(current_stock * selling_price), 0.00) AS total_retail_value
            FROM `products`
            WHERE status = 'active'
        ";
        $invStmt = $pdo->query($invSql);
        $invData = $invStmt->fetch(PDO::FETCH_ASSOC);
        $currentInventoryValuation = (float)$invData['total_inventory_value'];
        $totalStockUnits = (int)$invData['total_stock_units'];

        // Average sale value
        $avgSaleValue = ($salesCount > 0) ? round($netSales / $salesCount, 2) : 0.00;

        return [
            'start_date'            => $startDate,
            'end_date'              => $endDate,
            'sales_count'           => $salesCount,
            'total_units_sold'      => $totalUnitsSold,
            'gross_sales'           => $grossSales,
            'total_discounts'       => $discounts,
            'net_sales'             => $netSalesEffective,
            'refunded_amount'       => $refundedAmount,
            'cogs'                  => $cogsEffective,
            'gross_profit'          => $grossProfit,
            'gross_margin_percent'  => $grossMarginPercent,
            'operating_expenses'    => $operatingExpenses,
            'expense_count'         => $expenseCount,
            'net_profit'            => $netProfit,
            'net_margin_percent'    => $netMarginPercent,
            'total_payments_received'=> $totalPaymentsReceived,
            'accounts_receivable'   => $accountsReceivable,
            'avg_sale_value'        => $avgSaleValue,
            'inventory_valuation'   => $currentInventoryValuation,
            'total_stock_units'     => $totalStockUnits
        ];
    }

    /**
     * Compare Financial Performance between Current Period and Previous Period
     */
    public static function getPeriodComparison(string $currentStart, string $currentEnd, string $prevStart, string $prevEnd): array {
        $current = self::getExecutiveSummary($currentStart, $currentEnd);
        $previous = self::getExecutiveSummary($prevStart, $prevEnd);

        $calculateDiff = function(float $curr, float $prev): array {
            $diff = $curr - $prev;
            $percent = ($prev != 0.00) ? round(($diff / abs($prev)) * 100, 2) : (($curr > 0) ? 100.00 : 0.00);
            return [
                'current' => $curr,
                'previous' => $prev,
                'diff' => $diff,
                'percentage' => $percent,
                'direction' => ($diff > 0) ? 'up' : (($diff < 0) ? 'down' : 'flat')
            ];
        };

        return [
            'current'           => $current,
            'previous'          => $previous,
            'net_sales'         => $calculateDiff($current['net_sales'], $previous['net_sales']),
            'cogs'              => $calculateDiff($current['cogs'], $previous['cogs']),
            'gross_profit'      => $calculateDiff($current['gross_profit'], $previous['gross_profit']),
            'gross_margin'      => [
                'current' => $current['gross_margin_percent'],
                'previous' => $previous['gross_margin_percent'],
                'diff' => round($current['gross_margin_percent'] - $previous['gross_margin_percent'], 2)
            ],
            'operating_expenses'=> $calculateDiff($current['operating_expenses'], $previous['operating_expenses']),
            'net_profit'        => $calculateDiff($current['net_profit'], $previous['net_profit']),
            'net_margin'        => [
                'current' => $current['net_margin_percent'],
                'previous' => $previous['net_margin_percent'],
                'diff' => round($current['net_margin_percent'] - $previous['net_margin_percent'], 2)
            ],
            'sales_count'       => $calculateDiff($current['sales_count'], $previous['sales_count'])
        ];
    }

    /**
     * Product Profitability Analysis
     */
    public static function getProductProfitability(array $filters = []): array {
        $pdo = Database::getConnection();

        $sql = "
            SELECT 
                p.id AS product_id,
                p.sku,
                p.name AS product_name,
                p.part_number,
                c.name AS category_name,
                b.name AS brand_name,
                COALESCE(SUM(si.quantity), 0) AS total_qty_sold,
                COALESCE(SUM(si.subtotal), 0.00) AS total_revenue,
                COALESCE(SUM(si.quantity * si.unit_cost), 0.00) AS total_cogs,
                COALESCE(SUM(si.subtotal - (si.quantity * si.unit_cost)), 0.00) AS gross_profit
            FROM `sale_items` si
            JOIN `sales` s ON si.sale_id = s.id
            JOIN `products` p ON si.product_id = p.id
            LEFT JOIN `categories` c ON p.category_id = c.id
            LEFT JOIN `brands` b ON p.brand_id = b.id
            WHERE s.status NOT IN ('cancelled', 'draft')
        ";
        $params = [];

        if (!empty($filters['date_from'])) {
            $sql .= " AND DATE(s.sale_date) >= ?";
            $params[] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $sql .= " AND DATE(s.sale_date) <= ?";
            $params[] = $filters['date_to'];
        }
        if (!empty($filters['category_id'])) {
            $sql .= " AND p.category_id = ?";
            $params[] = (int)$filters['category_id'];
        }
        if (!empty($filters['brand_id'])) {
            $sql .= " AND p.brand_id = ?";
            $params[] = (int)$filters['brand_id'];
        }
        if (!empty($filters['search'])) {
            $sql .= " AND (p.name LIKE ? OR p.sku LIKE ? OR p.part_number LIKE ?)";
            $term = "%{$filters['search']}%";
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
        }

        $sql .= "
            GROUP BY p.id, p.sku, p.name, p.part_number, c.name, b.name
            HAVING total_qty_sold > 0
            ORDER BY gross_profit DESC
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($items as &$item) {
            $rev = (float)$item['total_revenue'];
            $profit = (float)$item['gross_profit'];
            $item['margin_percent'] = ($rev > 0) ? round(($profit / $rev) * 100, 2) : 0.00;
        }

        return $items;
    }

    /**
     * Category Profitability Analysis
     */
    public static function getCategoryProfitability(array $filters = []): array {
        $pdo = Database::getConnection();

        $sql = "
            SELECT 
                c.id AS category_id,
                COALESCE(c.name, 'Bila Kategoria') AS category_name,
                COUNT(DISTINCT si.product_id) AS products_count,
                COALESCE(SUM(si.quantity), 0) AS total_qty_sold,
                COALESCE(SUM(si.subtotal), 0.00) AS total_revenue,
                COALESCE(SUM(si.quantity * si.unit_cost), 0.00) AS total_cogs,
                COALESCE(SUM(si.subtotal - (si.quantity * si.unit_cost)), 0.00) AS gross_profit
            FROM `sale_items` si
            JOIN `sales` s ON si.sale_id = s.id
            JOIN `products` p ON si.product_id = p.id
            LEFT JOIN `categories` c ON p.category_id = c.id
            WHERE s.status NOT IN ('cancelled', 'draft')
        ";
        $params = [];

        if (!empty($filters['date_from'])) {
            $sql .= " AND DATE(s.sale_date) >= ?";
            $params[] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $sql .= " AND DATE(s.sale_date) <= ?";
            $params[] = $filters['date_to'];
        }

        $sql .= "
            GROUP BY c.id, c.name
            HAVING total_qty_sold > 0
            ORDER BY gross_profit DESC
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($items as &$item) {
            $rev = (float)$item['total_revenue'];
            $profit = (float)$item['gross_profit'];
            $item['margin_percent'] = ($rev > 0) ? round(($profit / $rev) * 100, 2) : 0.00;
        }

        return $items;
    }

    /**
     * Brand Profitability Analysis
     */
    public static function getBrandProfitability(array $filters = []): array {
        $pdo = Database::getConnection();

        $sql = "
            SELECT 
                b.id AS brand_id,
                COALESCE(b.name, 'Bila Chapa') AS brand_name,
                COUNT(DISTINCT si.product_id) AS products_count,
                COALESCE(SUM(si.quantity), 0) AS total_qty_sold,
                COALESCE(SUM(si.subtotal), 0.00) AS total_revenue,
                COALESCE(SUM(si.quantity * si.unit_cost), 0.00) AS total_cogs,
                COALESCE(SUM(si.subtotal - (si.quantity * si.unit_cost)), 0.00) AS gross_profit
            FROM `sale_items` si
            JOIN `sales` s ON si.sale_id = s.id
            JOIN `products` p ON si.product_id = p.id
            LEFT JOIN `brands` b ON p.brand_id = b.id
            WHERE s.status NOT IN ('cancelled', 'draft')
        ";
        $params = [];

        if (!empty($filters['date_from'])) {
            $sql .= " AND DATE(s.sale_date) >= ?";
            $params[] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $sql .= " AND DATE(s.sale_date) <= ?";
            $params[] = $filters['date_to'];
        }

        $sql .= "
            GROUP BY b.id, b.name
            HAVING total_qty_sold > 0
            ORDER BY gross_profit DESC
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($items as &$item) {
            $rev = (float)$item['total_revenue'];
            $profit = (float)$item['gross_profit'];
            $item['margin_percent'] = ($rev > 0) ? round(($profit / $rev) * 100, 2) : 0.00;
        }

        return $items;
    }

    /**
     * Vehicle Model Compatibility Profitability Analysis
     * Calculates sales grouped by compatible vehicle models without double counting product items
     */
    public static function getVehicleCompatibilityProfitability(array $filters = []): array {
        $pdo = Database::getConnection();

        $sql = "
            SELECT 
                vm.id AS model_id,
                CONCAT(vb.name, ' ', vm.name) AS vehicle_model,
                vm.vehicle_type,
                COUNT(DISTINCT si.product_id) AS compatible_products_sold,
                COALESCE(SUM(si.quantity), 0) AS total_qty_sold,
                COALESCE(SUM(si.subtotal), 0.00) AS total_revenue,
                COALESCE(SUM(si.quantity * si.unit_cost), 0.00) AS total_cogs,
                COALESCE(SUM(si.subtotal - (si.quantity * si.unit_cost)), 0.00) AS gross_profit
            FROM `product_vehicle_compatibilities` pvc
            JOIN `vehicle_models` vm ON pvc.vehicle_model_id = vm.id
            JOIN `vehicle_brands` vb ON vm.vehicle_brand_id = vb.id
            JOIN `sale_items` si ON pvc.product_id = si.product_id
            JOIN `sales` s ON si.sale_id = s.id
            WHERE s.status NOT IN ('cancelled', 'draft')
        ";
        $params = [];

        if (!empty($filters['date_from'])) {
            $sql .= " AND DATE(s.sale_date) >= ?";
            $params[] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $sql .= " AND DATE(s.sale_date) <= ?";
            $params[] = $filters['date_to'];
        }

        $sql .= "
            GROUP BY vm.id, vb.name, vm.name, vm.vehicle_type
            HAVING total_qty_sold > 0
            ORDER BY gross_profit DESC
        ";

        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($items as &$item) {
                $rev = (float)$item['total_revenue'];
                $profit = (float)$item['gross_profit'];
                $item['margin_percent'] = ($rev > 0) ? round(($profit / $rev) * 100, 2) : 0.00;
            }

            return $items;
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Profit by Individual Sale / Invoice
     */
    public static function getSaleProfitability(array $filters = [], int $page = 1, int $perPage = 25): array {
        $pdo = Database::getConnection();
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;

        $sql = "
            SELECT 
                s.id,
                s.invoice_no,
                s.sale_date,
                s.subtotal AS gross_sales,
                s.discount_amount,
                s.grand_total AS net_revenue,
                s.total_cost AS cogs,
                s.gross_profit,
                s.payment_status,
                s.status,
                u.full_name AS cashier_name,
                c.name AS customer_name
            FROM `sales` s
            LEFT JOIN `users` u ON s.user_id = u.id
            LEFT JOIN `customers` c ON s.customer_id = c.id
            WHERE s.status NOT IN ('cancelled', 'draft')
        ";
        $countSql = "
            SELECT COUNT(*) 
            FROM `sales` s 
            LEFT JOIN `customers` c ON s.customer_id = c.id
            WHERE s.status NOT IN ('cancelled', 'draft')
        ";

        $params = [];

        if (!empty($filters['search'])) {
            $term = "%{$filters['search']}%";
            $clause = " AND (s.invoice_no LIKE ? OR c.name LIKE ? OR c.phone LIKE ?)";
            $sql .= $clause;
            $countSql .= $clause;
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
        }
        if (!empty($filters['date_from'])) {
            $clause = " AND DATE(s.sale_date) >= ?";
            $sql .= $clause;
            $countSql .= $clause;
            $params[] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $clause = " AND DATE(s.sale_date) <= ?";
            $sql .= $clause;
            $countSql .= $clause;
            $params[] = $filters['date_to'];
        }
        if (!empty($filters['user_id'])) {
            $clause = " AND s.user_id = ?";
            $sql .= $clause;
            $countSql .= $clause;
            $params[] = (int)$filters['user_id'];
        }

        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute($params);
        $totalRecords = (int)$countStmt->fetchColumn();

        $sql .= " ORDER BY s.sale_date DESC, s.id DESC LIMIT {$perPage} OFFSET {$offset}";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $sales = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($sales as &$s) {
            $rev = (float)$s['net_revenue'];
            $profit = (float)$s['gross_profit'];
            $s['margin_percent'] = ($rev > 0) ? round(($profit / $rev) * 100, 2) : 0.00;
        }

        return [
            'data' => $sales,
            'total' => $totalRecords,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => ceil($totalRecords / $perPage)
        ];
    }

    /**
     * Profit by Cashier
     */
    public static function getCashierProfitability(array $filters = []): array {
        $pdo = Database::getConnection();

        $sql = "
            SELECT 
                u.id AS cashier_id,
                u.full_name AS cashier_name,
                u.username,
                COUNT(s.id) AS sales_count,
                COALESCE(SUM(s.grand_total), 0.00) AS total_revenue,
                COALESCE(SUM(s.total_cost), 0.00) AS total_cogs,
                COALESCE(SUM(s.gross_profit), 0.00) AS gross_profit
            FROM `sales` s
            JOIN `users` u ON s.user_id = u.id
            WHERE s.status NOT IN ('cancelled', 'draft')
        ";
        $params = [];

        if (!empty($filters['date_from'])) {
            $sql .= " AND DATE(s.sale_date) >= ?";
            $params[] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $sql .= " AND DATE(s.sale_date) <= ?";
            $params[] = $filters['date_to'];
        }

        $sql .= "
            GROUP BY u.id, u.full_name, u.username
            ORDER BY gross_profit DESC
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($items as &$item) {
            $rev = (float)$item['total_revenue'];
            $profit = (float)$item['gross_profit'];
            $item['margin_percent'] = ($rev > 0) ? round(($profit / $rev) * 100, 2) : 0.00;
        }

        return $items;
    }

    /**
     * Profit by Customer
     */
    public static function getCustomerProfitability(array $filters = []): array {
        $pdo = Database::getConnection();

        $sql = "
            SELECT 
                c.id AS customer_id,
                c.name AS customer_name,
                c.phone AS customer_phone,
                c.customer_type,
                COUNT(s.id) AS purchases_count,
                COALESCE(SUM(s.grand_total), 0.00) AS total_revenue,
                COALESCE(SUM(s.total_cost), 0.00) AS total_cogs,
                COALESCE(SUM(s.gross_profit), 0.00) AS gross_profit
            FROM `sales` s
            JOIN `customers` c ON s.customer_id = c.id
            WHERE s.status NOT IN ('cancelled', 'draft')
        ";
        $params = [];

        if (!empty($filters['date_from'])) {
            $sql .= " AND DATE(s.sale_date) >= ?";
            $params[] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $sql .= " AND DATE(s.sale_date) <= ?";
            $params[] = $filters['date_to'];
        }

        $sql .= "
            GROUP BY c.id, c.name, c.phone, c.customer_type
            ORDER BY gross_profit DESC
            LIMIT 50
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($items as &$item) {
            $rev = (float)$item['total_revenue'];
            $profit = (float)$item['gross_profit'];
            $item['margin_percent'] = ($rev > 0) ? round(($profit / $rev) * 100, 2) : 0.00;
        }

        return $items;
    }

    /**
     * Monthly Revenue vs COGS vs Expenses vs Net Profit Trend for Charting
     */
    public static function getMonthlyFinancialTrend(int $months = 6): array {
        $pdo = Database::getConnection();
        
        $results = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $start = date('Y-m-01', strtotime("-{$i} months"));
            $end = date('Y-m-t', strtotime("-{$i} months"));
            $monthLabel = date('M Y', strtotime("-{$i} months"));

            $summary = self::getExecutiveSummary($start, $end);
            $results[] = [
                'month' => $monthLabel,
                'revenue' => $summary['net_sales'],
                'cogs' => $summary['cogs'],
                'gross_profit' => $summary['gross_profit'],
                'expenses' => $summary['operating_expenses'],
                'net_profit' => $summary['net_profit']
            ];
        }

        return $results;
    }

    /**
     * Get expenses grouped by category
     */
    public static function getExpensesByCategory(string $startDate, string $endDate): array {
        require_once __DIR__ . '/Expense.php';
        return Expense::getExpensesByCategory($startDate, $endDate);
    }
}
