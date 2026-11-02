<?php
/**
 * NGENZI MOTO SPARE PARTS
 * Debt Controller (Phase 9)
 * Handles Outstanding Debt Dashboard, Debt Aging Reports, Debt Collection & Customer Statements
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Helper;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Sale;
use App\Models\Setting;
use App\Models\User;
use Exception;

class DebtController extends Controller {

    private function checkAccess(string $permission = 'debt.view'): void {
        Auth::requireAuth();
        if (!Auth::hasPermission($permission) && !Auth::hasRole(['super_admin', 'manager', 'accountant'])) {
            Helper::setFlash('danger', 'Huna idhini ya kufikia moduli ya madeni (Access Denied).');
            $this->redirect('/index.php?route=dashboard');
            exit;
        }
    }

    /**
     * Outstanding Debt Dashboard & Debtors Directory
     */
    public function index(): void {
        $this->checkAccess('debt.view');

        $search = trim($_GET['search'] ?? '');
        $status = trim($_GET['status'] ?? '');

        // Fetch overall stats
        $stats = Payment::getDashboardStats();

        // Fetch debtors with aging summary
        $agingRows = Customer::getDebtAging();

        if ($search !== '') {
            $term = mb_strtolower($search);
            $agingRows = array_filter($agingRows, function($r) use ($term) {
                return (str_contains(mb_strtolower($r['customer_name'] ?? ''), $term) ||
                        str_contains(mb_strtolower($r['customer_code'] ?? ''), $term) ||
                        str_contains(mb_strtolower($r['customer_phone'] ?? ''), $term));
            });
        }

        $this->render('debt/index', [
            'pageTitle'  => 'Usimamizi wa Madeni (Debt Management) — NGENZI MOTO SPARE PARTS',
            'activeMenu' => 'debts',
            'stats'      => $stats['debt'],
            'debtors'    => $agingRows,
            'search'     => $search
        ]);
    }

    /**
     * Comprehensive Debt Aging Report Matrix (Current, 1-7, 8-30, 31-60, 61-90, 90+ days)
     */
    public function agingReport(): void {
        $this->checkAccess('debt.reports');

        $customerId = !empty($_GET['customer_id']) ? (int)$_GET['customer_id'] : null;
        $agingRows = Customer::getDebtAging($customerId);

        if ($customerId !== null && !empty($agingRows['customer_id'])) {
            $agingRows = [$agingRows];
        }

        $customers = Customer::all(['status' => 'active'], 1, 500);

        $this->render('debt/aging', [
            'pageTitle'  => 'Ripoti ya Uzeekaji wa Madeni (Debt Aging Report) — NGENZI MOTO',
            'activeMenu' => 'debt_aging',
            'agingRows'  => $agingRows,
            'customers'  => $customers,
            'customerId' => $customerId
        ]);
    }

    /**
     * Debt Collection Terminal (GET / POST)
     */
    public function collect(): void {
        $this->checkAccess('debt.collect');

        $customerId = (int)($_GET['customer_id'] ?? $_POST['customer_id'] ?? 0);
        $customer = $customerId ? Customer::find($customerId) : null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $csrfToken = $_POST['csrf_token'] ?? '';
            if (!Helper::validateCsrf($csrfToken)) {
                Helper::setFlash('danger', 'Hitilafu ya usalama (CSRF invalid).');
                $this->redirect("/index.php?route=debt_collect&customer_id={$customerId}");
                return;
            }

            $amount = (float)($_POST['amount'] ?? 0.00);
            $paymentMethod = trim($_POST['payment_method'] ?? 'Cash');
            $provider = !empty($_POST['provider']) ? trim($_POST['provider']) : null;
            $referenceNo = !empty($_POST['reference_no']) ? trim($_POST['reference_no']) : null;
            $notes = !empty($_POST['notes']) ? trim($_POST['notes']) : null;

            // Custom allocation if specified
            $customAllocation = null;
            if (!empty($_POST['allocation']) && is_array($_POST['allocation'])) {
                $customAllocation = [];
                foreach ($_POST['allocation'] as $saleId => $allocAmt) {
                    $allocAmt = (float)$allocAmt;
                    if ($allocAmt > 0) {
                        $customAllocation[] = [
                            'sale_id' => (int)$saleId,
                            'amount'  => $allocAmt
                        ];
                    }
                }
            }

            try {
                $res = Payment::collectDebt(
                    $customerId,
                    $amount,
                    $paymentMethod,
                    $provider,
                    $referenceNo,
                    $notes,
                    $customAllocation,
                    Auth::id()
                );

                Helper::setFlash('success', "Malipo ya deni ya TSh " . number_format($amount, 2) . " yamepokelewa kikamilifu! Risiti #{$res['payment_no']}. Salio jipya la deni ni TSh " . number_format($res['new_customer_balance'], 2));
                $this->redirect("/index.php?route=debt_customer_profile&id={$customerId}");

            } catch (Exception $e) {
                Helper::setFlash('danger', "Hitilafu: " . $e->getMessage());
                $this->redirect("/index.php?route=debt_collect&customer_id={$customerId}");
            }
            return;
        }

        $outstandingInvoices = $customerId ? Customer::getOutstandingInvoices($customerId) : [];
        $debtors = Customer::all(['status' => 'active'], 1, 500);

        $this->render('debt/collect', [
            'pageTitle'           => 'Kukusanya Malipo ya Deni (Debt Collection) — NGENZI MOTO',
            'activeMenu'          => 'debt_collect',
            'customer'            => $customer,
            'customerId'          => $customerId,
            'outstandingInvoices' => $outstandingInvoices,
            'debtors'             => $debtors,
            'paymentMethods'      => explode(',', Setting::get('payment_methods', 'Cash,Mobile Money,Bank,Card')),
            'providers'           => explode(',', Setting::get('mobile_money_providers', 'M-Pesa,Tigo Pesa,Airtel Money,Halopesa'))
        ]);
    }

    /**
     * Customer Account Statement (Ledger with DEBIT & CREDIT)
     */
    public function statement(): void {
        $this->checkAccess('customer_statement.view');

        $customerId = (int)($_GET['customer_id'] ?? 0);
        $startDate = trim($_GET['start_date'] ?? date('Y-m-01'));
        $endDate = trim($_GET['end_date'] ?? date('Y-m-d'));

        if ($customerId <= 0) {
            Helper::setFlash('warning', 'Tafadhali chagua mteja ili kuona taarifa ya akaunti (Customer Statement).');
            $this->redirect('/index.php?route=debts');
            return;
        }

        try {
            $statement = Customer::getStatement($customerId, $startDate, $endDate);

            $settings = [
                'business_name'    => Setting::get('business_name', 'NGENZI MOTO SPARE PARTS'),
                'business_tagline' => Setting::get('business_tagline', 'Dar es Salaam Motorcycle & Bajaj Spare Parts Specialist'),
                'business_phone'   => Setting::get('business_phone', '+255 754 123 456 / +255 715 000 111'),
                'business_email'   => Setting::get('business_email', 'info@motosparertz.com'),
                'business_address' => Setting::get('business_address', 'Mtaa wa Msimbazi / Kariakoo, Dar es Salaam, Tanzania')
            ];

            $isPrint = (($_GET['print'] ?? 0) == 1);
            $layout = $isPrint ? 'blank' : 'main';

            $this->render('debt/statement', [
                'pageTitle'  => "Taarifa ya Akaunti ya Mteja: {$statement['customer']['name']}",
                'activeMenu' => 'debts',
                'statement'  => $statement,
                'settings'   => $settings,
                'isPrint'    => $isPrint
            ], $layout);

        } catch (Exception $e) {
            Helper::setFlash('danger', "Hitilafu: " . $e->getMessage());
            $this->redirect('/index.php?route=debts');
        }
    }

    /**
     * Customer Financial Debt Profile
     */
    public function profile(): void {
        $this->checkAccess('debt.view');

        $id = (int)($_GET['id'] ?? 0);
        $customer = Customer::find($id);

        if (!$customer) {
            Helper::setFlash('danger', 'Mteja hakupatikana.');
            $this->redirect('/index.php?route=debts');
            return;
        }

        $stats = Customer::getStatistics($id);
        $outstandingInvoices = Customer::getOutstandingInvoices($id);
        $paymentHistory = Customer::getPaymentHistory($id);
        $aging = Customer::getDebtAging($id);

        $this->render('debt/profile', [
            'pageTitle'           => "Wasifu wa Deni: {$customer['name']} ({$customer['customer_code']})",
            'activeMenu'          => 'debts',
            'customer'            => $customer,
            'stats'               => $stats,
            'outstandingInvoices' => $outstandingInvoices,
            'paymentHistory'      => $paymentHistory,
            'aging'               => $aging
        ]);
    }
}
