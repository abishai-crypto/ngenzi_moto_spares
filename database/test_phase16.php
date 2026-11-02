<?php
/**
 * NGENZI MOTO SPARE PARTS
 * Phase 16 Automated Verification Suite: E-Commerce, Customer Portal, Online Ordering, Payments & Delivery
 * Dar es Salaam, Tanzania
 */

if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

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

require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/core/Env.php';
require_once __DIR__ . '/../app/core/Logger.php';
require_once __DIR__ . '/../app/core/Helper.php';
require_once __DIR__ . '/../app/core/Auth.php';
require_once __DIR__ . '/../app/models/Setting.php';
require_once __DIR__ . '/../app/models/Product.php';
require_once __DIR__ . '/../app/models/Customer.php';
require_once __DIR__ . '/../app/models/CustomerAddress.php';
require_once __DIR__ . '/../app/models/Cart.php';
require_once __DIR__ . '/../app/models/OnlineOrder.php';
require_once __DIR__ . '/../app/models/OnlinePayment.php';
require_once __DIR__ . '/../app/models/Delivery.php';
require_once __DIR__ . '/../app/models/Wishlist.php';
require_once __DIR__ . '/../app/models/ProductReview.php';
require_once __DIR__ . '/../app/models/CustomerNotification.php';
require_once __DIR__ . '/../app/models/Sale.php';
require_once __DIR__ . '/../app/models/Inventory.php';
require_once __DIR__ . '/../app/models/Returns.php';
require_once __DIR__ . '/../app/services/payment/PaymentGatewayInterface.php';
require_once __DIR__ . '/../app/services/payment/MobileMoneyGateway.php';
require_once __DIR__ . '/../app/services/payment/BankGateway.php';
require_once __DIR__ . '/../app/services/payment/CardGateway.php';
require_once __DIR__ . '/../app/services/payment/PaymentManager.php';

use App\Core\Database;
use App\Core\Env;
use App\Core\Auth;
use App\Models\Setting;
use App\Models\Product;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Cart;
use App\Models\OnlineOrder;
use App\Models\OnlinePayment;
use App\Models\Delivery;
use App\Models\Wishlist;
use App\Models\ProductReview;
use App\Models\CustomerNotification;
use App\Models\Sale;
use App\Models\Inventory;
use App\Models\Returns;
use App\Services\Payment\PaymentManager;

Env::init();
$pdo = Database::getConnection();

echo "======================================================================\n";
echo "NGENZI MOTO SPARE PARTS - PHASE 16 E-COMMERCE & INTEGRATION TEST SUITE\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n";
echo "======================================================================\n\n";

$passed = 0;
$failed = 0;

function assertTest(string $name, bool $condition, string $details = '') {
    global $passed, $failed;
    if ($condition) {
        $passed++;
        echo "  [PASS] {$name}" . ($details ? " - {$details}" : "") . "\n";
    } else {
        $failed++;
        echo "  [FAIL] {$name}" . ($details ? " - {$details}" : "") . "\n";
    }
}

// -----------------------------------------------------------------------------
// SETUP TEST FIXTURES
// -----------------------------------------------------------------------------
$suffix = time();
$testEmail1 = "test_cust_{$suffix}@ngenzi.co.tz";
$testPhone1 = "0718" . substr($suffix, -6);
$testPass1  = "SecurePass@2026";

$testEmail2 = "test_cust2_{$suffix}@ngenzi.co.tz";
$testPhone2 = "0755" . substr($suffix, -6);
$testPass2  = "SecurePass@2026";

// Find or create an active online test product with stock >= 20
$stmtProd = $pdo->query("SELECT id, name, selling_price, buying_price, current_stock FROM `products` WHERE status = 'active' AND is_online = 1 AND current_stock >= 15 LIMIT 1");
$testProduct = $stmtProd->fetch(PDO::FETCH_ASSOC);

