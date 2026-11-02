<?php
/**
 * NGENZI MOTO SPARE PARTS
 * Phase 15 Migration: Backup, Settings, System Health & Production Readiness
 */

require_once __DIR__ . '/../app/core/Database.php';

use App\Core\Database;

$pdo = Database::getConnection();

echo "=== Phase 15 Migration: Backup & Production Readiness ===\n";

// 1. Seed Granular Permissions for Backups, Settings & System Health
$permissions = [
    ['name' => 'backups.view', 'module' => 'system', 'description' => 'Kuangalia Orodha ya Nakala za Data (View Backups)'],
    ['name' => 'backups.create', 'module' => 'system', 'description' => 'Kutengeneza Nakala ya Data (Create Backup)'],
    ['name' => 'backups.download', 'module' => 'system', 'description' => 'Kupakua Faili la Nakala (Download Backup)'],
    ['name' => 'backups.delete', 'module' => 'system', 'description' => 'Kufuta Faili la Nakala (Delete Backup)'],
    ['name' => 'backups.restore_test', 'module' => 'system', 'description' => 'Kupima Urejesho wa Data kwenye Mazingira ya Majaribio (Test Restore)'],
    ['name' => 'settings.view', 'module' => 'system', 'description' => 'Kuangalia Mipangilio ya Mfumo (View System Settings)'],
    ['name' => 'settings.edit', 'module' => 'system', 'description' => 'Kurekebisha Mipangilio ya Biashara (Edit System Settings)'],
    ['name' => 'system.health', 'module' => 'system', 'description' => 'Kuangalia Afya ya Mfumo (View System Health)'],
];

$permStmt = $pdo->prepare("
    INSERT INTO `permissions` (`name`, `module`, `description`)
    VALUES (?, ?, ?)
    ON DUPLICATE KEY UPDATE `description` = VALUES(`description`), `module` = VALUES(`module`)
");

$seededCount = 0;
foreach ($permissions as $p) {
    $permStmt->execute([$p['name'], $p['module'], $p['description']]);
    $seededCount++;
}
echo "✓ Permissions seeded: {$seededCount} permissions processed.\n";

// 2. Map Permissions to Roles
$rolesStmt = $pdo->query("SELECT id, name FROM roles");
$roleMap = [];
while ($r = $rolesStmt->fetch(PDO::FETCH_ASSOC)) {
    $roleMap[$r['name']] = (int)$r['id'];
}

$allPerms = $pdo->query("SELECT id, name FROM `permissions`")->fetchAll(PDO::FETCH_KEY_PAIR);
$permIdByName = array_flip($allPerms);

$adminPerms = [
    'backups.view', 'backups.create', 'backups.download', 'backups.delete', 'backups.restore_test',
    'settings.view', 'settings.edit', 'system.health'
];

$rolePermInsert = $pdo->prepare("INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (?, ?)");

if (isset($roleMap['super_admin'])) {
    $adminRoleId = $roleMap['super_admin'];
    foreach ($adminPerms as $permName) {
        if (isset($permIdByName[$permName])) {
            $rolePermInsert->execute([$adminRoleId, $permIdByName[$permName]]);
        }
    }
    echo "✓ Super Admin mapped to all Phase 15 permissions.\n";
}

if (isset($roleMap['manager'])) {
    $managerRoleId = $roleMap['manager'];
    $managerPerms = ['backups.view', 'settings.view', 'system.health'];
    foreach ($managerPerms as $permName) {
        if (isset($permIdByName[$permName])) {
            $rolePermInsert->execute([$managerRoleId, $permIdByName[$permName]]);
        }
    }
    echo "✓ Manager mapped to view-only Phase 15 permissions.\n";
}

// 3. Seed Default System Settings if missing
$defaultSettings = [
    'app_version' => ['v1.0.0', 'Toleo la Mfumo (Application Version)'],
    'system_environment' => ['production', 'Mazingira ya Uendeshaji (Environment)'],
    'app_timezone' => ['Africa/Dar_es_Salaam', 'Ukanda wa Saa (Timezone)'],
    'business_phone' => ['+255 700 000 000', 'Namba ya Simu ya Biashara'],
    'business_email' => ['info@ngenzispareparts.co.tz', 'Barua Pepe ya Biashara'],
    'business_address' => ['Mtaa wa Livingstone & Aggrey, Kariakoo, Dar es Salaam', 'Anuani ya Biashara'],
    'receipt_footer' => ['Asante kwa kufanya biashara na Ngenzi Moto Spare Parts! Karibu tena.', 'Ujumbe wa Chini kwenye Risiti'],
    'invoice_footer' => ['Bidhaa zikishanunuliwa haziwezi kurudishwa isipokuwa kwa masharti maalum ndani ya siku 3.', 'Ujumbe wa Chini kwenye Ankara/Invoice'],
    'default_currency' => ['TZS', 'Sarafu Rasmi ya Nchi'],
    'currency_symbol' => ['TSh', 'Alama ya Sarafu']
];

$checkSetting = $pdo->prepare("SELECT id FROM `settings` WHERE setting_key = ?");
$insertSetting = $pdo->prepare("INSERT INTO `settings` (setting_key, setting_value, description) VALUES (?, ?, ?)");

$settingsAdded = 0;
foreach ($defaultSettings as $key => $data) {
    $checkSetting->execute([$key]);
    if (!$checkSetting->fetch()) {
        $insertSetting->execute([$key, $data[0], $data[1]]);
        $settingsAdded++;
    }
}
echo "✓ System settings processed: {$settingsAdded} new settings added.\n";

echo "=== Phase 15 Migration Complete! ===\n";
