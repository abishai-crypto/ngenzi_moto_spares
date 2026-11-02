<?php
/**
 * NGENZI MOTO SPARE PARTS
 * Return Controller (Phase 11)
 * Handles Returns & Refunds Management, Approval Workflows,
 * Storekeeper Restocking, Financial Settlement, Exchange, Printouts & Reports.
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Helper;
use App\Models\Returns;
use App\Models\Sale;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use Exception;

require_once __DIR__ . '/../models/Returns.php';
require_once __DIR__ . '/../models/Sale.php';
require_once __DIR__ . '/../models/Customer.php';
require_once __DIR__ . '/../models/Product.php';

class ReturnController extends Controller {

    private function checkAccess(string $permission = 'returns.view'): void {
        Auth::requireAuth();
        if (!Auth::hasPermission($permission) && !Auth::hasRole(['super_admin', 'manager', 'accountant', 'cashier', 'storekeeper'])) {
            Helper::setFlash('danger', 'Huna idhini ya kufikia sehemu hii ya marejesho (Access Denied).');
            $this->redirect('/index.php?route=dashboard');
            exit;
        }
    }

    /**
     * Returns Dashboard with KPIs, status queues, and breakdown
     */
    public function dashboard(): void {
        $this->checkAccess('returns.view');

        $stats = Returns::getDashboardStats();
        $recentReturns = Returns::all([], 1, 10);
        $pendingReturns = Returns::all(['status' => 'requested'], 1, 10);

        $this->render('returns/dashboard', [
            'pageTitle'      => 'Dashibodi ya Marejesho — NGENZI MOTO SPARE PARTS',
            'activeMenu'     => 'returns',
            'stats'          => $stats,
            'recentReturns'  => $recentReturns,
            'pendingReturns' => $pendingReturns
        ]);
    }

    /**
     * Filterable, paginated directory of all returns
     */
    public function index(): void {
        $this->checkAccess('returns.view');

        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 25;

        $filters = [
            'search'      => trim($_GET['search'] ?? ''),
            'status'      => trim($_GET['status'] ?? ''),
            'return_type' => trim($_GET['return_type'] ?? ''),
            'date_from'   => trim($_GET['date_from'] ?? ''),
            'date_to'     => trim($_GET['date_to'] ?? ''),
            'customer_id' => !empty($_GET['customer_id']) ? (int)$_GET['customer_id'] : null,
            'created_by'  => !empty($_GET['created_by']) ? (int)$_GET['created_by'] : null
        ];

        $returns = Returns::all($filters, $page, $perPage);
        $totalReturns = Returns::count($filters);
        $totalPages = max(1, (int)ceil($totalReturns / $perPage));

        $staffList = User::all(null, null, 'active');
        $returnReasons = explode(',', Setting::get('return_reasons', 'CUSTOMER_RETURN,DAMAGED_ITEM,WRONG_ITEM,DEFECTIVE_ITEM,WARRANTY_RETURN,OTHER'));

        $this->render('returns/index', [
            'pageTitle'     => 'Orodha ya Marejesho — NGENZI MOTO SPARE PARTS',
            'activeMenu'    => 'returns',
            'returns'       => $returns,
            'totalReturns'  => $totalReturns,
            'totalPages'    => $totalPages,
            'currentPage'   => $page,
            'filters'       => $filters,
            'staffList'     => $staffList,
            'returnReasons' => $returnReasons
        ]);
    }

    /**
     * Create Return Form
     */
    public function create(): void {
        $this->checkAccess('returns.create');

        $saleId = !empty($_GET['sale_id']) ? (int)$_GET['sale_id'] : 0;
        $invoiceNo = trim($_GET['invoice_no'] ?? '');

        $sale = null;
        $items = [];

        if ($saleId > 0) {
            $sale = Sale::find($saleId);
            if ($sale) {
                $items = Returns::getReturnableItemsForSale($saleId);
            }
        } elseif (!empty($invoiceNo)) {
            $sale = Sale::findByInvoice($invoiceNo);
            if ($sale) {
                $saleId = (int)$sale['id'];
                $items = Returns::getReturnableItemsForSale($saleId);
            }
        }

        $returnReasons = explode(',', Setting::get('return_reasons', 'CUSTOMER_RETURN,DAMAGED_ITEM,WRONG_ITEM,DEFECTIVE_ITEM,WARRANTY_RETURN,OTHER'));
        $defaultRestockFeePercent = (float)Setting::get('default_restocking_fee_percent', '0.00');

        $this->render('returns/create', [
            'pageTitle'                => 'Kuanzisha Rejesho Jipya — NGENZI MOTO SPARE PARTS',
            'activeMenu'               => 'returns',
            'sale'                     => $sale,
            'items'                    => $items,
            'returnReasons'            => $returnReasons,
            'defaultRestockFeePercent' => $defaultRestockFeePercent,
            'invoiceQuery'             => $invoiceNo
        ]);
    }

    /**
     * Store newly created Return
     */
    public function store(): void {
        $this->checkAccess('returns.create');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/index.php?route=return_create');
            return;
        }

        if (!Helper::validateCsrf($_POST['csrf_token'] ?? '')) {
            Helper::setFlash('danger', 'Hitilafu ya kiusalama ya fomu (CSRF Token Mismatch). Jaribu tena.');
            $this->redirect('/index.php?route=return_create');
            return;
        }

        $saleId = (int)($_POST['sale_id'] ?? 0);
        $returnType = trim($_POST['return_type'] ?? 'CUSTOMER_RETURN');
        $reason = trim($_POST['reason'] ?? 'CUSTOMER_RETURN');
        $notes = trim($_POST['notes'] ?? '');
        $returnFee = max(0.00, (float)($_POST['return_fee'] ?? 0.00));
        $autoApprove = !empty($_POST['auto_approve']) && (Auth::hasRole(['super_admin', 'manager']) || Auth::hasPermission('returns.approve'));
        $isDraft = !empty($_POST['is_draft']);

        $itemsInput = $_POST['items'] ?? [];
        $itemsData = [];

        foreach ($itemsInput as $saleItemId => $it) {
            $qty = (int)($it['quantity'] ?? 0);
            if ($qty > 0) {
                $itemsData[] = [
                    'sale_item_id'     => (int)$saleItemId,
                    'quantity'         => $qty,
                    'condition_status' => $it['condition_status'] ?? 'resellable',
                    'return_reason'    => $it['return_reason'] ?? $reason,
                    'notes'            => $it['notes'] ?? null
                ];
            }
        }

        if (empty($itemsData)) {
            Helper::setFlash('danger', 'Tafadhali chagua angalau kipuri kimoja na uweke idadi ya kurejesha.');
            $this->redirect("/index.php?route=return_create&sale_id={$saleId}");
            return;
        }

        try {
            $res = Returns::createReturn([
                'sale_id'      => $saleId,
                'return_type'  => $returnType,
                'reason'       => $reason,
                'notes'        => $notes,
                'return_fee'   => $returnFee,
                'auto_approve' => $autoApprove,
                'is_draft'     => $isDraft
            ], $itemsData, Auth::id() ?? 1);

            Helper::setFlash('success', "Rejesho #{$res['return_number']} limeundwa kikamilifu!");
            $this->redirect("/index.php?route=return_show&id={$res['return_id']}");

        } catch (Exception $e) {
            Helper::setFlash('danger', "Hitilafu: " . $e->getMessage());
            $this->redirect("/index.php?route=return_create&sale_id={$saleId}");
        }
    }

    /**
     * Show Return details & workflow actions
     */
    public function show(): void {
        $this->checkAccess('returns.view');

        $id = (int)($_GET['id'] ?? 0);
        $return = Returns::find($id);

        if (!$return) {
            Helper::setFlash('danger', 'Kumbukumbu ya rejesho haikupatikana.');
            $this->redirect('/index.php?route=returns');
            return;
        }

        $items = Returns::getItems($id);
        $paymentMethods = explode(',', Setting::get('payment_methods', 'Cash,Mobile Money,Bank,Card,Credit,Customer Credit'));
        $providers = explode(',', Setting::get('mobile_money_providers', 'M-Pesa,Tigo Pesa,Airtel Money,Halopesa'));

        $canApprove = Auth::hasPermission('returns.approve') || Auth::hasRole(['super_admin', 'manager']);
        $canReceive = Auth::hasPermission('returns.receive') || Auth::hasRole(['super_admin', 'manager', 'storekeeper']);
        $canRefund = Auth::hasPermission('returns.refund') || Auth::hasRole(['super_admin', 'manager', 'accountant']);
        $canCancel = Auth::hasPermission('returns.cancel') || Auth::hasRole(['super_admin', 'manager']);
        $canViewFinancial = Auth::hasPermission('returns.view_financial') || Auth::hasRole(['super_admin', 'manager', 'accountant']);

        $this->render('returns/show', [
            'pageTitle'        => "Taarifa za Rejesho #{$return['return_number']}",
            'activeMenu'       => 'returns',
            'return'           => $return,
            'items'            => $items,
            'paymentMethods'   => $paymentMethods,
            'providers'        => $providers,
            'canApprove'       => $canApprove,
            'canReceive'       => $canReceive,
            'canRefund'        => $canRefund,
            'canCancel'        => $canCancel,
            'canViewFinancial' => $canViewFinancial
        ]);
    }

    /**
     * Approve Return Action (POST)
     */
    public function approve(): void {
        Auth::requireAuth();
        if (!Auth::hasPermission('returns.approve') && !Auth::hasRole(['super_admin', 'manager'])) {
            Helper::setFlash('danger', 'Huna idhini ya kuidhinisha marejesho.');
            $this->redirect('/index.php?route=returns');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Helper::validateCsrf($_POST['csrf_token'] ?? '')) {
            Helper::setFlash('danger', 'Ombi halikukamilika kwa usahihi.');
            $this->redirect('/index.php?route=returns');
            return;
        }

        $id = (int)($_POST['return_id'] ?? 0);

        try {
            Returns::approveReturn($id, Auth::id() ?? 1);
            Helper::setFlash('success', 'Rejesho limeidhinishwa kikamilifu.');
        } catch (Exception $e) {
            Helper::setFlash('danger', 'Hitilafu: ' . $e->getMessage());
        }

        $this->redirect("/index.php?route=return_show&id={$id}");
    }

    /**
     * Receive & Restock Action (POST)
     */
    public function receive(): void {
        Auth::requireAuth();
        if (!Auth::hasPermission('returns.receive') && !Auth::hasRole(['super_admin', 'manager', 'storekeeper'])) {
            Helper::setFlash('danger', 'Huna idhini ya kupokea na kurejesha bidhaa stoo.');
            $this->redirect('/index.php?route=returns');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Helper::validateCsrf($_POST['csrf_token'] ?? '')) {
            Helper::setFlash('danger', 'Ombi halikukamilika kwa usahihi.');
            $this->redirect('/index.php?route=returns');
            return;
        }

        $id = (int)($_POST['return_id'] ?? 0);
        $notes = trim($_POST['receive_notes'] ?? '');

        try {
            $res = Returns::receiveAndRestock($id, Auth::id() ?? 1, $notes);
            Helper::setFlash('success', "Mzigo umepokelewa. Vipuri vinavyouzika ({$res['restocked_lines']}) vimeingizwa stoo, vilivyoharibika ({$res['damaged_lines']}) havijaingizwa stoo.");
        } catch (Exception $e) {
            Helper::setFlash('danger', 'Hitilafu: ' . $e->getMessage());
        }

        $this->redirect("/index.php?route=return_show&id={$id}");
    }

    /**
     * Process Refund Action (POST)
     */
    public function refund(): void {
        Auth::requireAuth();
        if (!Auth::hasPermission('returns.refund') && !Auth::hasRole(['super_admin', 'manager', 'accountant'])) {
            Helper::setFlash('danger', 'Huna idhini ya kutoa fedha au marejesho ya kifedha.');
            $this->redirect('/index.php?route=returns');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Helper::validateCsrf($_POST['csrf_token'] ?? '')) {
            Helper::setFlash('danger', 'Ombi halikukamilika kwa usahihi.');
            $this->redirect('/index.php?route=returns');
            return;
        }

        $id = (int)($_POST['return_id'] ?? 0);
        $refundMethod = trim($_POST['refund_method'] ?? 'Cash');
        $provider = trim($_POST['provider'] ?? '');

        try {
            $res = Returns::processRefund($id, [
                'refund_method' => $refundMethod,
                'provider'      => $provider
            ], Auth::id() ?? 1);

            Helper::setFlash('success', "Marejesho ya fedha yamekamilika! Risiti #{$res['refund_reference_no']}.");
        } catch (Exception $e) {
            Helper::setFlash('danger', 'Hitilafu: ' . $e->getMessage());
        }

        $this->redirect("/index.php?route=return_show&id={$id}");
    }

    /**
     * Cancel Return Action (POST)
     */
    public function cancel(): void {
        Auth::requireAuth();
        if (!Auth::hasPermission('returns.cancel') && !Auth::hasRole(['super_admin', 'manager'])) {
            Helper::setFlash('danger', 'Huna idhini ya kughairi marejesho.');
            $this->redirect('/index.php?route=returns');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Helper::validateCsrf($_POST['csrf_token'] ?? '')) {
            Helper::setFlash('danger', 'Ombi halikukamilika kwa usahihi.');
            $this->redirect('/index.php?route=returns');
            return;
        }

        $id = (int)($_POST['return_id'] ?? 0);
        $reason = trim($_POST['cancel_reason'] ?? 'Imeghairiwa na mtumiaji');

        try {
            Returns::cancelReturn($id, $reason, Auth::id() ?? 1);
            Helper::setFlash('warning', 'Rejesho limeghairiwa na mabadiliko ya stoo/fedha yamerudishwa.');
        } catch (Exception $e) {
            Helper::setFlash('danger', 'Hitilafu: ' . $e->getMessage());
        }

        $this->redirect("/index.php?route=return_show&id={$id}");
    }

    /**
     * Reject Return Action (POST)
     */
    public function reject(): void {
        Auth::requireAuth();
        if (!Auth::hasPermission('returns.approve') && !Auth::hasRole(['super_admin', 'manager'])) {
            Helper::setFlash('danger', 'Huna idhini ya kukataa marejesho.');
            $this->redirect('/index.php?route=returns');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Helper::validateCsrf($_POST['csrf_token'] ?? '')) {
            Helper::setFlash('danger', 'Ombi halikukamilika kwa usahihi.');
            $this->redirect('/index.php?route=returns');
            return;
        }

        $id = (int)($_POST['return_id'] ?? 0);
        $reason = trim($_POST['reject_reason'] ?? 'Imekataliwa na Meneja');

        try {
            Returns::rejectReturn($id, $reason, Auth::id() ?? 1);
            Helper::setFlash('warning', 'Rejesho limekataliwa.');
        } catch (Exception $e) {
            Helper::setFlash('danger', 'Hitilafu: ' . $e->getMessage());
        }

        $this->redirect("/index.php?route=return_show&id={$id}");
    }

    /**
     * Printable Thermal Receipt (80mm)
     */
    public function receipt(): void {
        $this->checkAccess('returns.view');

        $id = (int)($_GET['id'] ?? 0);
        $return = Returns::find($id);

        if (!$return) {
            die("Kumbukumbu ya rejesho haikupatikana.");
        }

        $items = Returns::getItems($id);

        $this->render('returns/receipt', [
            'pageTitle' => "Risiti ya Marejesho #{$return['return_number']}",
            'return'    => $return,
            'items'     => $items
        ], 'blank');
    }

    /**
     * Printable A4 Return / Credit Note
     */
    public function creditNote(): void {
        $this->checkAccess('returns.view');

        $id = (int)($_GET['id'] ?? 0);
        $return = Returns::find($id);

        if (!$return) {
            die("Kumbukumbu ya rejesho haikupatikana.");
        }

        $items = Returns::getItems($id);

        $this->render('returns/credit_note', [
            'pageTitle' => "Hati ya Marejesho #{$return['return_number']}",
            'return'    => $return,
            'items'     => $items
        ], 'blank');
    }

    /**
     * Return Reports & Return Rate Analytics
     */
    public function reports(): void {
        $this->checkAccess('returns.report');

        $groupBy = $_GET['group_by'] ?? 'product';
        $filters = [
            'group_by'  => $groupBy,
            'date_from' => trim($_GET['date_from'] ?? ''),
            'date_to'   => trim($_GET['date_to'] ?? '')
        ];

        $reportData = Returns::getReturnReports($filters);

        $this->render('returns/reports', [
            'pageTitle'   => 'Ripoti na Takwimu za Marejesho — NGENZI MOTO SPARE PARTS',
            'activeMenu'  => 'returns_reports',
            'reportData'  => $reportData,
            'groupBy'     => $groupBy,
            'filters'     => $filters
        ]);
    }

    /**
     * AJAX Search for Sale / Invoice lookup
     */
    public function ajaxSaleLookup(): void {
        header('Content-Type: application/json');
        Auth::requireAuth();

        $query = trim($_GET['query'] ?? '');
        if (empty($query)) {
            echo json_encode(['success' => false, 'message' => 'Query is empty.']);
            exit;
        }

        $sale = Sale::findByInvoice($query);
        if (!$sale) {
            // Also try by ID or partial match
            $pdo = \App\Core\Database::getConnection();
            $stmt = $pdo->prepare("SELECT id, invoice_no, sale_date, customer_name, total_amount FROM `sales` WHERE invoice_no LIKE ? ORDER BY id DESC LIMIT 5");
            $stmt->execute(["%{$query}%"]);
            $matches = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            if (empty($matches)) {
                echo json_encode(['success' => false, 'message' => 'Ankara haikupatikana.']);
                exit;
            }

            echo json_encode(['success' => true, 'multiple' => true, 'matches' => $matches]);
            exit;
        }

        $items = Returns::getReturnableItemsForSale((int)$sale['id']);

        echo json_encode([
            'success' => true,
            'sale'    => $sale,
            'items'   => $items
        ]);
        exit;
    }
}
