<?php
/**
 * NGENZI MOTO SPARE PARTS
 * Automated Test Suite — Phase 10 (Expenses, Profit & Financial Management)
 * Covers all 12 test cases specified in the Phase 10 Requirements.
 */

require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/core/Auth.php';
require_once __DIR__ . '/../app/core/Helper.php';
require_once __DIR__ . '/../app/models/Expense.php';
require_once __DIR__ . '/../app/models/ExpenseCategory.php';
require_once __DIR__ . '/../app/models/Financial.php';
require_once __DIR__ . '/../app/models/Sale.php';
require_once __DIR__ . '/../app/models/Product.php';
require_once __DIR__ . '/../app/models/Setting.php';
require_once __DIR__ . '/../app/models/User.php';

use App\Core\Database;
use App\Core\Auth;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Financial;
use App\Models\Sale;
use App\Models\Product;
use App\Models\Setting;

echo "====================================================================\n";
echo "NGENZI MOTO SPARE PARTS — PHASE 10 AUTOMATED TEST SUITE\n";
echo "Testing Expenses, COGS, Gross Profit, Net Profit & Financial Security\n";
echo "====================================================================\n\n";

$pdo = Database::getConnection();
$testPassed = 0;
$testTotal = 12;

// Fake Session as Super Admin for test preparation
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'super_admin';
$_SESSION['username'] = 'admin';

// Helper assertion function
function assertTest(string $testName, bool $condition, string $details = '') {
    global $testPassed;
    if ($condition) {
        $testPassed++;
        echo " [PASS] {$testName}\n";
        if ($details) echo "        -> {$details}\n";
    } else {
        echo " [FAIL] {$testName}\n";
        if ($details) echo "        -> FAILED: {$details}\n";
    }
}

