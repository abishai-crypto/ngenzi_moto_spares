<?php
/**
 * NGENZI MOTO SPARE PARTS
 * Phase 17 Migration: Optional Bolt Delivery, Flexible Shipping, Expanded Catalog & Advanced E-Commerce
 * Dar es Salaam, Tanzania
 */

require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/core/Database.php';

use App\Core\Database;

$pdo = Database::getConnection();

echo "=== Starting Phase 17 Migration ===\n";

// Helper function: Add column if not exists
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

// 1. Extend `deliveries` table
echo "1. Extending `deliveries` table for flexible shipping providers...\n";
addColumnIfNotExists($pdo, 'deliveries', 'delivery_provider', "VARCHAR(50) NOT NULL DEFAULT 'STORE_DELIVERY' AFTER `delivery_method`");
addColumnIfNotExists($pdo, 'deliveries', 'external_reference', "VARCHAR(150) NULL AFTER `delivery_provider`");
addColumnIfNotExists($pdo, 'deliveries', 'driver_name', "VARCHAR(100) NULL AFTER `external_reference`");
addColumnIfNotExists($pdo, 'deliveries', 'driver_phone', "VARCHAR(25) NULL AFTER `driver_name`");
addColumnIfNotExists($pdo, 'deliveries', 'vehicle_info', "VARCHAR(100) NULL AFTER `driver_phone`");
addColumnIfNotExists($pdo, 'deliveries', 'courier_name', "VARCHAR(100) NULL AFTER `vehicle_info`");
addColumnIfNotExists($pdo, 'deliveries', 'courier_phone', "VARCHAR(25) NULL AFTER `courier_name`");
addColumnIfNotExists($pdo, 'deliveries', 'courier_instructions', "TEXT NULL AFTER `courier_phone`");
addColumnIfNotExists($pdo, 'deliveries', 'pickup_instructions', "TEXT NULL AFTER `courier_instructions`");

// Convert delivery_status from ENUM to VARCHAR(50) to support full lifecycle transitions
try {
    $pdo->exec("ALTER TABLE `deliveries` MODIFY COLUMN `delivery_status` VARCHAR(50) NOT NULL DEFAULT 'PENDING'");
    echo "  ✓ Modified `deliveries.delivery_status` to VARCHAR(50) for lifecycle flexibility\n";
} catch (Exception $e) {
    echo "  - Note on delivery_status: " . $e->getMessage() . "\n";
}

// 2. Extend `online_orders` table
echo "2. Extending `online_orders` table...\n";
addColumnIfNotExists($pdo, 'online_orders', 'delivery_provider', "VARCHAR(50) NOT NULL DEFAULT 'STORE_DELIVERY' AFTER `delivery_method`");
addColumnIfNotExists($pdo, 'online_orders', 'delivery_reference', "VARCHAR(150) NULL AFTER `delivery_provider`");

// 3. Extend `products` table for catalog & availability
echo "3. Extending `products` table...\n";
addColumnIfNotExists($pdo, 'products', 'is_catalog_only', "TINYINT(1) NOT NULL DEFAULT 0 AFTER `is_online`");
addColumnIfNotExists($pdo, 'products', 'availability_status', "VARCHAR(50) NOT NULL DEFAULT 'in_stock' AFTER `is_catalog_only`");
addColumnIfNotExists($pdo, 'products', 'product_type', "VARCHAR(100) NULL AFTER `online_description`");

