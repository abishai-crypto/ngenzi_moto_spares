-- ==========================================================
-- NGENZI MOTO SPARE PARTS DATABASE SCHEMA (PHASE 2)
-- Dar es Salaam, Tanzania
-- Designed for MySQL / MariaDB (Port: 3307)
-- Normalized to 3NF, Full Foreign Keys, Indexes & Decimal Types
-- ==========================================================

CREATE DATABASE IF NOT EXISTS `ngenzi_moto_spare` 
DEFAULT CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE `ngenzi_moto_spare`;

-- 1. ROLES
CREATE TABLE IF NOT EXISTS `roles` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(50) NOT NULL UNIQUE,
    `display_name` VARCHAR(100) NOT NULL,
    `description` VARCHAR(255) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 2. PERMISSIONS
CREATE TABLE IF NOT EXISTS `permissions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL UNIQUE,
    `module` VARCHAR(50) NOT NULL,
    `description` VARCHAR(255) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 3. ROLE_PERMISSIONS
CREATE TABLE IF NOT EXISTS `role_permissions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `role_id` INT NOT NULL,
    `permission_id` INT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_role_perm` (`role_id`, `permission_id`),
    FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`permission_id`) REFERENCES `permissions`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 4. USERS
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `role_id` INT NOT NULL,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `full_name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) NULL UNIQUE,
    `phone` VARCHAR(20) NULL,
    `password` VARCHAR(255) NOT NULL,
    `status` ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    `last_login` DATETIME NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`) ON UPDATE CASCADE
) ENGINE=InnoDB;

-- 5. CATEGORIES
CREATE TABLE IF NOT EXISTS `categories` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL UNIQUE,
    `code` VARCHAR(20) NULL UNIQUE,
    `description` TEXT NULL,
    `status` ENUM('active', 'inactive') DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 6. BRANDS (Spare Part Brands e.g., Bosch, NGK, Boxer, TVS, Rolon, etc.)
CREATE TABLE IF NOT EXISTS `brands` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL UNIQUE,
    `country` VARCHAR(50) NULL,
    `description` VARCHAR(255) NULL,
    `status` ENUM('active', 'inactive') DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 7. UNITS (Piece, Pair, Set, Litre, Roll, Box)
CREATE TABLE IF NOT EXISTS `units` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(50) NOT NULL,
    `short_name` VARCHAR(10) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 8. VEHICLE BRANDS (Motorcycles & Bajaj)
CREATE TABLE IF NOT EXISTS `vehicle_brands` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(50) NOT NULL UNIQUE,
    `vehicle_type` ENUM('Motorcycle', 'Bajaj', 'Both') DEFAULT 'Motorcycle',
    `description` VARCHAR(255) NULL,
    `status` ENUM('active', 'inactive') DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 9. VEHICLE MODELS (e.g. Boxer BM150, TVS HLX 125, Bajaj RE 4S, TVS King)
CREATE TABLE IF NOT EXISTS `vehicle_models` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `brand_id` INT NOT NULL,
    `model_name` VARCHAR(100) NOT NULL,
    `year_from` INT NULL,
    `year_to` INT NULL,
    `engine_capacity` VARCHAR(20) NULL, -- e.g., '150cc', '125cc'
    `vehicle_type` ENUM('Motorcycle', 'Bajaj') NOT NULL,
    `notes` VARCHAR(255) NULL,
    `status` ENUM('active', 'inactive') DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`brand_id`) REFERENCES `vehicle_brands`(`id`) ON UPDATE CASCADE,
    INDEX `idx_veh_model_brand` (`brand_id`)
) ENGINE=InnoDB;

-- 10. SUPPLIERS
CREATE TABLE IF NOT EXISTS `suppliers` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `supplier_code` VARCHAR(50) NOT NULL UNIQUE,
    `name` VARCHAR(100) NOT NULL,
    `contact_person` VARCHAR(100) NULL,
    `phone` VARCHAR(20) NOT NULL,
    `email` VARCHAR(100) NULL,
    `address` VARCHAR(255) NULL,
    `payment_terms` VARCHAR(100) NULL,
    `credit_limit` DECIMAL(15,2) DEFAULT 0.00,
    `balance` DECIMAL(15,2) DEFAULT 0.00,
    `notes` TEXT NULL,
    `status` ENUM('active', 'inactive') DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_supplier_phone` (`phone`)
) ENGINE=InnoDB;

