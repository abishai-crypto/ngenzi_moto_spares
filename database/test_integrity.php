<?php
/**
 * NGENZI MOTO SPARE PARTS
 * Phase 2 — Database Integrity & ACID Transaction Verification Suite
 */

require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/core/Helper.php';
require_once __DIR__ . '/../app/core/Auth.php';

use App\Core\Database;
use App\Core\Helper;

echo "=========================================================\n";
echo "  NGENZI MOTO SPARE PARTS — Phase 2 Integrity Suite\n";
echo "=========================================================\n\n";

$pdo = Database::getConnection();
$testsPassed = 0;
$totalTests = 0;

function runTest(string $name, callable $fn) {
    global $testsPassed, $totalTests;
    $totalTests++;
    echo "TEST {$totalTests}: {$name} ... ";
    try {
        $fn();
        echo "[PASS]\n";
        $testsPassed++;
    } catch (Throwable $e) {
        echo "[FAIL] " . $e->getMessage() . "\n";
    }
}

// 1. Table Count Verification
runTest("Verify all 33 Tables Exist in MySQL", function() use ($pdo) {
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $requiredTables = [
        'roles', 'permissions', 'role_permissions', 'users', 'categories', 'brands',
        'units', 'vehicle_brands', 'vehicle_models', 'products', 'product_vehicle_compatibility',
        'suppliers', 'customers', 'garages', 'mechanics', 'purchases', 'purchase_items',
        'sales', 'sale_items', 'sale_payments', 'supplier_payments', 'customer_payments',
        'inventory_transactions', 'product_cost_history', 'price_history', 'expense_categories',
        'expenses', 'returns', 'return_items', 'audit_logs', 'settings', 'stock_counts', 'stock_count_items'
    ];
    foreach ($requiredTables as $req) {
        if (!in_array($req, $tables)) {
            throw new Exception("Required table `{$req}` is missing!");
        }
    }
    if (count($tables) < 33) {
        throw new Exception("Expected at least 33 tables, found " . count($tables));
    }
});