// 4. Create `product_images` table for multiple images
echo "4. Creating `product_images` table...\n";
$pdo->exec("
CREATE TABLE IF NOT EXISTS `product_images` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `product_id` INT(11) NOT NULL,
    `image_url` VARCHAR(255) NOT NULL,
    `alt_text` VARCHAR(255) NULL,
    `sort_order` INT(11) NOT NULL DEFAULT 0,
    `is_primary` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_pi_product` (`product_id`),
    CONSTRAINT `fk_pi_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");
echo "  ✓ Table `product_images` verified\n";

// 5. Create `product_demand_events` table for Owner Assistance / Discovery
echo "5. Creating `product_demand_events` table...\n";
$pdo->exec("
CREATE TABLE IF NOT EXISTS `product_demand_events` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `product_id` INT(11) NULL,
    `search_term` VARCHAR(255) NULL,
    `vehicle_model_id` INT(11) NULL,
    `category_id` INT(11) NULL,
    `event_type` ENUM('search', 'compatibility_search', 'out_of_stock_request', 'wishlist', 'view', 'order') NOT NULL,
    `customer_id` INT(11) NULL,
    `ip_address` VARCHAR(45) NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_pde_product` (`product_id`),
    KEY `idx_pde_type` (`event_type`),
    KEY `idx_pde_search` (`search_term`(50)),
    CONSTRAINT `fk_pde_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_pde_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");
echo "  ✓ Table `product_demand_events` verified\n";

// 6. Seed granular permissions for Phase 17
echo "6. Seeding RBAC permissions for Phase 17...\n";
$phase17Permissions = [
    ['name' => 'online_delivery.dispatch', 'module' => 'online_store', 'description' => 'Kugawa dereva, msafirishaji au Bolt kwa ajili ya usafirishaji'],
    ['name' => 'online_catalog.manage', 'module' => 'online_store', 'description' => 'Kusimamia katalogi ya mtandaoni na picha za vipuri'],
    ['name' => 'online_demand.view', 'module' => 'online_store', 'description' => 'Kuangalia takwimu za mahitaji ya vipuri (Demand Discovery)']
];

$permStmt = $pdo->prepare("
    INSERT INTO `permissions` (`name`, `module`, `description`)
    VALUES (?, ?, ?)
    ON DUPLICATE KEY UPDATE `description` = VALUES(`description`), `module` = VALUES(`module`)
");

foreach ($phase17Permissions as $p) {
    $permStmt->execute([$p['name'], $p['module'], $p['description']]);
}

$permMap = $pdo->query("SELECT name, id FROM `permissions`")->fetchAll(PDO::FETCH_KEY_PAIR);
$rolesMap = $pdo->query("SELECT id, name FROM `roles`")->fetchAll(PDO::FETCH_KEY_PAIR);
$roleIdByName = array_flip($rolesMap);
$rolePermInsert = $pdo->prepare("INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`) VALUES (?, ?)");

if (isset($roleIdByName['super_admin'])) {
    $adminId = $roleIdByName['super_admin'];
    foreach ($phase17Permissions as $p) {
        if (isset($permMap[$p['name']])) {
            $rolePermInsert->execute([$adminId, $permMap[$p['name']]]);
        }
    }
}
if (isset($roleIdByName['manager'])) {
    $mgrId = $roleIdByName['manager'];
    foreach ($phase17Permissions as $p) {
        if (isset($permMap[$p['name']])) {
            $rolePermInsert->execute([$mgrId, $permMap[$p['name']]]);
        }
    }
}
echo "  ✓ Seeded Phase 17 permissions mapped to Super Admin & Manager\n";

// 7. Seed Phase 17 Settings
echo "7. Seeding Phase 17 delivery & catalog settings...\n";
$settings17 = [
    'delivery_store_enabled' => ['1', 'Kuwasha Usafirishaji wa Duka (Store Delivery)'],
    'delivery_bolt_enabled' => ['1', 'Kuwasha Chaguo la Usafirishaji la Bolt (1=Washa, 0=Zima)'],
    'delivery_pickup_enabled' => ['1', 'Kuwasha Mteja Kujichukulia Mwenyewe Dukani (Pickup)'],
    'delivery_own_courier_enabled' => ['1', 'Kuwasha Msafirishaji Binafsi wa Mteja (My Own Courier)'],
    'delivery_other_courier_enabled' => ['1', 'Kuwasha Wasafirishaji Wengine (Other Courier)'],
    'store_pickup_instructions' => ['Chukua bidhaa zako dukani Ngenzi Moto Spare Parts, Kariakoo Dar es Salaam. Fungua kuanzia saa 2:00 asubuhi hadi 12:00 jioni.', 'Maelekezo ya Kujichukulia Mzigo Dukani'],
    'delivery_notes' => ['Usafirishaji hufanyika ndani ya masaa 2-24 jijini Dar es Salaam kulingana na umbali.', 'Maelezo ya Usafirishaji kwa Wateja'],
    'bolt_api_configured' => ['0', 'Hali ya API ya Bolt (0=Haijaunganishwa/Mwongozo, 1=Imeunganishwa)'],
    'bolt_api_key' => ['', 'Ufunguo wa API ya Bolt (Bolt API Key)'],
    'bolt_api_url' => ['', 'Anwani ya API ya Bolt'],
    'catalog_display_exact_stock' => ['0', 'Kuonyesha Idadi Halisi ya Vipuri Stoo kwa Umma (0=Ficha, 1=Onyesha)'],
    'notification_email_enabled' => ['0', 'Kuwasha Barua Pepe ya Taarifa kwa Wateja'],
    'notification_sms_enabled' => ['0', 'Kuwasha SMS ya Taarifa kwa Wateja'],
    'notification_whatsapp_enabled' => ['0', 'Kuwasha WhatsApp ya Taarifa kwa Wateja'],
    'customer_support_phone' => ['+255 754 123 456', 'Namba ya Huduma kwa Wateja ya Ngenzi']
];

$checkSetting = $pdo->prepare("SELECT id FROM `settings` WHERE `setting_key` = ?");
$insertSetting = $pdo->prepare("INSERT INTO `settings` (`setting_key`, `setting_value`, `description`) VALUES (?, ?, ?)");
$settingsCount = 0;

foreach ($settings17 as $key => $data) {
    $checkSetting->execute([$key]);
    if (!$checkSetting->fetch()) {
        $insertSetting->execute([$key, $data[0], $data[1]]);
        $settingsCount++;
    }
}
echo "  ✓ Seeded {$settingsCount} new settings for Phase 17\n";

// 8. Seed Expanded Motorcycle & Bajaj Reference Catalog Items (Catalog Only, 0 Stock, 0 Financial Impact)
echo "8. Seeding expanded reference spare-parts catalog (Catalog Only)...\n";
$referenceCatalogItems = [
    // Engine Parts
    [
        'sku' => 'CAT-ENG-001',
        'name' => 'Piston Kit Standard (Pistoni na Rings)',
        'category_code' => 'ENG',
        'product_type' => 'Engine Parts',
        'description' => 'Katalogi ya Pistoni na pete zake kwa ajili ya Boxer BM 150 na TVS HLX 150.',
        'vehicles' => [1, 2] // Boxer BM 150, Boxer BM 100
    ],
    [
        'sku' => 'CAT-ENG-002',
        'name' => 'Cylinder Block Kit (Silinda na Pistoni)',
        'category_code' => 'ENG',
        'product_type' => 'Engine Parts',
        'description' => 'Silinda kamili yenye pistoni na gaskets kwa ajili ya injini za pikipiki na bajaji.',
        'vehicles' => [1, 5] // Boxer BM 150, TVS King
    ],
    [
        'sku' => 'CAT-ENG-003',
        'name' => 'Complete Engine Gasket Kit (Seti ya Gasket)',
        'category_code' => 'ENG',
        'product_type' => 'Engine Parts',
        'description' => 'Seti kamili ya gaskets za injini kuzuia kuvuja kwa oili na mgandamizo.',
        'vehicles' => [1, 3, 5]
    ],
    [
        'sku' => 'CAT-ENG-004',
        'name' => 'Timing Chain & Tensioner Set',
        'category_code' => 'ENG',
        'product_type' => 'Engine Parts',
        'description' => 'Mnyororo wa taimingi wa ndani na kifaa cha kukaza (tensioner).',
        'vehicles' => [1, 4]
    ],
    [
        'sku' => 'CAT-ENG-005',
        'name' => 'Intake & Exhaust Valves (Vali za Hewa na Moshi)',
        'category_code' => 'ENG',
        'product_type' => 'Engine Parts',
        'description' => 'Seti ya vali imara za chuma zenye kuhimili joto kali la chumba cha mwako.',
        'vehicles' => [1, 2, 5]
    ],

    // Clutch & Transmission
    [
        'sku' => 'CAT-CLT-001',
        'name' => 'Heavy Duty Clutch Plate Set',
        'category_code' => 'CLT',
        'product_type' => 'Clutch & Transmission',
        'description' => 'Bati za klachi za kiwango cha juu cha msuguano kwa pikipiki za biashara.',
        'vehicles' => [1, 3]
    ],
    [
        'sku' => 'CAT-CLT-002',
        'name' => 'Clutch Pressure Plate & Springs',
        'category_code' => 'CLT',
        'product_type' => 'Clutch & Transmission',
        'description' => 'Bamba la mgandamizo wa klachi na chemchemi zake kwa usalama wa gia.',
        'vehicles' => [1, 5]
    ],
    [
        'sku' => 'CAT-CLT-003',
        'name' => 'Clutch Cable Assembly',
        'category_code' => 'CBL',
        'product_type' => 'Clutch & Transmission',
        'description' => 'Waya imara wa klachi wenye kifuniko cha kuzuia vumbi na maji.',
        'vehicles' => [1, 2, 4]
    ],

    // Brake System
    [
        'sku' => 'CAT-BRK-001',
        'name' => 'Ceramic Front Brake Pads',
        'category_code' => 'BRK',
        'product_type' => 'Brake System',
        'description' => 'Pads za kisasa za breki za kauri zisizopiga kelele na zenye kushika kwa nguvu.',
        'vehicles' => [1, 3, 4]
    ],
    [
        'sku' => 'CAT-BRK-002',
        'name' => 'Rear Brake Shoe Set (Viatu vya Breki)',
        'category_code' => 'BRK',
        'product_type' => 'Brake System',
        'description' => 'Seti ya viatu vya breki ya nyuma vyenye asbestosi salama kwa barabara za vumbi.',
        'vehicles' => [1, 2, 5]
    ],
    [
        'sku' => 'CAT-BRK-003',
        'name' => 'Front Brake Disc Rotor',
        'category_code' => 'BRK',
        'product_type' => 'Brake System',
        'description' => 'Diski ya chuma cha pua iliyotobolewa kwa ajili ya kupooza joto haraka.',
        'vehicles' => [1, 3]
    ],
    [
        'sku' => 'CAT-BRK-004',
        'name' => 'Brake Master Cylinder Pump',
        'category_code' => 'BRK',
        'product_type' => 'Brake System',
        'description' => 'Bomba kuu la mafuta ya breki lenye kiwambo cha kuangalia kiwango cha mafuta.',
        'vehicles' => [1, 4]
    ],

    // Electrical
    [
        'sku' => 'CAT-ELE-001',
        'name' => 'Maintenance Free 12V Battery',
        'category_code' => 'ELE',
        'product_type' => 'Electrical',
        'description' => 'Betri ya gel ya 12V isiyohitaji kuongezwa asidi au maji ya betri.',
        'vehicles' => [1, 2, 3, 4, 5]
    ],
    [
        'sku' => 'CAT-ELE-002',
        'name' => 'Digital CDI Unit',
        'category_code' => 'ELE',
        'product_type' => 'Electrical',
        'description' => 'Kifaa cha kielektroniki cha kuratibu cheche za plagi kwa ufanisi wa mafuta.',
        'vehicles' => [1, 2]
    ],
    [
        'sku' => 'CAT-ELE-003',
        'name' => 'High Output Stator Coil / Magneto',
        'category_code' => 'ELE',
        'product_type' => 'Electrical',
        'description' => 'Koili ya chaji ya umeme na taa yenye nyaya safi za shaba.',
        'vehicles' => [1, 5]
    ],
    [
        'sku' => 'CAT-ELE-004',
        'name' => 'LED Headlight Assembly',
        'category_code' => 'ELE',
        'product_type' => 'Electrical',
        'description' => 'Taa ya mbele ya LED yenye mwanga mkali wa masafa marefu na mafupi.',
        'vehicles' => [1, 3]
    ],

    // Fuel System
    [
        'sku' => 'CAT-FLT-001',
        'name' => 'Standard Carburetor Assembly',
        'category_code' => 'ENG',
        'product_type' => 'Fuel System',
        'description' => 'Kabureta halisi yenye uwezo wa kurekebisha hewa na mafuta kwa usahihi.',
        'vehicles' => [1, 2]
    ],
    [
        'sku' => 'CAT-FLT-002',
        'name' => 'High Flow Fuel Filter',
        'category_code' => 'FLT',
        'product_type' => 'Fuel System',
        'description' => 'Kichungi cha petroli kinachoondoa chembechembe na maji kwenye tanki.',
        'vehicles' => [1, 2, 3, 4, 5]
    ],

    // Suspension & Steering
    [
        'sku' => 'CAT-SUS-001',
        'name' => 'Front Telescopic Fork Absorber',
        'category_code' => 'SUS',
        'product_type' => 'Suspension & Steering',
        'description' => 'Shock absorber ya mbele inayohimili mashimo na mizigo mizito.',
        'vehicles' => [1, 2, 3]
    ],
    [
        'sku' => 'CAT-SUS-002',
        'name' => 'Heavy Duty Rear Shock Absorber Pair',
        'category_code' => 'SUS',
        'product_type' => 'Suspension & Steering',
        'description' => 'Jozi ya shock absorbers za nyuma zenye springi nene za kubeba abiria na mizigo.',
        'vehicles' => [1, 2]
    ],
    [
        'sku' => 'CAT-SUS-003',
        'name' => 'Tapered Steering Stem Bearing Set',
        'category_code' => 'BRG',
        'product_type' => 'Suspension & Steering',
        'description' => 'Bearings za usukani za koni kwa uendeshaji laini bila kutikisika.',
        'vehicles' => [1, 4]
    ],

    // Wheels & Chain
    [
        'sku' => 'CAT-CHN-001',
        'name' => 'Heavy Duty Drive Chain & Sprocket Kit (428H)',
        'category_code' => 'CHN',
        'product_type' => 'Wheels & Tyres',
        'description' => 'Seti ya mnyororo mnene wa 428H na sproketi za mbele na nyuma.',
        'vehicles' => [1, 2, 3]
    ],

    // Bajaj Three-Wheeler Specific
    [
        'sku' => 'CAT-BAJ-001',
        'name' => 'Bajaj RE 4S Differential Gear Set',
        'category_code' => 'CLT',
        'product_type' => 'Bajaj Three-Wheeler Specific',
        'description' => 'Gia kamili ya difrensho kwa ajili ya bajaji za abiria na mizigo za Bajaj RE na TVS King.',
        'vehicles' => [5]
    ],
    [
        'sku' => 'CAT-BAJ-002',
        'name' => 'Bajaj Rear Axle Shaft Assembly',
        'category_code' => 'CLT',
        'product_type' => 'Bajaj Three-Wheeler Specific',
        'description' => 'Ekseli ya nyuma ya kuendeshea magurudumu ya bajaji.',
        'vehicles' => [5]
    ],
    [
        'sku' => 'CAT-BAJ-003',
        'name' => 'Bajaj Heavy Duty Brake Master Cylinder (Tricycle)',
        'category_code' => 'BRK',
        'product_type' => 'Bajaj Three-Wheeler Specific',
        'description' => 'Bomba kuu la breki za miguu za bajaji za magurudumu matatu.',
        'vehicles' => [5]
    ],
    [
        'sku' => 'CAT-BAJ-004',
        'name' => 'Bajaj Passenger Canopy Weather Shield',
        'category_code' => 'BDY',
        'product_type' => 'Bajaj Three-Wheeler Specific',
        'description' => 'Paa na turubai imara la kuzuia mvua na jua kwa abiria wa bajaji.',
        'vehicles' => [5]
    ]
];

// Look up category codes to IDs
$catMap = $pdo->query("SELECT code, id FROM `categories`")->fetchAll(PDO::FETCH_KEY_PAIR);
$defaultUnitId = (int)$pdo->query("SELECT id FROM `units` LIMIT 1")->fetchColumn() ?: 1;

$checkProduct = $pdo->prepare("SELECT id FROM `products` WHERE sku = ?");
$insertProduct = $pdo->prepare("
    INSERT INTO `products` (
        sku, name, description, online_description, category_id, unit_id,
        buying_price, selling_price, recommended_selling_price, minimum_selling_price,
        target_margin, current_stock, min_stock_level, reorder_level, max_stock_level,
        status, is_online, is_catalog_only, availability_status, product_type, created_at
    ) VALUES (
        ?, ?, ?, ?, ?, ?,
        0.00, 0.00, 0.00, 0.00,
        30.00, 0, 0, 0, 50,
        'active', 1, 1, 'catalog_only', ?, NOW()
    )
");

$insertCompat = $pdo->prepare("INSERT IGNORE INTO `product_vehicle_compatibility` (product_id, vehicle_model_id, notes) VALUES (?, ?, ?)");

$catalogAdded = 0;
foreach ($referenceCatalogItems as $item) {
    $checkProduct->execute([$item['sku']]);
    $prodId = $checkProduct->fetchColumn();

    if (!$prodId) {
        $catId = $catMap[$item['category_code']] ?? 1;
        $insertProduct->execute([
            $item['sku'],
            $item['name'],
            $item['description'],
            $item['description'],
            $catId,
            $defaultUnitId,
            $item['product_type']
        ]);
        $prodId = (int)$pdo->lastInsertId();
        $catalogAdded++;

        // Map compatibility
        if (!empty($item['vehicles'])) {
            foreach ($item['vehicles'] as $vId) {
                $insertCompat->execute([$prodId, $vId, 'Catalog Reference Compatibility']);
            }
        }
    }
}
echo "  ✓ Seeded {$catalogAdded} reference catalog items with vehicle compatibility\n";

echo "\n=== Phase 17 Migration Completed Successfully! ===\n";
