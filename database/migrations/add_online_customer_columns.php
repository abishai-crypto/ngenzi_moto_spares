<?php
/**
 * Migration: Add Online Registration Columns to Customers Table (Phase 18)
 * Ensures online customers are clearly identified in phpMyAdmin and staff dashboard
 */

require_once __DIR__ . '/../../app/core/Database.php';
require_once __DIR__ . '/../../app/core/Env.php';
\App\Core\Env::init();

$pdo = \App\Core\Database::getConnection();

echo "=== STARTING CUSTOMER ONLINE COLUMNS MIGRATION ===\n";

// 1. Check if is_online_registered exists
$cols = $pdo->query("SHOW COLUMNS FROM customers")->fetchAll(PDO::FETCH_COLUMN);

if (!in_array('is_online_registered', $cols)) {
    echo "Adding 'is_online_registered' column...\n";
    $pdo->exec("ALTER TABLE `customers` ADD COLUMN `is_online_registered` TINYINT(1) NOT NULL DEFAULT 0 AFTER `status`");
    echo "[OK] 'is_online_registered' added.\n";
} else {
    echo "[EXISTS] 'is_online_registered' already exists.\n";
}

if (!in_array('registration_channel', $cols)) {
    echo "Adding 'registration_channel' column...\n";
    $pdo->exec("ALTER TABLE `customers` ADD COLUMN `registration_channel` VARCHAR(30) NOT NULL DEFAULT 'IN_STORE' AFTER `is_online_registered`");
    echo "[OK] 'registration_channel' added.\n";
} else {
    echo "[EXISTS] 'registration_channel' already exists.\n";
}

// 2. Modify customer_type enum to include 'online'
try {
    echo "Updating customer_type enum to include 'online'...\n";
    $pdo->exec("ALTER TABLE `customers` MODIFY COLUMN `customer_type` ENUM('individual','retail','mechanic','garage','business','walk_in','reseller','dealer','online','other') NOT NULL DEFAULT 'retail'");
    echo "[OK] customer_type enum updated with 'online'.\n";
} catch (Exception $e) {
    echo "Notice: enum update note: " . $e->getMessage() . "\n";
}

// 3. Mark all customers who registered online with password or have online orders
$updateStmt = $pdo->prepare("
    UPDATE `customers`
    SET `is_online_registered` = 1,
        `registration_channel` = 'ONLINE_STORE',
        `customer_type` = 'online'
    WHERE `password` IS NOT NULL OR `id` IN (SELECT DISTINCT `customer_id` FROM `online_orders`)
");
$updateStmt->execute();
$updatedCount = $updateStmt->rowCount();
echo "[OK] Marked {$updatedCount} customers as online registered (customer_type = 'online', registration_channel = 'ONLINE_STORE').\n";

// 4. Verify results
$stmt = $pdo->query("SELECT id, customer_code, name, phone, email, customer_type, is_online_registered, registration_channel, created_at FROM customers WHERE is_online_registered = 1 ORDER BY id DESC LIMIT 10");
echo "\n=== LATEST ONLINE CUSTOMERS IN DATABASE ===\n";
while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "#{$r['id']} | Code: {$r['customer_code']} | Name: {$r['name']} | Type: {$r['customer_type']} | Channel: {$r['registration_channel']} | Created: {$r['created_at']}\n";
}

echo "\n=== MIGRATION COMPLETE ===\n";
