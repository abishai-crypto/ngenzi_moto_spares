<?php
/**
 * NGENZI MOTO SPARE PARTS
 * Phase 16 Migration: E-Commerce, Customer Portal, Online Ordering, Payments & Delivery Integration
 * Dar es Salaam, Tanzania
 */

require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/core/Database.php';

use App\Core\Database;

$pdo = Database::getConnection();

echo "=== Starting Phase 16 Migration ===\n";

// 1. Alter existing tables (additive only, check column existence first)
function addColumnIfNotExists(PDO $pdo, string $table, string $column, string $definition): void {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?
    ");
    $stmt->execute([$table, $column]);
    if ((int)$stmt->fetchColumn() === 0) {
        $pdo->exec("ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$definition}");
        echo "  ✓ Added column `{$column}` to `{$table}`\n";
    } else {
        echo "  - Column `{$column}` already exists in `{$table}`\n";
    }
}

echo "1. Extending existing tables...\n";
addColumnIfNotExists($pdo, 'products', 'is_online', 'TINYINT(1) NOT NULL DEFAULT 1');
addColumnIfNotExists($pdo, 'products', 'is_featured', 'TINYINT(1) NOT NULL DEFAULT 0');
addColumnIfNotExists($pdo, 'products', 'image_url', 'VARCHAR(255) NULL AFTER `shelf_location`');
addColumnIfNotExists($pdo, 'products', 'online_description', 'TEXT NULL AFTER `description`');
addColumnIfNotExists($pdo, 'products', 'online_sort_order', 'INT(11) NOT NULL DEFAULT 0');

addColumnIfNotExists($pdo, 'customers', 'password', 'VARCHAR(255) NULL AFTER `email`');
addColumnIfNotExists($pdo, 'customers', 'last_login', 'DATETIME NULL AFTER `status`');

addColumnIfNotExists($pdo, 'sales', 'sales_channel', "ENUM('POS', 'ONLINE') NOT NULL DEFAULT 'POS' AFTER `payment_terms`");
addColumnIfNotExists($pdo, 'sales', 'online_order_id', 'INT(11) NULL AFTER `sales_channel`');

// 2. Create New Tables for E-Commerce & Customer Portal
echo "2. Creating Phase 16 E-Commerce tables...\n";

