<?php
/**
 * NGENZI MOTO SPARE PARTS
 * Phase 8 Database Migration: POS, Sales & Role Permissions
 */

require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/core/Database.php';

use App\Core\Database;

$pdo = Database::getConnection();

echo "Starting Phase 8 Migration...\n";

// 1. Add snapshot columns to `sales` table if missing
$salesCols = $pdo->query("DESCRIBE `sales`")->fetchAll(PDO::FETCH_COLUMN);

if (!in_array('customer_name', $salesCols)) {
    $pdo->exec("ALTER TABLE `sales` ADD COLUMN `customer_name` VARCHAR(150) NULL AFTER `customer_id`");
    echo "✓ Added `customer_name` snapshot column to `sales` table.\n";
}

if (!in_array('customer_phone', $salesCols)) {
    $pdo->exec("ALTER TABLE `sales` ADD COLUMN `customer_phone` VARCHAR(50) NULL AFTER `customer_name`");
    echo "✓ Added `customer_phone` snapshot column to `sales` table.\n";
}

// 2. Add snapshot columns to `sale_items` table if missing
$saleItemsCols = $pdo->query("DESCRIBE `sale_items`")->fetchAll(PDO::FETCH_COLUMN);

if (!in_array('product_name', $saleItemsCols)) {
    $pdo->exec("ALTER TABLE `sale_items` ADD COLUMN `product_name` VARCHAR(150) NULL AFTER `product_id`");
    echo "✓ Added `product_name` snapshot column to `sale_items` table.\n";
}

if (!in_array('product_sku', $saleItemsCols)) {
    $pdo->exec("ALTER TABLE `sale_items` ADD COLUMN `product_sku` VARCHAR(50) NULL AFTER `product_name`");
    echo "✓ Added `product_sku` snapshot column to `sale_items` table.\n";
}

// 3. Seed Phase 8 Granular Permissions
$newPermissions = [
    'sales.view'                => 'Ruhusa ya kutazama historia ya mauzo',
    'sales.create'              => 'Ruhusa ya kufanya mauzo kwenye mashine ya POS',
    'sales.discount'            => 'Ruhusa ya kutoa punguzo la kawaida la bei',
    'sales.price_override'      => 'Ruhusa ya kubadili bei ya mauzo',
    'sales.sell_below_minimum'  => 'Ruhusa ya kuuza chini ya bei ya chini (minimum selling price)',
    'sales.cancel'              => 'Ruhusa ya kufuta au kusitisha mauzo yaliyokamilika',
    'sales.view_profit'         => 'Ruhusa ya kuangalia faida na gharama (COGS/Profit) kwenye mauzo'
];

$stmtPermCheck = $pdo->prepare("SELECT id FROM `permissions` WHERE name = ?");
$stmtPermInsert = $pdo->prepare("INSERT INTO `permissions` (name, description, module) VALUES (?, ?, 'sales')");

foreach ($newPermissions as $pName => $pDesc) {
    $stmtPermCheck->execute([$pName]);
    if (!$stmtPermCheck->fetchColumn()) {
        $stmtPermInsert->execute([$pName, $pDesc]);
    }
}
echo "✓ Phase 8 Permissions seeded into `permissions` table.\n";

// 4. Map permissions to roles
$roles = $pdo->query("SELECT name, id FROM `roles`")->fetchAll(PDO::FETCH_KEY_PAIR);
$allPerms = $pdo->query("SELECT name, id FROM `permissions`")->fetchAll(PDO::FETCH_KEY_PAIR);

$stmtRolePerm = $pdo->prepare("
    INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
    VALUES (?, ?)
");

// Super Admin & Manager (All 7 permissions)
foreach (['super_admin', 'manager'] as $rName) {
    if (isset($roles[$rName])) {
        $rId = $roles[$rName];
        foreach (array_keys($newPermissions) as $pName) {
            if (isset($allPerms[$pName])) {
                $stmtRolePerm->execute([$rId, $allPerms[$pName]]);
            }
        }
    }
}

// Cashier (view, create/pos, discount)
$cashierPerms = ['sales.view', 'sales.create', 'sales.discount'];
if (isset($roles['cashier'])) {
    $cId = $roles['cashier'];
    foreach ($cashierPerms as $pName) {
        if (isset($allPerms[$pName])) {
            $stmtRolePerm->execute([$cId, $allPerms[$pName]]);
        }
    }
}

// Accountant (view, view_profit)
$accountantPerms = ['sales.view', 'sales.view_profit'];
if (isset($roles['accountant'])) {
    $aId = $roles['accountant'];
    foreach ($accountantPerms as $pName) {
        if (isset($allPerms[$pName])) {
            $stmtRolePerm->execute([$aId, $allPerms[$pName]]);
        }
    }
}

// Storekeeper (view)
$storekeeperPerms = ['sales.view'];
if (isset($roles['storekeeper'])) {
    $sId = $roles['storekeeper'];
    foreach ($storekeeperPerms as $pName) {
        if (isset($allPerms[$pName])) {
            $stmtRolePerm->execute([$sId, $allPerms[$pName]]);
        }
    }
}

echo "✓ Role permissions mapped successfully.\n";
echo "Phase 8 Migration Completed Successfully!\n";
