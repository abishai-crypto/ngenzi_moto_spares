<?php
/**
 * NGENZI MOTO SPARE PARTS
 * Phase 11 Database Migration
 * Adds columns and constraints for Returns & Refunds Management
 * Seeds 10 Granular Permissions, Role Mappings & System Settings
 */

require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/core/Database.php';

use App\Core\Database;

try {
    $pdo = Database::getConnection();
    echo "=======================================================\n";
    echo "Starting Phase 11 Database Migration (Returns & Refunds)...\n";
    echo "=======================================================\n";

    // Helper to check if column exists
    $hasColumn = function(string $table, string $column) use ($pdo): bool {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM information_schema.COLUMNS 
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?
        ");
        $stmt->execute([$table, $column]);
        return (int)$stmt->fetchColumn() > 0;
    };

    // Helper to check if index exists
    $hasIndex = function(string $table, string $index) use ($pdo): bool {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM information_schema.STATISTICS 
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?
        ");
        $stmt->execute([$table, $index]);
        return (int)$stmt->fetchColumn() > 0;
    };

    // 1. ALTER / ENHANCE `returns` TABLE
    echo "1. Checking & Enhancing `returns` table...\n";

    // Ensure `returns` table exists
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `returns` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `return_number` VARCHAR(50) NOT NULL UNIQUE,
            `sale_id` INT NOT NULL,
            `customer_id` INT NULL,
            `return_date` DATE NOT NULL,
            `reason` VARCHAR(255) NOT NULL,
            `refund_amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
            `status` ENUM('draft', 'requested', 'approved', 'received', 'refunded', 'completed', 'rejected', 'cancelled') NOT NULL DEFAULT 'draft',
            `created_by` INT NOT NULL,
            `approved_by` INT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (`sale_id`) REFERENCES `sales`(`id`) ON UPDATE CASCADE,
            FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`) ON UPDATE CASCADE,
            FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON UPDATE CASCADE,
            FOREIGN KEY (`approved_by`) REFERENCES `users`(`id`) ON UPDATE CASCADE
        ) ENGINE=InnoDB;
    ");

    // Modify status ENUM to include full lifecycle statuses
    $pdo->exec("
        ALTER TABLE `returns` 
        MODIFY COLUMN `status` ENUM('draft', 'requested', 'approved', 'received', 'refunded', 'completed', 'rejected', 'cancelled') NOT NULL DEFAULT 'draft'
    ");

    if (!$hasColumn('returns', 'return_type')) {
        $pdo->exec("ALTER TABLE `returns` ADD COLUMN `return_type` ENUM('CUSTOMER_RETURN', 'DAMAGED_ITEM', 'WRONG_ITEM', 'DEFECTIVE_ITEM', 'WARRANTY_RETURN', 'OTHER') NOT NULL DEFAULT 'CUSTOMER_RETURN' AFTER `customer_id`");
        echo " - Added `return_type` to `returns`\n";
    }

    if (!$hasColumn('returns', 'subtotal')) {
        $pdo->exec("ALTER TABLE `returns` ADD COLUMN `subtotal` DECIMAL(15,2) NOT NULL DEFAULT 0.00 AFTER `reason`");
        echo " - Added `subtotal` to `returns`\n";
    }

    if (!$hasColumn('returns', 'return_fee')) {
        $pdo->exec("ALTER TABLE `returns` ADD COLUMN `return_fee` DECIMAL(15,2) NOT NULL DEFAULT 0.00 AFTER `subtotal`");
        echo " - Added `return_fee` to `returns`\n";
    }

    if (!$hasColumn('returns', 'refund_method')) {
        $pdo->exec("ALTER TABLE `returns` ADD COLUMN `refund_method` VARCHAR(50) NULL AFTER `refund_amount`");
        echo " - Added `refund_method` to `returns`\n";
    }

    if (!$hasColumn('returns', 'refund_provider')) {
        $pdo->exec("ALTER TABLE `returns` ADD COLUMN `refund_provider` VARCHAR(50) NULL AFTER `refund_method`");
        echo " - Added `refund_provider` to `returns`\n";
    }

    if (!$hasColumn('returns', 'refund_reference_no')) {
        $pdo->exec("ALTER TABLE `returns` ADD COLUMN `refund_reference_no` VARCHAR(100) NULL AFTER `refund_provider`");
        echo " - Added `refund_reference_no` to `returns`\n";
    }

    if (!$hasColumn('returns', 'refund_status')) {
        $pdo->exec("ALTER TABLE `returns` ADD COLUMN `refund_status` ENUM('unrefunded', 'refunded', 'credit_issued', 'debt_reduced', 'exchange') NOT NULL DEFAULT 'unrefunded' AFTER `refund_reference_no`");
        echo " - Added `refund_status` to `returns`\n";
    }

    if (!$hasColumn('returns', 'notes')) {
        $pdo->exec("ALTER TABLE `returns` ADD COLUMN `notes` TEXT NULL AFTER `status`");
        echo " - Added `notes` to `returns`\n";
    }

    if (!$hasColumn('returns', 'approved_at')) {
        $pdo->exec("ALTER TABLE `returns` ADD COLUMN `approved_at` DATETIME NULL AFTER `approved_by`");
        echo " - Added `approved_at` to `returns`\n";
    }

    if (!$hasColumn('returns', 'received_by')) {
        $pdo->exec("ALTER TABLE `returns` ADD COLUMN `received_by` INT NULL AFTER `approved_at`");
        $pdo->exec("ALTER TABLE `returns` ADD CONSTRAINT `fk_returns_received_by` FOREIGN KEY (`received_by`) REFERENCES `users`(`id`) ON UPDATE CASCADE ON DELETE SET NULL");
        echo " - Added `received_by` to `returns`\n";
    }

    if (!$hasColumn('returns', 'received_at')) {
        $pdo->exec("ALTER TABLE `returns` ADD COLUMN `received_at` DATETIME NULL AFTER `received_by`");
        echo " - Added `received_at` to `returns`\n";
    }

    if (!$hasColumn('returns', 'refunded_by')) {
        $pdo->exec("ALTER TABLE `returns` ADD COLUMN `refunded_by` INT NULL AFTER `received_at`");
        $pdo->exec("ALTER TABLE `returns` ADD CONSTRAINT `fk_returns_refunded_by` FOREIGN KEY (`refunded_by`) REFERENCES `users`(`id`) ON UPDATE CASCADE ON DELETE SET NULL");
        echo " - Added `refunded_by` to `returns`\n";
    }

    if (!$hasColumn('returns', 'refunded_at')) {
        $pdo->exec("ALTER TABLE `returns` ADD COLUMN `refunded_at` DATETIME NULL AFTER `refunded_by`");
        echo " - Added `refunded_at` to `returns`\n";
    }

    if (!$hasColumn('returns', 'cancelled_by')) {
        $pdo->exec("ALTER TABLE `returns` ADD COLUMN `cancelled_by` INT NULL AFTER `refunded_at`");
        $pdo->exec("ALTER TABLE `returns` ADD CONSTRAINT `fk_returns_cancelled_by` FOREIGN KEY (`cancelled_by`) REFERENCES `users`(`id`) ON UPDATE CASCADE ON DELETE SET NULL");
        echo " - Added `cancelled_by` to `returns`\n";
    }

    if (!$hasColumn('returns', 'cancelled_at')) {
        $pdo->exec("ALTER TABLE `returns` ADD COLUMN `cancelled_at` DATETIME NULL AFTER `cancelled_by`");
        echo " - Added `cancelled_at` to `returns`\n";
    }

    if (!$hasColumn('returns', 'cancel_reason')) {
        $pdo->exec("ALTER TABLE `returns` ADD COLUMN `cancel_reason` VARCHAR(255) NULL AFTER `cancelled_at`");
        echo " - Added `cancel_reason` to `returns`\n";
    }

    if (!$hasColumn('returns', 'is_restocked')) {
        $pdo->exec("ALTER TABLE `returns` ADD COLUMN `is_restocked` TINYINT(1) NOT NULL DEFAULT 0 AFTER `cancel_reason`");
        echo " - Added `is_restocked` to `returns`\n";
    }

    if (!$hasColumn('returns', 'exchange_sale_id')) {
        $pdo->exec("ALTER TABLE `returns` ADD COLUMN `exchange_sale_id` INT NULL AFTER `is_restocked`");
        $pdo->exec("ALTER TABLE `returns` ADD CONSTRAINT `fk_returns_exchange_sale` FOREIGN KEY (`exchange_sale_id`) REFERENCES `sales`(`id`) ON UPDATE CASCADE ON DELETE SET NULL");
        echo " - Added `exchange_sale_id` to `returns`\n";
    }

    // Ensure Indexes on `returns`
    if (!$hasIndex('returns', 'idx_returns_sale')) {
        $pdo->exec("ALTER TABLE `returns` ADD INDEX `idx_returns_sale` (`sale_id`)");
        echo " - Added index `idx_returns_sale`\n";
    }
    if (!$hasIndex('returns', 'idx_returns_customer')) {
        $pdo->exec("ALTER TABLE `returns` ADD INDEX `idx_returns_customer` (`customer_id`)");
        echo " - Added index `idx_returns_customer`\n";
    }
    if (!$hasIndex('returns', 'idx_returns_status')) {
        $pdo->exec("ALTER TABLE `returns` ADD INDEX `idx_returns_status` (`status`)");
        echo " - Added index `idx_returns_status`\n";
    }
    if (!$hasIndex('returns', 'idx_returns_date')) {
        $pdo->exec("ALTER TABLE `returns` ADD INDEX `idx_returns_date` (`return_date`)");
        echo " - Added index `idx_returns_date`\n";
    }


    // 2. ALTER / ENHANCE `return_items` TABLE
    echo "2. Checking & Enhancing `return_items` table...\n";

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `return_items` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `return_id` INT NOT NULL,
            `sale_item_id` INT NULL,
            `product_id` INT NOT NULL,
            `quantity` INT NOT NULL,
            `unit_refund_price` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
            `condition_status` ENUM('resellable', 'damaged', 'defective', 'not_resellable') NOT NULL DEFAULT 'resellable',
            `restockable` TINYINT(1) DEFAULT 1,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`return_id`) REFERENCES `returns`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`sale_item_id`) REFERENCES `sale_items`(`id`) ON DELETE SET NULL,
            FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON UPDATE CASCADE
        ) ENGINE=InnoDB;
    ");

    $pdo->exec("
        ALTER TABLE `return_items` 
        MODIFY COLUMN `condition_status` ENUM('resellable', 'damaged', 'defective', 'not_resellable') NOT NULL DEFAULT 'resellable'
    ");

    if (!$hasColumn('return_items', 'product_name')) {
        $pdo->exec("ALTER TABLE `return_items` ADD COLUMN `product_name` VARCHAR(150) NULL AFTER `product_id`");
        echo " - Added `product_name` snapshot to `return_items`\n";
    }

    if (!$hasColumn('return_items', 'product_sku')) {
        $pdo->exec("ALTER TABLE `return_items` ADD COLUMN `product_sku` VARCHAR(50) NULL AFTER `product_name`");
        echo " - Added `product_sku` snapshot to `return_items`\n";
    }

    if (!$hasColumn('return_items', 'original_quantity')) {
        $pdo->exec("ALTER TABLE `return_items` ADD COLUMN `original_quantity` INT NOT NULL DEFAULT 1 AFTER `product_sku`");
        echo " - Added `original_quantity` to `return_items`\n";
    }

    if (!$hasColumn('return_items', 'unit_selling_price')) {
        $pdo->exec("ALTER TABLE `return_items` ADD COLUMN `unit_selling_price` DECIMAL(15,2) NOT NULL DEFAULT 0.00 AFTER `quantity`");
        echo " - Added `unit_selling_price` to `return_items`\n";
    }

    if (!$hasColumn('return_items', 'unit_discount')) {
        $pdo->exec("ALTER TABLE `return_items` ADD COLUMN `unit_discount` DECIMAL(15,2) NOT NULL DEFAULT 0.00 AFTER `unit_selling_price`");
        echo " - Added `unit_discount` to `return_items`\n";
    }

    if (!$hasColumn('return_items', 'unit_cost')) {
        $pdo->exec("ALTER TABLE `return_items` ADD COLUMN `unit_cost` DECIMAL(15,2) NOT NULL DEFAULT 0.00 AFTER `unit_refund_price`");
        echo " - Added `unit_cost` to `return_items`\n";
    }

    if (!$hasColumn('return_items', 'subtotal')) {
        $pdo->exec("ALTER TABLE `return_items` ADD COLUMN `subtotal` DECIMAL(15,2) NOT NULL DEFAULT 0.00 AFTER `unit_cost`");
        echo " - Added `subtotal` to `return_items`\n";
    }

    if (!$hasColumn('return_items', 'total_cogs')) {
        $pdo->exec("ALTER TABLE `return_items` ADD COLUMN `total_cogs` DECIMAL(15,2) NOT NULL DEFAULT 0.00 AFTER `subtotal`");
        echo " - Added `total_cogs` to `return_items`\n";
    }

    if (!$hasColumn('return_items', 'return_reason')) {
        $pdo->exec("ALTER TABLE `return_items` ADD COLUMN `return_reason` VARCHAR(255) NULL AFTER `condition_status`");
        echo " - Added `return_reason` to `return_items`\n";
    }

    if (!$hasColumn('return_items', 'is_restocked')) {
        $pdo->exec("ALTER TABLE `return_items` ADD COLUMN `is_restocked` TINYINT(1) NOT NULL DEFAULT 0 AFTER `restockable`");
        echo " - Added `is_restocked` to `return_items`\n";
    }

    if (!$hasColumn('return_items', 'restocked_at')) {
        $pdo->exec("ALTER TABLE `return_items` ADD COLUMN `restocked_at` DATETIME NULL AFTER `is_restocked`");
        echo " - Added `restocked_at` to `return_items`\n";
    }

    if (!$hasColumn('return_items', 'notes')) {
        $pdo->exec("ALTER TABLE `return_items` ADD COLUMN `notes` VARCHAR(255) NULL AFTER `restocked_at`");
        echo " - Added `notes` to `return_items`\n";
    }

    // Ensure Indexes on `return_items`
    if (!$hasIndex('return_items', 'idx_ri_product')) {
        $pdo->exec("ALTER TABLE `return_items` ADD INDEX `idx_ri_product` (`product_id`)");
        echo " - Added index `idx_ri_product`\n";
    }
    if (!$hasIndex('return_items', 'idx_ri_return')) {
        $pdo->exec("ALTER TABLE `return_items` ADD INDEX `idx_ri_return` (`return_id`)");
        echo " - Added index `idx_ri_return`\n";
    }


    // 3. SEED 10 GRANULAR PERMISSIONS FOR PHASE 11
    echo "3. Seeding Phase 11 Granular Permissions...\n";
    $permissions = [
        ['name' => 'returns.view',           'module' => 'returns', 'description' => 'Kutazama orodha na historia ya marejesho'],
        ['name' => 'returns.create',         'module' => 'returns', 'description' => 'Kuanzisha na kuunda ombi jipya la marejesho'],
        ['name' => 'returns.request',        'module' => 'returns', 'description' => 'Kuwasilisha marejesho ya wateja kwa ajili ya idhini'],
        ['name' => 'returns.approve',        'module' => 'returns', 'description' => 'Kuidhinisha au kukataa marejesho ya bidhaa (Approve/Reject)'],
        ['name' => 'returns.receive',        'module' => 'returns', 'description' => 'Kupokea na kurejesha vipuri stoo (Storekeeper Restock)'],
        ['name' => 'returns.refund',         'module' => 'returns', 'description' => 'Kutoa pesa au marejesho ya fedha/deni kwa mteja (Issue Refund)'],
        ['name' => 'returns.cancel',         'module' => 'returns', 'description' => 'Kughairi marejesho (Cancel Return)'],
        ['name' => 'returns.report',         'module' => 'returns', 'description' => 'Kutazama ripoti na uchambuzi wa marejesho (Return Rate)'],
        ['name' => 'returns.view_financial', 'module' => 'returns', 'description' => 'Kutazama athari za kifedha na faida ya marejesho'],
        ['name' => 'returns.exchange',       'module' => 'returns', 'description' => 'Kufanya mabadilishano ya bidhaa zilizorudishwa (Exchange)']
    ];

    $permStmt = $pdo->prepare("
        INSERT INTO `permissions` (`name`, `module`, `description`)
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE `description` = VALUES(`description`), `module` = VALUES(`module`)
    ");

    foreach ($permissions as $p) {
        $permStmt->execute([$p['name'], $p['module'], $p['description']]);
    }
    echo " - Seeded 10 granular permissions successfully.\n";


    // 4. MAP PERMISSIONS TO ROLES
    echo "4. Mapping Phase 11 permissions to roles...\n";
    $rolesStmt = $pdo->query("SELECT id, name FROM `roles`");
    $roleMap = [];
    while ($r = $rolesStmt->fetch(PDO::FETCH_ASSOC)) {
        $roleMap[$r['name']] = (int)$r['id'];
    }

    $allPermIds = $pdo->query("SELECT id, name FROM `permissions`")->fetchAll(PDO::FETCH_KEY_PAIR);

    $rolePermMappings = [
        'super_admin' => array_keys($allPermIds), // All permissions
        'manager'     => [
            'returns.view', 'returns.create', 'returns.request', 'returns.approve',
            'returns.receive', 'returns.refund', 'returns.cancel', 'returns.report',
            'returns.view_financial', 'returns.exchange'
        ],
        'accountant'  => [
            'returns.view', 'returns.refund', 'returns.report', 'returns.view_financial'
        ],
        'cashier'     => [
            'returns.view', 'returns.create', 'returns.request', 'returns.exchange'
            // Explicitly NO returns.approve, returns.receive, returns.cancel, returns.view_financial
        ],
        'storekeeper' => [
            'returns.view', 'returns.receive'
        ]
    ];

    $grantStmt = $pdo->prepare("INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`) VALUES (?, ?)");

    foreach ($rolePermMappings as $roleName => $permNames) {
        if (!isset($roleMap[$roleName])) continue;
        $roleId = $roleMap[$roleName];

        foreach ($permNames as $pName) {
            $permId = array_search($pName, $allPermIds);
            if ($permId !== false) {
                $grantStmt->execute([$roleId, $permId]);
            }
        }
    }
    echo " - Phase 11 role permissions mapped successfully.\n";


    // 5. SEED SYSTEM SETTINGS FOR PHASE 11
    echo "5. Seeding Phase 11 system settings...\n";
    $settings = [
        ['return_prefix', 'RET-', 'Kianzio cha nambari ya marejesho ya bidhaa (Return Number Prefix)'],
        ['refund_receipt_prefix', 'REF-', 'Kianzio cha nambari ya risiti ya marejesho ya fedha (Refund Receipt Prefix)'],
        ['return_reasons', 'CUSTOMER_RETURN,DAMAGED_ITEM,WRONG_ITEM,DEFECTIVE_ITEM,WARRANTY_RETURN,OTHER', 'Sababu rasmi za marejesho ya bidhaa kwenye mfumo'],
        ['default_restocking_fee_percent', '0.00', 'Asilimia ya tozo ya kurudisha mzigo (Default Restocking Fee %)'],
        ['allow_cashier_direct_return', '0', 'Ruhusu Cashier kukamilisha marejesho moja kwa moja bila idhini ya Meneja (0 = Hapana, 1 = Ndiyo)']
    ];

    $setStmt = $pdo->prepare("
        INSERT INTO `settings` (`setting_key`, `setting_value`, `description`, `updated_by`)
        VALUES (?, ?, ?, 1)
        ON DUPLICATE KEY UPDATE `description` = VALUES(`description`)
    ");

    foreach ($settings as $s) {
        $setStmt->execute([$s[0], $s[1], $s[2]]);
    }
    echo " - Phase 11 settings seeded successfully.\n";

    echo "=======================================================\n";
    echo "PHASE 11 DATABASE MIGRATION COMPLETED SUCCESSFULLY!\n";
    echo "=======================================================\n";

} catch (Exception $e) {
    echo "MIGRATION ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
