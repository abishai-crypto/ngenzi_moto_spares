<?php
/**
 * NGENZI MOTO SPARE PARTS
 * Product Model (Phase 4)
 * Comprehensive Motorcycle & Bajaj Spare Parts Catalog Engine with 30% Margin Protection
 */

namespace App\Models;

use App\Core\Database;
use App\Core\Auth;
use App\Core\Helper;
use PDO;
use Exception;

class Product {
    /**
     * Calculate 30% Smart Margin Price
     * Formula: Selling Price = Cost / (1 - Margin%)
     */
    public static function calculateRecommendedPrice(float $cost, float $marginPercent = 30.0): float {
        if ($cost <= 0) return 0.0;
        if ($marginPercent >= 100.0) $marginPercent = 99.0;
        $price = $cost / (1 - ($marginPercent / 100.0));
        return round($price);
    }

    /**
     * Calculate Realized Gross Margin Percentage
     * Formula: Margin% = ((Selling Price - Cost) / Selling Price) * 100
     */
    public static function calculateMargin(float $cost, float $sellingPrice): array {
        if ($sellingPrice <= 0) {
            return [
                'gross_profit'   => 0.0,
                'margin_percent' => 0.0,
                'status'         => 'critical',
                'badge_class'    => 'danger',
                'label'          => 'Hatari (0%)'
            ];
        }

        $grossProfit = $sellingPrice - $cost;
        $marginPercent = ($grossProfit / $sellingPrice) * 100.0;

        if ($marginPercent >= 30.0) {
            $status = 'normal';
            $badge = 'success';
            $label = 'Salama (≥30%)';
        } elseif ($marginPercent >= 20.0) {
            $status = 'warning';
            $badge = 'warning';
            $label = 'Chini ya 30% (Onyo)';
        } else {
            $status = 'critical';
            $badge = 'danger';
            $label = 'Hatari (<20%)';
        }

        return [
            'gross_profit'   => round($grossProfit, 2),
            'margin_percent' => round($marginPercent, 2),
            'status'         => $status,
            'badge_class'    => $badge,
            'label'          => $label
        ];
    }

    /**
     * Auto Generate Unique SKU
     * e.g. Category prefix 'BRK' -> BRK-000001
     */
    public static function generateSku(int $categoryId): string {
        $pdo = Database::getConnection();
        $category = Category::find($categoryId);
        $prefix = 'PRD';

        if ($category && !empty($category['code'])) {
            $prefix = strtoupper($category['code']);
        } elseif ($category && !empty($category['name'])) {
            $clean = preg_replace('/[^A-Za-z0-9]/', '', $category['name']);
            $prefix = strtoupper(substr($clean, 0, 3));
        }

        // Sequential number loop until unique
        $stmt = $pdo->query("SELECT COUNT(*) FROM `products`");
        $count = (int)$stmt->fetchColumn() + 1;

        do {
            $sku = sprintf("%s-%06d", $prefix, $count);
            $check = $pdo->prepare("SELECT id FROM `products` WHERE sku = ? LIMIT 1");
            $check->execute([$sku]);
            $exists = $check->fetchColumn();
            if (!$exists) {
                return $sku;
            }
            $count++;
        } while (true);
    }

    /**
     * Find product by ID with full join
     */
    public static function find(int $id): ?array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT p.*, 
                   c.name as category_name, c.code as category_code,
                   b.name as brand_name, b.country as brand_country,
                   u.name as unit_name, u.short_name as unit_short
            FROM `products` p
            JOIN `categories` c ON p.category_id = c.id
            LEFT JOIN `brands` b ON p.brand_id = b.id
            JOIN `units` u ON p.unit_id = u.id
            WHERE p.id = ?
        ");
        $stmt->execute([$id]);
        $product = $stmt->fetch();
        if (!$product) return null;

