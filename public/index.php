<?php
/**
 * NGENZI MOTO SPARE PARTS
 * Main Front Controller
 * Dar es Salaam, Tanzania
 */

// Error reporting initial defaults
error_reporting(E_ALL);

// PSR-4 Style Autoloader for App namespace
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/../app/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);
    // Convert namespace to path (e.g. Core\Database -> core/Database.php)
    $parts = explode('\\', $relativeClass);
    $className = array_pop($parts);
    $dirPath = strtolower(implode('/', $parts));
    
    $file = $baseDir . ($dirPath ? $dirPath . '/' : '') . $className . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});

// Send HTTP Security Headers (Phase 14 Hardening)
if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');

    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
    if ($isHttps) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}

use App\Core\Auth;
use App\Core\Env;
use App\Core\Helper;
use App\Core\Logger;
use App\Core\Lang;
use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\UserController;
use App\Controllers\ProductController;
use App\Controllers\CatalogController;
use App\Controllers\InventoryController;
use App\Controllers\CustomerController;
use App\Controllers\MechanicController;
use App\Controllers\GarageController;
use App\Controllers\PosController;
use App\Controllers\SaleController;
use App\Controllers\PaymentController;
use App\Controllers\DebtController;
use App\Controllers\ExpenseController;
use App\Controllers\FinancialController;
use App\Controllers\ReturnController;
use App\Controllers\ReportController;
use App\Controllers\AuditController;
use App\Controllers\SecurityController;
use App\Controllers\BackupController;
use App\Controllers\SettingController;
use App\Controllers\StoreController;
use App\Controllers\CustomerAuthController;
use App\Controllers\CustomerPortalController;
use App\Controllers\CartController;
use App\Controllers\CheckoutController;
use App\Controllers\OnlineOrderAdminController;

// Initialize Environment & Timezone
Env::init();
date_default_timezone_set(Env::get('APP_TIMEZONE', 'Africa/Dar_es_Salaam'));

// Configure Debug Mode & Register Production Error Handlers
if (Env::isDebug()) {
    ini_set('display_errors', 1);
} else {
    ini_set('display_errors', 0);
}
Logger::registerHandlers();

// Initialize Localization Engine (Phase 18)
require_once __DIR__ . '/../app/core/Lang.php';
require_once __DIR__ . '/../app/core/helpers.php';
Lang::getLocale();

// Start session
Auth::startSession();

// Simple Route Dispatcher
$route = $_GET['route'] ?? (Auth::check() ? 'dashboard' : 'store');

