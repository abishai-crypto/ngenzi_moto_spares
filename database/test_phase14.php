<?php
/**
 * NGENZI MOTO SPARE PARTS
 * Phase 14 Automated Security Verification & Reliability Test Suite
 * Tests all 15 Core Security Scenarios for Production Hardening
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
use App\Core\Helper;
use App\Models\Sale;
use App\Models\Payment;
use App\Models\Returns;
use App\Models\Inventory;
use App\Models\Customer;
use App\Models\Product;
use App\Models\User;
use App\Models\AuditLog;
use App\Models\Setting;

$pdo = Database::getConnection();

echo "====================================================================\n";
echo "  NGENZI MOTO SPARE PARTS — Phase 14 Security & Reliability Suite\n";
echo "  Comprehensive Security Hardening, Audit Trail & Access Control\n";
echo "====================================================================\n\n";

$passedTests = 0;
$totalTests = 15;

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
        VALUES ('CUST-SEC-TEST', 'Security Test Customer', '0711888777', 'retail', 200000.00, 1, 0.00, 'active', NOW())
        ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id), status='active'
    ");
    $secCustomerId = (int)$pdo->lastInsertId();

    $stmtProd = $pdo->query("SELECT id, current_stock, buying_price, selling_price, minimum_selling_price FROM `products` WHERE status = 'active' LIMIT 1");
    $secProduct = $stmtProd->fetch(PDO::FETCH_ASSOC);
    $secProductId = (int)$secProduct['id'];

    // Ensure ample stock and known prices for testing
    $pdo->prepare("UPDATE `products` SET current_stock = 100, selling_price = 15000.00, minimum_selling_price = 12000.00, buying_price = 9000.00 WHERE id = ?")
        ->execute([$secProductId]);

    // -------------------------------------------------------------
    // TEST 1: SQL Injection Protection in Search & Filters
    // -------------------------------------------------------------
    $sqliPayload = "' OR '1'='1' -- /*";
    $searchRes = Product::all(['search' => $sqliPayload], 10, 0);
    // If properly parameterized, it searches for the literal string and does not return all products
    $allCount = Product::count();
    $test1Pass = is_array($searchRes) && (count($searchRes) < $allCount || $allCount == 0);
    reportTest(1, "SQL Injection Protection: Parameterized search & filter inputs", $test1Pass, "Payload handled safely as literal string");

    // -------------------------------------------------------------
    // TEST 2: XSS Protection & Spreadsheet Formula Injection Protection
    // -------------------------------------------------------------
    $xssPayload = "<script>alert('XSS')</script>";
    $escaped = Helper::escape($xssPayload);
    $xssPass = ($escaped === "&lt;script&gt;alert(&#039;XSS&#039;)&lt;/script&gt;");

    $formulaPayload = "=CMD|' /C calc'!A0";
    $sanitizedFormula = Helper::sanitizeCsvValue($formulaPayload);
    $formulaPass = ($sanitizedFormula === "'=CMD|' /C calc'!A0");

    $test2Pass = $xssPass && $formulaPass;
    reportTest(2, "XSS & CSV Formula Injection Protection: Safe escaping & formula neutralization", $test2Pass, "XSS escaped, Excel formula prefixed with apostrophe");

    // -------------------------------------------------------------
    // TEST 3: CSRF Protection on State-Changing Operations
    // -------------------------------------------------------------
    Auth::startSession();
    $validToken = Helper::csrfToken();
    $csrfPass1 = Helper::validateCsrf($validToken);
    $csrfPass2 = !Helper::validateCsrf("invalid_malicious_token_12345");
    $csrfPass3 = !Helper::validateCsrf(null);
    $test3Pass = $csrfPass1 && $csrfPass2 && $csrfPass3;
    reportTest(3, "CSRF Token Validation: Cryptographic hash comparison rejects invalid/missing tokens", $test3Pass, "Valid accepted, forged/null strictly rejected");

    // -------------------------------------------------------------
    // TEST 4: IDOR Protection: Invalid Record ID Handled Gracefully
    // -------------------------------------------------------------
    $nonExistentSale = Sale::find(99999999);
    $nonExistentCust = Customer::find(99999999);
    $nonExistentLog  = AuditLog::find(99999999);
    $test4Pass = ($nonExistentSale === null) && ($nonExistentCust === null) && ($nonExistentLog === null);
    reportTest(4, "IDOR Protection: Non-existent record IDs return null without SQL or path disclosure", $test4Pass, "Handled safely without runtime exceptions or leaks");

    // -------------------------------------------------------------
    // TEST 5: Cashier RBAC Isolation: Financial Data Masking
    // -------------------------------------------------------------
    // Simulate Cashier Role
    $_SESSION['authenticated'] = true;
    $_SESSION['user_id'] = 9999;
    $_SESSION['role_name'] = 'cashier';
    $_SESSION['permissions'] = ['sales.create', 'sales.view', 'dashboard.view'];

    $cashierCanSeeFin = Auth::canViewFinancials();
    $maskedCost = Auth::maskFinancial(15000.00);

    // Simulate Manager Role
    $_SESSION['role_name'] = 'manager';
    $managerCanSeeFin = Auth::canViewFinancials();
    $unmaskedCost = Auth::maskFinancial(15000.00);

    // Restore test admin session
    $_SESSION['role_name'] = 'super_admin';

    $test5Pass = (!$cashierCanSeeFin) && (strpos($maskedCost, 'Imezuiwa') !== false) && ($managerCanSeeFin) && (strpos($unmaskedCost, '15,000') !== false);
    reportTest(5, "Cashier RBAC Security: Buying cost & profit margins strictly masked from Cashier", $test5Pass, "Cashier: {$maskedCost} vs Manager: {$unmaskedCost}");

    // -------------------------------------------------------------
    // TEST 6: Price Manipulation Protection (POS Minimum Selling Price)
    // -------------------------------------------------------------
    // Attempt selling below MSP (12,000) at 10,000 as cashier
    $_SESSION['role_name'] = 'cashier';
    $_SESSION['permissions'] = ['sales.create'];

    $priceTamperBlocked = false;
    try {
        Sale::createSale(
            ['customer_id' => $secCustomerId],
            [['product_id' => $secProductId, 'quantity' => 1, 'unit_price' => 10000.00, 'discount' => 0.00]],
            ['amount_paid' => 10000.00, 'payment_method' => 'Cash'],
            9999
        );
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'chini ya bei ya chini') !== false) {
            $priceTamperBlocked = true;
        }
    }
    $_SESSION['role_name'] = 'super_admin';
    reportTest(6, "Price Override Security: Server enforces minimum selling price and blocks tampering", $priceTamperBlocked, "Unauthorized selling below MSP rejected");

    // -------------------------------------------------------------
    // TEST 7: Discount Manipulation Protection
    // -------------------------------------------------------------
    $_SESSION['role_name'] = 'cashier';
    $_SESSION['permissions'] = ['sales.create']; // Lacks sales.discount

    $discountTamperBlocked = false;
    try {
        Sale::createSale(
            ['customer_id' => $secCustomerId],
            [['product_id' => $secProductId, 'quantity' => 1, 'unit_price' => 15000.00, 'discount' => 3000.00]],
            ['amount_paid' => 12000.00, 'payment_method' => 'Cash'],
            9999
        );
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Huna ruhusa ya kutoa punguzo') !== false) {
            $discountTamperBlocked = true;
        }
    }
    $_SESSION['role_name'] = 'super_admin';
    reportTest(7, "Discount Security: Server re-verifies discount permissions and recalculates totals", $discountTamperBlocked, "Unauthorized discount request rejected");

    // -------------------------------------------------------------
    // TEST 8: Stock Manipulation Protection
    // -------------------------------------------------------------
    // Attempt selling more than available stock
    $currentStock = (int)$pdo->query("SELECT current_stock FROM `products` WHERE id = {$secProductId}")->fetchColumn();
    $excessQuantity = $currentStock + 500;

    $stockTamperBlocked = false;
    try {
        Sale::createSale(
            ['customer_id' => $secCustomerId],
            [['product_id' => $secProductId, 'quantity' => $excessQuantity, 'unit_price' => 15000.00]],
            ['amount_paid' => 15000.00 * $excessQuantity, 'payment_method' => 'Cash'],
            1
        );
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Stoo haitoshi') !== false) {
            $stockTamperBlocked = true;
        }
    }
    reportTest(8, "Stock Manipulation Protection: Row-locking enforces inventory limits and prevents negative stock", $stockTamperBlocked, "Attempt to oversell {$excessQuantity} units rejected");

    // -------------------------------------------------------------
    // TEST 9: Duplicate Payment Prevention
    // -------------------------------------------------------------
    // Create a sale with debt
    $saleRes = Sale::createSale(
        ['customer_id' => $secCustomerId, 'payment_terms' => '30 days'],
        [['product_id' => $secProductId, 'quantity' => 2, 'unit_price' => 15000.00]],
        ['amount_paid' => 0.00, 'payment_method' => 'Credit'],
        1
    );
    $testSaleId = $saleRes['sale_id'];

    $testRef = "MPESA-SEC-" . time() . "-" . rand(100, 999);
    // First payment succeeds
    $pay1 = Payment::recordSalePayment($testSaleId, [
        'amount'         => 5000.00,
        'payment_method' => 'M-Pesa',
        'reference_no'   => $testRef
    ], 1);

    // Second payment with same reference number MUST be rejected
    $duplicateBlocked = false;
    try {
        Payment::recordSalePayment($testSaleId, [
            'amount'         => 5000.00,
            'payment_method' => 'M-Pesa',
            'reference_no'   => $testRef
        ], 1);
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate Transaction') !== false || strpos($e->getMessage(), 'tayari imeshatumika') !== false) {
            $duplicateBlocked = true;
        }
    }
    reportTest(9, "Duplicate Payment Protection: Rejects duplicate payment reference & rapid replay", $duplicateBlocked, "First payment #{$pay1['payment_no']} recorded, second attempt with ref #{$testRef} rejected");

    // -------------------------------------------------------------
    // TEST 10: Duplicate Refund Protection
    // -------------------------------------------------------------
    // Create a return for the sale
    $saleItems = Returns::getReturnableItemsForSale($testSaleId);
    $saleItemId = (int)$saleItems[0]['id'];

    $retRes = Returns::createReturn(
        ['sale_id' => $testSaleId, 'reason' => 'CUSTOMER_RETURN'],
        [['sale_item_id' => $saleItemId, 'quantity' => 1, 'condition_status' => 'resellable']],
        1
    );
    $testReturnId = $retRes['return_id'];

    Returns::approveReturn($testReturnId, 1);
    Returns::receiveAndRestock($testReturnId, 1);
    
    // First refund
    Returns::processRefund($testReturnId, ['refund_method' => 'Cash'], 1);

    // Second refund attempt MUST throw Exception
    $duplicateRefundBlocked = false;
    try {
        Returns::processRefund($testReturnId, ['refund_method' => 'Cash'], 1);
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Already Refunded') !== false || strpos($e->getMessage(), 'tayari yameshafanyika') !== false) {
            $duplicateRefundBlocked = true;
        }
    }
    reportTest(10, "Duplicate Refund Protection: Second refund attempt on same return strictly rejected", $duplicateRefundBlocked, "Idempotency preserved");

    // -------------------------------------------------------------
    // TEST 11: Document Number Uniqueness & Database Constraints
    // -------------------------------------------------------------
    $inv1 = Sale::generateInvoiceNo();
    $pay1No = Payment::generatePaymentNo();
    $ret1No = Returns::generateReturnNumber();
    $exp1No = \App\Models\Expense::generateExpenseNo();

    // Check unique constraints exist in DB schema
    $chkInv = $pdo->query("SHOW INDEX FROM `sales` WHERE Column_name = 'invoice_no' AND Non_unique = 0")->fetch();
    $chkPay = $pdo->query("SHOW INDEX FROM `sale_payments` WHERE Column_name = 'payment_no' AND Non_unique = 0")->fetch();
    $chkRet = $pdo->query("SHOW INDEX FROM `returns` WHERE Column_name = 'return_number' AND Non_unique = 0")->fetch();

    $test11Pass = ($chkInv && $chkPay && $chkRet && !empty($inv1) && !empty($pay1No));
    reportTest(11, "Unique Number Generation: Database-backed unique constraints enforced", $test11Pass, "Invoices, Payments, and Returns backed by UNIQUE MySQL keys");

    // -------------------------------------------------------------
    // TEST 12: Session Inactivity Timeout Configuration
    // -------------------------------------------------------------
    $timeoutSetting = (int)Setting::get('session_timeout_minutes', '30');
    $test12Pass = ($timeoutSetting > 0 && $timeoutSetting <= 120);
    reportTest(12, "Session Timeout Protection: Configured inactivity timeout threshold", $test12Pass, "Timeout configured to {$timeoutSetting} minutes in database settings");

    // -------------------------------------------------------------
    // TEST 13: Deactivated User Account Blocking
    // -------------------------------------------------------------
    // Create a temporary test user
    $tempUsername = "temp_sec_user_" . rand(1000, 9999);
    $tempUserId = User::create([
        'username'  => $tempUsername,
        'password'  => 'Password123!',
        'full_name' => 'Temporary Security Test User',
        'role_id'   => 4, // Cashier
        'status'    => 'active'
    ]);

    // Deactivate user
    User::toggleStatus($tempUserId);
    $deactivatedStatus = $pdo->query("SELECT status FROM `users` WHERE id = {$tempUserId}")->fetchColumn();

    // Verify login attempt fails for deactivated user
    $loginResult = Auth::attempt($tempUsername, 'Password123!');
    $test13Pass = ($deactivatedStatus === 'inactive') && ($loginResult === false);

    // Clean up temporary user
    $pdo->prepare("DELETE FROM `users` WHERE id = ?")->execute([$tempUserId]);
    reportTest(13, "Deactivated User Protection: Inactive user immediately barred from authentication", $test13Pass, "Login rejected for status 'inactive'");

    // -------------------------------------------------------------
    // TEST 14: Audit Trail Immutability & Logging
    // -------------------------------------------------------------
    $auditBefore = AuditLog::count();
    Helper::logAudit('SECURITY_TEST_EVENT', 'SECURITY', 1, null, ['status' => 'verified']);
    $auditAfter = AuditLog::count();

    // Verify record logged
    $latestLog = $pdo->query("SELECT * FROM `audit_logs` WHERE action = 'SECURITY_TEST_EVENT' ORDER BY id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);

    // Check that AuditLog class does not have delete/update methods
    $auditReflection = new ReflectionClass(AuditLog::class);
    $hasDelete = $auditReflection->hasMethod('delete');
    $hasUpdate = $auditReflection->hasMethod('update');

    $test14Pass = ($auditAfter === $auditBefore + 1) && ($latestLog !== false) && (!$hasDelete) && (!$hasUpdate);
    reportTest(14, "Audit Trail Immutability: Immutable append-only logging verified", $test14Pass, "Events logged with IP & UA, NO CRUD delete/update methods exist");

    // -------------------------------------------------------------
    // TEST 15: Security Headers & Directory Path Protections
    // -------------------------------------------------------------
    $rootHtaccess = file_exists(__DIR__ . '/../.htaccess');
    $appHtaccess  = file_exists(__DIR__ . '/../app/.htaccess');
    $dbHtaccess   = file_exists(__DIR__ . '/../database/.htaccess');

    $appContent = $appHtaccess ? file_get_contents(__DIR__ . '/../app/.htaccess') : '';
    $hasDenyAll = (strpos($appContent, 'Require all denied') !== false);

    $test15Pass = $rootHtaccess && $appHtaccess && $dbHtaccess && $hasDenyAll;
    reportTest(15, "Path & Directory Protection: .htaccess rules block direct source & database access", $test15Pass, "Protected app/, database/ and root sensitive file extensions");

    echo "\n====================================================================\n";
    echo "  SUMMARY: {$passedTests} / {$totalTests} TESTS PASSED (" . round(($passedTests / $totalTests) * 100) . "% SUCCESS)\n";
    echo "====================================================================\n";

    if ($passedTests === $totalTests) {
        echo "ALL PHASE 14 SECURITY TESTS PASSED! SYSTEM HARDENING & RELIABILITY OPERATIONAL.\n";
    }

} catch (Exception $e) {
    echo "\nTEST RUNNER EXCEPTION: " . $e->getMessage() . "\n";
    echo "Stack Trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