-- 11. PRODUCTS
CREATE TABLE IF NOT EXISTS `products` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `sku` VARCHAR(50) NOT NULL UNIQUE,
    `barcode` VARCHAR(50) NULL UNIQUE,
    `name` VARCHAR(150) NOT NULL,
    `description` TEXT NULL,
    `category_id` INT NOT NULL,
    `brand_id` INT NULL,
    `unit_id` INT NOT NULL,
    `buying_price` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `selling_price` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `recommended_selling_price` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `minimum_selling_price` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `target_margin` DECIMAL(5,2) NOT NULL DEFAULT 30.00, -- 30.00%
    `current_stock` INT NOT NULL DEFAULT 0,
    `min_stock_level` INT NOT NULL DEFAULT 5,
    `reorder_level` INT NOT NULL DEFAULT 10,
    `max_stock_level` INT NOT NULL DEFAULT 100,
    `shelf_location` VARCHAR(50) NULL,
    `warranty_days` INT DEFAULT 0,
    `status` ENUM('active', 'inactive', 'discontinued') DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON UPDATE CASCADE,
    FOREIGN KEY (`brand_id`) REFERENCES `brands`(`id`) ON UPDATE CASCADE,
    FOREIGN KEY (`unit_id`) REFERENCES `units`(`id`) ON UPDATE CASCADE,
    INDEX `idx_product_sku` (`sku`),
    INDEX `idx_product_barcode` (`barcode`),
    INDEX `idx_product_name` (`name`),
    INDEX `idx_product_category` (`category_id`),
    INDEX `idx_product_brand` (`brand_id`)
) ENGINE=InnoDB;

-- 12. PRODUCT VEHICLE COMPATIBILITY (Many-to-Many)
CREATE TABLE IF NOT EXISTS `product_vehicle_compatibility` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `product_id` INT NOT NULL,
    `vehicle_model_id` INT NOT NULL,
    `notes` VARCHAR(100) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_prod_veh` (`product_id`, `vehicle_model_id`),
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`vehicle_model_id`) REFERENCES `vehicle_models`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 13. CUSTOMERS
CREATE TABLE IF NOT EXISTS `customers` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `customer_code` VARCHAR(50) NOT NULL UNIQUE,
    `name` VARCHAR(100) NOT NULL,
    `phone` VARCHAR(20) NULL,
    `alternative_phone` VARCHAR(20) NULL,
    `email` VARCHAR(100) NULL,
    `address` VARCHAR(255) NULL,
    `area` VARCHAR(100) NULL,
    `gender` VARCHAR(20) NULL,
    `customer_type` ENUM('individual', 'retail', 'mechanic', 'garage', 'business', 'walk_in', 'reseller', 'dealer', 'other') DEFAULT 'individual',
    `credit_limit` DECIMAL(15,2) DEFAULT 0.00,
    `credit_allowed` TINYINT(1) NOT NULL DEFAULT 1,
    `payment_terms` VARCHAR(50) NOT NULL DEFAULT '30 days',
    `credit_status` ENUM('active', 'warning', 'blocked') NOT NULL DEFAULT 'active',
    `current_balance` DECIMAL(15,2) DEFAULT 0.00,
    `notes` TEXT NULL,
    `status` ENUM('active', 'inactive', 'blocked') DEFAULT 'active',
    `created_by` INT NULL,
    `updated_by` INT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`updated_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_customer_phone` (`phone`),
    INDEX `idx_customer_code` (`customer_code`)
) ENGINE=InnoDB;

-- 14. GARAGES
CREATE TABLE IF NOT EXISTS `garages` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `garage_code` VARCHAR(50) NULL UNIQUE,
    `garage_name` VARCHAR(150) NOT NULL,
    `owner_name` VARCHAR(100) NULL,
    `phone` VARCHAR(20) NOT NULL,
    `alternative_phone` VARCHAR(20) NULL,
    `email` VARCHAR(100) NULL,
    `location` VARCHAR(150) NOT NULL,
    `area` VARCHAR(100) NULL,
    `address` VARCHAR(255) NULL,
    `specialization` VARCHAR(100) NULL, -- e.g. Boxer, TVS King, Bajaj RE
    `garage_type` VARCHAR(100) NULL,
    `credit_limit` DECIMAL(15,2) DEFAULT 0.00,
    `customer_id` INT NULL,
    `status` ENUM('active', 'inactive') DEFAULT 'active',
    `notes` TEXT NULL,
    `created_by` INT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_garage_code` (`garage_code`)
) ENGINE=InnoDB;

