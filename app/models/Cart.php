<?php
/**
 * NGENZI MOTO SPARE PARTS
 * Cart Model (Phase 16)
 * Server-Side Price Recalculation, Stock Validation & Session Synchronization
 */

namespace App\Models;

use App\Core\Database;
use App\Core\Auth;
use PDO;
use Exception;

class Cart {
    /**
     * Get or create active cart ID for current session / customer
     */
    public static function getCartId(): int {
        $pdo = Database::getConnection();
        Auth::startSession();

        $sessionId = session_id();
        $customerId = Auth::customerId();

        if ($customerId) {
            $stmt = $pdo->prepare("SELECT id FROM `carts` WHERE customer_id = ? ORDER BY id DESC LIMIT 1");
            $stmt->execute([$customerId]);
            $cartId = $stmt->fetchColumn();
            if ($cartId) {
                return (int)$cartId;
            }
        }

        // Check by session_id
        $stmtSession = $pdo->prepare("SELECT id FROM `carts` WHERE session_id = ? ORDER BY id DESC LIMIT 1");
        $stmtSession->execute([$sessionId]);
        $cartId = $stmtSession->fetchColumn();

        if ($cartId) {
            if ($customerId) {
                $pdo->prepare("UPDATE `carts` SET customer_id = ? WHERE id = ?")->execute([$customerId, $cartId]);
            }
            return (int)$cartId;
        }

        // Create new cart
        $stmtInsert = $pdo->prepare("INSERT INTO `carts` (customer_id, session_id, created_at) VALUES (?, ?, NOW())");
        $stmtInsert->execute([$customerId, $sessionId]);
        return (int)$pdo->lastInsertId();
    }

