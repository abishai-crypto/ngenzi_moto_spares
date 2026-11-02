<?php
/**
 * NGENZI MOTO SPARE PARTS
 * Phase 5 Database Migration Script
 * Enhances inventory_transactions, stock_counts, purchases, and seeds permissions.
 */

require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/core/Database.php';

$pdo = \App\Core\Database::getConnection();

echo "Starting Phase 5 Database Migration...\n";

// 1. Update inventory_transactions transaction_type enum
try {
    $pdo->exec("
        ALTER TABLE `inventory_transactions` 
        MODIFY COLUMN `transaction_type` ENUM(
            'opening_stock', 'purchase', 'sale', 'return', 
            'sale_return', 'purchase_return', 'damage', 'loss', 
            'adjustment', 'adjustment_in', 'adjustment_out', 
            'stock_count', 'stock_count_adjustment'
        ) NOT NULL
    ");
    echo "[OK] Updated inventory_transactions.transaction_type enum.\n";
} catch (Exception $e) {
    echo "[WARN] Error updating inventory_transactions: " . $e->getMessage() . "\n";
}

// 2. Check and add count_no and update status enum on stock_counts
try {
    $cols = $pdo->query("SHOW COLUMNS FROM `stock_counts` LIKE 'count_no'")->fetchAll();
    if (empty($cols)) {
        $pdo->exec("ALTER TABLE `stock_counts` ADD COLUMN `count_no` VARCHAR(50) NULL UNIQUE AFTER `id`");
        echo "[OK] Added count_no to stock_counts.\n";
    } else {
        echo "[INFO] count_no already exists in stock_counts.\n";
    }

    $pdo->exec("
        ALTER TABLE `stock_counts` 
        MODIFY COLUMN `status` ENUM(
            'draft', 'in_progress', 'submitted', 'pending', 
            'approved', 'rejected', 'cancelled'
        ) DEFAULT 'draft'
    ");
    echo "[OK] Updated stock_counts.status enum.\n";
} catch (Exception $e) {
    echo "[WARN] Error updating stock_counts: " . $e->getMessage() . "\n";
}

// 3. Check and add status column to purchases
try {
    $cols = $pdo->query("SHOW COLUMNS FROM `purchases` LIKE 'status'")->fetchAll();
    if (empty($cols)) {
        $pdo->exec("ALTER TABLE `purchases` ADD COLUMN `status` ENUM('draft', 'ordered', 'received', 'cancelled') DEFAULT 'received' AFTER `balance_due`");
        echo "[OK] Added status column to purchases.\n";
    } else {
        echo "[INFO] status column already exists in purchases.\n";
    }
} catch (Exception $e) {
    echo "[WARN] Error updating purchases: " . $e->getMessage() . "\n";
}

// 4. Seed granular inventory permissions
$permissions = [
    [
        'name' => 'inventory.view',
        'module' => 'inventory',
        'description' => 'Kuangalia orodha na idadi ya bidhaa stoo'
    ],
    [
        'name' => 'inventory.adjust',
        'module' => 'inventory',
        'description' => 'Kurekebisha idadi ya stoo (Adjustment In/Out, Damage, Loss)'
    ],
    [
        'name' => 'inventory.count',
        'module' => 'inventory',
        'description' => 'Kufanya ukaguzi wa kimwili na kuhesabu bidhaa stoo'
    ],
    [
        'name' => 'inventory.history',
        'module' => 'inventory',
        'description' => 'Kuangalia historia ya mienendo yote ya stoo'
    ],
    [
        'name' => 'inventory.valuation',
        'module' => 'inventory',
        'description' => 'Kuangalia ripoti ya thamani ya kifedha ya mali iliyopo stoo'
    ]
];

$stmtCheck = $pdo->prepare("SELECT id FROM `permissions` WHERE `name` = ?");
$stmtInsert = $pdo->prepare("INSERT INTO `permissions` (`name`, `module`, `description`) VALUES (?, ?, ?)");

foreach ($permissions as $p) {
    $stmtCheck->execute([$p['name']]);
    if (!$stmtCheck->fetch()) {
        $stmtInsert->execute([$p['name'], $p['module'], $p['description']]);
        echo "[OK] Inserted permission: {$p['name']}\n";
    } else {
        echo "[INFO] Permission {$p['name']} already exists.\n";
    }
}

// 5. Map permissions to roles
// Get role IDs
$roles = [];
$rStmt = $pdo->query("SELECT id, name FROM `roles`");
while ($r = $rStmt->fetch(PDO::FETCH_ASSOC)) {
    $roles[$r['name']] = $r['id'];
}

// Map perms
$rolePermMap = [
    'super_admin' => ['inventory.view', 'inventory.adjust', 'inventory.count', 'inventory.history', 'inventory.valuation'],
    'manager'     => ['inventory.view', 'inventory.adjust', 'inventory.count', 'inventory.history', 'inventory.valuation'],
    'storekeeper' => ['inventory.view', 'inventory.adjust', 'inventory.count', 'inventory.history'],
    'accountant'  => ['inventory.view', 'inventory.history', 'inventory.valuation'],
    'cashier'     => ['inventory.view']
];

$stmtFindPerm = $pdo->prepare("SELECT id FROM `permissions` WHERE `name` = ?");
$stmtCheckRP = $pdo->prepare("SELECT id FROM `role_permissions` WHERE `role_id` = ? AND `permission_id` = ?");
$stmtInsertRP = $pdo->prepare("INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (?, ?)");

foreach ($rolePermMap as $roleName => $perms) {
    if (!isset($roles[$roleName])) continue;
    $roleId = $roles[$roleName];

    foreach ($perms as $permName) {
        $stmtFindPerm->execute([$permName]);
        $permId = $stmtFindPerm->fetchColumn();
        if ($permId) {
            $stmtCheckRP->execute([$roleId, $permId]);
            if (!$stmtCheckRP->fetch()) {
                $stmtInsertRP->execute([$roleId, $permId]);
                echo "[OK] Linked {$permName} to role {$roleName}\n";
            }
        }
    }
}

echo "Migration finished successfully!\n";
