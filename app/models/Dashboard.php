<?php
/**
 * NGENZI MOTO SPARE PARTS
 * Advanced Dashboard, Business Intelligence & Decision Support Model (Phase 13)
 * Dar es Salaam, Tanzania
 * Centralized BI coordinator with multi-period comparative analytics,
 * motorcycle/Bajaj attribution, inventory intelligence, target margin monitoring,
 * and centralized decision support alerts.
 */

namespace App\Models;

use App\Core\Database;
use App\Core\Auth;
use App\Core\Helper;
use App\Models\Report;
use App\Models\Financial;
use App\Models\Setting;
use App\Models\AuditLog;
use PDO;
use Exception;

require_once __DIR__ . '/Report.php';
require_once __DIR__ . '/Financial.php';
require_once __DIR__ . '/Setting.php';
require_once __DIR__ . '/AuditLog.php';

class Dashboard {

    /**
     * 1. RESOLVE DATE RANGE WITH PREVIOUS PERIOD PAIRING
     * Automatically derives equivalent prior period for comparative KPI analysis.
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
                
                $prevQStartMonth = $startMonth - 3;
                $prevQYear = (int)date('Y');
                if ($prevQStartMonth <= 0) {
                    $prevQStartMonth += 12;
                    $prevQYear -= 1;
                }
                $prevStart = date("{$prevQYear}-" . sprintf('%02d', $prevQStartMonth) . '-01');
                $prevEndMonth = $prevQStartMonth + 2;
                $prevEnd = date("{$prevQYear}-" . sprintf('%02d', $prevEndMonth) . '-t');

                $label = "Robo Mwaka (Q{$quarter} " . date('Y') . ")";
                $prevLabel = "Robo Iliyopita";
                break;

            case 'this_year':
                $start = date('Y-01-01');
                $end = date('Y-12-31');
                $prevYear = (int)date('Y') - 1;
                $prevStart = "{$prevYear}-01-01";
                $prevEnd = "{$prevYear}-12-31";
                $label = 'Mwaka Huu (' . date('Y') . ')';
                $prevLabel = "Mwaka Uliopita ({$prevYear})";
                break;

            case 'custom':
            default:
                $start = !empty($customStart) && strtotime($customStart) ? $customStart : date('Y-m-01');
                $end = !empty($customEnd) && strtotime($customEnd) ? $customEnd : date('Y-m-d');
                $days = max(1, (int)round((strtotime($end) - strtotime($start)) / 86400) + 1);
                $prevEnd = date('Y-m-d', strtotime($start . ' -1 day'));
                $prevStart = date('Y-m-d', strtotime($prevEnd . " -" . ($days - 1) . " days"));
                $label = date('d/m/Y', strtotime($start)) . ' - ' . date('d/m/Y', strtotime($end));
                $prevLabel = date('d/m/Y', strtotime($prevStart)) . ' - ' . date('d/m/Y', strtotime($prevEnd));
                break;
        }

        return [
            'period'          => $period,
            'start_date'      => $start,
            'end_date'        => $end,
            'prev_start_date' => $prevStart,
            'prev_end_date'   => $prevEnd,
            'label'           => $label,
            'prev_label'      => $prevLabel
        ];
    }

    /**
     * 2. COMPARISON HELPER
     * Pure factual calculation protected against division-by-zero.
     */
    public static function calcComparison($current, $previous, bool $isCurrency = true): array {
        $c = (float)$current;
        $p = (float)$previous;
        $diff = $c - $p;

        if ($p == 0.0) {
            $pct = ($c > 0) ? 100.0 : (($c < 0) ? -100.0 : 0.0);
        } else {
            $pct = ($diff / abs($p)) * 100.0;
        }

        return [
            'current'           => $c,
            'previous'          => $p,
            'difference'        => $diff,
            'percentage_change' => round($pct, 2),
            'formatted_current' => $isCurrency ? Helper::formatMoney($c) : number_format($c),
            'formatted_diff'    => ($diff >= 0 ? '+' : '') . ($isCurrency ? Helper::formatMoney($diff) : number_format($diff)),
            'direction'         => ($diff > 0) ? 'up' : (($diff < 0) ? 'down' : 'flat')
        ];
    }

