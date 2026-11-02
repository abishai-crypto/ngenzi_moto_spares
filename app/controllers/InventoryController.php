<?php
/**
 * NGENZI MOTO SPARE PARTS
 * InventoryController (Phase 5)
 * Comprehensive Stock Management, Movements, Adjustments, Physical Counts & Analytics
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Helper;
use App\Models\Inventory;
use App\Models\StockCount;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use App\Models\VehicleBrand;
use App\Models\VehicleModel;
use App\Models\User;
use Exception;

class InventoryController extends Controller {

    /**
     * Check permission with fallback to module permissions
     */
    private function checkPerm(string $specificPerm, string $generalPerm): void {
        if (!Auth::hasPermission($specificPerm) && !Auth::hasPermission($generalPerm) && !Auth::hasRole('super_admin')) {
            $this->setFlash('danger', 'Huna ruhusa ya kufanya kitendo hiki (Access Denied).');
            $this->redirect('/index.php?route=inventory');
            exit;
        }
    }

    /**
     * Inventory Executive Dashboard
     */
    public function dashboard(): void {
        $this->checkPerm('inventory.view', 'manage_inventory');

        $stats = Inventory::getDashboardStats();
        $lowStockProducts = Inventory::getLowStockProducts(5);
        $outOfStockProducts = Inventory::getOutOfStockProducts(5);
        $reorderSuggestions = array_slice(Inventory::getReorderSuggestions(), 0, 5);

        $canSeeCosts = Auth::hasAnyRole(['super_admin', 'manager', 'accountant']) || Auth::hasPermission('inventory.valuation');

        $this->render('inventory/dashboard', [
            'title'              => 'Dashibodi ya Stoo & Mali',
            'activeMenu'         => 'inventory_dashboard',
            'stats'              => $stats,
            'lowStockProducts'   => $lowStockProducts,
            'outOfStockProducts' => $outOfStockProducts,
            'reorderSuggestions' => $reorderSuggestions,
            'canSeeCosts'        => $canSeeCosts
        ]);
    }

    /**
     * Master Inventory Listing with Filters, Search, and Barcode support
     */
    public function index(): void {
        $this->checkPerm('inventory.view', 'manage_inventory');

        $search = trim($_GET['search'] ?? '');
        $status = trim($_GET['status'] ?? '');
        $categoryId = !empty($_GET['category_id']) ? (int)$_GET['category_id'] : null;
        $brandId = !empty($_GET['brand_id']) ? (int)$_GET['brand_id'] : null;
        $vehicleModelId = !empty($_GET['vehicle_model_id']) ? (int)$_GET['vehicle_model_id'] : null;
        $page = max(1, (int)($_GET['page'] ?? 1));

        $filters = [
            'search'           => $search,
            'category_id'      => $categoryId,
            'brand_id'         => $brandId,
            'vehicle_model_id' => $vehicleModelId,
            'status'           => ($status === 'active' || $status === 'inactive') ? $status : 'all'
        ];

        // Stock status specific filtering
        if ($status === 'in_stock') {
            $filters['stock_status'] = 'in_stock';
        } elseif ($status === 'low_stock') {
            $filters['stock_status'] = 'low_stock';
        } elseif ($status === 'out_of_stock') {
            $filters['stock_status'] = 'out_of_stock';
        }

        $products = Product::all($filters, $page, 20);
        $total = Product::count($filters);
        $totalPages = max(1, (int)ceil($total / 20));

        $categories = Category::all();
        $brands = Brand::all();
        $vehicleModels = VehicleModel::all();

        $canManage = Auth::hasAnyRole(['super_admin', 'manager', 'storekeeper']) || Auth::hasPermission('inventory.adjust');
        $canSeeCosts = Auth::hasAnyRole(['super_admin', 'manager', 'accountant']) || Auth::hasPermission('inventory.valuation');

        $this->render('inventory/index', [
            'title'          => 'Orodha ya Mali & Vipuri Vilivyopo Stoo',
            'activeMenu'     => 'inventory',
            'products'       => $products,
            'total'          => $total,
            'page'           => $page,
            'totalPages'     => $totalPages,
            'filters'        => [
                'search'           => $search,
                'status'           => $status,
                'category_id'      => $categoryId,
                'brand_id'         => $brandId,
                'vehicle_model_id' => $vehicleModelId
            ],
            'categories'     => $categories,
            'brands'         => $brands,
            'vehicleModels'  => $vehicleModels,
            'canManage'      => $canManage,
            'canSeeCosts'    => $canSeeCosts
        ]);
    }

    /**
     * Stock Movement Audit Trail / History
     */
    public function history(): void {
        $this->checkPerm('inventory.history', 'manage_inventory');

        $page = max(1, (int)($_GET['page'] ?? 1));
        $filters = [
            'search'         => trim($_GET['search'] ?? ''),
            'type'           => trim($_GET['type'] ?? ''),
            'user_id'        => !empty($_GET['user_id']) ? (int)$_GET['user_id'] : null,
            'product_id'     => !empty($_GET['product_id']) ? (int)$_GET['product_id'] : null,
            'date_from'      => trim($_GET['date_from'] ?? ''),
            'date_to'        => trim($_GET['date_to'] ?? ''),
            'reference_type' => trim($_GET['reference_type'] ?? '')
        ];

        // Export CSV support
        if (isset($_GET['export']) && $_GET['export'] === 'csv') {
            $exportResult = Inventory::getHistory($filters, 1, 5000);
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename=inventory_history_' . date('Y-m-d') . '.csv');
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Tarehe', 'SKU', 'Kipuri', 'Aina ya Mwenendo', 'Kabla', 'Marekebisho', 'Baada', 'Gharama (TZS)', 'Rejea', 'Mtumiaji', 'Maelezo']);
            foreach ($exportResult['data'] as $row) {
                fputcsv($out, [
                    $row['created_at'],
                    $row['product_sku'],
                    $row['product_name'],
                    $row['transaction_type'],
                    $row['quantity_before'],
                    $row['quantity'],
                    $row['quantity_after'],
                    $row['unit_cost'],
                    $row['reference_type'] . ($row['reference_id'] ? " #" . $row['reference_id'] : ''),
                    $row['user_name'] ?? $row['user_username'],
                    $row['notes']
                ]);
            }
            fclose($out);
            exit;
        }

        $result = Inventory::getHistory($filters, $page, 25);
        $users = User::all();
        $canSeeCosts = Auth::hasAnyRole(['super_admin', 'manager', 'accountant']) || Auth::hasPermission('inventory.valuation');

        $this->render('inventory/history', [
            'title'       => 'Kumbukumbu za Mienendo ya Stoo (Stock History)',
            'activeMenu'  => 'inventory_history',
            'movements'   => $result['data'],
            'total'       => $result['total'],
            'page'        => $result['page'],
            'totalPages'  => $result['total_pages'],
            'filters'     => $filters,
            'users'       => $users,
            'canSeeCosts' => $canSeeCosts
        ]);
    }

    /**
     * Stock Adjustment Form
     */
    public function adjust(): void {
        $this->checkPerm('inventory.adjust', 'manage_inventory');

        $selectedProductId = !empty($_GET['product_id']) ? (int)$_GET['product_id'] : null;
        $selectedProduct = null;
        if ($selectedProductId) {
            $selectedProduct = Product::find($selectedProductId);
        }

        $products = Product::all(['status' => 'active'], 1, 1000);

        $this->render('inventory/adjust', [
            'title'           => 'Marekebisho ya Stoo (Stock Adjustment)',
            'activeMenu'      => 'inventory_adjust',
            'products'        => $products,
            'selectedProduct' => $selectedProduct
        ]);
    }

    /**
     * Process Stock Adjustment Form POST
     */
    public function storeAdjustment(): void {
        $this->checkPerm('inventory.adjust', 'manage_inventory');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/index.php?route=inventory_adjust');
            return;
        }

        $csrfToken = $_POST['csrf_token'] ?? '';
        if (!Helper::validateCsrf($csrfToken)) {
            $this->setFlash('danger', 'Ombi halikukamilika kwa sababu ya usalama (Invalid CSRF token).');
            $this->redirect('/index.php?route=inventory_adjust');
            return;
        }

        $productId = (int)($_POST['product_id'] ?? 0);
        $adjustmentType = trim($_POST['adjustment_type'] ?? '');
        $quantity = (int)($_POST['quantity'] ?? 0);
        $reason = trim($_POST['reason'] ?? '');
        $notes = trim($_POST['notes'] ?? '');

        if ($productId <= 0) {
            $this->setFlash('danger', 'Tafadhali chagua kipuri unachotaka kurekebisha.');
            $this->redirect('/index.php?route=inventory_adjust');
            return;
        }

        if ($quantity <= 0) {
            $this->setFlash('danger', 'Idadi ya kurekebisha lazima iwe kubwa kuliko sifuri.');
            $this->redirect("/index.php?route=inventory_adjust&product_id={$productId}");
            return;
        }

        if (empty($reason)) {
            $this->setFlash('danger', 'Tafadhali chagua au weka sababu ya marekebisho haya ya stoo.');
            $this->redirect("/index.php?route=inventory_adjust&product_id={$productId}");
            return;
        }

        try {
            $result = Inventory::adjustStock(
                $productId,
                $adjustmentType,
                $quantity,
                $reason,
                $notes,
                Auth::id()
            );

            $this->setFlash('success', "Marekebisho ya stoo yamekamilika! Idadi ya awali: {$result['quantity_before']}, Idadi mpya: {$result['quantity_after']}.");
            $this->redirect('/index.php?route=inventory_history');

        } catch (Exception $e) {
            $this->setFlash('danger', "Hitilafu: " . $e->getMessage());
            $this->redirect("/index.php?route=inventory_adjust&product_id={$productId}");
        }
    }

    /**
     * Physical Stock Count Sessions Directory
     */
    public function counts(): void {
        $this->checkPerm('inventory.count', 'approve_stock_adjustments');

        $status = trim($_GET['status'] ?? '');
        $counts = StockCount::all(['status' => $status]);
        $canApprove = Auth::hasAnyRole(['super_admin', 'manager']) || Auth::hasPermission('approve_stock_adjustments');

        $this->render('inventory/counts/index', [
            'title'      => 'Ukaguzi wa Kimwili wa Stoo (Stock Counts)',
            'activeMenu' => 'inventory_counts',
            'counts'     => $counts,
            'canApprove' => $canApprove,
            'status'     => $status
        ]);
    }

    /**
     * Create Physical Stock Count Wizard
     */
    public function createCount(): void {
        $this->checkPerm('inventory.count', 'approve_stock_adjustments');

        $categories = Category::all();
        $categoryId = !empty($_GET['category_id']) ? (int)$_GET['category_id'] : null;

        $filters = ['status' => 'active'];
        if ($categoryId) {
            $filters['category_id'] = $categoryId;
        }

        $products = Product::all($filters, 1, 1000);

        $this->render('inventory/counts/create', [
            'title'        => 'Anzisha Ukaguzi Mpya wa Stoo',
            'activeMenu'   => 'inventory_counts',
            'categories'   => $categories,
            'categoryId'   => $categoryId,
            'products'     => $products,
            'suggestedCode'=> StockCount::generateCountNo()
        ]);
    }

    /**
     * Store Physical Stock Count Session
     */
    public function storeCount(): void {
        $this->checkPerm('inventory.count', 'approve_stock_adjustments');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/index.php?route=inventory_counts');
            return;
        }

        $csrfToken = $_POST['csrf_token'] ?? '';
        if (!Helper::validateCsrf($csrfToken)) {
            $this->setFlash('danger', 'Hitilafu ya usalama (Invalid CSRF).');
            $this->redirect('/index.php?route=inventory_count_create');
            return;
        }

        $countNo = trim($_POST['count_no'] ?? '');
        $countDate = trim($_POST['count_date'] ?? date('Y-m-d'));
        $notes = trim($_POST['notes'] ?? '');
        $itemsData = $_POST['items'] ?? [];

        if (empty($itemsData) || !is_array($itemsData)) {
            $this->setFlash('danger', 'Hakuna bidhaa zilizojumuishwa kwenye ukaguzi huu.');
            $this->redirect('/index.php?route=inventory_count_create');
            return;
        }

        try {
            $sessionId = StockCount::createSession([
                'count_no'   => $countNo,
                'count_date' => $countDate,
                'notes'      => $notes,
                'status'     => 'submitted'
            ], Auth::id() ?? 1);

            $formattedItems = [];
            foreach ($itemsData as $productId => $val) {
                if (!isset($val['physical_quantity'])) continue;
                $formattedItems[] = [
                    'product_id'        => (int)$productId,
                    'physical_quantity' => (int)$val['physical_quantity'],
                    'reason'            => trim($val['reason'] ?? '')
                ];
            }

            StockCount::addItems($sessionId, $formattedItems);

            $this->setFlash('success', "Ukaguzi wa stoo (#{$countNo}) umewasilishwa kwa ukaguzi na uamuzi wa Meneja/Msimamizi.");
            $this->redirect("/index.php?route=inventory_count_show&id={$sessionId}");

        } catch (Exception $e) {
            $this->setFlash('danger', "Hitilafu: " . $e->getMessage());
            $this->redirect('/index.php?route=inventory_count_create');
        }
    }

    /**
     * Review Physical Stock Count Session & Reconciliation
     */
    public function showCount(): void {
        $this->checkPerm('inventory.count', 'approve_stock_adjustments');

        $id = (int)($_GET['id'] ?? 0);
        $count = StockCount::find($id);

        if (!$count) {
            $this->setFlash('danger', 'Ukaguzi wa stoo haukupatikana.');
            $this->redirect('/index.php?route=inventory_counts');
            return;
        }

        $canApprove = (Auth::hasAnyRole(['super_admin', 'manager']) || Auth::hasPermission('approve_stock_adjustments')) && $count['status'] !== 'approved';
        $canSeeCosts = Auth::hasAnyRole(['super_admin', 'manager', 'accountant']) || Auth::hasPermission('inventory.valuation');

        $this->render('inventory/counts/show', [
            'title'       => "Ukaguzi wa Stoo #{$count['count_no']}",
            'activeMenu'  => 'inventory_counts',
            'count'       => $count,
            'canApprove'  => $canApprove,
            'canSeeCosts' => $canSeeCosts
        ]);
    }

    /**
     * Approve Stock Count Session (Applies Adjustments to Inventory)
     */
    public function approveCount(): void {
        $this->checkPerm('approve_stock_adjustments', 'manage_inventory');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/index.php?route=inventory_counts');
            return;
        }

        $csrfToken = $_POST['csrf_token'] ?? '';
        if (!Helper::validateCsrf($csrfToken)) {
            $this->setFlash('danger', 'Hitilafu ya usalama (CSRF).');
            $this->redirect('/index.php?route=inventory_counts');
            return;
        }

        $id = (int)($_POST['id'] ?? 0);

        try {
            $res = StockCount::approveCount($id, Auth::id() ?? 1);
            $this->setFlash('success', "Ukaguzi wa stoo #{$res['count_no']} umeidhinishwa! Marekebisho {$res['adjustments_made']} ya stoo yamerekodiwa kiotomatiki.");
            $this->redirect("/index.php?route=inventory_count_show&id={$id}");

        } catch (Exception $e) {
            $this->setFlash('danger', "Hitilafu katika kuidhinisha ukaguzi: " . $e->getMessage());
            $this->redirect("/index.php?route=inventory_count_show&id={$id}");
        }
    }

    /**
     * Reject Stock Count Session
     */
    public function rejectCount(): void {
        $this->checkPerm('approve_stock_adjustments', 'manage_inventory');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/index.php?route=inventory_counts');
            return;
        }

        $id = (int)($_POST['id'] ?? 0);
        $reason = trim($_POST['rejection_reason'] ?? 'Sababu haikuelezwa');

        try {
            StockCount::rejectCount($id, Auth::id() ?? 1, $reason);
            $this->setFlash('warning', "Ukaguzi wa stoo umekataliwa.");
            $this->redirect("/index.php?route=inventory_count_show&id={$id}");

        } catch (Exception $e) {
            $this->setFlash('danger', "Hitilafu: " . $e->getMessage());
            $this->redirect("/index.php?route=inventory_count_show&id={$id}");
        }
    }

    /**
     * Stock Valuation Report
     */
    public function valuation(): void {
        $this->checkPerm('inventory.valuation', 'manage_inventory');

        $categoryId = !empty($_GET['category_id']) ? (int)$_GET['category_id'] : null;
        $brandId = !empty($_GET['brand_id']) ? (int)$_GET['brand_id'] : null;

        $report = Inventory::getValuationReport($categoryId, $brandId);
        $categories = Category::all();
        $brands = Brand::all();

        // Export CSV
        if (isset($_GET['export']) && $_GET['export'] === 'csv') {
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename=inventory_valuation_' . date('Y-m-d') . '.csv');
            $out = fopen('php://output', 'w');
            fputcsv($out, ['SKU', 'Kipuri', 'Kategoria', 'Brand', 'Idadi Stoo', 'Bei ya Kununua (Cost)', 'Jumla ya Thamani (Cost)', 'Bei ya Kuuza', 'Jumla ya Thamani (Turnover)', 'Faida Inayotarajiwa']);
            foreach ($report['products'] as $p) {
                fputcsv($out, [
                    $p['sku'],
                    $p['name'],
                    $p['category_name'],
                    $p['brand_name'],
                    $p['quantity'],
                    $p['buying_cost'],
                    $p['total_cost_value'],
                    $p['selling_price'],
                    $p['total_selling_value'],
                    $p['potential_profit']
                ]);
            }
            fclose($out);
            exit;
        }

        $this->render('inventory/valuation', [
            'title'      => 'Thamani ya Mali Iliyopo Stoo (Inventory Valuation)',
            'activeMenu' => 'inventory_valuation',
            'report'     => $report,
            'categories' => $categories,
            'brands'     => $brands,
            'categoryId' => $categoryId,
            'brandId'    => $brandId
        ]);
    }

    /**
     * Fast-Moving, Slow-Moving & Dead Stock Analytics
     */
    public function analytics(): void {
        $this->checkPerm('inventory.view', 'manage_inventory');

        $period = trim($_GET['period'] ?? '30d');
        if (!in_array($period, ['7d', '30d', '90d'])) {
            $period = '30d';
        }

        $analytics = Inventory::getMovementAnalytics($period);
        $canSeeCosts = Auth::hasAnyRole(['super_admin', 'manager', 'accountant']) || Auth::hasPermission('inventory.valuation');

        $this->render('inventory/analytics', [
            'title'       => 'Uchambuzi wa Mienendo ya Vipuri (Fast & Slow Moving)',
            'activeMenu'  => 'inventory_analytics',
            'analytics'   => $analytics,
            'period'      => $period,
            'canSeeCosts' => $canSeeCosts
        ]);
    }

    /**
     * Reorder Suggestions Page
     */
    public function reorder(): void {
        $this->checkPerm('inventory.view', 'manage_inventory');

        $suggestions = Inventory::getReorderSuggestions();
        $canSeeCosts = Auth::hasAnyRole(['super_admin', 'manager', 'accountant']) || Auth::hasPermission('inventory.valuation');

        $this->render('inventory/reorder', [
            'title'       => 'Mapendekezo ya Kuagiza Vipuri (Reorder Suggestions)',
            'activeMenu'  => 'inventory_reorder',
            'suggestions' => $suggestions,
            'canSeeCosts' => $canSeeCosts
        ]);
    }

    /**
     * Barcode Quick Lookup AJAX Endpoint
     */
    public function ajaxBarcodeLookup(): void {
        header('Content-Type: application/json');

        $code = trim($_GET['code'] ?? '');
        if (empty($code)) {
            echo json_encode(['success' => false, 'message' => 'Msimbo haujatolewa']);
            exit;
        }

        $pdo = \App\Core\Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT p.id, p.sku, p.barcode, p.name, p.quantity, p.minimum_stock_level,
                   p.buying_cost, p.selling_price, u.short_name as unit_short,
                   c.name as category_name
            FROM `products` p
            JOIN `categories` c ON p.category_id = c.id
            JOIN `units` u ON p.unit_id = u.id
            WHERE p.barcode = ? OR p.sku = ?
            LIMIT 1
        ");
        $stmt->execute([$code, $code]);
        $product = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$product) {
            echo json_encode(['success' => false, 'message' => 'Kipuri hakikupatikana']);
            exit;
        }

        // Mask cost if cashier
        if (!Auth::hasAnyRole(['super_admin', 'manager', 'accountant']) && !Auth::hasPermission('inventory.valuation')) {
            $product['buying_cost'] = null;
        }

        echo json_encode([
            'success' => true,
            'product' => $product
        ]);
        exit;
    }
}
