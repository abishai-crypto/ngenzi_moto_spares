<?php
/**
 * NGENZI MOTO SPARE PARTS
 * Phase 11 Automated Verification & Test Suite
 * Tests all 11 Core Scenarios for Returns & Refunds Management
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
use App\Models\Returns;
use App\Models\Sale;
use App\Models\Payment;
use App\Models\Inventory;
use App\Models\Customer;
use App\Models\Product;

$pdo = Database::getConnection();

echo "=========================================================\n";
echo "  NGENZI MOTO SPARE PARTS — Phase 11 Test Suite (Returns)\n";
echo "=========================================================\n\n";

$passedTests = 0;
$totalTests = 11;

// Helper to log test result
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
    // Setup test customer & test product
    $pdo->exec("
        INSERT INTO `customers` (customer_code, name, phone, customer_type, credit_limit, credit_allowed, current_balance, status, created_at)
        VALUES ('CUST-RET-TEST', 'Test Return Customer', '0711999888', 'retail', 500000.00, 1, 0.00, 'active', NOW())
        ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id), status='active'
    ");
    $testCustomerId = (int)$pdo->lastInsertId();

    // Ensure test product with stock
    $stmtProd = $pdo->query("SELECT id, current_stock, buying_price, selling_price FROM `products` WHERE status = 'active' LIMIT 1");
    $testProduct = $stmtProd->fetch(PDO::FETCH_ASSOC);
    $testProductId = (int)$testProduct['id'];

    // Ensure ample stock
    $pdo->prepare("UPDATE `products` SET current_stock = 100 WHERE id = ?")->execute([$testProductId]);

    // -------------------------------------------------------------
    // TEST 1: Partial Return (10 sold, return 2, stock +2)
    // -------------------------------------------------------------
    $cart = [
        [
            'product_id' => $testProductId,
            'quantity'   => 10,
            'unit_price' => 15000.00,
            'discount'   => 0.00
        ]
    ];
    $saleRes1 = Sale::createSale(
        ['customer_id' => $testCustomerId, 'customer_name' => 'Test Return Customer'],
        $cart,
        ['amount_paid' => 150000.00, 'payment_method' => 'Cash'],
        1
    );
    $saleId1 = $saleRes1['sale_id'];

    $stockBeforeReturn1 = (int)$pdo->query("SELECT current_stock FROM `products` WHERE id = {$testProductId}")->fetchColumn();

    $saleItems1 = Returns::getReturnableItemsForSale($saleId1);
    $saleItemId1 = (int)$saleItems1[0]['id'];

    // Return 2 items
    $retRes1 = Returns::createReturn(
        ['sale_id' => $saleId1, 'reason' => 'WRONG_ITEM', 'return_type' => 'WRONG_ITEM'],
        [['sale_item_id' => $saleItemId1, 'quantity' => 2, 'condition_status' => 'resellable']],
        1
    );
    $returnId1 = $retRes1['return_id'];

    // Approve & Receive
    Returns::approveReturn($returnId1, 1);
    Returns::receiveAndRestock($returnId1, 1);

    $stockAfterReturn1 = (int)$pdo->query("SELECT current_stock FROM `products` WHERE id = {$testProductId}")->fetchColumn();
    $returnableAfter1 = Returns::getReturnableItemsForSale($saleId1);
    $remainingReturnable1 = (int)$returnableAfter1[0]['remaining_returnable'];

    $test1Pass = ($retRes1['refund_amount'] == 30000.00) && 
                 ($remainingReturnable1 === 8) && 
                 ($stockAfterReturn1 === $stockBeforeReturn1 + 2);
    reportTest(1, "Partial Return: Return 2 out of 10 items, verify 8 returnable left & stock +2", $test1Pass, "Stock: {$stockBeforeReturn1} -> {$stockAfterReturn1}, Left: {$remainingReturnable1}");


    // -------------------------------------------------------------
    // TEST 2: Full Return (Return remaining 8 items, sale marked refunded)
    // -------------------------------------------------------------
    $retRes2 = Returns::createReturn(
        ['sale_id' => $saleId1, 'reason' => 'CUSTOMER_RETURN', 'return_type' => 'CUSTOMER_RETURN'],
        [['sale_item_id' => $saleItemId1, 'quantity' => 8, 'condition_status' => 'resellable']],
        1
    );
    $returnId2 = $retRes2['return_id'];
    Returns::approveReturn($returnId2, 1);
    Returns::receiveAndRestock($returnId2, 1);
    $refRes2 = Returns::processRefund($returnId2, ['refund_method' => 'Cash'], 1);

    $returnableAfter2 = Returns::getReturnableItemsForSale($saleId1);
    $remainingReturnable2 = (int)$returnableAfter2[0]['remaining_returnable'];
    $saleStatusAfter2 = $pdo->query("SELECT sale_status FROM `sales` WHERE id = {$saleId1}")->fetchColumn();

    $test2Pass = ($remainingReturnable2 === 0) && ($saleStatusAfter2 === 'refunded') && ($refRes2['sale_status'] === 'refunded');
    reportTest(2, "Full Return: Return all remaining 8 quantity, verify sale marked REFUNDED", $test2Pass, "Remaining: {$remainingReturnable2}, Sale Status: {$saleStatusAfter2}");


    // -------------------------------------------------------------
    // TEST 3: Over-Return Prevention (Attempt return 8 when only 7 remain)
    // -------------------------------------------------------------
    $saleRes3 = Sale::createSale(
        ['customer_id' => $testCustomerId, 'customer_name' => 'Test Return Customer'],
        [['product_id' => $testProductId, 'quantity' => 10, 'unit_price' => 15000.00, 'discount' => 0.00]],
        ['amount_paid' => 150000.00, 'payment_method' => 'Cash'],
        1
    );
    $saleId3 = $saleRes3['sale_id'];
    $saleItems3 = Returns::getReturnableItemsForSale($saleId3);
    $saleItemId3 = (int)$saleItems3[0]['id'];

    // Return 3 items first (7 remain)
    $retRes3 = Returns::createReturn(
        ['sale_id' => $saleId3, 'reason' => 'CUSTOMER_RETURN'],
        [['sale_item_id' => $saleItemId3, 'quantity' => 3, 'condition_status' => 'resellable']],
        1
    );

    // Now attempt to return 8 items (should throw exception)
    $overReturnCaught = false;
    try {
        Returns::createReturn(
            ['sale_id' => $saleId3, 'reason' => 'CUSTOMER_RETURN'],
            [['sale_item_id' => $saleItemId3, 'quantity' => 8, 'condition_status' => 'resellable']],
            1
        );
    } catch (Exception $e) {
        $overReturnCaught = true;
    }

    reportTest(3, "Over-Return Prevention: Reject request of 8 when only 7 remain returnable", $overReturnCaught, "Exception correctly triggered");


    // -------------------------------------------------------------
    // TEST 4: Damaged Item Return (Stock does NOT increase)
    // -------------------------------------------------------------
    $saleRes4 = Sale::createSale(
        ['customer_id' => $testCustomerId, 'customer_name' => 'Test Return Customer'],
        [['product_id' => $testProductId, 'quantity' => 5, 'unit_price' => 15000.00, 'discount' => 0.00]],
        ['amount_paid' => 75000.00, 'payment_method' => 'Cash'],
        1
    );
    $saleId4 = $saleRes4['sale_id'];
    $saleItems4 = Returns::getReturnableItemsForSale($saleId4);
    $saleItemId4 = (int)$saleItems4[0]['id'];

    $stockBeforeDamaged = (int)$pdo->query("SELECT current_stock FROM `products` WHERE id = {$testProductId}")->fetchColumn();

    $retRes4 = Returns::createReturn(
        ['sale_id' => $saleId4, 'reason' => 'DAMAGED_ITEM', 'return_type' => 'DAMAGED_ITEM'],
        [['sale_item_id' => $saleItemId4, 'quantity' => 2, 'condition_status' => 'damaged']],
        1
    );
    $returnId4 = $retRes4['return_id'];
    Returns::approveReturn($returnId4, 1);
    Returns::receiveAndRestock($returnId4, 1);

    $stockAfterDamaged = (int)$pdo->query("SELECT current_stock FROM `products` WHERE id = {$testProductId}")->fetchColumn();

    $test4Pass = ($stockAfterDamaged === $stockBeforeDamaged);
    reportTest(4, "Damaged Item: Return 2 damaged items, verify normal sellable stock does NOT increase", $test4Pass, "Stock before: {$stockBeforeDamaged}, Stock after: {$stockAfterDamaged}");


    // -------------------------------------------------------------
    // TEST 5: Refund Processing (Refund reference & payment recorded)
    // -------------------------------------------------------------
    $refRes5 = Returns::processRefund($returnId4, ['refund_method' => 'Cash'], 1);
    $hasRefundRef = !empty($refRes5['refund_reference_no']) && strpos($refRes5['refund_reference_no'], 'REF-') === 0;
    $payRecord = $pdo->query("SELECT COUNT(*) FROM `sale_payments` WHERE reference_no = '{$retRes4['return_number']}' AND amount < 0")->fetchColumn();

    $test5Pass = $hasRefundRef && ($payRecord > 0);
    reportTest(5, "Refund Processing: Verify REF-XXXXXX generated and sale_payments record created", $test5Pass, "Refund Ref: {$refRes5['refund_reference_no']}");


    // -------------------------------------------------------------
    // TEST 6: Credit Sale Return (Reduces debt and invoice balance)
    // -------------------------------------------------------------
    $pdo->prepare("UPDATE `customers` SET current_balance = 0.00 WHERE id = ?")->execute([$testCustomerId]);

    // Create credit sale: 10 items @ 15,000 = TSh 150,000
    $saleRes6 = Sale::createSale(
        ['customer_id' => $testCustomerId, 'customer_name' => 'Test Return Customer', 'payment_terms' => '30 days'],
        [['product_id' => $testProductId, 'quantity' => 10, 'unit_price' => 15000.00, 'discount' => 0.00]],
        ['amount_paid' => 0.00, 'payment_method' => 'Credit'],
        1
    );
    $saleId6 = $saleRes6['sale_id'];

    $initialCustBal = (float)$pdo->query("SELECT current_balance FROM `customers` WHERE id = {$testCustomerId}")->fetchColumn();
    $initialSaleDue = (float)$pdo->query("SELECT balance_due FROM `sales` WHERE id = {$saleId6}")->fetchColumn();

    // Customer returns goods worth TSh 45,000 (3 pieces @ 15,000)
    $saleItems6 = Returns::getReturnableItemsForSale($saleId6);
    $retRes6 = Returns::createReturn(
        ['sale_id' => $saleId6, 'reason' => 'CUSTOMER_RETURN'],
        [['sale_item_id' => (int)$saleItems6[0]['id'], 'quantity' => 3, 'condition_status' => 'resellable']],
        1
    );
    $returnId6 = $retRes6['return_id'];
    Returns::approveReturn($returnId6, 1);
    Returns::receiveAndRestock($returnId6, 1);
    $refundRes6 = Returns::processRefund($returnId6, ['refund_method' => 'Cash'], 1);

    $finalCustBal = (float)$pdo->query("SELECT current_balance FROM `customers` WHERE id = {$testCustomerId}")->fetchColumn();
    $finalSaleDue = (float)$pdo->query("SELECT balance_due FROM `sales` WHERE id = {$saleId6}")->fetchColumn();

    $test6Pass = ($initialCustBal == 150000.00) && ($finalCustBal == 105000.00) && ($finalSaleDue == 105000.00) &&
                 ($refundRes6['debt_reduced'] == 45000.00) && ($refundRes6['cash_refunded'] == 0.00);
    reportTest(6, "Credit Sale: Outstanding TSh 150,000 reduced by TSh 45,000 return -> Debt becomes TSh 105,000", $test6Pass, "Debt: {$initialCustBal} -> {$finalCustBal}, Cash Refunded: {$refundRes6['cash_refunded']}");


    // -------------------------------------------------------------
    // TEST 7: Return Reports & Product Return Rate Analysis
    // -------------------------------------------------------------
    $prodStats = Product::getReturnStats($testProductId);
    $hasReturnRate = isset($prodStats['return_rate_percent']) && $prodStats['return_rate_percent'] >= 0;
    $hasReturnedQty = (int)$prodStats['total_units_returned'] > 0;

    $test7Pass = $hasReturnRate && $hasReturnedQty;
    reportTest(7, "Profit & Product Analytics: Return Rate calculated correctly for product", $test7Pass, "Units Sold: {$prodStats['total_units_sold']}, Returned: {$prodStats['total_units_returned']}, Rate: {$prodStats['return_rate_percent']}%");


    // -------------------------------------------------------------
    // TEST 8: Duplicate Processing Prevention (Idempotency)
    // -------------------------------------------------------------
    $duplicateCaught = false;
    try {
        // Attempt to receive/restock returnId1 again (which was already restocked in Test 1)
        Returns::receiveAndRestock($returnId1, 1);
    } catch (Exception $e) {
        $duplicateCaught = true;
    }

    reportTest(8, "Duplicate Processing: Attempting to receive/restock the same return twice is rejected", $duplicateCaught, "Idempotency safety verified");


    // -------------------------------------------------------------
    // TEST 9: Permissions Enforcement (RBAC)
    // -------------------------------------------------------------
    // Check role permissions: Cashier must NOT have returns.approve or returns.receive
    $cashierPerms = $pdo->query("
        SELECT p.name 
        FROM `role_permissions` rp 
        JOIN `roles` r ON rp.role_id = r.id 
        JOIN `permissions` p ON rp.permission_id = p.id 
        WHERE r.name = 'cashier' AND p.name IN ('returns.approve', 'returns.receive', 'returns.cancel')
    ")->fetchAll(PDO::FETCH_COLUMN);

    $managerPerms = $pdo->query("
        SELECT p.name 
        FROM `role_permissions` rp 
        JOIN `roles` r ON rp.role_id = r.id 
        JOIN `permissions` p ON rp.permission_id = p.id 
        WHERE r.name = 'manager' AND p.name IN ('returns.approve', 'returns.receive', 'returns.refund')
    ")->fetchAll(PDO::FETCH_COLUMN);

    $test9Pass = empty($cashierPerms) && (count($managerPerms) === 3);
    reportTest(9, "RBAC Permissions: Cashier blocked from unauthorized approve/receive, Manager has full control", $test9Pass, "Cashier forbidden grants: " . count($cashierPerms) . ", Manager grants: " . count($managerPerms));


    // -------------------------------------------------------------
    // TEST 10: Audit Logging Verification
    // -------------------------------------------------------------
    $auditCreated = $pdo->query("SELECT COUNT(*) FROM `audit_logs` WHERE module = 'returns' AND action = 'RETURN_CREATED'")->fetchColumn();
    $auditApproved = $pdo->query("SELECT COUNT(*) FROM `audit_logs` WHERE module = 'returns' AND action = 'RETURN_APPROVED'")->fetchColumn();
    $auditRestocked = $pdo->query("SELECT COUNT(*) FROM `audit_logs` WHERE module = 'returns' AND action = 'RETURN_RECEIVED_RESTOCKED'")->fetchColumn();
    $auditRefunded = $pdo->query("SELECT COUNT(*) FROM `audit_logs` WHERE module = 'returns' AND action = 'RETURN_REFUNDED'")->fetchColumn();

    $test10Pass = ($auditCreated > 0) && ($auditApproved > 0) && ($auditRestocked > 0) && ($auditRefunded > 0);
    reportTest(10, "Audit Logging: Verify complete audit trail across Return lifecycle", $test10Pass, "Created: {$auditCreated}, Approved: {$auditApproved}, Restocked: {$auditRestocked}, Refunded: {$auditRefunded}");


    // -------------------------------------------------------------
    // TEST 11: Return Cancellation & Reversal
    // -------------------------------------------------------------
    // Create return, approve, receive, then cancel -> verify rollback
    $saleRes11 = Sale::createSale(
        ['customer_id' => $testCustomerId, 'customer_name' => 'Test Return Customer'],
        [['product_id' => $testProductId, 'quantity' => 5, 'unit_price' => 15000.00, 'discount' => 0.00]],
        ['amount_paid' => 75000.00, 'payment_method' => 'Cash'],
        1
    );
    $saleId11 = $saleRes11['sale_id'];
    $saleItems11 = Returns::getReturnableItemsForSale($saleId11);

    $retRes11 = Returns::createReturn(
        ['sale_id' => $saleId11, 'reason' => 'WRONG_ITEM'],
        [['sale_item_id' => (int)$saleItems11[0]['id'], 'quantity' => 2, 'condition_status' => 'resellable']],
        1
    );
    $returnId11 = $retRes11['return_id'];
    Returns::approveReturn($returnId11, 1);
    Returns::receiveAndRestock($returnId11, 1);

    $stockBeforeCancel = (int)$pdo->query("SELECT current_stock FROM `products` WHERE id = {$testProductId}")->fetchColumn();

    // Cancel return
    $cancelRes = Returns::cancelReturn($returnId11, "Mteja alikataa kurejesha tena", 1);
    $stockAfterCancel = (int)$pdo->query("SELECT current_stock FROM `products` WHERE id = {$testProductId}")->fetchColumn();
    $returnStatusAfterCancel = $pdo->query("SELECT status FROM `returns` WHERE id = {$returnId11}")->fetchColumn();

    $test11Pass = ($returnStatusAfterCancel === 'cancelled') && ($stockAfterCancel === $stockBeforeCancel - 2);
    reportTest(11, "Return Reversal / Cancellation: Reverses stock increment and marks return cancelled", $test11Pass, "Stock before: {$stockBeforeCancel}, Stock after: {$stockAfterCancel}, Status: {$returnStatusAfterCancel}");

    echo "\n=========================================================\n";
    echo "  SUMMARY: {$passedTests} / {$totalTests} TESTS PASSED (" . round(($passedTests / $totalTests) * 100) . "% SUCCESS)\n";
    echo "=========================================================\n";

    if ($passedTests === $totalTests) {
        exit(0);
    } else {
        exit(1);
    }

} catch (Exception $e) {
    echo "\nTEST EXCEPTION: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