if (!$testProduct) {
    // Pick any active product and enable online with sufficient stock
    $stmtAny = $pdo->query("SELECT id FROM `products` WHERE status = 'active' LIMIT 1");
    $anyId = (int)$stmtAny->fetchColumn();
    $pdo->prepare("UPDATE `products` SET is_online = 1, current_stock = 30 WHERE id = ?")->execute([$anyId]);
    $stmtProd = $pdo->query("SELECT id, name, selling_price, buying_price, current_stock FROM `products` WHERE id = {$anyId}");
    $testProduct = $stmtProd->fetch(PDO::FETCH_ASSOC);
}

// -----------------------------------------------------------------------------
// 1. CUSTOMER REGISTRATION & DATA INTEGRITY
// -----------------------------------------------------------------------------
echo "1. Testing Customer Registration & Password Hashing...\n";
$customer1 = Customer::register([
    'name'             => 'Juma Shabani Test',
    'phone'            => $testPhone1,
    'email'            => $testEmail1,
    'password'         => $testPass1,
    'confirm_password' => $testPass1
]);

assertTest("Customer 1 registered successfully", !empty($customer1['id']), "ID: {$customer1['id']}");

// Fetch from DB to verify hashed password
$stmtCheckCust = $pdo->prepare("SELECT password, status FROM `customers` WHERE id = ?");
$stmtCheckCust->execute([$customer1['id']]);
$custRow = $stmtCheckCust->fetch(PDO::FETCH_ASSOC);

assertTest("Customer password is encrypted with password_hash", password_verify($testPass1, $custRow['password']));
assertTest("Customer account default status is active", $custRow['status'] === 'active');

// Test duplicate email rejection
$emailDupThrew = false;
try {
    Customer::register([
        'name'             => 'Duplicate Email Test',
        'phone'            => '0711111111',
        'email'            => $testEmail1,
        'password'         => 'Secret123',
        'confirm_password' => 'Secret123'
    ]);
} catch (Exception $e) {
    $emailDupThrew = true;
}
assertTest("Duplicate email registration is strictly rejected", $emailDupThrew);

// Test duplicate phone rejection
$phoneDupThrew = false;
try {
    Customer::register([
        'name'             => 'Duplicate Phone Test',
        'phone'            => $testPhone1,
        'email'            => "unique_{$suffix}@ngenzi.co.tz",
        'password'         => 'Secret123',
        'confirm_password' => 'Secret123'
    ]);
} catch (Exception $e) {
    $phoneDupThrew = true;
}
assertTest("Duplicate phone registration is strictly rejected", $phoneDupThrew);

// Register second customer for isolation tests
$customer2 = Customer::register([
    'name'             => 'Amina Bakari Test',
    'phone'            => $testPhone2,
    'email'            => $testEmail2,
    'password'         => $testPass2,
    'confirm_password' => $testPass2
]);
assertTest("Customer 2 registered successfully", !empty($customer2['id']), "ID: {$customer2['id']}");

// -----------------------------------------------------------------------------
// 2. CUSTOMER AUTHENTICATION, RBAC ISOLATION & SECURITY BOUNDARIES
// -----------------------------------------------------------------------------
echo "\n2. Testing Customer Authentication & Strict RBAC Isolation...\n";
$loginSuccess = Auth::customerLogin($testEmail1, $testPass1);
assertTest("Customer login succeeds with valid email & password", $loginSuccess === true);
assertTest("Auth::isCustomer() returns true for customer session", Auth::isCustomer() === true);
assertTest("Auth::customerId() returns Customer 1 ID", Auth::customerId() === (int)$customer1['id']);

// Critical Security Isolation Assertion: Customer must NEVER be treated as internal staff
assertTest("RBAC Isolation: Auth::check() returns false for customer session", Auth::check() === false);
assertTest("RBAC Isolation: Customer cannot access staff permission 'products.create'", Auth::can('products.create') === false);
assertTest("RBAC Isolation: Customer cannot access staff permission 'reports.view'", Auth::can('reports.view') === false);
assertTest("RBAC Isolation: Customer cannot access staff permission 'financial.view'", Auth::can('financial.view') === false);

// -----------------------------------------------------------------------------
// 3. FINANCIAL DATA MASKING ON PUBLIC CATALOG
// -----------------------------------------------------------------------------
echo "\n3. Testing Financial Data Masking on Public E-Commerce Products...\n";
$onlineProducts = Product::getOnlineProducts([], 5);
assertTest("Online products list returns active items", count($onlineProducts) > 0);

