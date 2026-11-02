<?php
/**
 * NGENZI MOTO SPARE PARTS
 * Product Demand & Discovery Model (Phase 17)
 * Tracks Customer Searches, Compatibility Queries & Out-of-Stock Inquiries
 * Enables Owner to Discover High-Demand Spares & Convert Catalog Items
 * Dar es Salaam, Tanzania
 */

namespace App\Models;

use App\Core\Database;
use App\Core\Helper;
use PDO;
use Exception;

class ProductDemand {

    /**
     * Record a customer demand event (Search, View, Out of Stock, Wishlist)
     */
    public static function logEvent(
        string $eventType,
        ?int $productId = null,
        ?string $searchTerm = null,
        ?int $vehicleModelId = null,
        ?int $categoryId = null,
        ?int $customerId = null
    ): bool {
        try {
            $pdo = Database::getConnection();
            $ip = $_SERVER['REMOTE_ADDR'] ?? null;

            $stmt = $pdo->prepare("
                INSERT INTO `product_demand_events`
                (product_id, search_term, vehicle_model_id, category_id, event_type, customer_id, ip_address, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            return $stmt->execute([
                $productId,
                $searchTerm ? trim(substr($searchTerm, 0, 255)) : null,
                $vehicleModelId,
                $categoryId,
                $eventType,
                $customerId,
                $ip
            ]);
        } catch (Exception $e) {
            // Fail silently to never block customer browsing experience
            return false;
        }
    }

    /**
     * Get aggregated demand metrics for Owner Assistance Dashboard
     */
    public static function getDemandMetrics(int $limit = 50): array {
        $pdo = Database::getConnection();

        // 1. Existing products / Catalog items demand summary
        $sql = "
            SELECT * FROM (
                SELECT 
                    p.id,
                    p.sku,
                    p.name,
                    p.is_catalog_only,
                    p.availability_status,
                    p.current_stock,
                    p.selling_price,
                    c.name as category_name,
                    b.name as brand_name,
                    COUNT(DISTINCT CASE WHEN pde.event_type = 'view' THEN pde.id END) as views_count,
                    COUNT(DISTINCT CASE WHEN pde.event_type = 'search' THEN pde.id END) as search_count,
                    COUNT(DISTINCT CASE WHEN pde.event_type = 'compatibility_search' THEN pde.id END) as compatibility_count,
                    COUNT(DISTINCT CASE WHEN pde.event_type = 'out_of_stock_request' THEN pde.id END) as oos_requests_count,
                    (SELECT COUNT(*) FROM customer_wishlists cw WHERE cw.product_id = p.id) as wishlist_count,
                    (SELECT COUNT(*) FROM online_order_items ooi WHERE ooi.product_id = p.id) as order_count
                FROM `products` p
                JOIN `categories` c ON p.category_id = c.id
                LEFT JOIN `brands` b ON p.brand_id = b.id
                LEFT JOIN `product_demand_events` pde ON p.id = pde.product_id
                WHERE p.status = 'active'
                GROUP BY p.id
            ) as demand_summary
            ORDER BY (views_count * 1 + search_count * 2 + oos_requests_count * 4 + wishlist_count * 3 + order_count * 5) DESC
            LIMIT ?
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 2. Top searched terms without exact product match (Customer Inquiries)
        $searchSql = "
            SELECT 
                search_term,
                COUNT(*) as search_count,
                MAX(created_at) as last_searched
            FROM `product_demand_events`
            WHERE event_type = 'search' AND search_term IS NOT NULL AND search_term != ''
            GROUP BY LOWER(search_term)
            ORDER BY search_count DESC
            LIMIT 20
        ";
        $topSearches = $pdo->query($searchSql)->fetchAll(PDO::FETCH_ASSOC);

        return [
            'products'    => $products,
            'topSearches' => $topSearches
        ];
    }

    /**
     * Convert catalog/reference item to an active sellable inventory product
     * Owner/Manager explicitly assigns buying price, selling price, and initial stock
     */
    public static function convertCatalogProduct(
        int $productId,
        float $buyingPrice,
        float $sellingPrice,
        int $initialStock = 0,
        ?int $userId = null
    ): bool {
        $pdo = Database::getConnection();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare("SELECT * FROM `products` WHERE id = ?");
            $stmt->execute([$productId]);
            $prod = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$prod) {
                throw new Exception("Kipuri hakikupatikana.");
            }

            if ($buyingPrice < 0 || $sellingPrice <= 0) {
                throw new Exception("Tafadhali jaza bei halali ya kununua na kuuza.");
            }

            $update = $pdo->prepare("
                UPDATE `products`
                SET is_catalog_only = 0,
                    availability_status = 'in_stock',
                    buying_price = ?,
                    selling_price = ?,
                    recommended_selling_price = ?,
                    minimum_selling_price = ?,
                    current_stock = 0,
                    status = 'active',
                    is_online = 1,
                    updated_at = NOW()
                WHERE id = ?
            ");

            $minPrice = $sellingPrice * 0.95;
            $update->execute([
                $buyingPrice,
                $sellingPrice,
                $sellingPrice,
                $minPrice,
                $productId
            ]);

            // If initial stock was given, record opening stock inventory movement
            if ($initialStock > 0) {
                Inventory::recordMovement(
                    $productId,
                    'opening_stock',
                    $initialStock,
                    'catalog_conversion',
                    $productId,
                    'Kubadilisha katalogi kuwa bidhaa halisi ya stoo',
                    $buyingPrice,
                    $userId,
                    $pdo
                );
            }

            $pdo->commit();

            Helper::logAudit('CATALOG_CONVERTED_TO_SELLABLE', 'PRODUCTS', $productId, null, [
                'name'          => $prod['name'],
                'sku'           => $prod['sku'],
                'buying_price'  => $buyingPrice,
                'selling_price' => $sellingPrice,
                'initial_stock' => $initialStock,
                'user_id'       => $userId
            ]);

            return true;
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }
}