        $product['margin_analysis'] = self::calculateMargin((float)$product['buying_price'], (float)$product['selling_price']);
        $product['compatible_vehicles'] = self::getCompatibleVehicles((int)$product['id']);
        return $product;
    }

    /**
     * Find product by SKU
     */
    public static function findBySku(string $sku): ?array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM `products` WHERE sku = ? LIMIT 1");
        $stmt->execute([$sku]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Find product by Barcode
     */
    public static function findByBarcode(string $barcode): ?array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM `products` WHERE barcode = ? LIMIT 1");
        $stmt->execute([$barcode]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Get compatible vehicle models for a product
     */
    public static function getCompatibleVehicles(int $productId): array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT vm.*, vb.name as brand_name, pvc.notes as compatibility_notes
            FROM `product_vehicle_compatibility` pvc
            JOIN `vehicle_models` vm ON pvc.vehicle_model_id = vm.id
            JOIN `vehicle_brands` vb ON vm.brand_id = vb.id
            WHERE pvc.product_id = ?
            ORDER BY vb.name ASC, vm.model_name ASC
        ");
        $stmt->execute([$productId]);
        return $stmt->fetchAll();
    }

    /**
     * Get compatible vehicle model IDs array for checkbox pre-selection
     */
    public static function getCompatibleVehicleIds(int $productId): array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT vehicle_model_id FROM `product_vehicle_compatibility` WHERE product_id = ?");
        $stmt->execute([$productId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Get Price History for product
     */
    public static function getPriceHistory(int $productId): array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT ph.*, u.full_name as changed_by_name, u.username as changed_by_username
            FROM `price_history` ph
            LEFT JOIN `users` u ON ph.changed_by = u.id
            WHERE ph.product_id = ?
            ORDER BY ph.created_at DESC
        ");
        $stmt->execute([$productId]);
        return $stmt->fetchAll();
    }

    /**
     * Get Cost History for product (Restricted)
     */
    public static function getCostHistory(int $productId): array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT ch.*, u.full_name as changed_by_name, u.username as changed_by_username
            FROM `product_cost_history` ch
            LEFT JOIN `users` u ON ch.changed_by = u.id
            WHERE ch.product_id = ?
            ORDER BY ch.created_at DESC
        ");
        $stmt->execute([$productId]);
        return $stmt->fetchAll();
    }

    /**
     * Fetch products with multi-attribute filtering & pagination
     */
    public static function all(array $filters = [], int $limit = 20, int $offset = 0): array {
        $pdo = Database::getConnection();
        $sql = "
            SELECT p.*, 
                   c.name as category_name,
                   b.name as brand_name,
                   u.short_name as unit_short,
                   COUNT(pvc.id) as vehicle_compatibility_count,
                   GROUP_CONCAT(DISTINCT CONCAT(vb.name, ' ', vm.model_name) SEPARATOR ', ') as compatible_models_summary
            FROM `products` p
            JOIN `categories` c ON p.category_id = c.id
            LEFT JOIN `brands` b ON p.brand_id = b.id
            JOIN `units` u ON p.unit_id = u.id
            LEFT JOIN `product_vehicle_compatibility` pvc ON p.id = pvc.product_id
            LEFT JOIN `vehicle_models` vm ON pvc.vehicle_model_id = vm.id
            LEFT JOIN `vehicle_brands` vb ON vm.brand_id = vb.id
            WHERE 1=1
        ";
        $params = [];

        // Text search: Name, SKU, Barcode, Spec/Type, Category, Brand, Shelf location, Description, and Compatibility
        if (!empty($filters['search'])) {
            $term = "%{$filters['search']}%";
            $sql .= " AND (
                p.name LIKE ? 
                OR p.sku LIKE ? 
                OR p.barcode LIKE ? 
                OR p.product_type LIKE ?
                OR c.name LIKE ?
                OR c.code LIKE ?
                OR b.name LIKE ?
                OR p.shelf_location LIKE ? 
                OR p.description LIKE ?
                OR p.id IN (
                    SELECT pvc_srch.product_id 
                    FROM product_vehicle_compatibility pvc_srch 
                    JOIN vehicle_models vm_srch ON pvc_srch.vehicle_model_id = vm_srch.id 
                    JOIN vehicle_brands vb_srch ON vm_srch.brand_id = vb_srch.id 
                    WHERE vm_srch.model_name LIKE ? OR vb_srch.name LIKE ?
                )
            )";
            $params = array_merge($params, [$term, $term, $term, $term, $term, $term, $term, $term, $term, $term, $term]);
        }

        // Category filter
        if (!empty($filters['category_id'])) {
            $sql .= " AND p.category_id = ?";
            $params[] = (int)$filters['category_id'];
        }

        // Brand filter
        if (!empty($filters['brand_id'])) {
            $sql .= " AND p.brand_id = ?";
            $params[] = (int)$filters['brand_id'];
        }

        // Status filter
        if (!empty($filters['status'])) {
            $sql .= " AND p.status = ?";
            $params[] = $filters['status'];
        }

        // Stock Status filter
        if (!empty($filters['stock_status'])) {
            if ($filters['stock_status'] === 'in_stock') {
                $sql .= " AND p.current_stock > p.reorder_level";
            } elseif ($filters['stock_status'] === 'low_stock') {
                $sql .= " AND p.current_stock > 0 AND p.current_stock <= p.reorder_level";
            } elseif ($filters['stock_status'] === 'out_of_stock') {
                $sql .= " AND p.current_stock <= 0";
            }
        }

        // Vehicle Filter (by vehicle model)
        if (!empty($filters['vehicle_model_id'])) {
            $sql .= " AND p.id IN (SELECT product_id FROM `product_vehicle_compatibility` WHERE vehicle_model_id = ?)";
            $params[] = (int)$filters['vehicle_model_id'];
        }

        // Vehicle Filter (by vehicle brand)
        if (!empty($filters['vehicle_brand_id'])) {
            $sql .= " AND p.id IN (
                SELECT pvc_sub.product_id 
                FROM `product_vehicle_compatibility` pvc_sub
                JOIN `vehicle_models` vm_sub ON pvc_sub.vehicle_model_id = vm_sub.id
                WHERE vm_sub.brand_id = ?
            )";
            $params[] = (int)$filters['vehicle_brand_id'];
        }

        // Vehicle Type filter ('Motorcycle' or 'Bajaj')
        if (!empty($filters['vehicle_type'])) {
            $sql .= " AND p.id IN (
                SELECT pvc_sub2.product_id 
                FROM `product_vehicle_compatibility` pvc_sub2
                JOIN `vehicle_models` vm_sub2 ON pvc_sub2.vehicle_model_id = vm_sub2.id
                WHERE vm_sub2.vehicle_type = ?
            )";
            $params[] = $filters['vehicle_type'];
        }

        $sql .= " GROUP BY p.id ORDER BY p.name ASC LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $products = $stmt->fetchAll();

        foreach ($products as &$p) {
            $p['margin_analysis'] = self::calculateMargin((float)$p['buying_price'], (float)$p['selling_price']);
        }

        return $products;
    }

    /**
     * Count products matching filters for pagination
     */
    public static function count(array $filters = []): int {
        $pdo = Database::getConnection();
        $sql = "
            SELECT COUNT(DISTINCT p.id)
            FROM `products` p
            JOIN `categories` c ON p.category_id = c.id
            LEFT JOIN `brands` b ON p.brand_id = b.id
            LEFT JOIN `product_vehicle_compatibility` pvc ON p.id = pvc.product_id
            LEFT JOIN `vehicle_models` vm ON pvc.vehicle_model_id = vm.id
            WHERE 1=1
        ";
        $params = [];

        if (!empty($filters['search'])) {
            $term = "%{$filters['search']}%";
            $sql .= " AND (
                p.name LIKE ? 
                OR p.sku LIKE ? 
                OR p.barcode LIKE ? 
                OR p.product_type LIKE ?
                OR c.name LIKE ?
                OR c.code LIKE ?
                OR b.name LIKE ?
                OR p.shelf_location LIKE ? 
                OR p.description LIKE ?
                OR p.id IN (
                    SELECT pvc_srch.product_id 
                    FROM product_vehicle_compatibility pvc_srch 
                    JOIN vehicle_models vm_srch ON pvc_srch.vehicle_model_id = vm_srch.id 
                    JOIN vehicle_brands vb_srch ON vm_srch.brand_id = vb_srch.id 
                    WHERE vm_srch.model_name LIKE ? OR vb_srch.name LIKE ?
                )
            )";
            $params = array_merge($params, [$term, $term, $term, $term, $term, $term, $term, $term, $term, $term, $term]);
        }

        if (!empty($filters['category_id'])) {
            $sql .= " AND p.category_id = ?";
            $params[] = (int)$filters['category_id'];
        }

        if (!empty($filters['brand_id'])) {
            $sql .= " AND p.brand_id = ?";
            $params[] = (int)$filters['brand_id'];
        }

        if (!empty($filters['status'])) {
            $sql .= " AND p.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['stock_status'])) {
            if ($filters['stock_status'] === 'in_stock') {
                $sql .= " AND p.current_stock > p.reorder_level";
            } elseif ($filters['stock_status'] === 'low_stock') {
                $sql .= " AND p.current_stock > 0 AND p.current_stock <= p.reorder_level";
            } elseif ($filters['stock_status'] === 'out_of_stock') {
                $sql .= " AND p.current_stock <= 0";
            }
        }

        if (!empty($filters['vehicle_model_id'])) {
            $sql .= " AND p.id IN (SELECT product_id FROM `product_vehicle_compatibility` WHERE vehicle_model_id = ?)";
            $params[] = (int)$filters['vehicle_model_id'];
        }

        if (!empty($filters['vehicle_brand_id'])) {
            $sql .= " AND p.id IN (
                SELECT pvc_sub.product_id 
                FROM `product_vehicle_compatibility` pvc_sub
                JOIN `vehicle_models` vm_sub ON pvc_sub.vehicle_model_id = vm_sub.id
                WHERE vm_sub.brand_id = ?
            )";
            $params[] = (int)$filters['vehicle_brand_id'];
        }

        if (!empty($filters['vehicle_type'])) {
            $sql .= " AND p.id IN (
                SELECT pvc_sub2.product_id 
                FROM `product_vehicle_compatibility` pvc_sub2
                JOIN `vehicle_models` vm_sub2 ON pvc_sub2.vehicle_model_id = vm_sub2.id
                WHERE vm_sub2.vehicle_type = ?
            )";
            $params[] = $filters['vehicle_type'];
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Create Product with Vehicle Compatibility in ACID Transaction
     */
    public static function create(array $data, array $vehicleModelIds = []): int {
        $pdo = Database::getConnection();
        $pdo->beginTransaction();

        try {
            $name = trim($data['name'] ?? '');
            if (empty($name)) {
                throw new Exception("Jina la kipuri (Product Name) linahitajika.");
            }

            $categoryId = (int)($data['category_id'] ?? 0);
            if ($categoryId <= 0 || !Category::find($categoryId)) {
                throw new Exception("Tafadhali chagua kategoria sahihi ya kipuri.");
            }

            $unitId = (int)($data['unit_id'] ?? 0);
            if ($unitId <= 0 || !Unit::find($unitId)) {
                throw new Exception("Tafadhali chagua kipimo (Unit) sahihi.");
            }

            $brandId = !empty($data['brand_id']) ? (int)$data['brand_id'] : null;

            // Handle SKU
            $sku = trim($data['sku'] ?? '');
            if (empty($sku)) {
                $sku = self::generateSku($categoryId);
            } else {
                if (self::findBySku($sku)) {
                    throw new Exception("SKU '{$sku}' tayari inatumika kwa kipuri kingine. Chagua SKU ya kipekee au iache wazi ili izalishwe kiotomatiki.");
                }
            }

            // Handle Barcode
            $barcode = trim($data['barcode'] ?? '');
            if (!empty($barcode) && self::findByBarcode($barcode)) {
                throw new Exception("Barcode '{$barcode}' tayari inatumika kwenye mfumo.");
            }

            // Financial Calculations
            $buyingPrice = (float)($data['buying_price'] ?? 0.0);
            if ($buyingPrice < 0) {
                throw new Exception("Bei ya kununulia haiwezi kuwa hasi.");
            }

            $targetMargin = !empty($data['target_margin']) ? (float)$data['target_margin'] : 30.0;
            $recommendedPrice = self::calculateRecommendedPrice($buyingPrice, $targetMargin);

            $sellingPrice = !empty($data['selling_price']) ? (float)$data['selling_price'] : $recommendedPrice;
            if ($sellingPrice < 0) {
                throw new Exception("Bei ya kuuzia haiwezi kuwa hasi.");
            }

            $minimumPrice = !empty($data['minimum_selling_price']) ? (float)$data['minimum_selling_price'] : $buyingPrice;

            // Stock Values
            $currentStock   = max(0, (int)($data['current_stock'] ?? 0));
            $minStockLevel  = max(0, (int)($data['min_stock_level'] ?? 5));
            $reorderLevel   = max(0, (int)($data['reorder_level'] ?? 10));
            $maxStockLevel  = max(0, (int)($data['max_stock_level'] ?? 100));
            $shelfLocation  = trim($data['shelf_location'] ?? '');
            $warrantyDays   = max(0, (int)($data['warranty_days'] ?? 0));
            $description    = trim($data['description'] ?? '');
            $status         = in_array($data['status'] ?? '', ['active', 'inactive', 'discontinued']) ? $data['status'] : 'active';

            $stmt = $pdo->prepare("
                INSERT INTO `products` 
                (`sku`, `barcode`, `name`, `description`, `category_id`, `brand_id`, `unit_id`,
                 `buying_price`, `selling_price`, `recommended_selling_price`, `minimum_selling_price`,
                 `target_margin`, `current_stock`, `min_stock_level`, `reorder_level`, `max_stock_level`,
                 `shelf_location`, `warranty_days`, `status`)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $sku,
                $barcode ?: null,
                $name,
                $description ?: null,
                $categoryId,
                $brandId ?: null,
                $unitId,
                $buyingPrice,
                $sellingPrice,
                $recommendedPrice,
                $minimumPrice,
                $targetMargin,
                $currentStock,
                $minStockLevel,
                $reorderLevel,
                $maxStockLevel,
                $shelfLocation ?: null,
                $warrantyDays,
                $status
            ]);

            $newProductId = (int)$pdo->lastInsertId();

            // Insert initial Price History
            $userId = Auth::id() ?: 1;
            $histPrice = $pdo->prepare("
                INSERT INTO `price_history` 
                (`product_id`, `old_selling_price`, `new_selling_price`, `old_minimum_price`, `new_minimum_price`, `reason`, `changed_by`)
                VALUES (?, 0.00, ?, 0.00, ?, 'Usajili wa awali wa kipuri', ?)
            ");
            $histPrice->execute([$newProductId, $sellingPrice, $minimumPrice, $userId]);

            // Insert initial Cost History
            $histCost = $pdo->prepare("
                INSERT INTO `product_cost_history` 
                (`product_id`, `old_cost`, `new_cost`, `reason`, `changed_by`)
                VALUES (?, 0.00, ?, 'Usajili wa awali wa kipuri', ?)
            ");
            $histCost->execute([$newProductId, $buyingPrice, $userId]);

            // Insert Vehicle Compatibility mappings
            if (!empty($vehicleModelIds)) {
                $compStmt = $pdo->prepare("
                    INSERT INTO `product_vehicle_compatibility` (`product_id`, `vehicle_model_id`, `notes`)
                    VALUES (?, ?, ?)
                ");
                foreach ($vehicleModelIds as $vId) {
                    $vId = (int)$vId;
                    if ($vId > 0) {
                        $compStmt->execute([$newProductId, $vId, 'Imeongezwa wakati wa kusajili']);
                    }
                }
            }

            $pdo->commit();

            Helper::logAudit('PRODUCT_CREATED', 'PRODUCTS', $newProductId, null, [
                'sku'           => $sku,
                'name'          => $name,
                'buying_price'  => $buyingPrice,
                'selling_price' => $sellingPrice,
                'vehicles'      => count($vehicleModelIds)
            ]);

            return $newProductId;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Update Product with Price/Cost History & Compatibility in ACID Transaction
     */
    public static function update(int $id, array $data, array $vehicleModelIds = []): bool {
        $pdo = Database::getConnection();
        $product = self::find($id);
        if (!$product) {
            throw new Exception("Kipuri hakikupatikana.");
        }

        $pdo->beginTransaction();

        try {
            $name = trim($data['name'] ?? $product['name']);
            if (empty($name)) {
                throw new Exception("Jina la kipuri linahitajika.");
            }

            $categoryId = (int)($data['category_id'] ?? $product['category_id']);
            $unitId     = (int)($data['unit_id'] ?? $product['unit_id']);
            $brandId    = !empty($data['brand_id']) ? (int)$data['brand_id'] : null;

            // Barcode uniqueness
            $barcode = trim($data['barcode'] ?? '');
            if (!empty($barcode) && $barcode !== $product['barcode']) {
                if (self::findByBarcode($barcode)) {
                    throw new Exception("Barcode '{$barcode}' tayari inatumika kwa kipuri kingine.");
                }
            }

            // Price & Cost changes
            $newBuyingPrice = isset($data['buying_price']) ? (float)$data['buying_price'] : (float)$product['buying_price'];
            $targetMargin   = isset($data['target_margin']) ? (float)$data['target_margin'] : (float)$product['target_margin'];
            $recommendedPrice = self::calculateRecommendedPrice($newBuyingPrice, $targetMargin);

            $newSellingPrice = isset($data['selling_price']) ? (float)$data['selling_price'] : (float)$product['selling_price'];
            $newMinimumPrice = isset($data['minimum_selling_price']) ? (float)$data['minimum_selling_price'] : (float)$product['minimum_selling_price'];
            $priceChangeReason = trim($data['price_change_reason'] ?? 'Mabadiliko ya bei ya kipuri');

            $userId = Auth::id() ?: 1;

            // Track Selling Price Change
            if (abs($newSellingPrice - (float)$product['selling_price']) > 0.001 || abs($newMinimumPrice - (float)$product['minimum_selling_price']) > 0.001) {
                $histPrice = $pdo->prepare("
                    INSERT INTO `price_history` 
                    (`product_id`, `old_selling_price`, `new_selling_price`, `old_minimum_price`, `new_minimum_price`, `reason`, `changed_by`)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $histPrice->execute([
                    $id,
                    $product['selling_price'],
                    $newSellingPrice,
                    $product['minimum_selling_price'],
                    $newMinimumPrice,
                    $priceChangeReason,
                    $userId
                ]);

                Helper::logAudit('PRICE_CHANGED', 'PRODUCTS', $id, [
                    'selling_price' => $product['selling_price'],
                    'minimum_price' => $product['minimum_selling_price']
                ], [
                    'selling_price' => $newSellingPrice,
                    'minimum_price' => $newMinimumPrice,
                    'reason'        => $priceChangeReason
                ]);
            }

            // Track Buying Cost Change
            if (abs($newBuyingPrice - (float)$product['buying_price']) > 0.001) {
                $histCost = $pdo->prepare("
                    INSERT INTO `product_cost_history` 
                    (`product_id`, `old_cost`, `new_cost`, `reason`, `changed_by`)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $histCost->execute([
                    $id,
                    $product['buying_price'],
                    $newBuyingPrice,
                    $priceChangeReason,
                    $userId
                ]);

                Helper::logAudit('COST_CHANGED', 'PRODUCTS', $id, ['buying_price' => $product['buying_price']], [
                    'buying_price' => $newBuyingPrice,
                    'reason'       => $priceChangeReason
                ]);
            }

            $minStockLevel = max(0, (int)($data['min_stock_level'] ?? $product['min_stock_level']));
            $reorderLevel  = max(0, (int)($data['reorder_level'] ?? $product['reorder_level']));
            $maxStockLevel = max(0, (int)($data['max_stock_level'] ?? $product['max_stock_level']));
            $shelfLocation = trim($data['shelf_location'] ?? $product['shelf_location']);
            $warrantyDays  = max(0, (int)($data['warranty_days'] ?? $product['warranty_days']));
            $description   = trim($data['description'] ?? $product['description']);
            $status        = in_array($data['status'] ?? '', ['active', 'inactive', 'discontinued']) ? $data['status'] : $product['status'];

            $stmt = $pdo->prepare("
                UPDATE `products`
                SET `name` = ?, `barcode` = ?, `description` = ?, `category_id` = ?, `brand_id` = ?, `unit_id` = ?,
                    `buying_price` = ?, `selling_price` = ?, `recommended_selling_price` = ?, `minimum_selling_price` = ?,
                    `target_margin` = ?, `min_stock_level` = ?, `reorder_level` = ?, `max_stock_level` = ?,
                    `shelf_location` = ?, `warranty_days` = ?, `status` = ?
                WHERE `id` = ?
            ");

            $stmt->execute([
                $name,
                $barcode ?: null,
                $description ?: null,
                $categoryId,
                $brandId ?: null,
                $unitId,
                $newBuyingPrice,
                $newSellingPrice,
                $recommendedPrice,
                $newMinimumPrice,
                $targetMargin,
                $minStockLevel,
                $reorderLevel,
                $maxStockLevel,
                $shelfLocation ?: null,
                $warrantyDays,
                $status,
                $id
            ]);

            // Synchronize Vehicle Compatibility
            $delComp = $pdo->prepare("DELETE FROM `product_vehicle_compatibility` WHERE `product_id` = ?");
            $delComp->execute([$id]);

            if (!empty($vehicleModelIds)) {
                $insComp = $pdo->prepare("
                    INSERT INTO `product_vehicle_compatibility` (`product_id`, `vehicle_model_id`, `notes`)
                    VALUES (?, ?, 'Ilisasishwa kwenye hariri ya kipuri')
                ");
                foreach ($vehicleModelIds as $vId) {
                    $vId = (int)$vId;
                    if ($vId > 0) {
                        $insComp->execute([$id, $vId]);
                    }
                }
            }

            $pdo->commit();

            Helper::logAudit('PRODUCT_UPDATED', 'PRODUCTS', $id, $product, [
                'name'          => $name,
                'buying_price'  => $newBuyingPrice,
                'selling_price' => $newSellingPrice,
                'status'        => $status,
                'vehicles'      => count($vehicleModelIds)
            ]);

            return true;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Toggle product status (active / inactive) without destroying history
     */
    public static function toggleStatus(int $id): string {
        $pdo = Database::getConnection();
        $product = self::find($id);
        if (!$product) {
            throw new Exception("Kipuri hakikupatikana.");
        }

        $newStatus = ($product['status'] === 'active') ? 'inactive' : 'active';
        $stmt = $pdo->prepare("UPDATE `products` SET `status` = ? WHERE `id` = ?");
        $stmt->execute([$newStatus, $id]);

        $action = ($newStatus === 'active') ? 'PRODUCT_ACTIVATED' : 'PRODUCT_DEACTIVATED';
        Helper::logAudit($action, 'PRODUCTS', $id, ['status' => $product['status']], ['status' => $newStatus]);
        return $newStatus;
    }

    /**
     * Search products compatible with a specific vehicle model
     */
    public static function searchByVehicle(int $vehicleModelId): array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT p.*, c.name as category_name, b.name as brand_name, u.short_name as unit_short,
                   pvc.notes as compatibility_notes
            FROM `products` p
            JOIN `product_vehicle_compatibility` pvc ON p.id = pvc.product_id
            JOIN `categories` c ON p.category_id = c.id
            LEFT JOIN `brands` b ON p.brand_id = b.id
            JOIN `units` u ON p.unit_id = u.id
            WHERE pvc.vehicle_model_id = ? AND p.status = 'active'
            ORDER BY c.name ASC, p.name ASC
        ");
        $stmt->execute([$vehicleModelId]);
        $products = $stmt->fetchAll();

        foreach ($products as &$p) {
            $p['margin_analysis'] = self::calculateMargin((float)$p['buying_price'], (float)$p['selling_price']);
        }

        return $products;
    }

    /**
     * Get Product Return Statistics & Analysis
     */
    public static function getReturnStats(int $productId): array {
        require_once __DIR__ . '/Returns.php';
        return Returns::getProductReturnStats($productId);
    }

    /**
     * Get Product Return History
     */
    public static function getReturnHistory(int $productId, int $limit = 20): array {
        require_once __DIR__ . '/Returns.php';
        return Returns::getProductReturnHistory($productId, $limit);
    }

    // =========================================================================
    // PHASE 16: E-COMMERCE & ONLINE STORE CATALOG METHODS
    // =========================================================================

    /**
    /**
     * Helper to compute customer-facing availability status (Phase 17)
     * Strictly masks internal physical counts unless catalog_display_exact_stock is enabled.
     */
    public static function getAvailabilityInfo(array $product): array {
        $isCatalogOnly = !empty($product['is_catalog_only']);
        $statusKey = strtolower(trim($product['availability_status'] ?? 'in_stock'));
        $stock = (int)($product['current_stock'] ?? 0);
        $reorder = (int)($product['reorder_level'] ?? 5);

        $displayExactStock = ((string)Setting::get('catalog_display_exact_stock', '0') === '1');

        if ($isCatalogOnly || $statusKey === 'catalog_only') {
            return [
                'code'             => 'CATALOG_ONLY',
                'status'           => 'CATALOG ONLY',
                'label'            => 'Katalogi Tu (Reference Only)',
                'badge_class'      => 'bg-secondary',
                'display_stock'    => 'Katalogi',
                'display_quantity' => null,
                'can_order'        => false
            ];
        }

        if ($statusKey === 'coming_soon') {
            return [
                'code'             => 'COMING_SOON',
                'status'           => 'COMING SOON',
                'label'            => 'Inakuja Hivi Karibuni (Coming Soon)',
                'badge_class'      => 'bg-info text-dark',
                'display_stock'    => 'Inakuja Karibuni',
                'display_quantity' => null,
                'can_order'        => false
            ];
        }

        if ($statusKey === 'available_on_order') {
            return [
                'code'             => 'AVAILABLE_ON_ORDER',
                'status'           => 'AVAILABLE ON ORDER',
                'label'            => 'Inapatikana kwa Agizo (Available On Order)',
                'badge_class'      => 'bg-warning text-dark',
                'display_stock'    => 'Kwa Agizo',
                'display_quantity' => null,
                'can_order'        => true
            ];
        }

        if ($stock <= 0) {
            return [
                'code'             => 'OUT_OF_STOCK',
                'status'           => 'OUT OF STOCK',
                'label'            => 'Imeisha Stoo (Out of Stock)',
                'badge_class'      => 'bg-danger',
                'display_stock'    => 'Imeisha Stoo',
                'display_quantity' => $displayExactStock ? 0 : null,
                'can_order'        => false
            ];
        }

        if ($stock <= $reorder) {
            return [
                'code'             => 'LOW_STOCK',
                'status'           => 'LOW STOCK',
                'label'            => 'Vipuri Vichache (Low Stock)',
                'badge_class'      => 'bg-warning text-dark',
                'display_stock'    => $displayExactStock ? "Vichache ({$stock})" : 'Vipuri Vichache',
                'display_quantity' => $displayExactStock ? $stock : null,
                'can_order'        => true
            ];
        }

        return [
            'code'             => 'IN_STOCK',
            'status'           => 'IN STOCK',
            'label'            => 'Vipo Stoo (In Stock)',
            'badge_class'      => 'bg-success',
            'display_stock'    => $displayExactStock ? "Vipo ({$stock})" : 'Vipo Stoo',
            'display_quantity' => $displayExactStock ? $stock : null,
            'can_order'        => true
        ];
    }

    /**
     * Fetch products for the public online store (strictly hides cost prices & internal margins)
     */
    public static function getOnlineProducts(array $filters = [], int $limit = 20, int $offset = 0): array {
        $pdo = Database::getConnection();
        $sql = "
            SELECT p.id, p.sku, p.name, p.description, p.online_description,
                   p.selling_price, p.current_stock, p.reorder_level, p.status, p.is_online,
                   p.is_catalog_only, p.availability_status, p.product_type,
                   p.is_featured, p.image_url, p.warranty_days,
                   c.id as category_id, c.name as category_name,
                   b.id as brand_id, b.name as brand_name,
                   u.short_name as unit_short,
                   GROUP_CONCAT(DISTINCT CONCAT(vb.name, ' ', vm.model_name) SEPARATOR ', ') as compatible_models
            FROM `products` p
            JOIN `categories` c ON p.category_id = c.id
            LEFT JOIN `brands` b ON p.brand_id = b.id
            JOIN `units` u ON p.unit_id = u.id
            LEFT JOIN `product_vehicle_compatibility` pvc ON p.id = pvc.product_id
            LEFT JOIN `vehicle_models` vm ON pvc.vehicle_model_id = vm.id
            LEFT JOIN `vehicle_brands` vb ON vm.brand_id = vb.id
            WHERE p.status = 'active' AND p.is_online = 1
        ";
        $params = [];

        // Text search across name, sku, description, category, brand, vehicle
        if (!empty($filters['search'])) {
            $term = "%{$filters['search']}%";
            $sql .= " AND (
                p.name LIKE ? 
                OR p.sku LIKE ? 
                OR p.description LIKE ? 
                OR p.online_description LIKE ?
                OR p.product_type LIKE ?
                OR c.name LIKE ?
                OR b.name LIKE ?
                OR p.id IN (
                    SELECT pvc_s.product_id 
                    FROM product_vehicle_compatibility pvc_s 
                    JOIN vehicle_models vm_s ON pvc_s.vehicle_model_id = vm_s.id 
                    JOIN vehicle_brands vb_s ON vm_s.brand_id = vb_s.id 
                    WHERE vm_s.model_name LIKE ? OR vb_s.name LIKE ?
                )
            )";
            $params = array_merge($params, [$term, $term, $term, $term, $term, $term, $term, $term, $term]);
        }

        // Category filter
        if (!empty($filters['category_id'])) {
            $sql .= " AND p.category_id = ?";
            $params[] = (int)$filters['category_id'];
        }

        // Brand filter
        if (!empty($filters['brand_id'])) {
            $sql .= " AND p.brand_id = ?";
            $params[] = (int)$filters['brand_id'];
        }

        // Vehicle Model filter
        if (!empty($filters['vehicle_model_id'])) {
            $sql .= " AND p.id IN (SELECT product_id FROM `product_vehicle_compatibility` WHERE vehicle_model_id = ?)";
            $params[] = (int)$filters['vehicle_model_id'];
        }

        // Vehicle Brand filter
        if (!empty($filters['vehicle_brand_id'])) {
            $sql .= " AND p.id IN (
                SELECT pvc_vb.product_id FROM `product_vehicle_compatibility` pvc_vb
                JOIN `vehicle_models` vm_vb ON pvc_vb.vehicle_model_id = vm_vb.id
                WHERE vm_vb.brand_id = ?
            )";
            $params[] = (int)$filters['vehicle_brand_id'];
        }

        // Vehicle Type filter ('Motorcycle' or 'Bajaj')
        if (!empty($filters['vehicle_type'])) {
            $sql .= " AND p.id IN (
                SELECT pvc_vt.product_id FROM `product_vehicle_compatibility` pvc_vt
                JOIN `vehicle_models` vm_vt ON pvc_vt.vehicle_model_id = vm_vt.id
                WHERE vm_vt.vehicle_type = ?
            )";
            $params[] = $filters['vehicle_type'];
        }

        // In Stock filter
        if (!empty($filters['in_stock_only'])) {
            $sql .= " AND p.current_stock > 0 AND p.is_catalog_only = 0";
        }

        // Availability filter
        if (!empty($filters['availability'])) {
            if ($filters['availability'] === 'in_stock') {
                $sql .= " AND p.current_stock > 0 AND p.is_catalog_only = 0";
            } elseif ($filters['availability'] === 'on_order') {
                $sql .= " AND p.availability_status = 'available_on_order'";
            } elseif ($filters['availability'] === 'catalog') {
                $sql .= " AND p.is_catalog_only = 1";
            }
        }

        // Featured filter
        if (!empty($filters['is_featured'])) {
            $sql .= " AND p.is_featured = 1";
        }

        // Price range
        if (isset($filters['min_price']) && is_numeric($filters['min_price'])) {
            $sql .= " AND p.selling_price >= ?";
            $params[] = (float)$filters['min_price'];
        }
        if (isset($filters['max_price']) && is_numeric($filters['max_price'])) {
            $sql .= " AND p.selling_price <= ?";
            $params[] = (float)$filters['max_price'];
        }

        // Sorting
        $sortOrder = "p.online_sort_order ASC, p.name ASC";
        if (!empty($filters['sort'])) {
            if ($filters['sort'] === 'price_asc') {
                $sortOrder = "p.selling_price ASC";
            } elseif ($filters['sort'] === 'price_desc') {
                $sortOrder = "p.selling_price DESC";
            } elseif ($filters['sort'] === 'newest') {
                $sortOrder = "p.id DESC";
            } elseif ($filters['sort'] === 'name_asc') {
                $sortOrder = "p.name ASC";
            } elseif ($filters['sort'] === 'popular') {
                $sortOrder = "p.is_featured DESC, p.id DESC";
            }
        }

        $sql .= " GROUP BY p.id ORDER BY {$sortOrder} LIMIT " . (int)$limit . " OFFSET " . (int)$offset;

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($products as &$p) {
            $p['availability'] = self::getAvailabilityInfo($p);
            $p['in_stock'] = ((int)$p['current_stock'] > 0 && empty($p['is_catalog_only']));
            $p['image_url'] = $p['image_url'] ?: '/assets/img/spare_part_placeholder.svg';
        }

        return $products;
    }

    /**
     * Count products matching online filters
     */
    public static function countOnlineProducts(array $filters = []): int {
        $pdo = Database::getConnection();
        $sql = "
            SELECT COUNT(DISTINCT p.id)
            FROM `products` p
            JOIN `categories` c ON p.category_id = c.id
            LEFT JOIN `brands` b ON p.brand_id = b.id
            LEFT JOIN `product_vehicle_compatibility` pvc ON p.id = pvc.product_id
            LEFT JOIN `vehicle_models` vm ON pvc.vehicle_model_id = vm.id
            LEFT JOIN `vehicle_brands` vb ON vm.brand_id = vb.id
            WHERE p.status = 'active' AND p.is_online = 1
        ";
        $params = [];

        if (!empty($filters['search'])) {
            $term = "%{$filters['search']}%";
            $sql .= " AND (
                p.name LIKE ? 
                OR p.sku LIKE ? 
                OR p.description LIKE ? 
                OR p.online_description LIKE ?
                OR p.product_type LIKE ?
                OR c.name LIKE ?
                OR b.name LIKE ?
                OR p.id IN (
                    SELECT pvc_s.product_id 
                    FROM product_vehicle_compatibility pvc_s 
                    JOIN vehicle_models vm_s ON pvc_s.vehicle_model_id = vm_s.id 
                    JOIN vehicle_brands vb_s ON vm_s.brand_id = vb_s.id 
                    WHERE vm_s.model_name LIKE ? OR vb_s.name LIKE ?
                )
            )";
            $params = array_merge($params, [$term, $term, $term, $term, $term, $term, $term, $term, $term]);
        }

        if (!empty($filters['category_id'])) {
            $sql .= " AND p.category_id = ?";
            $params[] = (int)$filters['category_id'];
        }

        if (!empty($filters['brand_id'])) {
            $sql .= " AND p.brand_id = ?";
            $params[] = (int)$filters['brand_id'];
        }

        if (!empty($filters['vehicle_model_id'])) {
            $sql .= " AND p.id IN (SELECT product_id FROM `product_vehicle_compatibility` WHERE vehicle_model_id = ?)";
            $params[] = (int)$filters['vehicle_model_id'];
        }

        if (!empty($filters['vehicle_brand_id'])) {
            $sql .= " AND p.id IN (
                SELECT pvc_vb.product_id FROM `product_vehicle_compatibility` pvc_vb
                JOIN `vehicle_models` vm_vb ON pvc_vb.vehicle_model_id = vm_vb.id
                WHERE vm_vb.brand_id = ?
            )";
            $params[] = (int)$filters['vehicle_brand_id'];
        }

        if (!empty($filters['vehicle_type'])) {
            $sql .= " AND p.id IN (
                SELECT pvc_vt.product_id FROM `product_vehicle_compatibility` pvc_vt
                JOIN `vehicle_models` vm_vt ON pvc_vt.vehicle_model_id = vm_vt.id
                WHERE vm_vt.vehicle_type = ?
            )";
            $params[] = $filters['vehicle_type'];
        }

        if (!empty($filters['in_stock_only'])) {
            $sql .= " AND p.current_stock > 0 AND p.is_catalog_only = 0";
        }

        if (!empty($filters['is_featured'])) {
            $sql .= " AND p.is_featured = 1";
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Find single product for online product detail view
     */
    public static function findOnline(int $productId): ?array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT p.id, p.sku, p.name, p.description, p.online_description,
                   p.selling_price, p.current_stock, p.reorder_level, p.status, p.is_online,
                   p.is_catalog_only, p.availability_status, p.product_type,
                   p.is_featured, p.image_url, p.warranty_days,
                   c.id as category_id, c.name as category_name,
                   b.id as brand_id, b.name as brand_name,
                   u.short_name as unit_short, u.name as unit_full
            FROM `products` p
            JOIN `categories` c ON p.category_id = c.id
            LEFT JOIN `brands` b ON p.brand_id = b.id
            JOIN `units` u ON p.unit_id = u.id
            WHERE p.id = ? AND p.status = 'active' AND p.is_online = 1
        ");
        $stmt->execute([$productId]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$product) {
            return null;
        }

        $product['availability'] = self::getAvailabilityInfo($product);
        $product['in_stock'] = ((int)$product['current_stock'] > 0 && empty($product['is_catalog_only']));
        $product['image_url'] = $product['image_url'] ?: '/assets/img/spare_part_placeholder.svg';

        // Fetch multiple images from product_images table
        $stmtImages = $pdo->prepare("
            SELECT id, image_url, alt_text, sort_order, is_primary
            FROM `product_images`
            WHERE product_id = ?
            ORDER BY is_primary DESC, sort_order ASC, id ASC
        ");
        $stmtImages->execute([$productId]);
        $gallery = $stmtImages->fetchAll(PDO::FETCH_ASSOC);

        // If gallery is empty but product has image_url, construct primary
        if (empty($gallery) && !empty($product['image_url'])) {
            $gallery = [[
                'id'         => 0,
                'image_url'  => $product['image_url'],
                'alt_text'   => $product['name'],
                'is_primary' => 1
            ]];
        }
        $product['images'] = $gallery;

        // Fetch compatible vehicles
        $stmtVehicles = $pdo->prepare("
            SELECT vm.id as vehicle_model_id, vm.model_name, vm.vehicle_type,
                   vm.engine_capacity, vm.year_from, vm.year_to,
                   vb.name as brand_name, pvc.notes as compatibility_notes
            FROM `product_vehicle_compatibility` pvc
            JOIN `vehicle_models` vm ON pvc.vehicle_model_id = vm.id
            JOIN `vehicle_brands` vb ON vm.brand_id = vb.id
            WHERE pvc.product_id = ? AND vm.status = 'active'
            ORDER BY vm.vehicle_type ASC, vb.name ASC, vm.model_name ASC
        ");
        $stmtVehicles->execute([$productId]);
        $product['compatible_vehicles'] = $stmtVehicles->fetchAll(PDO::FETCH_ASSOC);

        return $product;
    }

    /**
     * Fetch featured online products
     */
    public static function getFeaturedOnlineProducts(int $limit = 8): array {
        return self::getOnlineProducts(['is_featured' => 1], $limit);
    }

    /**
     * Fetch related products in the same category or compatible vehicle
     */
    public static function getRelatedOnlineProducts(int $productId, int $categoryId, int $limit = 4): array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT p.id, p.sku, p.name, p.selling_price, p.current_stock, p.image_url,
                   p.is_catalog_only, p.availability_status,
                   c.name as category_name, b.name as brand_name, u.short_name as unit_short
            FROM `products` p
            JOIN `categories` c ON p.category_id = c.id
            LEFT JOIN `brands` b ON p.brand_id = b.id
            JOIN `units` u ON p.unit_id = u.id
            WHERE p.category_id = ? AND p.id != ? AND p.status = 'active' AND p.is_online = 1
            ORDER BY p.is_featured DESC, p.id DESC LIMIT ?
        ");
        $stmt->bindValue(1, $categoryId, PDO::PARAM_INT);
        $stmt->bindValue(2, $productId, PDO::PARAM_INT);
        $stmt->bindValue(3, $limit, PDO::PARAM_INT);
        $stmt->execute();
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($items as &$it) {
            $it['image_url'] = $it['image_url'] ?: '/assets/img/spare_part_placeholder.svg';
            $it['in_stock'] = ((int)$it['current_stock'] > 0 && empty($it['is_catalog_only']));
            $it['availability'] = self::getAvailabilityInfo($it);
        }
        return $items;
    }
}

