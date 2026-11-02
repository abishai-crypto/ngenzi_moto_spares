<?php
/**
 * NGENZI MOTO SPARE PARTS
 * Advanced Dashboard & Business Intelligence Controller (Phase 13)
 * Dar es Salaam, Tanzania
 * Multi-Role Dashboard with Executive KPIs, Interactive Charts,
 * Inventory Intelligence, Decision Support Alerts, and CSV Export.
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Database;
use App\Core\Helper;
use App\Models\Dashboard;
use App\Models\Report;
use App\Models\Financial;
use App\Models\Expense;
use App\Models\Setting;
use App\Models\User;
use Exception;

require_once __DIR__ . '/../models/Dashboard.php';
require_once __DIR__ . '/../models/Report.php';
require_once __DIR__ . '/../models/Financial.php';
require_once __DIR__ . '/../models/Expense.php';
require_once __DIR__ . '/../models/Setting.php';
require_once __DIR__ . '/../models/User.php';

class DashboardController extends Controller {

    /**
     * 1. MAIN ADVANCED DASHBOARD HUB
     */
    public function index(): void {
        Auth::requireLogin();
        $user = Auth::user();
        $userRole = $_SESSION['role_name'] ?? ($user['role'] ?? 'cashier');

        // RBAC Permissions resolution
        $canSeeProfit = Auth::hasRole(['super_admin', 'manager', 'accountant']) || 
                        Auth::hasPermission('dashboard.executive') || 
                        Auth::hasPermission('reports.profit') || 
                        Auth::hasPermission('financial.profit');

        $canSeeCost = Auth::hasRole(['super_admin', 'manager', 'accountant']) || 
                      Auth::hasPermission('reports.view_cost') || 
                      Auth::hasPermission('financial.cost');

        $canManageAlerts = Auth::hasRole(['super_admin', 'manager']) || 
                           Auth::hasPermission('dashboard.manage_alerts');

        $canExport = Auth::hasRole(['super_admin', 'manager', 'accountant']) || 
                     Auth::hasPermission('dashboard.export');

        // Global Date Filter
        $period = trim($_GET['period'] ?? 'this_month');
        $customStart = trim($_GET['start_date'] ?? '');
        $customEnd = trim($_GET['end_date'] ?? '');
        $sortBy = trim($_GET['sort_by'] ?? 'quantity');

        $range = Dashboard::resolveDateRange($period, $customStart, $customEnd);

        // 1. Executive KPIs with Comparative Analysis
        $kpis = Dashboard::getExecutiveKPIs(
            $range['start_date'], 
            $range['end_date'], 
            $range['prev_start_date'], 
            $range['prev_end_date'], 
            $canSeeProfit, 
            $canSeeCost
        );

        // 2. Trend Visualizations
        $salesTrend = Dashboard::getSalesTrend($range['start_date'], $range['end_date']);
        $profitTrend = $canSeeProfit ? Dashboard::getProfitTrend($range['start_date'], $range['end_date']) : [];

        // 3. Financial Breakdown
        $revVsCash = Dashboard::getRevenueVsCashComparison($range['start_date'], $range['end_date']);
        $waterfall = $canSeeProfit ? Dashboard::getProfitWaterfall($range['start_date'], $range['end_date']) : [];
        $targetMargin = Dashboard::getTargetMarginMonitor($canSeeCost);

        // 4. Sales Distributions
        $paymentReport = Report::getPaymentMethodReport(['date_from' => $range['start_date'], 'date_to' => $range['end_date']]);
        $paymentMethods = [];
        foreach ($paymentReport['methods'] ?? [] as $pm) {
            $paymentMethods[] = [
                'payment_method'     => $pm['payment_method'] ?? 'N/A',
                'transactions_count' => (int)($pm['transaction_count'] ?? 0),
                'total_collected'    => (float)($pm['total_amount'] ?? 0),
                'percentage'         => (float)($pm['percentage'] ?? 0),
            ];
        }
        $salesByCategory = Report::getSalesByCategory(['date_from' => $range['start_date'], 'date_to' => $range['end_date']]);
        $salesByBrand = Report::getSalesByBrand(['date_from' => $range['start_date'], 'date_to' => $range['end_date']]);
        $vehicleAnalytics = Dashboard::getMotorcycleVsBajajAnalytics($range['start_date'], $range['end_date'], $canSeeProfit);
        $topVehicleModels = Dashboard::getTopVehicleModels($range['start_date'], $range['end_date'], 6);
        $topProducts = Dashboard::getTopSellingProducts($range['start_date'], $range['end_date'], $sortBy, 8, $canSeeProfit);

        // 5. Inventory Intelligence & Movements
        $inventoryIntel = Dashboard::getInventoryIntelligence($canSeeCost);
        $stockMovement = Dashboard::getStockMovementSummary($range['start_date'], $range['end_date']);
        $deadStock = Dashboard::getDeadStockCandidates(90, 6, $canSeeCost);
        $slowStock = Dashboard::getSlowMovingCandidates(30, 2, 6);

        // 6. Customers, Suppliers & Credit
        $topCustomers = Report::getSalesByCustomer(['date_from' => $range['start_date'], 'date_to' => $range['end_date']]);
        $supplierData = Report::getSuppliersReport(['date_from' => $range['start_date'], 'date_to' => $range['end_date']]);
        $debtAging = Report::getDebtAgingReport();
        $overdueDebts = Dashboard::getOverdueDebtsAlert(6);

        // 7. Returns & Refunds
        $returnsReport = Report::getReturnsReport(['date_from' => $range['start_date'], 'date_to' => $range['end_date']], 1, 6);
        $returnRates = Report::getReturnRateReport(['date_from' => $range['start_date'], 'date_to' => $range['end_date']]);

        // 8. Operating Expenses
        $expenseCategories = Expense::getExpensesByCategory($range['start_date'], $range['end_date']);

        // 9. Centralized Decision Support Alerts & Timeline
        $centralizedAlerts = Dashboard::getCentralizedAlerts($canSeeProfit);
        $recentActivity = Dashboard::getRecentActivity(8);
        $recentPriceChanges = Dashboard::getRecentPriceCostChanges(5, $canSeeCost);

        // Alert Threshold Settings
        $thresholds = [
            'dead_stock_days'       => Setting::get('dashboard_dead_stock_days', '90'),
            'slow_stock_days'       => Setting::get('dashboard_slow_stock_days', '30'),
            'slow_stock_threshold'  => Setting::get('dashboard_slow_stock_threshold', '2'),
            'overdue_debt_days'     => Setting::get('dashboard_overdue_debt_days', '1'),
            'high_return_threshold' => Setting::get('dashboard_high_return_threshold', '10.00'),
        ];

        $this->render('dashboard/index', [
            'pageTitle'           => 'Dashibodi Kuu & Ujasusi wa Biashara (BI) — NGENZI MOTO SPARE PARTS',
            'activeMenu'          => 'dashboard',
            'user'                => $user,
            'userRole'            => $userRole,
            'canSeeProfit'        => $canSeeProfit,
            'canSeeCost'          => $canSeeCost,
            'canManageAlerts'     => $canManageAlerts,
            'canExport'           => $canExport,
            'range'               => $range,
            'period'              => $period,
            'sortBy'              => $sortBy,
            'kpis'                => $kpis,
            'salesTrend'          => $salesTrend,
            'profitTrend'         => $profitTrend,
            'revVsCash'           => $revVsCash,
            'waterfall'           => $waterfall,
            'targetMargin'        => $targetMargin,
            'paymentMethods'      => $paymentMethods,
            'salesByCategory'     => $salesByCategory,
            'salesByBrand'        => $salesByBrand,
            'vehicleAnalytics'    => $vehicleAnalytics,
            'topVehicleModels'    => $topVehicleModels,
            'topProducts'         => $topProducts,
            'inventoryIntel'      => $inventoryIntel,
            'stockMovement'       => $stockMovement,
            'deadStock'           => $deadStock,
            'slowStock'           => $slowStock,
            'topCustomers'        => array_slice($topCustomers, 0, 6),
            'supplierData'        => array_slice($supplierData, 0, 6),
            'debtAging'           => $debtAging,
            'overdueDebts'        => $overdueDebts,
            'returnsReport'       => $returnsReport,
            'returnRates'         => array_slice($returnRates, 0, 6),
            'expenseCategories'   => $expenseCategories,
            'centralizedAlerts'   => $centralizedAlerts,
            'recentActivity'      => $recentActivity,
            'recentPriceChanges'  => $recentPriceChanges,
            'thresholds'          => $thresholds,
            'flash'               => Helper::getFlash()
        ]);
    }

    /**
     * 2. EXPORT DASHBOARD SUMMARY (CSV)
     * Strictly verifies authorization and respects hidden cost/profit restrictions.
     */
    public function export(): void {
        Auth::requireLogin();

        $canExport = Auth::hasRole(['super_admin', 'manager', 'accountant']) || Auth::hasPermission('dashboard.export');
        if (!$canExport) {
            Helper::setFlash('danger', 'Huna idhini ya kupakua taarifa za dashibodi (Access Denied).');
            $this->redirect('/index.php?route=dashboard');
            exit;
        }

        $canSeeProfit = Auth::hasRole(['super_admin', 'manager', 'accountant']) || Auth::hasPermission('reports.profit');
        $canSeeCost = Auth::hasRole(['super_admin', 'manager', 'accountant']) || Auth::hasPermission('reports.view_cost');

        $period = trim($_GET['period'] ?? 'this_month');
        $customStart = trim($_GET['start_date'] ?? '');
        $customEnd = trim($_GET['end_date'] ?? '');
        $range = Dashboard::resolveDateRange($period, $customStart, $customEnd);

        $kpis = Dashboard::getExecutiveKPIs($range['start_date'], $range['end_date'], $range['prev_start_date'], $range['prev_end_date'], $canSeeProfit, $canSeeCost);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="NGENZI_Dashboard_Summary_' . date('Ymd_His') . '.csv"');

        $out = fopen('php://output', 'w');
        fputs($out, "\xEF\xBB\xBF"); // UTF-8 BOM

        fputcsv($out, Helper::sanitizeCsvRow(['NGENZI MOTO SPARE PARTS — MUHTASARI WA BIASHARA']));
        fputcsv($out, Helper::sanitizeCsvRow(['Kipindi:', $range['label'], 'Kipindi cha Nyuma:', $range['prev_label']]));
        fputcsv($out, Helper::sanitizeCsvRow(['Tarehe ya Kupakua:', date('d/m/Y H:i:s')]));
        fputcsv($out, []);

        fputcsv($out, Helper::sanitizeCsvRow(['Kipimo (KPI Metric)', 'Thamani ya Sasa', 'Thamani Iliyopita', 'Tofauti (Variance)', 'Mabadiliko (%)']));
        fputcsv($out, Helper::sanitizeCsvRow(['Idadi ya Mauzo', $kpis['sales_count']['current'], $kpis['sales_count']['previous'], $kpis['sales_count']['difference'], $kpis['sales_count']['percentage_change'] . '%']));
        fputcsv($out, Helper::sanitizeCsvRow(['Vipande Vilivyouzwa (Units)', $kpis['units_sold']['current'], $kpis['units_sold']['previous'], $kpis['units_sold']['difference'], $kpis['units_sold']['percentage_change'] . '%']));
        fputcsv($out, Helper::sanitizeCsvRow(['Mauzo Halisi (Net Sales)', $kpis['net_sales']['current'], $kpis['net_sales']['previous'], $kpis['net_sales']['difference'], $kpis['net_sales']['percentage_change'] . '%']));
        fputcsv($out, Helper::sanitizeCsvRow(['Pesa Zilizokusanywa (Cash Collected)', $kpis['cash_collected']['current'], $kpis['cash_collected']['previous'], $kpis['cash_collected']['difference'], $kpis['cash_collected']['percentage_change'] . '%']));
        fputcsv($out, Helper::sanitizeCsvRow(['Deni Lililobaki (Credit Outstanding)', $kpis['credit_outstanding']['current'], $kpis['credit_outstanding']['previous'], $kpis['credit_outstanding']['difference'], $kpis['credit_outstanding']['percentage_change'] . '%']));

        if ($canSeeProfit) {
            fputcsv($out, Helper::sanitizeCsvRow(['Gharama ya Mauzo (COGS)', $kpis['cogs']['current'], $kpis['cogs']['previous'], $kpis['cogs']['difference'], $kpis['cogs']['percentage_change'] . '%']));
            fputcsv($out, Helper::sanitizeCsvRow(['Faida Ghafi (Gross Profit)', $kpis['gross_profit']['current'], $kpis['gross_profit']['previous'], $kpis['gross_profit']['difference'], $kpis['gross_profit']['percentage_change'] . '%']));
            fputcsv($out, Helper::sanitizeCsvRow(['Asilimia ya Faida Ghafi (Gross Margin %)', $kpis['gross_margin']['current'] . '%', $kpis['gross_margin']['previous'] . '%', $kpis['gross_margin']['difference'] . '%', $kpis['gross_margin']['percentage_change'] . '%']));
            fputcsv($out, Helper::sanitizeCsvRow(['Matumizi ya Uendeshaji (OPEX)', $kpis['operating_expenses']['current'], $kpis['operating_expenses']['previous'], $kpis['operating_expenses']['difference'], $kpis['operating_expenses']['percentage_change'] . '%']));
            fputcsv($out, Helper::sanitizeCsvRow(['Faida Halisi (Net Profit)', $kpis['net_profit']['current'], $kpis['net_profit']['previous'], $kpis['net_profit']['difference'], $kpis['net_profit']['percentage_change'] . '%']));
            fputcsv($out, Helper::sanitizeCsvRow(['Asilimia ya Faida Halisi (Net Margin %)', $kpis['net_margin']['current'] . '%', $kpis['net_margin']['previous'] . '%', $kpis['net_margin']['difference'] . '%', $kpis['net_margin']['percentage_change'] . '%']));
        }

        if ($canSeeCost) {
            fputcsv($out, Helper::sanitizeCsvRow(['Thamani ya Mali Stoo (Stock Cost Value)', $kpis['stock_value']['current'], $kpis['stock_value']['previous'], $kpis['stock_value']['difference'], $kpis['stock_value']['percentage_change'] . '%']));
        }

        fputcsv($out, Helper::sanitizeCsvRow(['Bidhaa Zilizobaki Kidogo (Low Stock)', $kpis['low_stock_count']['current'], $kpis['low_stock_count']['previous'], $kpis['low_stock_count']['difference'], $kpis['low_stock_count']['percentage_change'] . '%']));
        fputcsv($out, Helper::sanitizeCsvRow(['Bidhaa Zilizokwisha (Out of Stock)', $kpis['out_of_stock_count']['current'], $kpis['out_of_stock_count']['previous'], $kpis['out_of_stock_count']['difference'], $kpis['out_of_stock_count']['percentage_change'] . '%']));
        fputcsv($out, Helper::sanitizeCsvRow(['Thamani ya Manunuzi (Purchases)', $kpis['purchase_value']['current'], $kpis['purchase_value']['previous'], $kpis['purchase_value']['difference'], $kpis['purchase_value']['percentage_change'] . '%']));

        fclose($out);
        exit;
    }

    /**
     * 3. UPDATE ALERT THRESHOLDS CONFIGURATION
     */
    public function updateThresholds(): void {
        Auth::requireLogin();
        $user = Auth::user();

        if (!Auth::hasRole(['super_admin', 'manager']) && !Auth::hasPermission('dashboard.manage_alerts')) {
            Helper::setFlash('danger', 'Huna idhini ya kubadilisha vigezo vya tahadhari (Access Denied).');
            $this->redirect('/index.php?route=dashboard');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Helper::validateCsrf($_POST['csrf_token'] ?? '')) {
                Helper::setFlash('danger', 'Hitilafu ya kiusalama: Muda wa fomu umekwisha (Invalid CSRF).');
                $this->redirect('/index.php?route=dashboard');
                exit;
            }

            $deadDays = max(15, (int)($_POST['dead_stock_days'] ?? 90));
            $slowDays = max(7, (int)($_POST['slow_stock_days'] ?? 30));
            $slowThreshold = max(1, (int)($_POST['slow_stock_threshold'] ?? 2));
            $overdueDays = max(0, (int)($_POST['overdue_debt_days'] ?? 1));
            $highReturn = max(1.0, (float)($_POST['high_return_threshold'] ?? 10.0));

            Setting::set('dashboard_dead_stock_days', (string)$deadDays, $user['id']);
            Setting::set('dashboard_slow_stock_days', (string)$slowDays, $user['id']);
            Setting::set('dashboard_slow_stock_threshold', (string)$slowThreshold, $user['id']);
            Setting::set('dashboard_overdue_debt_days', (string)$overdueDays, $user['id']);
            Setting::set('dashboard_high_return_threshold', (string)$highReturn, $user['id']);

            Helper::logAudit('DASHBOARD_THRESHOLDS_UPDATED', 'SETTINGS', $user['id'], null, [
                'dead_stock_days'       => $deadDays,
                'slow_stock_days'       => $slowDays,
                'slow_stock_threshold'  => $slowThreshold,
                'overdue_debt_days'     => $overdueDays,
                'high_return_threshold' => $highReturn
            ]);

            Helper::setFlash('success', 'Vigezo vya tahadhari vimesasishwa kikamilifu.');
        }

        $this->redirect('/index.php?route=dashboard');
    }
}
