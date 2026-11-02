<?php
/**
 * NGENZI MOTO SPARE PARTS
 * Phase 14 Database Migration
 * Security Hardening, Audit Trail, Access Control & System Reliability
 */

require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/core/Database.php';

use App\Core\Database;

try {
    $pdo = Database::getConnection();
    echo "=======================================================\n";
    echo "Starting Phase 14 Database Migration (Security & Audit)...\n";
    echo "=======================================================\n";

    // 1. SEED SECURITY & AUDIT PERMISSIONS
    echo "1. Seeding Phase 14 Security & Audit Permissions...\n";
    $permissions = [
        ['name' => 'view_audit_logs',  'module' => 'audit',    'description' => 'Kutazama kumbukumbu ya matukio ya mfumo (Audit Trail)'],
        ['name' => 'audit.view',       'module' => 'audit',    'description' => 'Kutazama kumbukumbu ya matukio ya mfumo (Audit Trail)'],
        ['name' => 'audit.export',     'module' => 'audit',    'description' => 'Kupakua kumbukumbu ya matukio (Audit Log CSV Export)'],
        ['name' => 'security.view',    'module' => 'security', 'description' => 'Kutazama hadhi ya usalama wa mfumo (Security Overview)']
    ];

    $permStmt = $pdo->prepare("
        INSERT INTO `permissions` (`name`, `module`, `description`)
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE `description` = VALUES(`description`), `module` = VALUES(`module`)
    ");

    foreach ($permissions as $p) {
        $permStmt->execute([$p['name'], $p['module'], $p['description']]);
    }
    echo " - Seeded security & audit permissions successfully.\n";

    // 2. MAP PERMISSIONS TO ROLES
    echo "2. Mapping Phase 14 permissions to roles...\n";
    $rolesStmt = $pdo->query("SELECT id, name FROM `roles`");
    $roleMap = [];
    while ($r = $rolesStmt->fetch(PDO::FETCH_ASSOC)) {
        $roleMap[$r['name']] = (int)$r['id'];
    }

    $allPermIds = $pdo->query("SELECT id, name FROM `permissions`")->fetchAll(PDO::FETCH_KEY_PAIR);

    $rolePermMappings = [
        'super_admin' => ['view_audit_logs', 'audit.view', 'audit.export', 'security.view'],
        'manager'     => ['view_audit_logs', 'audit.view', 'audit.export', 'security.view']
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
    echo " - Phase 14 role permissions mapped successfully.\n";

    // 3. SEED SECURITY SETTINGS
    echo "3. Seeding Security Configuration Settings...\n";
    $settings = [
        ['session_timeout_minutes', '30', 'Muda wa mfumo kujifunga baada ya kutotumika (Inactivity Timeout in Minutes)', 1],
        ['password_min_length', '8', 'Urefu wa chini wa nenosiri (Minimum Password Length)', 1],
        ['max_login_attempts', '5', 'Idadi ya juu ya majaribio ya kuingia kabla ya kufungiwa kwa muda', 1],
        ['lockout_time_seconds', '180', 'Muda wa kufungiwa baada ya majaribio mengi yasiyo sahihi (Seconds)', 1],
    ];

    $setStmt = $pdo->prepare("
        INSERT INTO `settings` (`setting_key`, `setting_value`, `description`, `updated_by`)
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE `description` = VALUES(`description`)
    ");

    foreach ($settings as $s) {
        $setStmt->execute([$s[0], $s[1], $s[2], $s[3]]);
    }
    echo " - Seeded 4 security settings successfully.\n";

    // 4. VERIFY AUDIT LOG TABLE INDEXES FOR FAST QUERYING
    echo "4. Checking and optimizing audit_logs indexes...\n";
    $auditIndexes = $pdo->query("SHOW INDEX FROM `audit_logs`")->fetchAll(PDO::FETCH_ASSOC);
    $existingIndexNames = array_unique(array_column($auditIndexes, 'Key_name'));

    if (!in_array('idx_audit_module_action', $existingIndexNames)) {
        $pdo->exec("ALTER TABLE `audit_logs` ADD INDEX `idx_audit_module_action` (`module`, `action`)");
        echo " - Added index idx_audit_module_action to audit_logs.\n";
    }
    if (!in_array('idx_audit_created_at', $existingIndexNames)) {
        $pdo->exec("ALTER TABLE `audit_logs` ADD INDEX `idx_audit_created_at` (`created_at`)");
        echo " - Added index idx_audit_created_at to audit_logs.\n";
    }

    echo "=======================================================\n";
    echo "PHASE 14 DATABASE MIGRATION COMPLETED SUCCESSFULLY!\n";
    echo "=======================================================\n";

} catch (Exception $e) {
    echo "MIGRATION ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
