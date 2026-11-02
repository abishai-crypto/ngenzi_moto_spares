-- ==========================================================
-- NGENZI MOTO SPARE PARTS — DATABASE INDEXES
-- Optimizations for POS, Inventory, and Financial Search
-- ==========================================================

USE `ngenzi_moto_spare`;

-- 1. Products Indexes (Fast POS barcode, SKU, and name search)
CREATE INDEX IF NOT EXISTS `idx_products_sku` ON `products` (`sku`);
CREATE INDEX IF NOT EXISTS `idx_products_barcode` ON `products` (`barcode`);
CREATE INDEX IF NOT EXISTS `idx_products_name` ON `products` (`name`);
CREATE INDEX IF NOT EXISTS `idx_products_category` ON `products` (`category_id`);
CREATE INDEX IF NOT EXISTS `idx_products_brand` ON `products` (`brand_id`);

-- 2. Vehicle Model Compatibility Indexes
CREATE INDEX IF NOT EXISTS `idx_veh_models_brand` ON `vehicle_models` (`brand_id`);
CREATE INDEX IF NOT EXISTS `idx_veh_compat_prod` ON `product_vehicle_compatibility` (`product_id`);
CREATE INDEX IF NOT EXISTS `idx_veh_compat_model` ON `product_vehicle_compatibility` (`vehicle_model_id`);

-- 3. Sales & POS Transaction Indexes
CREATE INDEX IF NOT EXISTS `idx_sales_invoice` ON `sales` (`invoice_no`);
CREATE INDEX IF NOT EXISTS `idx_sales_date` ON `sales` (`sale_date`);
CREATE INDEX IF NOT EXISTS `idx_sales_customer` ON `sales` (`customer_id`);
CREATE INDEX IF NOT EXISTS `idx_sales_cashier` ON `sales` (`cashier_id`);
CREATE INDEX IF NOT EXISTS `idx_sales_status` ON `sales` (`sale_status`);

-- 4. Purchases Indexes
CREATE INDEX IF NOT EXISTS `idx_purchases_number` ON `purchases` (`purchase_no`);
CREATE INDEX IF NOT EXISTS `idx_purchases_date` ON `purchases` (`purchase_date`);
CREATE INDEX IF NOT EXISTS `idx_purchases_supplier` ON `purchases` (`supplier_id`);

-- 5. Inventory Transaction Traceability
CREATE INDEX IF NOT EXISTS `idx_inv_tx_product` ON `inventory_transactions` (`product_id`);
CREATE INDEX IF NOT EXISTS `idx_inv_tx_type` ON `inventory_transactions` (`transaction_type`);
CREATE INDEX IF NOT EXISTS `idx_inv_tx_date` ON `inventory_transactions` (`created_at`);

-- 6. Customers & Suppliers Phone Indexes
CREATE INDEX IF NOT EXISTS `idx_customers_phone` ON `customers` (`phone`);
CREATE INDEX IF NOT EXISTS `idx_suppliers_phone` ON `suppliers` (`phone`);
CREATE INDEX IF NOT EXISTS `idx_mechanics_phone` ON `mechanics` (`phone`);

-- 7. Audit Trail Index
CREATE INDEX IF NOT EXISTS `idx_audit_logs_user` ON `audit_logs` (`user_id`);
CREATE INDEX IF NOT EXISTS `idx_audit_logs_module` ON `audit_logs` (`module`);
CREATE INDEX IF NOT EXISTS `idx_audit_logs_date` ON `audit_logs` (`created_at`);
