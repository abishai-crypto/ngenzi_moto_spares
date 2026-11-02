<?php
/**
 * NGENZI MOTO SPARE PARTS
 * ProductReview Model (Phase 16)
 * Verified Purchase Ratings & Customer Product Reviews
 */

namespace App\Models;

use App\Core\Database;
use PDO;
use Exception;

class ProductReview {
    /**
     * Check if customer has verified purchase of this product
     */
    public static function hasPurchased(int $customerId, int $productId): bool {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM `online_order_items` ooi
            JOIN `online_orders` oo ON ooi.order_id = oo.id
            WHERE oo.customer_id = ? AND ooi.product_id = ? AND oo.status IN ('PAID', 'CONFIRMED', 'DELIVERED', 'COMPLETED')
        ");
        $stmt->execute([$customerId, $productId]);
        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * Submit product review (requires verified purchase)
     */
    public static function submit(int $customerId, int $productId, int $rating, string $reviewText): bool {
        $pdo = Database::getConnection();

        if ($rating < 1 || $rating > 5) {
            throw new Exception("Kiwango cha tathmini lazima kiwe kati ya nyota 1 na 5.");
        }
        if (empty(trim($reviewText))) {
            throw new Exception("Tafadhali andika maoni yako kuhusu kipuri hiki.");
        }

        if (!self::hasPurchased($customerId, $productId)) {
            throw new Exception("Huwezi kutoa maoni kwa kipuri ambacho haujawahi kukinunua.");
        }

        $stmt = $pdo->prepare("
            INSERT INTO `product_reviews` (customer_id, product_id, rating, review_text, status, created_at)
            VALUES (?, ?, ?, ?, 'APPROVED', NOW())
        ");
        return $stmt->execute([$customerId, $productId, $rating, trim($reviewText)]);
    }

    /**
     * Get approved reviews for a product
     */
    public static function forProduct(int $productId): array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT pr.*, c.name as customer_name
            FROM `product_reviews` pr
            JOIN `customers` c ON pr.customer_id = c.id
            WHERE pr.product_id = ? AND pr.status = 'APPROVED'
            ORDER BY pr.created_at DESC
        ");
        $stmt->execute([$productId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get average rating and count for a product
     */
    public static function getRatingSummary(int $productId): array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT AVG(rating) as avg_rating, COUNT(*) as review_count
            FROM `product_reviews`
            WHERE product_id = ? AND status = 'APPROVED'
        ");
        $stmt->execute([$productId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return [
            'avg_rating'   => $row['avg_rating'] ? round((float)$row['avg_rating'], 1) : 5.0,
            'review_count' => (int)($row['review_count'] ?? 0)
        ];
    }
}
