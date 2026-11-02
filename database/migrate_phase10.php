<?php
/**
 * NGENZI MOTO SPARE PARTS
 * Phase 10 Database Migration
 * Expenses, Profit & Financial Management Schema, Categories, RBAC Permissions & Settings
 */

require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/core/Database.php';

use App\Core\Database;

try {
    $pdo = Database::getConnection();
    echo "=======================================================\n";
    echo "Starting Phase 10 Database Migration (Expenses & Profit)...\n";
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

    // 1. EXPENSE CATEGORIES TABLE
    echo "1. Checking & Enhancing `expense_categories` table...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `expense_categories` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(100) NOT NULL UNIQUE,
            `code` VARCHAR(20) NULL UNIQUE,
            `description` VARCHAR(255) NULL,
            `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB;
    ");

    if (!$hasColumn('expense_categories', 'code')) {
        $pdo->exec("ALTER TABLE `expense_categories` ADD COLUMN `code` VARCHAR(20) NULL UNIQUE AFTER `name`");
        echo " - Added `code` to `expense_categories`\n";
    }

    // Seed Standard Categories for Motorcycle/Bajaj Spare Parts Business
    $standardCategories = [
        ['Rent (Kodi ya Pango / Fremu)', 'RENT', 'Kodi ya fremu ya duka na stoo ya vipuri'],
        ['Electricity / LUKU (Umeme)', 'ELEC', 'Gharama za bili ya umeme wa duka na stoo'],
        ['Water (Maji)', 'WATER', 'Bili za maji ya ofisi na duka'],
        ['Internet & Communication (Mtandao & Mawasiliano)', 'NET', 'Vifurushi vya simu, intaneti ya ofisi na mawasiliano'],
        ['Transport & Logistics (Usafirishaji)', 'TRANS', 'Uchukuzi wa vipuri toka bandarini/kwa supplier hadi dukani na kusafirisha kwa wateja'],
        ['Salaries & Wages (Mishahara & Vibaruwa)', 'SALARY', 'Mishahara ya wafanyakazi, posho na vibaruwa wa kushusha mzigo'],
        ['Repairs & Maintenance (Matengenezo)', 'REPAIR', 'Ukarabati wa samani, taa, vifaa vya ofisi na mfumo'],
        ['Packaging Materials (Vifungashio)', 'PACK', 'Mifuko ya nailoni, maboksi, tepu na lebo za vipuri'],
        ['Marketing & Advertising (Masoko & Tangazo)', 'MKT', 'Mabango, vipeperushi, matangazo ya mtandaoni na kukuza biashara'],
        ['Bank Charges (Tozo za Benki)', 'BANK_FEE', 'Gharama za uendeshaji akaunti na miamala ya benki'],
        ['Mobile Money Charges (Tozo za Mitandao)', 'MOM_FEE', 'Tozo za kutuma na kutoa pesa kwenye Lipa Namba / M-Pesa / Tigo Pesa'],
        ['Taxes & Municipal Fees (Kodi & Leseni za Serikali)', 'TAX', 'Leseni ya biashara, ushuru wa manispaa, TRA na vibali'],
        ['Security & Guarding (Ulinzi)', 'SEC', 'Gharama za walinzi wa usiku na ulinzi wa mtaa/jengo'],
        ['Office Supplies & Stationeries (Vifaa vya Ofisi)', 'OFFICE', 'Karatasi za risiti (thermal rolls), stempu, kalamu na nyaraka'],
        ['Meals & Refreshments (Chakula & Vinywaji)', 'MEALS', 'Chakula cha mchana na viburudisho vya wafanyakazi na wageni'],
        ['Other Operating Expenses (Matumizi Mengineyo)', 'OTHER', 'Matumizi madogo madogo ya dharura ya kila siku']
    ];

    $catStmt = $pdo->prepare("
        INSERT INTO `expense_categories` (`name`, `code`, `description`, `status`)
        VALUES (?, ?, ?, 'active')
        ON DUPLICATE KEY UPDATE `description` = VALUES(`description`), `code` = VALUES(`code`)
    ");

    foreach ($standardCategories as $cat) {
        $catStmt->execute([$cat[0], $cat[1], $cat[2]]);
    }
    echo " - Seeded 16 standard expense categories.\n";


    // 2. EXPENSES TABLE
    echo "2. Checking & Enhancing `expenses` table...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `expenses` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `expense_no` VARCHAR(50) NOT NULL UNIQUE,
            `category_id` INT NOT NULL,
            `amount` DECIMAL(15,2) NOT NULL,
            `expense_date` DATE NOT NULL,
            `payment_method` VARCHAR(50) NOT NULL DEFAULT 'Cash',
            `provider` VARCHAR(50) NULL,
            `reference_no` VARCHAR(100) NULL,
            `payee` VARCHAR(150) NULL,
            `description` TEXT NOT NULL,
            `notes` TEXT NULL,
            `status` ENUM('draft', 'recorded', 'approved', 'rejected', 'cancelled') NOT NULL DEFAULT 'recorded',
            `recorded_by` INT NOT NULL,
            `approved_by` INT NULL,
            `approved_at` DATETIME NULL,
            `cancelled_by` INT NULL,
            `cancelled_at` DATETIME NULL,
            `cancel_reason` VARCHAR(255) NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (`category_id`) REFERENCES `expense_categories`(`id`) ON UPDATE CASCADE,
            FOREIGN KEY (`recorded_by`) REFERENCES `users`(`id`) ON UPDATE CASCADE,
            FOREIGN KEY (`approved_by`) REFERENCES `users`(`id`) ON UPDATE CASCADE ON DELETE SET NULL,
            FOREIGN KEY (`cancelled_by`) REFERENCES `users`(`id`) ON UPDATE CASCADE ON DELETE SET NULL
        ) ENGINE=InnoDB;
    ");

    if ($hasColumn('expenses', 'status')) {
        $pdo->exec("
            ALTER TABLE `expenses` 
            MODIFY COLUMN `status` ENUM('draft', 'recorded', 'approved', 'rejected', 'cancelled') NOT NULL DEFAULT 'recorded'
        ");
    } else {
        $pdo->exec("
            ALTER TABLE `expenses` 
            ADD COLUMN `status` ENUM('draft', 'recorded', 'approved', 'rejected', 'cancelled') NOT NULL DEFAULT 'recorded' AFTER `description`
        ");
        echo " - Added `status` to `expenses`\n";
    }

    if (!$hasColumn('expenses', 'provider')) {
        $pdo->exec("ALTER TABLE `expenses` ADD COLUMN `provider` VARCHAR(50) NULL AFTER `payment_method`");
        echo " - Added `provider` to `expenses`\n";
    }

    if (!$hasColumn('expenses', 'payee')) {
        $pdo->exec("ALTER TABLE `expenses` ADD COLUMN `payee` VARCHAR(150) NULL AFTER `reference_no`");
        echo " - Added `payee` to `expenses`\n";
    }

    if (!$hasColumn('expenses', 'notes')) {
        $pdo->exec("ALTER TABLE `expenses` ADD COLUMN `notes` TEXT NULL AFTER `description`");
        echo " - Added `notes` to `expenses`\n";
    }

    if (!$hasColumn('expenses', 'approved_by')) {
        $pdo->exec("ALTER TABLE `expenses` ADD COLUMN `approved_by` INT NULL AFTER `recorded_by`");
        $pdo->exec("ALTER TABLE `expenses` ADD CONSTRAINT `fk_expenses_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `users`(`id`) ON UPDATE CASCADE ON DELETE SET NULL");
        echo " - Added `approved_by` to `expenses`\n";
    }

    if (!$hasColumn('expenses', 'approved_at')) {
        $pdo->exec("ALTER TABLE `expenses` ADD COLUMN `approved_at` DATETIME NULL AFTER `approved_by`");
        echo " - Added `approved_at` to `expenses`\n";
    }

    if (!$hasColumn('expenses', 'cancelled_by')) {
        $pdo->exec("ALTER TABLE `expenses` ADD COLUMN `cancelled_by` INT NULL AFTER `approved_at`");
        $pdo->exec("ALTER TABLE `expenses` ADD CONSTRAINT `fk_expenses_cancelled_by` FOREIGN KEY (`cancelled_by`) REFERENCES `users`(`id`) ON UPDATE CASCADE ON DELETE SET NULL");
        echo " - Added `cancelled_by` to `expenses`\n";
    }

    if (!$hasColumn('expenses', 'cancelled_at')) {
        $pdo->exec("ALTER TABLE `expenses` ADD COLUMN `cancelled_at` DATETIME NULL AFTER `cancelled_by`");
        echo " - Added `cancelled_at` to `expenses`\n";
    }

    if (!$hasColumn('expenses', 'cancel_reason')) {
        $pdo->exec("ALTER TABLE `expenses` ADD COLUMN `cancel_reason` VARCHAR(255) NULL AFTER `cancelled_at`");
        echo " - Added `cancel_reason` to `expenses`\n";
    }

    // Indexes
    if (!$hasIndex('expenses', 'idx_expense_status')) {
        $pdo->exec("ALTER TABLE `expenses` ADD INDEX `idx_expense_status` (`status`)");
        echo " - Added index `idx_expense_status`\n";
    }
    if (!$hasIndex('expenses', 'idx_expense_date')) {
        $pdo->exec("ALTER TABLE `expenses` ADD INDEX `idx_expense_date` (`expense_date`)");
        echo " - Added index `idx_expense_date`\n";
    }
    if (!$hasIndex('expenses', 'idx_expense_category')) {
        $pdo->exec("ALTER TABLE `expenses` ADD INDEX `idx_expense_category` (`category_id`)");
        echo " - Added index `idx_expense_category`\n";
    }


    // 3. SEED 12 GRANULAR RBAC PERMISSIONS FOR PHASE 10
    echo "3. Seeding Phase 10 Granular Permissions...\n";
    $permissions = [
        ['name' => 'expenses.view',        'module' => 'expenses',  'description' => 'Kutazama orodha na historia ya matumizi ya biashara'],
        ['name' => 'expenses.create',      'module' => 'expenses',  'description' => 'Kurekodi matumizi mapya ya fedha'],
        ['name' => 'expenses.edit',        'module' => 'expenses',  'description' => 'Kuhariri taarifa za matumizi kabla ya kuidhinishwa'],
        ['name' => 'expenses.approve',     'module' => 'expenses',  'description' => 'Kuidhinisha au kukataa maombi ya matumizi (Expense Approval)'],
        ['name' => 'expenses.cancel',      'module' => 'expenses',  'description' => 'Kughairi matumizi yaliyokwisha rekodiwa (Cancel Expense)'],
        ['name' => 'expenses.reports',     'module' => 'expenses',  'description' => 'Kutazama ripoti na uchambuzi wa matumizi kwa kategoria'],
        ['name' => 'financial.dashboard',  'module' => 'financial', 'description' => 'Kutazama dashibodi kuu ya fedha na mwenendo wa faida'],
        ['name' => 'financial.reports',    'module' => 'financial', 'description' => 'Kutazama ripoti za kina za fedha, mapato na uwiano'],
        ['name' => 'financial.profit',     'module' => 'financial', 'description' => 'Kutazama hesabu za faida ghafi na faida halisi (Gross/Net Profit)'],
        ['name' => 'financial.cost',       'module' => 'financial', 'description' => 'Kutazama gharama za ununuzi wa bidhaa (COGS / Unit Costs)'],
        ['name' => 'product_profit.view',  'module' => 'financial', 'description' => 'Kutazama uchambuzi wa faida kwa kila kipuri (Product Profitability)'],
        ['name' => 'sales_profit.view',    'module' => 'financial', 'description' => 'Kutazama faida kwa kila ankara ya mauzo na kwa kila cashier']
    ];

    $permStmt = $pdo->prepare("
        INSERT INTO `permissions` (`name`, `module`, `description`)
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE `description` = VALUES(`description`), `module` = VALUES(`module`)
    ");

    foreach ($permissions as $p) {
        $permStmt->execute([$p['name'], $p['module'], $p['description']]);
    }
    echo " - Seeded 12 granular financial & expense permissions successfully.\n";


    // 4. MAP PERMISSIONS TO ROLES
    echo "4. Mapping Phase 10 permissions to roles...\n";
    $rolesStmt = $pdo->query("SELECT id, name FROM `roles`");
    $roleMap = [];
    while ($r = $rolesStmt->fetch(PDO::FETCH_ASSOC)) {
        $roleMap[$r['name']] = (int)$r['id'];
    }

    $allPermIds = $pdo->query("SELECT id, name FROM `permissions`")->fetchAll(PDO::FETCH_KEY_PAIR);

    $rolePermMappings = [
        'super_admin' => array_keys($allPermIds), // All permissions
        'manager'     => [
            'expenses.view', 'expenses.create', 'expenses.edit', 'expenses.approve', 'expenses.cancel', 'expenses.reports',
            'financial.dashboard', 'financial.reports', 'financial.profit', 'financial.cost', 'product_profit.view', 'sales_profit.view'
        ],
        'accountant'  => [
            'expenses.view', 'expenses.create', 'expenses.edit', 'expenses.approve', 'expenses.cancel', 'expenses.reports',
            'financial.dashboard', 'financial.reports', 'financial.profit', 'financial.cost', 'product_profit.view', 'sales_profit.view'
        ],
        'cashier'     => [
            'expenses.view', 'expenses.create'
            // Explicitly BLOCKED from expenses.approve, expenses.cancel, financial.profit, financial.cost, product_profit.view, sales_profit.view
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
    echo " - Phase 10 role permissions mapped successfully.\n";


    // 5. SEED SYSTEM SETTINGS FOR PHASE 10
    echo "5. Seeding Phase 10 system settings...\n";
    $settings = [
        ['expense_prefix', 'EXP-', 'Kianzio cha nambari ya matumizi (Expense Number Prefix)'],
        ['default_expense_payment_method', 'Cash', 'Njia chaguo-msingi ya kulipa matumizi']
    ];

    $setStmt = $pdo->prepare("
        INSERT INTO `settings` (`setting_key`, `setting_value`, `description`, `updated_by`)
        VALUES (?, ?, ?, 1)
        ON DUPLICATE KEY UPDATE `description` = VALUES(`description`)
    ");

    foreach ($settings as $s) {
        $setStmt->execute([$s[0], $s[1], $s[2]]);
    }
    echo " - Phase 10 settings seeded successfully.\n";

    echo "=======================================================\n";
    echo "PHASE 10 DATABASE MIGRATION COMPLETED SUCCESSFULLY!\n";
    echo "=======================================================\n";

} catch (Exception $e) {
    echo "MIGRATION ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
