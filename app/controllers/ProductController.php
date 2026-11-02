<?php
/**
 * NGENZI MOTO SPARE PARTS
 * ProductController (Phase 4)
 * Comprehensive Spare Parts Catalog & Vehicle Compatibility Controller
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Helper;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use App\Models\Unit;
use App\Models\VehicleBrand;
use App\Models\VehicleModel;
use Exception;

class ProductController extends Controller {
    /**
     * Master Product Catalog Directory
     */
    public function index(): void {
        Auth::requireAuth();

        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 20;
        $offset = ($page - 1) * $limit;

        $filters = [
            'search'           => trim($_GET['search'] ?? ''),
            'category_id'      => trim($_GET['category_id'] ?? ''),
            'brand_id'         => trim($_GET['brand_id'] ?? ''),
            'status'           => trim($_GET['status'] ?? ''),
            'stock_status'     => trim($_GET['stock_status'] ?? ''),
            'vehicle_type'     => trim($_GET['vehicle_type'] ?? ''),
            'vehicle_brand_id' => trim($_GET['vehicle_brand_id'] ?? ''),
            'vehicle_model_id' => trim($_GET['vehicle_model_id'] ?? ''),
        ];

        $products = Product::all($filters, $limit, $offset);
        $totalCount = Product::count($filters);
        $totalPages = ceil($totalCount / $limit);

        $categories    = Category::all('active');
        $brands        = Brand::all('active');
        $vehicleBrands = VehicleBrand::all(null, 'active');
        $vehicleModels = VehicleModel::all(null, null, 'active');

        $this->render('products/index', [
            'pageTitle'     => 'Katalogi ya Vipuri Vyote — NGENZI MOTO SPARE PARTS',
            'products'      => $products,
            'totalCount'    => $totalCount,
            'totalPages'    => $totalPages,
            'currentPage'   => $page,
            'filters'       => $filters,
            'categories'    => $categories,
            'brands'        => $brands,
            'vehicleBrands' => $vehicleBrands,
            'vehicleModels' => $vehicleModels,
            'canManage'     => Auth::can('manage_products'),
            'canSeeCosts'   => Auth::canViewFinancials(),
            'csrfToken'     => Helper::csrfToken(),
            'flash'         => Helper::getFlash(),
        ]);
    }

    /**
     * Product Creation View
     */
    public function create(): void {
        Auth::requirePermission('manage_products');

        $categories    = Category::all('active');
        $brands        = Brand::all('active');
        $units         = Unit::all();
        $vehicleBrands = VehicleBrand::all(null, 'active');
        $vehicleModels = VehicleModel::all(null, null, 'active');

        $this->render('products/create', [
            'pageTitle'     => 'Sajili Kipuri Kipya — NGENZI MOTO SPARE PARTS',
            'categories'    => $categories,
            'brands'        => $brands,
            'units'         => $units,
            'vehicleBrands' => $vehicleBrands,
            'vehicleModels' => $vehicleModels,
            'csrfToken'     => Helper::csrfToken(),
            'flash'         => Helper::getFlash(),
        ]);
    }

    /**
     * Store New Product in DB
     */
    public function store(): void {
        Auth::requirePermission('manage_products');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('products');
        }

        if (!Helper::validateCsrf($_POST['csrf_token'] ?? '')) {
            $this->redirect('product_create', 'danger', 'Muda wa fomu umekwisha (Invalid CSRF). Tafadhali jaribu tena.');
        }

        $vehicleModelIds = $_POST['vehicle_models'] ?? [];

        try {
            $newId = Product::create($_POST, $vehicleModelIds);
            $this->redirect("product_show&id={$newId}", 'success', "Kipuri '{$_POST['name']}' kimesajiliwa kikamilifu!");
        } catch (Exception $e) {
            $this->redirect('product_create', 'danger', $e->getMessage());
        }
    }

    /**
     * Product Edit View
     */
    public function edit(): void {
        Auth::requirePermission('manage_products');

        $id = (int)($_GET['id'] ?? 0);
        $product = Product::find($id);

        if (!$product) {
            $this->redirect('products', 'danger', 'Kipuri hakikupatikana.');
        }

        $categories         = Category::all('active');
        $brands             = Brand::all('active');
        $units              = Unit::all();
        $vehicleBrands      = VehicleBrand::all(null, 'active');
        $vehicleModels      = VehicleModel::all(null, null, 'active');
        $assignedVehicleIds = Product::getCompatibleVehicleIds($id);

        $this->render('products/edit', [
            'pageTitle'          => "Hariri Kipuri: {$product['name']} — NGENZI MOTO SPARE PARTS",
            'product'            => $product,
            'categories'         => $categories,
            'brands'             => $brands,
            'units'              => $units,
            'vehicleBrands'      => $vehicleBrands,
            'vehicleModels'      => $vehicleModels,
            'assignedVehicleIds' => $assignedVehicleIds,
            'canManagePrices'    => Auth::can('manage_prices'),
            'canSeeCosts'        => Auth::canViewFinancials(),
            'csrfToken'          => Helper::csrfToken(),
            'flash'              => Helper::getFlash(),
        ]);
    }

    /**
     * Update Product details
     */
    public function update(): void {
        Auth::requirePermission('manage_products');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('products');
        }

        $id = (int)($_POST['id'] ?? 0);
        if (!Helper::validateCsrf($_POST['csrf_token'] ?? '')) {
            $this->redirect("product_edit&id={$id}", 'danger', 'Muda wa fomu umekwisha (Invalid CSRF).');
        }

        $vehicleModelIds = $_POST['vehicle_models'] ?? [];

        try {
            Product::update($id, $_POST, $vehicleModelIds);
            $this->redirect("product_show&id={$id}", 'success', "Taarifa za kipuri zimesasishwa kikamilifu.");
        } catch (Exception $e) {
            $this->redirect("product_edit&id={$id}", 'danger', $e->getMessage());
        }
    }

    /**
     * Product Business Detail View
     */
    public function show(): void {
        Auth::requireAuth();

        $id = (int)($_GET['id'] ?? 0);
        $product = Product::find($id);

        if (!$product) {
            $this->redirect('products', 'danger', 'Kipuri hakikupatikana.');
        }

        $priceHistory = Product::getPriceHistory($id);
        $costHistory  = Auth::canViewFinancials() ? Product::getCostHistory($id) : [];
        $stockHistory = \App\Models\Inventory::getProductHistory($id, 30);
        $returnStats  = Product::getReturnStats($id);
        $returnHistory = Product::getReturnHistory($id, 20);
        $images       = \App\Models\ProductImage::forProduct($id);

        $this->render('products/show', [
            'pageTitle'     => "{$product['name']} ({$product['sku']}) — NGENZI MOTO SPARE PARTS",
            'product'       => $product,
            'images'        => $images,
            'priceHistory'  => $priceHistory,
            'costHistory'   => $costHistory,
            'stockHistory'  => $stockHistory,
            'returnStats'   => $returnStats,
            'returnHistory' => $returnHistory,
            'canManage'     => Auth::can('manage_products'),
            'canSeeCosts'   => Auth::canViewFinancials(),
            'csrfToken'     => Helper::csrfToken(),
            'flash'         => Helper::getFlash(),
        ]);
    }

    /**
     * Toggle product status (active / inactive)
     */
    public function toggleStatus(): void {
        Auth::requirePermission('manage_products');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('products');
        }

        if (!Helper::validateCsrf($_POST['csrf_token'] ?? '')) {
            $this->redirect('products', 'danger', 'Muda wa fomu umekwisha.');
        }

        $id = (int)($_POST['id'] ?? 0);

        try {
            $newStatus = Product::toggleStatus($id);
            $msg = ($newStatus === 'active') ? 'Kipuri kimewashwa kikamilifu.' : 'Kipuri kimezimwa (Inactive) salama bila kufuta historia.';
            $this->redirect('products', 'info', $msg);
        } catch (Exception $e) {
            $this->redirect('products', 'danger', $e->getMessage());
        }
    }

    /**
     * Dedicated Vehicle-Based Spare Search Interface
     * Mobile-friendly cascading lookup: Vehicle Type -> Brand -> Model -> Spares
     */
    public function vehicleSearch(): void {
        Auth::requireAuth();

        $selectedType    = trim($_GET['vehicle_type'] ?? '');
        $selectedBrandId = (int)($_GET['brand_id'] ?? 0);
        $selectedModelId = (int)($_GET['model_id'] ?? 0);

        $vehicleBrands = [];
        if (!empty($selectedType)) {
            $vehicleBrands = VehicleBrand::all($selectedType, 'active');
        }

        $vehicleModels = [];
        if (!empty($selectedBrandId)) {
            $vehicleModels = VehicleModel::all($selectedBrandId, $selectedType ?: null, 'active');
        }

        $compatibleProducts = [];
        $chosenModel = null;
        if (!empty($selectedModelId)) {
            $chosenModel = VehicleModel::find($selectedModelId);
            $compatibleProducts = Product::searchByVehicle($selectedModelId);
        }

        $this->render('products/vehicle_search', [
            'pageTitle'          => 'Tafuta Vipuri kwa Muundo wa Chombo — NGENZI MOTO SPARE PARTS',
            'selectedType'       => $selectedType,
            'selectedBrandId'    => $selectedBrandId,
            'selectedModelId'    => $selectedModelId,
            'vehicleBrands'      => $vehicleBrands,
            'vehicleModels'      => $vehicleModels,
            'chosenModel'        => $chosenModel,
            'compatibleProducts' => $compatibleProducts,
            'canSeeCosts'        => Auth::canViewFinancials(),
            'flash'              => Helper::getFlash(),
        ]);
    }

    /**
     * Fast AJAX product search for autocomplete and POS preparation
     */
    public function ajaxSearch(): void {
        Auth::requireAuth();
        header('Content-Type: application/json');

        $query = trim($_GET['q'] ?? '');
        if (strlen($query) < 2) {
            echo json_encode([]);
            exit;
        }

        $products = Product::all(['search' => $query, 'status' => 'active'], 15, 0);
        $canSeeCosts = Auth::canViewFinancials();

        $results = array_map(function ($p) use ($canSeeCosts) {
            return [
                'id'            => $p['id'],
                'sku'           => $p['sku'],
                'name'          => $p['name'],
                'category'      => $p['category_name'],
                'selling_price' => (float)$p['selling_price'],
                'buying_price'  => $canSeeCosts ? (float)$p['buying_price'] : null,
                'current_stock' => (int)$p['current_stock'],
                'unit'          => $p['unit_short'],
                'shelf'         => $p['shelf_location'],
            ];
        }, $products);

        echo json_encode($results);
        exit;
    }

    /**
     * Upload product image (Phase 17)
     */
    public function uploadImage(): void {
        Auth::requireAuth();
        if (!Auth::hasPermission('products.create') && !Auth::hasPermission('manage_products') && !Auth::isSuperAdmin()) {
            $this->setFlash('danger', 'Huna mamlaka ya kupakia picha za vipuri.');
            $this->redirect('products');
        }

        $productId = (int)($_POST['product_id'] ?? 0);
        if ($productId <= 0 || empty($_FILES['image'])) {
            $this->setFlash('danger', 'Tafadhali chagua picha ya kupakia.');
            $this->redirect('products');
        }

        try {
            $imageUrl = \App\Models\ProductImage::validateAndUpload($_FILES['image'], $productId);
            $altText = trim($_POST['alt_text'] ?? '');
            $isPrimary = !empty($_POST['is_primary']);

            \App\Models\ProductImage::add($productId, $imageUrl, $altText, 0, $isPrimary);
            $this->setFlash('success', 'Picha ya kipuri imepakiwa na kuhifadhiwa kikamilifu.');
        } catch (Exception $e) {
            $this->setFlash('danger', $e->getMessage());
        }

        $this->redirect('product_show&id=' . $productId);
    }

    /**
     * Set image as primary (Phase 17)
     */
    public function setPrimaryImage(): void {
        Auth::requireAuth();
        $imageId = (int)($_POST['image_id'] ?? 0);
        $productId = (int)($_POST['product_id'] ?? 0);

        if ($imageId > 0 && $productId > 0) {
            \App\Models\ProductImage::setPrimary($imageId, $productId);
            $this->setFlash('success', 'Picha kuu imesasishwa.');
        }

        $this->redirect('product_show&id=' . $productId);
    }

    /**
     * Delete product image (Phase 17)
     */
    public function deleteImage(): void {
        Auth::requireAuth();
        $imageId = (int)($_POST['image_id'] ?? 0);
        $productId = (int)($_POST['product_id'] ?? 0);

        if ($imageId > 0 && $productId > 0) {
            \App\Models\ProductImage::delete($imageId, $productId);
            $this->setFlash('success', 'Picha ya kipuri imeondolewa.');
        }

        $this->redirect('product_show&id=' . $productId);
    }
}