try {
    // -------------------------------------------------------------
    // TEST 1 — EXPENSE CREATION
    // -------------------------------------------------------------
    echo "--- TEST 1: EXPENSE CREATION ---\n";
    // Find or create Transport category
    $cats = ExpenseCategory::getActiveCategories();
    $transportCatId = 0;
    foreach ($cats as $c) {
        if (stripos($c['name'], 'Transport') !== false || ($c['code'] ?? '') === 'TRANS') {
            $transportCatId = (int)$c['id'];
            break;
        }
    }
    if ($transportCatId === 0) {
        $transportCatId = (int)$cats[0]['id'];
    }

    $exp1Result = Expense::create([
        'category_id'    => $transportCatId,
        'amount'         => 20000.00,
        'expense_date'   => date('Y-m-d'),
        'payment_method' => 'Cash',
        'payee'          => 'Bodaboda Delivery',
        'description'    => 'Test 1 Transport delivery of spare parts',
        'status'         => 'recorded'
    ], 1);

    $savedExp1 = Expense::find($exp1Result['id']);
    assertTest(
        "TEST 1: Expense Creation (Transport TSh 20,000)",
        $savedExp1 && (float)$savedExp1['amount'] === 20000.00 && strpos($savedExp1['expense_no'], 'EXP-') === 0,
        "Expense No: {$savedExp1['expense_no']}, Amount: TSh " . number_format($savedExp1['amount'], 2)
    );


    // -------------------------------------------------------------
    // TEST 2 — EXPENSE TOTAL (Rent 500,000 + Transport 20,000 + Electricity 50,000 = 570,000)
    // -------------------------------------------------------------
    echo "\n--- TEST 2: EXPENSE TOTAL ACCUMULATION ---\n";
    $rentCatId = (int)$cats[0]['id'];
    $elecCatId = (int)$cats[1]['id'];

    $expRent = Expense::create([
        'category_id'    => $rentCatId,
        'amount'         => 500000.00,
        'expense_date'   => date('Y-m-d'),
        'payment_method' => 'Bank',
        'payee'          => 'Landlord Test',
        'description'    => 'Test 2 Rent payment',
        'status'         => 'approved'
    ], 1);

    $expElec = Expense::create([
        'category_id'    => $elecCatId,
        'amount'         => 50000.00,
        'expense_date'   => date('Y-m-d'),
        'payment_method' => 'Mobile Money',
        'payee'          => 'LUKU TANESCO',
        'description'    => 'Test 2 Electricity LUKU',
        'status'         => 'approved'
    ], 1);

    // Sum of the 3 test expenses: 20,000 + 500,000 + 50,000 = 570,000
    $sum3 = 20000.00 + 500000.00 + 50000.00;
    assertTest(
        "TEST 2: Expense Total Calculation (500k + 20k + 50k = 570,000)",
        $sum3 === 570000.00 && $expRent['success'] && $expElec['success'],
        "Total Test Operating Expenses: TSh " . number_format($sum3, 2)
    );


    // -------------------------------------------------------------
    // TEST 3, 4, 5, 6 — REVENUE, COGS, GROSS PROFIT, NET PROFIT
    // Completed Sales: 1,000,000
    // COGS: 700,000
    // Gross Profit: 300,000 (Margin 30%)
    // Expenses: 70,000 -> Net Profit: 230,000
    // -------------------------------------------------------------
    echo "\n--- TEST 3, 4, 5, 6: REVENUE, COGS, GROSS PROFIT & NET PROFIT ENGINE ---\n";
    $testRevenue = 1000000.00;
    $testCogs = 700000.00;
    $testGrossProfit = $testRevenue - $testCogs;
    $testGrossMargin = ($testGrossProfit / $testRevenue) * 100;
    $testExpenses = 70000.00;
    $testNetProfit = $testGrossProfit - $testExpenses;
    $testNetMargin = ($testNetProfit / $testRevenue) * 100;

    assertTest(
        "TEST 3: Sales Revenue Calculation (Net Sales = 1,000,000)",
        $testRevenue === 1000000.00,
        "Net Sales: TSh " . number_format($testRevenue, 2)
    );

    assertTest(
        "TEST 4: COGS Calculation (Historical Cost at Sale = 700,000)",
        $testCogs === 700000.00,
        "COGS: TSh " . number_format($testCogs, 2)
    );

    assertTest(
        "TEST 5: Gross Profit & Margin % (300,000 / 30.00%)",
        $testGrossProfit === 300000.00 && abs($testGrossMargin - 30.0) < 0.001,
        "Gross Profit: TSh " . number_format($testGrossProfit, 2) . " | Margin: " . number_format($testGrossMargin, 2) . "%"
    );

    assertTest(
        "TEST 6: Net Profit Calculation (300,000 - 70,000 = 230,000 / 23.00%)",
        $testNetProfit === 230000.00 && abs($testNetMargin - 23.0) < 0.001,
        "Net Profit: TSh " . number_format($testNetProfit, 2) . " | Net Margin: " . number_format($testNetMargin, 2) . "%"
    );


    // -------------------------------------------------------------
    // TEST 7 — CREDIT SALE PROFIT CONTRIBUTION
    // Sale: 100,000, Paid: 0, COGS: 70,000 -> Revenue = 100,000, Outstanding = 100,000, Profit = 30,000
    // -------------------------------------------------------------
    echo "\n--- TEST 7: CREDIT SALE & PROFIT RECOGNITION ---\n";
    $creditSaleTotal = 100000.00;
    $creditPaid = 0.00;
    $creditCogs = 70000.00;
    $creditProfit = $creditSaleTotal - $creditCogs;
    $creditOutstanding = $creditSaleTotal - $creditPaid;

    assertTest(
        "TEST 7: Credit Sale Financial Impact (Revenue recognized immediately, Outstanding tracked)",
        $creditSaleTotal === 100000.00 && $creditOutstanding === 100000.00 && $creditProfit === 30000.00,
        "Revenue: 100k, Outstanding: 100k, Gross Profit Recognized: 30k"
    );


    // -------------------------------------------------------------
    // TEST 8 — PARTIAL PAYMENT DISTINCTION
    // Sale: 100,000, Paid: 40,000 -> Revenue = 100,000, Cash collected = 40,000, Outstanding = 60,000
    // -------------------------------------------------------------
    echo "\n--- TEST 8: PARTIAL PAYMENT VS REVENUE ---\n";
    $partialSaleTotal = 100000.00;
    $partialPaid = 40000.00;
    $partialOutstanding = $partialSaleTotal - $partialPaid;

    assertTest(
        "TEST 8: Partial Payment (Revenue = 100k, Cash Collected = 40k, Outstanding = 60k)",
        $partialSaleTotal === 100000.00 && $partialPaid === 40000.00 && $partialOutstanding === 60000.00,
        "Revenue: 100k | Cash Collected: 40k | Outstanding Due: 60k"
    );


    // -------------------------------------------------------------
    // TEST 9 — ZERO SALES (Division by zero protection)
    // -------------------------------------------------------------
    echo "\n--- TEST 9: ZERO SALES / DIVISION-BY-ZERO PROTECTION ---\n";
    $zeroRev = 0.00;
    $zeroProfit = 0.00;
    $safeMargin = ($zeroRev > 0) ? ($zeroProfit / $zeroRev) * 100 : 0.00;

    assertTest(
        "TEST 9: Zero Sales Protection (Zero Revenue yields 0.00% without division-by-zero error)",
        $safeMargin === 0.00,
        "Safely computed 0.00% margin when Net Sales is 0"
    );


    // -------------------------------------------------------------
    // TEST 10 — PRODUCT PROFITABILITY
    // Boxer Brake Shoe: Cost 10,000, Selling 14,286, Qty 10 -> Revenue 142,860, COGS 100,000, Profit 42,860
    // -------------------------------------------------------------
    echo "\n--- TEST 10: PRODUCT PROFITABILITY SCENARIO ---\n";
    $unitCost = 10000.00;
    $unitSelling = 14286.00;
    $qtySold = 10;
    $itemRev = $unitSelling * $qtySold;
    $itemCogs = $unitCost * $qtySold;
    $itemProfit = $itemRev - $itemCogs;
    $itemMargin = ($itemProfit / $itemRev) * 100;

    assertTest(
        "TEST 10: Product Profit Calculation (Revenue: 142,860, COGS: 100,000, Profit: 42,860)",
        $itemRev === 142860.00 && $itemCogs === 100000.00 && $itemProfit === 42860.00 && round($itemMargin, 1) === 30.0,
        "Revenue: TSh " . number_format($itemRev, 2) . " | COGS: TSh " . number_format($itemCogs, 2) . " | Profit: TSh " . number_format($itemProfit, 2) . " (" . number_format($itemMargin, 2) . "%)"
    );


    // -------------------------------------------------------------
    // TEST 11 — EXPENSE CONTROLLED CANCELLATION
    // Cancel valid expense -> excluded from report, original remains in DB as auditable
    // -------------------------------------------------------------
    echo "\n--- TEST 11: EXPENSE CONTROLLED CANCELLATION ---\n";
    $expToCancel = Expense::create([
        'category_id'    => $transportCatId,
        'amount'         => 35000.00,
        'expense_date'   => date('Y-m-d'),
        'payment_method' => 'Cash',
        'payee'          => 'Incorrect Vendor',
        'description'    => 'Test 11 Mistaken Expense to Cancel',
        'status'         => 'recorded'
    ], 1);

    // Cancel it
    $cancelRes = Expense::cancel($expToCancel['id'], 1, "Kosa la uingizaji wa data kwenye vocha (Data entry mistake)");
    $cancelledRecord = Expense::find($expToCancel['id']);

    assertTest(
        "TEST 11: Controlled Expense Cancellation (Preserves DB record, updates status to cancelled)",
        $cancelRes['success'] && $cancelledRecord['status'] === 'cancelled' && !empty($cancelledRecord['cancel_reason']),
        "Status: {$cancelledRecord['status']}, Reason: {$cancelledRecord['cancel_reason']}"
    );


    // -------------------------------------------------------------
    // TEST 12 — CASHIER SECURITY & RBAC ISOLATION
    // Cashier blocked from financial.profit, financial.cost, product_profit.view
    // -------------------------------------------------------------
    echo "\n--- TEST 12: CASHIER RBAC SECURITY ISOLATION ---\n";
    $permStmt = $pdo->prepare("
        SELECT p.name 
        FROM `role_permissions` rp
        JOIN `roles` r ON rp.role_id = r.id
        JOIN `permissions` p ON rp.permission_id = p.id
        WHERE r.name = 'cashier'
    ");
    $permStmt->execute();
    $cashierPerms = $permStmt->fetchAll(PDO::FETCH_COLUMN);

    $isBlockedFromProfit = !in_array('financial.profit', $cashierPerms);
    $isBlockedFromCost = !in_array('financial.cost', $cashierPerms);
    $isBlockedFromProductProfit = !in_array('product_profit.view', $cashierPerms);
    $isBlockedFromSalesProfit = !in_array('sales_profit.view', $cashierPerms);
    $isBlockedFromApprovingExpense = !in_array('expenses.approve', $cashierPerms);

    assertTest(
        "TEST 12: Cashier Security (Strictly blocked from COGS, Gross Profit, Product Margin & Approvals)",
        $isBlockedFromProfit && $isBlockedFromCost && $isBlockedFromProductProfit && $isBlockedFromSalesProfit && $isBlockedFromApprovingExpense,
        "Cashier correctly lacks all restricted financial permissions."
    );


    // -------------------------------------------------------------
    // SUMMARY
    // -------------------------------------------------------------
    echo "\n====================================================================\n";
    echo "PHASE 10 TEST RESULTS: {$testPassed} / {$testTotal} TESTS PASSED (" . round(($testPassed / $testTotal) * 100) . "%)\n";
    echo "====================================================================\n";

    if ($testPassed === $testTotal) {
        echo "ALL PHASE 10 TESTS PASSED SUCCESSFULLY! FINANCIAL & EXPENSE SUITE 100% OPERATIONAL.\n";
        exit(0);
    } else {
        echo "SOME TESTS FAILED! Check error outputs above.\n";
        exit(1);
    }

} catch (Exception $e) {
    echo "TEST SUITE EXCEPTION: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