switch ($route) {
    case 'switch_language':
        $lang = trim($_GET['lang'] ?? 'sw');
        Lang::setLocale($lang);
        $redirect = $_SERVER['HTTP_REFERER'] ?? '/index.php';
        if (strpos($redirect, 'switch_language') !== false) {
            $redirect = '/index.php';
        }
        header("Location: {$redirect}");
        exit;

    case 'login':
        (new AuthController())->showLogin();
        break;

    case 'login_post':
        (new AuthController())->login();
        break;

    case 'logout':
        (new AuthController())->logout();
        break;

    // User Management Routes (Phase 3)
    case 'users':
        (new UserController())->index();
        break;

    case 'user_create':
        (new UserController())->create();
        break;

    case 'user_store':
        (new UserController())->store();
        break;

    case 'user_edit':
        (new UserController())->edit();
        break;

    case 'user_update':
        (new UserController())->update();
        break;

    case 'user_toggle':
        (new UserController())->toggleStatus();
        break;

    case 'user_reset_password':
        (new UserController())->resetPassword();
        break;

    case 'change_password':
        (new UserController())->changePasswordForm();
        break;

    case 'change_password_post':
        (new UserController())->changePasswordPost();
        break;

    case 'roles_permissions':
        (new UserController())->rolesAndPermissions();
        break;

    case 'role_permissions_update':
        (new UserController())->updateRolePermissions();
        break;

    // Product & Catalog Routes (Phase 4)
    case 'products':
        (new ProductController())->index();
        break;

    case 'product_create':
        (new ProductController())->create();
        break;

    case 'product_store':
        (new ProductController())->store();
        break;

    case 'product_edit':
        (new ProductController())->edit();
        break;

    case 'product_update':
        (new ProductController())->update();
        break;

    case 'product_show':
        (new ProductController())->show();
        break;

    case 'product_toggle':
        (new ProductController())->toggleStatus();
        break;

    case 'product_vehicle_search':
        (new ProductController())->vehicleSearch();
        break;

    case 'product_ajax_search':
        (new ProductController())->ajaxSearch();
        break;

    case 'categories':
        (new CatalogController())->categories();
        break;

    case 'category_store':
        (new CatalogController())->categoryStore();
        break;

    case 'category_update':
        (new CatalogController())->categoryUpdate();
        break;

    case 'category_toggle':
        (new CatalogController())->categoryToggle();
        break;

    case 'brands':
        (new CatalogController())->brands();
        break;

    case 'brand_store':
        (new CatalogController())->brandStore();
        break;

    case 'brand_update':
        (new CatalogController())->brandUpdate();
        break;

    case 'brand_toggle':
        (new CatalogController())->brandToggle();
        break;

    case 'units':
        (new CatalogController())->units();
        break;

    case 'unit_store':
        (new CatalogController())->unitStore();
        break;

    case 'unit_update':
        (new CatalogController())->unitUpdate();
        break;

    case 'vehicles':
        (new CatalogController())->vehicles();
        break;

    case 'vehicle_brand_store':
        (new CatalogController())->vehicleBrandStore();
        break;

    case 'vehicle_brand_toggle':
        (new CatalogController())->vehicleBrandToggle();
        break;

    case 'vehicle_model_store':
        (new CatalogController())->vehicleModelStore();
        break;

    case 'vehicle_model_toggle':
        (new CatalogController())->vehicleModelToggle();
        break;

    // === PHASE 5: INVENTORY & STOCK MANAGEMENT ROUTES ===
    case 'inventory_dashboard':
        (new InventoryController())->dashboard();
        break;

    case 'inventory':
        (new InventoryController())->index();
        break;

    case 'inventory_history':
        (new InventoryController())->history();
        break;

    case 'inventory_adjust':
        (new InventoryController())->adjust();
        break;

    case 'inventory_adjust_save':
        (new InventoryController())->storeAdjustment();
        break;

    case 'inventory_counts':
        (new InventoryController())->counts();
        break;

    case 'inventory_count_create':
        (new InventoryController())->createCount();
        break;

    case 'inventory_count_store':
        (new InventoryController())->storeCount();
        break;

    case 'inventory_count_show':
        (new InventoryController())->showCount();
        break;

    case 'inventory_count_approve':
        (new InventoryController())->approveCount();
        break;

    case 'inventory_count_reject':
        (new InventoryController())->rejectCount();
        break;

    case 'inventory_valuation':
        (new InventoryController())->valuation();
        break;

    case 'inventory_analytics':
        (new InventoryController())->analytics();
        break;

    case 'inventory_reorder':
        (new InventoryController())->reorder();
        break;

    case 'inventory_barcode':
        (new InventoryController())->ajaxBarcodeLookup();
        break;

    // === PHASE 7: CUSTOMERS, MECHANICS & GARAGES ROUTES ===
    // Customer Routes
    case 'customer_portal':
    case 'customer_dashboard':
        if (Auth::isCustomer()) {
            (new CustomerPortalController())->dashboard();
        } else {
            (new CustomerController())->dashboard();
        }
        break;

    case 'customers':
        (new CustomerController())->index();
        break;

    case 'customer_create':
        (new CustomerController())->create();
        break;

    case 'customer_store':
        (new CustomerController())->store();
        break;

    case 'customer_show':
        (new CustomerController())->show();
        break;

    case 'customer_edit':
        (new CustomerController())->edit();
        break;

    case 'customer_update':
        (new CustomerController())->update();
        break;

    case 'customer_toggle_status':
        (new CustomerController())->toggleStatus();
        break;

    case 'customer_vehicle_add':
        (new CustomerController())->addVehicle();
        break;

    case 'customer_vehicle_remove':
        (new CustomerController())->removeVehicle();
        break;

    case 'customer_ajax_search':
        (new CustomerController())->ajaxSearch();
        break;

    // Mechanic Routes
    case 'mechanics':
        (new MechanicController())->index();
        break;

    case 'mechanic_create':
        (new MechanicController())->create();
        break;

    case 'mechanic_store':
        (new MechanicController())->store();
        break;

    case 'mechanic_show':
        (new MechanicController())->show();
        break;

    case 'mechanic_edit':
        (new MechanicController())->edit();
        break;

    case 'mechanic_update':
        (new MechanicController())->update();
        break;

    case 'mechanic_toggle_status':
        (new MechanicController())->toggleStatus();
        break;

    // Garage Routes
    case 'garages':
        (new GarageController())->index();
        break;

    case 'garage_create':
        (new GarageController())->create();
        break;

    case 'garage_store':
        (new GarageController())->store();
        break;

    case 'garage_show':
        (new GarageController())->show();
        break;

    case 'garage_edit':
        (new GarageController())->edit();
        break;

    case 'garage_update':
        (new GarageController())->update();
        break;

    case 'garage_toggle_status':
        (new GarageController())->toggleStatus();
        break;

    // === PHASE 8: POS & SALES MANAGEMENT ROUTES ===
    // POS Terminal Routes
    case 'pos':
        (new PosController())->index();
        break;

    case 'pos_search_products':
        (new PosController())->searchProducts();
        break;

    case 'pos_barcode_lookup':
        (new PosController())->lookupBarcode();
        break;

    case 'pos_quick_customer':
        (new PosController())->quickCreateCustomer();
        break;

    case 'pos_checkout':
        (new PosController())->checkout();
        break;

    // Sales History & Details Routes
    case 'sales':
        (new SaleController())->index();
        break;

    case 'sale_show':
        (new SaleController())->show();
        break;

    case 'sale_receipt':
        (new SaleController())->receipt();
        break;

    case 'sale_invoice':
        (new SaleController())->invoice();
        break;

    case 'sale_cancel':
        (new SaleController())->cancel();
        break;

    case 'sales_daily_summary':
        (new SaleController())->dailySummary();
        break;

    // === PHASE 9: PAYMENTS, CREDIT & DEBT MANAGEMENT ROUTES ===
    // Payment Directory & Receipts
    case 'payments':
        (new PaymentController())->index();
        break;

    case 'payment_show':
        (new PaymentController())->show();
        break;

    case 'payment_receipt':
        (new PaymentController())->receipt();
        break;

    case 'payment_store':
        (new PaymentController())->store();
        break;

    case 'payment_reverse':
        (new PaymentController())->reverse();
        break;

    case 'payments_daily_collection':
        (new PaymentController())->dailyCollection();
        break;

    // Debt & Credit Management Routes
    case 'debts':
        (new DebtController())->index();
        break;

    case 'debt_aging':
        (new DebtController())->agingReport();
        break;

    case 'debt_collect':
        (new DebtController())->collect();
        break;

    case 'debt_statement':
        (new DebtController())->statement();
        break;

    case 'debt_customer_profile':
        (new DebtController())->profile();
        break;

    // === PHASE 10: EXPENSES & FINANCIAL MANAGEMENT ROUTES ===
    case 'expenses':
        (new ExpenseController())->index();
        break;

    case 'expenses/create':
    case 'expenses_create':
        (new ExpenseController())->create();
        break;

    case 'expenses/store':
    case 'expenses_store':
        (new ExpenseController())->store();
        break;

    case 'expenses/show':
    case 'expenses_show':
        (new ExpenseController())->show();
        break;

    case 'expenses/approve':
    case 'expenses_approve':
        (new ExpenseController())->approve();
        break;

    case 'expenses/reject':
    case 'expenses_reject':
        (new ExpenseController())->reject();
        break;

    case 'expenses/cancel':
    case 'expenses_cancel':
        (new ExpenseController())->cancel();
        break;

    case 'expenses/categories':
    case 'expense_categories':
        (new ExpenseController())->categories();
        break;

    case 'expenses/categories/store':
    case 'expense_categories_store':
        (new ExpenseController())->storeCategory();
        break;

    case 'expenses/categories/update':
    case 'expense_categories_update':
        (new ExpenseController())->updateCategory();
        break;

    case 'expenses/categories/toggle':
    case 'expense_categories_toggle':
        (new ExpenseController())->toggleCategoryStatus();
        break;

    case 'financial/dashboard':
    case 'financial_dashboard':
        (new FinancialController())->dashboard();
        break;

    case 'financial/profit_report':
    case 'financial_profit':
    case 'financial_report':
        (new FinancialController())->profitReport();
        break;

    case 'financial/product_profit':
    case 'product_profit':
        (new FinancialController())->productProfit();
        break;

    case 'financial/category_profit':
    case 'category_profit':
        (new FinancialController())->categoryProfit();
        break;

    case 'financial/sale_profit':
    case 'sale_profit':
        (new FinancialController())->saleProfit();
        break;

    case 'financial/expense_analysis':
    case 'expense_analysis':
        (new FinancialController())->expenseAnalysis();
        break;

    case 'financial/period_comparison':
    case 'period_comparison':
        (new FinancialController())->periodComparison();
        break;

    // === PHASE 11: RETURNS & REFUNDS MANAGEMENT ROUTES ===
    case 'returns_dashboard':
        (new ReturnController())->dashboard();
        break;

    case 'returns':
        (new ReturnController())->index();
        break;

    case 'return_create':
        (new ReturnController())->create();
        break;

    case 'return_store':
        (new ReturnController())->store();
        break;

    case 'return_show':
        (new ReturnController())->show();
        break;

    case 'return_approve':
        (new ReturnController())->approve();
        break;

    case 'return_receive':
        (new ReturnController())->receive();
        break;

    case 'return_refund':
        (new ReturnController())->refund();
        break;

    case 'return_reject':
        (new ReturnController())->reject();
        break;

    case 'return_cancel':
        (new ReturnController())->cancel();
        break;

    case 'return_receipt':
        (new ReturnController())->receipt();
        break;

    case 'return_credit_note':
        (new ReturnController())->creditNote();
        break;

    case 'return_reports':
        (new ReturnController())->reports();
        break;

    case 'return_ajax_sale_lookup':
        (new ReturnController())->ajaxSaleLookup();
        break;

    // === PHASE 12: REPORTS & ADVANCED BUSINESS ANALYTICS ROUTES ===
    case 'reports':
    case 'reports/dashboard':
    case 'reports_dashboard':
        (new ReportController())->index();
        break;

    case 'reports/sales':
    case 'reports_sales':
        (new ReportController())->sales();
        break;

    case 'reports/sales_by_product':
    case 'reports_sales_by_product':
        (new ReportController())->salesByProduct();
        break;

    case 'reports/sales_by_category':
    case 'reports_sales_by_category':
        (new ReportController())->salesByCategory();
        break;

    case 'reports/sales_by_cashier':
    case 'reports_sales_by_cashier':
        (new ReportController())->salesByCashier();
        break;

    case 'reports/sales_by_customer':
    case 'reports_sales_by_customer':
        (new ReportController())->salesByCustomer();
        break;

    case 'reports/payments':
    case 'reports_payments':
        (new ReportController())->payments();
        break;

    case 'reports/vehicle_compatibility':
    case 'reports_vehicle_compatibility':
    case 'reports_vehicles':
        (new ReportController())->vehicleCompatibility();
        break;

    case 'reports/debts':
    case 'reports_debts':
        (new ReportController())->debts();
        break;

    case 'reports/customer_statement':
    case 'reports_customer_statement':
        (new ReportController())->customerStatement();
        break;

    case 'reports/inventory':
    case 'reports_inventory':
        (new ReportController())->inventory();
        break;

    case 'reports/purchases':
    case 'reports_purchases':
        (new ReportController())->purchases();
        break;

    case 'reports/returns':
    case 'reports_returns':
        (new ReportController())->returns();
        break;

    case 'reports/product_performance':
    case 'reports_product_performance':
        (new ReportController())->productPerformance();
        break;

    case 'reports/product_images':
    case 'report_product_images':
    case 'product_images':
        (new ReportController())->productImages();
        break;

    case 'reports/export':
    case 'reports_export':
        (new ReportController())->export();
        break;

    case 'dashboard/export':
    case 'dashboard_export':
        (new DashboardController())->export();
        break;

    case 'dashboard/update_thresholds':
    case 'dashboard_update_thresholds':
        (new DashboardController())->updateThresholds();
        break;

    // === PHASE 14: AUDIT TRAIL & SECURITY OVERVIEW ROUTES ===
    case 'audit':
        (new AuditController())->index();
        break;

    case 'audit_details':
        (new AuditController())->details();
        break;

    case 'audit_export':
        (new AuditController())->export();
        break;

    case 'security':
        (new SecurityController())->index();
        break;

    // === PHASE 15: BACKUP, SETTINGS & SYSTEM HEALTH ROUTES ===
    case 'backups':
        (new BackupController())->index();
        break;

    case 'backup_create':
        (new BackupController())->create();
        break;

    case 'backup_download':
        (new BackupController())->download();
        break;

    case 'backup_delete':
        (new BackupController())->delete();
        break;

    case 'backup_restore_test':
        (new BackupController())->restoreTest();
        break;

    case 'settings':
        (new SettingController())->index();
        break;

    case 'settings_update':
        (new SettingController())->update();
        break;

    case 'health':
        (new SettingController())->health();
        break;

    // === PHASE 16: PUBLIC STOREFRONT ROUTES ===
    case 'store':
    case 'home':
        (new StoreController())->index();
        break;

    case 'shop':
        (new StoreController())->shop();
        break;

    case 'product_detail':
        (new StoreController())->productDetail();
        break;

    case 'store_vehicle':
        (new StoreController())->vehicleCompatibility();
        break;

    case 'terms':
        (new StoreController())->terms();
        break;

    case 'privacy':
        (new StoreController())->privacy();
        break;

    case 'return_policy':
        (new StoreController())->returnPolicy();
        break;

    // === PHASE 16: CUSTOMER AUTHENTICATION ROUTES ===
    case 'customer_login':
        (new CustomerAuthController())->showLogin();
        break;

    case 'customer_login_post':
        (new CustomerAuthController())->login();
        break;

    case 'customer_register':
        (new CustomerAuthController())->showRegister();
        break;

    case 'customer_register_post':
        (new CustomerAuthController())->register();
        break;

    case 'customer_logout':
        (new CustomerAuthController())->logout();
        break;

    // === PHASE 16: SHOPPING CART ROUTES ===
    case 'cart':
        (new CartController())->index();
        break;

    case 'cart_add':
        (new CartController())->add();
        break;

    case 'cart_update':
        (new CartController())->update();
        break;

    case 'cart_remove':
        (new CartController())->remove();
        break;

    case 'cart_clear':
        (new CartController())->clear();
        break;

    case 'cart_summary':
        (new CartController())->summary();
        break;

    // === PHASE 16: CHECKOUT & PAYMENT ROUTES ===
    case 'checkout':
        (new CheckoutController())->index();
        break;

    case 'checkout_process':
        (new CheckoutController())->process();
        break;

    case 'checkout_success':
        (new CheckoutController())->success();
        break;

    // === PHASE 16: CUSTOMER PORTAL & ACCOUNT ROUTES ===
    case 'customer_profile':
        (new CustomerPortalController())->profile();
        break;

    case 'customer_profile_update':
        (new CustomerPortalController())->updateProfile();
        break;

    case 'customer_addresses':
        (new CustomerPortalController())->addresses();
        break;

    case 'customer_address_store':
        (new CustomerPortalController())->storeAddress();
        break;

    case 'customer_address_update':
        (new CustomerPortalController())->updateAddress();
        break;

    case 'customer_address_default':
        (new CustomerPortalController())->setDefaultAddress();
        break;

    case 'customer_address_delete':
        (new CustomerPortalController())->deleteAddress();
        break;

    case 'customer_orders':
        (new CustomerPortalController())->orders();
        break;

    case 'customer_order_show':
    case 'customer_order_detail':
        (new CustomerPortalController())->orderDetail();
        break;

    case 'customer_order_cancel':
        (new CustomerPortalController())->cancelOrder();
        break;

    case 'customer_order_invoice':
    case 'customer_print_invoice':
        (new CustomerPortalController())->printInvoice();
        break;

    case 'customer_return_create':
        (new CustomerPortalController())->returnRequest();
        break;

    case 'customer_return_store':
        (new CustomerPortalController())->storeReturnRequest();
        break;

    case 'customer_wishlist':
        (new CustomerPortalController())->wishlist();
        break;

    case 'wishlist_add':
        (new CustomerPortalController())->addToWishlist();
        break;

    case 'wishlist_remove':
        (new CustomerPortalController())->removeFromWishlist();
        break;

    case 'wishlist_move_to_cart':
        (new CustomerPortalController())->moveWishlistToCart();
        break;

    case 'customer_notifications':
        (new CustomerPortalController())->notifications();
        break;

    case 'customer_notification_read':
        (new CustomerPortalController())->markNotificationRead();
        break;

    // === PHASE 16: STAFF ONLINE ORDERS & E-COMMERCE ADMIN ROUTES ===
    case 'online_dashboard':
        (new OnlineOrderAdminController())->dashboard();
        break;

    case 'online_orders':
        (new OnlineOrderAdminController())->orders();
        break;

    case 'online_order_show':
        (new OnlineOrderAdminController())->orderShow();
        break;

    case 'online_order_update_status':
        (new OnlineOrderAdminController())->updateOrderStatus();
        break;

    case 'online_payment_confirm':
        (new OnlineOrderAdminController())->confirmPayment();
        break;

    case 'online_deliveries':
        (new OnlineOrderAdminController())->deliveries();
        break;

    case 'online_delivery_update_status':
        (new OnlineOrderAdminController())->updateDeliveryStatus();
        break;

    case 'online_delivery_assign':
        (new OnlineOrderAdminController())->assignCourier();
        break;

    case 'online_demand':
        (new OnlineOrderAdminController())->demand();
        break;

    case 'online_convert_catalog':
        (new OnlineOrderAdminController())->convertCatalogProduct();
        break;

    case 'request_out_of_stock':
        (new StoreController())->requestOutOfStock();
        break;

    case 'product_image_upload':
        (new ProductController())->uploadImage();
        break;

    case 'product_image_delete':
        (new ProductController())->deleteImage();
        break;

    case 'product_image_set_primary':
        (new ProductController())->setPrimaryImage();
        break;

    case 'online_customers':
        (new OnlineOrderAdminController())->customers();
        break;

    case 'online_settings':
        (new OnlineOrderAdminController())->settings();
        break;

    case 'dashboard':
    default:
        (new DashboardController())->index();
        break;
}