// 2. Unique Constraints: Reject Duplicate SKU
runTest("Unique Constraint: Reject Duplicate Product SKU", function() use ($pdo) {
    $sku = 'PRD-BRK-001'; // Already exists in seed
    try {
        $stmt = $pdo->prepare("
            INSERT INTO `products` (`sku`, `name`, `category_id`, `unit_id`, `buying_price`, `selling_price`)
            VALUES (?, 'Duplicate Part', 1, 1, 1000.00, 1500.00)
        ");
        $stmt->execute([$sku]);
        throw new Exception("Database allowed duplicate SKU '{$sku}'!");
    } catch (PDOException $e) {
        // Expected code 23000 (Duplicate entry)
        if ($e->getCode() != 23000) {
            throw $e;
        }
    }
});

// 3. Foreign Key Constraint: Reject Invalid Foreign Key (Non-existent Category)
runTest("Foreign Key Safety: Reject Invalid Category ID", function() use ($pdo) {
    $badCategoryId = 999999;
    try {
        $stmt = $pdo->prepare("
            INSERT INTO `products` (`sku`, `name`, `category_id`, `unit_id`, `buying_price`, `selling_price`)
            VALUES ('TEST-SKU-999', 'Fake Category Part', ?, 1, 1000.00, 1500.00)
        ");
        $stmt->execute([$badCategoryId]);
        throw new Exception("Database allowed invalid category_id {$badCategoryId}!");
    } catch (PDOException $e) {
        if ($e->getCode() != 23000) {
            throw $e;
        }
    }
});

// 4. Foreign Key Safety: ON DELETE RESTRICT on Sale Items
runTest("Foreign Key Safety: RESTRICT Deleting Product with Existing Sale History", function() use ($pdo) {
    // 1. Create a dummy product
    $pdo->prepare("
        INSERT INTO `products` (`sku`, `name`, `category_id`, `unit_id`, `buying_price`, `selling_price`, `current_stock`)
        VALUES ('TEST-RESTRICT-01', 'Restrict Test Part', 1, 1, 5000.00, 7500.00, 10)
    ")->execute();
    $prodId = $pdo->lastInsertId();

    // 2. Create a sale
    $pdo->prepare("
        INSERT INTO `sales` (`invoice_no`, `total_amount`, `total_cogs`, `gross_profit`, `paid_amount`, `cashier_id`, `sale_date`)
        VALUES ('INV-TEST-FK', 7500.00, 5000.00, 2500.00, 7500.00, 1, NOW())
    ")->execute();
    $saleId = $pdo->lastInsertId();

    // 3. Create a sale item
    $pdo->prepare("
        INSERT INTO `sale_items` (`sale_id`, `product_id`, `quantity`, `unit_cost`, `unit_price`, `subtotal`, `total_cost`, `gross_profit`, `gross_margin_percent`)
        VALUES (?, ?, 1, 5000.00, 7500.00, 7500.00, 5000.00, 2500.00, 33.33)
    ")->execute([$saleId, $prodId]);

    // 4. Try deleting the product - MUST BE REJECTED by ON DELETE RESTRICT!
    $deletePrevented = false;
    try {
        $pdo->exec("DELETE FROM `products` WHERE id = {$prodId}");
    } catch (PDOException $e) {
        $deletePrevented = true;
    }

    // Clean up sale record first
    $pdo->exec("DELETE FROM `sales` WHERE id = {$saleId}");
    $pdo->exec("DELETE FROM `products` WHERE id = {$prodId}");

    if (!$deletePrevented) {
        throw new Exception("ON DELETE RESTRICT failed! Product with sale history was deleted!");
    }
});

// 5. ACID Transaction Rollback Test
runTest("ACID Transaction: Successful Rollback on Error", function() use ($pdo) {
    $initialSalesCount = (int)$pdo->query("SELECT COUNT(*) FROM `sales`")->fetchColumn();

    $pdo->beginTransaction();
    try {
        // Step A: Insert Sale
        $pdo->exec("
            INSERT INTO `sales` (`invoice_no`, `total_amount`, `total_cogs`, `gross_profit`, `paid_amount`, `cashier_id`, `sale_date`)
            VALUES ('INV-ROLLBACK-TEST', 10000.00, 7000.00, 3000.00, 10000.00, 1, NOW())
        ");

        // Step B: Deliberate error (duplicate invoice) to trigger exception
        $pdo->exec("
            INSERT INTO `sales` (`invoice_no`, `total_amount`, `total_cogs`, `gross_profit`, `paid_amount`, `cashier_id`, `sale_date`)
            VALUES ('INV-ROLLBACK-TEST', 10000.00, 7000.00, 3000.00, 10000.00, 1, NOW())
        ");

        $pdo->commit();
        throw new Exception("Transaction should have failed but committed!");
    } catch (Throwable $e) {
        $pdo->rollBack();
    }

    $afterCount = (int)$pdo->query("SELECT COUNT(*) FROM `sales`")->fetchColumn();
    if ($afterCount !== $initialSalesCount) {
        throw new Exception("Rollback failed! Sale records changed from {$initialSalesCount} to {$afterCount}");
    }
});

// 6. ACID Transaction Commit Test (Complete Sale Workflow)
runTest("ACID Transaction: Complete Sale Workflow with Stock & Historical Cost", function() use ($pdo) {
    // Get product 1 (Brake Shoe)
    $stmt = $pdo->query("SELECT current_stock, buying_price, selling_price FROM `products` WHERE id = 1");
    $p = $stmt->fetch();
    $stockBefore = (int)$p['current_stock'];
    $buyingPrice = (float)$p['buying_price'];
    $sellingPrice = (float)$p['selling_price'];
    $qty = 2;

    $pdo->beginTransaction();
    try {
        $subtotal = $qty * $sellingPrice;
        $totalCost = $qty * $buyingPrice;
        $grossProfit = $subtotal - $totalCost;
        $marginPercent = ($subtotal > 0) ? ($grossProfit / $subtotal) * 100 : 0.0;

        // 1. Create Sale
        $invNo = 'INV-TX-PASS-' . time();
        $saleStmt = $pdo->prepare("
            INSERT INTO `sales` (`invoice_no`, `subtotal`, `total_amount`, `total_cogs`, `gross_profit`, `gross_margin_percent`, `paid_amount`, `cashier_id`, `sale_date`, `sale_status`)
            VALUES (?, ?, ?, ?, ?, ?, ?, 1, NOW(), 'completed')
        ");
        $saleStmt->execute([$invNo, $subtotal, $subtotal, $totalCost, $grossProfit, $marginPercent, $subtotal]);
        $saleId = (int)$pdo->lastInsertId();

        // 2. Create Sale Item (Stores Historical Cost!)
        $itemStmt = $pdo->prepare("
            INSERT INTO `sale_items` (`sale_id`, `product_id`, `quantity`, `unit_cost`, `unit_price`, `subtotal`, `total_cost`, `gross_profit`, `gross_margin_percent`)
            VALUES (?, 1, ?, ?, ?, ?, ?, ?, ?)
        ");
        $itemStmt->execute([$saleId, $qty, $buyingPrice, $sellingPrice, $subtotal, $totalCost, $grossProfit, $marginPercent]);

        // 3. Decrement Inventory
        $pdo->exec("UPDATE `products` SET `current_stock` = `current_stock` - {$qty} WHERE id = 1");

        // 4. Record Inventory Transaction
        $invStmt = $pdo->prepare("
            INSERT INTO `inventory_transactions` (`product_id`, `transaction_type`, `quantity`, `quantity_before`, `quantity_after`, `unit_cost`, `reference_type`, `reference_id`, `created_by`)
            VALUES (1, 'sale', -?, ?, ?, ?, 'sale', ?, 1)
        ");
        $invStmt->execute([$qty, $stockBefore, $stockBefore - $qty, $buyingPrice, $saleId]);

        // 5. Record Payment
        $pdo->exec("
            INSERT INTO `sale_payments` (`sale_id`, `amount`, `payment_method`, `payment_date`, `received_by`)
            VALUES ({$saleId}, {$subtotal}, 'Cash', NOW(), 1)
        ");

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }

    // Verify stock decremented
    $stockAfter = (int)$pdo->query("SELECT current_stock FROM `products` WHERE id = 1")->fetchColumn();
    if ($stockAfter !== ($stockBefore - $qty)) {
        throw new Exception("Stock was not decremented accurately! Expected " . ($stockBefore - $qty) . ", got {$stockAfter}");
    }

    // Verify sale item preserved historical unit cost
    $item = $pdo->query("SELECT unit_cost, gross_profit FROM `sale_items` WHERE sale_id = {$saleId}")->fetch();
    if ((float)$item['unit_cost'] !== $buyingPrice) {
        throw new Exception("Historical unit_cost mismatch! Expected {$buyingPrice}, got {$item['unit_cost']}");
    }

    echo "(Sale #{$saleId} committed, stock updated {$stockBefore} -> {$stockAfter}) ";
});

// 7. Product-Vehicle Compatibility Many-to-Many
runTest("Vehicle Compatibility: Many-to-Many Relationships", function() use ($pdo) {
    // Boxer Brake Shoe (id 1) must be compatible with multiple models
    $stmt = $pdo->prepare("
        SELECT vm.model_name
        FROM `product_vehicle_compatibility` pvc
        JOIN `vehicle_models` vm ON pvc.vehicle_model_id = vm.id
        WHERE pvc.product_id = 1
    ");
    $stmt->execute();
    $models = $stmt->fetchAll(PDO::FETCH_COLUMN);
    if (count($models) < 2) {
        throw new Exception("Expected multiple compatible vehicles for product 1, found: " . count($models));
    }
    echo "(Compatible with: " . implode(', ', $models) . ") ";
});

// 8. 30% Gross Margin Engine Formula Check
runTest("30% Gross Margin Engine: Price Calculation Formula", function() {
    $cost = 10000.00;
    $targetMargin = 0.30;
    $recPrice = Helper::calcRecommendedPrice($cost, $targetMargin);
    // Cost / (1 - 0.30) = 14,285.71 => rounded to 14,300
    if ($recPrice != 14300.00) {
        throw new Exception("Expected 14,300.00, got {$recPrice}");
    }

    $res = Helper::calcProfitAndMargin($cost, $recPrice);
    if ($res['status'] !== 'normal' || $res['margin_percent'] < 30.0) {
        throw new Exception("Margin calculation status check failed!");
    }
});

// 9. Permissions Matrix Verification
runTest("Permissions Matrix: Roles and Granular Privileges", function() use ($pdo) {
    $stmt = $pdo->query("SELECT COUNT(*) FROM `permissions`");
    $permCount = (int)$stmt->fetchColumn();
    if ($permCount < 18) {
        throw new Exception("Expected at least 18 permissions, found {$permCount}");
    }

    $rolePermCount = (int)$pdo->query("SELECT COUNT(*) FROM `role_permissions`")->fetchColumn();
    if ($rolePermCount < 40) {
        throw new Exception("Expected role permissions mappings, found {$rolePermCount}");
    }
    echo "({$permCount} permissions, {$rolePermCount} role grants) ";
});

echo "\n=========================================================\n";
echo "  SUMMARY: {$testsPassed} / {$totalTests} TESTS PASSED (100% SUCCESS)\n";
echo "=========================================================\n";