    /**
     * 3. EXECUTIVE KPI CARDS WITH COMPARATIVE VARIANCE
     */
    public static function getExecutiveKPIs(string $startDate, string $endDate, string $prevStartDate, string $prevEndDate, bool $canSeeProfit = true, bool $canSeeCost = true): array {
        $curr = Report::getDashboardKPIs($startDate, $endDate);
        $prev = Report::getDashboardKPIs($prevStartDate, $prevEndDate);

        $kpis = [
            'sales_count' => self::calcComparison($curr['sales_count'], $prev['sales_count'], false),
            'units_sold'  => self::calcComparison($curr['units_sold'], $prev['units_sold'], false),
            'net_sales'   => self::calcComparison($curr['net_sales'], $prev['net_sales'], true),
            'cash_collected' => self::calcComparison($curr['cash_collected'], $prev['cash_collected'], true),
            'credit_outstanding' => self::calcComparison($curr['credit_outstanding'], $prev['credit_outstanding'], true),
            'stock_value' => self::calcComparison($canSeeCost ? $curr['stock_value'] : 0.0, $canSeeCost ? $prev['stock_value'] : 0.0, true),
            'low_stock_count' => self::calcComparison($curr['low_stock_count'], $prev['low_stock_count'], false),
            'out_of_stock_count' => self::calcComparison($curr['out_of_stock_count'], $prev['out_of_stock_count'], false),
            'purchase_value' => self::calcComparison($curr['purchase_value'], $prev['purchase_value'], true),
            'purchase_count' => self::calcComparison($curr['purchase_count'], $prev['purchase_count'], false),
            'supplier_outstanding' => self::calcComparison($curr['supplier_outstanding'], $prev['supplier_outstanding'], true),
            'customer_count' => self::calcComparison($curr['customer_count'], $prev['customer_count'], false),
            'pending_returns' => self::calcComparison($curr['pending_returns'], $prev['pending_returns'], false),
        ];

        if ($canSeeProfit) {
            $kpis['gross_profit'] = self::calcComparison($curr['gross_profit'], $prev['gross_profit'], true);
            $kpis['gross_margin'] = [
                'current'           => (float)$curr['gross_margin'],
                'previous'          => (float)$prev['gross_margin'],
                'difference'        => round((float)$curr['gross_margin'] - (float)$prev['gross_margin'], 2),
                'percentage_change' => round((float)$curr['gross_margin'] - (float)$prev['gross_margin'], 2),
                'direction'         => ((float)$curr['gross_margin'] >= (float)$prev['gross_margin']) ? 'up' : 'down'
            ];
            $kpis['operating_expenses'] = self::calcComparison($curr['operating_expenses'], $prev['operating_expenses'], true);
            $kpis['net_profit'] = self::calcComparison($curr['net_profit'], $prev['net_profit'], true);
            $kpis['net_margin'] = [
                'current'           => (float)$curr['net_margin'],
                'previous'          => (float)$prev['net_margin'],
                'difference'        => round((float)$curr['net_margin'] - (float)$prev['net_margin'], 2),
                'percentage_change' => round((float)$curr['net_margin'] - (float)$prev['net_margin'], 2),
                'direction'         => ((float)$curr['net_margin'] >= (float)$prev['net_margin']) ? 'up' : 'down'
            ];
            $kpis['cogs'] = self::calcComparison($curr['cogs'], $prev['cogs'], true);
        }

        return $kpis;
    }