-- 15. MECHANICS
CREATE TABLE IF NOT EXISTS `mechanics` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `mechanic_code` VARCHAR(50) NULL UNIQUE,
    `customer_id` INT NULL,
    `garage_id` INT NULL,
    `name` VARCHAR(100) NOT NULL,
    `phone` VARCHAR(20) NOT NULL,
    `alternative_phone` VARCHAR(20) NULL,
    `area` VARCHAR(100) NULL,
    `specialization` VARCHAR(100) NULL,
    `years_of_experience` INT NULL,
    `notes` TEXT NULL,
    `status` ENUM('active', 'inactive', 'blocked') DEFAULT 'active',
    `created_by` INT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`garage_id`) REFERENCES `garages`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_mech_phone` (`phone`),
    INDEX `idx_mech_code` (`mechanic_code`)
) ENGINE=InnoDB;

-- 15B. CUSTOMER VEHICLES
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

-- 16. PURCHASES
CREATE TABLE IF NOT EXISTS `purchases` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `purchase_no` VARCHAR(50) NOT NULL UNIQUE,
    `supplier_id` INT NOT NULL,
    `invoice_number` VARCHAR(50) NULL,
    `purchase_date` DATE NOT NULL,
    `subtotal` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `transport_cost` DECIMAL(15,2) DEFAULT 0.00,
    `other_costs` DECIMAL(15,2) DEFAULT 0.00,
    `grand_total` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `paid_amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `balance_due` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `status` ENUM('draft', 'ordered', 'received', 'cancelled') DEFAULT 'received',
    `payment_status` ENUM('unpaid', 'partially_paid', 'paid', 'cancelled') DEFAULT 'unpaid',
    `payment_method` VARCHAR(50) DEFAULT 'Cash',
    `notes` TEXT NULL,
    `created_by` INT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`supplier_id`) REFERENCES `suppliers`(`id`) ON UPDATE CASCADE,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON UPDATE CASCADE,
    INDEX `idx_purchase_number` (`purchase_no`),
    INDEX `idx_purchase_date` (`purchase_date`),
    INDEX `idx_purchase_supplier` (`supplier_id`)
) ENGINE=InnoDB;

-- 17. PURCHASE ITEMS
CREATE TABLE IF NOT EXISTS `purchase_items` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `purchase_id` INT NOT NULL,
    `product_id` INT NOT NULL,
    `quantity` INT NOT NULL,
    `unit_cost` DECIMAL(15,2) NOT NULL,
    `transport_cost` DECIMAL(15,2) DEFAULT 0.00,
    `other_cost` DECIMAL(15,2) DEFAULT 0.00,
    `total_cost` DECIMAL(15,2) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`purchase_id`) REFERENCES `purchases`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON UPDATE CASCADE
) ENGINE=InnoDB;

-- 18. SALES (Financial integrity protected - no cascade delete from products or customers)
CREATE TABLE IF NOT EXISTS `sales` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `invoice_no` VARCHAR(50) NOT NULL UNIQUE,
    `customer_id` INT NULL,
    `customer_name` VARCHAR(150) NULL, -- Historical snapshot at sale time
    `customer_phone` VARCHAR(50) NULL, -- Historical snapshot at sale time
    `mechanic_id` INT NULL,
    `subtotal` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `discount_amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `discount_reason` VARCHAR(255) NULL,
    `discount_approved_by` INT NULL,
    `total_amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `total_cogs` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `gross_profit` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `gross_margin_percent` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    `paid_amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `balance_due` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `payment_method` VARCHAR(50) NOT NULL DEFAULT 'Cash',
    `payment_status` ENUM('paid', 'partially_paid', 'unpaid', 'credit') DEFAULT 'paid',
    `sale_status` ENUM('completed', 'cancelled', 'refunded', 'partially_refunded') DEFAULT 'completed',
    `notes` TEXT NULL,
    `credit_override_by` INT NULL,
    `credit_override_reason` VARCHAR(255) NULL,
    `cashier_id` INT NOT NULL,
    `sale_date` DATETIME NOT NULL,
    `due_date` DATE NULL,
    `payment_terms` VARCHAR(50) NULL DEFAULT 'Cash only',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`) ON UPDATE CASCADE,
    FOREIGN KEY (`mechanic_id`) REFERENCES `mechanics`(`id`) ON UPDATE CASCADE,
    FOREIGN KEY (`discount_approved_by`) REFERENCES `users`(`id`) ON UPDATE CASCADE,
    FOREIGN KEY (`credit_override_by`) REFERENCES `users`(`id`) ON UPDATE CASCADE ON DELETE SET NULL,
    FOREIGN KEY (`cashier_id`) REFERENCES `users`(`id`) ON UPDATE CASCADE,
    INDEX `idx_sale_invoice` (`invoice_no`),
    INDEX `idx_sale_date` (`sale_date`),
    INDEX `idx_sale_due_date` (`due_date`),
    INDEX `idx_sale_customer` (`customer_id`),
    INDEX `idx_sale_cashier` (`cashier_id`)
) ENGINE=InnoDB;

