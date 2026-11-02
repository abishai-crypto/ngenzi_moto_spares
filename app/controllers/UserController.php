<?php
/**
 * NGENZI MOTO SPARE PARTS
 * UserController (Phase 3)
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Helper;
use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use Exception;

class UserController extends Controller {
    /**
     * User Management Listing
     */
    public function index(): void {
        Auth::requirePermission('manage_users');

        $search = trim($_GET['search'] ?? '');
        $role = trim($_GET['role'] ?? '');
        $status = trim($_GET['status'] ?? '');

        $users = User::all($search ?: null, $role ?: null, $status ?: null);
        $roles = User::roles();

        $this->render('users/index', [
            'pageTitle' => 'Usimamizi wa Wafanyakazi — NGENZI MOTO SPARE PARTS',
            'users'     => $users,
            'roles'     => $roles,
            'search'    => $search,
            'role'      => $role,
            'status'    => $status,
            'csrfToken' => Helper::csrfToken(),
            'flash'     => Helper::getFlash(),
        ]);
    }

    /**
     * Display create user form
     */
    public function create(): void {
        Auth::requirePermission('manage_users');

        $this->render('users/create', [
            'pageTitle' => 'Sajili Mfanyakazi Mpya — NGENZI MOTO SPARE PARTS',
            'roles'     => User::roles(),
            'csrfToken' => Helper::csrfToken(),
            'flash'     => Helper::getFlash(),
        ]);
    }

    /**
     * Store new user
     */
    public function store(): void {
        Auth::requirePermission('manage_users');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('users');
        }

        if (!Helper::validateCsrf($_POST['csrf_token'] ?? '')) {
            $this->redirect('user_create', 'danger', 'Muda wa fomu umekwisha (Invalid CSRF). Jaribu tena.');
        }

        $fullName = trim($_POST['full_name'] ?? '');
        $username = strtolower(trim($_POST['username'] ?? ''));
        $email    = trim($_POST['email'] ?? '');
        $phone    = trim($_POST['phone'] ?? '');
        $roleId   = (int)($_POST['role_id'] ?? 0);
        $password = $_POST['password'] ?? '';
        $confirm  = $_POST['confirm_password'] ?? '';
        $status   = $_POST['status'] ?? 'active';

        if (empty($fullName) || empty($username) || empty($roleId) || empty($password)) {
            $this->redirect('user_create', 'warning', 'Tafadhali jaza taarifa zote za lazima (Jina, Username, Jukumu, Nenosiri).');
        }

        if ($password !== $confirm) {
            $this->redirect('user_create', 'danger', 'Nenosiri na uthibitisho wa nenosiri haviendani!');
        }

        try {
            User::create([
                'full_name' => $fullName,
                'username'  => $username,
                'email'     => $email,
                'phone'     => $phone,
                'role_id'   => $roleId,
                'password'  => $password,
                'status'    => $status,
            ]);

            $this->redirect('users', 'success', "Mtumiaji {$username} amesajiliwa kikamilifu!");
        } catch (Exception $e) {
            $this->redirect('user_create', 'danger', $e->getMessage());
        }
    }

    /**
     * Display edit user form
     */
    public function edit(): void {
        Auth::requirePermission('manage_users');

        $id = (int)($_GET['id'] ?? 0);
        $user = User::find($id);

        if (!$user) {
            $this->redirect('users', 'danger', 'Mtumiaji hakupatikana.');
        }

        $this->render('users/edit', [
            'pageTitle' => 'Hariri Mfanyakazi — NGENZI MOTO SPARE PARTS',
            'user'      => $user,
            'roles'     => User::roles(),
            'csrfToken' => Helper::csrfToken(),
            'flash'     => Helper::getFlash(),
        ]);
    }

    /**
     * Update user details
     */
    public function update(): void {
        Auth::requirePermission('manage_users');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('users');
        }

        if (!Helper::validateCsrf($_POST['csrf_token'] ?? '')) {
            $this->redirect('users', 'danger', 'Muda wa fomu umekwisha (Invalid CSRF).');
        }

        $id = (int)($_POST['id'] ?? 0);
        $fullName = trim($_POST['full_name'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $phone    = trim($_POST['phone'] ?? '');
        $roleId   = (int)($_POST['role_id'] ?? 0);
        $status   = $_POST['status'] ?? 'active';

        try {
            User::update($id, [
                'full_name' => $fullName,
                'email'     => $email,
                'phone'     => $phone,
                'role_id'   => $roleId,
                'status'    => $status,
            ]);

            $this->redirect('users', 'success', "Taarifa za mtumiaji zimesasishwa kikamilifu.");
        } catch (Exception $e) {
            $this->redirect("user_edit&id={$id}", 'danger', $e->getMessage());
        }
    }

    /**
     * Toggle user status (Activate / Deactivate)
     */
    public function toggleStatus(): void {
        Auth::requirePermission('manage_users');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('users');
        }

        if (!Helper::validateCsrf($_POST['csrf_token'] ?? '')) {
            $this->redirect('users', 'danger', 'Muda wa fomu umekwisha (Invalid CSRF).');
        }

        $id = (int)($_POST['id'] ?? 0);

        try {
            $newStatus = User::toggleStatus($id);
            $msg = ($newStatus === 'active') ? 'Akaunti imewashwa kikamilifu.' : 'Akaunti imezimwa kikamilifu.';
            $this->redirect('users', 'info', $msg);
        } catch (Exception $e) {
            $this->redirect('users', 'danger', $e->getMessage());
        }
    }

    /**
     * Admin resets user's password
     */
    public function resetPassword(): void {
        Auth::requirePermission('manage_users');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('users');
        }

        if (!Helper::validateCsrf($_POST['csrf_token'] ?? '')) {
            $this->redirect('users', 'danger', 'Muda wa fomu umekwisha (Invalid CSRF).');
        }

        $id = (int)($_POST['id'] ?? 0);
        $newPass = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_new_password'] ?? '';

        if (empty($newPass) || strlen($newPass) < 6) {
            $this->redirect('users', 'warning', 'Nenosiri lazima liwe na herufi zisizopungua 6.');
        }

        if ($newPass !== $confirm) {
            $this->redirect('users', 'danger', 'Nenosiri jipya na uthibitisho haviendani!');
        }

        try {
            User::resetPassword($id, $newPass);
            $this->redirect('users', 'success', "Nenosiri jipya limewekwa kikamilifu!");
        } catch (Exception $e) {
            $this->redirect('users', 'danger', $e->getMessage());
        }
    }

    /**
     * Logged-in user change password view
     */
    public function changePasswordForm(): void {
        Auth::requireAuth();

        $this->render('users/change_password', [
            'pageTitle' => 'Badili Nenosiri Lako — NGENZI MOTO SPARE PARTS',
            'user'      => Auth::user(),
            'csrfToken' => Helper::csrfToken(),
            'flash'     => Helper::getFlash(),
        ]);
    }

    /**
     * Process user password change
     */
    public function changePasswordPost(): void {
        Auth::requireAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('change_password');
        }

        if (!Helper::validateCsrf($_POST['csrf_token'] ?? '')) {
            $this->redirect('change_password', 'danger', 'Muda wa fomu umekwisha (Invalid CSRF).');
        }

        $current = $_POST['current_password'] ?? '';
        $newPass = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if ($newPass !== $confirm) {
            $this->redirect('change_password', 'danger', 'Nenosiri jipya na uthibitisho haviendani!');
        }

        try {
            User::changePassword(Auth::id(), $current, $newPass);
            $this->redirect('change_password', 'success', 'Nenosiri lako limebadilishwa kikamilifu!');
        } catch (Exception $e) {
            $this->redirect('change_password', 'danger', $e->getMessage());
        }
    }

    /**
     * Role & Permission Matrix View (Owner only)
     */
    public function rolesAndPermissions(): void {
        Auth::requireRole('super_admin');

        $roles = Role::all();
        $permissionsByModule = Permission::groupedByModule();

        // Load permissions map for each role
        $rolePermissionsMap = [];
        foreach ($roles as $r) {
            $rolePermissionsMap[$r['id']] = Role::getPermissionIds($r['id']);
        }

        $this->render('users/roles', [
            'pageTitle'            => 'Haki na Majukumu (RBAC Matrix) — NGENZI MOTO SPARE PARTS',
            'roles'                => $roles,
            'permissionsByModule'  => $permissionsByModule,
            'rolePermissionsMap'   => $rolePermissionsMap,
            'csrfToken'            => Helper::csrfToken(),
            'flash'                => Helper::getFlash(),
        ]);
    }

    /**
     * Update permissions for a role
     */
    public function updateRolePermissions(): void {
        Auth::requireRole('super_admin');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('roles_permissions');
        }

        if (!Helper::validateCsrf($_POST['csrf_token'] ?? '')) {
            $this->redirect('roles_permissions', 'danger', 'Muda wa fomu umekwisha (Invalid CSRF).');
        }

        $roleId = (int)($_POST['role_id'] ?? 0);
        $perms = $_POST['permissions'] ?? [];

        // Safeguard: Cannot remove all permissions from super_admin
        $role = Role::find($roleId);
        if ($role && $role['name'] === 'super_admin') {
            $this->redirect('roles_permissions', 'warning', 'Haki za Mmiliki (Super Admin) haziwezi kuondolewa!');
        }

        try {
            Role::syncPermissions($roleId, $perms);
            $this->redirect('roles_permissions', 'success', "Haki za jukumu '{$role['display_name']}' zimesasishwa kikamilifu!");
        } catch (Exception $e) {
            $this->redirect('roles_permissions', 'danger', $e->getMessage());
        }
    }
}
