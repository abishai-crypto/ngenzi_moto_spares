<?php
/**
 * NGENZI MOTO SPARE PARTS
 * Phase 7 Migration: Customers, Mechanics, Garages & Customer Vehicles
 */

require_once __DIR__ . '/../app/core/Database.php';

use App\Core\Database;

$pdo = Database::getConnection();

echo "Starting Phase 7 Migration...\n";

// 1. Create customer_vehicles table
$pdo->exec("
    CREATE TABLE IF NOT EXISTS `customer_vehicles` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `customer_id` INT NOT NULL,
        `vehicle_model_id` INT NOT NULL,
        `registration_number` VARCHAR(50) NULL,
        `chassis_number` VARCHAR(50) NULL,
        `engine_number` VARCHAR(50) NULL,
        `notes` TEXT NULL,
        `status` ENUM('active', 'inactive') DEFAULT 'active',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`vehicle_model_id`) REFERENCES `vehicle_models`(`id`) ON DELETE RESTRICT,
        INDEX `idx_cv_customer` (`customer_id`),
        INDEX `idx_cv_vehicle` (`vehicle_model_id`),
        INDEX `idx_cv_reg` (`registration_number`)
    ) ENGINE=InnoDB;
");
echo "✓ Table `customer_vehicles` checked/created.\n";

// Helper function to check if column exists
function columnExists(PDO $pdo, string $table, string $column): bool {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() 
          AND TABLE_NAME = ? 
          AND COLUMN_NAME = ?
    ");
    $stmt->execute([$table, $column]);
    return (int)$stmt->fetchColumn() > 0;
}

