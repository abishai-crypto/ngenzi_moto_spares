<?php
/**
 * NGENZI MOTO SPARE PARTS
 * Phase 15 Automated Verification Suite: Backup, Deployment, Configuration, Testing & Production Readiness
 * Dar es Salaam, Tanzania
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/core/Env.php';
require_once __DIR__ . '/../app/core/Logger.php';
require_once __DIR__ . '/../app/core/Helper.php';
require_once __DIR__ . '/../app/core/Auth.php';
require_once __DIR__ . '/../app/models/Setting.php';
require_once __DIR__ . '/../app/models/Backup.php';
require_once __DIR__ . '/../app/models/Product.php';
require_once __DIR__ . '/../app/models/Customer.php';
require_once __DIR__ . '/../app/models/Sale.php';
require_once __DIR__ . '/../app/models/Returns.php';

use App\Core\Database;
use App\Core\Env;
use App\Core\Logger;
use App\Core\Auth;
use App\Models\Setting;
use App\Models\Backup;
use App\Models\Product;
use App\Models\Customer;
use App\Models\Sale;
use App\Models\Returns;

$pdo = Database::getConnection();

echo "======================================================================\n";
echo "NGENZI MOTO SPARE PARTS - PHASE 15 PRODUCTION READINESS TEST SUITE\n";
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
// TEST 1: ENVIRONMENT CONFIGURATION (.ENV & ENV CLASS)
// -----------------------------------------------------------------------------
echo "1. Testing Environment Detection & Configuration Loader (.env)...\n";
Env::init();
$envApp = Env::get('APP_NAME');
$envHost = Env::get('DB_HOST');
$envPort = Env::get('DB_PORT');
$envDebug = Env::isDebug();
$envName = Env::environment();

assertTest("Env loader reads APP_NAME correctly", $envApp === 'NGENZI MOTO SPARE PARTS', "App Name: {$envApp}");
assertTest("Env loader reads DB_PORT as integer/string 3307", (int)$envPort === 3307, "Port: {$envPort}");
assertTest("Env loader supports default fallback for missing key", Env::get('NON_EXISTENT_KEY_XYZ', 'fallback_val') === 'fallback_val');

// -----------------------------------------------------------------------------
// TEST 2: DEBUG MODE & SENSITIVE LOG SCRUBBING
// -----------------------------------------------------------------------------
echo "\n2. Testing Debug Mode & Sensitive Log Scrubbing...\n";
$sensitiveData = [
    'username' => 'test_admin',
    'password' => 'SuperSecret123!',
    'db_password' => 'mysql_pass',
    'csrf_token' => 'a1b2c3d4e5f6',
    'credit_card' => '4111222233334444',
    'amount' => 50000
];

$sanitized = Logger::sanitizeContext($sensitiveData);
assertTest("Password is redacted in log context", $sanitized['password'] === '***REDACTED***');
assertTest("Database password is redacted in log context", $sanitized['db_password'] === '***REDACTED***');
assertTest("CSRF token is redacted in log context", $sanitized['csrf_token'] === '***REDACTED***');
assertTest("Credit card is redacted in log context", $sanitized['credit_card'] === '***REDACTED***');
assertTest("Non-sensitive fields are preserved", $sanitized['username'] === 'test_admin' && $sanitized['amount'] === 50000);

// Verify log writing
Logger::info("Phase 15 Automated Test Log Entry", ['test_run' => true]);
$logFile = Logger::getLogPath();
assertTest("Application log file exists and is writable", file_exists($logFile) && is_writable($logFile));

// -----------------------------------------------------------------------------
// TEST 3: DATABASE BACKUP ENGINE (CREATION & FORMAT)
// -----------------------------------------------------------------------------
echo "\n3. Testing Database Backup Creation & Dual Engine...\n";
$backupResult = Backup::createBackup(1);

assertTest("Backup execution returned success", $backupResult['success'] === true, "Engine: {$backupResult['engine']}");
assertTest("Backup file was created on disk", file_exists($backupResult['filepath']));
assertTest("Backup file size is non-zero (> 50KB)", $backupResult['filesize'] > 50000, "Size: {$backupResult['formatted_size']}");
assertTest("Backup filename follows required convention", (bool)preg_match('/^spare_parts_backup_\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}\.sql$/', $backupResult['filename']));

$backupContent = file_get_contents($backupResult['filepath'], false, null, 0, 4096);
assertTest("Backup contains table creation DDL statements", strpos($backupContent, 'CREATE TABLE') !== false);
assertTest("Backup specifies safe foreign key handling", strpos($backupContent, 'FOREIGN_KEY_CHECKS') !== false);

// -----------------------------------------------------------------------------
// TEST 4: SAFE RESTORE VERIFICATION IN TEMPORARY DATABASE
// -----------------------------------------------------------------------------
echo "\n4. Testing Safe Restore Verification in Temporary Database...\n";
$testDbName = 'ngenzi_restore_test';
$restoreResult = Backup::testRestore($backupResult['filename'], $testDbName);

assertTest("Disaster recovery restore test succeeded", $restoreResult['success'] === true, $restoreResult['message'] ?? '');
assertTest("Restored database had at least 30 tables", ($restoreResult['tables_restored'] ?? 0) >= 30, "Restored: " . ($restoreResult['tables_restored'] ?? 0) . " tables");
assertTest("Critical business tables verified in restored backup", ($restoreResult['critical_tables_verified'] ?? false) === true);

// Verify temporary database was cleanly dropped afterwards
$dbCheckStmt = $pdo->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '{$testDbName}'");
$tempStillExists = $dbCheckStmt->fetchColumn();
assertTest("Temporary restore test database was safely cleaned up", $tempStillExists === false);

// -----------------------------------------------------------------------------
// TEST 5: STORAGE DIRECTORY WEB ACCESS RESTRICTION (.HTACCESS)
// -----------------------------------------------------------------------------
echo "\n5. Testing Storage Directory Web Protection (.htaccess)...\n";
$storageHtaccess = dirname(__DIR__) . '/storage/.htaccess';
$backupHtaccess = dirname(__DIR__) . '/storage/backups/.htaccess';
$logHtaccess = dirname(__DIR__) . '/storage/logs/.htaccess';

assertTest("storage/.htaccess exists", file_exists($storageHtaccess));
assertTest("storage/backups/.htaccess exists", file_exists($backupHtaccess));
assertTest("storage/logs/.htaccess exists", file_exists($logHtaccess));

$htContent = file_get_contents($storageHtaccess);
assertTest("storage/.htaccess contains deny all directives", strpos($htContent, 'Require all denied') !== false || strpos($htContent, 'Deny from all') !== false);

// -----------------------------------------------------------------------------
// TEST 6: SYSTEM SETTINGS MANAGEMENT & PERSISTENCE
// -----------------------------------------------------------------------------
echo "\n6. Testing Business Settings Persistence via Setting::set()...\n";
$testKey = 'test_setting_p15_' . time();
$testVal = 'Tanzania_Kariakoo_' . rand(1000, 9999);

$saved = Setting::set($testKey, $testVal, 1, 'Temporary test setting');
assertTest("Setting::set() returns true on write", $saved === true);

Setting::clearCache();
$fetched = Setting::get($testKey);
assertTest("Setting::get() retrieves persisted value from DB", $fetched === $testVal, "Value: {$fetched}");

// Clean up test setting
$pdo->prepare("DELETE FROM `settings` WHERE setting_key = ?")->execute([$testKey]);
Setting::clearCache();

// -----------------------------------------------------------------------------
// TEST 7: SYSTEM HEALTH DIAGNOSTICS & RUNTIME METRICS
// -----------------------------------------------------------------------------
echo "\n7. Testing System Health Diagnostics...\n";
$requiredExts = ['pdo', 'pdo_mysql', 'mbstring', 'openssl', 'json', 'ctype'];
$allExtsLoaded = true;
foreach ($requiredExts as $ext) {
    if (!extension_loaded($ext)) {
        $allExtsLoaded = false;
        break;
    }
}
assertTest("All required PHP runtime extensions are loaded", $allExtsLoaded);

$backupDir = Backup::getBackupDir();
assertTest("Backup directory is writable", is_writable($backupDir));
$logsDir = dirname(__DIR__) . '/storage/logs';
assertTest("Logs directory is writable", is_writable($logsDir));

// -----------------------------------------------------------------------------
// TEST 8: RBAC PERMISSIONS FOR BACKUPS & SETTINGS
// -----------------------------------------------------------------------------
echo "\n8. Testing RBAC Role Permissions for Phase 15...\n";
$adminRoleStmt = $pdo->prepare("
    SELECT COUNT(*) FROM role_permissions rp
    JOIN permissions p ON rp.permission_id = p.id
    JOIN roles r ON rp.role_id = r.id
    WHERE r.name = 'super_admin' AND p.name IN ('backups.view', 'backups.create', 'backups.download', 'settings.view', 'settings.edit', 'system.health')
");
$adminRoleStmt->execute();
$adminPermCount = (int)$adminRoleStmt->fetchColumn();
assertTest("Super Admin has all 6 Phase 15 permissions mapped", $adminPermCount === 6, "Count: {$adminPermCount}/6");

$cashierRoleStmt = $pdo->prepare("
    SELECT COUNT(*) FROM role_permissions rp
    JOIN permissions p ON rp.permission_id = p.id
    JOIN roles r ON rp.role_id = r.id
    WHERE r.name = 'cashier' AND p.name IN ('backups.create', 'backups.delete', 'backups.download', 'settings.edit')
");
$cashierRoleStmt->execute();
$cashierRestrictedCount = (int)$cashierRoleStmt->fetchColumn();
assertTest("Cashier role is strictly blocked from sensitive backup and settings modifications", $cashierRestrictedCount === 0);

// -----------------------------------------------------------------------------
// TEST 9: ACCOUNTING RECONCILIATION & DECIMAL PRECISION
// -----------------------------------------------------------------------------
echo "\n9. Testing Accounting Decimal Precision & Financial Integrity...\n";
// Verify sales table: subtotal - discount = total_amount
$saleInconsistencies = $pdo->query("
    SELECT COUNT(*) FROM sales
    WHERE ABS((subtotal - discount_amount) - total_amount) > 0.05
    AND sale_status NOT IN ('cancelled', 'draft')
")->fetchColumn();
assertTest("All active sales have exact subtotal - discount = total balance", (int)$saleInconsistencies === 0);

// Verify paid_amount + balance_due = total_amount for non-refunded sales
$paymentInconsistencies = $pdo->query("
    SELECT COUNT(*) FROM sales
    WHERE ABS((paid_amount + balance_due) - total_amount) > 0.05
    AND sale_status NOT IN ('cancelled', 'draft', 'refunded', 'partially_refunded')
")->fetchColumn();
assertTest("All active non-refunded sales have exact paid_amount + balance_due = total_amount", (int)$paymentInconsistencies === 0);

// -----------------------------------------------------------------------------
// TEST 10: INVENTORY INTEGRITY & STOCK RECONCILIATION
// -----------------------------------------------------------------------------
echo "\n10. Testing Inventory Integrity & Non-Negative Stock Rules...\n";
$negativeStockCount = $pdo->query("
    SELECT COUNT(*) FROM products WHERE current_stock < 0
")->fetchColumn();
assertTest("Zero products have negative current stock", (int)$negativeStockCount === 0);

$orphanSaleItems = $pdo->query("
    SELECT COUNT(*) FROM sale_items si
    LEFT JOIN products p ON si.product_id = p.id
    WHERE p.id IS NULL
")->fetchColumn();
assertTest("Zero orphaned sale items (all reference valid products)", (int)$orphanSaleItems === 0);

$orphanPayments = $pdo->query("
    SELECT COUNT(*) FROM sale_payments sp
    LEFT JOIN sales s ON sp.sale_id = s.id
    WHERE s.id IS NULL
")->fetchColumn();
assertTest("Zero orphaned sale payments (all reference valid sales)", (int)$orphanPayments === 0);

// -----------------------------------------------------------------------------
// TEST 11: EMPTY-STATE TOLERANCE
// -----------------------------------------------------------------------------
echo "\n11. Testing Empty-State Tolerance...\n";
// Query non-existent product or return
$nonExistentProduct = Product::find(999999);
assertTest("Product::find(999999) returns null without exception", $nonExistentProduct === null);

$nonExistentCustomer = Customer::find(999999);
assertTest("Customer::find(999999) returns null without exception", $nonExistentCustomer === null);

$nonExistentBackup = Backup::getBackupPath('invalid_file_../../etc/passwd.sql');
assertTest("Backup::getBackupPath safely rejects directory traversal attempts", $nonExistentBackup === null);

// -----------------------------------------------------------------------------
// SUMMARY
// -----------------------------------------------------------------------------
echo "\n======================================================================\n";
echo "PHASE 15 TEST RESULTS:\n";
echo "Total Assertions: " . ($passed + $failed) . "\n";
echo "Passed: {$passed}\n";
echo "Failed: {$failed}\n";
echo "Success Rate: " . round(($passed / ($passed + $failed)) * 100, 1) . "%\n";
echo "======================================================================\n";

if ($failed === 0) {
    echo "STATUS: ALL PHASE 15 PRODUCTION READINESS TESTS PASSED (100%)!\n";
    exit(0);
} else {
    echo "STATUS: SOME TESTS FAILED!\n";
    exit(1);
}