-- 19. SALE ITEMS (Crucial: stores historical cost_price_at_sale at time of transaction)
CREATE TABLE IF NOT EXISTS `sale_items` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `sale_id` INT NOT NULL,
    `product_id` INT NOT NULL,
    `product_name` VARCHAR(150) NULL, -- Historical snapshot
    `product_sku` VARCHAR(50) NULL, -- Historical snapshot
    `quantity` INT NOT NULL,
    `unit_cost` DECIMAL(15,2) NOT NULL, -- Historical cost at sale time
    `unit_price` DECIMAL(15,2) NOT NULL,
    `discount_per_unit` DECIMAL(15,2) DEFAULT 0.00,
    `subtotal` DECIMAL(15,2) NOT NULL,
    `total_cost` DECIMAL(15,2) NOT NULL,
    `gross_profit` DECIMAL(15,2) NOT NULL,
    `gross_margin_percent` DECIMAL(5,2) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`sale_id`) REFERENCES `sales`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- 20. SALE PAYMENTS
CREATE TABLE IF NOT EXISTS `sale_payments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `payment_no` VARCHAR(50) NULL UNIQUE,
    `sale_id` INT NOT NULL,
    `customer_id` INT NULL,
    `amount` DECIMAL(15,2) NOT NULL,
    `payment_method` VARCHAR(50) NOT NULL,
    `provider` VARCHAR(50) NULL,
    `reference_no` VARCHAR(100) NULL,
    `payment_date` DATETIME NOT NULL,
    `received_by` INT NOT NULL,
    `notes` VARCHAR(255) NULL,
    `status` ENUM('completed', 'reversed') NOT NULL DEFAULT 'completed',
    `reversal_reason` VARCHAR(255) NULL,
    `reversed_by` INT NULL,
    `reversed_at` DATETIME NULL,
    `parent_payment_id` INT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`sale_id`) REFERENCES `sales`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`) ON UPDATE CASCADE ON DELETE SET NULL,
    FOREIGN KEY (`received_by`) REFERENCES `users`(`id`) ON UPDATE CASCADE,
    FOREIGN KEY (`reversed_by`) REFERENCES `users`(`id`) ON UPDATE CASCADE ON DELETE SET NULL,
    FOREIGN KEY (`parent_payment_id`) REFERENCES `sale_payments`(`id`) ON UPDATE CASCADE ON DELETE SET NULL,
    INDEX `idx_sp_payment_no` (`payment_no`),
    INDEX `idx_sp_sale` (`sale_id`),
    INDEX `idx_sp_customer` (`customer_id`),
    INDEX `idx_sp_date` (`payment_date`)
) ENGINE=InnoDB;

