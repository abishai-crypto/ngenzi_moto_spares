<?php
/**
 * NGENZI MOTO SPARE PARTS
 * OnlineOrderAdminController (Phase 16)
 * Internal Staff Management for Online Store, Orders, Deliveries & Online Customers
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Database;
use App\Core\Helper;
use App\Models\OnlineOrder;
use App\Models\OnlinePayment;
use App\Models\Delivery;
use App\Models\Setting;
use PDO;
use Exception;

class OnlineOrderAdminController extends Controller {

    public function __construct() {
        Auth::requireRole(['super_admin', 'manager', 'cashier']);
    }

    /**
     * Online Store Staff Dashboard
     */
    public function dashboard(): void {
        $pdo = Database::getConnection();

        // Online Orders Today
        $stmtToday = $pdo->query("SELECT COUNT(*), COALESCE(SUM(total_amount), 0.00) FROM `online_orders` WHERE DATE(created_at) = CURDATE() AND status != 'CANCELLED'");
        [$ordersToday, $salesToday] = $stmtToday->fetch(PDO::FETCH_NUM);

        // Status counts
        $stmtPending = $pdo->query("SELECT COUNT(*) FROM `online_orders` WHERE status IN ('PENDING', 'PAYMENT_PENDING')");
        $pendingOrders = (int)$stmtPending->fetchColumn();

        $stmtPaid = $pdo->query("SELECT COUNT(*) FROM `online_orders` WHERE payment_status = 'paid'");
        $paidOrders = (int)$stmtPaid->fetchColumn();

        $stmtProcessing = $pdo->query("SELECT COUNT(*) FROM `online_orders` WHERE status = 'PROCESSING'");
        $processingOrders = (int)$stmtProcessing->fetchColumn();

        $stmtOutForDel = $pdo->query("SELECT COUNT(*) FROM `online_orders` WHERE status = 'OUT_FOR_DELIVERY'");
        $outForDeliveryOrders = (int)$stmtOutForDel->fetchColumn();

        $stmtDelivered = $pdo->query("SELECT COUNT(*) FROM `online_orders` WHERE status IN ('DELIVERED', 'COMPLETED')");
        $deliveredOrders = (int)$stmtDelivered->fetchColumn();

        // Customers stats
        $stmtCust = $pdo->query("SELECT COUNT(*) FROM `customers` WHERE password IS NOT NULL");
        $onlineCustomers = (int)$stmtCust->fetchColumn();

        $stmtNewCust = $pdo->query("SELECT COUNT(*) FROM `customers` WHERE password IS NOT NULL AND created_at >= DATE_FORMAT(NOW(), '%Y-%m-01')");
        $newCustThisMonth = (int)$stmtNewCust->fetchColumn();

        // Recent 10 online orders
        $recentOrders = $pdo->query("
            SELECT oo.*, c.name as customer_name, c.phone as customer_phone, d.delivery_status
            FROM `online_orders` oo
            JOIN `customers` c ON oo.customer_id = c.id
            LEFT JOIN `deliveries` d ON oo.id = d.order_id
            ORDER BY oo.created_at DESC, oo.id DESC
            LIMIT 10
        ")->fetchAll(PDO::FETCH_ASSOC);

        $this->render('online_admin/dashboard', [
            'pageTitle'            => 'Dashibodi ya Duka la Mtandaoni — Utawala',
            'ordersToday'          => (int)$ordersToday,
            'salesToday'           => (float)$salesToday,
            'pendingOrders'        => $pendingOrders,
            'paidOrders'           => $paidOrders,
            'processingOrders'     => $processingOrders,
            'outForDeliveryOrders' => $outForDeliveryOrders,
            'deliveredOrders'      => $deliveredOrders,
            'onlineCustomers'      => $onlineCustomers,
            'newCustThisMonth'     => $newCustThisMonth,
            'recentOrders'         => $recentOrders
        ], 'main');
    }

    /**
     * Orders List
     */
    public function orders(): void {
        $pdo = Database::getConnection();

        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 20;
        $offset = ($page - 1) * $perPage;

        $where = ["1=1"];
        $params = [];

        if (!empty($_GET['status'])) {
            $where[] = "oo.status = ?";
            $params[] = $_GET['status'];
        }

        if (!empty($_GET['payment_status'])) {
            $where[] = "oo.payment_status = ?";
            $params[] = $_GET['payment_status'];
        }

        if (!empty($_GET['search'])) {
            $term = "%{$_GET['search']}%";
            $where[] = "(oo.order_number LIKE ? OR c.name LIKE ? OR c.phone LIKE ?)";
            $params = array_merge($params, [$term, $term, $term]);
        }

        $whereClause = implode(" AND ", $where);

        $stmtCount = $pdo->prepare("
            SELECT COUNT(*) 
            FROM `online_orders` oo 
            JOIN `customers` c ON oo.customer_id = c.id 
            WHERE {$whereClause}
        ");
        $stmtCount->execute($params);
        $totalOrders = (int)$stmtCount->fetchColumn();
        $totalPages = ceil($totalOrders / $perPage);

        $sql = "
            SELECT oo.*, c.name as customer_name, c.phone as customer_phone, d.delivery_status
            FROM `online_orders` oo
            JOIN `customers` c ON oo.customer_id = c.id
            LEFT JOIN `deliveries` d ON oo.id = d.order_id
            WHERE {$whereClause}
            ORDER BY oo.created_at DESC, oo.id DESC
            LIMIT ? OFFSET ?
        ";

        $stmt = $pdo->prepare($sql);
        $idx = 1;
        foreach ($params as $val) {
            $stmt->bindValue($idx++, $val);
        }
        $stmt->bindValue($idx++, $perPage, PDO::PARAM_INT);
        $stmt->bindValue($idx++, $offset, PDO::PARAM_INT);
        $stmt->execute();
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->render('online_admin/orders_index', [
            'pageTitle'   => 'Maagizo ya Mtandaoni — Utawala',
            'orders'      => $orders,
            'totalOrders' => $totalOrders,
            'currentPage' => $page,
            'totalPages'  => $totalPages
        ], 'main');
    }

    /**
     * Show single order in staff admin
     */
    public function orderShow(): void {
        $orderId = (int)($_GET['id'] ?? 0);
        $order = OnlineOrder::find($orderId);

        if (!$order) {
            $this->setFlash('danger', 'Agizo halikupatikana.');
            $this->redirect('online_orders');
        }

        $this->render('online_admin/order_show', [
            'pageTitle' => "Uchakataji wa Agizo #{$order['order_number']}",
            'order'     => $order
        ], 'main');
    }

    /**
     * Update order status by staff
     */
    public function updateOrderStatus(): void {
        $orderId = (int)($_POST['order_id'] ?? 0);
        $newStatus = trim($_POST['status'] ?? '');
        $comment = trim($_POST['comment'] ?? '');

        try {
            OnlineOrder::updateStatus($orderId, $newStatus, Auth::id(), $comment);
            $this->setFlash('success', "Hali ya agizo imesasishwa kuwa {$newStatus}.");
        } catch (Exception $e) {
            $this->setFlash('danger', $e->getMessage());
        }

        $this->redirect('online_order_show&id=' . $orderId);
    }

    /**
     * Confirm offline payment by staff
     */
    public function confirmPayment(): void {
        $paymentId = (int)($_POST['payment_id'] ?? 0);
        $providerRef = trim($_POST['provider_reference'] ?? '');
        $notes = trim($_POST['notes'] ?? '');

        try {
            $res = OnlinePayment::markSuccess($paymentId, $providerRef, Auth::id(), $notes);
            $this->setFlash('success', 'Malipo yamethibitishwa na ankara ya mauzo imetengenezwa.');
            $this->redirect('online_order_show&id=' . $res['order_id']);
        } catch (Exception $e) {
            $this->setFlash('danger', $e->getMessage());
            $this->redirect('online_orders');
        }
    }

    /**
     * Deliveries List with provider filtering & lifecycle actions
     */
    public function deliveries(): void {
        $filters = [
            'status'   => $_GET['status'] ?? '',
            'provider' => $_GET['provider'] ?? '',
            'search'   => $_GET['search'] ?? ''
        ];
        $deliveries = Delivery::all($filters, 1, 50);

        $this->render('online_admin/deliveries', [
            'pageTitle'  => 'Usafirishaji wa Maagizo — Utawala',
            'deliveries' => $deliveries,
            'filters'    => $filters
        ], 'main');
    }

    /**
     * Update delivery status with lifecycle validation
     */
    public function updateDeliveryStatus(): void {
        $deliveryId = (int)($_POST['delivery_id'] ?? 0);
        $status = trim($_POST['status'] ?? '');
        $notes = trim($_POST['notes'] ?? '');

        try {
            Delivery::updateStatus($deliveryId, $status, Auth::id(), $notes);
            $this->setFlash('success', 'Hali ya usafirishaji imesasishwa kuwa ' . htmlspecialchars($status) . '.');
        } catch (Exception $e) {
            $this->setFlash('danger', $e->getMessage());
        }

        $this->redirect('online_deliveries');
    }

    /**
     * Assign courier, driver or external reference (Bolt/Other)
     */
    public function assignCourier(): void {
        $deliveryId = (int)($_POST['delivery_id'] ?? 0);
        $data = [
            'driver_name'        => $_POST['driver_name'] ?? null,
            'driver_phone'       => $_POST['driver_phone'] ?? null,
            'vehicle_info'       => $_POST['vehicle_info'] ?? null,
            'courier_name'       => $_POST['courier_name'] ?? null,
            'courier_phone'      => $_POST['courier_phone'] ?? null,
            'external_reference' => $_POST['external_reference'] ?? null,
            'delivery_provider'  => $_POST['delivery_provider'] ?? null,
            'tracking_notes'     => $_POST['tracking_notes'] ?? null
        ];

        try {
            Delivery::assignCourier($deliveryId, $data, Auth::id());
            $this->setFlash('success', 'Taarifa za dereva na msafirishaji zimesasishwa kikamilifu.');
        } catch (Exception $e) {
            $this->setFlash('danger', $e->getMessage());
        }

        $this->redirect('online_deliveries');
    }

    /**
     * Owner Assistance: Product Discovery & Demand Metrics (Phase 17)
     */
    public function demand(): void {
        $metrics = \App\Models\ProductDemand::getDemandMetrics(50);

        $this->render('online_admin/demand', [
            'pageTitle'   => 'Mahitaji ya Wateja & Ugunduzi wa Vipuri (Product Demand Discovery)',
            'products'    => $metrics['products'],
            'topSearches' => $metrics['topSearches']
        ], 'main');
    }

    /**
     * Convert catalog reference item into an active sellable inventory product
     */
    public function convertCatalogProduct(): void {
        $productId = (int)($_POST['product_id'] ?? 0);
        $buyingPrice = (float)($_POST['buying_price'] ?? 0);
        $sellingPrice = (float)($_POST['selling_price'] ?? 0);
        $initialStock = (int)($_POST['initial_stock'] ?? 0);

        try {
            \App\Models\ProductDemand::convertCatalogProduct($productId, $buyingPrice, $sellingPrice, $initialStock, Auth::id());
            $this->setFlash('success', 'Kipuri kimebadilishwa kuwa bidhaa halisi ya kuuzwa na stoo imesasishwa.');
        } catch (Exception $e) {
            $this->setFlash('danger', $e->getMessage());
        }

        $this->redirect('online_demand');
    }

    /**
     * Online Customers List
     */
    public function customers(): void {
        $pdo = Database::getConnection();

        $sql = "
            SELECT c.*, COUNT(oo.id) as order_count, COALESCE(SUM(oo.total_amount), 0.00) as total_spent
            FROM `customers` c
            LEFT JOIN `online_orders` oo ON c.id = oo.customer_id AND oo.status != 'CANCELLED'
            WHERE c.password IS NOT NULL
            GROUP BY c.id
            ORDER BY c.created_at DESC
        ";
        $customers = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

        $this->render('online_admin/customers', [
            'pageTitle' => 'Wateja wa Mtandaoni — Utawala',
            'customers' => $customers
        ], 'main');
    }

    /**
     * Online Store & Delivery Settings
     */
    public function settings(): void {
        $pdo = Database::getConnection();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $allowedKeys = [
                'online_store_enabled', 'online_store_title', 'online_store_description',
                'delivery_flat_rate', 'free_delivery_threshold', 'lipa_namba_mpesa',
                'lipa_namba_tigo', 'lipa_namba_airtel', 'bank_transfer_instructions',
                'online_return_policy_days',
                'delivery_store_enabled', 'delivery_bolt_enabled', 'delivery_pickup_enabled',
                'delivery_own_courier_enabled', 'delivery_other_courier_enabled',
                'store_pickup_instructions', 'delivery_notes', 'bolt_api_configured',
                'bolt_api_key', 'bolt_api_url', 'catalog_display_exact_stock',
                'notification_email_enabled', 'notification_sms_enabled', 'notification_whatsapp_enabled',
                'customer_support_phone'
            ];

            foreach ($allowedKeys as $k) {
                if (isset($_POST[$k])) {
                    Setting::set($k, trim($_POST[$k]));
                }
            }

            $this->setFlash('success', 'Mipangilio ya duka la mtandaoni na usafirishaji imehifadhiwa kikamilifu.');
            $this->redirect('online_settings');
        }

        $settings = [];
        $stmt = $pdo->query("SELECT setting_key, setting_value FROM `settings`");
        while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $settings[$r['setting_key']] = $r['setting_value'];
        }

        $this->render('online_admin/settings', [
            'pageTitle' => 'Mipangilio ya Duka la Mtandaoni & Usafirishaji — Utawala',
            'settings'  => $settings
        ], 'main');
    }
}
