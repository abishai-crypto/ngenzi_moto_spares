<?php
/**
 * NGENZI MOTO SPARE PARTS
 * Customer Controller — Phase 7
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Helper;
use App\Models\Customer;
use App\Models\CustomerVehicle;
use App\Models\VehicleModel;
use Exception;

class CustomerController extends Controller {

    /**
     * Helper to verify permissions
     */
    protected function checkPerm(string $permission): void {
        Auth::requireLogin();
        if (Auth::hasRole('super_admin') || Auth::hasRole('manager')) {
            return;
        }
        if (!Auth::hasPermission($permission)) {
            $this->setFlash('danger', 'Huna idhini ya kutekeleza kitendo hiki (Access Denied).');
            $this->redirect('/index.php?route=dashboard');
            exit;
        }
    }

    /**
     * Customer Dashboard
     */
    public function dashboard(): void {
        $this->checkPerm('customers.view');

        $stats = Customer::getDashboardStats();
        $canCreate = Auth::hasPermission('customers.create') || Auth::hasRole('super_admin') || Auth::hasRole('manager') || Auth::hasRole('cashier');

        $this->render('customers/dashboard', [
            'title'      => 'Dashibodi ya Wateja & Gereji',
            'activeMenu' => 'customers_dashboard',
            'stats'      => $stats,
            'canCreate'  => $canCreate
        ]);
    }

    /**
     * Master Customer List with Search, Filters & Pagination
     */
    public function index(): void {
        $this->checkPerm('customers.view');

        $search = trim($_GET['search'] ?? '');
        $customerType = trim($_GET['customer_type'] ?? '');
        $status = trim($_GET['status'] ?? '');
        $area = trim($_GET['area'] ?? '');
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 20;

        $filters = [
            'search'        => $search,
            'customer_type' => $customerType,
            'status'        => $status,
            'area'          => $area
        ];

        $customers = Customer::all($filters, $page, $perPage);
        $total = Customer::count($filters);
        $totalPages = max(1, (int)ceil($total / $perPage));

        $canCreate = Auth::hasPermission('customers.create') || Auth::hasRole('super_admin') || Auth::hasRole('manager') || Auth::hasRole('cashier');
        $canEdit = Auth::hasPermission('customers.edit') || Auth::hasRole('super_admin') || Auth::hasRole('manager');
        $canDeactivate = Auth::hasPermission('customers.deactivate') || Auth::hasRole('super_admin') || Auth::hasRole('manager');

        $this->render('customers/index', [
            'title'         => 'Orodha ya Wateja (Customer Directory)',
            'activeMenu'    => 'customers_list',
            'customers'     => $customers,
            'total'         => $total,
            'page'          => $page,
            'totalPages'    => $totalPages,
            'filters'       => $filters,
            'canCreate'     => $canCreate,
            'canEdit'       => $canEdit,
            'canDeactivate' => $canDeactivate
        ]);
    }

    /**
     * Form to add a new customer
     */
    public function create(): void {
        $this->checkPerm('customers.create');

        $suggestedCode = Customer::generateCode();

        $this->render('customers/create', [
            'title'         => 'Sajili Mteja Mpya',
            'activeMenu'    => 'customers_create',
            'suggestedCode' => $suggestedCode
        ]);
    }

    /**
     * Process add customer POST
     */
    public function store(): void {
        $this->checkPerm('customers.create');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/index.php?route=customers');
            return;
        }

        $csrfToken = $_POST['csrf_token'] ?? '';
        if (!Helper::validateCsrf($csrfToken)) {
            $this->setFlash('danger', 'Hitilafu ya kiusalama (Invalid CSRF Token).');
            $this->redirect('/index.php?route=customer_create');
            return;
        }

        $name = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $altPhone = trim($_POST['alternative_phone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $area = trim($_POST['area'] ?? '');
        $gender = trim($_POST['gender'] ?? '');
        $customerType = trim($_POST['customer_type'] ?? 'individual');
        $creditLimit = (float)($_POST['credit_limit'] ?? 0.00);
        $notes = trim($_POST['notes'] ?? '');
        $customCode = trim($_POST['customer_code'] ?? '');

        if (empty($name)) {
            $this->setFlash('danger', 'Tafadhali jaza jina la mteja.');
            $this->redirect('/index.php?route=customer_create');
            return;
        }

        try {
            $customerId = Customer::create([
                'customer_code'     => $customCode,
                'name'              => $name,
                'phone'             => $phone,
                'alternative_phone' => $altPhone,
                'email'             => $email,
                'address'           => $address,
                'area'              => $area,
                'gender'            => $gender,
                'customer_type'     => $customerType,
                'credit_limit'      => $creditLimit,
                'notes'             => $notes,
                'created_by'        => Auth::id()
            ]);

            $this->setFlash('success', "Mteja '{$name}' amesajiliwa kikamilifu!");
            $this->redirect("/index.php?route=customer_show&id={$customerId}");

        } catch (Exception $e) {
            $this->setFlash('danger', "Hitilafu: " . $e->getMessage());
            $this->redirect('/index.php?route=customer_create');
        }
    }

    /**
     * Detailed Customer Profile
     */
    public function show(): void {
        $this->checkPerm('customers.view');

        $id = (int)($_GET['id'] ?? 0);
        $customer = Customer::find($id);

        if (!$customer) {
            $this->setFlash('danger', 'Mteja hakupatikana kwenye mfumo.');
            $this->redirect('/index.php?route=customers');
            return;
        }

        $stats = Customer::getStatistics($id);
        $purchaseHistory = Customer::getPurchaseHistory($id, 30);
        $paymentHistory = Customer::getPaymentHistory($id, 30);
        $returnsHistory = Customer::getCustomerReturns($id, 30);
        $vehicles = CustomerVehicle::getByCustomer($id);
        $compatibleParts = CustomerVehicle::getCompatiblePartsForCustomer($id);
        $vehicleModels = VehicleModel::all();

        $canEdit = Auth::hasPermission('customers.edit') || Auth::hasRole('super_admin') || Auth::hasRole('manager');
        $canDeactivate = Auth::hasPermission('customers.deactivate') || Auth::hasRole('super_admin') || Auth::hasRole('manager');
        $canManageVehicles = Auth::hasPermission('customer_vehicles.create') || Auth::hasRole('super_admin') || Auth::hasRole('manager') || Auth::hasRole('cashier');

        $this->render('customers/show', [
            'title'             => "Wasifu wa Mteja: {$customer['name']} ({$customer['customer_code']})",
            'activeMenu'        => 'customers_list',
            'customer'          => $customer,
            'stats'             => $stats,
            'purchaseHistory'   => $purchaseHistory,
            'paymentHistory'    => $paymentHistory,
            'returnsHistory'    => $returnsHistory,
            'vehicles'          => $vehicles,
            'compatibleParts'   => $compatibleParts,
            'vehicleModels'     => $vehicleModels,
            'canEdit'           => $canEdit,
            'canDeactivate'     => $canDeactivate,
            'canManageVehicles' => $canManageVehicles
        ]);
    }

    /**
     * Form to edit customer
     */
    public function edit(): void {
        $this->checkPerm('customers.edit');

        $id = (int)($_GET['id'] ?? 0);
        $customer = Customer::find($id);

        if (!$customer) {
            $this->setFlash('danger', 'Mteja hakupatikana.');
            $this->redirect('/index.php?route=customers');
            return;
        }

        $this->render('customers/edit', [
            'title'      => "Hariri Mteja: {$customer['name']}",
            'activeMenu' => 'customers_list',
            'customer'   => $customer
        ]);
    }

    /**
     * Process edit customer POST
     */
    public function update(): void {
        $this->checkPerm('customers.edit');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/index.php?route=customers');
            return;
        }

        $csrfToken = $_POST['csrf_token'] ?? '';
        if (!Helper::validateCsrf($csrfToken)) {
            $this->setFlash('danger', 'Hitilafu ya usalama (CSRF Token invalid).');
            $this->redirect('/index.php?route=customers');
            return;
        }

        $id = (int)($_POST['id'] ?? 0);
        $customer = Customer::find($id);
        if (!$customer) {
            $this->setFlash('danger', 'Mteja hakupatikana.');
            $this->redirect('/index.php?route=customers');
            return;
        }

        $name = trim($_POST['name'] ?? '');
        if (empty($name)) {
            $this->setFlash('danger', 'Jina la mteja haliwezi kuwa tupu.');
            $this->redirect("/index.php?route=customer_edit&id={$id}");
            return;
        }

        try {
            Customer::update($id, [
                'customer_code'     => trim($_POST['customer_code'] ?? $customer['customer_code']),
                'name'              => $name,
                'phone'             => trim($_POST['phone'] ?? ''),
                'alternative_phone' => trim($_POST['alternative_phone'] ?? ''),
                'email'             => trim($_POST['email'] ?? ''),
                'address'           => trim($_POST['address'] ?? ''),
                'area'              => trim($_POST['area'] ?? ''),
                'gender'            => trim($_POST['gender'] ?? ''),
                'customer_type'     => trim($_POST['customer_type'] ?? $customer['customer_type']),
                'credit_limit'      => (float)($_POST['credit_limit'] ?? $customer['credit_limit']),
                'notes'             => trim($_POST['notes'] ?? ''),
                'status'            => trim($_POST['status'] ?? $customer['status']),
                'updated_by'        => Auth::id()
            ]);

            $this->setFlash('success', "Taarifa za mteja '{$name}' zimesasishwa!");
            $this->redirect("/index.php?route=customer_show&id={$id}");

        } catch (Exception $e) {
            $this->setFlash('danger', "Hitilafu: " . $e->getMessage());
            $this->redirect("/index.php?route=customer_edit&id={$id}");
        }
    }

    /**
     * Toggle Customer status (Active / Inactive / Blocked)
     */
    public function toggleStatus(): void {
        $this->checkPerm('customers.deactivate');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/index.php?route=customers');
            return;
        }

        $csrfToken = $_POST['csrf_token'] ?? '';
        if (!Helper::validateCsrf($csrfToken)) {
            $this->setFlash('danger', 'Hitilafu ya kiusalama.');
            $this->redirect('/index.php?route=customers');
            return;
        }

        $id = (int)($_POST['id'] ?? 0);
        $status = trim($_POST['status'] ?? 'inactive');

        try {
            Customer::setStatus($id, $status, Auth::id());
            $this->setFlash('success', "Hadhi ya mteja imebadilishwa kuwa '{$status}'!");
        } catch (Exception $e) {
            $this->setFlash('danger', "Hitilafu: " . $e->getMessage());
        }

        $this->redirect("/index.php?route=customer_show&id={$id}");
    }

    /**
     * Add vehicle to customer
     */
    public function addVehicle(): void {
        $this->checkPerm('customer_vehicles.create');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/index.php?route=customers');
            return;
        }

        $csrfToken = $_POST['csrf_token'] ?? '';
        if (!Helper::validateCsrf($csrfToken)) {
            $this->setFlash('danger', 'Hitilafu ya kiusalama (CSRF).');
            $this->redirect('/index.php?route=customers');
            return;
        }

        $customerId = (int)($_POST['customer_id'] ?? 0);
        $vehicleModelId = (int)($_POST['vehicle_model_id'] ?? 0);
        $regNo = trim($_POST['registration_number'] ?? '');
        $chassisNo = trim($_POST['chassis_number'] ?? '');
        $engineNo = trim($_POST['engine_number'] ?? '');
        $notes = trim($_POST['notes'] ?? '');

        try {
            CustomerVehicle::addVehicle([
                'customer_id'         => $customerId,
                'vehicle_model_id'    => $vehicleModelId,
                'registration_number' => $regNo,
                'chassis_number'      => $chassisNo,
                'engine_number'       => $engineNo,
                'notes'               => $notes,
                'user_id'             => Auth::id()
            ]);

            $this->setFlash('success', 'Chombo kimeunganishwa na mteja kikamilifu!');
        } catch (Exception $e) {
            $this->setFlash('danger', "Hitilafu: " . $e->getMessage());
        }

        $this->redirect("/index.php?route=customer_show&id={$customerId}");
    }

    /**
     * Remove / deactivate vehicle association
     */
    public function removeVehicle(): void {
        $this->checkPerm('customer_vehicles.delete');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/index.php?route=customers');
            return;
        }

        $csrfToken = $_POST['csrf_token'] ?? '';
        if (!Helper::validateCsrf($csrfToken)) {
            $this->setFlash('danger', 'Hitilafu ya kiusalama.');
            $this->redirect('/index.php?route=customers');
            return;
        }

        $vehicleId = (int)($_POST['vehicle_id'] ?? 0);
        $customerId = (int)($_POST['customer_id'] ?? 0);

        try {
            CustomerVehicle::removeVehicle($vehicleId, Auth::id());
            $this->setFlash('success', 'Chombo kimeondolewa kwenye wasifu wa mteja.');
        } catch (Exception $e) {
            $this->setFlash('danger', "Hitilafu: " . $e->getMessage());
        }

        $this->redirect("/index.php?route=customer_show&id={$customerId}");
    }

    /**
     * AJAX endpoint for customer lookup (for POS / future sales)
     */
    public function ajaxSearch(): void {
        $this->checkPerm('customers.view');
        header('Content-Type: application/json');

        $query = trim($_GET['q'] ?? '');
        if (strlen($query) < 1) {
            echo json_encode([]);
            return;
        }

        $results = Customer::search($query, 15);
        echo json_encode($results);
    }
}
