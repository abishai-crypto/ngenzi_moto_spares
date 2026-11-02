<?php
/**
 * NGENZI MOTO SPARE PARTS
 * Phase 13 Database Migration
 * Advanced Dashboard, Business Intelligence & Decision Support — Permissions, Settings & Indexes
 */

require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/core/Database.php';

use App\Core\Database;

try {
    $pdo = Database::getConnection();
    echo "=======================================================\n";
    echo "Starting Phase 13 Database Migration (Advanced Dashboard & BI)...\n";
    echo "=======================================================\n";

    // 1. SEED DASHBOARD PERMISSIONS
    echo "1. Seeding Phase 13 Dashboard Permissions...\n";
    $permissions = [
        ['name' => 'dashboard.view',          'module' => 'dashboard', 'description' => 'Kufikia dashibodi ya mfumo'],
        ['name' => 'dashboard.executive',     'module' => 'dashboard', 'description' => 'Kutazama takwimu za kiutendaji na kifedha (Executive KPI cards & P&L)'],
        ['name' => 'dashboard.export',        'module' => 'dashboard', 'description' => 'Kupakua muhtasari wa dashibodi (CSV/Report export)'],
        ['name' => 'dashboard.manage_alerts', 'module' => 'dashboard', 'description' => 'Kurekebisha vigezo vya tahadhari (Alert thresholds)']
    ];

    $permStmt = $pdo->prepare("
        INSERT INTO `permissions` (`name`, `module`, `description`)
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE `description` = VALUES(`description`), `module` = VALUES(`module`)
    ");

    foreach ($permissions as $p) {
        $permStmt->execute([$p['name'], $p['module'], $p['description']]);
    }
    echo " - Seeded 4 dashboard permissions successfully.\n";

    // 2. MAP PERMISSIONS TO ROLES
    echo "2. Mapping Phase 13 permissions to roles...\n";
    $rolesStmt = $pdo->query("SELECT id, name FROM `roles`");
    $roleMap = [];
    while ($r = $rolesStmt->fetch(PDO::FETCH_ASSOC)) {
        $roleMap[$r['name']] = (int)$r['id'];
    }

    $allPermIds = $pdo->query("SELECT id, name FROM `permissions`")->fetchAll(PDO::FETCH_KEY_PAIR);

    $rolePermMappings = [
        'super_admin' => ['dashboard.view', 'dashboard.executive', 'dashboard.export', 'dashboard.manage_alerts'],
        'manager'     => ['dashboard.view', 'dashboard.executive', 'dashboard.export', 'dashboard.manage_alerts'],
        'accountant'  => ['dashboard.view', 'dashboard.executive', 'dashboard.export'],
        'storekeeper' => ['dashboard.view'],
        'cashier'     => ['dashboard.view']
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
    echo " - Phase 13 role permissions mapped successfully.\n";

    // 3. SEED ALERT THRESHOLD SETTINGS
    echo "3. Seeding Alert & Decision Support Settings...\n";
    $settings = [
        ['dashboard_dead_stock_days', '90', 'Siku za kutotembea kwa mzigo ili kutangazwa Dead Stock', 1],
        ['dashboard_slow_stock_days', '30', 'Kipindi cha siku cha kutathmini mzigo unaoenda taratibu (Slow Moving)', 1],
        ['dashboard_slow_stock_threshold', '2', 'Kiwango cha juu cha vipande vinavyouzwa katika kipindi cha slow stock', 1],
        ['dashboard_overdue_debt_days', '1', 'Siku ambazo deni linapita tarehe ya ahadi ili kuwa Overdue Alert', 1],
        ['dashboard_high_return_threshold', '10.00', 'Kiwango cha asilimia ya marejesho kinachowasha tahadhari (High Return Rate %)', 1],
    ];

    $setStmt = $pdo->prepare("
        INSERT INTO `settings` (`setting_key`, `setting_value`, `description`, `updated_by`)
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE `description` = VALUES(`description`)
    ");

    foreach ($settings as $s) {
        $setStmt->execute([$s[0], $s[1], $s[2], $s[3]]);
    }
    echo " - Seeded 5 dashboard threshold settings successfully.\n";

    echo "=======================================================\n";
    echo "PHASE 13 DATABASE MIGRATION COMPLETED SUCCESSFULLY!\n";
    echo "=======================================================\n";

} catch (Exception $e) {
    echo "MIGRATION ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
