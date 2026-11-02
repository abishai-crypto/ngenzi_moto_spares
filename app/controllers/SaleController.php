<?php
/**
 * NGENZI MOTO SPARE PARTS
 * SaleController (Phase 8)
 * Sales Management, History, Thermal Receipts, Invoices,
 * Cancellations, and Daily Performance Reports.
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Helper;
use App\Models\Sale;
use App\Models\Setting;
use App\Models\User;
use Exception;

class SaleController extends Controller {

    /**
     * Verify user permission to view sales
     */
    private function checkAccess(): void {
        Auth::requireAuth();
        if (!Auth::hasPermission('sales.view') && !Auth::hasRole(['super_admin', 'manager', 'cashier', 'accountant', 'storekeeper'])) {
            Helper::setFlash('danger', 'Huna ruhusa ya kutazama historia ya mauzo.');
            $this->redirect('/index.php?route=dashboard');
            exit;
        }
    }

    /**
     * Sales History Directory
     */
    public function index(): void {
        $this->checkAccess();

        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 20;

        $filters = [
            'search'         => trim($_GET['search'] ?? ''),
            'payment_status' => trim($_GET['payment_status'] ?? ''),
            'sale_status'    => trim($_GET['sale_status'] ?? ''),
            'payment_method' => trim($_GET['payment_method'] ?? ''),
            'cashier_id'     => trim($_GET['cashier_id'] ?? ''),
            'customer_id'    => trim($_GET['customer_id'] ?? ''),
            'date_from'      => trim($_GET['date_from'] ?? ''),
            'date_to'        => trim($_GET['date_to'] ?? '')
        ];

        $sales = Sale::all($filters, $page, $perPage);
        $totalSales = Sale::count($filters);
        $totalPages = max(1, (int)ceil($totalSales / $perPage));

        // Quick Daily KPI Summary (Today)
        $todaySummary = Sale::getDailySummary(date('Y-m-d'));

        $canCancel = Auth::hasPermission('sales.cancel') || Auth::hasRole(['super_admin', 'manager']);
        $canViewProfit = Auth::canViewFinancials();

        // Cashiers / Staff list for filter dropdown
        $cashiers = User::all(null, null, 'active');

        $this->render('sales/index', [
            'pageTitle'     => 'Historia ya Mauzo — NGENZI MOTO SPARE PARTS',
            'activeMenu'    => 'sales',
            'sales'         => $sales,
            'totalSales'    => $totalSales,
            'totalPages'    => $totalPages,
            'currentPage'   => $page,
            'filters'       => $filters,
            'todaySummary'  => $todaySummary,
            'canCancel'     => $canCancel,
            'canViewProfit' => $canViewProfit,
            'cashiers'      => $cashiers,
            'currency'      => Setting::get('currency', 'TZS')
        ]);
    }

    /**
     * View Detailed Sale Record
     */
    public function show(): void {
        $this->checkAccess();

        $id = (int)($_GET['id'] ?? 0);
        $sale = Sale::find($id);

        if (!$sale) {
            Helper::setFlash('danger', 'Ankara ya mauzo haikupatikana.');
            $this->redirect('/index.php?route=sales');
            return;
        }

        $items = Sale::getItems($id);
        $payments = Sale::getPayments($id);

        $canCancel = Auth::hasPermission('sales.cancel') || Auth::hasRole(['super_admin', 'manager']);
        $canViewProfit = Auth::canViewFinancials();

        $this->render('sales/show', [
            'pageTitle'     => "Taarifa za Mauzo: Ankara #{$sale['invoice_no']}",
            'activeMenu'    => 'sales',
            'sale'          => $sale,
            'items'         => $items,
            'payments'      => $payments,
            'canCancel'     => $canCancel,
            'canViewProfit' => $canViewProfit,
            'currency'      => Setting::get('currency', 'TZS')
        ]);
    }

    /**
     * Thermal Receipt Printable View (58mm / 80mm)
     */
    public function receipt(): void {
        $this->checkAccess();

        $id = (int)($_GET['id'] ?? 0);
        $sale = Sale::find($id);

        if (!$sale) {
            Helper::setFlash('danger', 'Ankara ya mauzo haikupatikana.');
            $this->redirect('/index.php?route=sales');
            return;
        }

        $items = Sale::getItems($id);
        $payments = Sale::getPayments($id);

        $settings = [
            'business_name'    => Setting::get('business_name', 'NGENZI MOTO SPARE PARTS'),
            'business_tagline' => Setting::get('business_tagline', 'Dar es Salaam Motorcycle & Bajaj Spare Parts'),
            'business_phone'   => Setting::get('business_phone', '+255 754 123 456 / +255 715 000 111'),
            'business_email'   => Setting::get('business_email', 'info@motosparertz.com'),
            'business_address' => Setting::get('business_address', 'Mtaa wa Msimbazi / Kariakoo, Dar es Salaam'),
            'currency'         => Setting::get('currency', 'TZS'),
            'receipt_footer'   => Setting::get('receipt_footer', 'Asante sana kwa kufanya biashara nasi!')
        ];

        $this->render('sales/receipt', [
            'pageTitle' => "Risiti #{$sale['invoice_no']}",
            'sale'      => $sale,
            'items'     => $items,
            'payments'  => $payments,
            'settings'  => $settings
        ], 'blank'); // Use blank layout for clean printing
    }

    /**
     * Formal A4 Commercial Invoice Printable View
     */
    public function invoice(): void {
        $this->checkAccess();

        $id = (int)($_GET['id'] ?? 0);
        $sale = Sale::find($id);

        if (!$sale) {
            Helper::setFlash('danger', 'Ankara haikupatikana.');
            $this->redirect('/index.php?route=sales');
            return;
        }

        $items = Sale::getItems($id);
        $payments = Sale::getPayments($id);

        $settings = [
            'business_name'    => Setting::get('business_name', 'NGENZI MOTO SPARE PARTS'),
            'business_tagline' => Setting::get('business_tagline', 'Motorcycle & Bajaj Spare Parts Specialist'),
            'business_phone'   => Setting::get('business_phone', '+255 754 123 456'),
            'business_email'   => Setting::get('business_email', 'info@motosparertz.com'),
            'business_address' => Setting::get('business_address', 'Kariakoo, Dar es Salaam, Tanzania'),
            'currency'         => Setting::get('currency', 'TZS')
        ];

        $this->render('sales/invoice', [
            'pageTitle' => "Ankara ya Mauzo (Invoice) #{$sale['invoice_no']}",
            'sale'      => $sale,
            'items'     => $items,
            'payments'  => $payments,
            'settings'  => $settings
        ], 'blank');
    }

    /**
     * Cancel a completed sale with stock reversal
     */
    public function cancel(): void {
        Auth::requireAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/index.php?route=sales');
            return;
        }

        // Strictly verify cancel permission
        if (!Auth::hasPermission('sales.cancel') && !Auth::hasRole(['super_admin', 'manager'])) {
            Helper::setFlash('danger', 'Huna ruhusa ya kughairi au kufuta mauzo yaliyokamilika (Action Restricted).');
            $this->redirect('/index.php?route=sales');
            return;
        }

        $csrfToken = $_POST['csrf_token'] ?? '';
        if (!Helper::validateCsrf($csrfToken)) {
            Helper::setFlash('danger', 'Hitilafu ya usalama (CSRF invalid).');
            $this->redirect('/index.php?route=sales');
            return;
        }

        $saleId = (int)($_POST['sale_id'] ?? 0);
        $reason = trim($_POST['cancel_reason'] ?? '');

        if ($saleId <= 0) {
            Helper::setFlash('danger', 'Mauzo batili.');
            $this->redirect('/index.php?route=sales');
            return;
        }

        if (empty($reason)) {
            Helper::setFlash('danger', 'Tafadhali taja sababu ya kughairi mauzo haya.');
            $this->redirect("/index.php?route=sale_show&id={$saleId}");
            return;
        }

        try {
            Sale::cancelSale($saleId, $reason, Auth::id() ?? 1);
            Helper::setFlash('success', "Mauzo yameghairiwa kikamilifu na bidhaa zimerejeshwa stoo.");
        } catch (Exception $e) {
            Helper::setFlash('danger', "Hitilafu katika kughairi mauzo: " . $e->getMessage());
        }

        $this->redirect("/index.php?route=sale_show&id={$saleId}");
    }

    /**
     * Daily Sales Summary and Cashier Breakdown Report
     */
    public function dailySummary(): void {
        $this->checkAccess();

        $date = trim($_GET['date'] ?? date('Y-m-d'));
        $cashierId = !empty($_GET['cashier_id']) ? (int)$_GET['cashier_id'] : null;

        $dailyStats = Sale::getDailySummary($date);
        $cashierStats = Sale::getCashierDailySummary($date, $cashierId);

        $cashiers = User::all(null, null, 'active');
        $canViewProfit = Auth::canViewFinancials();

        $this->render('sales/daily_summary', [
            'pageTitle'     => "Ripoti ya Mauzo ya Kila Siku — {$date}",
            'activeMenu'    => 'sales_daily_summary',
            'date'          => $date,
            'cashierId'     => $cashierId,
            'dailyStats'    => $dailyStats,
            'cashierStats'  => $cashierStats,
            'cashiers'      => $cashiers,
            'canViewProfit' => $canViewProfit,
            'currency'      => Setting::get('currency', 'TZS')
        ]);
    }
}
