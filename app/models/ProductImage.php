<?php
/**
 * NGENZI MOTO SPARE PARTS
 * Product Image Model & Upload Handler (Phase 17)
 * Multiple Product Images & Secure Media Management
 * Dar es Salaam, Tanzania
 */

namespace App\Models;

use App\Core\Database;
use App\Core\Helper;
use PDO;
use Exception;

class ProductImage {

    public const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];
    public const ALLOWED_MIME_TYPES = ['image/jpeg', 'image/png', 'image/webp'];
    public const MAX_FILE_SIZE_BYTES = 5242880; // 5 MB

    /**
     * Get all images for a product
     */
    public static function forProduct(int $productId): array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT * FROM `product_images`
            WHERE product_id = ?
            ORDER BY is_primary DESC, sort_order ASC, id ASC
        ");
        $stmt->execute([$productId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Add new image record for product
     */
    public static function add(int $productId, string $imageUrl, ?string $altText = null, int $sortOrder = 0, bool $isPrimary = false): int {
        $pdo = Database::getConnection();

        if ($isPrimary) {
            // Unset existing primary
            $pdo->prepare("UPDATE `product_images` SET is_primary = 0 WHERE product_id = ?")->execute([$productId]);
            // Also update product main image_url
            $pdo->prepare("UPDATE `products` SET image_url = ? WHERE id = ?")->execute([$imageUrl, $productId]);
        }

        $stmt = $pdo->prepare("
            INSERT INTO `product_images` (product_id, image_url, alt_text, sort_order, is_primary, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$productId, $imageUrl, $altText, $sortOrder, $isPrimary ? 1 : 0]);
        return (int)$pdo->lastInsertId();
    }

    /**
     * Set specific image as primary for the product
     */
    public static function setPrimary(int $imageId, int $productId): bool {
        $pdo = Database::getConnection();

        $stmt = $pdo->prepare("SELECT image_url FROM `product_images` WHERE id = ? AND product_id = ?");
        $stmt->execute([$imageId, $productId]);
        $imageUrl = $stmt->fetchColumn();

        if (!$imageUrl) {
            return false;
        }

        $pdo->prepare("UPDATE `product_images` SET is_primary = 0 WHERE product_id = ?")->execute([$productId]);
        $pdo->prepare("UPDATE `product_images` SET is_primary = 1 WHERE id = ?")->execute([$imageId]);
        $pdo->prepare("UPDATE `products` SET image_url = ? WHERE id = ?")->execute([$imageUrl, $productId]);

        return true;
    }

    /**
     * Delete image
     */
    public static function delete(int $imageId, int $productId): bool {
        $pdo = Database::getConnection();

        $stmt = $pdo->prepare("SELECT image_url, is_primary FROM `product_images` WHERE id = ? AND product_id = ?");
        $stmt->execute([$imageId, $productId]);
        $img = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$img) {
            return false;
        }

        $pdo->prepare("DELETE FROM `product_images` WHERE id = ?")->execute([$imageId]);

        // Attempt to remove physical file if within public/uploads/
        if (strpos($img['image_url'], '/uploads/products/') === 0) {
            $filePath = __DIR__ . '/../../public' . $img['image_url'];
            if (file_exists($filePath)) {
                @unlink($filePath);
            }
        }

        // If it was primary, promote next available image
        if ($img['is_primary']) {
            $next = $pdo->prepare("SELECT id, image_url FROM `product_images` WHERE product_id = ? ORDER BY sort_order ASC, id ASC LIMIT 1");
            $next->execute([$productId]);
            $nextImg = $next->fetch(PDO::FETCH_ASSOC);
            if ($nextImg) {
                $pdo->prepare("UPDATE `product_images` SET is_primary = 1 WHERE id = ?")->execute([$nextImg['id']]);
                $pdo->prepare("UPDATE `products` SET image_url = ? WHERE id = ?")->execute([$nextImg['image_url'], $productId]);
            } else {
                $pdo->prepare("UPDATE `products` SET image_url = NULL WHERE id = ?")->execute([$productId]);
            }
        }

        return true;
    }

    /**
     * Securely validate and process image upload
     * Enforces MIME type, extension, size, and sanitization
     */
    public static function validateAndUpload(array $file, int $productId): string {
        if (!isset($file['error']) || is_array($file['error'])) {
            throw new Exception("Hitilafu kwenye upakiaji wa faili la picha.");
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("Upakiaji ulishindikana (Error Code: {$file['error']}).");
        }

        if ($file['size'] > self::MAX_FILE_SIZE_BYTES) {
            throw new Exception("Ukubwa wa picha umezidi kiwango cha juu (Upeo: 5MB).");
        }

        // Validate extension
        $rawFilename = $file['name'] ?? '';
        $ext = strtolower(pathinfo($rawFilename, PATHINFO_EXTENSION));

        if (!in_array($ext, self::ALLOWED_EXTENSIONS, true)) {
            throw new Exception("Muundo wa faili haukubaliki. Tafadhali pakia picha ya JPG, JPEG, PNG au WebP pekee.");
        }

        // Validate MIME type safely using finfo
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);

        if (!in_array($mime, self::ALLOWED_MIME_TYPES, true)) {
            throw new Exception("Aina ya picha haikubaliki (MIME: {$mime}). Tafadhali pakia picha halisi.");
        }

        // Generate safe unique filename
        $uploadDir = __DIR__ . '/../../public/uploads/products/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $safeName = sprintf("prod_%d_%s_%s.%s", $productId, date('Ymd_His'), bin2hex(random_bytes(4)), $ext);
        $destination = $uploadDir . $safeName;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            throw new Exception("Imeshindwa kuhifadhi faili kwenye seva.");
        }

        return '/uploads/products/' . $safeName;
    }
}