-- 21. SUPPLIER PAYMENTS
CREATE TABLE IF NOT EXISTS `supplier_payments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `supplier_id` INT NOT NULL,
    `purchase_id` INT NULL,
    `payment_method` VARCHAR(50) NOT NULL,
    `amount` DECIMAL(15,2) NOT NULL,
    `reference_no` VARCHAR(100) NULL,
    `payment_date` DATETIME NOT NULL,
    `paid_by` INT NOT NULL,
    `notes` VARCHAR(255) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`supplier_id`) REFERENCES `suppliers`(`id`) ON UPDATE CASCADE,
    FOREIGN KEY (`purchase_id`) REFERENCES `purchases`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`paid_by`) REFERENCES `users`(`id`) ON UPDATE CASCADE
) ENGINE=InnoDB;

-- 22. CUSTOMER PAYMENTS (Debt payments)
CREATE TABLE IF NOT EXISTS `customer_payments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `payment_no` VARCHAR(50) NULL UNIQUE,
    `customer_id` INT NOT NULL,
    `sale_id` INT NULL,
    `payment_method` VARCHAR(50) NOT NULL,
    `provider` VARCHAR(50) NULL,
    `amount` DECIMAL(15,2) NOT NULL,
    `reference_no` VARCHAR(100) NULL,
    `payment_date` DATETIME NOT NULL,
    `received_by` INT NOT NULL,
    `notes` VARCHAR(255) NULL,
    `status` ENUM('completed', 'reversed') NOT NULL DEFAULT 'completed',
    `reversal_reason` VARCHAR(255) NULL,
    `reversed_by` INT NULL,
    `reversed_at` DATETIME NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`) ON UPDATE CASCADE,
    FOREIGN KEY (`sale_id`) REFERENCES `sales`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`received_by`) REFERENCES `users`(`id`) ON UPDATE CASCADE,
    FOREIGN KEY (`reversed_by`) REFERENCES `users`(`id`) ON UPDATE CASCADE ON DELETE SET NULL,
    INDEX `idx_cp_payment_no` (`payment_no`),
    INDEX `idx_cp_customer` (`customer_id`),
    INDEX `idx_cp_date` (`payment_date`)
) ENGINE=InnoDB;

-- 23. INVENTORY TRANSACTIONS
CREATE TABLE IF NOT EXISTS `inventory_transactions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `product_id` INT NOT NULL,
    `transaction_type` ENUM('opening_stock', 'purchase', 'sale', 'return', 'sale_return', 'purchase_return', 'damage', 'loss', 'adjustment', 'adjustment_in', 'adjustment_out', 'stock_count', 'stock_count_adjustment') NOT NULL,
    `quantity` INT NOT NULL, -- absolute or signed
    `quantity_before` INT NOT NULL,
    `quantity_after` INT NOT NULL,
    `unit_cost` DECIMAL(15,2) NULL,
    `reference_type` VARCHAR(50) NULL, -- 'sale', 'purchase', 'stock_count', 'return'
    `reference_id` INT NULL,
    `notes` VARCHAR(255) NULL,
    `created_by` INT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON UPDATE CASCADE,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON UPDATE CASCADE,
    INDEX `idx_inv_product` (`product_id`),
    INDEX `idx_inv_type` (`transaction_type`),
    INDEX `idx_inv_created` (`created_at`)
) ENGINE=InnoDB;

-- 24. PRODUCT COST HISTORY
CREATE TABLE IF NOT EXISTS `product_cost_history` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `product_id` INT NOT NULL,
    `old_cost` DECIMAL(15,2) NOT NULL,
    `new_cost` DECIMAL(15,2) NOT NULL,
    `reason` VARCHAR(255) NULL,
    `reference_type` VARCHAR(50) NULL,
    `reference_id` INT NULL,
    `changed_by` INT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`changed_by`) REFERENCES `users`(`id`) ON UPDATE CASCADE,
    INDEX `idx_cost_hist_prod` (`product_id`)
) ENGINE=InnoDB;

-- 25. PRICE HISTORY
CREATE TABLE IF NOT EXISTS `price_history` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `product_id` INT NOT NULL,
    `old_selling_price` DECIMAL(15,2) NOT NULL,
    `new_selling_price` DECIMAL(15,2) NOT NULL,
    `old_minimum_price` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `new_minimum_price` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `reason` VARCHAR(255) NULL,
    `changed_by` INT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`changed_by`) REFERENCES `users`(`id`) ON UPDATE CASCADE,
    INDEX `idx_price_hist_prod` (`product_id`)
) ENGINE=InnoDB;

-- 26. EXPENSE CATEGORIES
CREATE TABLE IF NOT EXISTS `expense_categories` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL UNIQUE,
    `description` VARCHAR(255) NULL,
    `status` ENUM('active', 'inactive') DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 27. EXPENSES
