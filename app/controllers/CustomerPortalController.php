<?php
/**
 * NGENZI MOTO SPARE PARTS
 * CustomerPortalController (Phase 16)
 * Customer Account Dashboard, Address Book, Order Tracking, Returns & Wishlist
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Database;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\OnlineOrder;
use App\Models\OnlinePayment;
use App\Models\Wishlist;
use App\Models\CustomerNotification;
use App\Models\Returns;
use PDO;
use Exception;

class CustomerPortalController extends Controller {

    public function __construct() {
        Auth::requireCustomer();
    }

    /**
     * Customer Dashboard
     */
    public function dashboard(): void {
        $customerId = Auth::customerId();
        $customer = Auth::customer();

        $recentOrders = OnlineOrder::forCustomer($customerId, 1, 5);
        $totalOrders = OnlineOrder::countForCustomer($customerId);
        $wishlistCount = Wishlist::count($customerId);
        $unreadNotifications = CustomerNotification::countUnread($customerId);
        $addresses = CustomerAddress::forCustomer($customerId);

        $pdo = Database::getConnection();
        $pendingOrders = (int)$pdo->query("SELECT COUNT(*) FROM `online_orders` WHERE customer_id = {$customerId} AND status IN ('PENDING', 'PAYMENT_PENDING', 'PROCESSING', 'OUT_FOR_DELIVERY')")->fetchColumn();
        $completedOrders = (int)$pdo->query("SELECT COUNT(*) FROM `online_orders` WHERE customer_id = {$customerId} AND status IN ('DELIVERED', 'COMPLETED')")->fetchColumn();

        $this->render('customer/dashboard', [
            'pageTitle'           => 'Dashibodi ya Mteja — Ngenzi Moto Spare Parts',
            'customer'            => $customer,
            'recentOrders'        => $recentOrders,
            'totalOrders'         => $totalOrders,
            'pendingOrders'       => $pendingOrders,
            'completedOrders'     => $completedOrders,
            'wishlistCount'       => $wishlistCount,
            'unreadNotifications' => $unreadNotifications,
            'addressesCount'      => count($addresses)
        ], 'store');
    }

    /**
     * Customer Profile
     */
    public function profile(): void {
        $customerId = Auth::customerId();
        $pdo = Database::getConnection();
        $customer = $pdo->query("SELECT * FROM `customers` WHERE id = {$customerId}")->fetch(PDO::FETCH_ASSOC);

        $this->render('customer/profile', [
            'pageTitle' => 'Taarifa za Akaunti Yangu — Ngenzi Moto Spare Parts',
            'customer'  => $customer
        ], 'store');
    }

    public function updateProfile(): void {
        $customerId = Auth::customerId();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('customer_profile');
        }

        try {
            Customer::updateOnlineProfile($customerId, $_POST);
            $this->setFlash('success', 'Taarifa za akaunti yako zimesasishwa kikamilifu.');
        } catch (Exception $e) {
            $this->setFlash('danger', $e->getMessage());
        }

        $this->redirect('customer_profile');
    }

    /**
     * Delivery Addresses Management
     */
    public function addresses(): void {
        $customerId = Auth::customerId();
        $addresses = CustomerAddress::forCustomer($customerId);

        $this->render('customer/addresses', [
            'pageTitle' => 'Anwani Zangu za Usafirishaji — Ngenzi Moto Spare Parts',
            'addresses' => $addresses
        ], 'store');
    }

    public function storeAddress(): void {
        $customerId = Auth::customerId();

        try {
            $_POST['customer_id'] = $customerId;
            CustomerAddress::create($_POST);
            $this->setFlash('success', 'Anwani mpya imeongezwa kikamilifu.');
        } catch (Exception $e) {
            $this->setFlash('danger', $e->getMessage());
        }

        $this->redirect('customer_addresses');
    }

    public function updateAddress(): void {
        $customerId = Auth::customerId();
        $addressId = (int)($_POST['address_id'] ?? 0);

        try {
            CustomerAddress::update($addressId, $customerId, $_POST);
            $this->setFlash('success', 'Anwani imesasishwa.');
        } catch (Exception $e) {
            $this->setFlash('danger', $e->getMessage());
        }

        $this->redirect('customer_addresses');
    }

    public function setDefaultAddress(): void {
        $customerId = Auth::customerId();
        $addressId = (int)($_GET['id'] ?? 0);

        CustomerAddress::setDefault($addressId, $customerId);
        $this->setFlash('success', 'Anwani chaguomsingi imewekwa.');
        $this->redirect('customer_addresses');
    }

    public function deleteAddress(): void {
        $customerId = Auth::customerId();
        $addressId = (int)($_POST['address_id'] ?? ($_GET['id'] ?? 0));

        try {
            CustomerAddress::delete($addressId, $customerId);
            $this->setFlash('info', 'Anwani imefutwa.');
        } catch (Exception $e) {
            $this->setFlash('danger', $e->getMessage());
        }

        $this->redirect('customer_addresses');
    }

    /**
     * Customer Orders
     */
    public function orders(): void {
        $customerId = Auth::customerId();
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 10;

        $orders = OnlineOrder::forCustomer($customerId, $page, $perPage);
        $totalOrders = OnlineOrder::countForCustomer($customerId);
        $totalPages = ceil($totalOrders / $perPage);

        $this->render('customer/orders', [
            'pageTitle'   => 'Maagizo Yangu — Ngenzi Moto Spare Parts',
            'orders'      => $orders,
            'currentPage' => $page,
            'totalPages'  => $totalPages,
            'totalOrders' => $totalOrders
        ], 'store');
    }

    /**
     * Single Order Details & Tracking
     */
    public function orderDetail(): void {
        $customerId = Auth::customerId();
        $orderId = (int)($_GET['id'] ?? 0);

        $order = OnlineOrder::find($orderId, $customerId);
        if (!$order) {
            $this->setFlash('danger', 'Agizo halikupatikana au huna ruhusa nalo.');
            $this->redirect('customer_orders');
        }

        $this->render('customer/order_show', [
            'pageTitle' => "Ufuatiliaji wa Agizo #{$order['order_number']}",
            'order'     => $order
        ], 'store');
    }

    /**
     * Cancel Order
     */
    public function cancelOrder(): void {
        $customerId = Auth::customerId();
        $orderId = (int)($_POST['order_id'] ?? 0);
        $reason = trim($_POST['reason'] ?? '');

        try {
            OnlineOrder::cancelByCustomer($orderId, $customerId, $reason);
            $this->setFlash('success', 'Agizo limeghairiwa na stoo imerejeshwa kikamilifu.');
        } catch (Exception $e) {
            $this->setFlash('danger', $e->getMessage());
        }

        $this->redirect('customer_order_show&id=' . $orderId);
    }

    /**
     * Printable Order Invoice & Payment Receipt
     */
    public function printInvoice(): void {
        $customerId = Auth::customerId();
        $orderId = (int)($_GET['id'] ?? 0);

        $order = OnlineOrder::find($orderId, $customerId);
        if (!$order) {
            die("Agizo halikupatikana.");
        }

        $this->render('customer/invoice_print', [
            'pageTitle' => "Ankara #{$order['order_number']}",
            'order'     => $order
        ], 'print');
    }

    /**
     * Customer Return Request View
     */
    public function returnRequest(): void {
        $customerId = Auth::customerId();
        $orderId = (int)($_GET['order_id'] ?? 0);

        $order = OnlineOrder::find($orderId, $customerId);
        if (!$order) {
            $this->setFlash('danger', 'Agizo halikupatikana.');
            $this->redirect('customer_orders');
        }

        if (!in_array($order['status'], ['DELIVERED', 'COMPLETED', 'PAID', 'CONFIRMED'])) {
            $this->setFlash('danger', 'Huwezi kuomba kurejesha bidhaa ya agizo ambalo bado halijawasilishwa.');
            $this->redirect('customer_order_show&id=' . $orderId);
        }

        $this->render('customer/return_request', [
            'pageTitle' => "Ombi la Marejesho — Agizo #{$order['order_number']}",
            'order'     => $order
        ], 'store');
    }

    /**
     * Store Customer Return Request (Integrates with Phase 11 Returns)
     */
    public function storeReturnRequest(): void {
        $customerId = Auth::customerId();
        $orderId = (int)($_POST['order_id'] ?? 0);
        $pdo = Database::getConnection();

        $order = OnlineOrder::find($orderId, $customerId);
        if (!$order) {
            $this->setFlash('danger', 'Agizo halikupatikana.');
            $this->redirect('customer_orders');
        }

        $items = $_POST['items'] ?? [];
        $reason = trim($_POST['reason'] ?? 'CUSTOMER_RETURN');
        $notes = trim($_POST['notes'] ?? '');

        if (empty($items)) {
            $this->setFlash('danger', 'Tafadhali chagua angalau kipuri kimoja cha kurejesha.');
            $this->redirect('customer_return_create&order_id=' . $orderId);
        }

        try {
            $pdo->beginTransaction();

            $saleId = !empty($order['sale_id']) ? (int)$order['sale_id'] : OnlineOrder::integrateWithSales($orderId);
            $returnNo = sprintf("RET-ONL-%06d", $orderId);

            $totalRefundAmount = 0.00;
            $returnItemsToInsert = [];

            foreach ($items as $productId => $itData) {
                $qty = (int)($itData['quantity'] ?? 0);
                if ($qty <= 0) continue;

                // Fetch item from order
                $stmt = $pdo->prepare("SELECT * FROM `online_order_items` WHERE order_id = ? AND product_id = ?");
                $stmt->execute([$orderId, $productId]);
                $orderItem = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$orderItem) continue;

                $unitPrice = (float)$orderItem['unit_price'];
                $itemSubtotal = $unitPrice * $qty;
                $totalRefundAmount += $itemSubtotal;

                $returnItemsToInsert[] = [
                    'product_id'   => $orderItem['product_id'],
                    'product_name' => $orderItem['product_name'],
                    'product_sku'  => $orderItem['product_sku'],
                    'quantity'     => $qty,
                    'unit_price'   => $unitPrice,
                    'unit_cost'    => (float)$orderItem['unit_cost'],
                    'subtotal'     => $itemSubtotal
                ];
            }

            if (empty($returnItemsToInsert)) {
                throw new Exception("Hakuna bidhaa zenye idadi sahihi ya kurejesha zilizochaguliwa.");
            }

            // Insert into `returns` table with status 'requested'
            $stmtReturn = $pdo->prepare("
                INSERT INTO `returns` (
                    return_number, sale_id, customer_id, return_type, return_date,
                    reason, subtotal, return_fee, refund_amount, status,
                    notes, created_by, created_at
                ) VALUES (
                    ?, ?, ?, ?, CURDATE(),
                    ?, ?, 0.00, ?, 'requested',
                    ?, ?, NOW()
                )
            ");

            $stmtReturn->execute([
                $returnNo,
                $saleId,
                $customerId,
                $reason,
                $reason,
                $totalRefundAmount,
                $totalRefundAmount,
                $notes,
                1 // Admin/System context
            ]);

            $returnId = (int)$pdo->lastInsertId();

            // Insert into `return_items`
            $stmtRItem = $pdo->prepare("
                INSERT INTO `return_items` (
                    return_id, product_id, product_name, product_sku,
                    quantity, unit_selling_price, unit_refund_price, unit_cost,
                    subtotal, condition_status, return_reason, created_at
                ) VALUES (
                    ?, ?, ?, ?,
                    ?, ?, ?, ?,
                    ?, 'resellable', ?, NOW()
                )
            ");

            foreach ($returnItemsToInsert as $rItem) {
                $stmtRItem->execute([
                    $returnId,
                    $rItem['product_id'],
                    $rItem['product_name'],
                    $rItem['product_sku'],
                    $rItem['quantity'],
                    $rItem['unit_price'],
                    $rItem['unit_price'],
                    $rItem['unit_cost'],
                    $rItem['subtotal'],
                    $notes ?: 'Ombi la mteja mtandaoni'
                ]);
            }

            // Create notification
            $pdo->prepare("
                INSERT INTO `customer_notifications` (customer_id, title, message, type, action_url, created_at)
                VALUES (?, ?, ?, 'system', ?, NOW())
            ")->execute([
                $customerId,
                "Ombi la Marejesho #{$returnNo} Limepokelewa",
                "Ombi lako la kurejesha bidhaa limepokelewa na linakaguliwa na wasimamizi wa duka. Utataarifiwa litakapoidhinishwa.",
                "index.php?route=customer_order_show&id={$orderId}"
            ]);

            $pdo->commit();
            $this->setFlash('success', "Ombi lako la marejesho (#{$returnNo}) limewasilishwa kwa uongozi wa duka. Tutalipitia mara moja.");
            $this->redirect('customer_order_show&id=' . $orderId);

        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $this->setFlash('danger', $e->getMessage());
            $this->redirect('customer_return_create&order_id=' . $orderId);
        }
    }

    /**
     * Wishlist Management
     */
    public function wishlist(): void {
        $customerId = Auth::customerId();
        $items = Wishlist::forCustomer($customerId);

        $this->render('customer/wishlist', [
            'pageTitle' => 'Vipuri Nilivyohifadhi (Wishlist) — Ngenzi Moto Spare Parts',
            'items'     => $items
        ], 'store');
    }

    public function addToWishlist(): void {
        $customerId = Auth::customerId();
        $productId = (int)($_POST['product_id'] ?? ($_GET['product_id'] ?? 0));
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

        Wishlist::add($customerId, $productId);

        if ($isAjax) {
            $this->json(['success' => true, 'count' => Wishlist::count($customerId)]);
        } else {
            $this->setFlash('success', 'Kipuri kimehifadhiwa kwenye orodha yako (Wishlist).');
            $this->redirect('product_detail&id=' . $productId);
        }
    }

    public function removeFromWishlist(): void {
        $customerId = Auth::customerId();
        $productId = (int)($_POST['product_id'] ?? ($_GET['product_id'] ?? 0));

        Wishlist::remove($customerId, $productId);
        $this->setFlash('info', 'Kipuri kimeondolewa kwenye orodha yako ya unayopenda.');
        $this->redirect('customer_wishlist');
    }

    public function moveWishlistToCart(): void {
        $customerId = Auth::customerId();
        $productId = (int)($_POST['product_id'] ?? ($_GET['product_id'] ?? 0));

        try {
            Wishlist::moveToCart($customerId, $productId);
            $this->setFlash('success', 'Kipuri kimehamishiwa kwenye kapu la ununuzi.');
        } catch (Exception $e) {
            $this->setFlash('danger', $e->getMessage());
        }

        $this->redirect('customer_wishlist');
    }

    /**
     * Notifications
     */
    public function notifications(): void {
        $customerId = Auth::customerId();
        $notifications = CustomerNotification::forCustomer($customerId, 50);

        $this->render('customer/notifications', [
            'pageTitle'     => 'Taarifa & Meseji Zangu — Ngenzi Moto Spare Parts',
            'notifications' => $notifications
        ], 'store');
    }

    public function markNotificationRead(): void {
        $customerId = Auth::customerId();
        $notifId = (int)($_POST['id'] ?? ($_GET['id'] ?? 0));

        if ($notifId > 0) {
            CustomerNotification::markAsRead($notifId, $customerId);
        } else {
            CustomerNotification::markAllAsRead($customerId);
        }

        $this->redirect('customer_notifications');
    }
}
