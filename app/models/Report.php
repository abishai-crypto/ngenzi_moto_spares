<?php
/**
 * NGENZI MOTO SPARE PARTS
 * Comprehensive Report & Business Analytics Model (Phase 12)
 * Dar es Salaam, Tanzania
 * Single Source of Truth for Sales, Profit, Inventory, Purchases,
 * Customers, Debts, Cash Flow, Returns, Vehicle Analytics & Period Comparisons.
 */

namespace App\Models;

use App\Core\Database;
use App\Core\Auth;
use App\Core\Helper;
use PDO;
use Exception;

class Report {

    /**
     * Resolve Standard Date Range
     */
    public static function resolveDateRange(string $period = 'this_month', ?string $customStart = null, ?string $customEnd = null): array {
        $today = date('Y-m-d');

        switch ($period) {
            case 'today':
                $start = $today;
                $end = $today;
                $label = 'Leo (' . date('d/m/Y') . ')';
                break;

            case 'yesterday':
                $start = date('Y-m-d', strtotime('-1 day'));
                $end = date('Y-m-d', strtotime('-1 day'));
                $label = 'Jana (' . date('d/m/Y', strtotime('-1 day')) . ')';
                break;

            case 'this_week':
                $start = date('Y-m-d', strtotime('monday this week'));
                $end = date('Y-m-d', strtotime('sunday this week'));
                $label = 'Wiki Hii (' . date('d/m', strtotime($start)) . ' - ' . date('d/m/Y', strtotime($end)) . ')';
                break;

            case 'last_week':
                $start = date('Y-m-d', strtotime('monday last week'));
                $end = date('Y-m-d', strtotime('sunday last week'));
                $label = 'Wiki Iliyopita';
                break;

            case 'this_month':
                $start = date('Y-m-01');
                $end = date('Y-m-t');
                $label = 'Mwezi Huu (' . date('F Y') . ')';
                break;

            case 'last_month':
                $start = date('Y-m-01', strtotime('first day of last month'));
                $end = date('Y-m-t', strtotime('last day of last month'));
                $label = 'Mwezi Uliopita (' . date('F Y', strtotime('last month')) . ')';
                break;

            case 'this_quarter':
                $currentMonth = (int)date('n');
                $quarter = ceil($currentMonth / 3);
                $startMonth = ($quarter - 1) * 3 + 1;
                $start = date('Y-' . sprintf('%02d', $startMonth) . '-01');
                $endMonth = $quarter * 3;
                $end = date('Y-' . sprintf('%02d', $endMonth) . '-t');
                $label = "Robo Mwaka (Q{$quarter} " . date('Y') . ")";
                break;

            case 'this_year':
                $start = date('Y-01-01');
                $end = date('Y-12-31');
                $label = 'Mwaka Huu (' . date('Y') . ')';
                break;

            case 'custom':
            default:
                $start = !empty($customStart) && strtotime($customStart) ? $customStart : date('Y-m-01');
                $end = !empty($customEnd) && strtotime($customEnd) ? $customEnd : date('Y-m-d');
                $label = date('d/m/Y', strtotime($start)) . ' - ' . date('d/m/Y', strtotime($end));
                break;
        }

        return [
            'period'     => $period,
            'start_date' => $start,
            'end_date'   => $end,
            'label'      => $label
        ];
    }

