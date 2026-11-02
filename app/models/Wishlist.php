<?php
/**
 * NGENZI MOTO SPARE PARTS
 * Wishlist Model (Phase 16)
 * Customer Saved Items & Move to Cart
 */

namespace App\Models;

use App\Core\Database;
use App\Models\Cart;
use PDO;
use Exception;

class Wishlist {
    /**
     * Add product to customer's wishlist
     */
    public static function add(int $customerId, int $productId): bool {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            INSERT INTO `customer_wishlists` (customer_id, product_id, created_at)
            VALUES (?, ?, NOW())
            ON DUPLICATE KEY UPDATE created_at = NOW()
        ");
        return $stmt->execute([$customerId, $productId]);
    }

    /**
     * Remove product from wishlist
     */
    public static function remove(int $customerId, int $productId): bool {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("DELETE FROM `customer_wishlists` WHERE customer_id = ? AND product_id = ?");
        return $stmt->execute([$customerId, $productId]);
    }

    /**
     * Check if product is in customer's wishlist
     */
    public static function has(int $customerId, int $productId): bool {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM `customer_wishlists` WHERE customer_id = ? AND product_id = ?");
        $stmt->execute([$customerId, $productId]);
        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * Alias for has()
     */
    public static function isInWishlist(int $customerId, int $productId): bool {
        return self::has($customerId, $productId);
    }

    /**
     * Get all wishlist items for a customer
     */
    public static function forCustomer(int $customerId): array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT cw.id as wishlist_id, cw.created_at as saved_at,
                   p.id as product_id, p.name as product_name, p.sku, p.selling_price,
                   p.current_stock, p.status, p.is_online, p.image_url,
                   c.name as category_name, b.name as brand_name
            FROM `customer_wishlists` cw
            JOIN `products` p ON cw.product_id = p.id
            JOIN `categories` c ON p.category_id = c.id
            LEFT JOIN `brands` b ON p.brand_id = b.id
            WHERE cw.customer_id = ?
            ORDER BY cw.created_at DESC
        ");
        $stmt->execute([$customerId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Count items in customer's wishlist
     */
    public static function count(int $customerId): int {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM `customer_wishlists` WHERE customer_id = ?");
        $stmt->execute([$customerId]);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Move wishlist item to cart
     */
    public static function moveToCart(int $customerId, int $productId): bool {
        Cart::addItem($productId, 1);
        self::remove($customerId, $productId);
        return true;
    }
}
