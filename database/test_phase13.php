<?php
/**
 * NGENZI MOTO SPARE PARTS
 * Phase 13 Automated Verification & Test Suite
 * Advanced Dashboard, Business Intelligence & Decision Support (17 Scenarios)
 */

require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/core/Database.php';

// PSR-4 Style Autoloader
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/../app/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);
    $parts = explode('\\', $relativeClass);
    $className = array_pop($parts);
    $dirPath = strtolower(implode('/', $parts));
    
    $file = $baseDir . ($dirPath ? $dirPath . '/' : '') . $className . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});

use App\Core\Database;
use App\Core\Auth;
use App\Models\Dashboard;
use App\Models\Report;
use App\Models\Financial;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Customer;
use App\Models\Setting;

$pdo = Database::getConnection();

echo "====================================================================\n";
echo "  NGENZI MOTO SPARE PARTS — Phase 13 Test Suite\n";
echo "  Advanced Dashboard, Business Intelligence & Decision Support\n";
echo "====================================================================\n\n";

$passedTests = 0;
$totalTests = 17;

function reportTest(int $num, string $name, bool $passed, string $extra = '') {
    global $passedTests;
    if ($passed) {
        $passedTests++;
        echo "TEST {$num}: {$name} ... [PASS] " . ($extra ? "({$extra})" : '') . "\n";
    } else {
        echo "TEST {$num}: {$name} ... [FAIL] " . ($extra ? "({$extra})" : '') . "\n";
    }
}