// 2. Alter customers table
$pdo->exec("
    ALTER TABLE `customers` 
    MODIFY COLUMN `customer_type` ENUM('individual', 'retail', 'mechanic', 'garage', 'business', 'walk_in', 'reseller', 'dealer', 'other') DEFAULT 'individual',
    MODIFY COLUMN `status` ENUM('active', 'inactive', 'blocked') DEFAULT 'active'
");

if (!columnExists($pdo, 'customers', 'alternative_phone')) {
    $pdo->exec("ALTER TABLE `customers` ADD COLUMN `alternative_phone` VARCHAR(20) NULL AFTER `phone`");
}
if (!columnExists($pdo, 'customers', 'area')) {
    $pdo->exec("ALTER TABLE `customers` ADD COLUMN `area` VARCHAR(100) NULL AFTER `address`");
}
if (!columnExists($pdo, 'customers', 'gender')) {
    $pdo->exec("ALTER TABLE `customers` ADD COLUMN `gender` VARCHAR(20) NULL AFTER `area`");
}
if (!columnExists($pdo, 'customers', 'created_by')) {
    $pdo->exec("ALTER TABLE `customers` ADD COLUMN `created_by` INT NULL AFTER `status`");
    $pdo->exec("ALTER TABLE `customers` ADD FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL");
}
if (!columnExists($pdo, 'customers', 'updated_by')) {
    $pdo->exec("ALTER TABLE `customers` ADD COLUMN `updated_by` INT NULL AFTER `created_by`");
    $pdo->exec("ALTER TABLE `customers` ADD FOREIGN KEY (`updated_by`) REFERENCES `users`(`id`) ON DELETE SET NULL");
}
echo "✓ Table `customers` schema updated.\n";

// 3. Alter garages table
if (!columnExists($pdo, 'garages', 'garage_code')) {
    $pdo->exec("ALTER TABLE `garages` ADD COLUMN `garage_code` VARCHAR(50) NULL UNIQUE AFTER `id`");
}
if (!columnExists($pdo, 'garages', 'alternative_phone')) {
    $pdo->exec("ALTER TABLE `garages` ADD COLUMN `alternative_phone` VARCHAR(20) NULL AFTER `phone`");
}
if (!columnExists($pdo, 'garages', 'email')) {
    $pdo->exec("ALTER TABLE `garages` ADD COLUMN `email` VARCHAR(100) NULL AFTER `alternative_phone`");
}
if (!columnExists($pdo, 'garages', 'area')) {
    $pdo->exec("ALTER TABLE `garages` ADD COLUMN `area` VARCHAR(100) NULL AFTER `location`");
}
if (!columnExists($pdo, 'garages', 'garage_type')) {
    $pdo->exec("ALTER TABLE `garages` ADD COLUMN `garage_type` VARCHAR(100) NULL AFTER `specialization`");
}
if (!columnExists($pdo, 'garages', 'customer_id')) {
    $pdo->exec("ALTER TABLE `garages` ADD COLUMN `customer_id` INT NULL AFTER `credit_limit`");
    $pdo->exec("ALTER TABLE `garages` ADD FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`) ON DELETE SET NULL");
}
if (!columnExists($pdo, 'garages', 'created_by')) {
    $pdo->exec("ALTER TABLE `garages` ADD COLUMN `created_by` INT NULL AFTER `status`");
    $pdo->exec("ALTER TABLE `garages` ADD FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL");
}
echo "✓ Table `garages` schema updated.\n";

// 4. Alter mechanics table
if (!columnExists($pdo, 'mechanics', 'mechanic_code')) {
    $pdo->exec("ALTER TABLE `mechanics` ADD COLUMN `mechanic_code` VARCHAR(50) NULL UNIQUE AFTER `id`");
}
if (!columnExists($pdo, 'mechanics', 'alternative_phone')) {
    $pdo->exec("ALTER TABLE `mechanics` ADD COLUMN `alternative_phone` VARCHAR(20) NULL AFTER `phone`");
}
if (!columnExists($pdo, 'mechanics', 'area')) {
    $pdo->exec("ALTER TABLE `mechanics` ADD COLUMN `area` VARCHAR(100) NULL AFTER `alternative_phone`");
}
if (!columnExists($pdo, 'mechanics', 'years_of_experience')) {
    $pdo->exec("ALTER TABLE `mechanics` ADD COLUMN `years_of_experience` INT NULL AFTER `specialization`");
}
if (!columnExists($pdo, 'mechanics', 'status')) {
    $pdo->exec("ALTER TABLE `mechanics` ADD COLUMN `status` ENUM('active', 'inactive', 'blocked') DEFAULT 'active' AFTER `notes`");
}
if (!columnExists($pdo, 'mechanics', 'created_by')) {
    $pdo->exec("ALTER TABLE `mechanics` ADD COLUMN `created_by` INT NULL AFTER `status`");
    $pdo->exec("ALTER TABLE `mechanics` ADD FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL");
}
echo "✓ Table `mechanics` schema updated.\n";

// 5. Populate missing codes for existing customers, mechanics, garages
$customers = $pdo->query("SELECT id, customer_code FROM `customers` ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
$idx = 1;
foreach ($customers as $c) {
    if (empty($c['customer_code'])) {
        $code = sprintf("CUS-%06d", $idx);
        $pdo->prepare("UPDATE `customers` SET customer_code = ? WHERE id = ?")->execute([$code, $c['id']]);
    }
    $idx++;
}

$garages = $pdo->query("SELECT id, garage_code FROM `garages` ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
$idx = 1;
foreach ($garages as $g) {
    if (empty($g['garage_code'])) {
        $code = sprintf("GAR-%06d", $idx);
        $pdo->prepare("UPDATE `garages` SET garage_code = ? WHERE id = ?")->execute([$code, $g['id']]);
    }
    $idx++;
}

$mechanics = $pdo->query("SELECT id, mechanic_code FROM `mechanics` ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
$idx = 1;
foreach ($mechanics as $m) {
    if (empty($m['mechanic_code'])) {
        $code = sprintf("MECH-%06d", $idx);
        $pdo->prepare("UPDATE `mechanics` SET mechanic_code = ? WHERE id = ?")->execute([$code, $m['id']]);
    }
    $idx++;
}
echo "✓ Codes generated for existing customers, mechanics, and garages.\n";

// 6. Seed Permissions
$newPermissions = [
    'customers.view'           => 'Ruhusa ya kuangalia wateja na orodha zao',
    'customers.create'         => 'Ruhusa ya kusajili mteja mpya',
    'customers.edit'           => 'Ruhusa ya kuhariri taarifa za mteja',
    'customers.deactivate'     => 'Ruhusa ya kusitisha au kuzuia mteja',
    'mechanics.view'           => 'Ruhusa ya kuangalia orodha ya mafundi',
    'mechanics.create'         => 'Ruhusa ya kusajili fundi mpya',
    'mechanics.edit'           => 'Ruhusa ya kuhariri taarifa za fundi',
    'mechanics.deactivate'     => 'Ruhusa ya kusitisha fundi',
    'garages.view'             => 'Ruhusa ya kuangalia gereji na karakana',
    'garages.create'           => 'Ruhusa ya kusajili gereji mpya',
    'garages.edit'             => 'Ruhusa ya kuhariri taarifa za gereji',
    'garages.deactivate'       => 'Ruhusa ya kusitisha gereji',
    'customer_vehicles.view'   => 'Ruhusa ya kuangalia vyombo vya wateja',
    'customer_vehicles.create' => 'Ruhusa ya kuunganisha chombo na mteja',
    'customer_vehicles.edit'   => 'Ruhusa ya kuhariri chombo cha mteja',
    'customer_vehicles.delete' => 'Ruhusa ya kuondoa au kusitisha chombo cha mteja',
    'customer_history.view'    => 'Ruhusa ya kuangalia historia ya mauzo na malipo ya mteja'
];

$stmtPermCheck = $pdo->prepare("SELECT id FROM `permissions` WHERE name = ?");
$stmtPermInsert = $pdo->prepare("INSERT INTO `permissions` (name, description, module) VALUES (?, ?, 'customers')");

foreach ($newPermissions as $pName => $pDesc) {
    $stmtPermCheck->execute([$pName]);
    if (!$stmtPermCheck->fetchColumn()) {
        $stmtPermInsert->execute([$pName, $pDesc]);
    }
}
echo "✓ Phase 7 Permissions seeded into `permissions` table.\n";

// 7. Map permissions to roles
$roles = $pdo->query("SELECT name, id FROM `roles`")->fetchAll(PDO::FETCH_KEY_PAIR);
$allPerms = $pdo->query("SELECT name, id FROM `permissions`")->fetchAll(PDO::FETCH_KEY_PAIR);

$stmtRolePerm = $pdo->prepare("
    INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
    VALUES (?, ?)
");

// Grant to Super Admin and Manager (Full access)
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

// Grant to Cashier: view & create customers/vehicles, view history, view mechanics/garages
$cashierPerms = [
    'customers.view', 'customers.create',
    'customer_vehicles.view', 'customer_vehicles.create',
    'mechanics.view', 'garages.view',
    'customer_history.view'
];
if (isset($roles['cashier'])) {
    $cId = $roles['cashier'];
    foreach ($cashierPerms as $pName) {
        if (isset($allPerms[$pName])) {
            $stmtRolePerm->execute([$cId, $allPerms[$pName]]);
        }
    }
}

// Grant to Accountant: view customers, mechanics, garages, customer_history
$accountantPerms = [
    'customers.view', 'mechanics.view', 'garages.view', 'customer_history.view'
];
if (isset($roles['accountant'])) {
    $aId = $roles['accountant'];
    foreach ($accountantPerms as $pName) {
        if (isset($allPerms[$pName])) {
            $stmtRolePerm->execute([$aId, $allPerms[$pName]]);
        }
    }
}

// Grant to Storekeeper: view
$storekeeperPerms = [
    'customers.view', 'mechanics.view', 'garages.view', 'customer_vehicles.view'
];
if (isset($roles['storekeeper'])) {
    $sId = $roles['storekeeper'];
    foreach ($storekeeperPerms as $pName) {
        if (isset($allPerms[$pName])) {
            $stmtRolePerm->execute([$sId, $allPerms[$pName]]);
        }
    }
}

echo "✓ Role permissions mapped successfully.\n";
echo "Phase 7 Migration Completed Successfully!\n";
