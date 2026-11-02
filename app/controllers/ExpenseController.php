<?php
/**
 * NGENZI MOTO SPARE PARTS
 * Expense Controller (Phase 10)
 * Handles Expense Management, Categorization, Approvals, Controlled Cancellations,
 * Swahili Localization & Audit Trail.
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Helper;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;
use Exception;

require_once __DIR__ . '/../models/Expense.php';
require_once __DIR__ . '/../models/ExpenseCategory.php';
require_once __DIR__ . '/../models/User.php';

class ExpenseController extends Controller {

    /**
     * Check permission helper
     */
    private function checkAccess(string $permission = 'expenses.view'): void {
        Auth::requireAuth();
        if (!Auth::hasPermission($permission) && !Auth::hasRole(['super_admin', 'manager', 'accountant'])) {
            Helper::setFlash('danger', 'Huna idhini ya kufikia sehemu hii ya matumizi (Access Denied).');
            $this->redirect('/index.php?route=dashboard');
            exit;
        }
    }

    /**
     * Expense History & Directory
     */
    public function index(): void {
        $this->checkAccess('expenses.view');

        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $filters = [
            'search'         => trim($_GET['search'] ?? ''),
            'category_id'    => trim($_GET['category_id'] ?? ''),
            'payment_method' => trim($_GET['payment_method'] ?? ''),
            'status'         => trim($_GET['status'] ?? ''),
            'recorded_by'    => trim($_GET['recorded_by'] ?? ''),
            'date_from'      => trim($_GET['date_from'] ?? ''),
            'date_to'        => trim($_GET['date_to'] ?? '')
        ];

        $expensesData = Expense::all($filters, $page, 20);
        $categories = ExpenseCategory::getActiveCategories();
        $users = User::all();

        $this->render('expenses/index', [
            'pageTitle'    => 'Orodha ya Matumizi — NGENZI MOTO SPARE PARTS',
            'activeMenu'   => 'expenses',
            'expenses'     => $expensesData['data'],
            'total'        => $expensesData['total'],
            'totalAmount'  => $expensesData['total_amount'],
            'page'         => $expensesData['page'],
            'totalPages'   => $expensesData['total_pages'],
            'categories'   => $categories,
            'users'        => $users,
            'filters'      => $filters
        ]);
    }

    /**
     * Show Add Expense form
     */
    public function create(): void {
        $this->checkAccess('expenses.create');

        $categories = ExpenseCategory::getActiveCategories();
        $nextExpenseNo = Expense::generateExpenseNo();

        $this->render('expenses/create', [
            'pageTitle'     => 'Rekodi Matumizi Mapya — NGENZI MOTO SPARE PARTS',
            'activeMenu'    => 'expenses_create',
            'categories'    => $categories,
            'nextExpenseNo' => $nextExpenseNo
        ]);
    }

    /**
     * Store Expense POST handler
     */
    public function store(): void {
        $this->checkAccess('expenses.create');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/index.php?route=expenses/create');
            return;
        }

        if (!Helper::verifyCSRF($_POST['csrf_token'] ?? '')) {
            Helper::setFlash('danger', 'Hitilafu ya kiusalama (Invalid CSRF Token). Tafadhali jaribu tena.');
            $this->redirect('/index.php?route=expenses/create');
            return;
        }

        try {
            $userId = (int)Auth::id();
            $data = [
                'category_id'    => (int)($_POST['category_id'] ?? 0),
                'amount'         => (float)($_POST['amount'] ?? 0),
                'expense_date'   => trim($_POST['expense_date'] ?? date('Y-m-d')),
                'payment_method' => trim($_POST['payment_method'] ?? 'Cash'),
                'provider'       => trim($_POST['provider'] ?? ''),
                'reference_no'   => trim($_POST['reference_no'] ?? ''),
                'payee'          => trim($_POST['payee'] ?? ''),
                'description'    => trim($_POST['description'] ?? ''),
                'notes'          => trim($_POST['notes'] ?? ''),
                'status'         => (Auth::hasPermission('expenses.approve') || Auth::hasRole(['super_admin', 'manager', 'accountant'])) ? 'approved' : 'recorded'
            ];

            $result = Expense::create($data, $userId);

            Helper::setFlash('success', $result['message']);
            $this->redirect('/index.php?route=expenses/show&id=' . $result['id']);

        } catch (Exception $e) {
            Helper::setFlash('danger', $e->getMessage());
            $this->redirect('/index.php?route=expenses/create');
        }
    }

    /**
     * Show detailed Expense Voucher & Audit
     */
    public function show(): void {
        $this->checkAccess('expenses.view');

        $id = (int)($_GET['id'] ?? 0);
        $expense = Expense::find($id);

        if (!$expense) {
            Helper::setFlash('danger', 'Matumizi hayakupatikana.');
            $this->redirect('/index.php?route=expenses');
            return;
        }

        $this->render('expenses/show', [
            'pageTitle'  => "Vocha ya Matumizi {$expense['expense_no']} — NGENZI MOTO SPARE PARTS",
            'activeMenu' => 'expenses',
            'expense'    => $expense
        ]);
    }

    /**
     * Approve Expense POST handler
     */
    public function approve(): void {
        $this->checkAccess('expenses.approve');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/index.php?route=expenses');
            return;
        }

        if (!Helper::verifyCSRF($_POST['csrf_token'] ?? '')) {
            Helper::setFlash('danger', 'Hitilafu ya kiusalama (Invalid CSRF Token).');
            $this->redirect('/index.php?route=expenses');
            return;
        }

        $id = (int)($_POST['id'] ?? 0);
        $notes = trim($_POST['notes'] ?? '');

        try {
            $result = Expense::approve($id, (int)Auth::id(), $notes);
            Helper::setFlash('success', $result['message']);
        } catch (Exception $e) {
            Helper::setFlash('danger', $e->getMessage());
        }

        $this->redirect('/index.php?route=expenses/show&id=' . $id);
    }

    /**
     * Reject Expense POST handler
     */
    public function reject(): void {
        $this->checkAccess('expenses.approve');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/index.php?route=expenses');
            return;
        }

        if (!Helper::verifyCSRF($_POST['csrf_token'] ?? '')) {
            Helper::setFlash('danger', 'Hitilafu ya kiusalama.');
            $this->redirect('/index.php?route=expenses');
            return;
        }

        $id = (int)($_POST['id'] ?? 0);
        $reason = trim($_POST['reason'] ?? '');

        try {
            $result = Expense::reject($id, (int)Auth::id(), $reason);
            Helper::setFlash('warning', $result['message']);
        } catch (Exception $e) {
            Helper::setFlash('danger', $e->getMessage());
        }

        $this->redirect('/index.php?route=expenses/show&id=' . $id);
    }

    /**
     * Cancel Expense POST handler (Controlled cancellation)
     */
    public function cancel(): void {
        $this->checkAccess('expenses.cancel');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/index.php?route=expenses');
            return;
        }

        if (!Helper::verifyCSRF($_POST['csrf_token'] ?? '')) {
            Helper::setFlash('danger', 'Hitilafu ya kiusalama.');
            $this->redirect('/index.php?route=expenses');
            return;
        }

        $id = (int)($_POST['id'] ?? 0);
        $reason = trim($_POST['reason'] ?? '');

        try {
            $result = Expense::cancel($id, (int)Auth::id(), $reason);
            Helper::setFlash('success', $result['message']);
        } catch (Exception $e) {
            Helper::setFlash('danger', $e->getMessage());
        }

        $this->redirect('/index.php?route=expenses/show&id=' . $id);
    }

    /**
     * Expense Categories Management
     */
    public function categories(): void {
        $this->checkAccess('expenses.view');

        $search = trim($_GET['search'] ?? '');
        $status = trim($_GET['status'] ?? '');
        $categories = ExpenseCategory::all($search, $status);

        $this->render('expenses/categories', [
            'pageTitle'   => 'Kategoria za Matumizi — NGENZI MOTO SPARE PARTS',
            'activeMenu'  => 'expense_categories',
            'categories'  => $categories,
            'search'      => $search,
            'status'      => $status
        ]);
    }

    /**
     * Store new Category POST handler
     */
    public function storeCategory(): void {
        $this->checkAccess('expenses.create');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/index.php?route=expenses/categories');
            return;
        }

        if (!Helper::verifyCSRF($_POST['csrf_token'] ?? '')) {
            Helper::setFlash('danger', 'Hitilafu ya kiusalama.');
            $this->redirect('/index.php?route=expenses/categories');
            return;
        }

        try {
            $data = [
                'name'        => trim($_POST['name'] ?? ''),
                'code'        => trim($_POST['code'] ?? ''),
                'description' => trim($_POST['description'] ?? ''),
                'status'      => 'active'
            ];

            $result = ExpenseCategory::create($data, (int)Auth::id());
            Helper::setFlash('success', $result['message']);
        } catch (Exception $e) {
            Helper::setFlash('danger', $e->getMessage());
        }

        $this->redirect('/index.php?route=expenses/categories');
    }

    /**
     * Update Category POST handler
     */
    public function updateCategory(): void {
        $this->checkAccess('expenses.edit');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/index.php?route=expenses/categories');
            return;
        }

        if (!Helper::verifyCSRF($_POST['csrf_token'] ?? '')) {
            Helper::setFlash('danger', 'Hitilafu ya kiusalama.');
            $this->redirect('/index.php?route=expenses/categories');
            return;
        }

        $id = (int)($_POST['id'] ?? 0);

        try {
            $data = [
                'name'        => trim($_POST['name'] ?? ''),
                'code'        => trim($_POST['code'] ?? ''),
                'description' => trim($_POST['description'] ?? ''),
                'status'      => trim($_POST['status'] ?? 'active')
            ];

            $result = ExpenseCategory::update($id, $data, (int)Auth::id());
            Helper::setFlash('success', $result['message']);
        } catch (Exception $e) {
            Helper::setFlash('danger', $e->getMessage());
        }

        $this->redirect('/index.php?route=expenses/categories');
    }

    /**
     * Toggle Category Status POST handler
     */
    public function toggleCategoryStatus(): void {
        $this->checkAccess('expenses.edit');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/index.php?route=expenses/categories');
            return;
        }

        if (!Helper::verifyCSRF($_POST['csrf_token'] ?? '')) {
            Helper::setFlash('danger', 'Hitilafu ya kiusalama.');
            $this->redirect('/index.php?route=expenses/categories');
            return;
        }

        $id = (int)($_POST['id'] ?? 0);

        try {
            $result = ExpenseCategory::toggleStatus($id, (int)Auth::id());
            Helper::setFlash('success', $result['message']);
        } catch (Exception $e) {
            Helper::setFlash('danger', $e->getMessage());
        }

        $this->redirect('/index.php?route=expenses/categories');
    }
}