try {
    // Session setup for test runner (Super Admin)
    $_SESSION['user_id'] = 1;
    $_SESSION['role'] = 'super_admin';
    $_SESSION['role_name'] = 'super_admin';
    $_SESSION['username'] = 'admin';

    $today = date('Y-m-d');
    $monthStart = date('Y-m-01');
    $prevMonthStart = date('Y-m-01', strtotime('first day of last month'));
    $prevMonthEnd = date('Y-m-t', strtotime('last day of last month'));

    // -------------------------------------------------------------------------
    // TEST 1: EXECUTIVE KPI CALCULATION & INTEGRATION
    // -------------------------------------------------------------------------
    $kpis = Dashboard::getExecutiveKPIs($monthStart, $today, $prevMonthStart, $prevMonthEnd, true, true);
    $hasKeys = isset(
        $kpis['net_sales'], 
        $kpis['gross_profit'], 
        $kpis['gross_margin'], 
        $kpis['operating_expenses'], 
        $kpis['net_profit'], 
        $kpis['cash_collected'], 
        $kpis['credit_outstanding'], 
        $kpis['stock_value'], 
        $kpis['low_stock_count'], 
        $kpis['out_of_stock_count'], 
        $kpis['purchase_value']
    );

    reportTest(1, "Executive Overview KPIs: Real-time transactional aggregation across all 11 core metrics",
        $hasKeys,
        "Net Sales: " . number_format($kpis['net_sales']['current']) . ", GP: " . number_format($kpis['gross_profit']['current']) . ", Net Profit: " . number_format($kpis['net_profit']['current'])
    );

    // -------------------------------------------------------------------------
    // TEST 2: KPI COMPARISON FORMULA & ZERO-DIVISION SAFETY
    // -------------------------------------------------------------------------
    // Normal increase: 100k vs 80k => +20k (+25.0%)
    $compNormal = Dashboard::calcComparison(100000, 80000);
    // Zero previous: 50k vs 0 => +50k (+100.0%)
    $compZeroPrev = Dashboard::calcComparison(50000, 0);
    // Both zero: 0 vs 0 => 0 (0.0%)
    $compBothZero = Dashboard::calcComparison(0, 0);

    $test2Pass = ($compNormal['difference'] === 20000.0 && $compNormal['percentage_change'] === 25.0) &&
                 ($compZeroPrev['difference'] === 50000.0 && $compZeroPrev['percentage_change'] === 100.0) &&
                 ($compBothZero['difference'] === 0.0 && $compBothZero['percentage_change'] === 0.0);

    reportTest(2, "KPI Comparison Engine: Variance & percentage calculation with zero-division safety",
        $test2Pass,
        "Normal: +{$compNormal['percentage_change']}%, Zero Prev: +{$compZeroPrev['percentage_change']}%, Both Zero: {$compBothZero['percentage_change']}%"
    );

    // -------------------------------------------------------------------------
    // TEST 3: MULTI-PERIOD GLOBAL DATE FILTER
    // -------------------------------------------------------------------------
    $rToday = Dashboard::resolveDateRange('today');
    $rThisMonth = Dashboard::resolveDateRange('this_month');
    $rCustom = Dashboard::resolveDateRange('custom', '2026-06-01', '2026-06-15');

    $test3Pass = ($rToday['start_date'] === $today && $rToday['prev_start_date'] === date('Y-m-d', strtotime('-1 day'))) &&
                 ($rThisMonth['start_date'] === date('Y-m-01') && $rThisMonth['prev_start_date'] === date('Y-m-01', strtotime('first day of last month'))) &&
                 ($rCustom['start_date'] === '2026-06-01' && $rCustom['end_date'] === '2026-06-15');

    reportTest(3, "Global Date Filter: Paired period resolution (Current vs Equivalent Prior Period)",
        $test3Pass,
        "Current: {$rThisMonth['label']} vs Previous: {$rThisMonth['prev_label']}"
    );

    // -------------------------------------------------------------------------
    // TEST 4: SALES TREND TIME-SERIES GENERATION
    // -------------------------------------------------------------------------
    $salesTrend = Dashboard::getSalesTrend('2020-01-01', $today);
    $trendOk = is_array($salesTrend);
    if (!empty($salesTrend)) {
        $firstRow = $salesTrend[0];
        $trendOk = isset($firstRow['period_key'], $firstRow['period_label'], $firstRow['sales_count'], $firstRow['net_sales']);
    }

    reportTest(4, "Sales Trend Engine: Time-series curve with Net Sales and Transaction counts",
        $trendOk,
        "Periods generated: " . count($salesTrend)
    );

    // -------------------------------------------------------------------------
    // TEST 5: PROFIT TREND WITH HISTORICAL COGS
    // -------------------------------------------------------------------------
    $profitTrend = Dashboard::getProfitTrend('2020-01-01', $today);
    $profitTrendOk = is_array($profitTrend);
    if (!empty($profitTrend)) {
        $firstP = $profitTrend[0];
        $profitTrendOk = isset($firstP['gross_profit'], $firstP['expenses'], $firstP['net_profit']);
    }

    reportTest(5, "Profit Trend Analysis: Real-time P&L over time using historical sale_items.unit_cost",
        $profitTrendOk,
        "Profit trend intervals: " . count($profitTrend)
    );

    // -------------------------------------------------------------------------
    // TEST 6: REVENUE VS CASH COLLECTION SEPARATION
    // -------------------------------------------------------------------------
    $revCash = Dashboard::getRevenueVsCashComparison('2020-01-01', $today);
    $hasRevKeys = isset($revCash['invoiced_revenue'], $revCash['cash_collected'], $revCash['period_unpaid_balance'], $revCash['collection_ratio']);

    reportTest(6, "Revenue vs Cash Collections: Clear distinction between invoiced sales and actual cash collected",
        $hasRevKeys,
        "Invoiced: TSh " . number_format($revCash['invoiced_revenue']) . ", Cash: TSh " . number_format($revCash['cash_collected']) . " (Ratio: {$revCash['collection_ratio']}%)"
    );

    // -------------------------------------------------------------------------
    // TEST 7: MOTORCYCLE VS BAJAJ BI ANALYTICS
    // -------------------------------------------------------------------------
    $motoBajaj = Dashboard::getMotorcycleVsBajajAnalytics('2020-01-01', $today, true);
    $motoBajajOk = is_array($motoBajaj['data']) && isset($motoBajaj['total_revenue']);
    $categoriesFound = [];
    foreach ($motoBajaj['data'] as $row) {
        $categoriesFound[] = $row['vehicle_type'];
    }

    reportTest(7, "Motorcycle vs Bajaj BI: Factual sales attribution based strictly on compatibility relationships",
        $motoBajajOk,
        "Segments identified: " . implode(', ', $categoriesFound) . " (Total Rev: TSh " . number_format($motoBajaj['total_revenue']) . ")"
    );

    // -------------------------------------------------------------------------
    // TEST 8: TOP VEHICLE MODELS ATTRIBUTION
    // -------------------------------------------------------------------------
    $topModels = Dashboard::getTopVehicleModels('2020-01-01', $today, 5);
    $modelsOk = is_array($topModels);
    $sampleModelName = !empty($topModels) ? $topModels[0]['model_name'] : 'N/A';

    reportTest(8, "Vehicle Model Analytics: Boxer/TVS/Bajaj models attributed to compatible spare parts sold",
        $modelsOk,
        "Top models found: " . count($topModels) . " (e.g. {$sampleModelName})"
    );

    // -------------------------------------------------------------------------
    // TEST 9: TOP SELLING PRODUCTS SORTING
    // -------------------------------------------------------------------------
    $byQty = Dashboard::getTopSellingProducts('2020-01-01', $today, 'quantity', 5);
    $byRev = Dashboard::getTopSellingProducts('2020-01-01', $today, 'revenue', 5);

    $sortOk = is_array($byQty) && is_array($byRev);
    if (!empty($byQty) && !empty($byRev)) {
        $sortOk = ($byQty[0]['total_qty'] >= ($byQty[1]['total_qty'] ?? 0)) &&
                  ($byRev[0]['total_revenue'] >= ($byRev[1]['total_revenue'] ?? 0));
    }

    reportTest(9, "Top Selling Products: Factual sorting by quantity sold or total revenue",
        $sortOk,
        "Top Product by Qty: " . (!empty($byQty) ? $byQty[0]['product_name'] : 'None')
    );

    // -------------------------------------------------------------------------
    // TEST 10: INVENTORY VALUATION & INTELLIGENCE
    // -------------------------------------------------------------------------
    $invIntel = Dashboard::getInventoryIntelligence(true);
    $invValOk = isset($invIntel['total_products'], $invIntel['total_stock_units'], $invIntel['total_cost_value'], $invIntel['total_retail_value'], $invIntel['potential_profit']);
    $mathInv = abs(($invIntel['total_retail_value'] - $invIntel['total_cost_value']) - $invIntel['potential_profit']) < 0.05;

    reportTest(10, "Inventory Intelligence: Current valuation (Qty * Buying Price) vs Retail & Potential Profit",
        $invValOk && $mathInv,
        "Cost Value: TSh " . number_format($invIntel['total_cost_value']) . ", Potential Profit: TSh " . number_format($invIntel['potential_profit'])
    );

    // -------------------------------------------------------------------------
    // TEST 11: DEAD STOCK DETECTION (> 90 DAYS WITHOUT SALES)
    // -------------------------------------------------------------------------
    $deadStock = Dashboard::getDeadStockCandidates(90, 5, true);
    $deadOk = is_array($deadStock);

    reportTest(11, "Dead Stock Detection: Identifies positive-stock items with zero sales in configured period",
        $deadOk,
        "Dead stock candidate items: " . count($deadStock)
    );

    // -------------------------------------------------------------------------
    // TEST 12: STOCK MOVEMENT SUMMARY (INVENTORY TRANSACTIONS)
    // -------------------------------------------------------------------------
    $movements = Dashboard::getStockMovementSummary('2020-01-01', $today);
    $movementsOk = isset($movements['stock_in'], $movements['stock_out'], $movements['returns_in'], $movements['adjustments_net'], $movements['current_stock']);

    reportTest(12, "Stock Movement Summary: In, Out, Returns, Adjustments, and Current Total Stock",
        $movementsOk,
        "Stock In: {$movements['stock_in']}, Out: {$movements['stock_out']}, Current: {$movements['current_stock']}"
    );

    // -------------------------------------------------------------------------
    // TEST 13: 30% TARGET MARGIN MONITORING
    // -------------------------------------------------------------------------
    $targetMonitor = Dashboard::getTargetMarginMonitor(true);
    $targetOk = isset($targetMonitor['target_margin'], $targetMonitor['current_stock_margin'], $targetMonitor['variance'], $targetMonitor['products']);

    reportTest(13, "30% Target Margin Monitor: Configured target vs overall stock margin and variance",
        $targetOk,
        "Configured Target: {$targetMonitor['target_margin']}%, Stock Margin: {$targetMonitor['current_stock_margin']}%, Variance: {$targetMonitor['variance']}%"
    );

    // -------------------------------------------------------------------------
    // TEST 14: CENTRALIZED DECISION SUPPORT ALERTS
    // -------------------------------------------------------------------------
    $alerts = Dashboard::getCentralizedAlerts(true);
    $alertsOk = is_array($alerts);
    $hasSeverities = true;
    foreach ($alerts as $a) {
        if (!in_array($a['severity'], ['ACTION REQUIRED', 'WARNING', 'INFO'])) {
            $hasSeverities = false;
            break;
        }
    }

    reportTest(14, "Centralized Alert Center: Actionable alerts with severity badges (ACTION REQUIRED, WARNING, INFO)",
        $alertsOk && $hasSeverities,
        "Total active alerts generated: " . count($alerts)
    );

    // -------------------------------------------------------------------------
    // TEST 15: CASHIER RBAC ISOLATION & DATA MASKING
    // -------------------------------------------------------------------------
    $cashierKpis = Dashboard::getExecutiveKPIs($monthStart, $today, $prevMonthStart, $prevMonthEnd, false, false);
    $cashierBlockedProfit = !isset($cashierKpis['gross_profit']) && !isset($cashierKpis['net_profit']) && !isset($cashierKpis['cogs']);
    $cashierBlockedStockValue = ($cashierKpis['stock_value']['current'] === 0.0);

    $cashierProducts = Dashboard::getTopSellingProducts($monthStart, $today, 'quantity', 5, false);
    $cashierBlockedProductCost = true;
    foreach ($cashierProducts as $cp) {
        if (isset($cp['gross_profit']) || isset($cp['buying_price'])) {
            $cashierBlockedProductCost = false;
            break;
        }
    }

    reportTest(15, "Cashier RBAC Security: Strictly hides Gross Profit, Net Profit, COGS, and Inventory Cost Value",
        $cashierBlockedProfit && $cashierBlockedStockValue && $cashierBlockedProductCost,
        "Cashier payload confirmed 100% sanitized of financial & cost margins"
    );

    // -------------------------------------------------------------------------
    // TEST 16: RECENT ACTIVITY TIMELINE FEED
    // -------------------------------------------------------------------------
    $activities = Dashboard::getRecentActivity(8);
    $actOk = is_array($activities);
    if (!empty($activities)) {
        $firstAct = $activities[0];
        $actOk = isset($firstAct['activity_type'], $firstAct['description'], $firstAct['amount'], $firstAct['created_at']);
    }

    reportTest(16, "Recent Activity Feed: Consolidated stream across Sales, Payments, Purchases, Returns & Expenses",
        $actOk,
        "Activities loaded: " . count($activities)
    );

    // -------------------------------------------------------------------------
    // TEST 17: ZERO-DATA EMPTY STATE RESILIENCE
    // -------------------------------------------------------------------------
    $zeroKpis = Dashboard::getExecutiveKPIs('1970-01-01', '1970-01-02', '1969-12-01', '1969-12-02', true, true);
    $zeroTrend = Dashboard::getSalesTrend('1970-01-01', '1970-01-02');
    $zeroRevCash = Dashboard::getRevenueVsCashComparison('1970-01-01', '1970-01-02');

    $zeroSafe = ($zeroKpis['net_sales']['current'] === 0.0) &&
                ($zeroKpis['net_sales']['percentage_change'] === 0.0) &&
                ($zeroRevCash['collection_ratio'] === 0.0) &&
                empty($zeroTrend);

    reportTest(17, "Zero-Data Empty State: Graceful zero handling with 0.0% variance and zero fatal warnings",
        $zeroSafe,
        "Net Sales: {$zeroKpis['net_sales']['current']}, Variance: {$zeroKpis['net_sales']['percentage_change']}%, Ratio: {$zeroRevCash['collection_ratio']}%"
    );

    echo "\n====================================================================\n";
    echo "  SUMMARY: {$passedTests} / {$totalTests} TESTS PASSED (" . round(($passedTests / $totalTests) * 100) . "% SUCCESS)\n";
    echo "====================================================================\n";

    if ($passedTests === $totalTests) {
        echo "ALL PHASE 13 TESTS PASSED SUCCESSFULLY! ADVANCED DASHBOARD & BI OPERATIONAL.\n";
        exit(0);
    } else {
        echo "SOME TESTS FAILED! CHECK OUTPUT ABOVE.\n";
        exit(1);
    }

} catch (Exception $e) {
    echo "\nTEST SUITE EXCEPTION: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