    /**
     * 1. REPORTING DASHBOARD EXECUTIVE KPIS
     */
    public static function getDashboardKPIs(string $startDate, string $endDate): array {
        $pdo = Database::getConnection();

        // 1. Sales & Revenue
        $saleStmt = $pdo->prepare("
            SELECT 
                COUNT(id) AS sales_count,
                COALESCE(SUM(subtotal), 0.00) AS gross_sales,
                COALESCE(SUM(discount_amount), 0.00) AS total_discounts,
                COALESCE(SUM(total_amount), 0.00) AS net_sales,
                COALESCE(SUM(paid_amount), 0.00) AS sales_paid,
                COALESCE(SUM(balance_due), 0.00) AS credit_outstanding
            FROM `sales`
            WHERE sale_status NOT IN ('cancelled')
              AND DATE(sale_date) BETWEEN ? AND ?
        ");
        $saleStmt->execute([$startDate, $endDate]);
        $sales = $saleStmt->fetch(PDO::FETCH_ASSOC);

        $grossSales = (float)$sales['gross_sales'];
        $discounts = (float)$sales['total_discounts'];
        $netSales = (float)$sales['net_sales'];
        $creditOutstanding = (float)$sales['credit_outstanding'];

        // 2. COGS (Historical cost at sale)
        $cogsStmt = $pdo->prepare("
            SELECT 
                COALESCE(SUM(si.quantity * si.unit_cost), 0.00) AS exact_cogs,
                COALESCE(SUM(si.quantity), 0) AS total_units_sold
            FROM `sale_items` si
            JOIN `sales` s ON si.sale_id = s.id
            WHERE s.sale_status NOT IN ('cancelled')
              AND DATE(s.sale_date) BETWEEN ? AND ?
        ");
        $cogsStmt->execute([$startDate, $endDate]);
        $cogsData = $cogsStmt->fetch(PDO::FETCH_ASSOC);
        $cogs = (float)$cogsData['exact_cogs'];
        $unitsSold = (int)$cogsData['total_units_sold'];

        // Approved Returns adjustment
        $refundedAmount = 0.00;
        try {
            $retStmt = $pdo->prepare("
                SELECT COALESCE(SUM(refund_amount), 0.00) 
                FROM `returns` 
                WHERE status = 'approved' AND DATE(created_at) BETWEEN ? AND ?
            ");
            $retStmt->execute([$startDate, $endDate]);
            $refundedAmount = (float)$retStmt->fetchColumn();
        } catch (Exception $e) {}

        $netSalesEffective = max(0.00, $netSales - $refundedAmount);
        $grossProfit = $netSalesEffective - $cogs;
        $grossMargin = ($netSalesEffective > 0) ? round(($grossProfit / $netSalesEffective) * 100, 2) : 0.00;

        // 3. Operating Expenses
        $expStmt = $pdo->prepare("
            SELECT COALESCE(SUM(amount), 0.00) 
            FROM `expenses` 
            WHERE status IN ('recorded', 'approved') AND expense_date BETWEEN ? AND ?
        ");
        $expStmt->execute([$startDate, $endDate]);
        $operatingExpenses = (float)$expStmt->fetchColumn();

        $netProfit = $grossProfit - $operatingExpenses;
        $netMargin = ($netSalesEffective > 0) ? round(($netProfit / $netSalesEffective) * 100, 2) : 0.00;

        // 4. Actual Cash Flow Collected in Period
        $payStmt = $pdo->prepare("
            SELECT COALESCE(SUM(amount), 0.00) 
            FROM `sale_payments` 
            WHERE DATE(payment_date) BETWEEN ? AND ?
        ");
        $payStmt->execute([$startDate, $endDate]);
        $cashCollected = (float)$payStmt->fetchColumn();

        // 5. Inventory Asset Value & Stock Health
        $invStmt = $pdo->query("
            SELECT 
                COALESCE(SUM(current_stock * buying_price), 0.00) AS stock_value,
                COALESCE(SUM(CASE WHEN current_stock <= min_stock_level AND current_stock > 0 THEN 1 ELSE 0 END), 0) AS low_stock_count,
                COALESCE(SUM(CASE WHEN current_stock <= 0 THEN 1 ELSE 0 END), 0) AS out_of_stock_count
            FROM `products`
            WHERE status = 'active'
        ");
        $inv = $invStmt->fetch(PDO::FETCH_ASSOC);

        // 6. Purchases in Period
        $purchStmt = $pdo->prepare("
            SELECT 
                COUNT(id) AS purchase_count,
                COALESCE(SUM(grand_total), 0.00) AS purchase_value,
                COALESCE(SUM(balance_due), 0.00) AS supplier_outstanding
            FROM `purchases`
            WHERE status NOT IN ('cancelled')
              AND DATE(purchase_date) BETWEEN ? AND ?
        ");
        $purchStmt->execute([$startDate, $endDate]);
        $purch = $purchStmt->fetch(PDO::FETCH_ASSOC);

        // 7. Customers Count
        $custCount = (int)$pdo->query("SELECT COUNT(id) FROM `customers` WHERE status = 'active'")->fetchColumn();

        // 8. Pending Returns Count
        $pendingReturns = 0;
        try {
            $pendingReturns = (int)$pdo->query("SELECT COUNT(id) FROM `returns` WHERE status = 'requested'")->fetchColumn();
        } catch (Exception $e) {}

        return [
            'sales_count'           => (int)$sales['sales_count'],
            'units_sold'            => $unitsSold,
            'gross_sales'           => $grossSales,
            'total_discounts'       => $discounts,
            'net_sales'             => $netSalesEffective,
            'refunded_amount'       => $refundedAmount,
            'cogs'                  => $cogs,
            'gross_profit'          => $grossProfit,
            'gross_margin'          => $grossMargin,
            'operating_expenses'    => $operatingExpenses,
            'net_profit'            => $netProfit,
            'net_margin'            => $netMargin,
            'cash_collected'        => $cashCollected,
            'credit_outstanding'    => $creditOutstanding,
            'stock_value'           => (float)$inv['stock_value'],
            'low_stock_count'       => (int)$inv['low_stock_count'],
            'out_of_stock_count'    => (int)$inv['out_of_stock_count'],
            'purchase_count'        => (int)$purch['purchase_count'],
            'purchase_value'        => (float)$purch['purchase_value'],
            'supplier_outstanding'  => (float)$purch['supplier_outstanding'],
            'customer_count'        => $custCount,
            'pending_returns'       => $pendingReturns
        ];
    }

    /**
     * 2. DETAILED SALES REPORT
     */
    public static function getSalesReport(array $filters = [], int $page = 1, int $perPage = 25): array {
        $pdo = Database::getConnection();
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;

        $sql = "
            SELECT 
                s.id,
                s.invoice_no,
                s.sale_date,
                s.customer_name,
                s.customer_phone,
                s.subtotal AS gross_sales,
                s.discount_amount,
                s.total_amount AS net_sales,
                s.paid_amount,
                s.balance_due,
                s.payment_status,
                s.payment_method,
                s.sale_status,
                u.full_name AS cashier_name,
                (SELECT COUNT(*) FROM `sale_items` WHERE sale_id = s.id) AS items_count
            FROM `sales` s
            LEFT JOIN `users` u ON s.cashier_id = u.id
            WHERE s.sale_status NOT IN ('cancelled')
        ";

        $countSql = "SELECT COUNT(*) FROM `sales` s WHERE s.sale_status NOT IN ('cancelled')";
        $sumSql = "
            SELECT 
                COALESCE(SUM(s.subtotal), 0.00) AS total_gross,
                COALESCE(SUM(s.discount_amount), 0.00) AS total_discounts,
                COALESCE(SUM(s.total_amount), 0.00) AS total_net,
                COALESCE(SUM(s.paid_amount), 0.00) AS total_paid,
                COALESCE(SUM(s.balance_due), 0.00) AS total_balance
            FROM `sales` s 
            WHERE s.sale_status NOT IN ('cancelled')
        ";

        $params = [];

        if (!empty($filters['date_from'])) {
            $clause = " AND DATE(s.sale_date) >= ?";
            $sql .= $clause; $countSql .= $clause; $sumSql .= $clause;
            $params[] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $clause = " AND DATE(s.sale_date) <= ?";
            $sql .= $clause; $countSql .= $clause; $sumSql .= $clause;
            $params[] = $filters['date_to'];
        }
        if (!empty($filters['customer_id'])) {
            $clause = " AND s.customer_id = ?";
            $sql .= $clause; $countSql .= $clause; $sumSql .= $clause;
            $params[] = (int)$filters['customer_id'];
        }
        if (!empty($filters['cashier_id'])) {
            $clause = " AND s.cashier_id = ?";
            $sql .= $clause; $countSql .= $clause; $sumSql .= $clause;
            $params[] = (int)$filters['cashier_id'];
        }
        if (!empty($filters['payment_method'])) {
            $clause = " AND s.payment_method = ?";
            $sql .= $clause; $countSql .= $clause; $sumSql .= $clause;
            $params[] = $filters['payment_method'];
        }
        if (!empty($filters['payment_status'])) {
            $clause = " AND s.payment_status = ?";
            $sql .= $clause; $countSql .= $clause; $sumSql .= $clause;
            $params[] = $filters['payment_status'];
        }
        if (!empty($filters['search'])) {
            $clause = " AND (s.invoice_no LIKE ? OR s.customer_name LIKE ? OR s.customer_phone LIKE ?)";
            $term = "%{$filters['search']}%";
            $sql .= $clause; $countSql .= $clause; $sumSql .= $clause;
            $params[] = $term; $params[] = $term; $params[] = $term;
        }

        // Totals
        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute($params);
        $totalRecords = (int)$countStmt->fetchColumn();

        $sumStmt = $pdo->prepare($sumSql);
        $sumStmt->execute($params);
        $sums = $sumStmt->fetch(PDO::FETCH_ASSOC);

        // Paginated Data
        $sql .= " ORDER BY s.sale_date DESC, s.id DESC LIMIT {$perPage} OFFSET {$offset}";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'data'            => $records,
            'total'           => $totalRecords,
            'total_invoices'  => $totalRecords,
            'total_gross'     => (float)$sums['total_gross'],
            'gross_sales'     => (float)$sums['total_gross'],
            'total_discounts' => (float)$sums['total_discounts'],
            'total_net'       => (float)$sums['total_net'],
            'net_sales'       => (float)$sums['total_net'],
            'total_paid'      => (float)$sums['total_paid'],
            'total_balance'   => (float)$sums['total_balance'],
            'total_due'       => (float)$sums['total_balance'],
            'page'            => $page,
            'per_page'        => $perPage,
            'total_pages'     => ceil($totalRecords / $perPage)
        ];
    }

    /**
     * 3. SALES BY PRODUCT REPORT
     */
    public static function getSalesByProduct(array $filters = []): array {
        $pdo = Database::getConnection();

        $sql = "
            SELECT 
                p.id AS product_id,
                p.sku,
                p.name AS product_name,
                c.name AS category_name,
                b.name AS brand_name,
                COALESCE(SUM(si.quantity), 0) AS total_qty_sold,
                COALESCE(SUM(si.quantity * si.unit_price), 0.00) AS gross_revenue,
                COALESCE(SUM(si.quantity * si.discount_per_unit), 0.00) AS total_discount,
                COALESCE(SUM(si.subtotal), 0.00) AS net_revenue,
                COALESCE(SUM(si.quantity * si.unit_cost), 0.00) AS total_cogs,
                COALESCE(SUM(si.subtotal - (si.quantity * si.unit_cost)), 0.00) AS gross_profit
            FROM `sale_items` si
            JOIN `sales` s ON si.sale_id = s.id
            JOIN `products` p ON si.product_id = p.id
            LEFT JOIN `categories` c ON p.category_id = c.id
            LEFT JOIN `brands` b ON p.brand_id = b.id
            WHERE s.sale_status NOT IN ('cancelled')
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
            $sql .= " AND (p.name LIKE ? OR p.sku LIKE ?)";
            $term = "%{$filters['search']}%";
            $params[] = $term; $params[] = $term;
        }

        $sql .= "
            GROUP BY p.id, p.sku, p.name, c.name, b.name
            HAVING total_qty_sold > 0
            ORDER BY net_revenue DESC
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as &$r) {
            $rev = (float)$r['net_revenue'];
            $profit = (float)$r['gross_profit'];
            $r['gross_margin'] = ($rev > 0) ? round(($profit / $rev) * 100, 2) : 0.00;
        }

        return $rows;
    }

    /**
     * 4. SALES BY CATEGORY REPORT
     */
    public static function getSalesByCategory(array $filters = []): array {
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
            WHERE s.sale_status NOT IN ('cancelled')
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

        $sql .= " GROUP BY c.id, c.name HAVING total_qty_sold > 0 ORDER BY total_revenue DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as &$r) {
            $rev = (float)$r['total_revenue'];
            $profit = (float)$r['gross_profit'];
            $r['gross_margin'] = ($rev > 0) ? round(($profit / $rev) * 100, 2) : 0.00;
        }

        return $rows;
    }

    /**
     * 5. SALES BY BRAND REPORT
     */
    public static function getSalesByBrand(array $filters = []): array {
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
            WHERE s.sale_status NOT IN ('cancelled')
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

        $sql .= " GROUP BY b.id, b.name HAVING total_qty_sold > 0 ORDER BY total_revenue DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as &$r) {
            $rev = (float)$r['total_revenue'];
            $profit = (float)$r['gross_profit'];
            $r['gross_margin'] = ($rev > 0) ? round(($profit / $rev) * 100, 2) : 0.00;
        }

        return $rows;
    }

    /**
     * 6. SALES BY CASHIER REPORT
     */
    public static function getSalesByCashier(array $filters = []): array {
        $pdo = Database::getConnection();

        $sql = "
            SELECT 
                u.id AS cashier_id,
                u.full_name AS cashier_name,
                u.username,
                COUNT(s.id) AS sales_count,
                COALESCE(SUM((SELECT SUM(quantity) FROM `sale_items` WHERE sale_id = s.id)), 0) AS items_sold,
                COALESCE(SUM(s.subtotal), 0.00) AS gross_sales,
                COALESCE(SUM(s.discount_amount), 0.00) AS total_discounts,
                COALESCE(SUM(s.total_amount), 0.00) AS total_revenue
            FROM `sales` s
            JOIN `users` u ON s.cashier_id = u.id
            WHERE s.sale_status NOT IN ('cancelled')
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

        $sql .= " GROUP BY u.id, u.full_name, u.username ORDER BY total_revenue DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as &$r) {
            $count = (int)$r['sales_count'];
            $rev = (float)$r['total_revenue'];
            $r['avg_sale_value'] = ($count > 0) ? round($rev / $count, 2) : 0.00;
        }

        return $rows;
    }

    /**
     * 7. SALES BY CUSTOMER REPORT
     */
    public static function getSalesByCustomer(array $filters = []): array {
        $pdo = Database::getConnection();

        $sql = "
            SELECT 
                c.id AS customer_id,
                c.name AS customer_name,
                c.phone AS customer_phone,
                c.customer_type,
                COUNT(s.id) AS invoices_count,
                COALESCE(SUM((SELECT SUM(quantity) FROM `sale_items` WHERE sale_id = s.id)), 0) AS items_purchased,
                COALESCE(SUM(s.total_amount), 0.00) AS total_purchases,
                COALESCE(SUM(s.paid_amount), 0.00) AS amount_paid,
                COALESCE(SUM(s.balance_due), 0.00) AS outstanding_balance
            FROM `sales` s
            JOIN `customers` c ON s.customer_id = c.id
            WHERE s.sale_status NOT IN ('cancelled')
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

        $sql .= " GROUP BY c.id, c.name, c.phone, c.customer_type ORDER BY total_purchases DESC LIMIT 50";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * 8. PAYMENT METHOD REPORT
     */
    public static function getPaymentMethodReport(array $filters = []): array {
        $pdo = Database::getConnection();

        $sql = "
            SELECT 
                payment_method,
                COUNT(id) AS transaction_count,
                COALESCE(SUM(amount), 0.00) AS total_amount
            FROM `sale_payments`
            WHERE 1=1
        ";
        $params = [];

        if (!empty($filters['date_from'])) {
            $sql .= " AND DATE(payment_date) >= ?";
            $params[] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $sql .= " AND DATE(payment_date) <= ?";
            $params[] = $filters['date_to'];
        }

        $sql .= " GROUP BY payment_method ORDER BY total_amount DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $grandTotal = array_sum(array_column($rows, 'total_amount'));

        foreach ($rows as &$r) {
            $r['percentage'] = ($grandTotal > 0) ? round(($r['total_amount'] / $grandTotal) * 100, 2) : 0.00;
        }

        return [
            'methods'     => $rows,
            'grand_total' => $grandTotal
        ];
    }

    /**
     * 9. SALES BY VEHICLE COMPATIBILITY (Motorcycle & Bajaj Analytics)
     */
    public static function getSalesByVehicleCompatibility(array $filters = []): array {
        $pdo = Database::getConnection();

        $sql = "
            SELECT 
                vm.id AS model_id,
                CONCAT(vb.name, ' ', vm.model_name) AS vehicle_model,
                vm.vehicle_type,
                COUNT(DISTINCT si.product_id) AS compatible_products_sold,
                COALESCE(SUM(si.quantity), 0) AS total_qty_sold,
                COALESCE(SUM(si.subtotal), 0.00) AS total_revenue,
                COALESCE(SUM(si.quantity * si.unit_cost), 0.00) AS total_cogs,
                COALESCE(SUM(si.subtotal - (si.quantity * si.unit_cost)), 0.00) AS gross_profit
            FROM `product_vehicle_compatibility` pvc
            JOIN `vehicle_models` vm ON pvc.vehicle_model_id = vm.id
            JOIN `vehicle_brands` vb ON vm.brand_id = vb.id
            JOIN `sale_items` si ON pvc.product_id = si.product_id
            JOIN `sales` s ON si.sale_id = s.id
            WHERE s.sale_status NOT IN ('cancelled')
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
        if (!empty($filters['vehicle_type'])) {
            $sql .= " AND vm.vehicle_type = ?";
            $params[] = $filters['vehicle_type'];
        }

        $sql .= "
            GROUP BY vm.id, vb.name, vm.model_name, vm.vehicle_type
            HAVING total_qty_sold > 0
            ORDER BY total_revenue DESC
        ";

        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($rows as &$r) {
                $rev = (float)$r['total_revenue'];
                $profit = (float)$r['gross_profit'];
                $r['gross_margin'] = ($rev > 0) ? round(($profit / $rev) * 100, 2) : 0.00;
            }

            return $rows;
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * 10. DAILY SALES TREND (7, 30, 90 days or custom range)
     */
    public static function getDailySalesTrend(string $startDate, string $endDate): array {
        $pdo = Database::getConnection();

        $stmt = $pdo->prepare("
            SELECT 
                DATE(s.sale_date) AS sale_day,
                COUNT(s.id) AS sales_count,
                COALESCE(SUM(s.total_amount), 0.00) AS net_sales,
                COALESCE(SUM(s.paid_amount), 0.00) AS cash_collected
            FROM `sales` s
            WHERE s.sale_status NOT IN ('cancelled')
              AND DATE(s.sale_date) BETWEEN ? AND ?
            GROUP BY sale_day
            ORDER BY sale_day ASC
        ");
        $stmt->execute([$startDate, $endDate]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * 11. CASH COLLECTION REPORT
     */
    public static function getCashCollectionReport(array $filters = [], int $page = 1, int $perPage = 25): array {
        $pdo = Database::getConnection();
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;

        $sql = "
            SELECT 
                sp.id,
                sp.payment_no,
                sp.amount,
                sp.payment_method,
                sp.provider,
                sp.reference_no,
                sp.payment_date,
                sp.notes,
                s.invoice_no,
                c.name AS customer_name,
                u.full_name AS received_by_name
            FROM `sale_payments` sp
            JOIN `sales` s ON sp.sale_id = s.id
            LEFT JOIN `customers` c ON s.customer_id = c.id
            JOIN `users` u ON sp.received_by = u.id
            WHERE 1=1
        ";
        $countSql = "SELECT COUNT(*) FROM `sale_payments` sp JOIN `sales` s ON sp.sale_id = s.id WHERE 1=1";
        $sumSql = "SELECT COALESCE(SUM(sp.amount), 0.00) FROM `sale_payments` sp JOIN `sales` s ON sp.sale_id = s.id WHERE 1=1";

        $params = [];

        if (!empty($filters['date_from'])) {
            $clause = " AND DATE(sp.payment_date) >= ?";
            $sql .= $clause; $countSql .= $clause; $sumSql .= $clause;
            $params[] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $clause = " AND DATE(sp.payment_date) <= ?";
            $sql .= $clause; $countSql .= $clause; $sumSql .= $clause;
            $params[] = $filters['date_to'];
        }
        if (!empty($filters['payment_method'])) {
            $clause = " AND sp.payment_method = ?";
            $sql .= $clause; $countSql .= $clause; $sumSql .= $clause;
            $params[] = $filters['payment_method'];
        }
        if (!empty($filters['received_by'])) {
            $clause = " AND sp.received_by = ?";
            $sql .= $clause; $countSql .= $clause; $sumSql .= $clause;
            $params[] = (int)$filters['received_by'];
        }

        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute($params);
        $totalRecords = (int)$countStmt->fetchColumn();

        $sumStmt = $pdo->prepare($sumSql);
        $sumStmt->execute($params);
        $totalCollected = (float)$sumStmt->fetchColumn();

        $sql .= " ORDER BY sp.payment_date DESC, sp.id DESC LIMIT {$perPage} OFFSET {$offset}";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'data'            => $records,
            'total'           => $totalRecords,
            'total_collected' => $totalCollected,
            'page'            => $page,
            'per_page'        => $perPage,
            'total_pages'     => ceil($totalRecords / $perPage)
        ];
    }

    /**
     * 12. CREDIT SALES REPORT
     */
    public static function getCreditSalesReport(array $filters = [], int $page = 1, int $perPage = 25): array {
        $pdo = Database::getConnection();
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;

        $sql = "
            SELECT 
                s.id,
                s.invoice_no,
                s.sale_date,
                s.due_date,
                s.total_amount,
                s.paid_amount,
                s.balance_due,
                s.payment_status,
                c.name AS customer_name,
                c.phone AS customer_phone,
                DATEDIFF(CURRENT_DATE, s.due_date) AS days_overdue
            FROM `sales` s
            JOIN `customers` c ON s.customer_id = c.id
            WHERE s.sale_status NOT IN ('cancelled')
              AND (s.balance_due > 0 OR s.payment_status = 'credit')
        ";
        $countSql = "
            SELECT COUNT(*) 
            FROM `sales` s 
            JOIN `customers` c ON s.customer_id = c.id 
            WHERE s.sale_status NOT IN ('cancelled') 
              AND (s.balance_due > 0 OR s.payment_status = 'credit')
        ";
        $sumSql = "
            SELECT 
                COALESCE(SUM(s.total_amount), 0.00) AS total_credit_sales,
                COALESCE(SUM(s.paid_amount), 0.00) AS total_paid,
                COALESCE(SUM(s.balance_due), 0.00) AS total_outstanding
            FROM `sales` s 
            WHERE s.sale_status NOT IN ('cancelled') 
              AND (s.balance_due > 0 OR s.payment_status = 'credit')
        ";

        $params = [];

        if (!empty($filters['customer_id'])) {
            $clause = " AND s.customer_id = ?";
            $sql .= $clause; $countSql .= $clause; $sumSql .= $clause;
            $params[] = (int)$filters['customer_id'];
        }
        if (!empty($filters['date_from'])) {
            $clause = " AND DATE(s.sale_date) >= ?";
            $sql .= $clause; $countSql .= $clause; $sumSql .= $clause;
            $params[] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $clause = " AND DATE(s.sale_date) <= ?";
            $sql .= $clause; $countSql .= $clause; $sumSql .= $clause;
            $params[] = $filters['date_to'];
        }

        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute($params);
        $totalRecords = (int)$countStmt->fetchColumn();

        $sumStmt = $pdo->prepare($sumSql);
        $sumStmt->execute($params);
        $sums = $sumStmt->fetch(PDO::FETCH_ASSOC);

        $sql .= " ORDER BY s.due_date ASC, s.sale_date DESC LIMIT {$perPage} OFFSET {$offset}";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'data'              => $records,
            'total'             => $totalRecords,
            'total_credit'      => (float)$sums['total_credit_sales'],
            'total_paid'        => (float)$sums['total_paid'],
            'total_outstanding' => (float)$sums['total_outstanding'],
            'total_due'         => (float)$sums['total_outstanding'],
            'page'              => $page,
            'per_page'          => $perPage,
            'total_pages'       => ceil($totalRecords / $perPage)
        ];
    }

    /**
     * 13. DEBT AGING REPORT (Current, 1-7, 8-30, 31-60, 61-90, 90+ Days)
     */
    public static function getDebtAgingReport(): array {
        $pdo = Database::getConnection();

        $stmt = $pdo->query("
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
                DATEDIFF(CURRENT_DATE, COALESCE(s.due_date, s.sale_date)) AS overdue_days
            FROM `sales` s
            JOIN `customers` c ON s.customer_id = c.id
            WHERE s.sale_status NOT IN ('cancelled')
              AND s.balance_due > 0
            ORDER BY overdue_days DESC
        ");
        $debts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $buckets = [
            'current' => ['label' => 'Haijapita Muda (Current / Not Due)', 'count' => 0, 'amount' => 0.00],
            '1_7'     => ['label' => 'Siku 1 - 7 (1–7 Days Overdue)',       'count' => 0, 'amount' => 0.00],
            '8_30'    => ['label' => 'Siku 8 - 30 (8–30 Days Overdue)',     'count' => 0, 'amount' => 0.00],
            '31_60'   => ['label' => 'Siku 31 - 60 (31–60 Days Overdue)',   'count' => 0, 'amount' => 0.00],
            '61_90'   => ['label' => 'Siku 61 - 90 (61–90 Days Overdue)',   'count' => 0, 'amount' => 0.00],
            '90_plus' => ['label' => 'Zaidi ya Siku 90 (90+ Days Overdue)', 'count' => 0, 'amount' => 0.00]
        ];

        foreach ($debts as &$d) {
            $days = (int)$d['overdue_days'];
            $bal = (float)$d['balance_due'];

            if ($days <= 0) {
                $d['bucket'] = 'current';
            } elseif ($days <= 7) {
                $d['bucket'] = '1_7';
            } elseif ($days <= 30) {
                $d['bucket'] = '8_30';
            } elseif ($days <= 60) {
                $d['bucket'] = '31_60';
            } elseif ($days <= 90) {
                $d['bucket'] = '61_90';
            } else {
                $d['bucket'] = '90_plus';
            }

            $buckets[$d['bucket']]['count']++;
            $buckets[$d['bucket']]['amount'] += $bal;
        }

        $totalOutstanding = array_sum(array_column($buckets, 'amount'));

        return [
            'debts'             => $debts,
            'buckets'           => $buckets,
            'total_outstanding' => $totalOutstanding,
            'totals'            => [
                'total_debt'   => $totalOutstanding,
                'current'      => (float)$buckets['current']['amount'],
                'days_1_7'     => (float)$buckets['1_7']['amount'],
                'days_8_30'    => (float)$buckets['8_30']['amount'],
                'days_31_60'   => (float)$buckets['31_60']['amount'],
                'days_61_90'   => (float)$buckets['61_90']['amount'],
                'days_90_plus' => (float)$buckets['90_plus']['amount']
            ]
        ];
    }

    /**
     * 14. CUSTOMER STATEMENT (Chronological Ledger)
     */
    public static function getCustomerStatement(int $customerId, ?string $startDate = null, ?string $endDate = null): array {
        $pdo = Database::getConnection();

        $custStmt = $pdo->prepare("SELECT * FROM `customers` WHERE id = ?");
        $custStmt->execute([$customerId]);
        $customer = $custStmt->fetch(PDO::FETCH_ASSOC);

        if (!$customer) {
            throw new Exception("Mteja hakupatikana.");
        }

        $start = !empty($startDate) ? $startDate : date('Y-01-01');
        $end = !empty($endDate) ? $endDate : date('Y-m-d');

        // Opening balance before start date: (Total sales before start) - (Total payments before start)
        $openSalesStmt = $pdo->prepare("
            SELECT COALESCE(SUM(total_amount), 0.00) 
            FROM `sales` 
            WHERE customer_id = ? AND sale_status NOT IN ('cancelled') AND DATE(sale_date) < ?
        ");
        $openSalesStmt->execute([$customerId, $start]);
        $priorSales = (float)$openSalesStmt->fetchColumn();

        $openPayStmt = $pdo->prepare("
            SELECT COALESCE(SUM(sp.amount), 0.00) 
            FROM `sale_payments` sp
            JOIN `sales` s ON sp.sale_id = s.id
            WHERE s.customer_id = ? AND DATE(sp.payment_date) < ?
        ");
        $openPayStmt->execute([$customerId, $start]);
        $priorPayments = (float)$openPayStmt->fetchColumn();

        $openingBalance = $priorSales - $priorPayments;

        // Transactions in Period
        $transStmt = $pdo->prepare("
            SELECT 
                'sale' AS type,
                s.sale_date AS trans_date,
                s.invoice_no AS reference,
                CONCAT('Mauzo ya Vipuri #', s.invoice_no) AS description,
                s.total_amount AS debit,
                0.00 AS credit
            FROM `sales` s
            WHERE s.customer_id = ? 
              AND s.sale_status NOT IN ('cancelled') 
              AND DATE(s.sale_date) BETWEEN ? AND ?

            UNION ALL

            SELECT 
                'payment' AS type,
                sp.payment_date AS trans_date,
                sp.payment_no AS reference,
                CONCAT('Malipo ya Ankara #', s.invoice_no, ' (', sp.payment_method, ')') AS description,
                0.00 AS debit,
                sp.amount AS credit
            FROM `sale_payments` sp
            JOIN `sales` s ON sp.sale_id = s.id
            WHERE s.customer_id = ? 
              AND DATE(sp.payment_date) BETWEEN ? AND ?

            ORDER BY trans_date ASC
        ");
        $transStmt->execute([$customerId, $start, $end, $customerId, $start, $end]);
        $transactions = $transStmt->fetchAll(PDO::FETCH_ASSOC);

        $runningBalance = $openingBalance;
        $totalDebits = 0.00;
        $totalCredits = 0.00;

        foreach ($transactions as &$t) {
            $debit = (float)$t['debit'];
            $credit = (float)$t['credit'];
            $runningBalance += ($debit - $credit);
            $t['balance'] = $runningBalance;
            $totalDebits += $debit;
            $totalCredits += $credit;
        }

        return [
            'customer'        => $customer,
            'start_date'      => $start,
            'end_date'        => $end,
            'opening_balance' => $openingBalance,
            'transactions'    => $transactions,
            'total_debits'    => $totalDebits,
            'total_credits'   => $totalCredits,
            'closing_balance' => $runningBalance
        ];
    }

    /**
     * 15. INVENTORY VALUATION & STOCK REPORT
     */
    public static function getInventoryReport(array $filters = [], int $page = 1, int $perPage = 25): array {
        $pdo = Database::getConnection();
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;

        $sql = "
            SELECT 
                p.id,
                p.sku,
                p.name AS product_name,
                p.barcode,
                p.barcode AS part_number,
                c.name AS category_name,
                b.name AS brand_name,
                p.current_stock,
                p.min_stock_level AS minimum_stock,
                p.min_stock_level,
                p.reorder_level,
                p.buying_price AS unit_cost,
                p.selling_price,
                (p.current_stock * p.buying_price) AS inventory_value,
                (p.current_stock * p.selling_price) AS retail_value,
                p.status
            FROM `products` p
            LEFT JOIN `categories` c ON p.category_id = c.id
            LEFT JOIN `brands` b ON p.brand_id = b.id
            WHERE 1=1
        ";
        $countSql = "SELECT COUNT(*) FROM `products` p WHERE 1=1";
        $sumSql = "
            SELECT 
                COALESCE(SUM(p.current_stock), 0) AS total_units,
                COALESCE(SUM(p.current_stock * p.buying_price), 0.00) AS total_cost_value,
                COALESCE(SUM(p.current_stock * p.selling_price), 0.00) AS total_retail_value
            FROM `products` p 
            WHERE 1=1
        ";

        $params = [];

        if (!empty($filters['category_id'])) {
            $clause = " AND p.category_id = ?";
            $sql .= $clause; $countSql .= $clause; $sumSql .= $clause;
            $params[] = (int)$filters['category_id'];
        }
        if (!empty($filters['brand_id'])) {
            $clause = " AND p.brand_id = ?";
            $sql .= $clause; $countSql .= $clause; $sumSql .= $clause;
            $params[] = (int)$filters['brand_id'];
        }
        if (!empty($filters['stock_status'])) {
            if ($filters['stock_status'] === 'low') {
                $clause = " AND p.current_stock <= p.min_stock_level AND p.current_stock > 0";
            } elseif ($filters['stock_status'] === 'out') {
                $clause = " AND p.current_stock <= 0";
            } elseif ($filters['stock_status'] === 'in_stock') {
                $clause = " AND p.current_stock > p.min_stock_level";
            }
            if (isset($clause)) {
                $sql .= $clause; $countSql .= $clause; $sumSql .= $clause;
            }
        }
        if (!empty($filters['search'])) {
            $clause = " AND (p.name LIKE ? OR p.sku LIKE ? OR p.barcode LIKE ?)";
            $term = "%{$filters['search']}%";
            $sql .= $clause; $countSql .= $clause; $sumSql .= $clause;
            $params[] = $term; $params[] = $term; $params[] = $term;
        }

        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute($params);
        $totalRecords = (int)$countStmt->fetchColumn();

        $sumStmt = $pdo->prepare($sumSql);
        $sumStmt->execute($params);
        $sums = $sumStmt->fetch(PDO::FETCH_ASSOC);

        $sql .= " ORDER BY p.current_stock ASC, p.name ASC LIMIT {$perPage} OFFSET {$offset}";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'data'               => $records,
            'total'              => $totalRecords,
            'total_items'        => $totalRecords,
            'total_units'        => (int)$sums['total_units'],
            'total_stock_qty'    => (int)$sums['total_units'],
            'total_cost_value'   => (float)$sums['total_cost_value'],
            'total_retail_value' => (float)$sums['total_retail_value'],
            'potential_profit'   => (float)$sums['total_retail_value'] - (float)$sums['total_cost_value'],
            'page'               => $page,
            'per_page'           => $perPage,
            'total_pages'        => ceil($totalRecords / $perPage)
        ];
    }

    /**
     * 16. STOCK MOVEMENT REPORT (Transactions Ledger)
     */
    public static function getStockMovementReport(array $filters = [], int $page = 1, int $perPage = 25): array {
        $pdo = Database::getConnection();
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;

        $sql = "
            SELECT 
                it.id,
                it.created_at AS trans_date,
                p.sku,
                p.name AS product_name,
                it.transaction_type,
                it.quantity_in,
                it.quantity_out,
                it.stock_before,
                it.stock_after,
                it.reference_no,
                it.notes,
                u.full_name AS user_name
            FROM `inventory_transactions` it
            JOIN `products` p ON it.product_id = p.id
            LEFT JOIN `users` u ON it.created_by = u.id
            WHERE 1=1
        ";
        $countSql = "SELECT COUNT(*) FROM `inventory_transactions` it JOIN `products` p ON it.product_id = p.id WHERE 1=1";
        $params = [];

        if (!empty($filters['product_id'])) {
            $clause = " AND it.product_id = ?";
            $sql .= $clause; $countSql .= $clause;
            $params[] = (int)$filters['product_id'];
        }
        if (!empty($filters['transaction_type'])) {
            $clause = " AND it.transaction_type = ?";
            $sql .= $clause; $countSql .= $clause;
            $params[] = $filters['transaction_type'];
        }
        if (!empty($filters['date_from'])) {
            $clause = " AND DATE(it.created_at) >= ?";
            $sql .= $clause; $countSql .= $clause;
            $params[] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $clause = " AND DATE(it.created_at) <= ?";
            $sql .= $clause; $countSql .= $clause;
            $params[] = $filters['date_to'];
        }

        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute($params);
        $totalRecords = (int)$countStmt->fetchColumn();

        $sql .= " ORDER BY it.created_at DESC, it.id DESC LIMIT {$perPage} OFFSET {$offset}";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'data'        => $records,
            'total'       => $totalRecords,
            'page'        => $page,
            'per_page'    => $perPage,
            'total_pages' => ceil($totalRecords / $perPage)
        ];
    }

    /**
     * 17. FAST-MOVING PRODUCTS
     */
    public static function getFastMovingProducts(array $filters = [], int $limit = 20): array {
        $pdo = Database::getConnection();

        $sql = "
            SELECT 
                p.id AS product_id,
                p.sku,
                p.name AS product_name,
                c.name AS category_name,
                b.name AS brand_name,
                p.current_stock,
                COALESCE(SUM(si.quantity), 0) AS total_qty_sold,
                COUNT(DISTINCT s.id) AS sales_frequency,
                COALESCE(SUM(si.subtotal), 0.00) AS total_revenue
            FROM `sale_items` si
            JOIN `sales` s ON si.sale_id = s.id
            JOIN `products` p ON si.product_id = p.id
            LEFT JOIN `categories` c ON p.category_id = c.id
            LEFT JOIN `brands` b ON p.brand_id = b.id
            WHERE s.sale_status NOT IN ('cancelled')
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
            GROUP BY p.id, p.sku, p.name, c.name, b.name, p.current_stock
            HAVING total_qty_sold > 0
            ORDER BY total_qty_sold DESC
            LIMIT {$limit}
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * 18. SLOW-MOVING PRODUCTS
     */
    public static function getSlowMovingProducts(array $filters = [], int $daysThreshold = 30, int $limit = 20): array {
        $pdo = Database::getConnection();

        $sql = "
            SELECT 
                p.id AS product_id,
                p.sku,
                p.name AS product_name,
                c.name AS category_name,
                p.current_stock,
                p.buying_price,
                (p.current_stock * p.buying_price) AS inventory_value,
                COALESCE(SUM(si.quantity), 0) AS total_qty_sold,
                MAX(s.sale_date) AS last_sale_date,
                DATEDIFF(CURRENT_DATE, COALESCE(MAX(s.sale_date), p.created_at)) AS days_since_last_sale
            FROM `products` p
            LEFT JOIN `categories` c ON p.category_id = c.id
            LEFT JOIN `sale_items` si ON p.id = si.product_id
            LEFT JOIN `sales` s ON si.sale_id = s.id AND s.sale_status NOT IN ('cancelled')
            WHERE p.status = 'active' AND p.current_stock > 0
            GROUP BY p.id, p.sku, p.name, c.name, p.current_stock, p.buying_price, p.created_at
            HAVING days_since_last_sale >= ?
            ORDER BY days_since_last_sale DESC, inventory_value DESC
            LIMIT {$limit}
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$daysThreshold]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * 19. DEAD STOCK (Stock > 0, No sales in period / >90 days)
     */
    public static function getDeadStockReport(int $daysThreshold = 90): array {
        $pdo = Database::getConnection();

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
                p.created_at,
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
                    AND s.sale_date >= DATE_SUB(CURRENT_DATE, INTERVAL ? DAY)
              )
            ORDER BY inventory_value DESC
        ");
        $stmt->execute([$daysThreshold]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * 20. REORDER REPORT (Stock <= reorder_level)
     */
    public static function getReorderReport(): array {
        $pdo = Database::getConnection();

        $stmt = $pdo->query("
            SELECT 
                p.id,
                p.sku,
                p.name AS product_name,
                c.name AS category_name,
                p.current_stock,
                p.min_stock_level AS minimum_stock,
                p.min_stock_level,
                p.reorder_level,
                p.buying_price,
                GREATEST(p.reorder_level * 2 - p.current_stock, p.min_stock_level) AS suggested_reorder_qty,
                (GREATEST(p.reorder_level * 2 - p.current_stock, p.min_stock_level) * p.buying_price) AS estimated_reorder_cost
            FROM `products` p
            LEFT JOIN `categories` c ON p.category_id = c.id
            WHERE p.status = 'active'
              AND p.current_stock <= p.reorder_level
            ORDER BY p.current_stock ASC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * 21. PURCHASES & SUPPLIER REPORTS
     */
    public static function getPurchasesReport(array $filters = [], int $page = 1, int $perPage = 25): array {
        $pdo = Database::getConnection();
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;

        $sql = "
            SELECT 
                p.id,
                p.purchase_no,
                p.purchase_date,
                s.name AS supplier_name,
                s.phone AS supplier_phone,
                p.grand_total AS purchase_value,
                p.grand_total AS total_amount,
                p.paid_amount,
                p.balance_due,
                p.payment_status,
                p.status AS purchase_status,
                p.status,
                (SELECT COUNT(*) FROM `purchase_items` WHERE purchase_id = p.id) AS products_count,
                (SELECT SUM(quantity) FROM `purchase_items` WHERE purchase_id = p.id) AS total_quantity
            FROM `purchases` p
            JOIN `suppliers` s ON p.supplier_id = s.id
            WHERE p.status NOT IN ('cancelled')
        ";
        $countSql = "SELECT COUNT(*) FROM `purchases` p WHERE p.status NOT IN ('cancelled')";
        $sumSql = "
            SELECT 
                COALESCE(SUM(p.grand_total), 0.00) AS total_purchased,
                COALESCE(SUM(p.paid_amount), 0.00) AS total_paid,
                COALESCE(SUM(p.balance_due), 0.00) AS total_balance
            FROM `purchases` p 
            WHERE p.status NOT IN ('cancelled')
        ";

        $params = [];

        if (!empty($filters['date_from'])) {
            $clause = " AND DATE(p.purchase_date) >= ?";
            $sql .= $clause; $countSql .= $clause; $sumSql .= $clause;
            $params[] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $clause = " AND DATE(p.purchase_date) <= ?";
            $sql .= $clause; $countSql .= $clause; $sumSql .= $clause;
            $params[] = $filters['date_to'];
        }
        if (!empty($filters['supplier_id'])) {
            $clause = " AND p.supplier_id = ?";
            $sql .= $clause; $countSql .= $clause; $sumSql .= $clause;
            $params[] = (int)$filters['supplier_id'];
        }

        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute($params);
        $totalRecords = (int)$countStmt->fetchColumn();

        $sumStmt = $pdo->prepare($sumSql);
        $sumStmt->execute($params);
        $sums = $sumStmt->fetch(PDO::FETCH_ASSOC);

        $sql .= " ORDER BY p.purchase_date DESC, p.id DESC LIMIT {$perPage} OFFSET {$offset}";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'data'            => $records,
            'total'           => $totalRecords,
            'total_purchased' => (float)$sums['total_purchased'],
            'total_paid'      => (float)$sums['total_paid'],
            'total_balance'   => (float)$sums['total_balance'],
            'page'            => $page,
            'per_page'        => $perPage,
            'total_pages'     => ceil($totalRecords / $perPage)
        ];
    }

    /**
     * 22. SUPPLIER ACTIVITY REPORT
     */
    public static function getSuppliersReport(array $filters = []): array {
        $pdo = Database::getConnection();

        $sql = "
            SELECT 
                s.id AS supplier_id,
                s.name AS supplier_name,
                s.phone,
                s.contact_person,
                COUNT(p.id) AS purchases_count,
                COALESCE(SUM(p.grand_total), 0.00) AS total_purchase_value,
                COALESCE(SUM(p.paid_amount), 0.00) AS total_paid,
                COALESCE(SUM(p.balance_due), 0.00) AS total_outstanding
            FROM `suppliers` s
            LEFT JOIN `purchases` p ON s.id = p.supplier_id AND p.status NOT IN ('cancelled')
            WHERE s.status = 'active'
        ";
        $params = [];

        if (!empty($filters['date_from'])) {
            $sql .= " AND DATE(p.purchase_date) >= ?";
            $params[] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $sql .= " AND DATE(p.purchase_date) <= ?";
            $params[] = $filters['date_to'];
        }

        $sql .= " GROUP BY s.id, s.name, s.phone, s.contact_person ORDER BY total_purchase_value DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * 23. RETURNS & REFUNDS REPORT
     */
    public static function getReturnsReport(array $filters = [], int $page = 1, int $perPage = 25): array {
        $pdo = Database::getConnection();
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;

        $sql = "
            SELECT 
                r.id,
                r.return_number,
                r.created_at AS return_date,
                r.return_type AS reason,
                r.refund_amount,
                r.refund_method,
                r.status,
                s.invoice_no,
                c.name AS customer_name,
                (SELECT COUNT(*) FROM `return_items` WHERE return_id = r.id) AS items_count,
                (SELECT SUM(quantity) FROM `return_items` WHERE return_id = r.id) AS total_qty_returned
            FROM `returns` r
            JOIN `sales` s ON r.sale_id = s.id
            LEFT JOIN `customers` c ON r.customer_id = c.id
            WHERE 1=1
        ";
        $countSql = "SELECT COUNT(*) FROM `returns` r WHERE 1=1";
        $sumSql = "
            SELECT 
                COUNT(r.id) AS total_returns,
                COALESCE(SUM(r.refund_amount), 0.00) AS total_refunded
            FROM `returns` r 
            WHERE 1=1
        ";

        $params = [];

        if (!empty($filters['date_from'])) {
            $clause = " AND DATE(r.created_at) >= ?";
            $sql .= $clause; $countSql .= $clause; $sumSql .= $clause;
            $params[] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $clause = " AND DATE(r.created_at) <= ?";
            $sql .= $clause; $countSql .= $clause; $sumSql .= $clause;
            $params[] = $filters['date_to'];
        }
        if (!empty($filters['status'])) {
            $clause = " AND r.status = ?";
            $sql .= $clause; $countSql .= $clause; $sumSql .= $clause;
            $params[] = $filters['status'];
        }

        try {
            $countStmt = $pdo->prepare($countSql);
            $countStmt->execute($params);
            $totalRecords = (int)$countStmt->fetchColumn();

            $sumStmt = $pdo->prepare($sumSql);
            $sumStmt->execute($params);
            $sums = $sumStmt->fetch(PDO::FETCH_ASSOC);

            $sql .= " ORDER BY r.created_at DESC, r.id DESC LIMIT {$perPage} OFFSET {$offset}";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $itemCountSql = "
                SELECT COALESCE(SUM(ri.quantity), 0)
                FROM `return_items` ri
                JOIN `returns` r ON ri.return_id = r.id
                WHERE 1=1
            ";
            if (!empty($filters['date_from'])) {
                $itemCountSql .= " AND DATE(r.created_at) >= " . $pdo->quote($filters['date_from']);
            }
            if (!empty($filters['date_to'])) {
                $itemCountSql .= " AND DATE(r.created_at) <= " . $pdo->quote($filters['date_to']);
            }
            $totalItemsReturned = (int)$pdo->query($itemCountSql)->fetchColumn();

            return [
                'data'                 => $records,
                'total'                => $totalRecords,
                'total_returns'        => $totalRecords,
                'total_items_returned' => $totalItemsReturned,
                'total_refunded'       => (float)$sums['total_refunded'],
                'page'                 => $page,
                'per_page'             => $perPage,
                'total_pages'          => ceil($totalRecords / $perPage)
            ];
        } catch (Exception $e) {
            return ['data' => [], 'total' => 0, 'total_returns' => 0, 'total_items_returned' => 0, 'total_refunded' => 0, 'page' => 1, 'per_page' => 25, 'total_pages' => 0];
        }
    }

    /**
     * 24. RETURN RATE REPORT (Return Rate = Returned Quantity / Sold Quantity * 100)
     */
    public static function getReturnRateReport(array $filters = []): array {
        $pdo = Database::getConnection();

        $sql = "
            SELECT 
                p.id AS product_id,
                p.sku,
                p.name AS product_name,
                c.name AS category_name,
                COALESCE(SUM(si.quantity), 0) AS total_sold_qty,
                COALESCE((
                    SELECT SUM(ri.quantity) 
                    FROM `return_items` ri 
                    JOIN `returns` r ON ri.return_id = r.id 
                    WHERE ri.product_id = p.id AND r.status = 'approved'
                ), 0) AS total_returned_qty
            FROM `sale_items` si
            JOIN `sales` s ON si.sale_id = s.id
            JOIN `products` p ON si.product_id = p.id
            LEFT JOIN `categories` c ON p.category_id = c.id
            WHERE s.sale_status NOT IN ('cancelled')
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

        $sql .= " GROUP BY p.id, p.sku, p.name, c.name HAVING total_sold_qty > 0 ORDER BY total_returned_qty DESC";

        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($rows as &$r) {
                $sold = (int)$r['total_sold_qty'];
                $ret = (int)$r['total_returned_qty'];
                $r['return_rate'] = ($sold > 0) ? round(($ret / $sold) * 100, 2) : 0.00;
            }

            return $rows;
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * 25. PRODUCT PERFORMANCE 360 ANALYTICS
     */
    public static function getProductPerformanceDetails(int $productId): array {
        $pdo = Database::getConnection();

        $stmt = $pdo->prepare("
            SELECT p.*, c.name AS category_name, b.name AS brand_name, u.name AS unit_name
            FROM `products` p
            LEFT JOIN `categories` c ON p.category_id = c.id
            LEFT JOIN `brands` b ON p.brand_id = b.id
            LEFT JOIN `units` u ON p.unit_id = u.id
            WHERE p.id = ?
        ");
        $stmt->execute([$productId]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$product) {
            throw new Exception("Kipuri hakikupatikana.");
        }

        // Sales Metrics
        $salesStmt = $pdo->prepare("
            SELECT 
                COALESCE(SUM(si.quantity), 0) AS total_units_sold,
                COALESCE(SUM(si.subtotal), 0.00) AS total_revenue,
                COALESCE(SUM(si.quantity * si.unit_cost), 0.00) AS total_cogs,
                COALESCE(SUM(si.subtotal - (si.quantity * si.unit_cost)), 0.00) AS gross_profit,
                MAX(s.sale_date) AS last_sale_date
            FROM `sale_items` si
            JOIN `sales` s ON si.sale_id = s.id
            WHERE si.product_id = ? AND s.sale_status NOT IN ('cancelled')
        ");
        $salesStmt->execute([$productId]);
        $salesData = $salesStmt->fetch(PDO::FETCH_ASSOC);

        $rev = (float)$salesData['total_revenue'];
        $profit = (float)$salesData['gross_profit'];
        $margin = ($rev > 0) ? round(($profit / $rev) * 100, 2) : 0.00;

        // Purchases Metrics
        $purchStmt = $pdo->prepare("
            SELECT 
                COALESCE(SUM(pi.quantity), 0) AS total_units_purchased,
                MAX(p.purchase_date) AS last_purchase_date
            FROM `purchase_items` pi
            JOIN `purchases` p ON pi.purchase_id = p.id
            WHERE pi.product_id = ? AND p.status NOT IN ('cancelled')
        ");
        $purchStmt->execute([$productId]);
        $purchData = $purchStmt->fetch(PDO::FETCH_ASSOC);

        // Returns Metrics
        $retQty = 0;
        try {
            $retStmt = $pdo->prepare("
                SELECT COALESCE(SUM(ri.quantity), 0)
                FROM `return_items` ri
                JOIN `returns` r ON ri.return_id = r.id
                WHERE ri.product_id = ? AND r.status = 'approved'
            ");
            $retStmt->execute([$productId]);
            $retQty = (int)$retStmt->fetchColumn();
        } catch (Exception $e) {}

        // 6-Month Sales Trend for this product
        $trendStmt = $pdo->prepare("
            SELECT 
                DATE_FORMAT(s.sale_date, '%Y-%m') AS month_label,
                SUM(si.quantity) AS qty_sold,
                SUM(si.subtotal) AS revenue
            FROM `sale_items` si
            JOIN `sales` s ON si.sale_id = s.id
            WHERE si.product_id = ? AND s.sale_status NOT IN ('cancelled')
              AND s.sale_date >= DATE_SUB(CURRENT_DATE, INTERVAL 6 MONTH)
            GROUP BY month_label
            ORDER BY month_label ASC
        ");
        $trendStmt->execute([$productId]);
        $salesTrend = $trendStmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'product'                => $product,
            'total_units_sold'       => (int)$salesData['total_units_sold'],
            'total_revenue'          => $rev,
            'total_cogs'             => (float)$salesData['total_cogs'],
            'gross_profit'           => $profit,
            'gross_margin'           => $margin,
            'last_sale_date'         => $salesData['last_sale_date'],
            'total_units_purchased'  => (int)$purchData['total_units_purchased'],
            'last_purchase_date'     => $purchData['last_purchase_date'],
            'total_units_returned'   => $retQty,
            'current_stock_value'    => (float)$product['current_stock'] * (float)$product['buying_price'],
            'sales_trend'            => $salesTrend
        ];
    }
}
