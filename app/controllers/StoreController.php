<?php
/**
 * NGENZI MOTO SPARE PARTS
 * StoreController (Phase 16 & 17)
 * Public Storefront, Vehicle Compatibility Browser, Demand Logging & Catalog Engine
 * Dar es Salaam, Tanzania
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Auth;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use App\Models\VehicleBrand;
use App\Models\VehicleModel;
use App\Models\ProductReview;
use App\Models\ProductDemand;
use App\Models\Setting;
use PDO;

class StoreController extends Controller {

    /**
     * Public Online Store Homepage
     */
    public function index(): void {
        $pdo = Database::getConnection();

        // 1. Featured products for carousel / top grid
        $featuredProducts = Product::getFeaturedOnlineProducts(8);
        if (empty($featuredProducts)) {
            $featuredProducts = Product::getOnlineProducts([], 8);
        }

        // 2. Active Categories with product count
        $stmtCats = $pdo->query("
            SELECT c.*, COUNT(p.id) as product_count
            FROM `categories` c
            LEFT JOIN `products` p ON c.id = p.category_id AND p.status = 'active' AND p.is_online = 1
            WHERE c.status = 'active'
            GROUP BY c.id
            HAVING product_count > 0
            ORDER BY c.name ASC
            LIMIT 12
        ");
        $categories = $stmtCats->fetchAll(PDO::FETCH_ASSOC);

        // 3. Vehicle Models (Motorcycles & Bajaj)
        $stmtVehicles = $pdo->query("
            SELECT vm.*, vb.name as brand_name, COUNT(pvc.id) as parts_count
            FROM `vehicle_models` vm
            JOIN `vehicle_brands` vb ON vm.brand_id = vb.id
            LEFT JOIN `product_vehicle_compatibility` pvc ON vm.id = pvc.vehicle_model_id
            WHERE vm.status = 'active'
            GROUP BY vm.id
            HAVING parts_count > 0
            ORDER BY vm.vehicle_type ASC, parts_count DESC
            LIMIT 12
        ");
        $vehicleModels = $stmtVehicles->fetchAll(PDO::FETCH_ASSOC);

        // 4. New arrivals
        $newArrivals = Product::getOnlineProducts(['sort' => 'newest'], 8);

        // 5. Complete product catalog (All spare parts with prices upfront - Phase 18)
        $allProducts = Product::getOnlineProducts([], 100);
        $totalOnlineCount = Product::countOnlineProducts([]);

        $this->render('store/home', [
            'pageTitle'          => Setting::get('online_store_title', 'Ngenzi Moto Spare Parts — Duka Rasmi la Mtandaoni'),
            'featuredProducts'   => $featuredProducts,
            'categories'         => $categories,
            'vehicleModels'      => $vehicleModels,
            'newArrivals'        => $newArrivals,
            'allProducts'        => $allProducts,
            'totalOnlineCount'   => $totalOnlineCount,
        ], 'store');
    }

    /**
     * Public Product Catalog / Shop
     */
    public function shop(): void {
        $pdo = Database::getConnection();

        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 16;
        $offset = ($page - 1) * $perPage;

        $filters = [
            'search'           => trim($_GET['search'] ?? ''),
            'category_id'      => !empty($_GET['category']) ? (int)$_GET['category'] : null,
            'brand_id'         => !empty($_GET['brand']) ? (int)$_GET['brand'] : null,
            'vehicle_model_id' => !empty($_GET['model']) ? (int)$_GET['model'] : null,
            'vehicle_brand_id' => !empty($_GET['vehicle_brand']) ? (int)$_GET['vehicle_brand'] : null,
            'vehicle_type'     => !empty($_GET['vehicle_type']) ? trim($_GET['vehicle_type']) : null,
            'availability'     => trim($_GET['availability'] ?? ''),
            'in_stock_only'    => !empty($_GET['in_stock']) ? 1 : null,
            'min_price'        => isset($_GET['min_price']) && is_numeric($_GET['min_price']) ? (float)$_GET['min_price'] : null,
            'max_price'        => isset($_GET['max_price']) && is_numeric($_GET['max_price']) ? (float)$_GET['max_price'] : null,
            'sort'             => trim($_GET['sort'] ?? '')
        ];

        // Track customer search / compatibility inquiries (Owner Demand Discovery - Phase 17)
        if (!empty($filters['search'])) {
            ProductDemand::logEvent('search', null, $filters['search'], $filters['vehicle_model_id'], $filters['category_id'], Auth::customerId() ?: null);
        } elseif (!empty($filters['vehicle_model_id'])) {
            ProductDemand::logEvent('compatibility_search', null, null, $filters['vehicle_model_id'], $filters['category_id'], Auth::customerId() ?: null);
        }

        $products = Product::getOnlineProducts($filters, $perPage, $offset);
        $totalProducts = Product::countOnlineProducts($filters);
        $totalPages = ceil($totalProducts / $perPage);

        // Available filter options
        $categories = $pdo->query("SELECT id, name FROM `categories` WHERE status = 'active' ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
        $brands = $pdo->query("SELECT id, name FROM `brands` WHERE status = 'active' ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
        $vehicleBrands = $pdo->query("SELECT id, name FROM `vehicle_brands` WHERE status = 'active' ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
        $vehicleModels = $pdo->query("SELECT vm.id, vm.model_name, vm.vehicle_type, vb.name as brand_name FROM `vehicle_models` vm JOIN `vehicle_brands` vb ON vm.brand_id = vb.id WHERE vm.status = 'active' ORDER BY vb.name ASC, vm.model_name ASC")->fetchAll(PDO::FETCH_ASSOC);

        $this->render('store/shop', [
            'pageTitle'     => 'Katalogi ya Vipuri — Ngenzi Moto Spare Parts',
            'products'      => $products,
            'totalProducts' => $totalProducts,
            'currentPage'   => $page,
            'totalPages'    => $totalPages,
            'filters'       => $filters,
            'categories'    => $categories,
            'brands'        => $brands,
            'vehicleBrands' => $vehicleBrands,
            'vehicleModels' => $vehicleModels
        ], 'store');
    }

    /**
     * Product Detail Page
     */
    public function productDetail(): void {
        $productId = (int)($_GET['id'] ?? 0);
        $product = Product::findOnline($productId);

        if (!$product) {
            $this->setFlash('danger', 'Kipuri ulichoomba hakikupatikana au kimeondolewa mtandaoni.');
            $this->redirect('shop');
        }

        // Log customer view event for demand discovery
        ProductDemand::logEvent('view', $productId, null, null, (int)$product['category_id'], Auth::customerId() ?: null);

        $relatedProducts = Product::getRelatedOnlineProducts($productId, (int)$product['category_id'], 4);
        $reviews = ProductReview::forProduct($productId);
        $ratingSummary = ProductReview::getRatingSummary($productId);

        $this->render('store/product_detail', [
            'pageTitle'       => "{$product['name']} — Ngenzi Moto Spare Parts",
            'product'         => $product,
            'relatedProducts' => $relatedProducts,
            'reviews'         => $reviews,
            'ratingSummary'   => $ratingSummary
        ], 'store');
    }

    /**
     * Customer requests alert for out-of-stock item
     */
    public function requestOutOfStock(): void {
        $productId = (int)($_POST['product_id'] ?? 0);
        if ($productId > 0) {
            ProductDemand::logEvent('out_of_stock_request', $productId, null, null, null, Auth::customerId() ?: null);
            $this->setFlash('success', 'Ombi lako limepokelewa. Utaarifiwa mara tu mzigo utakapoingia stoo.');
            $this->redirect('product_detail&id=' . $productId);
        } else {
            $this->redirect('shop');
        }
    }

    /**
     * Vehicle Model Compatibility Browser
     */
    public function vehicleCompatibility(): void {
        $pdo = Database::getConnection();
        $modelId = (int)($_GET['model_id'] ?? 0);

        $vehicle = null;
        $compatibleParts = [];

        if ($modelId > 0) {
            $stmt = $pdo->prepare("
                SELECT vm.*, vb.name as brand_name 
                FROM `vehicle_models` vm
                JOIN `vehicle_brands` vb ON vm.brand_id = vb.id
                WHERE vm.id = ? AND vm.status = 'active'
            ");
            $stmt->execute([$modelId]);
            $vehicle = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($vehicle) {
                // Log compatibility lookup
                ProductDemand::logEvent('compatibility_search', null, null, $modelId, null, Auth::customerId() ?: null);
                $compatibleParts = Product::getOnlineProducts(['vehicle_model_id' => $modelId], 50);
            }
        }

        $allModels = $pdo->query("
            SELECT vm.id, vm.model_name, vm.vehicle_type, vb.name as brand_name
            FROM `vehicle_models` vm
            JOIN `vehicle_brands` vb ON vm.brand_id = vb.id
            WHERE vm.status = 'active'
            ORDER BY vb.name ASC, vm.model_name ASC
        ")->fetchAll(PDO::FETCH_ASSOC);

        $this->render('store/vehicle_search', [
            'pageTitle'       => $vehicle ? "Vipuri Vinavyofaa: {$vehicle['brand_name']} {$vehicle['model_name']}" : "Tafuta Vipuri kwa Chombo Chako",
            'vehicle'         => $vehicle,
            'selectedVehicle' => $vehicle,
            'compatibleParts' => $compatibleParts,
            'allModels'       => $allModels
        ], 'store');
    }

    public function terms(): void {
        $this->render('store/terms', ['pageTitle' => 'Vigezo na Masharti ya Mauzo'], 'store');
    }

    public function privacy(): void {
        $this->render('store/privacy', ['pageTitle' => 'Sera ya Faragha'], 'store');
    }

    public function returnPolicy(): void {
        $this->render('store/return_policy', ['pageTitle' => 'Sera ya Kurudisha Vipuri'], 'store');
    }
}
