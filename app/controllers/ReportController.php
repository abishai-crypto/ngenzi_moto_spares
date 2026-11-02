<?php
/**
 * NGENZI MOTO SPARE PARTS
 * Reports & Business Analytics Controller (Phase 12)
 * Dar es Salaam, Tanzania
 * Central Reporting Engine with Granular RBAC, CSV Export,
 * Print Layouts & Multi-Dimensional Analytics.
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Helper;
use App\Models\Report;
use App\Models\Financial;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\User;
use App\Models\VehicleBrand;
use App\Models\VehicleModel;
use Exception;

require_once __DIR__ . '/../models/Report.php';
require_once __DIR__ . '/../models/Financial.php';
require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../models/Category.php';
require_once __DIR__ . '/../models/Brand.php';
require_once __DIR__ . '/../models/Customer.php';
require_once __DIR__ . '/../models/Supplier.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/VehicleBrand.php';
require_once __DIR__ . '/../models/VehicleModel.php';

class ReportController extends Controller {

    /**
     * Check granular reporting permission helper
     */
    private function checkAccess(string $permission = 'reports.view'): void {
        Auth::requireAuth();

        if (Auth::hasRole('super_admin')) {
            return;
        }

        if (!Auth::hasPermission($permission)) {
            Helper::setFlash('danger', 'Huna idhini ya kufikia sehemu hii ya ripoti (Access Denied).');
            $this->redirect('/index.php?route=dashboard');
            exit;
        }
    }

    /**
     * 1. CENTRAL REPORTING DASHBOARD / HUB
     */
    public function index(): void {
        $this->checkAccess('reports.view');

        $period = trim($_GET['period'] ?? 'this_month');
        $customStart = trim($_GET['start_date'] ?? '');
        $customEnd = trim($_GET['end_date'] ?? '');

        $range = Report::resolveDateRange($period, $customStart, $customEnd);
        $kpis = Report::getDashboardKPIs($range['start_date'], $range['end_date']);
        $dailyTrend = Report::getDailySalesTrend($range['start_date'], $range['end_date']);
        $paymentMethods = Report::getPaymentMethodReport(['date_from' => $range['start_date'], 'date_to' => $range['end_date']]);
        $fastMoving = Report::getFastMovingProducts(['date_from' => $range['start_date'], 'date_to' => $range['end_date']], 5);

        $this->render('reports/dashboard', [
            'pageTitle'      => 'Kituo Kikuu cha Ripoti na Takwimu — NGENZI MOTO SPARE PARTS',
            'activeMenu'     => 'reports',
            'range'          => $range,
            'kpis'           => $kpis,
            'dailyTrend'     => $dailyTrend,
            'paymentMethods' => $paymentMethods,
            'fastMoving'     => $fastMoving
        ]);
    }

    /**
     * 2. SALES REPORT
     */
    public function sales(): void {
        $this->checkAccess('reports.sales');

        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $period = trim($_GET['period'] ?? 'this_month');
        $customStart = trim($_GET['start_date'] ?? '');
        $customEnd = trim($_GET['end_date'] ?? '');
        $range = Report::resolveDateRange($period, $customStart, $customEnd);

        $filters = [
            'date_from'      => $range['start_date'],
            'date_to'        => $range['end_date'],
            'customer_id'    => trim($_GET['customer_id'] ?? ''),
            'cashier_id'     => trim($_GET['cashier_id'] ?? ''),
            'payment_method' => trim($_GET['payment_method'] ?? ''),
            'payment_status' => trim($_GET['payment_status'] ?? ''),
            'search'         => trim($_GET['search'] ?? '')
        ];

        $salesData = Report::getSalesReport($filters, $page, 25);
        $customers = Customer::all();
        $users = User::all();

        $this->render('reports/sales', [
            'pageTitle'  => 'Ripoti ya Mauzo (Sales Report) — NGENZI MOTO SPARE PARTS',
            'activeMenu' => 'reports_sales',
            'sales'      => $salesData['data'],
            'total'      => $salesData['total'],
            'sums'       => $salesData,
            'page'       => $salesData['page'],
            'totalPages' => $salesData['total_pages'],
            'customers'  => $customers,
            'users'      => $users,
            'range'      => $range,
            'filters'    => $filters
        ]);
    }

    /**
     * 3. SALES BY PRODUCT
     */
    public function salesByProduct(): void {
        $this->checkAccess('reports.sales');

        $period = trim($_GET['period'] ?? 'this_month');
        $customStart = trim($_GET['start_date'] ?? '');
        $customEnd = trim($_GET['end_date'] ?? '');
        $range = Report::resolveDateRange($period, $customStart, $customEnd);

        $filters = [
            'date_from'   => $range['start_date'],
            'date_to'     => $range['end_date'],
            'category_id' => trim($_GET['category_id'] ?? ''),
            'brand_id'    => trim($_GET['brand_id'] ?? ''),
            'search'      => trim($_GET['search'] ?? '')
        ];

        $products = Report::getSalesByProduct($filters);
        $categories = Category::all();
        $brands = Brand::all();

        $this->render('reports/sales_by_product', [
            'pageTitle'  => 'Mauzo kwa Bidhaa (Sales by Product) — NGENZI MOTO SPARE PARTS',
            'activeMenu' => 'reports_sales_by_product',
            'products'   => $products,
            'categories' => $categories,
            'brands'     => $brands,
            'range'      => $range,
            'filters'    => $filters
        ]);
    }

    /**
     * 4. SALES BY CATEGORY & BRAND
     */
    public function salesByCategory(): void {
        $this->checkAccess('reports.sales');

        $period = trim($_GET['period'] ?? 'this_month');
        $customStart = trim($_GET['start_date'] ?? '');
        $customEnd = trim($_GET['end_date'] ?? '');
        $range = Report::resolveDateRange($period, $customStart, $customEnd);

        $filters = ['date_from' => $range['start_date'], 'date_to' => $range['end_date']];
        $categories = Report::getSalesByCategory($filters);
        $brands = Report::getSalesByBrand($filters);

        $this->render('reports/sales_by_category', [
            'pageTitle'  => 'Mauzo kwa Kategoria & Chapa — NGENZI MOTO SPARE PARTS',
            'activeMenu' => 'reports_sales_by_category',
            'categories' => $categories,
            'brands'     => $brands,
            'range'      => $range
        ]);
    }

    /**
     * 5. SALES BY CASHIER
     */
    public function salesByCashier(): void {
        $this->checkAccess('reports.sales');

        $period = trim($_GET['period'] ?? 'this_month');
        $customStart = trim($_GET['start_date'] ?? '');
        $customEnd = trim($_GET['end_date'] ?? '');
        $range = Report::resolveDateRange($period, $customStart, $customEnd);

        $filters = ['date_from' => $range['start_date'], 'date_to' => $range['end_date']];
        $cashiers = Report::getSalesByCashier($filters);

        $this->render('reports/sales_by_cashier', [
            'pageTitle'  => 'Utendaji wa Wauzaji (Sales by Cashier) — NGENZI MOTO SPARE PARTS',
            'activeMenu' => 'reports_sales_by_cashier',
            'cashiers'   => $cashiers,
            'range'      => $range
        ]);
    }

    /**
     * 6. SALES BY CUSTOMER
     */
    public function salesByCustomer(): void {
        $this->checkAccess('reports.customers');

        $period = trim($_GET['period'] ?? 'this_month');
        $customStart = trim($_GET['start_date'] ?? '');
        $customEnd = trim($_GET['end_date'] ?? '');
        $range = Report::resolveDateRange($period, $customStart, $customEnd);

        $filters = ['date_from' => $range['start_date'], 'date_to' => $range['end_date']];
        $customers = Report::getSalesByCustomer($filters);

        $this->render('reports/sales_by_customer', [
            'pageTitle'  => 'Mauzo kwa Wateja (Sales by Customer) — NGENZI MOTO SPARE PARTS',
            'activeMenu' => 'reports_sales_by_customer',
            'customers'  => $customers,
            'range'      => $range
        ]);
    }

    /**
     * 7. PAYMENT METHOD & CASH FLOW REPORT
     */
    public function payments(): void {
        $this->checkAccess('reports.financial');

        $period = trim($_GET['period'] ?? 'this_month');
        $customStart = trim($_GET['start_date'] ?? '');
        $customEnd = trim($_GET['end_date'] ?? '');
        $range = Report::resolveDateRange($period, $customStart, $customEnd);

        $filters = ['date_from' => $range['start_date'], 'date_to' => $range['end_date']];
        $paymentMethods = Report::getPaymentMethodReport($filters);
        $cashCollections = Report::getCashCollectionReport($filters, 1, 50);

        $this->render('reports/payments', [
            'pageTitle'       => 'Ripoti ya Malipo na Mikusanyo ya Fedha — NGENZI MOTO SPARE PARTS',
            'activeMenu'      => 'reports_payments',
            'paymentMethods'  => $paymentMethods,
            'cashCollections' => $cashCollections,
            'range'           => $range
        ]);
    }

    /**
     * 8. VEHICLE COMPATIBILITY ANALYTICS
     */
    public function vehicleCompatibility(): void {
        $this->checkAccess('reports.sales');

        $period = trim($_GET['period'] ?? 'this_month');
        $customStart = trim($_GET['start_date'] ?? '');
        $customEnd = trim($_GET['end_date'] ?? '');
        $range = Report::resolveDateRange($period, $customStart, $customEnd);

        $vehicleType = trim($_GET['vehicle_type'] ?? '');
        $filters = [
            'date_from'    => $range['start_date'],
            'date_to'      => $range['end_date'],
            'vehicle_type' => $vehicleType
        ];

        $compatibilities = Report::getSalesByVehicleCompatibility($filters);

        $this->render('reports/vehicle_compatibility', [
            'pageTitle'       => 'Mauzo kwa Aina ya Chombo (Motorcycle / Bajaj) — NGENZI MOTO SPARE PARTS',
            'activeMenu'      => 'reports_vehicles',
            'compatibilities' => $compatibilities,
            'vehicleType'     => $vehicleType,
            'range'           => $range
        ]);
    }

    /**
     * 9. CREDIT SALES & DEBT AGING REPORT
     */
    public function debts(): void {
        $this->checkAccess('reports.debts');

        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $filters = [
            'customer_id' => trim($_GET['customer_id'] ?? ''),
            'date_from'   => trim($_GET['date_from'] ?? ''),
            'date_to'     => trim($_GET['date_to'] ?? '')
        ];

        $creditData = Report::getCreditSalesReport($filters, $page, 25);
        $agingReport = Report::getDebtAgingReport();
        $customers = Customer::all();

        $this->render('reports/debts', [
            'pageTitle'   => 'Ripoti ya Madeni na Ukuaji (Debt Aging) — NGENZI MOTO SPARE PARTS',
            'activeMenu'  => 'reports_debts',
            'creditSales' => $creditData['data'],
            'sums'        => $creditData,
            'aging'       => $agingReport,
            'customers'   => $customers,
            'page'        => $creditData['page'],
            'totalPages'  => $creditData['total_pages'],
            'filters'     => $filters
        ]);
    }

    /**
     * 10. CUSTOMER STATEMENT (Printable Ledger)
     */
    public function customerStatement(): void {
        $this->checkAccess('reports.customers');

        $customerId = (int)($_GET['customer_id'] ?? 0);
        $startDate = trim($_GET['start_date'] ?? date('Y-01-01'));
        $endDate = trim($_GET['end_date'] ?? date('Y-m-d'));
        $customers = Customer::all();

        $statement = null;
        if ($customerId > 0) {
            try {
                $statement = Report::getCustomerStatement($customerId, $startDate, $endDate);
            } catch (Exception $e) {
                Helper::setFlash('danger', $e->getMessage());
            }
        }

        $this->render('reports/customer_statement', [
            'pageTitle'  => 'Taarifa ya Akaunti ya Mteja (Customer Statement) — NGENZI MOTO SPARE PARTS',
            'activeMenu' => 'reports_customer_statement',
            'customers'  => $customers,
            'customerId' => $customerId,
            'startDate'  => $startDate,
            'endDate'    => $endDate,
            'statement'  => $statement
        ]);
    }

    /**
     * 11. INVENTORY & STOCK VELOCITY (Current, Movement, Fast/Slow, Dead, Reorder)
     */
    public function inventory(): void {
        $this->checkAccess('reports.inventory');

        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $tab = trim($_GET['tab'] ?? 'current');

        $filters = [
            'category_id'  => trim($_GET['category_id'] ?? ''),
            'brand_id'     => trim($_GET['brand_id'] ?? ''),
            'stock_status' => trim($_GET['stock_status'] ?? ''),
            'search'       => trim($_GET['search'] ?? '')
        ];

        $inventoryData = Report::getInventoryReport($filters, $page, 25);
        $categories = Category::all();
        $brands = Brand::all();
        $fastMoving = Report::getFastMovingProducts([], 20);
        $slowMoving = Report::getSlowMovingProducts([], 30, 20);
        $deadStock = Report::getDeadStockReport(90);
        $reorderList = Report::getReorderReport();

        $this->render('reports/inventory', [
            'pageTitle'     => 'Ripoti ya Stoo, Thamani na Mienendo ya Vipuri — NGENZI MOTO SPARE PARTS',
            'activeMenu'    => 'reports_inventory',
            'inventory'     => $inventoryData['data'],
            'sums'          => $inventoryData,
            'total'         => $inventoryData['total'],
            'page'          => $inventoryData['page'],
            'totalPages'    => $inventoryData['total_pages'],
            'categories'    => $categories,
            'brands'        => $brands,
            'fastMoving'    => $fastMoving,
            'slowMoving'    => $slowMoving,
            'deadStock'     => $deadStock,
            'reorderList'   => $reorderList,
            'tab'           => $tab,
            'filters'       => $filters
        ]);
    }

    /**
     * 12. PURCHASES & SUPPLIER REPORT
     */
    public function purchases(): void {
        $this->checkAccess('reports.purchases');

        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $period = trim($_GET['period'] ?? 'this_month');
        $customStart = trim($_GET['start_date'] ?? '');
        $customEnd = trim($_GET['end_date'] ?? '');
        $range = Report::resolveDateRange($period, $customStart, $customEnd);

        $filters = [
            'date_from'   => $range['start_date'],
            'date_to'     => $range['end_date'],
            'supplier_id' => trim($_GET['supplier_id'] ?? '')
        ];

        $purchasesData = Report::getPurchasesReport($filters, $page, 25);
        $supplierList = Report::getSuppliersReport($filters);
        $suppliers = Supplier::all();

        $this->render('reports/purchases', [
            'pageTitle'    => 'Ripoti ya Manunuzi na Wasambazaji — NGENZI MOTO SPARE PARTS',
            'activeMenu'   => 'reports_purchases',
            'purchases'    => $purchasesData['data'],
            'sums'         => $purchasesData,
            'page'         => $purchasesData['page'],
            'totalPages'   => $purchasesData['total_pages'],
            'supplierList' => $supplierList,
            'suppliers'    => $suppliers,
            'range'        => $range,
            'filters'      => $filters
        ]);
    }

    /**
     * 13. RETURNS & RETURN RATE REPORT
     */
    public function returns(): void {
        $this->checkAccess('reports.returns');

        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $period = trim($_GET['period'] ?? 'this_month');
        $customStart = trim($_GET['start_date'] ?? '');
        $customEnd = trim($_GET['end_date'] ?? '');
        $range = Report::resolveDateRange($period, $customStart, $customEnd);

        $filters = [
            'date_from' => $range['start_date'],
            'date_to'   => $range['end_date'],
            'status'    => trim($_GET['status'] ?? '')
        ];

        $returnsData = Report::getReturnsReport($filters, $page, 25);
        $returnRates = Report::getReturnRateReport($filters);

        $this->render('reports/returns', [
            'pageTitle'   => 'Ripoti ya Marejesho na Return Rate % — NGENZI MOTO SPARE PARTS',
            'activeMenu'  => 'reports_returns',
            'returns'     => $returnsData['data'],
            'sums'        => $returnsData,
            'page'        => $returnsData['page'],
            'totalPages'  => $returnsData['total_pages'],
            'returnRates' => $returnRates,
            'range'       => $range,
            'filters'     => $filters
        ]);
    }

    /**
     * 14. PRODUCT 360 PERFORMANCE ANALYTICS
     */
    public function productPerformance(): void {
        $this->checkAccess('reports.sales');

        $productId = (int)($_GET['id'] ?? 0);
        if ($productId <= 0) {
            $this->redirect('/index.php?route=reports/sales_by_product');
            return;
        }

        try {
            $analytics = Report::getProductPerformanceDetails($productId);
            $this->render('reports/product_performance', [
                'pageTitle'  => "Uchambuzi wa Kipuri: {$analytics['product']['name']} — NGENZI MOTO SPARE PARTS",
                'activeMenu' => 'reports_sales_by_product',
                'analytics'  => $analytics
            ]);
        } catch (Exception $e) {
            Helper::setFlash('danger', $e->getMessage());
            $this->redirect('/index.php?route=reports/sales_by_product');
        }
    }

    /**
     * 15. CSV EXPORT HANDLER
     */
    public function export(): void {
        $this->checkAccess('reports.export');

        $type = trim($_GET['type'] ?? 'sales');
        $period = trim($_GET['period'] ?? 'this_month');
        $customStart = trim($_GET['start_date'] ?? '');
        $customEnd = trim($_GET['end_date'] ?? '');
        $range = Report::resolveDateRange($period, $customStart, $customEnd);

        $filename = "Ripoti_{$type}_" . date('Ymd_His') . ".csv";

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');
        // Add UTF-8 BOM for Excel
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        $canViewFinancials = Auth::canViewFinancials();

        switch ($type) {
            case 'sales':
                fputcsv($output, Helper::sanitizeCsvRow(['Ankara', 'Tarehe', 'Mteja', 'Simu', 'Mauzo Ghafi', 'Punguzo', 'Mauzo Halisi', 'Kilicholipwa', 'Baki', 'Njia ya Malipo', 'Cashier']));
                $data = Report::getSalesReport(['date_from' => $range['start_date'], 'date_to' => $range['end_date']], 1, 10000)['data'];
                foreach ($data as $r) {
                    fputcsv($output, Helper::sanitizeCsvRow([
                        $r['invoice_no'],
                        date('d/m/Y', strtotime($r['sale_date'])),
                        $r['customer_name'] ?? 'Walk-in',
                        $r['customer_phone'] ?? '',
                        $r['gross_sales'],
                        $r['discount_amount'],
                        $r['net_sales'],
                        $r['paid_amount'],
                        $r['balance_due'],
                        $r['payment_method'],
                        $r['cashier_name'] ?? ''
                    ]));
                }
                break;

            case 'products':
                $headers = ['SKU', 'Jina la Kipuri', 'Kategoria', 'Chapa', 'Idadi Iliyouzwa', 'Mapato Halisi'];
                if ($canViewFinancials) {
                    $headers = array_merge($headers, ['COGS', 'Faida Ghafi', 'Margin %']);
                }
                fputcsv($output, Helper::sanitizeCsvRow($headers));

                $data = Report::getSalesByProduct(['date_from' => $range['start_date'], 'date_to' => $range['end_date']]);
                foreach ($data as $r) {
                    $row = [
                        $r['sku'],
                        $r['product_name'],
                        $r['category_name'] ?? '',
                        $r['brand_name'] ?? '',
                        $r['total_qty_sold'],
                        $r['net_revenue']
                    ];
                    if ($canViewFinancials) {
                        $row[] = $r['total_cogs'];
                        $row[] = $r['gross_profit'];
                        $row[] = $r['gross_margin'] . '%';
                    }
                    fputcsv($output, Helper::sanitizeCsvRow($row));
                }
                break;

            case 'inventory':
                $headers = ['SKU', 'Jina la Kipuri', 'Kategoria', 'Chapa', 'Stoo Iliyopo', 'Kiwango cha Chini'];
                if ($canViewFinancials) {
                    $headers[] = 'Bei ya Kununulia';
                }
                $headers[] = 'Bei ya Kuuzia';
                if ($canViewFinancials) {
                    $headers[] = 'Thamani ya Stoo';
                }
                fputcsv($output, Helper::sanitizeCsvRow($headers));

                $data = Report::getInventoryReport([], 1, 10000)['data'];
                foreach ($data as $r) {
                    $row = [
                        $r['sku'],
                        $r['product_name'],
                        $r['category_name'] ?? '',
                        $r['brand_name'] ?? '',
                        $r['current_stock'],
                        $r['minimum_stock']
                    ];
                    if ($canViewFinancials) {
                        $row[] = $r['unit_cost'];
                    }
                    $row[] = $r['selling_price'];
                    if ($canViewFinancials) {
                        $row[] = $r['inventory_value'];
                    }
                    fputcsv($output, Helper::sanitizeCsvRow($row));
                }
                break;

            case 'product_images':
                $headers = ['ID', 'SKU', 'Jina la Kipuri', 'Kategoria', 'Hali ya Picha (Status)', 'Njia ya Picha (Image Path)', 'Inapatikana Ndani (Offline Ready)'];
                fputcsv($output, Helper::sanitizeCsvRow($headers));
                
                $mappingPath = __DIR__ . '/../../public/assets/images/product_images.json';
                if (file_exists($mappingPath)) {
                    $json = json_decode(file_get_contents($mappingPath), true);
                    foreach ($json as $item) {
                        $row = [
                            $item['product_id'],
                            $item['sku'],
                            $item['product_name'],
                            $item['category'],
                            $item['status'],
                            $item['image_url'],
                            $item['physical_appearance_verified'] ? 'NDIYO' : 'HAPANA'
                        ];
                        fputcsv($output, Helper::sanitizeCsvRow($row));
                    }
                }
                break;

            default:
                fputcsv($output, Helper::sanitizeCsvRow(['Ujumbe']));
                fputcsv($output, Helper::sanitizeCsvRow(['Aina ya ripoti haijatambuliwa.']));
                break;
        }

        fclose($output);
        exit;
    }

    /**
     * 16. PRODUCT IMAGE INVENTORY REPORT (Section 13)
     */
    public function productImages(): void {
        Auth::requireAuth();

        $statusFilter = trim($_GET['status'] ?? '');
        $categoryFilter = trim($_GET['category_id'] ?? '');
        $search = trim($_GET['search'] ?? '');

        $pdo = \App\Core\Database::getConnection();
        
        $sql = "
            SELECT p.id, p.sku, p.name, p.category_id, p.brand_id, p.selling_price, p.buying_price,
                   p.current_stock, p.image_url, c.name as category_name, c.code as category_code,
                   b.name as brand_name
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            LEFT JOIN brands b ON p.brand_id = b.id
            WHERE 1=1
        ";
        $params = [];
        if ($categoryFilter !== '') {
            $sql .= " AND p.category_id = ?";
            $params[] = (int)$categoryFilter;
        }
        if ($search !== '') {
            $term = "%{$search}%";
            $sql .= " AND (p.name LIKE ? OR p.sku LIKE ? OR c.name LIKE ?)";
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
        }
        $sql .= " ORDER BY p.id ASC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $allProducts = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Load mapping json if exists
        $mappingPath = __DIR__ . '/../../public/assets/images/product_images.json';
        $mappingBySku = [];
        if (file_exists($mappingPath)) {
            $json = json_decode(file_get_contents($mappingPath), true);
            foreach ($json as $item) {
                $mappingBySku[$item['sku']] = $item;
            }
        }

        $items = [];
        $counts = [
            'total'          => 0,
            'available'      => 0,
            'missing'        => 0,
            'image_required' => 0,
            'invalid'        => 0,
            'duplicate'      => 0
        ];
        
        $seenPaths = [];

        foreach ($allProducts as $p) {
            $counts['total']++;
            $sku = $p['sku'];
            $img = $p['image_url'];
            
            // Check status
            $isTest = (strpos($sku, 'CONC-') === 0);
            
            if ($isTest) {
                $status = 'IMAGE_REQUIRED';
                $counts['image_required']++;
            } elseif (empty($img)) {
                $status = 'MISSING';
                $counts['missing']++;
            } elseif (strpos($img, 'placeholder') !== false) {
                $status = 'IMAGE_REQUIRED';
                $counts['image_required']++;
            } else {
                // Check if physical file exists locally
                $cleanRel = ltrim($img, '/');
                $pubPath = __DIR__ . '/../../public/' . $cleanRel;
                $rootPath = __DIR__ . '/../../' . $cleanRel;

                if (!file_exists($pubPath) && !file_exists($rootPath)) {
                    $status = 'MISSING';
                    $counts['missing']++;
                } else {
                    // Check if file is valid image
                    $checkFile = file_exists($pubPath) ? $pubPath : $rootPath;
                    $info = @getimagesize($checkFile);
                    if (!$info) {
                        $status = 'INVALID';
                        $counts['invalid']++;
                    } else {
                        // Check duplicate
                        if (isset($seenPaths[$img])) {
                            $status = 'DUPLICATE';
                            $counts['duplicate']++;
                        } else {
                            $status = 'AVAILABLE';
                            $counts['available']++;
                            $seenPaths[$img] = $sku;
                        }
                    }
                }
            }

            $p['image_status'] = $status;
            $p['file_exists'] = ($status === 'AVAILABLE' || $status === 'DUPLICATE');
            $p['local_path'] = $img ?: 'Hakuna faili';

            // Filter if requested
            if ($statusFilter !== '' && $status !== $statusFilter) {
                continue;
            }

            $items[] = $p;
        }

        $categories = Category::all('active');

        $this->render('reports/product_images', [
            'pageTitle'      => 'Ripoti ya Maktaba ya Picha za Vipuri (Product Image Inventory) — NGENZI',
            'activeMenu'     => 'reports',
            'items'          => $items,
            'counts'         => $counts,
            'categories'     => $categories,
            'statusFilter'   => $statusFilter,
            'categoryFilter' => $categoryFilter,
            'search'         => $search
        ]);
    }
}