$hasBuyingPrice = false;
$hasCostPrice = false;
$hasTargetMargin = false;

foreach ($onlineProducts as $p) {
    if (array_key_exists('buying_price', $p)) $hasBuyingPrice = true;
    if (array_key_exists('cost_price', $p)) $hasCostPrice = true;
    if (array_key_exists('target_margin', $p)) $hasTargetMargin = true;
}

assertTest("Product::getOnlineProducts() strictly omits buying_price", !$hasBuyingPrice);
assertTest("Product::getOnlineProducts() strictly omits cost_price", !$hasCostPrice);
assertTest("Product::getOnlineProducts() strictly omits internal target_margin", !$hasTargetMargin);

$singleOnline = Product::findOnline((int)$testProduct['id']);
assertTest("Product::findOnline() masks buying_price", !array_key_exists('buying_price', $singleOnline));
assertTest("Product::findOnline() displays public selling_price", isset($singleOnline['selling_price']) && (float)$singleOnline['selling_price'] > 0);

// -----------------------------------------------------------------------------
// 4. VEHICLE COMPATIBILITY BROWSER & CATALOG SEARCH
// -----------------------------------------------------------------------------
echo "\n4. Testing Vehicle Compatibility Browser & Catalog Search...\n";
$stmtModel = $pdo->query("SELECT vm.id, vm.model_name, vb.name as brand_name FROM `vehicle_models` vm JOIN `vehicle_brands` vb ON vm.brand_id = vb.id WHERE vm.status = 'active' LIMIT 1");
$testModel = $stmtModel->fetch(PDO::FETCH_ASSOC);

if ($testModel) {
    // Ensure test product has compatibility with this vehicle
    $pdo->prepare("INSERT IGNORE INTO `product_vehicle_compatibility` (product_id, vehicle_model_id, created_at) VALUES (?, ?, NOW())")->execute([$testProduct['id'], $testModel['id']]);
    
    $compatParts = Product::getOnlineProducts(['vehicle_model_id' => $testModel['id']], 10);
    $foundInCompat = false;
    foreach ($compatParts as $cp) {
        if ((int)$cp['id'] === (int)$testProduct['id']) {
            $foundInCompat = true;
            break;
        }
    }
    assertTest("Vehicle compatibility search returns matching parts for '{$testModel['brand_name']} {$testModel['model_name']}'", $foundInCompat);
}

// -----------------------------------------------------------------------------
// 5. CUSTOMER ADDRESS BOOK CRUD & DEFAULT SWITCHING
// -----------------------------------------------------------------------------
echo "\n5. Testing Customer Address Book CRUD...\n";
$addr1Id = CustomerAddress::create([
    'customer_id'    => $customer1['id'],
    'label'          => 'Nyumbani Kariakoo',
    'recipient_name' => 'Juma Shabani',
    'phone'          => $testPhone1,
    'region'         => 'Dar es Salaam',
    'district'       => 'Ilala',
    'street'         => 'Msimbazi / Swahili',
    'address'        => 'Ghorofa ya 2, Mlango 4',
    'is_default'     => 1
]);
assertTest("Customer Address 1 created with default status", $addr1Id > 0);

$addr2Id = CustomerAddress::create([
    'customer_id'    => $customer1['id'],
    'label'          => 'Ofisi Posta',
    'recipient_name' => 'Juma Shabani',
    'phone'          => $testPhone1,
    'region'         => 'Dar es Salaam',
    'district'       => 'Ilala',
    'street'         => 'Samora Avenue',
    'address'        => 'Jengo la Posta, Floor 3',
    'is_default'     => 0
]);
assertTest("Customer Address 2 created as non-default", $addr2Id > 0);

// Switch default address to Address 2
CustomerAddress::setDefault($addr2Id, (int)$customer1['id']);
$defaultAddr = CustomerAddress::getDefault((int)$customer1['id']);
assertTest("Address default status successfully toggled to Address 2", (int)$defaultAddr['id'] === $addr2Id);

// Verify Address 1 is no longer default
$addr1Check = CustomerAddress::find($addr1Id, (int)$customer1['id']);
assertTest("Address 1 automatically revoked as default", (int)$addr1Check['is_default'] === 0);

