<?php
/**
 * NGENZI MOTO SPARE PARTS
 * Garage Controller — Phase 7
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Helper;
use App\Models\Garage;
use App\Models\Mechanic;
use Exception;

class GarageController extends Controller {

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
     * Master Garages Directory
     */
    public function index(): void {
        $this->checkPerm('garages.view');

        $search = trim($_GET['search'] ?? '');
        $garageType = trim($_GET['garage_type'] ?? '');
        $area = trim($_GET['area'] ?? '');
        $status = trim($_GET['status'] ?? '');
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 20;

        $filters = [
            'search'      => $search,
            'garage_type' => $garageType,
            'area'        => $area,
            'status'      => $status
        ];

        $garages = Garage::all($filters, $page, $perPage);
        $total = Garage::count($filters);
        $totalPages = max(1, (int)ceil($total / $perPage));

        $canCreate = Auth::hasPermission('garages.create') || Auth::hasRole('super_admin') || Auth::hasRole('manager');
        $canEdit = Auth::hasPermission('garages.edit') || Auth::hasRole('super_admin') || Auth::hasRole('manager');
        $canDeactivate = Auth::hasPermission('garages.deactivate') || Auth::hasRole('super_admin') || Auth::hasRole('manager');

        $this->render('garages/index', [
            'title'         => 'Orodha ya Gereji & Karakana (Garages)',
            'activeMenu'    => 'garages_list',
            'garages'       => $garages,
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
     * Create Garage Form
     */
    public function create(): void {
        $this->checkPerm('garages.create');

        $suggestedCode = Garage::generateCode();

        $this->render('garages/create', [
            'title'         => 'Sajili Gereji / Karakana Mpya',
            'activeMenu'    => 'garages_create',
            'suggestedCode' => $suggestedCode
        ]);
    }

    /**
     * Store Garage POST
     */
    public function store(): void {
        $this->checkPerm('garages.create');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/index.php?route=garages');
            return;
        }

        $csrfToken = $_POST['csrf_token'] ?? '';
        if (!Helper::validateCsrf($csrfToken)) {
            $this->setFlash('danger', 'Hitilafu ya usalama (CSRF invalid).');
            $this->redirect('/index.php?route=garage_create');
            return;
        }

        $garageName = trim($_POST['garage_name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $location = trim($_POST['location'] ?? '');

        if (empty($garageName) || empty($phone) || empty($location)) {
            $this->setFlash('danger', 'Jina la gereji, simu na eneo vinahitajika.');
            $this->redirect('/index.php?route=garage_create');
            return;
        }

        try {
            $garageId = Garage::create([
                'garage_code'            => trim($_POST['garage_code'] ?? ''),
                'garage_name'            => $garageName,
                'owner_name'             => trim($_POST['owner_name'] ?? ''),
                'phone'                  => $phone,
                'alternative_phone'      => trim($_POST['alternative_phone'] ?? ''),
                'email'                  => trim($_POST['email'] ?? ''),
                'location'               => $location,
                'area'                   => trim($_POST['area'] ?? $location),
                'address'                => trim($_POST['address'] ?? ''),
                'specialization'         => trim($_POST['specialization'] ?? ''),
                'garage_type'            => trim($_POST['garage_type'] ?? 'Motorcycle Garage'),
                'credit_limit'           => (float)($_POST['credit_limit'] ?? 0.00),
                'notes'                  => trim($_POST['notes'] ?? ''),
                'create_customer_profile'=> !empty($_POST['create_customer_profile']) ? 1 : 0,
                'created_by'             => Auth::id()
            ]);

            $this->setFlash('success', "Gereji '{$garageName}' imesajiliwa kikamilifu!");
            $this->redirect("/index.php?route=garage_show&id={$garageId}");

        } catch (Exception $e) {
            $this->setFlash('danger', "Hitilafu: " . $e->getMessage());
            $this->redirect('/index.php?route=garage_create');
        }
    }

    /**
     * Garage Profile Page
     */
    public function show(): void {
        $this->checkPerm('garages.view');

        $id = (int)($_GET['id'] ?? 0);
        $garage = Garage::find($id);

        if (!$garage) {
            $this->setFlash('danger', 'Gereji haikupatikana.');
            $this->redirect('/index.php?route=garages');
            return;
        }

        $mechanics = Mechanic::getByGarage($id);

        $canEdit = Auth::hasPermission('garages.edit') || Auth::hasRole('super_admin') || Auth::hasRole('manager');
        $canDeactivate = Auth::hasPermission('garages.deactivate') || Auth::hasRole('super_admin') || Auth::hasRole('manager');

        $this->render('garages/show', [
            'title'         => "Wasifu wa Gereji: {$garage['garage_name']} ({$garage['garage_code']})",
            'activeMenu'    => 'garages_list',
            'garage'        => $garage,
            'mechanics'     => $mechanics,
            'canEdit'       => $canEdit,
            'canDeactivate' => $canDeactivate
        ]);
    }

    /**
     * Edit Garage Form
     */
    public function edit(): void {
        $this->checkPerm('garages.edit');

        $id = (int)($_GET['id'] ?? 0);
        $garage = Garage::find($id);

        if (!$garage) {
            $this->setFlash('danger', 'Gereji haikupatikana.');
            $this->redirect('/index.php?route=garages');
            return;
        }

        $this->render('garages/edit', [
            'title'      => "Hariri Gereji: {$garage['garage_name']}",
            'activeMenu' => 'garages_list',
            'garage'     => $garage
        ]);
    }

    /**
     * Update Garage POST
     */
    public function update(): void {
        $this->checkPerm('garages.edit');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/index.php?route=garages');
            return;
        }

        $csrfToken = $_POST['csrf_token'] ?? '';
        if (!Helper::validateCsrf($csrfToken)) {
            $this->setFlash('danger', 'Hitilafu ya usalama.');
            $this->redirect('/index.php?route=garages');
            return;
        }

        $id = (int)($_POST['id'] ?? 0);
        $garageName = trim($_POST['garage_name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $location = trim($_POST['location'] ?? '');

        if (empty($garageName) || empty($phone) || empty($location)) {
            $this->setFlash('danger', 'Jina la gereji, simu na eneo vinahitajika.');
            $this->redirect("/index.php?route=garage_edit&id={$id}");
            return;
        }

        try {
            Garage::update($id, [
                'garage_code'       => trim($_POST['garage_code'] ?? ''),
                'garage_name'       => $garageName,
                'owner_name'        => trim($_POST['owner_name'] ?? ''),
                'phone'             => $phone,
                'alternative_phone' => trim($_POST['alternative_phone'] ?? ''),
                'email'             => trim($_POST['email'] ?? ''),
                'location'          => $location,
                'area'              => trim($_POST['area'] ?? $location),
                'address'           => trim($_POST['address'] ?? ''),
                'specialization'    => trim($_POST['specialization'] ?? ''),
                'garage_type'       => trim($_POST['garage_type'] ?? 'Motorcycle Garage'),
                'credit_limit'      => (float)($_POST['credit_limit'] ?? 0.00),
                'notes'             => trim($_POST['notes'] ?? ''),
                'status'            => trim($_POST['status'] ?? 'active')
            ]);

            $this->setFlash('success', "Taarifa za gereji '{$garageName}' zimesasishwa!");
            $this->redirect("/index.php?route=garage_show&id={$id}");

        } catch (Exception $e) {
            $this->setFlash('danger', "Hitilafu: " . $e->getMessage());
            $this->redirect("/index.php?route=garage_edit&id={$id}");
        }
    }

    /**
     * Toggle Garage Status (Active / Inactive)
     */
    public function toggleStatus(): void {
        $this->checkPerm('garages.deactivate');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/index.php?route=garages');
            return;
        }

        $csrfToken = $_POST['csrf_token'] ?? '';
        if (!Helper::validateCsrf($csrfToken)) {
            $this->setFlash('danger', 'Hitilafu ya usalama.');
            $this->redirect('/index.php?route=garages');
            return;
        }

        $id = (int)($_POST['id'] ?? 0);
        $status = trim($_POST['status'] ?? 'inactive');

        try {
            Garage::setStatus($id, $status, Auth::id());
            $this->setFlash('success', "Hadhi ya gereji imebadilishwa kuwa '{$status}'!");
        } catch (Exception $e) {
            $this->setFlash('danger', "Hitilafu: " . $e->getMessage());
        }

        $this->redirect("/index.php?route=garage_show&id={$id}");
    }
}