    /**
     * Add product to cart with strict stock availability validation
     */
    public static function addItem(int $productId, int $quantity = 1): array {
        $pdo = Database::getConnection();
        $cartId = self::getCartId();

        if ($quantity <= 0) {
            $quantity = 1;
        }

        // Validate product
        $stmtProduct = $pdo->prepare("
            SELECT id, name, sku, selling_price, current_stock, status, is_online 
            FROM `products` 
            WHERE id = ?
        ");
        $stmtProduct->execute([$productId]);
        $product = $stmtProduct->fetch(PDO::FETCH_ASSOC);

        if (!$product) {
            throw new Exception("Kipuri hakikupatikana.");
        }
        if ($product['status'] !== 'active' || (int)$product['is_online'] !== 1) {
            throw new Exception("Kipuri '{$product['name']}' hakipatikani kwa mauzo ya mtandaoni kwa sasa.");
        }

        $currentStock = (int)$product['current_stock'];
        if ($currentStock <= 0) {
            throw new Exception("Samahani, kipuri '{$product['name']}' kimeisha stoo kwa sasa.");
        }

        // Check if item already in cart
        $stmtItem = $pdo->prepare("SELECT id, quantity FROM `cart_items` WHERE cart_id = ? AND product_id = ?");
        $stmtItem->execute([$cartId, $productId]);
        $existing = $stmtItem->fetch(PDO::FETCH_ASSOC);

        $newQty = $existing ? ((int)$existing['quantity'] + $quantity) : $quantity;

        if ($newQty > $currentStock) {
            throw new Exception("Idadi uliyoomba inazidi stoo iliyopo. Kilichopo stoo ni vipuri {$currentStock}.");
        }

        if ($existing) {
            $stmtUpdate = $pdo->prepare("UPDATE `cart_items` SET quantity = ?, updated_at = NOW() WHERE id = ?");
            $stmtUpdate->execute([$newQty, $existing['id']]);
        } else {
            $stmtInsert = $pdo->prepare("INSERT INTO `cart_items` (cart_id, product_id, quantity, created_at) VALUES (?, ?, ?, NOW())");
            $stmtInsert->execute([$cartId, $productId, $newQty]);
        }

        return self::getSummary();
    }

    /**
     * Update item quantity in cart
     */
    public static function updateQuantity(int $productId, int $quantity): array {
        $pdo = Database::getConnection();
        $cartId = self::getCartId();

        if ($quantity <= 0) {
            return self::removeItem($productId);
        }

        $stmtProduct = $pdo->prepare("SELECT id, name, current_stock, status, is_online FROM `products` WHERE id = ?");
        $stmtProduct->execute([$productId]);
        $product = $stmtProduct->fetch(PDO::FETCH_ASSOC);

        if (!$product || $product['status'] !== 'active' || (int)$product['is_online'] !== 1) {
            self::removeItem($productId);
            throw new Exception("Kipuri hakipatikani kwa sasa na kimeondolewa kwenye kapu.");
        }

        $currentStock = (int)$product['current_stock'];
        if ($quantity > $currentStock) {
            throw new Exception("Idadi inazidi stoo iliyopo ({$currentStock}).");
        }

        $stmt = $pdo->prepare("UPDATE `cart_items` SET quantity = ?, updated_at = NOW() WHERE cart_id = ? AND product_id = ?");
        $stmt->execute([$quantity, $cartId, $productId]);

        return self::getSummary();
    }

    /**
     * Remove item from cart
     */
    public static function removeItem(int $productId): array {
        $pdo = Database::getConnection();
        $cartId = self::getCartId();

        $stmt = $pdo->prepare("DELETE FROM `cart_items` WHERE cart_id = ? AND product_id = ?");
        $stmt->execute([$cartId, $productId]);

        return self::getSummary();
    }

    /**
     * Clear all items in cart
     */
    public static function clear(): void {
        $pdo = Database::getConnection();
        $cartId = self::getCartId();
        $pdo->prepare("DELETE FROM `cart_items` WHERE cart_id = ?")->execute([$cartId]);
    }

    /**
     * Get all cart items with live database recalculation
     */
    public static function getItems(): array {
        $pdo = Database::getConnection();
        $cartId = self::getCartId();

        $stmt = $pdo->prepare("
            SELECT ci.id as cart_item_id, ci.quantity, ci.product_id,
                   p.name as product_name, p.sku as product_sku, p.selling_price,
                   p.buying_price, p.current_stock, p.status as product_status,
                   p.is_online, p.image_url,
                   c.name as category_name, b.name as brand_name, u.short_name as unit_name
            FROM `cart_items` ci
            JOIN `products` p ON ci.product_id = p.id
            JOIN `categories` c ON p.category_id = c.id
            LEFT JOIN `brands` b ON p.brand_id = b.id
            JOIN `units` u ON p.unit_id = u.id
            WHERE ci.cart_id = ?
            ORDER BY ci.id ASC
        ");
        $stmt->execute([$cartId]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $processed = [];
        foreach ($items as $item) {
            $unitPrice = (float)$item['selling_price'];
            $qty = (int)$item['quantity'];
            $subtotal = $unitPrice * $qty;
            $stock = (int)$item['current_stock'];

            $isAvailable = ($item['product_status'] === 'active' && (int)$item['is_online'] === 1 && $stock >= $qty);

            $processed[] = [
                'cart_item_id'  => (int)$item['cart_item_id'],
                'product_id'    => (int)$item['product_id'],
                'product_name'  => $item['product_name'],
                'product_sku'   => $item['product_sku'],
                'category_name' => $item['category_name'],
                'brand_name'    => $item['brand_name'] ?? 'Generic',
                'unit_name'     => $item['unit_name'],
                'image_url'     => $item['image_url'] ?: '/assets/img/spare_part_placeholder.png',
                'unit_price'    => $unitPrice,
                'quantity'      => $qty,
                'subtotal'      => $subtotal,
                'current_stock' => $stock,
                'is_available'  => $isAvailable,
                'stock_warning' => ($stock < $qty) ? "Stoo iliyopo ni {$stock} pekee" : null
            ];
        }

        return $processed;
    }

    /**
     * Get cart summary metrics (count, subtotal, delivery fee estimate, total)
     */
    public static function getSummary(): array {
        $items = self::getItems();
        $totalItems = 0;
        $totalQuantity = 0;
        $subtotal = 0.00;
        $allAvailable = true;

        foreach ($items as $item) {
            $totalItems++;
            $totalQuantity += $item['quantity'];
            $subtotal += $item['subtotal'];
            if (!$item['is_available']) {
                $allAvailable = false;
            }
        }

        // Manual store pickup mode: No delivery fee charged (Bure / Store Pickup)
        $deliveryFee = 0.00;
        $total = $subtotal;

        return [
            'item_count'     => $totalItems,
            'total_quantity' => $totalQuantity,
            'subtotal'       => $subtotal,
            'delivery_fee'   => $deliveryFee,
            'total'          => $total,
            'all_available'  => $allAvailable,
            'items'          => $items
        ];
    }

    /**
     * Merge guest session cart into logged-in customer cart
     */
    public static function syncCustomerCart(int $customerId): void {
        $pdo = Database::getConnection();
        Auth::startSession();
        $sessionId = session_id();

        // Find guest cart by session_id
        $stmt = $pdo->prepare("SELECT id FROM `carts` WHERE session_id = ? AND (customer_id IS NULL OR customer_id != ?)");
        $stmt->execute([$sessionId, $customerId]);
        $guestCartId = $stmt->fetchColumn();

        if ($guestCartId) {
            // Find or create customer cart
            $stmtCust = $pdo->prepare("SELECT id FROM `carts` WHERE customer_id = ?");
            $stmtCust->execute([$customerId]);
            $custCartId = $stmtCust->fetchColumn();

            if (!$custCartId) {
                // Simply assign guest cart to customer
                $pdo->prepare("UPDATE `carts` SET customer_id = ? WHERE id = ?")->execute([$customerId, $guestCartId]);
            } else {
                // Merge items from guest cart into customer cart
                $guestItems = $pdo->query("SELECT product_id, quantity FROM `cart_items` WHERE cart_id = {$guestCartId}")->fetchAll(PDO::FETCH_ASSOC);
                foreach ($guestItems as $gi) {
                    $pdo->prepare("
                        INSERT INTO `cart_items` (cart_id, product_id, quantity) 
                        VALUES (?, ?, ?)
                        ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)
                    ")->execute([$custCartId, $gi['product_id'], $gi['quantity']]);
                }
                // Delete guest cart
                $pdo->exec("DELETE FROM `carts` WHERE id = {$guestCartId}");
            }
        }
    }
}