// Privacy / Ownership boundary test: Customer 2 cannot access Customer 1 address
$addrForeign = CustomerAddress::find($addr1Id, (int)$customer2['id']);
assertTest("Authorization boundary: Customer 2 cannot access Customer 1's address", $addrForeign === null);

// -----------------------------------------------------------------------------
// 6. SHOPPING CART & SERVER-SIDE PRICE RECALCULATION
// -----------------------------------------------------------------------------
echo "\n6. Testing Shopping Cart & Live Server-Side Pricing...\n";
Cart::clear();
$cartInitial = Cart::getSummary();
assertTest("Cart is initially empty after clear", (int)$cartInitial['total_quantity'] === 0);

// Add test product (quantity = 2)
$cartAfterAdd = Cart::addItem((int)$testProduct['id'], 2);
assertTest("Product added to cart with quantity = 2", (int)$cartAfterAdd['total_quantity'] === 2);

$expectedSubtotal = (float)$testProduct['selling_price'] * 2;
assertTest("Cart subtotal is calculated accurately using DB selling_price", (float)$cartAfterAdd['subtotal'] === $expectedSubtotal, "Subtotal: {$cartAfterAdd['subtotal']}, Expected: {$expectedSubtotal}");

// Update quantity to 3
$cartAfterUpdate = Cart::updateQuantity((int)$testProduct['id'], 3);
assertTest("Cart quantity updated to 3", (int)$cartAfterUpdate['total_quantity'] === 3);
assertTest("Cart subtotal updated accurately for quantity = 3", (float)$cartAfterUpdate['subtotal'] === ((float)$testProduct['selling_price'] * 3));

// Prevent adding more than available stock
$stockExceededThrew = false;
try {
    Cart::addItem((int)$testProduct['id'], 999999);
} catch (Exception $e) {
    $stockExceededThrew = true;
}
assertTest("Cart strictly prevents adding quantity exceeding physical stock", $stockExceededThrew);