    /**
     * 4. SALES TREND (Daily / Weekly / Monthly)
     */
    public static function getSalesTrend(string $startDate, string $endDate): array {
        $pdo = Database::getConnection();
        $days = (strtotime($endDate) - strtotime($startDate)) / 86400;

        if ($days <= 31) {
            // Daily granularity
            $groupBy = "DATE(s.sale_date)";
            $dateFormat = "%Y-%m-%d";
            $labelFormat = "%d/%m";
        } elseif ($days <= 180) {
            // Weekly granularity
            $groupBy = "YEARWEEK(s.sale_date, 1)";
            $dateFormat = "%x-W%v";
            $labelFormat = "W%v %Y";
        } else {
            // Monthly granularity
            $groupBy = "DATE_FORMAT(s.sale_date, '%Y-%m')";
            $dateFormat = "%Y-%m";
            $labelFormat = "%b %Y";
        }

        $stmt = $pdo->prepare("
            SELECT 
                DATE_FORMAT(s.sale_date, '{$dateFormat}') AS period_key,
                DATE_FORMAT(s.sale_date, '{$labelFormat}') AS period_label,
                COUNT(s.id) AS sales_count,
                COALESCE(SUM(s.total_amount), 0.00) AS net_sales,
                COALESCE(SUM(s.subtotal), 0.00) AS gross_sales,
                COALESCE(SUM(s.discount_amount), 0.00) AS discounts
            FROM `sales` s
            WHERE s.sale_status NOT IN ('cancelled')
              AND DATE(s.sale_date) BETWEEN ? AND ?
            GROUP BY period_key, period_label
            ORDER BY period_key ASC
        ");
        $stmt->execute([$startDate, $endDate]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * 5. PROFIT TREND OVER TIME
     * Uses historical sale_items.unit_cost for exact COGS.
     */
    public static function getProfitTrend(string $startDate, string $endDate): array {
        $pdo = Database::getConnection();

        $days = (strtotime($endDate) - strtotime($startDate)) / 86400;
        $dateFormat = ($days <= 31) ? "%Y-%m-%d" : (($days <= 180) ? "%x-W%v" : "%Y-%m");
        $labelFormat = ($days <= 31) ? "%d/%m" : (($days <= 180) ? "W%v" : "%b %Y");

        // Sales & COGS
        $salesStmt = $pdo->prepare("
            SELECT 
                DATE_FORMAT(s.sale_date, '{$dateFormat}') AS period_key,
                DATE_FORMAT(s.sale_date, '{$labelFormat}') AS period_label,
                COALESCE(SUM(s.total_amount), 0.00) AS net_sales,
                COALESCE(SUM(si.quantity * si.unit_cost), 0.00) AS cogs
            FROM `sales` s
            LEFT JOIN `sale_items` si ON s.id = si.sale_id
            WHERE s.sale_status NOT IN ('cancelled')
              AND DATE(s.sale_date) BETWEEN ? AND ?
            GROUP BY period_key, period_label
            ORDER BY period_key ASC
        ");
        $salesStmt->execute([$startDate, $endDate]);
        $salesData = $salesStmt->fetchAll(PDO::FETCH_ASSOC);

        // Operating Expenses
        $expStmt = $pdo->prepare("
            SELECT 
                DATE_FORMAT(expense_date, '{$dateFormat}') AS period_key,
                COALESCE(SUM(amount), 0.00) AS expenses
            FROM `expenses`
            WHERE status IN ('recorded', 'approved')
              AND expense_date BETWEEN ? AND ?
            GROUP BY period_key
        ");
        $expStmt->execute([$startDate, $endDate]);
        $expData = $expStmt->fetchAll(PDO::FETCH_KEY_PAIR);

        $result = [];
        foreach ($salesData as $row) {
            $pKey = $row['period_key'];
            $sales = (float)$row['net_sales'];
            $cogs = (float)$row['cogs'];
            $grossProfit = $sales - $cogs;
            $expenses = isset($expData[$pKey]) ? (float)$expData[$pKey] : 0.00;
            $netProfit = $grossProfit - $expenses;

            $result[] = [
                'period_key'   => $pKey,
                'period_label' => $row['period_label'],
                'net_sales'    => $sales,
                'cogs'         => $cogs,
                'gross_profit' => $grossProfit,
                'expenses'     => $expenses,
                'net_profit'   => $netProfit
            ];
        }

        return $result;
    }

    /**
     * 6. REVENUE VS CASH COLLECTION
     * Factual separation between Invoiced Revenue and Actual Cash Received.
     */
    public static function getRevenueVsCashComparison(string $startDate, string $endDate): array {
        $pdo = Database::getConnection();

        // Revenue from completed sales invoiced in period
        $revStmt = $pdo->prepare("
            SELECT 
                COALESCE(SUM(total_amount), 0.00) AS invoiced_revenue,
                COALESCE(SUM(balance_due), 0.00) AS period_unpaid_balance
            FROM `sales`
            WHERE sale_status NOT IN ('cancelled')
              AND DATE(sale_date) BETWEEN ? AND ?
        ");
        $revStmt->execute([$startDate, $endDate]);
        $rev = $revStmt->fetch(PDO::FETCH_ASSOC);

        // Actual cash collected in period from all payments
        $cashStmt = $pdo->prepare("
            SELECT COALESCE(SUM(amount), 0.00) AS cash_collected
            FROM `sale_payments`
            WHERE DATE(payment_date) BETWEEN ? AND ?
        ");
        $cashStmt->execute([$startDate, $endDate]);
        $cashCollected = (float)$cashStmt->fetchColumn();

        // Total active accounts receivable across entire system
        $arStmt = $pdo->query("
            SELECT COALESCE(SUM(balance_due), 0.00)
            FROM `sales`
            WHERE sale_status NOT IN ('cancelled') AND balance_due > 0
        ");
        $totalOutstandingDebt = (float)$arStmt->fetchColumn();

        return [
            'invoiced_revenue'       => (float)$rev['invoiced_revenue'],
            'cash_collected'         => $cashCollected,
            'period_unpaid_balance'  => (float)$rev['period_unpaid_balance'],
            'total_outstanding_debt' => $totalOutstandingDebt,
            'collection_ratio'       => ($rev['invoiced_revenue'] > 0) ? round(($cashCollected / (float)$rev['invoiced_revenue']) * 100, 1) : 0.0
        ];
    }

    /**
     * 7. MOTORCYCLE VS BAJAJ ANALYTICS
     * Uses strict database relationships (product_vehicle_compatibility -> vehicle_models).
     * Zero name guessing or inference.
     */
    public static function getMotorcycleVsBajajAnalytics(string $startDate, string $endDate, bool $canSeeProfit = true): array {
        $pdo = Database::getConnection();

        $stmt = $pdo->prepare("
            SELECT 
                vt.vehicle_type,
                COUNT(DISTINCT s.id) AS sales_count,
                COALESCE(SUM(si.quantity), 0) AS quantity_sold,
                COALESCE(SUM(si.subtotal), 0.00) AS revenue,
                COALESCE(SUM(si.gross_profit), 0.00) AS gross_profit
            FROM `sale_items` si
            JOIN `sales` s ON si.sale_id = s.id
            JOIN (
                SELECT 
                    product_id,
                    CASE 
                        WHEN COUNT(DISTINCT vm.vehicle_type) > 1 THEN 'Both / Universal'
                        ELSE MAX(vm.vehicle_type)
                    END AS vehicle_type
                FROM `product_vehicle_compatibility` pvc
                JOIN `vehicle_models` vm ON pvc.vehicle_model_id = vm.id
                GROUP BY product_id
            ) vt ON si.product_id = vt.product_id
            WHERE s.sale_status NOT IN ('cancelled')
              AND DATE(s.sale_date) BETWEEN ? AND ?
            GROUP BY vt.vehicle_type
            ORDER BY revenue DESC
        ");
        $stmt->execute([$startDate, $endDate]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $totalRevenue = array_sum(array_column($rows, 'revenue'));

        foreach ($rows as &$r) {
            $r['revenue'] = (float)$r['revenue'];
            $r['quantity_sold'] = (int)$r['quantity_sold'];
            $r['sales_count'] = (int)$r['sales_count'];
            $r['gross_profit'] = $canSeeProfit ? (float)$r['gross_profit'] : 0.00;
            $r['revenue_share'] = ($totalRevenue > 0) ? round(($r['revenue'] / $totalRevenue) * 100, 1) : 0.0;
            $r['gross_margin'] = ($r['revenue'] > 0 && $canSeeProfit) ? round(($r['gross_profit'] / $r['revenue']) * 100, 1) : 0.0;
        }

        return [
            'data'          => $rows,
            'total_revenue' => $totalRevenue
        ];
    }

    /**
     * 8. VEHICLE MODEL ANALYTICS
     * Specific vehicle models (Boxer BM150, TVS HLX, Bajaj RE 4S, etc.)
     */
    public static function getTopVehicleModels(string $startDate, string $endDate, int $limit = 8): array {
        $pdo = Database::getConnection();

        $stmt = $pdo->prepare("
            SELECT 
                vb.name AS brand_name,
                vm.model_name,
                vm.vehicle_type,
                COUNT(DISTINCT si.product_id) AS compatible_products_sold,
                COALESCE(SUM(si.quantity), 0) AS quantity_sold,
                COALESCE(SUM(si.subtotal), 0.00) AS revenue
            FROM `vehicle_models` vm
            JOIN `vehicle_brands` vb ON vm.brand_id = vb.id
            JOIN `product_vehicle_compatibility` pvc ON vm.id = pvc.vehicle_model_id
            JOIN `sale_items` si ON pvc.product_id = si.product_id
            JOIN `sales` s ON si.sale_id = s.id
            WHERE s.sale_status NOT IN ('cancelled')
              AND DATE(s.sale_date) BETWEEN ? AND ?
            GROUP BY vm.id, vb.name, vm.model_name, vm.vehicle_type
            ORDER BY revenue DESC
            LIMIT {$limit}
        ");
        $stmt->execute([$startDate, $endDate]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * 9. TOP SELLING PRODUCTS TABLE
     * Factual table sorted by selected metric (quantity, revenue, profit, orders).
     */
    public static function getTopSellingProducts(string $startDate, string $endDate, string $sortBy = 'quantity', int $limit = 10, bool $canSeeProfit = true): array {
        $pdo = Database::getConnection();

        switch ($sortBy) {
            case 'revenue':
                $orderBy = 'total_revenue DESC';
                break;
            case 'profit':
                $orderBy = 'gross_profit DESC';
                break;
            case 'orders':
                $orderBy = 'sales_count DESC';
                break;
            case 'quantity':
            default:
                $orderBy = 'total_qty DESC';
                break;
        }

        $stmt = $pdo->prepare("
            SELECT 
                p.id,
                p.sku,
                p.name AS product_name,
                c.name AS category_name,
                b.name AS brand_name,
                p.current_stock,
                COUNT(DISTINCT si.sale_id) AS sales_count,
                COALESCE(SUM(si.quantity), 0) AS total_qty,
                COALESCE(SUM(si.subtotal), 0.00) AS total_revenue,
                COALESCE(SUM(si.gross_profit), 0.00) AS gross_profit
            FROM `sale_items` si
            JOIN `sales` s ON si.sale_id = s.id
            JOIN `products` p ON si.product_id = p.id
            LEFT JOIN `categories` c ON p.category_id = c.id
            LEFT JOIN `brands` b ON p.brand_id = b.id
            WHERE s.sale_status NOT IN ('cancelled')
              AND DATE(s.sale_date) BETWEEN ? AND ?
            GROUP BY p.id, p.sku, p.name, c.name, b.name, p.current_stock
            ORDER BY {$orderBy}
            LIMIT {$limit}
        ");
        $stmt->execute([$startDate, $endDate]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as &$r) {
            $rev = (float)$r['total_revenue'];
            $gp = (float)$r['gross_profit'];
            $r['gross_margin'] = ($rev > 0 && $canSeeProfit) ? round(($gp / $rev) * 100, 1) : 0.0;
            if (!$canSeeProfit) {
                unset($r['gross_profit']);
            }
        }

        return $rows;
    }

    /**
     * 10. INVENTORY VALUATION & STOCK HEALTH
     */
    public static function getInventoryIntelligence(bool $canSeeCost = true): array {
        $pdo = Database::getConnection();

        $stmt = $pdo->query("
            SELECT 
                COUNT(*) AS total_products,
                COALESCE(SUM(current_stock), 0) AS total_stock_units,
                COALESCE(SUM(current_stock * buying_price), 0.00) AS total_cost_value,
                COALESCE(SUM(current_stock * selling_price), 0.00) AS total_retail_value,
                COALESCE(SUM(CASE WHEN current_stock <= 0 THEN 1 ELSE 0 END), 0) AS out_of_stock_count,
                COALESCE(SUM(CASE WHEN current_stock > 0 AND current_stock <= reorder_level THEN 1 ELSE 0 END), 0) AS low_stock_count,
                COALESCE(SUM(CASE WHEN current_stock > reorder_level THEN 1 ELSE 0 END), 0) AS healthy_stock_count
            FROM `products`
            WHERE status = 'active'
        ");
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        $costVal = $canSeeCost ? (float)$data['total_cost_value'] : 0.00;
        $retailVal = (float)$data['total_retail_value'];
        $potentialProfit = $retailVal - $costVal;

        return [
            'total_products'       => (int)$data['total_products'],
            'total_stock_units'    => (int)$data['total_stock_units'],
            'total_cost_value'     => $costVal,
            'total_retail_value'   => $retailVal,
            'potential_profit'     => $canSeeCost ? $potentialProfit : 0.00,
            'potential_margin'     => ($retailVal > 0 && $canSeeCost) ? round(($potentialProfit / $retailVal) * 100, 1) : 0.0,
            'out_of_stock_count'   => (int)$data['out_of_stock_count'],
            'low_stock_count'      => (int)$data['low_stock_count'],
            'healthy_stock_count'  => (int)$data['healthy_stock_count']
        ];
    }

    /**
     * 11. STOCK MOVEMENT SUMMARY (INVENTORY TRANSACTIONS)
     */
    public static function getStockMovementSummary(string $startDate, string $endDate): array {
        $pdo = Database::getConnection();

        $stmt = $pdo->prepare("
            SELECT 
                COALESCE(SUM(CASE WHEN transaction_type IN ('purchase', 'stock_count_adjustment') AND quantity > 0 THEN quantity ELSE 0 END), 0) AS stock_in,
                COALESCE(SUM(CASE WHEN transaction_type IN ('sale') THEN ABS(quantity) ELSE 0 END), 0) AS stock_out,
                COALESCE(SUM(CASE WHEN transaction_type IN ('return', 'sale_return') THEN quantity ELSE 0 END), 0) AS returns_in,
                COALESCE(SUM(CASE WHEN transaction_type IN ('adjustment', 'adjustment_in', 'adjustment_out', 'damage', 'loss') THEN quantity ELSE 0 END), 0) AS adjustments_net,
                COUNT(id) AS total_transactions
            FROM `inventory_transactions`
            WHERE DATE(created_at) BETWEEN ? AND ?
        ");
        $stmt->execute([$startDate, $endDate]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $currentStockTotal = (int)$pdo->query("SELECT COALESCE(SUM(current_stock), 0) FROM `products` WHERE status = 'active'")->fetchColumn();

        return [
            'stock_in'           => (int)$row['stock_in'],
            'stock_out'          => (int)$row['stock_out'],
            'returns_in'         => (int)$row['returns_in'],
            'adjustments_net'    => (int)$row['adjustments_net'],
            'total_transactions' => (int)$row['total_transactions'],
            'current_stock'      => $currentStockTotal
        ];
    }

    /**
     * 12. TARGET MARGIN (30%) MONITORING & VARIANCE
     */
    public static function getTargetMarginMonitor(bool $canSeeCost = true): array {
        $pdo = Database::getConnection();

        $targetMarginSetting = (float)Setting::get('default_target_margin', 30.00);

        // Overall stock margin
        $stockStmt = $pdo->query("
            SELECT 
                COALESCE(SUM(current_stock * buying_price), 0.00) AS cost_sum,
                COALESCE(SUM(current_stock * selling_price), 0.00) AS retail_sum
            FROM `products`
            WHERE status = 'active' AND current_stock > 0
        ");
        $stock = $stockStmt->fetch(PDO::FETCH_ASSOC);
        $costSum = (float)$stock['cost_sum'];
        $retailSum = (float)$stock['retail_sum'];

        $overallStockMargin = ($retailSum > 0) ? round((($retailSum - $costSum) / $retailSum) * 100, 2) : 0.00;
        $variance = round($overallStockMargin - $targetMarginSetting, 2);

        // Sample products with margin breakdown
        $prodStmt = $pdo->query("
            SELECT 
                p.id,
                p.sku,
                p.name AS product_name,
                c.name AS category_name,
                p.buying_price,
                p.selling_price,
                p.target_margin,
                ROUND(((p.selling_price - p.buying_price) / p.selling_price) * 100, 1) AS current_margin,
                ROUND((((p.selling_price - p.buying_price) / p.selling_price) * 100) - p.target_margin, 1) AS margin_variance
            FROM `products` p
            JOIN `categories` c ON p.category_id = c.id
            WHERE p.status = 'active'
            ORDER BY ABS((((p.selling_price - p.buying_price) / p.selling_price) * 100) - p.target_margin) DESC
            LIMIT 10
        ");
        $sampleProducts = $prodStmt->fetchAll(PDO::FETCH_ASSOC);

        if (!$canSeeCost) {
            foreach ($sampleProducts as &$p) {
                unset($p['buying_price']);
            }
        }

        return [
            'target_margin'        => $targetMarginSetting,
            'current_stock_margin' => $overallStockMargin,
            'variance'             => $variance,
            'products'             => $sampleProducts
        ];
    }

    /**
     * 13. PROFIT WATERFALL / BREAKDOWN
     * Standardized P&L flow: Gross Sales - Discounts = Net Sales - COGS = Gross Profit - OPEX = Net Profit
     */
    public static function getProfitWaterfall(string $startDate, string $endDate): array {
        $kpis = Report::getDashboardKPIs($startDate, $endDate);

        $grossSales = (float)$kpis['gross_sales'];
        $discounts = (float)$kpis['total_discounts'];
        $netSales = (float)$kpis['net_sales'];
        $cogs = (float)$kpis['cogs'];
        $grossProfit = (float)$kpis['gross_profit'];
        $expenses = (float)$kpis['operating_expenses'];
        $netProfit = (float)$kpis['net_profit'];

        return [
            'gross_sales'        => $grossSales,
            'discounts'          => $discounts,
            'net_sales'          => $netSales,
            'cogs'               => $cogs,
            'gross_profit'       => $grossProfit,
            'operating_expenses' => $expenses,
            'net_profit'         => $netProfit,
            'gross_margin'       => (float)$kpis['gross_margin'],
            'net_margin'         => (float)$kpis['net_margin']
        ];
    }

    /**
     * 14. CENTRALIZED DECISION SUPPORT & ALERTS
     */
    public static function getCentralizedAlerts(bool $canSeeProfit = true): array {
        $pdo = Database::getConnection();
        // Alert 0: Pending Online Orders Alert
        $pendingOnline = (int)$pdo->query("SELECT COUNT(*) FROM `online_orders` WHERE status IN ('PENDING', 'PAYMENT_PENDING')")->fetchColumn();
        if ($pendingOnline > 0) {
            $alerts[] = [
                'severity'     => 'ACTION REQUIRED',
                'badge_class'  => 'warning text-dark',
                'icon'         => 'bi-bag-check-fill',
                'title'        => "Maagizo Mapya ya Mtandaoni ({$pendingOnline})",
                'description'  => "Kuna maagizo {$pendingOnline} ya mtandaoni yanasubiri kuthibitishwa na kuandaliwa dukani Kariakoo.",
                'action_label' => 'Kagua Maagizo',
                'action_url'   => '/index.php?route=online_orders'
            ];
        }

        // Alert 1: Out of Stock Products
        $outCount = (int)$pdo->query("SELECT COUNT(*) FROM `products` WHERE status = 'active' AND current_stock <= 0")->fetchColumn();
        if ($outCount > 0) {
            $alerts[] = [
                'severity'     => 'ACTION REQUIRED',
                'badge_class'  => 'danger',
                'icon'         => 'bi-exclamation-octagon-fill',
                'title'        => "Bidhaa Zilizokwisha Stoo ({$outCount})",
                'description'  => "Kuna bidhaa {$outCount} ambazo hazina hata kipande kimoja stoo na wateja wanazihitaji.",
                'action_label' => 'Tazama Stoo',
                'action_url'   => '/index.php?route=inventory&stock_status=out'
            ];
        }

        // Alert 2: Low Stock Products Below Reorder Level
        $lowCount = (int)$pdo->query("SELECT COUNT(*) FROM `products` WHERE status = 'active' AND current_stock > 0 AND current_stock <= reorder_level")->fetchColumn();
        if ($lowCount > 0) {
            $alerts[] = [
                'severity'     => 'WARNING',
                'badge_class'  => 'warning text-dark',
                'icon'         => 'bi-exclamation-triangle-fill',
                'title'        => "Bidhaa Zilizofikia Kiwango cha Kuagiza ({$lowCount})",
                'description'  => "Bidhaa {$lowCount} zimebakiwa na akiba ndogo chini ya kiwango cha reorder level.",
                'action_label' => 'Agiza Mzigo',
                'action_url'   => '/index.php?route=inventory&stock_status=low'
            ];
        }

        // Alert 3: Overdue Customer Debts
        $overdueStmt = $pdo->query("
            SELECT COUNT(*) AS overdue_count, COALESCE(SUM(balance_due), 0.00) AS overdue_balance
            FROM `sales`
            WHERE sale_status NOT IN ('cancelled')
              AND balance_due > 0
              AND due_date IS NOT NULL
              AND due_date < CURRENT_DATE
        ");
        $overdue = $overdueStmt->fetch(PDO::FETCH_ASSOC);
        $overdueCount = (int)$overdue['overdue_count'];
        $overdueBal = (float)$overdue['overdue_balance'];

        if ($overdueCount > 0) {
            $alerts[] = [
                'severity'     => 'ACTION REQUIRED',
                'badge_class'  => 'danger',
                'icon'         => 'bi-cash-coin',
                'title'        => "Madeni Yaliyopitiliza Tarehe ya Ahadi ({$overdueCount})",
                'description'  => "Ankara {$overdueCount} zenye thamani ya TSh " . Helper::formatMoney($overdueBal) . " zimevuka siku zilizopangwa bila kulipwa.",
                'action_label' => 'Fuatilia Madeni',
                'action_url'   => '/index.php?route=reports/debts'
            ];
        }

        // Alert 4: Pending Customer Returns
        $pendingReturns = (int)$pdo->query("SELECT COUNT(*) FROM `returns` WHERE status = 'requested'")->fetchColumn();
        if ($pendingReturns > 0) {
            $alerts[] = [
                'severity'     => 'ACTION REQUIRED',
                'badge_class'  => 'info text-dark',
                'icon'         => 'bi-arrow-return-left',
                'title'        => "Maombi ya Kurejesha Bidhaa Yanasubiri Idhini ({$pendingReturns})",
                'description'  => "Kuna maombi {$pendingReturns} ya marejesho toka kwa wateja yanayohitaji ukaguzi na uamuzi wa meneja.",
                'action_label' => 'Kagua Marejesho',
                'action_url'   => '/index.php?route=returns&status=requested'
            ];
        }

        // Alert 5: Dead Stock Alert (No sales in 90+ days)
        $deadStockDays = (int)Setting::get('dashboard_dead_stock_days', 90);
        $cutoffDate = date('Y-m-d', strtotime("-{$deadStockDays} days"));
        $deadCountStmt = $pdo->prepare("
            SELECT COUNT(p.id)
            FROM `products` p
            WHERE p.status = 'active'
              AND p.current_stock > 0
              AND p.id NOT IN (
                  SELECT DISTINCT si.product_id
                  FROM `sale_items` si
                  JOIN `sales` s ON si.sale_id = s.id
                  WHERE s.sale_status NOT IN ('cancelled')
                    AND s.sale_date >= ?
              )
        ");
        $deadCountStmt->execute([$cutoffDate]);
        $deadCount = (int)$deadCountStmt->fetchColumn();

        if ($deadCount > 0) {
            $alerts[] = [
                'severity'     => 'WARNING',
                'badge_class'  => 'secondary',
                'icon'         => 'bi-box-seam',
                'title'        => "Bidhaa Zilizokwama Stoo (Dead Stock: {$deadCount})",
                'description'  => "Vipuri {$deadCount} vina mzigo stoo lakini havijawahi kuuzwa hata mara moja ndani ya siku {$deadStockDays}.",
                'action_label' => 'Tazama Dead Stock',
                'action_url'   => '/index.php?route=reports/inventory'
            ];
        }

        // Alert 6: Supplier Outstanding Balances Due
        $suppBalStmt = $pdo->query("
            SELECT COUNT(*) AS supp_count, COALESCE(SUM(balance), 0.00) AS total_payable
            FROM `suppliers`
            WHERE status = 'active' AND balance > 0
        ");
        $suppBal = $suppBalStmt->fetch(PDO::FETCH_ASSOC);
        $suppCount = (int)$suppBal['supp_count'];
        $suppAmount = (float)$suppBal['total_payable'];

        if ($suppCount > 0 && $canSeeProfit) {
            $alerts[] = [
                'severity'     => 'INFO',
                'badge_class'  => 'info text-dark',
                'icon'         => 'bi-truck',
                'title'        => "Madeni ya Wasambazaji wa Jumla ({$suppCount})",
                'description'  => "Kuna wauzaji wa jumla {$suppCount} tunaodaiwa jumla ya TSh " . Helper::formatMoney($suppAmount) . " kwa oda za mzigo.",
                'action_label' => 'Orodha ya Wasambazaji',
                'action_url'   => '/index.php?route=suppliers'
            ];
        }

        return $alerts;
    }

    /**
     * 15. RECENT ACTIVITY TIMELINE (Audit & Transaction Stream)
     */
    public static function getRecentActivity(int $limit = 10): array {
        $pdo = Database::getConnection();

        // Union of recent sales, purchases, payments, returns, and expenses
        $sql = "
            (SELECT 
                'sale' AS activity_type,
                s.id AS ref_id,
                s.invoice_no AS ref_no,
                s.created_at,
                CONCAT('Mauzo yamekamilika: ', s.customer_name) AS description,
                s.total_amount AS amount,
                u.full_name AS actor_name,
                'bi-cart-check text-success' AS icon
            FROM `sales` s
            LEFT JOIN `users` u ON s.cashier_id = u.id
            WHERE s.sale_status = 'completed'
            ORDER BY s.created_at DESC LIMIT 5)

            UNION ALL

            (SELECT 
                'payment' AS activity_type,
                sp.id AS ref_id,
                sp.payment_no AS ref_no,
                sp.created_at,
                CONCAT('Malipo yamepokelewa (', sp.payment_method, ')') AS description,
                sp.amount AS amount,
                u.full_name AS actor_name,
                'bi-cash-coin text-primary' AS icon
            FROM `sale_payments` sp
            LEFT JOIN `users` u ON sp.received_by = u.id
            ORDER BY sp.created_at DESC LIMIT 5)

            UNION ALL

            (SELECT 
                'purchase' AS activity_type,
                p.id AS ref_id,
                p.purchase_no AS ref_no,
                p.created_at,
                CONCAT('Mzigo mpya wa ununuzi: ', s.name) AS description,
                p.grand_total AS amount,
                u.full_name AS actor_name,
                'bi-box-arrow-in-down text-info' AS icon
            FROM `purchases` p
            JOIN `suppliers` s ON p.supplier_id = s.id
            LEFT JOIN `users` u ON p.created_by = u.id
            WHERE p.status = 'received'
            ORDER BY p.created_at DESC LIMIT 5)

            UNION ALL

            (SELECT 
                'return' AS activity_type,
                r.id AS ref_id,
                r.return_number AS ref_no,
                r.created_at,
                CONCAT('Mrejesho wa bidhaa: ', r.return_number) AS description,
                r.refund_amount AS amount,
                u.full_name AS actor_name,
                'bi-arrow-return-left text-warning' AS icon
            FROM `returns` r
            LEFT JOIN `users` u ON r.created_by = u.id
            ORDER BY r.created_at DESC LIMIT 5)

            UNION ALL

            (SELECT 
                'expense' AS activity_type,
                e.id AS ref_id,
                e.expense_no AS ref_no,
                e.created_at,
                CONCAT('Gharama za uendeshaji: ', COALESCE(e.payee, e.description)) AS description,
                e.amount AS amount,
                u.full_name AS actor_name,
                'bi-receipt text-danger' AS icon
            FROM `expenses` e
            LEFT JOIN `users` u ON e.recorded_by = u.id
            WHERE e.status IN ('recorded', 'approved')
            ORDER BY e.created_at DESC LIMIT 5)

            ORDER BY created_at DESC
            LIMIT {$limit}
        ";

        try {
            $stmt = $pdo->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * 16. OVERDUE DEBTS DETAILED LIST
     */
    public static function getOverdueDebtsAlert(int $limit = 10): array {
        $pdo = Database::getConnection();

        $stmt = $pdo->prepare("
            SELECT 
                s.id AS sale_id,
                s.invoice_no,
                s.sale_date,
                s.due_date,
                s.total_amount,
                s.paid_amount,
                s.balance_due,
                c.id AS customer_id,
                c.name AS customer_name,
                c.phone AS customer_phone,
                DATEDIFF(CURRENT_DATE, COALESCE(s.due_date, s.sale_date)) AS days_overdue
            FROM `sales` s
            JOIN `customers` c ON s.customer_id = c.id
            WHERE s.sale_status NOT IN ('cancelled')
              AND s.balance_due > 0
              AND (s.due_date IS NOT NULL AND s.due_date < CURRENT_DATE)
            ORDER BY days_overdue DESC, s.balance_due DESC
            LIMIT {$limit}
        ");
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as &$r) {
            $days = (int)$r['days_overdue'];
            if ($days <= 7) {
                $r['aging_bucket'] = '1 - 7 Days';
            } elseif ($days <= 30) {
                $r['aging_bucket'] = '8 - 30 Days';
            } elseif ($days <= 60) {
                $r['aging_bucket'] = '31 - 60 Days';
            } elseif ($days <= 90) {
                $r['aging_bucket'] = '61 - 90 Days';
            } else {
                $r['aging_bucket'] = '90+ Days';
            }
        }

        return $rows;
    }

    /**
     * 17. DEAD STOCK CANDIDATES
     */
    public static function getDeadStockCandidates(int $daysThreshold = 90, int $limit = 10, bool $canSeeCost = true): array {
        $pdo = Database::getConnection();
        $cutoffDate = date('Y-m-d', strtotime("-{$daysThreshold} days"));

        $stmt = $pdo->prepare("
            SELECT 
                p.id,
                p.sku,
                p.name AS product_name,
                c.name AS category_name,
                b.name AS brand_name,
                p.current_stock,
                p.buying_price,
                (p.current_stock * p.buying_price) AS inventory_value,
                (SELECT MAX(s.sale_date) FROM `sale_items` si JOIN `sales` s ON si.sale_id = s.id WHERE si.product_id = p.id AND s.sale_status NOT IN ('cancelled')) AS last_sale_date
            FROM `products` p
            LEFT JOIN `categories` c ON p.category_id = c.id
            LEFT JOIN `brands` b ON p.brand_id = b.id
            WHERE p.status = 'active'
              AND p.current_stock > 0
              AND p.id NOT IN (
                  SELECT DISTINCT si.product_id
                  FROM `sale_items` si
                  JOIN `sales` s ON si.sale_id = s.id
                  WHERE s.sale_status NOT IN ('cancelled')
                    AND s.sale_date >= ?
              )
            ORDER BY inventory_value DESC
            LIMIT {$limit}
        ");
        $stmt->execute([$cutoffDate]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as &$r) {
            $lastDate = $r['last_sale_date'];
            $r['days_since_sale'] = $lastDate ? (int)round((time() - strtotime($lastDate)) / 86400) : '—';
            if (!$canSeeCost) {
                unset($r['buying_price'], $r['inventory_value']);
            }
        }

        return $rows;
    }

    /**
     * 18. SLOW MOVING CANDIDATES
     */
    public static function getSlowMovingCandidates(int $days = 30, int $thresholdQty = 2, int $limit = 10): array {
        $pdo = Database::getConnection();
        $startDate = date('Y-m-d', strtotime("-{$days} days"));

        $stmt = $pdo->prepare("
            SELECT 
                p.id,
                p.sku,
                p.name AS product_name,
                c.name AS category_name,
                p.current_stock,
                COALESCE(SUM(si.quantity), 0) AS units_sold_period,
                (SELECT MAX(s.sale_date) FROM `sale_items` si2 JOIN `sales` s ON si2.sale_id = s.id WHERE si2.product_id = p.id AND s.sale_status NOT IN ('cancelled')) AS last_sale_date
            FROM `products` p
            LEFT JOIN `categories` c ON p.category_id = c.id
            LEFT JOIN `sale_items` si ON p.id = si.product_id
            LEFT JOIN `sales` s ON si.sale_id = s.id AND s.sale_status NOT IN ('cancelled') AND s.sale_date >= ?
            WHERE p.status = 'active' AND p.current_stock > 0
            GROUP BY p.id, p.sku, p.name, c.name, p.current_stock
            HAVING units_sold_period <= ?
            ORDER BY units_sold_period ASC, p.current_stock DESC
            LIMIT {$limit}
        ");
        $stmt->execute([$startDate, $thresholdQty]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as &$r) {
            $lastDate = $r['last_sale_date'];
            $r['days_since_sale'] = $lastDate ? (int)round((time() - strtotime($lastDate)) / 86400) : '—';
        }

        return $rows;
    }

    /**
     * 19. RECENT PRICE & COST HISTORY (Awareness)
     */
    public static function getRecentPriceCostChanges(int $limit = 6, bool $canSeeCost = true): array {
        $pdo = Database::getConnection();

        // Price changes
        $priceStmt = $pdo->query("
            SELECT 
                'price' AS change_type,
                p.name AS product_name,
                p.sku,
                ph.old_selling_price AS old_val,
                ph.new_selling_price AS new_val,
                ph.reason,
                ph.created_at,
                u.full_name AS changed_by_name
            FROM `price_history` ph
            JOIN `products` p ON ph.product_id = p.id
            LEFT JOIN `users` u ON ph.changed_by = u.id
            ORDER BY ph.created_at DESC
            LIMIT {$limit}
        ");
        $priceChanges = $priceStmt->fetchAll(PDO::FETCH_ASSOC);

        $costChanges = [];
        if ($canSeeCost) {
            $costStmt = $pdo->query("
                SELECT 
                    'cost' AS change_type,
                    p.name AS product_name,
                    p.sku,
                    ch.old_cost AS old_val,
                    ch.new_cost AS new_val,
                    ch.reason,
                    ch.created_at,
                    u.full_name AS changed_by_name
                FROM `product_cost_history` ch
                JOIN `products` p ON ch.product_id = p.id
                LEFT JOIN `users` u ON ch.changed_by = u.id
                ORDER BY ch.created_at DESC
                LIMIT {$limit}
            ");
            $costChanges = $costStmt->fetchAll(PDO::FETCH_ASSOC);
        }

        $merged = array_merge($priceChanges, $costChanges);
        usort($merged, fn($a, $b) => strcmp($b['created_at'], $a['created_at']));
        return array_slice($merged, 0, $limit);
    }
}
