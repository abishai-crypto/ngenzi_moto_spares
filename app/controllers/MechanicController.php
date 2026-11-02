<?php
/**
 * NGENZI MOTO SPARE PARTS
 * Mechanic Controller — Phase 7
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Helper;
use App\Models\Mechanic;
use App\Models\Garage;
use App\Models\Customer;
use Exception;

class MechanicController extends Controller {

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
     * Master Mechanics Directory
     */
    public function index(): void {
        $this->checkPerm('mechanics.view');

        $search = trim($_GET['search'] ?? '');
        $garageId = !empty($_GET['garage_id']) ? (int)$_GET['garage_id'] : null;
        $specialization = trim($_GET['specialization'] ?? '');
        $status = trim($_GET['status'] ?? '');
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 20;

        $filters = [
            'search'         => $search,
            'garage_id'      => $garageId,
            'specialization' => $specialization,
            'status'         => $status
        ];

        $mechanics = Mechanic::all($filters, $page, $perPage);
        $total = Mechanic::count($filters);
        $totalPages = max(1, (int)ceil($total / $perPage));
        $garages = Garage::all(['status' => 'active'], 1, 100);

        $canCreate = Auth::hasPermission('mechanics.create') || Auth::hasRole('super_admin') || Auth::hasRole('manager');
        $canEdit = Auth::hasPermission('mechanics.edit') || Auth::hasRole('super_admin') || Auth::hasRole('manager');
        $canDeactivate = Auth::hasPermission('mechanics.deactivate') || Auth::hasRole('super_admin') || Auth::hasRole('manager');

        $this->render('mechanics/index', [
            'title'         => 'Orodha ya Mafundi (Mechanics Directory)',
            'activeMenu'    => 'mechanics_list',
            'mechanics'     => $mechanics,
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
     * Create Mechanic Form
     */
    public function create(): void {
        $this->checkPerm('mechanics.create');

        $garages = Garage::all(['status' => 'active'], 1, 100);
        $suggestedCode = Mechanic::generateCode();

        $this->render('mechanics/create', [
            'title'         => 'Sajili Fundi Mpya',
            'activeMenu'    => 'mechanics_create',
            'garages'       => $garages,
            'suggestedCode' => $suggestedCode
        ]);
    }

    /**
     * Store Mechanic POST
     */
    public function store(): void {
        $this->checkPerm('mechanics.create');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/index.php?route=mechanics');
            return;
        }

        $csrfToken = $_POST['csrf_token'] ?? '';
        if (!Helper::validateCsrf($csrfToken)) {
            $this->setFlash('danger', 'Hitilafu ya usalama (CSRF invalid).');
            $this->redirect('/index.php?route=mechanic_create');
            return;
        }

        $name = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');

        if (empty($name) || empty($phone)) {
            $this->setFlash('danger', 'Jina la fundi na nambari ya simu vinahitajika.');
            $this->redirect('/index.php?route=mechanic_create');
            return;
        }

        try {
            $mechanicId = Mechanic::create([
                'mechanic_code'          => trim($_POST['mechanic_code'] ?? ''),
                'name'                   => $name,
                'phone'                  => $phone,
                'alternative_phone'      => trim($_POST['alternative_phone'] ?? ''),
                'area'                   => trim($_POST['area'] ?? ''),
                'garage_id'              => !empty($_POST['garage_id']) ? (int)$_POST['garage_id'] : null,
                'specialization'         => trim($_POST['specialization'] ?? ''),
                'years_of_experience'    => !empty($_POST['years_of_experience']) ? (int)$_POST['years_of_experience'] : null,
                'notes'                  => trim($_POST['notes'] ?? ''),
                'create_customer_profile'=> !empty($_POST['create_customer_profile']) ? 1 : 0,
                'created_by'             => Auth::id()
            ]);

            $this->setFlash('success', "Fundi '{$name}' amesajiliwa kikamilifu!");
            $this->redirect("/index.php?route=mechanic_show&id={$mechanicId}");

        } catch (Exception $e) {
            $this->setFlash('danger', "Hitilafu: " . $e->getMessage());
            $this->redirect('/index.php?route=mechanic_create');
        }
    }

    /**
     * Mechanic Profile Page
     */
    public function show(): void {
        $this->checkPerm('mechanics.view');

        $id = (int)($_GET['id'] ?? 0);
        $mechanic = Mechanic::find($id);

        if (!$mechanic) {
            $this->setFlash('danger', 'Fundi hakupatikana.');
            $this->redirect('/index.php?route=mechanics');
            return;
        }

        $canEdit = Auth::hasPermission('mechanics.edit') || Auth::hasRole('super_admin') || Auth::hasRole('manager');
        $canDeactivate = Auth::hasPermission('mechanics.deactivate') || Auth::hasRole('super_admin') || Auth::hasRole('manager');

        $this->render('mechanics/show', [
            'title'         => "Wasifu wa Fundi: {$mechanic['name']} ({$mechanic['mechanic_code']})",
            'activeMenu'    => 'mechanics_list',
            'mechanic'      => $mechanic,
            'canEdit'       => $canEdit,
            'canDeactivate' => $canDeactivate
        ]);
    }

    /**
     * Edit Mechanic Form
     */
    public function edit(): void {
        $this->checkPerm('mechanics.edit');

        $id = (int)($_GET['id'] ?? 0);
        $mechanic = Mechanic::find($id);

        if (!$mechanic) {
            $this->setFlash('danger', 'Fundi hakupatikana.');
            $this->redirect('/index.php?route=mechanics');
            return;
        }

        $garages = Garage::all(['status' => 'active'], 1, 100);

        $this->render('mechanics/edit', [
            'title'      => "Hariri Fundi: {$mechanic['name']}",
            'activeMenu' => 'mechanics_list',
            'mechanic'   => $mechanic,
            'garages'    => $garages
        ]);
    }

    /**
     * Update Mechanic POST
     */
    public function update(): void {
        $this->checkPerm('mechanics.edit');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/index.php?route=mechanics');
            return;
        }

        $csrfToken = $_POST['csrf_token'] ?? '';
        if (!Helper::validateCsrf($csrfToken)) {
            $this->setFlash('danger', 'Hitilafu ya usalama (CSRF invalid).');
            $this->redirect('/index.php?route=mechanics');
            return;
        }

        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');

        if (empty($name) || empty($phone)) {
            $this->setFlash('danger', 'Jina na nambari ya simu vinahitajika.');
            $this->redirect("/index.php?route=mechanic_edit&id={$id}");
            return;
        }

        try {
            Mechanic::update($id, [
                'mechanic_code'       => trim($_POST['mechanic_code'] ?? ''),
                'name'                => $name,
                'phone'               => $phone,
                'alternative_phone'   => trim($_POST['alternative_phone'] ?? ''),
                'area'                => trim($_POST['area'] ?? ''),
                'garage_id'           => !empty($_POST['garage_id']) ? (int)$_POST['garage_id'] : null,
                'specialization'      => trim($_POST['specialization'] ?? ''),
                'years_of_experience' => !empty($_POST['years_of_experience']) ? (int)$_POST['years_of_experience'] : null,
                'notes'               => trim($_POST['notes'] ?? ''),
                'status'              => trim($_POST['status'] ?? 'active')
            ]);

            $this->setFlash('success', "Taarifa za fundi '{$name}' zimesasishwa!");
            $this->redirect("/index.php?route=mechanic_show&id={$id}");

        } catch (Exception $e) {
            $this->setFlash('danger', "Hitilafu: " . $e->getMessage());
            $this->redirect("/index.php?route=mechanic_edit&id={$id}");
        }
    }

    /**
     * Toggle Mechanic Status (Active / Inactive / Blocked)
     */
    public function toggleStatus(): void {
        $this->checkPerm('mechanics.deactivate');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/index.php?route=mechanics');
            return;
        }

        $csrfToken = $_POST['csrf_token'] ?? '';
        if (!Helper::validateCsrf($csrfToken)) {
            $this->setFlash('danger', 'Hitilafu ya usalama.');
            $this->redirect('/index.php?route=mechanics');
            return;
        }

        $id = (int)($_POST['id'] ?? 0);
        $status = trim($_POST['status'] ?? 'inactive');

        try {
            Mechanic::setStatus($id, $status, Auth::id());
            $this->setFlash('success', "Hadhi ya fundi imebadilishwa kuwa '{$status}'!");
        } catch (Exception $e) {
            $this->setFlash('danger', "Hitilafu: " . $e->getMessage());
        }

        $this->redirect("/index.php?route=mechanic_show&id={$id}");
    }
}
