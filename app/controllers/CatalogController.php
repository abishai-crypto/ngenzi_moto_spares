<?php
/**
 * NGENZI MOTO SPARE PARTS
 * CatalogController (Phase 4)
 * Comprehensive Management of Categories, Brands, Units, Vehicle Brands & Models
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Helper;
use App\Models\Category;
use App\Models\Brand;
use App\Models\Unit;
use App\Models\VehicleBrand;
use App\Models\VehicleModel;
use Exception;

class CatalogController extends Controller {

    // =========================================================================
    // CATEGORIES
    // =========================================================================

    public function categories(): void {
        Auth::requireAuth();
        $status = trim($_GET['status'] ?? '');
        $categories = Category::all($status ?: null);

        $this->render('catalog/categories', [
            'pageTitle'  => 'Usimamizi wa Kategoria za Vipuri — NGENZI MOTO SPARE PARTS',
            'categories' => $categories,
            'status'     => $status,
            'canManage'  => Auth::can('manage_categories'),
            'csrfToken'  => Helper::csrfToken(),
            'flash'      => Helper::getFlash(),
        ]);
    }

    public function categoryStore(): void {
        Auth::requirePermission('manage_categories');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('categories');
        }

        if (!Helper::validateCsrf($_POST['csrf_token'] ?? '')) {
            $this->redirect('categories', 'danger', 'Muda wa fomu umekwisha (Invalid CSRF).');
        }

        try {
            Category::create($_POST);
            $this->redirect('categories', 'success', "Kategoria '{$_POST['name']}' imesajiliwa kikamilifu!");
        } catch (Exception $e) {
            $this->redirect('categories', 'danger', $e->getMessage());
        }
    }

    public function categoryUpdate(): void {
        Auth::requirePermission('manage_categories');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('categories');
        }

        $id = (int)($_POST['id'] ?? 0);
        if (!Helper::validateCsrf($_POST['csrf_token'] ?? '')) {
            $this->redirect('categories', 'danger', 'Muda wa fomu umekwisha.');
        }

        try {
            Category::update($id, $_POST);
            $this->redirect('categories', 'success', "Kategoria imesasishwa kikamilifu.");
        } catch (Exception $e) {
            $this->redirect('categories', 'danger', $e->getMessage());
        }
    }

    public function categoryToggle(): void {
        Auth::requirePermission('manage_categories');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('categories');
        }

        $id = (int)($_POST['id'] ?? 0);
        if (!Helper::validateCsrf($_POST['csrf_token'] ?? '')) {
            $this->redirect('categories', 'danger', 'Muda wa fomu umekwisha.');
        }

        try {
            $newStatus = Category::toggleStatus($id);
            $this->redirect('categories', 'info', "Hali ya kategoria imebadilishwa kuwa: {$newStatus}");
        } catch (Exception $e) {
            $this->redirect('categories', 'danger', $e->getMessage());
        }
    }

    // =========================================================================
    // BRANDS
    // =========================================================================

    public function brands(): void {
        Auth::requireAuth();
        $status = trim($_GET['status'] ?? '');
        $brands = Brand::all($status ?: null);

        $this->render('catalog/brands', [
            'pageTitle' => 'Chapa za Vipuri (Spare Part Brands) — NGENZI MOTO SPARE PARTS',
            'brands'    => $brands,
            'status'    => $status,
            'canManage' => Auth::can('manage_products'),
            'csrfToken' => Helper::csrfToken(),
            'flash'     => Helper::getFlash(),
        ]);
    }

    public function brandStore(): void {
        Auth::requirePermission('manage_products');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('brands');
        }

        if (!Helper::validateCsrf($_POST['csrf_token'] ?? '')) {
            $this->redirect('brands', 'danger', 'Muda wa fomu umekwisha.');
        }

        try {
            Brand::create($_POST);
            $this->redirect('brands', 'success', "Brand '{$_POST['name']}' imesajiliwa kikamilifu!");
        } catch (Exception $e) {
            $this->redirect('brands', 'danger', $e->getMessage());
        }
    }

    public function brandUpdate(): void {
        Auth::requirePermission('manage_products');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('brands');
        }

        $id = (int)($_POST['id'] ?? 0);
        if (!Helper::validateCsrf($_POST['csrf_token'] ?? '')) {
            $this->redirect('brands', 'danger', 'Muda wa fomu umekwisha.');
        }

        try {
            Brand::update($id, $_POST);
            $this->redirect('brands', 'success', "Brand imesasishwa kikamilifu.");
        } catch (Exception $e) {
            $this->redirect('brands', 'danger', $e->getMessage());
        }
    }

    public function brandToggle(): void {
        Auth::requirePermission('manage_products');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('brands');
        }

        $id = (int)($_POST['id'] ?? 0);
        if (!Helper::validateCsrf($_POST['csrf_token'] ?? '')) {
            $this->redirect('brands', 'danger', 'Muda wa fomu umekwisha.');
        }

        try {
            $newStatus = Brand::toggleStatus($id);
            $this->redirect('brands', 'info', "Hali ya brand imebadilishwa kuwa: {$newStatus}");
        } catch (Exception $e) {
            $this->redirect('brands', 'danger', $e->getMessage());
        }
    }

    // =========================================================================
    // UNITS
    // =========================================================================

    public function units(): void {
        Auth::requireAuth();
        $units = Unit::all();

        $this->render('catalog/units', [
            'pageTitle' => 'Vipimo vya Vipuri (Units) — NGENZI MOTO SPARE PARTS',
            'units'     => $units,
            'canManage' => Auth::can('manage_products'),
            'csrfToken' => Helper::csrfToken(),
            'flash'     => Helper::getFlash(),
        ]);
    }

    public function unitStore(): void {
        Auth::requirePermission('manage_products');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('units');
        }

        if (!Helper::validateCsrf($_POST['csrf_token'] ?? '')) {
            $this->redirect('units', 'danger', 'Muda wa fomu umekwisha.');
        }

        try {
            Unit::create($_POST);
            $this->redirect('units', 'success', "Kipimo kimesajiliwa kikamilifu!");
        } catch (Exception $e) {
            $this->redirect('units', 'danger', $e->getMessage());
        }
    }

    public function unitUpdate(): void {
        Auth::requirePermission('manage_products');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('units');
        }

        $id = (int)($_POST['id'] ?? 0);
        if (!Helper::validateCsrf($_POST['csrf_token'] ?? '')) {
            $this->redirect('units', 'danger', 'Muda wa fomu umekwisha.');
        }

        try {
            Unit::update($id, $_POST);
            $this->redirect('units', 'success', "Kipimo kimesasishwa kikamilifu.");
        } catch (Exception $e) {
            $this->redirect('units', 'danger', $e->getMessage());
        }
    }

    // =========================================================================
    // VEHICLE BRANDS & MODELS
    // =========================================================================

    public function vehicles(): void {
        Auth::requireAuth();

        $activeTab = trim($_GET['tab'] ?? 'models');
        $selectedBrandId = !empty($_GET['brand_id']) ? (int)$_GET['brand_id'] : null;
        $selectedType = trim($_GET['type'] ?? '');
        $status = trim($_GET['status'] ?? '');

        $vehicleBrands = VehicleBrand::all(null, $status ?: null);
        $vehicleModels = VehicleModel::all($selectedBrandId, $selectedType ?: null, $status ?: null);

        $this->render('catalog/vehicles', [
            'pageTitle'       => 'Pikipiki & Bajaji (Vehicles & Models) — NGENZI MOTO SPARE PARTS',
            'activeTab'       => $activeTab,
            'vehicleBrands'   => $vehicleBrands,
            'vehicleModels'   => $vehicleModels,
            'selectedBrandId' => $selectedBrandId,
            'selectedType'    => $selectedType,
            'status'          => $status,
            'canManage'       => Auth::can('manage_vehicles'),
            'csrfToken'       => Helper::csrfToken(),
            'flash'           => Helper::getFlash(),
        ]);
    }

    public function vehicleBrandStore(): void {
        Auth::requirePermission('manage_vehicles');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('vehicles&tab=brands');
        }

        if (!Helper::validateCsrf($_POST['csrf_token'] ?? '')) {
            $this->redirect('vehicles&tab=brands', 'danger', 'Muda wa fomu umekwisha.');
        }

        try {
            VehicleBrand::create($_POST);
            $this->redirect('vehicles&tab=brands', 'success', "Chapa ya chombo '{$_POST['name']}' imesajiliwa kikamilifu!");
        } catch (Exception $e) {
            $this->redirect('vehicles&tab=brands', 'danger', $e->getMessage());
        }
    }

    public function vehicleBrandToggle(): void {
        Auth::requirePermission('manage_vehicles');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('vehicles&tab=brands');
        }

        $id = (int)($_POST['id'] ?? 0);
        if (!Helper::validateCsrf($_POST['csrf_token'] ?? '')) {
            $this->redirect('vehicles&tab=brands', 'danger', 'Muda wa fomu umekwisha.');
        }

        try {
            $newStatus = VehicleBrand::toggleStatus($id);
            $this->redirect('vehicles&tab=brands', 'info', "Hali ya chapa imebadilishwa kuwa: {$newStatus}");
        } catch (Exception $e) {
            $this->redirect('vehicles&tab=brands', 'danger', $e->getMessage());
        }
    }

    public function vehicleModelStore(): void {
        Auth::requirePermission('manage_vehicles');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('vehicles&tab=models');
        }

        if (!Helper::validateCsrf($_POST['csrf_token'] ?? '')) {
            $this->redirect('vehicles&tab=models', 'danger', 'Muda wa fomu umekwisha.');
        }

        try {
            VehicleModel::create($_POST);
            $this->redirect('vehicles&tab=models', 'success', "Muundo wa chombo '{$_POST['model_name']}' umesajiliwa kikamilifu!");
        } catch (Exception $e) {
            $this->redirect('vehicles&tab=models', 'danger', $e->getMessage());
        }
    }

    public function vehicleModelToggle(): void {
        Auth::requirePermission('manage_vehicles');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('vehicles&tab=models');
        }

        $id = (int)($_POST['id'] ?? 0);
        if (!Helper::validateCsrf($_POST['csrf_token'] ?? '')) {
            $this->redirect('vehicles&tab=models', 'danger', 'Muda wa fomu umekwisha.');
        }

        try {
            $newStatus = VehicleModel::toggleStatus($id);
            $this->redirect('vehicles&tab=models', 'info', "Hali ya muundo imebadilishwa kuwa: {$newStatus}");
        } catch (Exception $e) {
            $this->redirect('vehicles&tab=models', 'danger', $e->getMessage());
        }
    }
}
