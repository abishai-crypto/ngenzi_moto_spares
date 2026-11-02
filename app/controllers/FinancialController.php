<?php
/**
 * NGENZI MOTO SPARE PARTS
 * Financial Controller (Phase 10)
 * Handles Financial Dashboard, Profit & Loss Statements, COGS Analysis,
 * Product Profitability, Period Comparisons, Category/Brand/Vehicle breakdowns,
 * Swahili Localization & Strict RBAC Enforcement.
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Helper;
use App\Models\Financial;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Category;
use App\Models\Brand;
use App\Models\User;
use Exception;

require_once __DIR__ . '/../models/Financial.php';
require_once __DIR__ . '/../models/Expense.php';
require_once __DIR__ . '/../models/ExpenseCategory.php';
require_once __DIR__ . '/../models/Category.php';
require_once __DIR__ . '/../models/Brand.php';
require_once __DIR__ . '/../models/User.php';

class FinancialController extends Controller {

    /**
     * Check financial permission helper
     * Restricts Cashier & Storekeeper from viewing COGS, Gross/Net profit unless explicitly permitted
     */
    private function checkAccess(string $permission = 'financial.dashboard'): void {
        Auth::requireAuth();

        // RBAC Check
        if (!Auth::hasPermission($permission) && !Auth::hasRole(['super_admin', 'manager', 'accountant'])) {
            Helper::setFlash('danger', 'Huna idhini ya kufikia taarifa za fedha na faida (Access Denied).');
            $this->redirect('/index.php?route=dashboard');
            exit;
        }
    }

    /**
     * Financial Executive Dashboard
     */
    public function dashboard(): void {
        $this->checkAccess('financial.dashboard');

        $period = trim($_GET['period'] ?? 'this_month');
        $customStart = trim($_GET['start_date'] ?? '');
        $customEnd = trim($_GET['end_date'] ?? '');

        $range = Financial::resolveDateRange($period, $customStart, $customEnd);
        $summary = Financial::getExecutiveSummary($range['start_date'], $range['end_date']);
        $trend = Financial::getMonthlyFinancialTrend(6);
        $categoryExpenses = Expense::getExpensesByCategory($range['start_date'], $range['end_date']);
        $topProducts = Financial::getProductProfitability(['date_from' => $range['start_date'], 'date_to' => $range['end_date']]);
        $topProducts = array_slice($topProducts, 0, 5);

        $this->render('financial/dashboard', [
            'pageTitle'        => 'Dashibodi ya Fedha na Faida — NGENZI MOTO SPARE PARTS',
            'activeMenu'       => 'financial_dashboard',
            'range'            => $range,
            'summary'          => $summary,
            'trend'            => $trend,
            'categoryExpenses' => $categoryExpenses,
            'topProducts'      => $topProducts
        ]);
    }

    /**
     * Profit & Loss Financial Report
     */
    public function profitReport(): void {
        $this->checkAccess('financial.profit');

        $period = trim($_GET['period'] ?? 'this_month');
        $customStart = trim($_GET['start_date'] ?? '');
        $customEnd = trim($_GET['end_date'] ?? '');

        $range = Financial::resolveDateRange($period, $customStart, $customEnd);
        $summary = Financial::getExecutiveSummary($range['start_date'], $range['end_date']);
        $categoryExpenses = Expense::getExpensesByCategory($range['start_date'], $range['end_date']);

        $this->render('financial/profit_report', [
            'pageTitle'        => 'Ripoti ya Mapato na Faida (P&L) — NGENZI MOTO SPARE PARTS',
            'activeMenu'       => 'financial_profit',
            'range'            => $range,
            'summary'          => $summary,
            'categoryExpenses' => $categoryExpenses
        ]);
    }

    /**
     * Product Profitability Analysis
     */
    public function productProfit(): void {
        $this->checkAccess('product_profit.view');

        $period = trim($_GET['period'] ?? 'this_month');
        $customStart = trim($_GET['start_date'] ?? '');
        $customEnd = trim($_GET['end_date'] ?? '');
        $range = Financial::resolveDateRange($period, $customStart, $customEnd);

        $filters = [
            'date_from'   => $range['start_date'],
            'date_to'     => $range['end_date'],
            'search'      => trim($_GET['search'] ?? ''),
            'category_id' => trim($_GET['category_id'] ?? ''),
            'brand_id'    => trim($_GET['brand_id'] ?? '')
        ];

        $products = Financial::getProductProfitability($filters);
        $categories = Category::all();
        $brands = Brand::all();

        $this->render('financial/product_profit', [
            'pageTitle'  => 'Uchambuzi wa Faida kwa Bidhaa (Product Profitability) — NGENZI MOTO SPARE PARTS',
            'activeMenu' => 'product_profit',
            'products'   => $products,
            'categories' => $categories,
            'brands'     => $brands,
            'range'      => $range,
            'filters'    => $filters
        ]);
    }

    /**
     * Category & Brand Profitability Analysis
     */
    public function categoryProfit(): void {
        $this->checkAccess('financial.profit');

        $period = trim($_GET['period'] ?? 'this_month');
        $customStart = trim($_GET['start_date'] ?? '');
        $customEnd = trim($_GET['end_date'] ?? '');
        $range = Financial::resolveDateRange($period, $customStart, $customEnd);

        $filters = [
            'date_from' => $range['start_date'],
            'date_to'   => $range['end_date']
        ];

        $categoryProfits = Financial::getCategoryProfitability($filters);
        $brandProfits = Financial::getBrandProfitability($filters);
        $vehicleProfits = Financial::getVehicleCompatibilityProfitability($filters);

        $this->render('financial/category_profit', [
            'pageTitle'       => 'Faida kwa Kategoria, Chapa & Aina ya Chombo — NGENZI MOTO SPARE PARTS',
            'activeMenu'      => 'category_profit',
            'categoryProfits' => $categoryProfits,
            'brandProfits'    => $brandProfits,
            'vehicleProfits'  => $vehicleProfits,
            'range'           => $range
        ]);
    }

    /**
     * Sale / Invoice Profitability
     */
    public function saleProfit(): void {
        $this->checkAccess('sales_profit.view');

        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $period = trim($_GET['period'] ?? 'this_month');
        $customStart = trim($_GET['start_date'] ?? '');
        $customEnd = trim($_GET['end_date'] ?? '');
        $range = Financial::resolveDateRange($period, $customStart, $customEnd);

        $filters = [
            'date_from' => $range['start_date'],
            'date_to'   => $range['end_date'],
            'search'    => trim($_GET['search'] ?? ''),
            'user_id'   => trim($_GET['user_id'] ?? '')
        ];

        $salesData = Financial::getSaleProfitability($filters, $page, 25);
        $cashierProfits = Financial::getCashierProfitability($filters);
        $customerProfits = Financial::getCustomerProfitability($filters);
        $users = User::all();

        $this->render('financial/sale_profit', [
            'pageTitle'       => 'Faida kwa Mauzo, Cashier & Wateja — NGENZI MOTO SPARE PARTS',
            'activeMenu'      => 'sale_profit',
            'sales'           => $salesData['data'],
            'total'           => $salesData['total'],
            'page'            => $salesData['page'],
            'totalPages'      => $salesData['total_pages'],
            'cashierProfits'  => $cashierProfits,
            'customerProfits' => $customerProfits,
            'users'           => $users,
            'range'           => $range,
            'filters'         => $filters
        ]);
    }

    /**
     * Expenses Analysis & Breakdown
     */
    public function expenseAnalysis(): void {
        $this->checkAccess('expenses.reports');

        $period = trim($_GET['period'] ?? 'this_month');
        $customStart = trim($_GET['start_date'] ?? '');
        $customEnd = trim($_GET['end_date'] ?? '');
        $range = Financial::resolveDateRange($period, $customStart, $customEnd);

        $categoryExpenses = Expense::getExpensesByCategory($range['start_date'], $range['end_date']);
        $trend = Expense::getExpenseTrend('daily', $range['start_date'], $range['end_date']);

        $this->render('financial/expense_analysis', [
            'pageTitle'        => 'Uchambuzi wa Matumizi (Expense Analysis) — NGENZI MOTO SPARE PARTS',
            'activeMenu'       => 'expense_analysis',
            'categoryExpenses' => $categoryExpenses,
            'trend'            => $trend,
            'range'            => $range
        ]);
    }

    /**
     * Period Comparison (Current vs Previous)
     */
    public function periodComparison(): void {
        $this->checkAccess('financial.reports');

        $period = trim($_GET['period'] ?? 'this_month');
        $customStart = trim($_GET['start_date'] ?? '');
        $customEnd = trim($_GET['end_date'] ?? '');
        $range = Financial::resolveDateRange($period, $customStart, $customEnd);

        $comparison = Financial::getPeriodComparison(
            $range['start_date'], 
            $range['end_date'], 
            $range['prev_start_date'], 
            $range['prev_end_date']
        );

        $this->render('financial/period_comparison', [
            'pageTitle'   => 'Ulinganifu wa Vipindi vya Kifedha (Period Comparison) — NGENZI MOTO SPARE PARTS',
            'activeMenu'  => 'period_comparison',
            'range'       => $range,
            'comparison'  => $comparison
        ]);
    }
}
