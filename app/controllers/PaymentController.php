<?php
/**
 * NGENZI MOTO SPARE PARTS
 * Payment Controller (Phase 9)
 * Handles Payment History, Single Payments, Controlled Reversals & Daily Collections
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Helper;
use App\Models\Payment;
use App\Models\Sale;
use App\Models\Customer;
use App\Models\Setting;
use App\Models\User;
use Exception;

class PaymentController extends Controller {

    private function checkAccess(string $permission = 'payments.view'): void {
        Auth::requireAuth();
        if (!Auth::hasPermission($permission) && !Auth::hasRole(['super_admin', 'manager', 'accountant'])) {
            Helper::setFlash('danger', 'Huna idhini ya kufikia sehemu hii ya malipo (Access Denied).');
            $this->redirect('/index.php?route=dashboard');
            exit;
        }
    }

    /**
     * Payments History Directory with Filters & Pagination
     */
    public function index(): void {
        $this->checkAccess('payments.view');

        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 25;

        $filters = [
            'search'         => trim($_GET['search'] ?? ''),
            'date_from'      => trim($_GET['date_from'] ?? ''),
            'date_to'        => trim($_GET['date_to'] ?? ''),
            'payment_method' => trim($_GET['payment_method'] ?? ''),
            'provider'       => trim($_GET['provider'] ?? ''),
            'status'         => trim($_GET['status'] ?? ''),
            'received_by'    => !empty($_GET['received_by']) ? (int)$_GET['received_by'] : null,
            'customer_id'    => !empty($_GET['customer_id']) ? (int)$_GET['customer_id'] : null
        ];

        $payments = Payment::all($filters, $page, $perPage);
        $totalPayments = Payment::count($filters);
        $totalPages = max(1, (int)ceil($totalPayments / $perPage));

        $cashiers = User::all(null, null, 'active');
        $paymentMethods = explode(',', Setting::get('payment_methods', 'Cash,Mobile Money,Bank,Card,Credit'));
        $providers = explode(',', Setting::get('mobile_money_providers', 'M-Pesa,Tigo Pesa,Airtel Money,Halopesa'));
        $canReverse = Auth::hasPermission('payments.reverse') || Auth::hasRole(['super_admin', 'manager']);

        $this->render('payments/index', [
            'pageTitle'      => 'Orodha ya Malipo — NGENZI MOTO SPARE PARTS',
            'activeMenu'     => 'payments',
            'payments'       => $payments,
            'totalPayments'  => $totalPayments,
            'totalPages'     => $totalPages,
            'currentPage'    => $page,
            'filters'        => $filters,
            'cashiers'       => $cashiers,
            'paymentMethods' => $paymentMethods,
            'providers'      => $providers,
            'canReverse'     => $canReverse
        ]);
    }

    /**
     * Show single payment details
     */
    public function show(): void {
        $this->checkAccess('payments.view');

        $id = (int)($_GET['id'] ?? 0);
        $payment = Payment::find($id);

        if (!$payment) {
            Helper::setFlash('danger', 'Kumbukumbu ya malipo haikupatikana.');
            $this->redirect('/index.php?route=payments');
            return;
        }

        $canReverse = Auth::hasPermission('payments.reverse') || Auth::hasRole(['super_admin', 'manager']);

        $this->render('payments/show', [
            'pageTitle'  => "Taarifa za Malipo #{$payment['payment_no']}",
            'activeMenu' => 'payments',
            'payment'    => $payment,
            'canReverse' => $canReverse
        ]);
    }

    /**
     * Printable Payment Receipt (Thermal 58mm/80mm and A4)
     */
    public function receipt(): void {
        $this->checkAccess('payments.print');

        $id = (int)($_GET['id'] ?? 0);
        $payment = Payment::find($id);

        if (!$payment) {
            Helper::setFlash('danger', 'Risiti ya malipo haikupatikana.');
            $this->redirect('/index.php?route=payments');
            return;
        }

        $settings = [
            'business_name'    => Setting::get('business_name', 'NGENZI MOTO SPARE PARTS'),
            'business_tagline' => Setting::get('business_tagline', 'Dar es Salaam Motorcycle & Bajaj Spare Parts Specialist'),
            'business_phone'   => Setting::get('business_phone', '+255 754 123 456 / +255 715 000 111'),
            'business_email'   => Setting::get('business_email', 'info@motosparertz.com'),
            'business_address' => Setting::get('business_address', 'Mtaa wa Msimbazi / Kariakoo, Dar es Salaam, Tanzania'),
            'receipt_footer'   => Setting::get('receipt_footer', 'Asante sana kwa kufanya biashara nasi!')
        ];

        $this->render('payments/receipt', [
            'pageTitle' => "Risiti ya Malipo #{$payment['payment_no']}",
            'payment'   => $payment,
            'settings'  => $settings
        ], 'blank');
    }

    /**
     * Record payment against a specific sale invoice
     */
    public function store(): void {
        $this->checkAccess('payments.create');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/index.php?route=payments');
            return;
        }

        $csrfToken = $_POST['csrf_token'] ?? '';
        if (!Helper::validateCsrf($csrfToken)) {
            Helper::setFlash('danger', 'Hitilafu ya usalama (CSRF invalid).');
            $this->redirect('/index.php?route=payments');
            return;
        }

        $saleId = (int)($_POST['sale_id'] ?? 0);
        $amount = (float)($_POST['amount'] ?? 0.00);
        $paymentMethod = trim($_POST['payment_method'] ?? 'Cash');
        $provider = !empty($_POST['provider']) ? trim($_POST['provider']) : null;
        $referenceNo = !empty($_POST['reference_no']) ? trim($_POST['reference_no']) : null;
        $notes = !empty($_POST['notes']) ? trim($_POST['notes']) : null;

        try {
            $result = Payment::recordSalePayment($saleId, [
                'amount'         => $amount,
                'payment_method' => $paymentMethod,
                'provider'       => $provider,
                'reference_no'   => $referenceNo,
                'notes'          => $notes
            ], Auth::id());

            Helper::setFlash('success', "Malipo ya TSh " . number_format($amount, 2) . " yamepokelewa kikamilifu! Risiti #{$result['payment_no']}");
            $this->redirect("/index.php?route=payment_receipt&id={$result['payment_id']}");

        } catch (Exception $e) {
            Helper::setFlash('danger', "Hitilafu: " . $e->getMessage());
            $this->redirect("/index.php?route=sale_show&id={$saleId}");
        }
    }

    /**
     * Reverse a completed payment (Controlled Workflow)
     */
    public function reverse(): void {
        Auth::requireAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/index.php?route=payments');
            return;
        }

        if (!Auth::hasPermission('payments.reverse') && !Auth::hasRole(['super_admin', 'manager'])) {
            http_response_code(403);
            Helper::setFlash('danger', 'Huna idhini ya kughairi malipo (Action Restricted: payments.reverse).');
            $this->redirect('/index.php?route=payments');
            return;
        }

        $csrfToken = $_POST['csrf_token'] ?? '';
        if (!Helper::validateCsrf($csrfToken)) {
            Helper::setFlash('danger', 'Hitilafu ya usalama (CSRF invalid).');
            $this->redirect('/index.php?route=payments');
            return;
        }

        $paymentId = (int)($_POST['payment_id'] ?? 0);
        $reason = trim($_POST['reversal_reason'] ?? '');

        try {
            $result = Payment::reversePayment($paymentId, $reason, Auth::id());
            Helper::setFlash('success', "Malipo #{$result['payment_no']} ya TSh " . number_format($result['amount'], 2) . " yameghairiwa kikamilifu.");
            $this->redirect('/index.php?route=payments');

        } catch (Exception $e) {
            Helper::setFlash('danger', "Hitilafu: " . $e->getMessage());
            $this->redirect('/index.php?route=payments');
        }
    }

    /**
     * Daily collection report by payment method and cashier
     */
    public function dailyCollection(): void {
        $this->checkAccess('payments.view');

        $date = trim($_GET['date'] ?? date('Y-m-d'));
        $cashierId = !empty($_GET['cashier_id']) ? (int)$_GET['cashier_id'] : null;

        $dailyStats = Payment::getDailyCollection($date, $cashierId);
        $cashierSummary = Payment::getCashierSummary($date);
        $cashiers = User::all(null, null, 'active');

        $this->render('payments/daily_collection', [
            'pageTitle'      => "Ripoti ya Makusanyo ya Siku — {$date}",
            'activeMenu'     => 'payments_daily_collection',
            'date'           => $date,
            'cashierId'      => $cashierId,
            'dailyStats'     => $dailyStats,
            'cashierSummary' => $cashierSummary,
            'cashiers'       => $cashiers
        ]);
    }
}