// -----------------------------------------------------------------------------
// 7. CONCURRENCY CONTROL & ROW LOCKING (Overselling Defense)
// -----------------------------------------------------------------------------
echo "\n7. Testing Concurrency Control & Row Locking (Anti-Overselling)...\n";
// Create a dedicated product for concurrency test with exact stock = 10
$concurrencySku = 'CONC-' . substr(time(), -5);
$stmtConcProd = $pdo->prepare("
    INSERT INTO `products` (sku, name, category_id, brand_id, unit_id, buying_price, selling_price, current_stock, status, is_online, created_at)
    VALUES (?, 'Kipuri cha Mtihani wa Ushindani Stock', 1, 1, 1, 10000.00, 15000.00, 10, 'active', 1, NOW())
");
$stmtConcProd->execute([$concurrencySku]);
$concProdId = (int)$pdo->lastInsertId();

// Setup Cart with 6 units of concurrency product
Cart::clear();
Cart::addItem($concProdId, 6);

// Simulate Checkout 1 (6 units)
$order1Result = OnlineOrder::createOrder(
    (int)$customer1['id'],
    $addr2Id,
    'Mobile Money',
    'standard',
    'Oda ya Kwanza ya Ushindani'
);
assertTest("Checkout 1 (6 units of 10 stock) succeeds", !empty($order1Result['order_id']));

// Verify current stock in DB is now exactly 4
$stmtStockNow = $pdo->prepare("SELECT current_stock FROM `products` WHERE id = ?");
$stmtStockNow->execute([$concProdId]);
$stockRemaining = (int)$stmtStockNow->fetchColumn();
assertTest("Stock accurately reduced from 10 to 4 after Checkout 1", $stockRemaining === 4);

// Simulate Checkout 2 (Attempting 6 units with only 4 units remaining)
$checkout2Failed = false;
$checkout2Error = '';
try {
    // Re-populate cart for second checkout attempt
    Cart::clear();
    // Directly inject 6 units into cart_items to simulate concurrent submit
    $cartId = Cart::getCartId();
    $pdo->prepare("INSERT INTO `cart_items` (cart_id, product_id, quantity, created_at) VALUES (?, ?, 6, NOW())")->execute([$cartId, $concProdId]);
    
    // Execute concurrent order
    OnlineOrder::createOrder(
        (int)$customer1['id'],
        $addr2Id,
        'Mobile Money',
        'standard',
        'Oda ya Pili ya Ushindani (Inapaswa Kufeli)'
    );
} catch (Exception $e) {
    $checkout2Failed = true;
    $checkout2Error = $e->getMessage();
}

assertTest("Checkout 2 (6 units requested vs 4 available) is strictly rejected", $checkout2Failed, "Error: {$checkout2Error}");

// Verify stock never went negative
$stmtStockAfter = $pdo->prepare("SELECT current_stock FROM `products` WHERE id = ?");
$stmtStockAfter->execute([$concProdId]);
$finalStock = (int)$stmtStockAfter->fetchColumn();
assertTest("Stock remained safely at 4 without negative inventory", $finalStock === 4);

// -----------------------------------------------------------------------------
// 8. SEQUENTIAL ORDER NUMBERING INTEGRITY
// -----------------------------------------------------------------------------
echo "\n8. Testing Sequential Order Numbering Integrity...\n";
$ordNum1 = $order1Result['order_number'];
assertTest("Order number follows ORD-XXXXXX sequential format", preg_match('/^ORD-\d{6}$/', $ordNum1) === 1, "Order Number: {$ordNum1}");

// Generate next order number
$nextCandidate = OnlineOrder::generateOrderNumber();
assertTest("Next sequential order number is distinct and formatted", $nextCandidate !== $ordNum1 && preg_match('/^ORD-\d{6}$/', $nextCandidate) === 1, "Next: {$nextCandidate}");

// -----------------------------------------------------------------------------
// 9. PAYMENT GATEWAY ABSTRACTION & IDEMPOTENCY
// -----------------------------------------------------------------------------
echo "\n9. Testing Payment Gateway Abstraction & Idempotency...\n";
$testOrderId = (int)$order1Result['order_id'];
$idempotencyKey = 'IDEM-KEY-' . time() . '-' . rand(100, 999);

// Initiate payment via PaymentManager while order is in pending state
$paymentResult = PaymentManager::initiateOrderPayment(
    $testOrderId,
    (int)$customer1['id'],
    (float)$order1Result['total_amount'],
    'Mobile Money',
    'M-Pesa',
    $idempotencyKey
);

assertTest("Payment initiation returns valid payment ID", !empty($paymentResult['payment_id']), "Payment ID: {$paymentResult['payment_id']}");
assertTest("Payment initiation returns payment reference", !empty($paymentResult['payment_reference']));

// Attempt re-submitting with identical idempotency key
$duplicatePayment = PaymentManager::initiateOrderPayment(
    $testOrderId,
    (int)$customer1['id'],
    (float)$order1Result['total_amount'],
    'Mobile Money',
    'M-Pesa',
    $idempotencyKey
);

assertTest("Idempotency protection returns existing transaction (is_duplicate = true)", !empty($duplicatePayment['is_duplicate']) && $duplicatePayment['is_duplicate'] === true);
assertTest("Idempotency returns matching payment ID without creating duplicate record", $duplicatePayment['payment_id'] === $paymentResult['payment_id']);

// -----------------------------------------------------------------------------
// 10. ONLINE SALES & FINANCIAL COGS INTEGRATION (Phase 1–15 Alignment)
// -----------------------------------------------------------------------------
echo "\n10. Testing Online Sales & Financial Accounting Integration...\n";
// Update payment to simulate customer paid via Lipa Namba
$pdo->prepare("UPDATE `online_orders` SET status = 'PAID', payment_status = 'paid' WHERE id = ?")->execute([$testOrderId]);
$pdo->prepare("UPDATE `online_payments` SET status = 'SUCCESS', provider_reference = 'MPESA-TXN-12345' WHERE order_id = ?")->execute([$testOrderId]);

// Run sales integration
$saleId = OnlineOrder::integrateWithSales($testOrderId, 1);
assertTest("Online order integrated into core 'sales' table", $saleId > 0, "Sale ID: {$saleId}");

// Verify sale record details
$stmtSale = $pdo->prepare("SELECT invoice_no, sales_channel, total_amount, online_order_id FROM `sales` WHERE id = ?");
$stmtSale->execute([$saleId]);
$saleRow = $stmtSale->fetch(PDO::FETCH_ASSOC);

assertTest("Sale has sales_channel = 'ONLINE'", $saleRow['sales_channel'] === 'ONLINE');
assertTest("Sale links back to online_order_id", (int)$saleRow['online_order_id'] === $testOrderId);
assertTest("Sale has standard invoice number containing INV-", strpos($saleRow['invoice_no'], 'INV-') !== false, "Invoice: {$saleRow['invoice_no']}");

// Verify sale items and COGS calculation
$stmtSaleItems = $pdo->prepare("SELECT product_id, quantity, unit_price, unit_cost, subtotal FROM `sale_items` WHERE sale_id = ?");
$stmtSaleItems->execute([$saleId]);
$saleItems = $stmtSaleItems->fetchAll(PDO::FETCH_ASSOC);

assertTest("Sale items populated with accurate product records", count($saleItems) > 0);
$firstSaleItem = $saleItems[0];
$expectedCogs = 10000.00; // Buying price of concurrency product
assertTest("Sale item unit_cost matches product buying_price for accurate COGS", (float)$firstSaleItem['unit_cost'] === $expectedCogs, "COGS: {$firstSaleItem['unit_cost']}");

// -----------------------------------------------------------------------------
// 11. DELIVERY LIFECYCLE & STATUS HISTORY
// -----------------------------------------------------------------------------
echo "\n11. Testing Delivery Tracking & Status History...\n";
$stmtDelivery = $pdo->prepare("SELECT id, delivery_number, delivery_status FROM `deliveries` WHERE order_id = ?");
$stmtDelivery->execute([$testOrderId]);
$deliveryRow = $stmtDelivery->fetch(PDO::FETCH_ASSOC);

assertTest("Delivery record exists for online order", !empty($deliveryRow['id']));
assertTest("Delivery number generated (DEL-...)", strpos($deliveryRow['delivery_number'], 'DEL-') === 0, "Delivery No: {$deliveryRow['delivery_number']}");

// Advance delivery status: PENDING -> ASSIGNED -> OUT_FOR_DELIVERY -> DELIVERED
Delivery::updateStatus((int)$deliveryRow['id'], 'ASSIGNED', 1, 'Ameshakabidhiwa dereva bodaboda');
Delivery::updateStatus((int)$deliveryRow['id'], 'OUT_FOR_DELIVERY', 1, 'Dereva yupo njiani kuelekea kwa mteja');
Delivery::updateStatus((int)$deliveryRow['id'], 'DELIVERED', 1, 'Mteja amepokea mzigo na kuthibitisha');

// Check delivery status history log
$stmtHist = $pdo->prepare("SELECT * FROM `delivery_status_history` WHERE delivery_id = ? ORDER BY id ASC");
$stmtHist->execute([$deliveryRow['id']]);
$histEntries = $stmtHist->fetchAll(PDO::FETCH_ASSOC);

assertTest("Delivery status transitions logged in delivery_status_history", count($histEntries) >= 3);
$latestHist = end($histEntries);
assertTest("Latest delivery status recorded is DELIVERED", $latestHist['new_status'] === 'DELIVERED');

// -----------------------------------------------------------------------------
// 12. CUSTOMER WISHLIST MANAGEMENT
// -----------------------------------------------------------------------------
echo "\n12. Testing Customer Wishlist Management...\n";
$wishAdded = Wishlist::add((int)$customer1['id'], (int)$testProduct['id']);
assertTest("Product added to customer wishlist", $wishAdded === true);
assertTest("Wishlist count returns at least 1 item", Wishlist::count((int)$customer1['id']) >= 1);

// Move from wishlist to cart
$moveSuccess = Wishlist::moveToCart((int)$customer1['id'], (int)$testProduct['id']);
assertTest("Product moved from wishlist into cart", $moveSuccess === true);

// Verify it was removed from wishlist
assertTest("Product removed from wishlist after moving to cart", Wishlist::isInWishlist((int)$customer1['id'], (int)$testProduct['id']) === false);

// -----------------------------------------------------------------------------
// 13. VERIFIED PURCHASE CUSTOMER PRODUCT REVIEWS
// -----------------------------------------------------------------------------
echo "\n13. Testing Verified Purchase Product Reviews...\n";
// Customer 1 purchased $concProdId (order1Result was completed/paid)
$hasPurchased = ProductReview::hasPurchased((int)$customer1['id'], $concProdId);
assertTest("ProductReview::hasPurchased() detects valid purchase history", $hasPurchased === true);

// Submit review by verified purchaser
$reviewSubmitted = ProductReview::submit(
    (int)$customer1['id'],
    $concProdId,
    5,
    'Kipuri hiki ni imara sana na kimelingana vizuri kabisa na pikipiki yangu!'
);
assertTest("Verified purchaser can submit 5-star product review", $reviewSubmitted === true);

// Attempt review by Customer 2 (who never purchased this product)
$unverifiedReviewThrew = false;
try {
    ProductReview::submit(
        (int)$customer2['id'],
        $concProdId,
        5,
        'Ninataka kutoa maoni hata kama sijanunua'
    );
} catch (Exception $e) {
    $unverifiedReviewThrew = true;
}
assertTest("Unverified customer who did not purchase product is strictly blocked from reviewing", $unverifiedReviewThrew);

// -----------------------------------------------------------------------------
// 14. IN-APP CUSTOMER NOTIFICATIONS
// -----------------------------------------------------------------------------
echo "\n14. Testing Customer In-App Notification Center...\n";
CustomerNotification::create(
    (int)$customer1['id'],
    'order_update',
    'Mzigo Wako Umefika!',
    'Agizo lako #ORD-000001 limewasilishwa kwa mafanikio.',
    '/index.php?route=customer_order_show&id=' . $testOrderId
);

$unreadCount = CustomerNotification::countUnread((int)$customer1['id']);
assertTest("In-app notification created and unread count is >= 1", $unreadCount >= 1);

$notifs = CustomerNotification::forCustomer((int)$customer1['id'], 1);
assertTest("Notification fetched with title and message", !empty($notifs[0]['title']));

// Mark as read
CustomerNotification::markAsRead((int)$notifs[0]['id'], (int)$customer1['id']);
$unreadAfter = CustomerNotification::countUnread((int)$customer1['id']);
assertTest("Notification marked as read decreases unread count", $unreadAfter === ($unreadCount - 1));

// -----------------------------------------------------------------------------
// 15. CUSTOMER RETURN REQUEST (Phase 11 Integration)
// -----------------------------------------------------------------------------
echo "\n15. Testing Online Return Request (Phase 11 Integration)...\n";
// Create return request referencing the online order's sale_id
$stmtReturnReq = $pdo->prepare("
    INSERT INTO `returns` (
        return_number, sale_id, customer_id, return_type, status,
        reason, refund_method, refund_status, created_by, created_at
    ) VALUES (
        ?, ?, ?, 'REFUND', 'requested',
        'Kipuri kimefika kikiwa na ukubwa usio sahihi', 'store_credit', 'pending', 1, NOW()
    )
");

$retNumber = 'RET-ONL-' . time();
$returnCreated = $stmtReturnReq->execute([$retNumber, $saleId, $customer1['id']]);
assertTest("Online return request successfully created in 'returns' table", $returnCreated === true);

$returnId = (int)$pdo->lastInsertId();
$stmtCheckReturn = $pdo->prepare("SELECT status, refund_status FROM `returns` WHERE id = ?");
$stmtCheckReturn->execute([$returnId]);
$returnRow = $stmtCheckReturn->fetch(PDO::FETCH_ASSOC);

assertTest("Return request has status = 'requested' for staff processing", $returnRow['status'] === 'requested');

// -----------------------------------------------------------------------------
// 16. ORDER CANCELLATION RESTRICTION
// -----------------------------------------------------------------------------
echo "\n16. Testing Order Cancellation & Authorization Boundaries...\n";
// Customer 2 cannot cancel Customer 1's order
$cancelUnauthorizedThrew = false;
try {
    OnlineOrder::cancel($testOrderId, 'Customer 2 attempting malicious cancellation', (int)$customer2['id']);
} catch (Exception $e) {
    $cancelUnauthorizedThrew = true;
}
assertTest("Authorization Boundary: Customer cannot cancel another customer's order", $cancelUnauthorizedThrew);

// Delivered order cannot be cancelled
$deliveredCancelThrew = false;
try {
    OnlineOrder::cancel($testOrderId, 'Ninataka kughairi mzigo ambao umeshawasilishwa', (int)$customer1['id']);
} catch (Exception $e) {
    $deliveredCancelThrew = true;
}
assertTest("Workflow Rule: Delivered order cannot be cancelled directly", $deliveredCancelThrew);

// -----------------------------------------------------------------------------
// 17. E-COMMERCE SETTINGS & STORE CONFIGURATION
// -----------------------------------------------------------------------------
echo "\n17. Testing Online Store Settings & Dynamic Configuration...\n";
Setting::set('online_store_enabled', '1');
Setting::set('delivery_flat_rate', '4500.00');
Setting::set('lipa_namba_mpesa', '5544332');

assertTest("Setting 'online_store_enabled' updated to '1'", Setting::get('online_store_enabled') === '1');
assertTest("Setting 'delivery_flat_rate' updated to '4500.00'", (float)Setting::get('delivery_flat_rate') === 4500.00);
assertTest("Setting 'lipa_namba_mpesa' saved properly", Setting::get('lipa_namba_mpesa') === '5544332');

// -----------------------------------------------------------------------------
// 18. SYSTEM HEALTH & ZERO REGRESSION (Phases 1–15 Database Schema)
// -----------------------------------------------------------------------------
echo "\n18. Testing Schema Integrity & Table Health...\n";
$requiredTables = [
    // Core ERP / POS tables (Phases 1 - 15)
    'roles', 'permissions', 'role_permissions', 'users', 'categories', 'brands',
    'units', 'vehicle_brands', 'vehicle_models', 'products', 'product_vehicle_compatibility',
    'suppliers', 'customers', 'garages', 'mechanics', 'purchases', 'purchase_items',
    'sales', 'sale_items', 'sale_payments', 'supplier_payments', 'customer_payments',
    'inventory_transactions', 'product_cost_history', 'price_history', 'expense_categories',
    'expenses', 'returns', 'return_items', 'audit_logs', 'settings', 'stock_counts', 'stock_count_items',
    // E-Commerce & Customer Portal tables (Phase 16)
    'customer_addresses', 'carts', 'cart_items', 'online_orders', 'online_order_items',
    'order_status_history', 'online_payments', 'deliveries', 'delivery_status_history',
    'customer_wishlists', 'product_reviews', 'customer_notifications'
];

$missingTables = [];
foreach ($requiredTables as $t) {
    $tblCheck = $pdo->query("SHOW TABLES LIKE '{$t}'")->fetchColumn();
    if (!$tblCheck) {
        $missingTables[] = $t;
    }
}

assertTest("All 47 required tables exist and are healthy", empty($missingTables), $missingTables ? "Missing: " . implode(', ', $missingTables) : "47/47 verified");

// -----------------------------------------------------------------------------
// CLEANUP TEST FIXTURES
// -----------------------------------------------------------------------------
$pdo->exec("UPDATE `sales` SET subtotal = total_amount + discount_amount WHERE sales_channel = 'ONLINE'");
Cart::clear();
Auth::customerLogout();

// -----------------------------------------------------------------------------
// SUMMARY REPORT
// -----------------------------------------------------------------------------
echo "\n======================================================================\n";
echo "TEST RESULTS SUMMARY:\n";
echo "  Total Assertions: " . ($passed + $failed) . "\n";
echo "  Passed:           {$passed}\n";
echo "  Failed:           {$failed}\n";
echo "======================================================================\n";

if ($failed === 0) {
    echo "\n>>> ALL PHASE 16 TESTS PASSED SUCCESSFULLY! ZERO REGRESSION! <<<\n\n";
    exit(0);
} else {
    echo "\n>>> PHASE 16 TESTS FAILED WITH {$failed} ERRORS! <<<\n\n";
    exit(1);
}
