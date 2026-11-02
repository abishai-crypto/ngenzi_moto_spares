<?php
/**
 * NGENZI MOTO SPARE PARTS
 * Phase 9 Database Migration
 * Adds columns for Payments, Credit & Debt Management
 * Seeds 12 Granular Permissions & System Settings
 */

require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/core/Database.php';

use App\Core\Database;

try {
    $pdo = Database::getConnection();
    echo "Starting Phase 9 Database Migration...\n";

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

    // 1. ALTER `sales` table
    echo "1. Checking `sales` table columns...\n";
    if (!$hasColumn('sales', 'due_date')) {
        $pdo->exec("ALTER TABLE `sales` ADD COLUMN `due_date` DATE NULL AFTER `sale_date`");
        echo " - Added `due_date` to `sales`\n";
    }
    if (!$hasColumn('sales', 'payment_terms')) {
        $pdo->exec("ALTER TABLE `sales` ADD COLUMN `payment_terms` VARCHAR(50) NULL DEFAULT 'Cash only' AFTER `due_date`");
        echo " - Added `payment_terms` to `sales`\n";
    }
    if (!$hasColumn('sales', 'credit_override_by')) {
        $pdo->exec("ALTER TABLE `sales` ADD COLUMN `credit_override_by` INT NULL AFTER `notes`");
        $pdo->exec("ALTER TABLE `sales` ADD CONSTRAINT `fk_sales_credit_override` FOREIGN KEY (`credit_override_by`) REFERENCES `users`(`id`) ON UPDATE CASCADE ON DELETE SET NULL");
        echo " - Added `credit_override_by` to `sales`\n";
    }
    if (!$hasColumn('sales', 'credit_override_reason')) {
        $pdo->exec("ALTER TABLE `sales` ADD COLUMN `credit_override_reason` VARCHAR(255) NULL AFTER `credit_override_by`");
        echo " - Added `credit_override_reason` to `sales`\n";
    }

    // 2. ALTER `sale_payments` table
    echo "2. Checking `sale_payments` table columns...\n";
    if (!$hasColumn('sale_payments', 'payment_no')) {
        $pdo->exec("ALTER TABLE `sale_payments` ADD COLUMN `payment_no` VARCHAR(50) NULL AFTER `id`");
        echo " - Added `payment_no` to `sale_payments`\n";
    }
    if (!$hasIndex('sale_payments', 'uk_sale_payment_no')) {
        $pdo->exec("ALTER TABLE `sale_payments` ADD UNIQUE KEY `uk_sale_payment_no` (`payment_no`)");
        echo " - Added unique key on `payment_no`\n";
    }
    if (!$hasColumn('sale_payments', 'customer_id')) {
        $pdo->exec("ALTER TABLE `sale_payments` ADD COLUMN `customer_id` INT NULL AFTER `sale_id`");
        $pdo->exec("ALTER TABLE `sale_payments` ADD CONSTRAINT `fk_sale_payments_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`) ON UPDATE CASCADE ON DELETE SET NULL");
        echo " - Added `customer_id` to `sale_payments`\n";
    }
    if (!$hasColumn('sale_payments', 'provider')) {
        $pdo->exec("ALTER TABLE `sale_payments` ADD COLUMN `provider` VARCHAR(50) NULL AFTER `payment_method`");
        echo " - Added `provider` to `sale_payments`\n";
    }
    if (!$hasColumn('sale_payments', 'status')) {
        $pdo->exec("ALTER TABLE `sale_payments` ADD COLUMN `status` ENUM('completed', 'reversed') NOT NULL DEFAULT 'completed' AFTER `notes`");
        echo " - Added `status` to `sale_payments`\n";
    }
    if (!$hasColumn('sale_payments', 'reversal_reason')) {
        $pdo->exec("ALTER TABLE `sale_payments` ADD COLUMN `reversal_reason` VARCHAR(255) NULL AFTER `status`");
        echo " - Added `reversal_reason` to `sale_payments`\n";
    }
    if (!$hasColumn('sale_payments', 'reversed_by')) {
        $pdo->exec("ALTER TABLE `sale_payments` ADD COLUMN `reversed_by` INT NULL AFTER `reversal_reason`");
        $pdo->exec("ALTER TABLE `sale_payments` ADD CONSTRAINT `fk_sale_payments_reversed_by` FOREIGN KEY (`reversed_by`) REFERENCES `users`(`id`) ON UPDATE CASCADE ON DELETE SET NULL");
        echo " - Added `reversed_by` to `sale_payments`\n";
    }
    if (!$hasColumn('sale_payments', 'reversed_at')) {
        $pdo->exec("ALTER TABLE `sale_payments` ADD COLUMN `reversed_at` DATETIME NULL AFTER `reversed_by`");
        echo " - Added `reversed_at` to `sale_payments`\n";
    }
    if (!$hasColumn('sale_payments', 'parent_payment_id')) {
        $pdo->exec("ALTER TABLE `sale_payments` ADD COLUMN `parent_payment_id` INT NULL AFTER `reversed_at`");
        $pdo->exec("ALTER TABLE `sale_payments` ADD CONSTRAINT `fk_sale_payments_parent` FOREIGN KEY (`parent_payment_id`) REFERENCES `sale_payments`(`id`) ON UPDATE CASCADE ON DELETE SET NULL");
        echo " - Added `parent_payment_id` to `sale_payments`\n";
    }

    // Populate missing payment numbers in existing sale_payments
    $missingNos = $pdo->query("SELECT id FROM `sale_payments` WHERE payment_no IS NULL OR payment_no = '' ORDER BY id ASC")->fetchAll(PDO::FETCH_COLUMN);
    if (!empty($missingNos)) {
        $uStmt = $pdo->prepare("UPDATE `sale_payments` SET payment_no = ? WHERE id = ?");
        foreach ($missingNos as $spId) {
            $uStmt->execute(['PAY-' . str_pad((string)$spId, 6, '0', STR_PAD_LEFT), $spId]);
        }
        echo " - Backfilled " . count($missingNos) . " existing sale_payments with PAY-XXXXXX references.\n";
    }

    // Populate customer_id from sales table if missing in sale_payments
    $pdo->exec("
        UPDATE `sale_payments` sp
        JOIN `sales` s ON sp.sale_id = s.id
        SET sp.customer_id = s.customer_id
        WHERE sp.customer_id IS NULL AND s.customer_id IS NOT NULL
    ");

    // 3. ALTER `customers` table
    echo "3. Checking `customers` table columns...\n";
    if (!$hasColumn('customers', 'credit_allowed')) {
        $pdo->exec("ALTER TABLE `customers` ADD COLUMN `credit_allowed` TINYINT(1) NOT NULL DEFAULT 1 AFTER `credit_limit`");
        echo " - Added `credit_allowed` to `customers`\n";
    }
    if (!$hasColumn('customers', 'payment_terms')) {
        $pdo->exec("ALTER TABLE `customers` ADD COLUMN `payment_terms` VARCHAR(50) NOT NULL DEFAULT '30 days' AFTER `credit_allowed`");
        echo " - Added `payment_terms` to `customers`\n";
    }
    if (!$hasColumn('customers', 'credit_status')) {
        $pdo->exec("ALTER TABLE `customers` ADD COLUMN `credit_status` ENUM('active', 'warning', 'blocked') NOT NULL DEFAULT 'active' AFTER `payment_terms`");
        echo " - Added `credit_status` to `customers`\n";
    }

    // Set retail walk-in customer (id 1) credit_allowed = 0 by default
    $pdo->exec("UPDATE `customers` SET credit_allowed = 0 WHERE customer_type = 'retail' AND id = 1");

    // 4. ALTER `customer_payments` table
    echo "4. Checking `customer_payments` table columns...\n";
    if (!$hasColumn('customer_payments', 'payment_no')) {
        $pdo->exec("ALTER TABLE `customer_payments` ADD COLUMN `payment_no` VARCHAR(50) NULL AFTER `id`");
        echo " - Added `payment_no` to `customer_payments`\n";
    }
    if (!$hasIndex('customer_payments', 'uk_customer_payment_no')) {
        $pdo->exec("ALTER TABLE `customer_payments` ADD UNIQUE KEY `uk_customer_payment_no` (`payment_no`)");
        echo " - Added unique key on `customer_payments.payment_no`\n";
    }
    if (!$hasColumn('customer_payments', 'provider')) {
        $pdo->exec("ALTER TABLE `customer_payments` ADD COLUMN `provider` VARCHAR(50) NULL AFTER `payment_method`");
        echo " - Added `provider` to `customer_payments`\n";
    }
    if (!$hasColumn('customer_payments', 'status')) {
        $pdo->exec("ALTER TABLE `customer_payments` ADD COLUMN `status` ENUM('completed', 'reversed') NOT NULL DEFAULT 'completed' AFTER `notes`");
        echo " - Added `status` to `customer_payments`\n";
    }
    if (!$hasColumn('customer_payments', 'reversal_reason')) {
        $pdo->exec("ALTER TABLE `customer_payments` ADD COLUMN `reversal_reason` VARCHAR(255) NULL AFTER `status`");
        echo " - Added `reversal_reason` to `customer_payments`\n";
    }
    if (!$hasColumn('customer_payments', 'reversed_by')) {
        $pdo->exec("ALTER TABLE `customer_payments` ADD COLUMN `reversed_by` INT NULL AFTER `reversal_reason`");
        $pdo->exec("ALTER TABLE `customer_payments` ADD CONSTRAINT `fk_cust_payments_reversed_by` FOREIGN KEY (`reversed_by`) REFERENCES `users`(`id`) ON UPDATE CASCADE ON DELETE SET NULL");
        echo " - Added `reversed_by` to `customer_payments`\n";
    }
    if (!$hasColumn('customer_payments', 'reversed_at')) {
        $pdo->exec("ALTER TABLE `customer_payments` ADD COLUMN `reversed_at` DATETIME NULL AFTER `reversed_by`");
        echo " - Added `reversed_at` to `customer_payments`\n";
    }

    // 5. Seed 12 Granular Permissions for Phase 9
    echo "5. Seeding Phase 9 permissions...\n";
    $permissions = [
        ['name' => 'payments.view',            'module' => 'payments', 'description' => 'Kutazama orodha na historia ya malipo'],
        ['name' => 'payments.create',          'module' => 'payments', 'description' => 'Kurekodi malipo mapya ya ankara au akaunti'],
        ['name' => 'payments.reverse',         'module' => 'payments', 'description' => 'Kughairi na kusahihisha malipo (Payment Reversal)'],
        ['name' => 'payments.print',           'module' => 'payments', 'description' => 'Kuchapisha risiti za malipo (Thermal / A4)'],
        ['name' => 'credit.view',              'module' => 'credit',   'description' => 'Kutazama taarifa za mikopo ya wateja'],
        ['name' => 'credit.create',            'module' => 'credit',   'description' => 'Kuidhinisha mauzo ya mkopo (Credit Sales)'],
        ['name' => 'credit.override_limit',    'module' => 'credit',   'description' => 'Kuvuka ukomo wa mkopo wa mteja (Credit Limit Override)'],
        ['name' => 'credit.edit_terms',        'module' => 'credit',   'description' => 'Kubadilisha vigezo na ukomo wa mkopo wa mteja'],
        ['name' => 'debt.view',                'module' => 'debt',     'description' => 'Kutazama dashibodi ya madeni ya wateja'],
        ['name' => 'debt.collect',             'module' => 'debt',     'description' => 'Kukusanya madeni na kugawa malipo kwenye ankara (Debt Collection)'],
        ['name' => 'debt.reports',             'module' => 'debt',     'description' => 'Kutazama ripoti ya uzeekaji wa madeni (Debt Aging Report)'],
        ['name' => 'customer_statement.view',  'module' => 'debt',     'description' => 'Kutazama na kuchapisha taarifa ya mteja (Customer Statement)']
    ];

    $permStmt = $pdo->prepare("
        INSERT INTO `permissions` (`name`, `module`, `description`)
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE `description` = VALUES(`description`), `module` = VALUES(`module`)
    ");

    foreach ($permissions as $p) {
        $permStmt->execute([$p['name'], $p['module'], $p['description']]);
    }
    echo " - Seeded 12 granular permissions successfully.\n";

    // 6. Map permissions to roles
    echo "6. Mapping permissions to roles...\n";
    $rolesStmt = $pdo->query("SELECT id, name FROM `roles`");
    $roleMap = [];
    while ($r = $rolesStmt->fetch(PDO::FETCH_ASSOC)) {
        $roleMap[$r['name']] = (int)$r['id'];
    }

    $allPermIds = $pdo->query("SELECT id, name FROM `permissions`")->fetchAll(PDO::FETCH_KEY_PAIR);

    $rolePermMappings = [
        'super_admin' => array_keys($allPermIds), // All permissions
        'manager'     => [
            'payments.view', 'payments.create', 'payments.reverse', 'payments.print',
            'credit.view', 'credit.create', 'credit.override_limit', 'credit.edit_terms',
            'debt.view', 'debt.collect', 'debt.reports', 'customer_statement.view'
        ],
        'accountant'  => [
            'payments.view', 'payments.create', 'payments.reverse', 'payments.print',
            'credit.view', 'credit.create', 'credit.override_limit', 'credit.edit_terms',
            'debt.view', 'debt.collect', 'debt.reports', 'customer_statement.view'
        ],
        'cashier'     => [
            'payments.view', 'payments.create', 'payments.print',
            'credit.view', 'debt.view', 'debt.collect', 'customer_statement.view'
            // No payments.reverse, credit.override_limit, credit.edit_terms, debt.reports
        ],
        'storekeeper' => []
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
    echo " - Role permissions successfully mapped.\n";

    // 7. Seed System Settings for Phase 9
    echo "7. Seeding Phase 9 system settings...\n";
    $settings = [
        ['payment_methods', 'Cash,Mobile Money,Bank,Card,Credit', 'Njia za malipo zinazokubalika kwenye mfumo'],
        ['mobile_money_providers', 'M-Pesa,Tigo Pesa,Airtel Money,Halopesa', 'Watoa huduma wa fedha kwa njia ya simu'],
        ['bank_accounts', 'CRDB Bank,NMB Bank,NBC Bank', 'Benki rasmi za kupokea malipo'],
        ['default_credit_period_days', '30', 'Siku za kawaida za kulipa deni la mkopo (Default credit terms)'],
        ['block_sales_on_overdue', '0', 'Zuia mauzo mapya kwa mteja mwenye deni lililopitiliza (0 = Onyo, 1 = Zuia Moja kwa Moja)'],
        ['payment_receipt_prefix', 'PAY-', 'Kianzio cha nambari ya risiti ya malipo']
    ];

    $setStmt = $pdo->prepare("
        INSERT INTO `settings` (`setting_key`, `setting_value`, `description`, `updated_by`)
        VALUES (?, ?, ?, 1)
        ON DUPLICATE KEY UPDATE `description` = VALUES(`description`)
    ");

    foreach ($settings as $s) {
        $setStmt->execute([$s[0], $s[1], $s[2]]);
    }
    echo " - Phase 9 settings seeded successfully.\n";

    echo "=======================================================\n";
    echo "PHASE 9 DATABASE MIGRATION COMPLETED SUCCESSFULLY!\n";
    echo "=======================================================\n";

} catch (Exception $e) {
    echo "MIGRATION ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