// Table: customer_addresses
$pdo->exec("
CREATE TABLE IF NOT EXISTS `customer_addresses` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `customer_id` INT(11) NOT NULL,
    `label` VARCHAR(50) NOT NULL DEFAULT 'Nyumbani',
    `recipient_name` VARCHAR(100) NOT NULL,
    `phone` VARCHAR(25) NOT NULL,
    `region` VARCHAR(50) NOT NULL DEFAULT 'Dar es Salaam',
    `district` VARCHAR(50) NOT NULL,
    `ward` VARCHAR(50) NULL,
    `street` VARCHAR(100) NULL,
    `address` VARCHAR(255) NOT NULL,
    `landmark` VARCHAR(255) NULL,
    `latitude` DECIMAL(10, 7) NULL,
    `longitude` DECIMAL(10, 7) NULL,
    `is_default` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_customer_addr_customer` (`customer_id`),
    CONSTRAINT `fk_addr_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");
echo "  ✓ Table `customer_addresses` verified\n";

// Table: carts
$pdo->exec("
CREATE TABLE IF NOT EXISTS `carts` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `customer_id` INT(11) NULL,
    `session_id` VARCHAR(100) NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_cart_customer` (`customer_id`),
    KEY `idx_cart_session` (`session_id`),
    CONSTRAINT `fk_cart_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");
echo "  ✓ Table `carts` verified\n";

// Table: cart_items
$pdo->exec("
CREATE TABLE IF NOT EXISTS `cart_items` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `cart_id` INT(11) NOT NULL,
    `product_id` INT(11) NOT NULL,
    `quantity` INT(11) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_cart_product` (`cart_id`, `product_id`),
    KEY `idx_ci_product` (`product_id`),
    CONSTRAINT `fk_ci_cart` FOREIGN KEY (`cart_id`) REFERENCES `carts` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_ci_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");
echo "  ✓ Table `cart_items` verified\n";

// Table: online_orders
$pdo->exec("
CREATE TABLE IF NOT EXISTS `online_orders` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `order_number` VARCHAR(50) NOT NULL,
    `customer_id` INT(11) NOT NULL,
    `sale_id` INT(11) NULL,
    `invoice_no` VARCHAR(50) NULL,
    `status` ENUM('PENDING', 'PAYMENT_PENDING', 'PAID', 'CONFIRMED', 'PROCESSING', 'READY_FOR_DELIVERY', 'OUT_FOR_DELIVERY', 'DELIVERED', 'COMPLETED', 'CANCELLED', 'REFUNDED') NOT NULL DEFAULT 'PENDING',
    `subtotal` DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
    `discount_amount` DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
    `delivery_fee` DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
    `total_amount` DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
    `payment_method` VARCHAR(50) NOT NULL DEFAULT 'Mobile Money',
    `payment_status` ENUM('unpaid', 'pending', 'paid', 'partially_paid', 'refunded', 'failed') NOT NULL DEFAULT 'unpaid',
    `delivery_method` VARCHAR(50) NOT NULL DEFAULT 'standard',
    `delivery_address_id` INT(11) NULL,
    `delivery_address_snapshot` TEXT NOT NULL,
    `customer_notes` TEXT NULL,
    `admin_notes` TEXT NULL,
    `cancelled_reason` VARCHAR(255) NULL,
    `cancelled_at` DATETIME NULL,
    `confirmed_at` DATETIME NULL,
    `delivered_at` DATETIME NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_online_order_number` (`order_number`),
    KEY `idx_oo_customer` (`customer_id`),
    KEY `idx_oo_sale` (`sale_id`),
    KEY `idx_oo_status` (`status`),
    CONSTRAINT `fk_oo_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
    CONSTRAINT `fk_oo_sale` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");
echo "  ✓ Table `online_orders` verified\n";

// Table: online_order_items
$pdo->exec("
CREATE TABLE IF NOT EXISTS `online_order_items` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `order_id` INT(11) NOT NULL,
    `product_id` INT(11) NOT NULL,
    `product_name` VARCHAR(150) NOT NULL,
    `product_sku` VARCHAR(50) NOT NULL,
    `quantity` INT(11) NOT NULL,
    `unit_cost` DECIMAL(15, 2) NOT NULL,
    `unit_price` DECIMAL(15, 2) NOT NULL,
    `subtotal` DECIMAL(15, 2) NOT NULL,
    `total_cost` DECIMAL(15, 2) NOT NULL,
    `gross_profit` DECIMAL(15, 2) NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_ooi_order` (`order_id`),
    KEY `idx_ooi_product` (`product_id`),
    CONSTRAINT `fk_ooi_order` FOREIGN KEY (`order_id`) REFERENCES `online_orders` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_ooi_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");
echo "  ✓ Table `online_order_items` verified\n";

// Table: order_status_history
$pdo->exec("
CREATE TABLE IF NOT EXISTS `order_status_history` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `order_id` INT(11) NOT NULL,
    `old_status` VARCHAR(50) NULL,
    `new_status` VARCHAR(50) NOT NULL,
    `changed_by_user_id` INT(11) NULL,
    `comment` TEXT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_osh_order` (`order_id`),
    CONSTRAINT `fk_osh_order` FOREIGN KEY (`order_id`) REFERENCES `online_orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");
echo "  ✓ Table `order_status_history` verified\n";

// Table: online_payments
$pdo->exec("
CREATE TABLE IF NOT EXISTS `online_payments` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `payment_reference` VARCHAR(100) NOT NULL,
    `order_id` INT(11) NOT NULL,
    `customer_id` INT(11) NOT NULL,
    `amount` DECIMAL(15, 2) NOT NULL,
    `currency` VARCHAR(10) NOT NULL DEFAULT 'TZS',
    `payment_method` VARCHAR(50) NOT NULL,
    `provider` VARCHAR(50) NOT NULL,
    `provider_reference` VARCHAR(150) NULL,
    `status` ENUM('INITIATED', 'PENDING', 'PROCESSING', 'SUCCESS', 'FAILED', 'CANCELLED', 'EXPIRED', 'REFUNDED') NOT NULL DEFAULT 'INITIATED',
    `idempotency_key` VARCHAR(150) NOT NULL,
    `gateway_response` TEXT NULL,
    `paid_at` DATETIME NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_op_ref` (`payment_reference`),
    UNIQUE KEY `uk_op_idempotency` (`idempotency_key`),
    KEY `idx_op_order` (`order_id`),
    KEY `idx_op_customer` (`customer_id`),
    CONSTRAINT `fk_op_order` FOREIGN KEY (`order_id`) REFERENCES `online_orders` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_op_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");
echo "  ✓ Table `online_payments` verified\n";

// Table: deliveries
$pdo->exec("
CREATE TABLE IF NOT EXISTS `deliveries` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `delivery_number` VARCHAR(50) NOT NULL,
    `order_id` INT(11) NOT NULL,
    `customer_id` INT(11) NOT NULL,
    `address_id` INT(11) NULL,
    `recipient_name` VARCHAR(100) NOT NULL,
    `recipient_phone` VARCHAR(25) NOT NULL,
    `delivery_method` VARCHAR(50) NOT NULL DEFAULT 'standard',
    `delivery_fee` DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
    `delivery_status` ENUM('PENDING', 'PREPARING', 'READY', 'ASSIGNED', 'OUT_FOR_DELIVERY', 'DELIVERED', 'FAILED', 'CANCELLED') NOT NULL DEFAULT 'PENDING',
    `tracking_notes` TEXT NULL,
    `estimated_delivery` DATETIME NULL,
    `delivered_at` DATETIME NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_delivery_number` (`delivery_number`),
    KEY `idx_del_order` (`order_id`),
    KEY `idx_del_status` (`delivery_status`),
    CONSTRAINT `fk_del_order` FOREIGN KEY (`order_id`) REFERENCES `online_orders` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_del_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");
echo "  ✓ Table `deliveries` verified\n";

// Table: delivery_status_history
$pdo->exec("
CREATE TABLE IF NOT EXISTS `delivery_status_history` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `delivery_id` INT(11) NOT NULL,
    `old_status` VARCHAR(50) NULL,
    `new_status` VARCHAR(50) NOT NULL,
    `notes` TEXT NULL,
    `created_by` INT(11) NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_dsh_delivery` (`delivery_id`),
    CONSTRAINT `fk_dsh_delivery` FOREIGN KEY (`delivery_id`) REFERENCES `deliveries` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");
echo "  ✓ Table `delivery_status_history` verified\n";

// Table: customer_wishlists
$pdo->exec("
CREATE TABLE IF NOT EXISTS `customer_wishlists` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `customer_id` INT(11) NOT NULL,
    `product_id` INT(11) NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_wishlist_item` (`customer_id`, `product_id`),
    KEY `idx_cw_product` (`product_id`),
    CONSTRAINT `fk_cw_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_cw_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");
echo "  ✓ Table `customer_wishlists` verified\n";

// Table: product_reviews
$pdo->exec("
CREATE TABLE IF NOT EXISTS `product_reviews` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `customer_id` INT(11) NOT NULL,
    `product_id` INT(11) NOT NULL,
    `order_id` INT(11) NULL,
    `rating` TINYINT(1) NOT NULL DEFAULT 5,
    `review_text` TEXT NOT NULL,
    `status` ENUM('PENDING', 'APPROVED', 'REJECTED') NOT NULL DEFAULT 'PENDING',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_pr_product` (`product_id`),
    KEY `idx_pr_customer` (`customer_id`),
    CONSTRAINT `fk_pr_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_pr_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");
echo "  ✓ Table `product_reviews` verified\n";

// Table: customer_notifications
$pdo->exec("
CREATE TABLE IF NOT EXISTS `customer_notifications` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `customer_id` INT(11) NOT NULL,
    `title` VARCHAR(150) NOT NULL,
    `message` TEXT NOT NULL,
    `type` VARCHAR(50) NOT NULL DEFAULT 'system',
    `is_read` TINYINT(1) NOT NULL DEFAULT 0,
    `action_url` VARCHAR(255) NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_cn_customer` (`customer_id`),
    CONSTRAINT `fk_cn_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");
echo "  ✓ Table `customer_notifications` verified\n";

// 3. Seed Role 'customer' & Permissions
echo "3. Seeding RBAC permissions for Phase 16...\n";

// Ensure 'customer' role exists
$stmt = $pdo->prepare("SELECT id FROM `roles` WHERE name = 'customer'");
$stmt->execute();
if (!$stmt->fetch()) {
    $pdo->exec("INSERT INTO `roles` (`id`, `name`, `display_name`, `description`) VALUES (6, 'customer', 'Customer / Mteja wa Mtandaoni', 'Wateja wanaonunua kupitia duka la mtandaoni')");
    echo "  ✓ Role `customer` created\n";
} else {
    echo "  - Role `customer` already exists\n";
}

$permissions = [
    ['name' => 'online_store.view', 'module' => 'online_store', 'description' => 'Kuangalia Dashibodi ya Duka la Mtandaoni'],
    ['name' => 'online_products.manage', 'module' => 'online_store', 'description' => 'Kuweka na Kurekebisha Mwonekano wa Vipuri Mtandaoni'],
    ['name' => 'online_orders.view', 'module' => 'online_store', 'description' => 'Kuangalia Orodha ya Maagizo ya Mtandaoni'],
    ['name' => 'online_orders.manage', 'module' => 'online_store', 'description' => 'Kuidhinisha na Kuchakata Maagizo ya Mtandaoni'],
    ['name' => 'online_orders.cancel', 'module' => 'online_store', 'description' => 'Kughairi Maagizo ya Mtandaoni'],
    ['name' => 'online_payments.view', 'module' => 'online_store', 'description' => 'Kuangalia Miamala ya Malipo ya Mtandaoni'],
    ['name' => 'online_payments.manage', 'module' => 'online_store', 'description' => 'Kuthibitisha Malipo ya Mtandaoni'],
    ['name' => 'online_delivery.view', 'module' => 'online_store', 'description' => 'Kuangalia Usafirishaji wa Maagizo ya Wateja'],
    ['name' => 'online_delivery.manage', 'module' => 'online_store', 'description' => 'Kupanga na Kurekebisha Hali ya Usafirishaji'],
    ['name' => 'customer_accounts.view', 'module' => 'online_store', 'description' => 'Kuangalia Akaunti za Wateja wa Mtandaoni'],
    ['name' => 'customer_accounts.manage', 'module' => 'online_store', 'description' => 'Kudhibiti na Kubadili Hali ya Wateja wa Mtandaoni'],
    ['name' => 'customer_returns.manage', 'module' => 'online_store', 'description' => 'Kuidhinisha na Kuchakata Marejesho ya Mtandaoni']
];

$permStmt = $pdo->prepare("
    INSERT INTO `permissions` (`name`, `module`, `description`)
    VALUES (?, ?, ?)
    ON DUPLICATE KEY UPDATE `description` = VALUES(`description`), `module` = VALUES(`module`)
");

foreach ($permissions as $p) {
    $permStmt->execute([$p['name'], $p['module'], $p['description']]);
}
echo "  ✓ Seeded " . count($permissions) . " granular permissions for Online Store\n";

// Map to Super Admin & Manager
$rolesMap = $pdo->query("SELECT id, name FROM `roles`")->fetchAll(PDO::FETCH_KEY_PAIR);
$roleIdByName = array_flip($rolesMap);
$permMap = $pdo->query("SELECT name, id FROM `permissions`")->fetchAll(PDO::FETCH_KEY_PAIR);

$rolePermInsert = $pdo->prepare("INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`) VALUES (?, ?)");

if (isset($roleIdByName['super_admin'])) {
    $adminId = $roleIdByName['super_admin'];
    foreach ($permissions as $p) {
        if (isset($permMap[$p['name']])) {
            $rolePermInsert->execute([$adminId, $permMap[$p['name']]]);
        }
    }
    echo "  ✓ Super Admin mapped to all Phase 16 permissions\n";
}

if (isset($roleIdByName['manager'])) {
    $mgrId = $roleIdByName['manager'];
    $mgrPerms = [
        'online_store.view', 'online_products.manage', 'online_orders.view', 'online_orders.manage',
        'online_payments.view', 'online_payments.manage', 'online_delivery.view', 'online_delivery.manage',
        'customer_accounts.view', 'customer_returns.manage'
    ];
    foreach ($mgrPerms as $pName) {
        if (isset($permMap[$pName])) {
            $rolePermInsert->execute([$mgrId, $permMap[$pName]]);
        }
    }
    echo "  ✓ Manager mapped to operational Phase 16 permissions\n";
}

// 4. Seed Default Settings for E-Commerce
echo "4. Seeding E-Commerce configuration settings...\n";
$defaultSettings = [
    'online_store_enabled' => ['1', 'Kuwasha au Kuzima Duka la Mtandaoni (1=Washa, 0=Zima)'],
    'online_store_title' => ['Ngenzi Moto Spare Parts — Duka Rasmi la Mtandaoni', 'Kichwa cha Duka la Mtandaoni'],
    'online_store_description' => ['Nunua vipuri halisi vya Pikipiki (Boxer, TVS, Honda, SanLG) na Bajaji (TVS King, RE 4S) Dar es Salaam kwa bei nafuu.', 'Maelezo ya Duka la Mtandaoni'],
    'delivery_flat_rate' => ['5000.00', 'Gharama ya Kawaida ya Usafirishaji Dar es Salaam (TSh)'],
    'free_delivery_threshold' => ['100000.00', 'Kiwango cha Ununuzi wa Kupata Usafirishaji wa Bure (TSh)'],
    'lipa_namba_mpesa' => ['5522114', 'Lipa Namba ya M-Pesa (Vodacom)'],
    'lipa_namba_tigo' => ['7744112', 'Lipa Namba ya Tigo Pesa'],
    'lipa_namba_airtel' => ['8899221', 'Lipa Namba ya Airtel Money'],
    'bank_transfer_instructions' => ['Benki ya CRDB | Jina: NGENZI MOTO SPARE PARTS | Akaunti: 0152899472600 (Tawi la Kariakoo)', 'Maelekezo ya Malipo ya Benki'],
    'online_return_policy_days' => ['3', 'Muda wa mwisho wa kuomba kurejesha kipuri cha mtandaoni (Siku)']
];

$checkSetting = $pdo->prepare("SELECT id FROM `settings` WHERE `setting_key` = ?");
$insertSetting = $pdo->prepare("INSERT INTO `settings` (`setting_key`, `setting_value`, `description`) VALUES (?, ?, ?)");

$settingsAdded = 0;
foreach ($defaultSettings as $key => $data) {
    $checkSetting->execute([$key]);
    if (!$checkSetting->fetch()) {
        $insertSetting->execute([$key, $data[0], $data[1]]);
        $settingsAdded++;
    }
}
echo "  ✓ Seeded {$settingsAdded} new e-commerce settings\n";

echo "\n=== Phase 16 Migration Completed Successfully! ===\n";
