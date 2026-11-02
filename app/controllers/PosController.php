<?php
/**
 * NGENZI MOTO SPARE PARTS
 * PosController (Phase 8)
 * Point of Sale Terminal Controller for Real-Time Checkout, Barcode Lookups,
 * Vehicle Compatibility Filtering & Instant Customer Onboarding.
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Helper;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use App\Models\VehicleModel;
use App\Models\Customer;
use App\Models\Sale;
use App\Models\Setting;
use Exception;

class PosController extends Controller {

    /**
     * Verify user permission to access POS
     */
    private function checkAccess(): void {
        Auth::requireAuth();
        if (!Auth::hasPermission('sales.create') && !Auth::hasRole(['super_admin', 'manager', 'cashier'])) {
            Helper::setFlash('danger', 'Huna ruhusa ya kuingia kwenye Mashine ya Mauzo (POS).');
            $this->redirect('/index.php?route=dashboard');
            exit;
        }
    }

    /**
     * Render the POS Terminal Screen
     */
    public function index(): void {
        $this->checkAccess();

        $categories = Category::all('active');
        $brands = Brand::all('active');
        $vehicleModels = VehicleModel::all(null, null, 'active');

        // Fetch initial in-stock products (top 16)
        $initialProducts = Product::all(['status' => 'active'], 16, 0);

        $canDiscount = Auth::hasPermission('sales.discount') || Auth::hasRole(['super_admin', 'manager']);
        $canSellBelowMin = Auth::hasPermission('sales.sell_below_minimum') || Auth::hasRole(['super_admin', 'manager']);
        $canViewProfit = Auth::canViewFinancials();
        $discountThreshold = (float)Setting::get('discount_approval_threshold', '5000.00');

        $this->render('pos/index', [
            'pageTitle'          => 'Mashine ya Mauzo (POS) — NGENZI MOTO SPARE PARTS',
            'activeMenu'         => 'pos',
            'categories'         => $categories,
            'brands'             => $brands,
            'vehicleModels'      => $vehicleModels,
            'initialProducts'    => $initialProducts,
            'canDiscount'        => $canDiscount,
            'canSellBelowMin'    => $canSellBelowMin,
            'canViewProfit'      => $canViewProfit,
            'discountThreshold'  => $discountThreshold,
            'currency'           => Setting::get('currency', 'TZS')
        ]);
    }

    /**
     * AJAX Product Search Endpoint
     * Supports search by name, SKU, barcode, shelf, category, brand, and vehicle model
     */
    public function searchProducts(): void {
        Auth::requireAuth();
        header('Content-Type: application/json; charset=utf-8');

        $query = trim($_GET['q'] ?? '');
        $categoryId = !empty($_GET['category_id']) ? (int)$_GET['category_id'] : null;
        $brandId = !empty($_GET['brand_id']) ? (int)$_GET['brand_id'] : null;
        $vehicleModelId = !empty($_GET['vehicle_model_id']) ? (int)$_GET['vehicle_model_id'] : null;
        $limit = min(50, max(1, (int)($_GET['limit'] ?? 20)));

        $filters = ['status' => 'active'];
        if ($query !== '') {
            $filters['search'] = $query;
        }
        if ($categoryId) {
            $filters['category_id'] = $categoryId;
        }
        if ($brandId) {
            $filters['brand_id'] = $brandId;
        }
        if ($vehicleModelId) {
            $filters['vehicle_model_id'] = $vehicleModelId;
        }

        $products = Product::all($filters, $limit, 0);
        $canSeeCosts = Auth::canViewFinancials();

        $results = array_map(function ($p) use ($canSeeCosts) {
            return [
                'id'                   => (int)$p['id'],
                'sku'                  => $p['sku'],
                'barcode'              => $p['barcode'] ?? '',
                'name'                 => $p['name'],
                'brand_name'           => $p['brand_name'] ?? 'General',
                'category_name'        => $p['category_name'] ?? 'General',
                'selling_price'        => (float)$p['selling_price'],
                'formatted_price'      => Helper::formatCurrency((float)$p['selling_price']),
                'minimum_selling_price'=> (float)($p['minimum_selling_price'] ?? 0.00),
                'current_stock'        => (int)$p['current_stock'],
                'unit_short'           => $p['unit_short'] ?? 'Pcs',
                'shelf_location'       => $p['shelf_location'] ?? '',
                'image_url'            => $p['image_url'] ?: '/assets/images/placeholder_product.svg',
                'is_out_of_stock'      => ((int)$p['current_stock'] <= 0),
                'buying_price'         => $canSeeCosts ? (float)$p['buying_price'] : null
            ];
        }, $products);

        echo json_encode([
            'success' => true,
            'count'   => count($results),
            'items'   => $results
        ]);
        exit;
    }

    /**
     * AJAX Barcode Lookup Endpoint
     * Instant search by exact barcode match
     */
    public function lookupBarcode(): void {
        Auth::requireAuth();
        header('Content-Type: application/json; charset=utf-8');

        $barcode = trim($_GET['barcode'] ?? '');
        if (empty($barcode)) {
            echo json_encode(['success' => false, 'message' => 'Tafadhali ingiza au piga picha ya barcode.']);
            exit;
        }

        $product = Product::findByBarcode($barcode);
        if (!$product) {
            // Also try SKU as fallback
            $product = Product::findBySku($barcode);
        }

        if (!$product) {
            echo json_encode([
                'success' => false, 
                'message' => "Hakuna kipuri kilichopatikana chenye barcode/SKU: '{$barcode}'."
            ]);
            exit;
        }

        if ($product['status'] !== 'active') {
            echo json_encode([
                'success' => false, 
                'message' => "Kipuri hiki kimesimamishwa (Status: {$product['status']})."
            ]);
            exit;
        }

        $canSeeCosts = Auth::canViewFinancials();

        echo json_encode([
            'success' => true,
            'item'    => [
                'id'                   => (int)$product['id'],
                'sku'                  => $product['sku'],
                'barcode'              => $product['barcode'] ?? '',
                'name'                 => $product['name'],
                'brand_name'           => $product['brand_name'] ?? 'General',
                'category_name'        => $product['category_name'] ?? 'General',
                'selling_price'        => (float)$product['selling_price'],
                'formatted_price'      => Helper::formatCurrency((float)$product['selling_price']),
                'minimum_selling_price'=> (float)($product['minimum_selling_price'] ?? 0.00),
                'current_stock'        => (int)$product['current_stock'],
                'unit_short'           => $product['unit_short'] ?? 'Pcs',
                'shelf_location'       => $product['shelf_location'] ?? '',
                'image_url'            => $product['image_url'] ?: '/assets/images/placeholder_product.svg',
                'is_out_of_stock'      => ((int)$product['current_stock'] <= 0),
                'buying_price'         => $canSeeCosts ? (float)$product['buying_price'] : null
            ]
        ]);
        exit;
    }

    /**
     * AJAX Quick Customer Registration Modal from POS
     */
    public function quickCreateCustomer(): void {
        Auth::requireAuth();
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Njia ya ombi si sahihi (POST required).']);
            exit;
        }

        $csrfToken = $_POST['csrf_token'] ?? '';
        if (!Helper::validateCsrf($csrfToken)) {
            echo json_encode(['success' => false, 'message' => 'Hitilafu ya kiusalama (CSRF invalid).']);
            exit;
        }

        $name = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $customerType = trim($_POST['customer_type'] ?? 'individual');
        $area = trim($_POST['area'] ?? '');

        if (empty($name) || empty($phone)) {
            echo json_encode(['success' => false, 'message' => 'Jina la mteja na nambari ya simu vinahitajika.']);
            exit;
        }

        try {
            $code = Customer::generateCode();
            $customerId = Customer::create([
                'customer_code'     => $code,
                'name'              => $name,
                'phone'             => $phone,
                'customer_type'     => $customerType,
                'area'              => $area,
                'credit_limit'      => 0.00,
                'notes'             => 'Usajili wa haraka kutoka POS terminal',
                'created_by'        => Auth::id()
            ]);

            echo json_encode([
                'success'  => true,
                'message'  => "Mteja '{$name}' amesajiliwa kikamilifu!",
                'customer' => [
                    'id'            => $customerId,
                    'customer_code' => $code,
                    'name'          => $name,
                    'phone'         => $phone,
                    'customer_type' => $customerType
                ]
            ]);
            exit;

        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => "Hitilafu: " . $e->getMessage()]);
            exit;
        }
    }

    /**
     * Process Checkout & Complete Sale POST Endpoint
     * Supports both application/x-www-form-urlencoded and JSON payload
     */
    public function checkout(): void {
        $this->checkAccess();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/index.php?route=pos');
            return;
        }

        // Parse payload (either JSON or traditional POST form)
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        $isJson = (strpos($contentType, 'application/json') !== false);

        if ($isJson) {
            $rawInput = file_get_contents('php://input');
            $payload = json_decode($rawInput, true) ?: [];
        } else {
            $payload = $_POST;
            if (!empty($_POST['cart_json'])) {
                $payload['cart'] = json_decode($_POST['cart_json'], true) ?: [];
            }
        }

        $csrfToken = $payload['csrf_token'] ?? '';
        if (!Helper::validateCsrf($csrfToken)) {
            if ($isJson) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Hitilafu ya kiusalama (CSRF invalid).']);
                exit;
            }
            Helper::setFlash('danger', 'Hitilafu ya usalama (CSRF invalid). Tafadhali jaribu tena.');
            $this->redirect('/index.php?route=pos');
            return;
        }

        $cart = $payload['cart'] ?? [];
        if (empty($cart) || !is_array($cart)) {
            if ($isJson) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Kapu la mauzo halina bidhaa (Cart is empty).']);
                exit;
            }
            Helper::setFlash('danger', 'Kapu la mauzo halina bidhaa.');
            $this->redirect('/index.php?route=pos');
            return;
        }

        $saleData = [
            'customer_id'            => !empty($payload['customer_id']) ? (int)$payload['customer_id'] : null,
            'customer_name'          => trim($payload['customer_name'] ?? ''),
            'customer_phone'         => trim($payload['customer_phone'] ?? ''),
            'mechanic_id'            => !empty($payload['mechanic_id']) ? (int)$payload['mechanic_id'] : null,
            'discount_amount'        => (float)($payload['overall_discount'] ?? 0.00),
            'discount_reason'        => trim($payload['discount_reason'] ?? ''),
            'notes'                  => trim($payload['notes'] ?? ''),
            'due_date'               => !empty($payload['due_date']) ? trim($payload['due_date']) : null,
            'payment_terms'          => !empty($payload['payment_terms']) ? trim($payload['payment_terms']) : null,
            'credit_override_by'     => !empty($payload['credit_override_by']) ? (int)$payload['credit_override_by'] : null,
            'credit_override_reason' => !empty($payload['credit_override_reason']) ? trim($payload['credit_override_reason']) : null
        ];

        $paymentData = [
            'payment_method'  => trim($payload['payment_method'] ?? 'Cash'),
            'provider'        => !empty($payload['provider']) ? trim($payload['provider']) : null,
            'amount_paid'     => (float)($payload['amount_paid'] ?? 0.00),
            'reference_no'    => trim($payload['reference_no'] ?? '')
        ];

        try {
            $result = Sale::createSale($saleData, $cart, $paymentData, Auth::id() ?? 1);

            if ($isJson) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success'      => true,
                    'message'      => "Mauzo yamekamilika kikamilifu! Ankara #{$result['invoice_no']}",
                    'sale'         => $result,
                    'redirect_url' => "/index.php?route=sale_receipt&id={$result['sale_id']}"
                ]);
                exit;
            }

            Helper::setFlash('success', "Mauzo yamekamilika kikamilifu! Ankara #{$result['invoice_no']}");
            $this->redirect("/index.php?route=sale_receipt&id={$result['sale_id']}");

        } catch (Exception $e) {
            if ($isJson) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
                exit;
            }
            Helper::setFlash('danger', "Hitilafu katika mauzo: " . $e->getMessage());
            $this->redirect('/index.php?route=pos');
        }
    }
}
