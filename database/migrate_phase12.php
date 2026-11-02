<?php
/**
 * NGENZI MOTO SPARE PARTS
 * Phase 12 Database Migration
 * Reports & Advanced Business Analytics — Indexes, Permissions & Settings
 */

require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/core/Database.php';

use App\Core\Database;

try {
    $pdo = Database::getConnection();
    echo "=======================================================\n";
    echo "Starting Phase 12 Database Migration (Reports & Analytics)...\n";
    echo "=======================================================\n";

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

    // 1. PERFORMANCE INDEXES FOR REPORTING AGGREGATIONS
    echo "1. Checking & Adding Performance Indexes for Reports...\n";

    $indexes = [
        ['sales', 'idx_sales_date_status', '(`sale_date`, `status`)'],
        ['sales', 'idx_sales_user_date', '(`user_id`, `sale_date`)'],
        ['sales', 'idx_sales_customer_date', '(`customer_id`, `sale_date`)'],
        ['sale_items', 'idx_sale_items_product_sale', '(`product_id`, `sale_id`)'],
        ['sale_payments', 'idx_sale_payments_created_method', '(`created_at`, `payment_method`)'],
        ['purchases', 'idx_purchases_date_supplier', '(`purchase_date`, `supplier_id`)'],
        ['inventory_transactions', 'idx_inv_trans_product_date', '(`product_id`, `created_at`)'],
        ['returns', 'idx_returns_created_status', '(`created_at`, `status`)'],
        ['return_items', 'idx_return_items_product_return', '(`product_id`, `return_id`)'],
        ['expenses', 'idx_expenses_date_status_cat', '(`expense_date`, `status`, `category_id`)'],
        ['product_vehicle_compatibilities', 'idx_pvc_vehicle_model', '(`vehicle_model_id`, `product_id`)']
    ];

    foreach ($indexes as $idx) {
        $table = $idx[0];
        $indexName = $idx[1];
        $columns = $idx[2];

        if (!$hasIndex($table, $indexName)) {
            try {
                $pdo->exec("ALTER TABLE `{$table}` ADD INDEX `{$indexName}` {$columns}");
                echo " - Added index `{$indexName}` on `{$table}`\n";
            } catch (Exception $e) {
                echo " - Note on index `{$indexName}`: " . $e->getMessage() . "\n";
            }
        }
    }

    // 2. SEED PHASE 12 GRANULAR REPORTING PERMISSIONS
    echo "2. Seeding Phase 12 Granular Reporting Permissions...\n";
    $permissions = [
        ['name' => 'reports.view',        'module' => 'reports', 'description' => 'Kufikia kituo kikuu cha ripoti na takwimu za biashara'],
        ['name' => 'reports.sales',       'module' => 'reports', 'description' => 'Kutazama ripoti za mauzo kwa siku, bidhaa, kategoria na wauzaji'],
        ['name' => 'reports.financial',   'module' => 'reports', 'description' => 'Kutazama taarifa kamili za fedha, mapato, matumizi na mtiririko wa pesa'],
        ['name' => 'reports.profit',      'module' => 'reports', 'description' => 'Kutazama uchambuzi wa kina wa faida ghafi na halisi (P&L)'],
        ['name' => 'reports.inventory',   'module' => 'reports', 'description' => 'Kutazama ripoti za stoo, thamani ya mali, fast/slow moving na dead stock'],
        ['name' => 'reports.purchases',   'module' => 'reports', 'description' => 'Kutazama ripoti za manunuzi ya mzigo na wauzaji wa jumla'],
        ['name' => 'reports.customers',   'module' => 'reports', 'description' => 'Kutazama ripoti za wateja, miamala, manunuzi na customer statements'],
        ['name' => 'reports.debts',       'module' => 'reports', 'description' => 'Kutazama ripoti za madeni ya wateja na uchambuzi wa siku (Debt Aging)'],
        ['name' => 'reports.returns',     'module' => 'reports', 'description' => 'Kutazama ripoti za marejesho ya bidhaa na kiwango cha kurejesha (Return Rate)'],
        ['name' => 'reports.expenses',    'module' => 'reports', 'description' => 'Kutazama ripoti na grafu za matumizi ya uendeshaji'],
        ['name' => 'reports.export',      'module' => 'reports', 'description' => 'Kupakua ripoti kwa mfumo wa CSV / Excel kwa matumizi ya ukaguzi'],
        ['name' => 'reports.view_cost',   'module' => 'reports', 'description' => 'Kutazama bei za kununulia (Buying Cost / COGS) kwenye ripoti']
    ];

    $permStmt = $pdo->prepare("
        INSERT INTO `permissions` (`name`, `module`, `description`)
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE `description` = VALUES(`description`), `module` = VALUES(`module`)
    ");

    foreach ($permissions as $p) {
        $permStmt->execute([$p['name'], $p['module'], $p['description']]);
    }
    echo " - Seeded 12 granular reporting permissions successfully.\n";

    // 3. MAP PERMISSIONS TO ROLES
    echo "3. Mapping Phase 12 permissions to roles...\n";
    $rolesStmt = $pdo->query("SELECT id, name FROM `roles`");
    $roleMap = [];
    while ($r = $rolesStmt->fetch(PDO::FETCH_ASSOC)) {
        $roleMap[$r['name']] = (int)$r['id'];
    }

    $allPermIds = $pdo->query("SELECT id, name FROM `permissions`")->fetchAll(PDO::FETCH_KEY_PAIR);

    $rolePermMappings = [
        'super_admin' => array_keys($allPermIds), // All permissions
        'manager'     => [
            'reports.view', 'reports.sales', 'reports.financial', 'reports.profit', 'reports.inventory',
            'reports.purchases', 'reports.customers', 'reports.debts', 'reports.returns', 'reports.expenses',
            'reports.export', 'reports.view_cost'
        ],
        'accountant'  => [
            'reports.view', 'reports.sales', 'reports.financial', 'reports.profit', 'reports.inventory',
            'reports.purchases', 'reports.customers', 'reports.debts', 'reports.returns', 'reports.expenses',
            'reports.export', 'reports.view_cost'
        ],
        'storekeeper' => [
            'reports.view', 'reports.inventory', 'reports.purchases', 'reports.returns'
            // Blocked from financial, profit, debts, view_cost
        ],
        'cashier'     => [
            'reports.view', 'reports.sales', 'reports.customers'
            // Blocked from financial, profit, debts, expenses, view_cost
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
    echo " - Phase 12 role permissions mapped successfully.\n";

    echo "=======================================================\n";
    echo "PHASE 12 DATABASE MIGRATION COMPLETED SUCCESSFULLY!\n";
    echo "=======================================================\n";

} catch (Exception $e) {
    echo "MIGRATION ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