CREATE TABLE IF NOT EXISTS `expenses` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `expense_no` VARCHAR(50) NOT NULL UNIQUE,
    `category_id` INT NOT NULL,
    `amount` DECIMAL(15,2) NOT NULL,
    `expense_date` DATE NOT NULL,
    `payment_method` VARCHAR(50) DEFAULT 'Cash',
    `reference_no` VARCHAR(100) NULL,
    `description` TEXT NOT NULL,
    `recorded_by` INT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`category_id`) REFERENCES `expense_categories`(`id`) ON UPDATE CASCADE,
    FOREIGN KEY (`recorded_by`) REFERENCES `users`(`id`) ON UPDATE CASCADE,
    INDEX `idx_expense_date` (`expense_date`),
    INDEX `idx_expense_category` (`category_id`)
) ENGINE=InnoDB;

-- 28. RETURNS & REFUNDS
CREATE TABLE IF NOT EXISTS `returns` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `return_number` VARCHAR(50) NOT NULL UNIQUE,
    `sale_id` INT NOT NULL,
    `customer_id` INT NULL,
    `return_date` DATE NOT NULL,
    `reason` VARCHAR(255) NOT NULL,
    `refund_amount` DECIMAL(15,2) NOT NULL,
    `status` ENUM('pending', 'approved', 'completed', 'rejected') DEFAULT 'completed',
    `created_by` INT NOT NULL,
    `approved_by` INT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`sale_id`) REFERENCES `sales`(`id`) ON UPDATE CASCADE,
    FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`) ON UPDATE CASCADE,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON UPDATE CASCADE,
    FOREIGN KEY (`approved_by`) REFERENCES `users`(`id`) ON UPDATE CASCADE,
    INDEX `idx_return_number` (`return_number`),
    INDEX `idx_return_date` (`return_date`)
) ENGINE=InnoDB;

-- 29. RETURN ITEMS
CREATE TABLE IF NOT EXISTS `return_items` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `return_id` INT NOT NULL,
    `sale_item_id` INT NULL,
    `product_id` INT NOT NULL,
    `quantity` INT NOT NULL,
    `unit_refund_price` DECIMAL(15,2) NOT NULL,
    `condition_status` ENUM('resellable', 'damaged', 'defective') NOT NULL DEFAULT 'resellable',
    `restockable` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`return_id`) REFERENCES `returns`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`sale_item_id`) REFERENCES `sale_items`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON UPDATE CASCADE
) ENGINE=InnoDB;

-- 30. AUDIT LOGS (Immutable security trail)
CREATE TABLE IF NOT EXISTS `audit_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NULL,
    `action` VARCHAR(100) NOT NULL,
    `module` VARCHAR(50) NOT NULL,
    `record_id` INT NULL,
    `old_values` TEXT NULL,
    `new_values` TEXT NULL,
    `ip_address` VARCHAR(45) NULL,
    `user_agent` VARCHAR(255) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_audit_user` (`user_id`),
    INDEX `idx_audit_module` (`module`),
    INDEX `idx_audit_created` (`created_at`)
) ENGINE=InnoDB;

-- 31. SETTINGS
CREATE TABLE IF NOT EXISTS `settings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `setting_key` VARCHAR(100) NOT NULL UNIQUE,
    `setting_value` TEXT NOT NULL,
    `description` VARCHAR(255) NULL,
    `updated_by` INT NULL,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`updated_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- 32. STOCK COUNTS (Audit & physical stock reconciliation)
CREATE TABLE IF NOT EXISTS `stock_counts` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `count_no` VARCHAR(50) NULL UNIQUE,
    `count_date` DATE NOT NULL,
    `status` ENUM('draft', 'in_progress', 'submitted', 'pending', 'approved', 'rejected', 'cancelled') DEFAULT 'draft',
    `notes` TEXT NULL,
    `conducted_by` INT NOT NULL,
    `approved_by` INT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`conducted_by`) REFERENCES `users`(`id`) ON UPDATE CASCADE,
    FOREIGN KEY (`approved_by`) REFERENCES `users`(`id`) ON UPDATE CASCADE
) ENGINE=InnoDB;

-- 33. STOCK COUNT ITEMS
CREATE TABLE IF NOT EXISTS `stock_count_items` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `stock_count_id` INT NOT NULL,
    `product_id` INT NOT NULL,
    `system_quantity` INT NOT NULL,
    `physical_quantity` INT NOT NULL,
    `variance` INT NOT NULL,
    `variance_cost` DECIMAL(15,2) NOT NULL,
    `reason` VARCHAR(255) NULL,
    FOREIGN KEY (`stock_count_id`) REFERENCES `stock_counts`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON UPDATE CASCADE
) ENGINE=InnoDB;
