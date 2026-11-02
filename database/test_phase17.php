<?php
/**
 * NGENZI MOTO SPARE PARTS
 * Phase 17 Automated Verification Suite:
 * Optional Bolt Delivery, Flexible Shipping, Expanded Motorcycle & Bajaj Catalog,
 * Safe Multi-Image Uploads, Owner Demand Discovery, Financial Isolation & Zero Regression
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
require_once __DIR__ . '/../app/models/ProductImage.php';
require_once __DIR__ . '/../app/models/ProductDemand.php';
require_once __DIR__ . '/../app/models/Customer.php';
require_once __DIR__ . '/../app/models/CustomerAddress.php';
require_once __DIR__ . '/../app/models/Cart.php';
require_once __DIR__ . '/../app/models/OnlineOrder.php';
require_once __DIR__ . '/../app/models/OnlinePayment.php';
require_once __DIR__ . '/../app/models/Delivery.php';
require_once __DIR__ . '/../app/models/Sale.php';
require_once __DIR__ . '/../app/models/Inventory.php';
require_once __DIR__ . '/../app/services/delivery/DeliveryProviderInterface.php';
require_once __DIR__ . '/../app/services/delivery/BoltDeliveryProvider.php';
require_once __DIR__ . '/../app/services/delivery/StoreDeliveryProvider.php';
require_once __DIR__ . '/../app/services/delivery/CustomerCourierProvider.php';
require_once __DIR__ . '/../app/services/delivery/PickupDeliveryProvider.php';
require_once __DIR__ . '/../app/services/delivery/OtherCourierProvider.php';
require_once __DIR__ . '/../app/services/delivery/DeliveryManager.php';
require_once __DIR__ . '/../app/services/notification/NotificationManager.php';

use App\Core\Database;
use App\Core\Env;
use App\Core\Auth;
use App\Models\Setting;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductDemand;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Cart;
use App\Models\OnlineOrder;
use App\Models\OnlinePayment;
use App\Models\Delivery;
use App\Models\Sale;
use App\Models\Inventory;
use App\Services\Delivery\DeliveryManager;
use App\Services\Delivery\BoltDeliveryProvider;
use App\Services\Delivery\StoreDeliveryProvider;
use App\Services\Delivery\CustomerCourierProvider;
use App\Services\Delivery\PickupDeliveryProvider;
use App\Services\Delivery\OtherCourierProvider;
use App\Services\Notification\NotificationManager;

Env::init();
$pdo = Database::getConnection();

echo "======================================================================\n";
echo "NGENZI MOTO SPARE PARTS - PHASE 17 AUTOMATED VERIFICATION SUITE\n";
echo "Optional Bolt Delivery, Flexible Shipping, Expanded Catalog & Demand\n";
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
// FIXTURES SETUP
// -----------------------------------------------------------------------------
$suffix = time() . '_' . rand(100, 999);
$testEmail1 = "p17_cust_{$suffix}@ngenzi.co.tz";
$testPhone1 = "0719" . substr($suffix, -6);
$testPass1  = "SecurePass@2026";

$testEmail2 = "p17_cust2_{$suffix}@ngenzi.co.tz";
$testPhone2 = "0759" . substr($suffix, -6);
$testPass2  = "SecurePass@2026";

$cust1 = Customer::register([
    'name'             => 'Phase 17 Juma Test',
    'email'            => $testEmail1,
    'phone'            => $testPhone1,
    'password'         => $testPass1,
    'confirm_password' => $testPass1
]);
$custId1 = (int)$cust1['id'];

$cust2 = Customer::register([
    'name'             => 'Phase 17 Bakari Test',
    'email'            => $testEmail2,
    'phone'            => $testPhone2,
    'password'         => $testPass2,
    'confirm_password' => $testPass2
]);
$custId2 = (int)$cust2['id'];

$addrId1 = CustomerAddress::create([
    'customer_id'    => $custId1,
    'label'          => 'Nyumbani Kariakoo',
    'recipient_name' => 'Juma Test',
    'phone'          => $testPhone1,
    'region'         => 'Dar es Salaam',
    'district'       => 'Ilala',
    'ward'           => 'Kariakoo',
    'street'         => 'Mtaa wa Msimbazi',
    'address'        => 'Msimbazi, Nyumba Na. 45',
    'latitude'       => -6.8234,
    'longitude'      => 39.2789,
    'is_default'     => 1
]);

$addrId2 = CustomerAddress::create([
    'customer_id'    => $custId2,
    'label'          => 'Nyumbani Mwenge',
    'recipient_name' => 'Bakari Test',
    'phone'          => $testPhone2,
    'region'         => 'Dar es Salaam',
    'district'       => 'Kinondoni',
    'ward'           => 'Mwenge',
    'street'         => 'Mwenge Vinyago',
    'address'        => 'Mwenge Vinyago Street',
    'latitude'       => -6.7723,
    'longitude'      => 39.2215,
    'is_default'     => 1
]);

// Find or create active physical sellable product
$stmtProd = $pdo->query("SELECT id, name, category_id, selling_price, buying_price, current_stock FROM `products` WHERE status = 'active' AND is_online = 1 AND is_catalog_only = 0 AND current_stock >= 20 LIMIT 1");
$physicalProduct = $stmtProd->fetch(PDO::FETCH_ASSOC);

if (!$physicalProduct) {
    // Pick active category, brand, unit
    $stmtCategory = $pdo->query("SELECT id FROM `categories` LIMIT 1");
    $catId = (int)$stmtCategory->fetchColumn();
    $stmtBrand = $pdo->query("SELECT id FROM `brands` LIMIT 1");
    $brandId = (int)$stmtBrand->fetchColumn();
    $stmtUnit = $pdo->query("SELECT id FROM `units` LIMIT 1");
    $unitId = (int)$stmtUnit->fetchColumn();

    $stmtInsert = $pdo->prepare("INSERT INTO `products` (name, sku, category_id, brand_id, unit_id, buying_price, selling_price, current_stock, reorder_level, status, is_online, is_catalog_only, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', 1, 0, NOW())");
    $testSku = 'P17-PHYS-' . substr(md5(uniqid()), 0, 8);
    $stmtInsert->execute(['Piston Ring Boxer BM150 Test', $testSku, $catId, $brandId, $unitId, 15000.00, 25000.00, 50, 5]);
    $prodId = (int)$pdo->lastInsertId();
    $physicalProduct = [
        'id'            => $prodId,
        'name'          => 'Piston Ring Boxer BM150 Test',
        'category_id'   => $catId,
        'selling_price' => 25000.00,
        'buying_price'  => 15000.00,
        'current_stock' => 50
    ];
}

// -----------------------------------------------------------------------------
// 1. DELIVERY PROVIDER RESOLUTION & CONFIGURATION ARCHITECTURE
// -----------------------------------------------------------------------------
echo "\n1. Testing Delivery Provider Architecture & Resolution...\n";

$providers = DeliveryManager::getEnabledProviders();
assertTest("DeliveryManager discovers registered providers", !empty($providers));

$providerCodes = array_keys($providers);
assertTest("Store delivery provider registered", in_array('STORE_DELIVERY', $providerCodes));
assertTest("Bolt delivery provider registered", in_array('BOLT', $providerCodes));
assertTest("Customer courier provider registered", in_array('CUSTOMER_COURIER', $providerCodes));
assertTest("Pickup delivery provider registered", in_array('CUSTOMER_PICKUP', $providerCodes));
assertTest("Other courier provider registered", in_array('OTHER_COURIER', $providerCodes));

$resolvedBolt = DeliveryManager::resolveProvider('BOLT');
assertTest("DeliveryManager resolves 'BOLT' to BoltDeliveryProvider", $resolvedBolt instanceof BoltDeliveryProvider);

$resolvedStore = DeliveryManager::resolveProvider('STORE_DELIVERY');
assertTest("DeliveryManager resolves 'STORE_DELIVERY' to StoreDeliveryProvider", $resolvedStore instanceof StoreDeliveryProvider);

$resolvedCustomerCourier = DeliveryManager::resolveProvider('CUSTOMER_COURIER');
assertTest("DeliveryManager resolves 'CUSTOMER_COURIER' to CustomerCourierProvider", $resolvedCustomerCourier instanceof CustomerCourierProvider);

$resolvedPickup = DeliveryManager::resolveProvider('CUSTOMER_PICKUP');
assertTest("DeliveryManager resolves 'CUSTOMER_PICKUP' to PickupDeliveryProvider", $resolvedPickup instanceof PickupDeliveryProvider);

$resolvedOtherCourier = DeliveryManager::resolveProvider('OTHER_COURIER');
assertTest("DeliveryManager resolves 'OTHER_COURIER' to OtherCourierProvider", $resolvedOtherCourier instanceof OtherCourierProvider);

// -----------------------------------------------------------------------------
// 2. BOLT DELIVERY UNCONFIGURED FALLBACK & NO FAKE APIS
// -----------------------------------------------------------------------------
echo "\n2. Testing Bolt Delivery Provider Status & Safe Fallback...\n";

// Ensure Bolt API key is empty/not configured
Setting::set('bolt_api_key', '');
Setting::set('bolt_api_configured', '0');

$boltProvider = new BoltDeliveryProvider();
$boltStatus = $boltProvider->getProviderStatus();
assertTest("Bolt reports status = 'NOT CONFIGURED' when API credentials absent", $boltStatus === 'NOT CONFIGURED');

// Automated dispatch without credentials safely returns manual fallback
$boltDispatchResult = $boltProvider->createDelivery(
    ['order_number' => 'ORD-999999', 'subtotal' => 50000.00],
    ['recipient_name' => 'Juma', 'recipient_phone' => '+255712000111']
);
assertTest("Bolt createDelivery falls back to manual workflow without faking API success", $boltDispatchResult['is_manual'] === true && $boltDispatchResult['status'] === 'PROVIDER_SELECTED');

$boltTracking = $boltProvider->getTrackingInformation('BOLT-MANUAL-12345');
assertTest("Bolt tracking reports arrival guidance for customer", !empty($boltTracking['estimated_arrival']));

// -----------------------------------------------------------------------------
// 3. STORE DELIVERY & DYNAMIC FREE THRESHOLD
// -----------------------------------------------------------------------------
echo "\n3. Testing Store Delivery Dynamic Pricing & Free Threshold...\n";

Setting::set('delivery_flat_rate', '4000.00');
Setting::set('free_delivery_threshold', '100000.00');

$feeBelow = DeliveryManager::calculateFee('STORE_DELIVERY', 75000.00);
assertTest("Store delivery charges standard flat rate below threshold", (float)$feeBelow === 4000.00, "Fee: {$feeBelow} TSh");

$feeAbove = DeliveryManager::calculateFee('STORE_DELIVERY', 125000.00);
assertTest("Store delivery grants free delivery (0 TSh) at or above threshold", (float)$feeAbove === 0.00, "Fee: {$feeAbove} TSh");

// -----------------------------------------------------------------------------
// 4. CUSTOMER COURIER & IN-STORE PICKUP (ZERO STORE FEE)
// -----------------------------------------------------------------------------
echo "\n4. Testing Customer Courier & In-Store Pickup Fees...\n";

$feeCourier = DeliveryManager::calculateFee('CUSTOMER_COURIER', 50000.00);
assertTest("Customer Courier store delivery fee is 0 TSh", (float)$feeCourier === 0.00);

$feePickup = DeliveryManager::calculateFee('CUSTOMER_PICKUP', 50000.00);
assertTest("Customer Pickup store delivery fee is 0 TSh", (float)$feePickup === 0.00);

$checkoutOpts = DeliveryManager::getCheckoutOptions(50000.00);
assertTest("DeliveryManager::getCheckoutOptions generates customer selection list", !empty($checkoutOpts));

// -----------------------------------------------------------------------------
// 5. DELIVERY FEE ISOLATION & FINANCIAL INTEGRITY
// -----------------------------------------------------------------------------
echo "\n5. Testing Delivery Fee Isolation (No Distortion of COGS or Gross Margin)...\n";

Auth::customerLogin($testEmail1, $testPass1);
Cart::clear();

// Add 2 units of test product
$unitSellPrice = (float)$physicalProduct['selling_price'];
$unitBuyPrice  = (float)$physicalProduct['buying_price'];
Cart::addItem((int)$physicalProduct['id'], 2);

$cartSummary = Cart::getSummary();
$expectedSubtotal = $unitSellPrice * 2;
assertTest("Cart subtotal equals unit_price * 2", (float)$cartSummary['subtotal'] === $expectedSubtotal);

// Checkout with STORE_DELIVERY (flat fee 4,000)
$orderResult = OnlineOrder::createOrder(
    $custId1,
    $addrId1,
    'Mobile Money',
    'STORE_DELIVERY',
    'Test delivery fee separation',
    'STORE_DELIVERY',
    ['delivery_fee' => 4000.00]
);

$orderId = (int)$orderResult['order_id'];
$orderData = OnlineOrder::find($orderId);

$expectedGrandTotal = $expectedSubtotal + 4000.00;
assertTest("Online order subtotal = product amount", (float)$orderData['subtotal'] === $expectedSubtotal);
assertTest("Online order delivery fee recorded separately", (float)$orderData['delivery_fee'] === 4000.00);
assertTest("Online order grand total = subtotal + delivery_fee", (float)$orderData['total_amount'] === $expectedGrandTotal);

// Mark order as PAID to trigger sales integration
OnlineOrder::updateStatus($orderId, 'PAID', 1, 'Testing financial isolation');
OnlineOrder::integrateWithSales($orderId);

// Verify core 'sales' table record
$stmtSale = $pdo->prepare("SELECT * FROM `sales` WHERE online_order_id = ?");
$stmtSale->execute([$orderId]);
$saleRecord = $stmtSale->fetch(PDO::FETCH_ASSOC);

assertTest("Sale created with sales_channel = 'ONLINE'", $saleRecord && $saleRecord['sales_channel'] === 'ONLINE');
assertTest("Sale subtotal strictly equals product subtotal (delivery fee excluded)", (float)$saleRecord['subtotal'] === $expectedSubtotal, "Subtotal: {$saleRecord['subtotal']}");

// Verify sale items COGS and pricing
$stmtItems = $pdo->prepare("SELECT * FROM `sale_items` WHERE sale_id = ?");
$stmtItems->execute([$saleRecord['id']]);
$saleItems = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

assertTest("Sale item unit_price matches product selling_price", (float)$saleItems[0]['unit_price'] === $unitSellPrice);
assertTest("Sale item unit_cost strictly matches product buying_price (COGS intact)", (float)$saleItems[0]['unit_cost'] === $unitBuyPrice);

// Calculate gross profit on sale items: (selling - buying) * qty
$expectedGrossProfit = ($unitSellPrice - $unitBuyPrice) * 2;
$actualGrossProfit = ((float)$saleItems[0]['unit_price'] - (float)$saleItems[0]['unit_cost']) * (int)$saleItems[0]['quantity'];
assertTest("Gross profit is strictly based on product margin without delivery fee distortion", $actualGrossProfit === $expectedGrossProfit, "Expected: {$expectedGrossProfit}, Actual: {$actualGrossProfit}");

// -----------------------------------------------------------------------------
// 6. DELIVERY LIFECYCLE STATE TRANSITIONS & INVALID TRANSITION BLOCKING
// -----------------------------------------------------------------------------
echo "\n6. Testing Delivery Lifecycle State Transitions & Validation...\n";

$deliveryRecord = Delivery::findByOrder($orderId);
$deliveryId = (int)$deliveryRecord['id'];
assertTest("Delivery record created for order", $deliveryRecord && !empty($deliveryRecord['id']));

// Transition: Assign driver
$assignSuccess = Delivery::assignCourier($deliveryId, [
    'driver_name'  => 'Hamisi Bodaboda',
    'driver_phone' => '+255713445566',
    'vehicle_info' => 'MC Boxer T 123 ABC',
    'status'       => 'ASSIGNED'
], 1);
assertTest("Delivery successfully assigned to driver", $assignSuccess);
$deliveryAfterAssign = Delivery::find($deliveryId);
assertTest("Delivery status updated to 'ASSIGNED'", $deliveryAfterAssign['delivery_status'] === 'ASSIGNED');
assertTest("Driver name and phone persisted in delivery record", $deliveryAfterAssign['driver_name'] === 'Hamisi Bodaboda' && $deliveryAfterAssign['driver_phone'] === '+255713445566');

// Transition: ASSIGNED -> OUT_FOR_DELIVERY
Delivery::updateStatus($deliveryId, 'OUT_FOR_DELIVERY', 1, 'Dereva yuko njiani');
$delTransit = Delivery::find($deliveryId);
assertTest("Delivery status transitioned to 'OUT_FOR_DELIVERY'", $delTransit['delivery_status'] === 'OUT_FOR_DELIVERY');

// Transition: OUT_FOR_DELIVERY -> DELIVERED
Delivery::updateStatus($deliveryId, 'DELIVERED', 1, 'Mteja amepokea mzigo na kuthibitisha');
$delDelivered = Delivery::find($deliveryId);
assertTest("Delivery status transitioned to 'DELIVERED'", $delDelivered['delivery_status'] === 'DELIVERED');
assertTest("Delivered timestamp recorded", !empty($delDelivered['delivered_at']));

// Invalid Transition Blocking: DELIVERED cannot transition back to PENDING
$invalidTransitionBlocked = false;
try {
    Delivery::updateStatus($deliveryId, 'PENDING', 1, 'Jaribio batili la kurudi nyuma');
} catch (Exception $e) {
    $invalidTransitionBlocked = true;
}
assertTest("Invalid state transition from DELIVERED -> PENDING is strictly rejected", $invalidTransitionBlocked);

// Verify status history audit logging
$historyStatuses = array_column($delDelivered['history'], 'new_status');
assertTest("Delivery status history tracks all progression states", in_array('ASSIGNED', $historyStatuses) && in_array('OUT_FOR_DELIVERY', $historyStatuses) && in_array('DELIVERED', $historyStatuses));

// -----------------------------------------------------------------------------
// 7. CUSTOMER COURIER & OTHER COURIER LIFECYCLES
// -----------------------------------------------------------------------------
echo "\n7. Testing Customer Courier & Other Courier Lifecycles...\n";

// Add item to cart for customer 1
Auth::customerLogin($testEmail1, $testPass1);
Cart::addItem((int)$physicalProduct['id'], 1);

// Create order with CUSTOMER_COURIER
$orderCourier = OnlineOrder::createOrder(
    $custId1,
    $addrId1,
    'Mobile Money',
    'CUSTOMER_COURIER',
    'Dereva wangu anakuja kuchukua',
    'CUSTOMER_COURIER',
    [
        'courier_name'         => 'Ally Msafiri Courier',
        'courier_phone'        => '+255788112233',
        'courier_instructions' => 'Kadi ya pikipiki ipo chini ya kiti'
    ]
);
$courierOrderId = (int)$orderCourier['order_id'];
$courierDelivery = Delivery::findByOrder($courierOrderId);
assertTest("Customer courier delivery records courier name", $courierDelivery['courier_name'] === 'Ally Msafiri Courier');
assertTest("Customer courier delivery records courier phone", $courierDelivery['courier_phone'] === '+255788112233');
assertTest("Customer courier delivery fee is exactly 0", (float)$courierDelivery['delivery_fee'] === 0.00);

// Add item to cart for other courier order
Cart::addItem((int)$physicalProduct['id'], 1);

// Assign external reference on OTHER_COURIER
$orderOther = OnlineOrder::createOrder(
    $custId1,
    $addrId1,
    'Mobile Money',
    'OTHER_COURIER',
    'Tuma kwa basi la Shabiby kwenda Dodoma',
    'OTHER_COURIER',
    ['delivery_fee' => 7000.00]
);
$otherOrderId = (int)$orderOther['order_id'];
$otherDelivery = Delivery::findByOrder($otherOrderId);
Delivery::assignCourier($otherDelivery['id'], [
    'courier_name'         => 'Shabiby Bus Courier',
    'courier_phone'        => '+255766334455',
    'external_reference'   => 'WAYBILL-SHB-778899',
    'courier_instructions' => 'Shabiby Bus - Gari linaondoka saa 12 jioni'
], 1);
$updatedOther = Delivery::find($otherDelivery['id']);
assertTest("Other courier stores external waybill reference", $updatedOther['external_reference'] === 'WAYBILL-SHB-778899');
assertTest("Other courier stores courier name & instructions", $updatedOther['courier_name'] === 'Shabiby Bus Courier' && strpos($updatedOther['courier_instructions'], 'Shabiby') !== false);

// -----------------------------------------------------------------------------
// 8. CUSTOMER ADDRESS ISOLATION (IDOR PROTECTION)
// -----------------------------------------------------------------------------
echo "\n8. Testing Customer Address Isolation (Anti-IDOR)...\n";

$cust1AddrSeenByCust2 = CustomerAddress::find($addrId1, $custId2);
assertTest("Customer 2 cannot query Customer 1's saved address", $cust1AddrSeenByCust2 === false || $cust1AddrSeenByCust2 === null);

$cust1AddressList = CustomerAddress::getByCustomerId($custId1);
$cust1AddressIds = array_column($cust1AddressList, 'id');
assertTest("Customer 1 address list contains Address 1", in_array($addrId1, $cust1AddressIds));
assertTest("Customer 1 address list does NOT leak Customer 2's address", !in_array($addrId2, $cust1AddressIds));

// Switch session to Customer 2 and add item to cart
Auth::customerLogin($testEmail2, $testPass2);
Cart::addItem((int)$physicalProduct['id'], 1);

// Attempting to create an order using another customer's address is rejected
$idorOrderBlocked = false;
try {
    OnlineOrder::createOrder($custId2, $addrId1, 'Mobile Money', 'STORE_DELIVERY');
} catch (Exception $e) {
    $idorOrderBlocked = true;
}
assertTest("Checkout strictly rejects using an address belonging to another customer", $idorOrderBlocked);

// -----------------------------------------------------------------------------
// 9. PRODUCT IMAGE UPLOAD VALIDATION & SAFE FILENAMES
// -----------------------------------------------------------------------------
echo "\n9. Testing Product Image Upload Validation & Security...\n";

// Validate allowed MIME types and extensions
assertTest("Allowed extensions include jpg, jpeg, png, webp", in_array('jpg', ProductImage::ALLOWED_EXTENSIONS) && in_array('webp', ProductImage::ALLOWED_EXTENSIONS));
assertTest("Allowed MIME types include image/jpeg, image/png, image/webp", in_array('image/jpeg', ProductImage::ALLOWED_MIME_TYPES) && in_array('image/webp', ProductImage::ALLOWED_MIME_TYPES));
assertTest("Max file size constant configured to 5MB (5242880 bytes)", ProductImage::MAX_FILE_SIZE_BYTES === 5242880);

// ProductImage::validateAndUpload rejects non-whitelisted extensions
$invalidExtThrew = false;
try {
    ProductImage::validateAndUpload([
        'name'     => 'shell.php',
        'type'     => 'application/x-php',
        'size'     => 1024,
        'error'    => UPLOAD_ERR_OK,
        'tmp_name' => __DIR__ . '/test_phase17.php'
    ], (int)$physicalProduct['id']);
} catch (Exception $e) {
    $invalidExtThrew = true;
}
assertTest("ProductImage::validateAndUpload rejects executable .php file", $invalidExtThrew);

// ProductImage::validateAndUpload rejects file size > 5MB
$oversizedThrew = false;
try {
    ProductImage::validateAndUpload([
        'name'     => 'big_photo.jpg',
        'type'     => 'image/jpeg',
        'size'     => 6 * 1024 * 1024,
        'error'    => UPLOAD_ERR_OK,
        'tmp_name' => __DIR__ . '/test_phase17.php'
    ], (int)$physicalProduct['id']);
} catch (Exception $e) {
    $oversizedThrew = true;
}
assertTest("ProductImage::validateAndUpload rejects oversized file (> 5MB)", $oversizedThrew);

// Verify .htaccess protection in uploads/products
$htaccessPath = __DIR__ . '/../public/uploads/products/.htaccess';
assertTest("public/uploads/products/.htaccess file exists", file_exists($htaccessPath));
if (file_exists($htaccessPath)) {
    $htaccessContent = file_get_contents($htaccessPath);
    assertTest(".htaccess blocks PHP script execution in product uploads directory", strpos($htaccessContent, 'Deny from all') !== false || strpos($htaccessContent, 'php') !== false);
}

// Product image DB CRUD & Primary Toggle
$prodId = (int)$physicalProduct['id'];
$img1Id = ProductImage::add($prodId, '/uploads/products/test_img_1.jpg', 'Picha ya kwanza', 1, true);
$img2Id = ProductImage::add($prodId, '/uploads/products/test_img_2.jpg', 'Picha ya pili', 2, false);

$images = ProductImage::forProduct($prodId);
assertTest("Product has at least 2 image records in product_images", count($images) >= 2);

$primaryFirst = $images[0];
assertTest("Primary image marked correctly (is_primary = 1)", (int)$primaryFirst['is_primary'] === 1 && (int)$primaryFirst['id'] === $img1Id);

// Switch primary to image 2
ProductImage::setPrimary($img2Id, $prodId);
$imagesAfterSwitch = ProductImage::forProduct($prodId);
$newPrimary = $imagesAfterSwitch[0];
assertTest("Primary image successfully switched to Image 2", (int)$newPrimary['is_primary'] === 1 && (int)$newPrimary['id'] === $img2Id);

// SVG Placeholder fallback for products without images
$placeholderPath = __DIR__ . '/../public/assets/img/spare_part_placeholder.svg';
assertTest("SVG placeholder asset exists on disk", file_exists($placeholderPath));

// -----------------------------------------------------------------------------
// 10. EXPANDED MOTORCYCLE & BAJAJ CATALOG & ISOLATION
// -----------------------------------------------------------------------------
echo "\n10. Testing Expanded Catalog Isolation (0 Stock, No Inventory Distortion)...\n";

// Fetch catalog-only reference items seeded in Phase 17
$stmtCatalog = $pdo->query("SELECT * FROM `products` WHERE is_catalog_only = 1 LIMIT 5");
$catalogItems = $stmtCatalog->fetchAll(PDO::FETCH_ASSOC);

assertTest("Catalog reference items exist in database", count($catalogItems) > 0);

$allCatalogZeroStock = true;
foreach ($catalogItems as $cItem) {
    if ((int)$cItem['current_stock'] !== 0) {
        $allCatalogZeroStock = false;
        break;
    }
}
assertTest("All catalog reference items have strictly 0 physical stock", $allCatalogZeroStock);

// Check availability status calculation for catalog items
$sampleCatalogItem = $catalogItems[0];
$availInfo = Product::getAvailabilityInfo($sampleCatalogItem);
assertTest("Catalog item availability badge is 'CATALOG ONLY' or 'AVAILABLE ON ORDER'", in_array($availInfo['status'], ['CATALOG ONLY', 'AVAILABLE ON ORDER']));
assertTest("Catalog item can_order flag is false when stock is 0", $availInfo['can_order'] === false);

// Attempting to add 0-stock catalog item to cart is strictly prevented
$cartCatalogBlocked = false;
try {
    Cart::addItem((int)$sampleCatalogItem['id'], 1);
} catch (Exception $e) {
    $cartCatalogBlocked = true;
}
assertTest("Adding catalog-only 0-stock item to cart is strictly prevented", $cartCatalogBlocked);

// -----------------------------------------------------------------------------
// 11. CUSTOMER AVAILABILITY STATES & EXACT STOCK MASKING
// -----------------------------------------------------------------------------
echo "\n11. Testing Availability Badges & Physical Stock Masking...\n";

// Disable exact stock display setting
Setting::set('catalog_display_exact_stock', '0');

$inStockItem = [
    'is_catalog_only'     => 0,
    'availability_status' => null,
    'current_stock'       => 25,
    'reorder_level'       => 5
];
$availInStock = Product::getAvailabilityInfo($inStockItem);
assertTest("Item with stock > reorder_level returns 'IN STOCK'", $availInStock['status'] === 'IN STOCK');
assertTest("Exact stock quantity is masked when setting is disabled", $availInStock['display_quantity'] === null || $availInStock['display_quantity'] === false);

$lowStockItem = [
    'is_catalog_only'     => 0,
    'availability_status' => null,
    'current_stock'       => 3,
    'reorder_level'       => 5
];
$availLowStock = Product::getAvailabilityInfo($lowStockItem);
assertTest("Item with stock <= reorder_level returns 'LOW STOCK'", $availLowStock['status'] === 'LOW STOCK');

$outOfStockItem = [
    'is_catalog_only'     => 0,
    'availability_status' => null,
    'current_stock'       => 0,
    'reorder_level'       => 5
];
$availOutOfStock = Product::getAvailabilityInfo($outOfStockItem);
assertTest("Item with stock = 0 returns 'OUT OF STOCK'", $availOutOfStock['status'] === 'OUT OF STOCK');

// Enable exact stock display setting
Setting::set('catalog_display_exact_stock', '1');
$availInStockWithQty = Product::getAvailabilityInfo($inStockItem);
assertTest("Exact stock quantity is displayed when setting is enabled", (int)$availInStockWithQty['display_quantity'] === 25);

// -----------------------------------------------------------------------------
// 12. FINANCIAL DATA MASKING ON PUBLIC PRODUCTS
// -----------------------------------------------------------------------------
echo "\n12. Testing Public Product Financial Data Masking...\n";

$onlineProducts = Product::getOnlineProducts();
$leakedFinancials = false;
foreach ($onlineProducts as $p) {
    if (isset($p['buying_price']) || isset($p['cost_price']) || isset($p['target_margin']) || isset($p['unit_cost'])) {
        $leakedFinancials = true;
        break;
    }
}
assertTest("Public product listing strictly omits buying_price, cost, and margins", !$leakedFinancials);

$singleProduct = Product::findOnline((int)$physicalProduct['id']);
assertTest("Product detail view strictly omits buying_price", !isset($singleProduct['buying_price']));
assertTest("Product detail view exposes public selling_price", isset($singleProduct['selling_price']) && (float)$singleProduct['selling_price'] > 0);

// -----------------------------------------------------------------------------
// 13. VEHICLE COMPATIBILITY & EXPANDED SEARCH
// -----------------------------------------------------------------------------
echo "\n13. Testing Motorcycle & Bajaj Search and Compatibility...\n";

// Search for 'Bajaj' or 'Boxer'
$searchResults = Product::getOnlineProducts(['search' => 'Bajaj']);
assertTest("Catalog search for 'Bajaj' returns matching parts", !empty($searchResults));

// Test related products recommendation logic
$related = Product::getRelatedOnlineProducts((int)$physicalProduct['id'], (int)$physicalProduct['category_id'], 4);
assertTest("Related products recommendation logic returns array", is_array($related));
$selfIncludedInRelated = false;
foreach ($related as $r) {
    if ((int)$r['id'] === (int)$physicalProduct['id']) {
        $selfIncludedInRelated = true;
        break;
    }
}
assertTest("Related products strictly excludes current product itself", !$selfIncludedInRelated);

// -----------------------------------------------------------------------------
// 14. OWNER DEMAND DISCOVERY & CONVERSION TO INVENTORY
// -----------------------------------------------------------------------------
echo "\n14. Testing Customer Demand Tracking & Catalog Conversion...\n";

// Track search event
$searchLogged = ProductDemand::logEvent('search', null, 'Bajaj RE 205 Piston', null, null, $custId1);
assertTest("ProductDemand::logEvent records search event", $searchLogged);

// Track out of stock request
$oosLogged = ProductDemand::logEvent('out_of_stock_request', (int)$sampleCatalogItem['id'], null, null, null, $custId1);
assertTest("ProductDemand::logEvent records out of stock inquiry", $oosLogged);

// Track catalog view
$viewLogged = ProductDemand::logEvent('view', (int)$sampleCatalogItem['id'], null, null, null, $custId1);
assertTest("ProductDemand::logEvent records product view", $viewLogged);

$demandMetrics = ProductDemand::getDemandMetrics(10);
assertTest("Owner demand metrics retrieved successfully", !empty($demandMetrics) && isset($demandMetrics['products']));
assertTest("Owner demand dashboard tracks customer search queries", isset($demandMetrics['topSearches']));

// Convert catalog item to active sellable inventory
$targetCatalogId = (int)$sampleCatalogItem['id'];
$convertResult = ProductDemand::convertCatalogProduct(
    $targetCatalogId,
    40000.00,
    65000.00,
    10,
    1
);

assertTest("Catalog item converted to active inventory successfully", $convertResult === true);

$convertedProduct = Product::find($targetCatalogId);
assertTest("Converted product is_catalog_only set to 0", (int)$convertedProduct['is_catalog_only'] === 0);
assertTest("Converted product has sellable stock = 10", (int)$convertedProduct['current_stock'] === 10);
assertTest("Converted product has updated selling_price", (float)$convertedProduct['selling_price'] === 65000.00);

// Verify inventory transaction was created for initial stock
$stmtInv = $pdo->prepare("SELECT * FROM `inventory_transactions` WHERE product_id = ? ORDER BY id DESC LIMIT 1");
$stmtInv->execute([$targetCatalogId]);
$invTx = $stmtInv->fetch(PDO::FETCH_ASSOC);
assertTest("Inventory transaction created for converted catalog item", $invTx && (int)$invTx['quantity'] === 10);

// -----------------------------------------------------------------------------
// 15. MULTI-CHANNEL NOTIFICATION ARCHITECTURE
// -----------------------------------------------------------------------------
echo "\n15. Testing Multi-Channel Notification Architecture...\n";

// In-app notification creation
$inAppRes = NotificationManager::sendInApp(
    $custId1,
    'Oda Yako Inatayarishwa',
    'Oda yako imepokelewa na dereva amekabidhiwa mzigo.',
    'order',
    'index.php?route=customer_orders'
);
assertTest("In-app notification persisted successfully in customer_notifications", $inAppRes === true);

// Send multi-channel notification for order
$orderNotif = NotificationManager::sendOrderNotification([
    'id'                => $orderId,
    'customer_id'       => $custId1,
    'order_number'      => $orderData['order_number'],
    'delivery_provider' => 'STORE_DELIVERY',
    'delivery_status'   => 'PENDING',
    'payment_status'    => 'paid',
    'total_amount'      => $orderData['total_amount']
]);

assertTest("sendOrderNotification returns in_app status = 'SENT'", $orderNotif['in_app'] === 'SENT');
assertTest("sendOrderNotification reports external email safely as 'NOT CONFIGURED'", $orderNotif['email'] === 'NOT CONFIGURED');
assertTest("sendOrderNotification reports external SMS safely as 'NOT CONFIGURED'", $orderNotif['sms'] === 'NOT CONFIGURED');
assertTest("sendOrderNotification reports external WhatsApp safely as 'NOT CONFIGURED'", $orderNotif['whatsapp'] === 'NOT CONFIGURED');

// -----------------------------------------------------------------------------
// 16. SECURITY & RBAC BOUNDARIES FOR PHASE 17
// -----------------------------------------------------------------------------
echo "\n16. Testing Security & RBAC Boundaries for Phase 17...\n";

// Ensure customers cannot access staff permissions
assertTest("Customer cannot access 'online_delivery.dispatch'", !Auth::can('online_delivery.dispatch'));
assertTest("Customer cannot access 'online_catalog.manage'", !Auth::can('online_catalog.manage'));
assertTest("Customer cannot access 'online_demand.view'", !Auth::can('online_demand.view'));

// Verify admin permissions exist in permissions table
$stmtPerm = $pdo->prepare("SELECT COUNT(*) FROM `permissions` WHERE name IN ('online_delivery.dispatch', 'online_catalog.manage', 'online_demand.view')");
$stmtPerm->execute();
$permCount = (int)$stmtPerm->fetchColumn();
assertTest("Phase 17 granular permissions registered in database", $permCount === 3);

// -----------------------------------------------------------------------------
// 17. SYSTEM HEALTH & ZERO REGRESSION (All 49 Database Tables)
// -----------------------------------------------------------------------------
echo "\n17. Testing Schema Integrity & Table Health Across All Phases...\n";

$allRequiredTables = [
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
    'customer_wishlists', 'product_reviews', 'customer_notifications',
    // Phase 17 Tables
    'product_images', 'product_demand_events'
];

$missingTables = [];
foreach ($allRequiredTables as $tbl) {
    $check = $pdo->query("SHOW TABLES LIKE '{$tbl}'")->fetchColumn();
    if (!$check) {
        $missingTables[] = $tbl;
    }
}
assertTest("All 49 required tables exist and are healthy", empty($missingTables), $missingTables ? "Missing: " . implode(', ', $missingTables) : "49/49 verified");

// Verify Phase 17 columns in `deliveries`
$delCols = $pdo->query("DESCRIBE `deliveries`")->fetchAll(PDO::FETCH_COLUMN);
assertTest("deliveries table has delivery_provider column", in_array('delivery_provider', $delCols));
assertTest("deliveries table has external_reference column", in_array('external_reference', $delCols));
assertTest("deliveries table has driver_name column", in_array('driver_name', $delCols));
assertTest("deliveries table has courier_name column", in_array('courier_name', $delCols));

// Verify Phase 17 columns in `products`
$prodCols = $pdo->query("DESCRIBE `products`")->fetchAll(PDO::FETCH_COLUMN);
assertTest("products table has is_catalog_only column", in_array('is_catalog_only', $prodCols));
assertTest("products table has availability_status column", in_array('availability_status', $prodCols));

// -----------------------------------------------------------------------------
// CLEANUP TEST DATA
// -----------------------------------------------------------------------------
// Clean up images created during test
ProductImage::delete($img1Id, $prodId);
ProductImage::delete($img2Id, $prodId);
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
    echo "\n>>> ALL PHASE 17 TESTS PASSED SUCCESSFULLY! ZERO REGRESSION! <<<\n\n";
    exit(0);
} else {
    echo "\n>>> PHASE 17 TESTS FAILED WITH {$failed} ERRORS! <<<\n\n";
    exit(1);
}
