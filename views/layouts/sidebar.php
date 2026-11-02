<?php
use App\Core\Auth;
$currentRoute = $_GET['route'] ?? 'dashboard';
$userRole = Auth::role();
?>
<aside class="sidebar">
    <!-- Brand Header -->
    <div class="sidebar-brand">
        <a href="/index.php?route=dashboard" class="brand-logo">
            <div class="brand-icon">
                <i class="bi bi-gear-fill"></i>
            </div>
            <div class="brand-text">
                <h5>NGENZI MOTO</h5>
                <small>SPARE PARTS • DSM</small>
            </div>
        </a>
    </div>

    <!-- Navigation Menu -->
    <div class="sidebar-menu">
        <div class="menu-category">Kuu (Main)</div>
        <a href="/index.php?route=dashboard" class="nav-link-custom <?= $currentRoute === 'dashboard' ? 'active' : '' ?>">
            <i class="bi bi-grid-1x2-fill"></i>
            <span>Dashibodi</span>
        </a>

        <?php if (Auth::hasRole(['super_admin', 'manager', 'cashier', 'accountant'])): ?>
        <div class="menu-category">Mauzo & POS</div>
        <?php if (Auth::hasRole(['super_admin', 'manager', 'cashier'])): ?>
        <a href="/index.php?route=pos" class="nav-link-custom <?= $currentRoute === 'pos' ? 'active' : '' ?>">
            <i class="bi bi-cart-check-fill"></i>
            <span>Mashine ya Mauzo (POS)</span>
        </a>
        <?php endif; ?>
        <a href="/index.php?route=sales" class="nav-link-custom <?= in_array($currentRoute, ['sales', 'sale_show']) ? 'active' : '' ?>">
            <i class="bi bi-receipt"></i>
            <span>Historia ya Mauzo</span>
        </a>
        <a href="/index.php?route=sales_daily_summary" class="nav-link-custom <?= $currentRoute === 'sales_daily_summary' ? 'active' : '' ?>">
            <i class="bi bi-graph-up-arrow"></i>
            <span>Ripoti ya Mauzo ya Siku</span>
        </a>
        <a href="/index.php?route=returns" class="nav-link-custom <?= in_array($currentRoute, ['returns', 'returns_dashboard', 'return_create', 'return_show', 'return_reports']) ? 'active' : '' ?>">
            <i class="bi bi-arrow-counterclockwise"></i>
            <span>Marejesho (Returns)</span>
        </a>
        <?php endif; ?>

        <?php if (Auth::hasRole(['super_admin', 'manager', 'cashier', 'accountant'])): ?>
        <div class="menu-category">Malipo & Madeni</div>
        <a href="/index.php?route=payments" class="nav-link-custom <?= in_array($currentRoute, ['payments', 'payment_show']) ? 'active' : '' ?>">
            <i class="bi bi-wallet-fill"></i>
            <span>Orodha ya Malipo</span>
        </a>
        <a href="/index.php?route=payments_daily_collection" class="nav-link-custom <?= $currentRoute === 'payments_daily_collection' ? 'active' : '' ?>">
            <i class="bi bi-cash-coin"></i>
            <span>Makusanyo ya Siku</span>
        </a>
        <a href="/index.php?route=debts" class="nav-link-custom <?= in_array($currentRoute, ['debts', 'debt_customer_profile', 'debt_statement']) ? 'active' : '' ?>">
            <i class="bi bi-credit-card-2-front-fill"></i>
            <span>Madeni ya Wateja</span>
        </a>
        <a href="/index.php?route=debt_aging" class="nav-link-custom <?= $currentRoute === 'debt_aging' ? 'active' : '' ?>">
            <i class="bi bi-hourglass-split"></i>
            <span>Uzeekaji wa Madeni (Aging)</span>
        </a>
        <a href="/index.php?route=debt_collect" class="nav-link-custom <?= $currentRoute === 'debt_collect' ? 'active' : '' ?>">
            <i class="bi bi-cash-stack"></i>
            <span>Kusanya Malipo ya Deni</span>
        </a>
        <?php endif; ?>

        <?php if (Auth::hasRole(['super_admin', 'manager', 'storekeeper', 'cashier'])): ?>
        <div class="menu-category">Vipuri & Katalogi</div>
        <a href="/index.php?route=products" class="nav-link-custom <?= in_array($currentRoute, ['products', 'product_create', 'product_edit', 'product_show']) ? 'active' : '' ?>">
            <i class="bi bi-box-seam-fill"></i>
            <span>Katalogi ya Vipuri</span>
        </a>
        <a href="/index.php?route=product_vehicle_search" class="nav-link-custom <?= $currentRoute === 'product_vehicle_search' ? 'active' : '' ?>">
            <i class="bi bi-search-heart"></i>
            <span>Tafuta kwa Chombo</span>
        </a>
        <?php if (Auth::hasRole(['super_admin', 'manager', 'storekeeper'])): ?>
        <a href="/index.php?route=vehicles" class="nav-link-custom <?= $currentRoute === 'vehicles' ? 'active' : '' ?>">
            <i class="bi bi-bicycle"></i>
            <span>Pikipiki & Bajaji</span>
        </a>
        <a href="/index.php?route=categories" class="nav-link-custom <?= in_array($currentRoute, ['categories', 'brands', 'units']) ? 'active' : '' ?>">
            <i class="bi bi-tags-fill"></i>
            <span>Kategoria & Brands</span>
        </a>
        <?php endif; ?>
        <?php endif; ?>

        <?php if (Auth::hasRole(['super_admin', 'manager', 'storekeeper', 'accountant', 'cashier'])): ?>
        <div class="menu-category">Stoo & Mali (Inventory)</div>
        <a href="/index.php?route=inventory_dashboard" class="nav-link-custom <?= $currentRoute === 'inventory_dashboard' ? 'active' : '' ?>">
            <i class="bi bi-speedometer2"></i>
            <span>Dashibodi ya Stoo</span>
        </a>
        <a href="/index.php?route=inventory" class="nav-link-custom <?= in_array($currentRoute, ['inventory', 'inventory_adjust']) ? 'active' : '' ?>">
            <i class="bi bi-boxes"></i>
            <span>Vipuri Vilivyopo Stoo</span>
        </a>
        <a href="/index.php?route=inventory_history" class="nav-link-custom <?= $currentRoute === 'inventory_history' ? 'active' : '' ?>">
            <i class="bi bi-clock-history"></i>
            <span>Mienendo ya Stoo (History)</span>
        </a>
        <?php if (Auth::hasRole(['super_admin', 'manager', 'storekeeper'])): ?>
        <a href="/index.php?route=inventory_counts" class="nav-link-custom <?= in_array($currentRoute, ['inventory_counts', 'inventory_count_create', 'inventory_count_show']) ? 'active' : '' ?>">
            <i class="bi bi-clipboard2-check-fill"></i>
            <span>Ukaguzi (Stock Count)</span>
        </a>
        <?php endif; ?>
        <?php if (Auth::hasRole(['super_admin', 'manager', 'accountant'])): ?>
        <a href="/index.php?route=inventory_valuation" class="nav-link-custom <?= $currentRoute === 'inventory_valuation' ? 'active' : '' ?>">
            <i class="bi bi-cash-stack"></i>
            <span>Thamani ya Stoo (Valuation)</span>
        </a>
        <?php endif; ?>
        <a href="/index.php?route=inventory_analytics" class="nav-link-custom <?= $currentRoute === 'inventory_analytics' ? 'active' : '' ?>">
            <i class="bi bi-graph-up-arrow"></i>
            <span>Uchambuzi (Fast & Slow)</span>
        </a>
        <a href="/index.php?route=inventory_reorder" class="nav-link-custom <?= $currentRoute === 'inventory_reorder' ? 'active' : '' ?>">
            <i class="bi bi-cart-plus"></i>
            <span>Mapendekezo ya Kuagiza</span>
        </a>
        <?php endif; ?>

        <?php if (Auth::hasRole(['super_admin', 'manager'])): ?>
        <div class="menu-category">Manunuzi (Purchases)</div>
        <a href="/index.php?route=purchases" class="nav-link-custom <?= $currentRoute === 'purchases' ? 'active' : '' ?>">
            <i class="bi bi-bag-check-fill"></i>
            <span>Manunuzi & Mzigo Mpya</span>
        </a>
        <a href="/index.php?route=suppliers" class="nav-link-custom <?= $currentRoute === 'suppliers' ? 'active' : '' ?>">
            <i class="bi bi-truck"></i>
            <span>Wasambazaji (Suppliers)</span>
        </a>
        <?php endif; ?>

        <?php if (Auth::hasRole(['super_admin', 'manager', 'accountant', 'cashier'])): ?>
        <div class="menu-category">Wateja & Gereji</div>
        <a href="/index.php?route=customer_dashboard" class="nav-link-custom <?= $currentRoute === 'customer_dashboard' ? 'active' : '' ?>">
            <i class="bi bi-speedometer"></i>
            <span>Dashibodi ya Wateja</span>
        </a>
        <a href="/index.php?route=customers" class="nav-link-custom <?= in_array($currentRoute, ['customers', 'customer_create', 'customer_edit', 'customer_show']) ? 'active' : '' ?>">
            <i class="bi bi-people-fill"></i>
            <span>Orodha ya Wateja</span>
        </a>
        <a href="/index.php?route=mechanics" class="nav-link-custom <?= in_array($currentRoute, ['mechanics', 'mechanic_create', 'mechanic_edit', 'mechanic_show']) ? 'active' : '' ?>">
            <i class="bi bi-wrench"></i>
            <span>Mafundi (Mechanics)</span>
        </a>
        <a href="/index.php?route=garages" class="nav-link-custom <?= in_array($currentRoute, ['garages', 'garage_create', 'garage_edit', 'garage_show']) ? 'active' : '' ?>">
            <i class="bi bi-buildings"></i>
            <span>Gereji & Karakana</span>
        </a>
        <a href="/index.php?route=debts" class="nav-link-custom <?= $currentRoute === 'debts' ? 'active' : '' ?>">
            <i class="bi bi-credit-card-2-front-fill"></i>
            <span>Madeni ya Wateja</span>
        </a>
        <?php endif; ?>

        <?php if (Auth::hasRole(['super_admin', 'manager', 'accountant']) || Auth::hasPermission('financial.dashboard') || Auth::hasPermission('expenses.view')): ?>
        <div class="menu-category">Fedha, Matumizi & Faida</div>
        <?php if (Auth::hasRole(['super_admin', 'manager', 'accountant']) || Auth::hasPermission('financial.dashboard')): ?>
        <a href="/index.php?route=financial/dashboard" class="nav-link-custom <?= in_array($currentRoute, ['financial/dashboard', 'financial_dashboard']) ? 'active' : '' ?>">
            <i class="bi bi-graph-up text-warning"></i>
            <span>Dashibodi ya Fedha</span>
        </a>
        <?php endif; ?>
        <?php if (Auth::hasRole(['super_admin', 'manager', 'accountant', 'cashier']) || Auth::hasPermission('expenses.view')): ?>
        <a href="/index.php?route=expenses" class="nav-link-custom <?= in_array($currentRoute, ['expenses', 'expenses/create', 'expenses/show', 'expenses/categories']) ? 'active' : '' ?>">
            <i class="bi bi-wallet2 text-danger"></i>
            <span>Matumizi (Expenses)</span>
        </a>
        <?php endif; ?>
        <?php if (Auth::hasRole(['super_admin', 'manager', 'accountant']) || Auth::hasPermission('financial.profit')): ?>
        <a href="/index.php?route=financial/profit_report" class="nav-link-custom <?= in_array($currentRoute, ['financial/profit_report', 'financial_profit', 'profit_report']) ? 'active' : '' ?>">
            <i class="bi bi-file-earmark-spreadsheet text-success"></i>
            <span>Ripoti ya Mapato (P&L)</span>
        </a>
        <a href="/index.php?route=financial/product_profit" class="nav-link-custom <?= in_array($currentRoute, ['financial/product_profit', 'product_profit']) ? 'active' : '' ?>">
            <i class="bi bi-box-seam text-info"></i>
            <span>Faida kwa Bidhaa</span>
        </a>
        <a href="/index.php?route=financial/category_profit" class="nav-link-custom <?= in_array($currentRoute, ['financial/category_profit', 'category_profit']) ? 'active' : '' ?>">
            <i class="bi bi-grid text-primary"></i>
            <span>Faida kwa Kategoria & Chapa</span>
        </a>
        <a href="/index.php?route=financial/sale_profit" class="nav-link-custom <?= in_array($currentRoute, ['financial/sale_profit', 'sale_profit']) ? 'active' : '' ?>">
            <i class="bi bi-receipt text-warning"></i>
            <span>Faida kwa Mauzo & Cashier</span>
        </a>
        <a href="/index.php?route=financial/expense_analysis" class="nav-link-custom <?= in_array($currentRoute, ['financial/expense_analysis', 'expense_analysis']) ? 'active' : '' ?>">
            <i class="bi bi-pie-chart text-danger"></i>
            <span>Uchambuzi wa Matumizi</span>
        </a>
        <a href="/index.php?route=financial/period_comparison" class="nav-link-custom <?= in_array($currentRoute, ['financial/period_comparison', 'period_comparison']) ? 'active' : '' ?>">
            <i class="bi bi-arrow-left-right text-light"></i>
            <span>Ulinganifu wa Vipindi</span>
        </a>
        <?php endif; ?>
        <?php endif; ?>
        <?php if (Auth::hasRole(['super_admin', 'manager', 'accountant', 'storekeeper', 'cashier']) || Auth::hasPermission('reports.view')): ?>
        <div class="menu-category">Ripoti & Takwimu (Analytics)</div>
        <a href="/index.php?route=reports" class="nav-link-custom <?= in_array($currentRoute, ['reports', 'reports/dashboard']) ? 'active' : '' ?>">
            <i class="bi bi-file-earmark-bar-graph-fill text-primary"></i>
            <span>Kituo cha Ripoti</span>
        </a>
        <?php if (Auth::hasRole(['super_admin', 'manager', 'accountant', 'cashier']) || Auth::hasPermission('reports.sales')): ?>
        <a href="/index.php?route=reports/sales" class="nav-link-custom <?= in_array($currentRoute, ['reports/sales', 'reports/sales_by_product', 'reports/sales_by_category', 'reports/sales_by_cashier', 'reports/sales_by_customer']) ? 'active' : '' ?>">
            <i class="bi bi-cart-check text-success"></i>
            <span>Ripoti za Mauzo</span>
        </a>
        <?php endif; ?>
        <?php if (Auth::hasRole(['super_admin', 'manager', 'accountant']) || Auth::hasPermission('reports.financial')): ?>
        <a href="/index.php?route=reports/payments" class="nav-link-custom <?= in_array($currentRoute, ['reports/payments']) ? 'active' : '' ?>">
            <i class="bi bi-cash-stack text-warning"></i>
            <span>Mikusanyo ya Pesa</span>
        </a>
        <?php endif; ?>
        <?php if (Auth::hasRole(['super_admin', 'manager', 'storekeeper', 'accountant']) || Auth::hasPermission('reports.inventory')): ?>
        <a href="/index.php?route=reports/inventory" class="nav-link-custom <?= in_array($currentRoute, ['reports/inventory']) ? 'active' : '' ?>">
            <i class="bi bi-boxes text-info"></i>
            <span>Ripoti ya Mali & Stoo</span>
        </a>
        <?php endif; ?>
        <?php if (Auth::hasRole(['super_admin', 'manager', 'accountant']) || Auth::hasPermission('reports.debts')): ?>
        <a href="/index.php?route=reports/debts" class="nav-link-custom <?= in_array($currentRoute, ['reports/debts', 'reports/customer_statement']) ? 'active' : '' ?>">
            <i class="bi bi-credit-card-2-front text-danger"></i>
            <span>Madeni & Debt Aging</span>
        </a>
        <?php endif; ?>
        <?php if (Auth::hasRole(['super_admin', 'manager', 'storekeeper']) || Auth::hasPermission('reports.purchases')): ?>
        <a href="/index.php?route=reports/purchases" class="nav-link-custom <?= in_array($currentRoute, ['reports/purchases']) ? 'active' : '' ?>">
            <i class="bi bi-truck text-secondary"></i>
            <span>Manunuzi & Wasambazaji</span>
        </a>
        <?php endif; ?>
        <?php if (Auth::hasRole(['super_admin', 'manager', 'storekeeper']) || Auth::hasPermission('reports.returns')): ?>
        <a href="/index.php?route=reports/returns" class="nav-link-custom <?= in_array($currentRoute, ['reports/returns']) ? 'active' : '' ?>">
            <i class="bi bi-arrow-counterclockwise text-danger"></i>
            <span>Marejesho (Returns)</span>
        </a>
        <?php endif; ?>
        <?php endif; ?>

        <?php if (Auth::hasRole(['super_admin', 'manager', 'cashier']) || Auth::can('online_store.view')): ?>
        <div class="menu-category">Duka la Mtandaoni (E-Commerce)</div>
        <a href="/index.php?route=online_dashboard" class="nav-link-custom <?= $currentRoute === 'online_dashboard' ? 'active' : '' ?>">
            <i class="bi bi-shop text-warning"></i>
            <span>Dashibodi ya Mtandaoni</span>
        </a>
        <a href="/index.php?route=online_orders" class="nav-link-custom <?= in_array($currentRoute, ['online_orders', 'online_order_show']) ? 'active' : '' ?>">
            <i class="bi bi-box-seam text-info"></i>
            <span>Maagizo ya Mtandaoni</span>
        </a>
        <a href="/index.php?route=online_customers" class="nav-link-custom <?= $currentRoute === 'online_customers' ? 'active' : '' ?>">
            <i class="bi bi-people text-primary"></i>
            <span>Wateja wa Mtandaoni</span>
        </a>
        <a href="/index.php?route=online_settings" class="nav-link-custom <?= $currentRoute === 'online_settings' ? 'active' : '' ?>">
            <i class="bi bi-sliders text-light"></i>
            <span>Mipangilio ya Mtandaoni</span>
        </a>
        <a href="/index.php?route=store" target="_blank" class="nav-link-custom">
            <i class="bi bi-box-arrow-up-right text-warning"></i>
            <span>Fungua Duka Mtandaoni</span>
        </a>
        <?php endif; ?>

        <?php if (Auth::can('manage_users') || Auth::hasRole(['super_admin'])): ?>
        <div class="menu-category">Utawala (Admin)</div>
        <a href="/index.php?route=users" class="nav-link-custom <?= in_array($currentRoute, ['users', 'user_create', 'user_edit']) ? 'active' : '' ?>">
            <i class="bi bi-person-gear"></i>
            <span>Wafanyakazi & Akaunti</span>
        </a>
        <?php if (Auth::hasRole(['super_admin'])): ?>
        <a href="/index.php?route=roles_permissions" class="nav-link-custom <?= $currentRoute === 'roles_permissions' ? 'active' : '' ?>">
            <i class="bi bi-shield-lock-fill"></i>
            <span>Haki & Majukumu (RBAC)</span>
        </a>
        <a href="/index.php?route=audit" class="nav-link-custom <?= in_array($currentRoute, ['audit', 'audit_details']) ? 'active' : '' ?>">
            <i class="bi bi-journal-text"></i>
            <span>Audit Trail (Kumbukumbu)</span>
        </a>
        <a href="/index.php?route=security" class="nav-link-custom <?= $currentRoute === 'security' ? 'active' : '' ?>">
            <i class="bi bi-shield-check"></i>
            <span>Usalama wa Mfumo (Security)</span>
        </a>
        <a href="/index.php?route=backups" class="nav-link-custom <?= in_array($currentRoute, ['backups', 'backup_create']) ? 'active' : '' ?>">
            <i class="bi bi-database-down"></i>
            <span>Hifadhi Data (Backups)</span>
        </a>
        <a href="/index.php?route=health" class="nav-link-custom <?= $currentRoute === 'health' ? 'active' : '' ?>">
            <i class="bi bi-activity"></i>
            <span>Afya ya Mfumo (Health)</span>
        </a>
        <a href="/index.php?route=settings" class="nav-link-custom <?= $currentRoute === 'settings' ? 'active' : '' ?>">
            <i class="bi bi-sliders"></i>
            <span>Mipangilio ya Mfumo</span>
        </a>
        <?php endif; ?>
        <?php endif; ?>
    </div>

    <!-- User Profile Bar -->
    <div class="sidebar-user">
        <div class="d-flex align-items-center gap-2">
            <div class="user-avatar-badge">
                <?= strtoupper(substr(Auth::user()['username'] ?? 'U', 0, 1)) ?>
            </div>
            <div class="user-info">
                <div class="fw-bold text-white fs-6" style="line-height:1.2; font-size:13px!important;">
                    <?= htmlspecialchars(substr(Auth::user()['full_name'] ?? 'Mtumiaji', 0, 18)) ?>
                </div>
                <small class="text-white-50" style="font-size:11px;">
                    <?= htmlspecialchars(Auth::user()['role_display'] ?? '') ?>
                </small>
            </div>
        </div>
        <a href="/index.php?route=logout" class="btn btn-sm btn-outline-danger border-0 p-1" title="Toka (Logout)">
            <i class="bi bi-power fs-5"></i>
        </a>
    </div>
</aside>
