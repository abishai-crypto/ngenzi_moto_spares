<?php
/**
 * NGENZI MOTO SPARE PARTS
 * Advanced Business Intelligence & Decision Support Dashboard (Phase 13)
 * Dar es Salaam, Tanzania
 */

use App\Core\Helper;
use App\Core\Auth;

$roleLabels = [
    'super_admin' => 'Msimamizi Mkuu (Super Admin)',
    'manager'     => 'Meneja wa Biashara (Manager)',
    'accountant'  => 'Mhasibu (Accountant)',
    'storekeeper' => 'Mtunza Stoo (Storekeeper)',
    'cashier'     => 'Muuzaji (Cashier)'
];
$displayRole = $roleLabels[$userRole] ?? ucfirst($userRole);
?>

<div class="container-fluid px-3 px-md-4 py-3">

    <!-- Top Action Bar & Role Banner -->
    <div class="card border-0 shadow-sm rounded-4 mb-4 bg-gradient-light overflow-hidden">
        <div class="card-body p-3 p-md-4">
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <span class="badge bg-primary px-3 py-2 rounded-pill font-monospace"><i class="bi bi-shield-check me-1"></i><?= $displayRole ?></span>
                        <span class="badge bg-light text-dark border px-2 py-1 rounded-pill"><i class="bi bi-clock me-1"></i><?= date('d/m/Y H:i') ?></span>
                    </div>
                    <h3 class="fw-extrabold text-dark mb-1">Habari za leo, <?= htmlspecialchars($user['full_name'] ?? 'Mtumiaji') ?>! 👋</h3>
                    <p class="text-muted small mb-0">Kituo cha Takwimu, Ujasusi wa Biashara (BI) & Maamuzi — Ngenzi Moto Spare Parts, Dar es Salaam.</p>
                </div>

                <!-- Role-Based Quick Action Buttons -->
                <div class="d-flex flex-wrap gap-2">
                    <a href="/index.php?route=pos" class="btn btn-primary btn-sm px-3 d-flex align-items-center gap-2 rounded-pill shadow-sm">
                        <i class="bi bi-cart4 fs-6"></i>
                        <span class="fw-bold">Mashine ya POS</span>
                    </a>

                    <?php if (in_array($userRole, ['super_admin', 'manager'])): ?>
                        <a href="/index.php?route=products/create" class="btn btn-outline-primary btn-sm px-3 rounded-pill">
                            <i class="bi bi-plus-circle me-1"></i>Weka Bidhaa
                        </a>
                        <a href="/index.php?route=purchases/create" class="btn btn-outline-secondary btn-sm px-3 rounded-pill">
                            <i class="bi bi-box-arrow-in-down me-1"></i>Pokea Mzigo
                        </a>
                        <a href="/index.php?route=expenses/create" class="btn btn-outline-danger btn-sm px-3 rounded-pill">
                            <i class="bi bi-receipt me-1"></i>Ingiza Gharama
                        </a>
                    <?php elseif ($userRole === 'cashier'): ?>
                        <a href="/index.php?route=sales" class="btn btn-outline-secondary btn-sm px-3 rounded-pill">
                            <i class="bi bi-journal-text me-1"></i>Historia ya Mauzo
                        </a>
                        <a href="/index.php?route=customers" class="btn btn-outline-secondary btn-sm px-3 rounded-pill">
                            <i class="bi bi-people me-1"></i>Wateja
                        </a>
                    <?php elseif ($userRole === 'storekeeper'): ?>
                        <a href="/index.php?route=inventory" class="btn btn-outline-primary btn-sm px-3 rounded-pill">
                            <i class="bi bi-boxes me-1"></i>Stoo & Mizigo
                        </a>
                        <a href="/index.php?route=purchases/create" class="btn btn-outline-secondary btn-sm px-3 rounded-pill">
                            <i class="bi bi-box-arrow-in-down me-1"></i>Pokea Mzigo
                        </a>
                    <?php elseif ($userRole === 'accountant'): ?>
                        <a href="/index.php?route=payments" class="btn btn-outline-primary btn-sm px-3 rounded-pill">
                            <i class="bi bi-cash-stack me-1"></i>Malipo
                        </a>
                        <a href="/index.php?route=expenses" class="btn btn-outline-danger btn-sm px-3 rounded-pill">
                            <i class="bi bi-receipt me-1"></i>Matumizi
                        </a>
                        <a href="/index.php?route=reports/debts" class="btn btn-outline-warning text-dark btn-sm px-3 rounded-pill">
                            <i class="bi bi-credit-card-2-front me-1"></i>Madeni
                        </a>
                    <?php endif; ?>

                    <?php if ($canExport): ?>
                        <a href="/index.php?route=dashboard/export&period=<?= urlencode($period) ?>&start_date=<?= urlencode($range['start_date']) ?>&end_date=<?= urlencode($range['end_date']) ?>" class="btn btn-outline-success btn-sm px-3 rounded-pill">
                            <i class="bi bi-download me-1"></i>Pakua CSV
                        </a>
                    <?php endif; ?>

                    <?php if ($canManageAlerts): ?>
                        <button type="button" class="btn btn-light border btn-sm px-3 rounded-pill" data-bs-toggle="modal" data-bs-target="#alertSettingsModal">
                            <i class="bi bi-sliders me-1"></i>Vigezo vya Tahadhari
                        </button>
                    <?php endif; ?>

                    <a href="/index.php?route=dashboard&period=<?= urlencode($period) ?>&start_date=<?= urlencode($range['start_date']) ?>&end_date=<?= urlencode($range['end_date']) ?>" class="btn btn-light border btn-sm px-2 rounded-circle" title="Sasisha Dashibodi">
                        <i class="bi bi-arrow-clockwise"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Global Date Range Filter Bar -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-3">
            <div class="d-flex flex-column flex-xl-row justify-content-between align-items-xl-center gap-3">
                <!-- Preset Period Buttons -->
                <div class="d-flex flex-wrap gap-1">
                    <span class="text-muted small align-self-center me-2 fw-semibold"><i class="bi bi-calendar-event me-1"></i>Kipindi:</span>
                    <?php
                    $periods = [
                        'today'        => 'Leo',
                        'yesterday'    => 'Jana',
                        'this_week'    => 'Wiki Hii',
                        'last_week'    => 'Wiki Iliyopita',
                        'this_month'   => 'Mwezi Huu',
                        'last_month'   => 'Mwezi Uliopita',
                        'this_quarter' => 'Robo Mwaka',
                        'this_year'    => 'Mwaka Huu'
                    ];
                    foreach ($periods as $key => $label):
                        $isActive = ($period === $key);
                    ?>
                        <a href="/index.php?route=dashboard&period=<?= $key ?>" class="btn btn-sm <?= $isActive ? 'btn-primary' : 'btn-outline-secondary' ?> rounded-pill px-3 py-1">
                            <?= $label ?>
                        </a>
                    <?php endforeach; ?>
                </div>

                <!-- Custom Date Range Form -->
                <form method="GET" action="/index.php" class="d-flex flex-wrap align-items-center gap-2">
                    <input type="hidden" name="route" value="dashboard">
                    <input type="hidden" name="period" value="custom">
                    <div class="input-group input-group-sm" style="width: auto;">
                        <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-calendar3"></i></span>
                        <input type="date" name="start_date" class="form-control border-start-0" value="<?= htmlspecialchars($range['start_date']) ?>" required>
                    </div>
                    <span class="text-muted small">hadi</span>
                    <div class="input-group input-group-sm" style="width: auto;">
                        <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-calendar3"></i></span>
                        <input type="date" name="end_date" class="form-control border-start-0" value="<?= htmlspecialchars($range['end_date']) ?>" required>
                    </div>
                    <button type="submit" class="btn btn-sm btn-dark rounded-pill px-3">Chuja</button>
                </form>
            </div>

            <!-- Active Period & Comparison Status Indicator -->
            <div class="d-flex flex-wrap justify-content-between align-items-center mt-3 pt-2 border-top small">
                <div class="text-dark">
                    <strong>Kipindi Kinachochambuliwa:</strong> <span class="badge bg-light text-dark border px-2 py-1"><?= htmlspecialchars($range['label']) ?></span>
                </div>
                <div class="text-muted">
                    <strong>Kipindi cha Kulinganisha:</strong> <span class="badge bg-light text-secondary border px-2 py-1"><?= htmlspecialchars($range['prev_label']) ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Centralized Decision Support Alerts -->
    <?php if (!empty($centralizedAlerts)): ?>
        <div class="row g-2 mb-4">
            <div class="col-12">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <h6 class="fw-bold text-dark mb-0"><i class="bi bi-bell-fill text-warning me-2"></i>Kituo cha Tahadhari & Maamuzi (Alert Center)</h6>
                    <span class="badge bg-secondary rounded-pill"><?= count($centralizedAlerts) ?> Tahadhari</span>
                </div>
            </div>
            <?php foreach ($centralizedAlerts as $alt): ?>
                <div class="col-md-6 col-xl-4">
                    <div class="card border-0 shadow-sm rounded-3 h-100 border-start border-4 border-<?= ($alt['severity'] === 'ACTION REQUIRED') ? 'danger' : (($alt['severity'] === 'WARNING') ? 'warning' : 'info') ?>">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <span class="badge bg-<?= $alt['badge_class'] ?> rounded-pill px-2 py-1" style="font-size: 0.75rem;">
                                    <?= htmlspecialchars($alt['severity']) ?>
                                </span>
                                <i class="bi <?= $alt['icon'] ?> fs-5 text-<?= ($alt['severity'] === 'ACTION REQUIRED') ? 'danger' : (($alt['severity'] === 'WARNING') ? 'warning' : 'info') ?>"></i>
                            </div>
                            <h6 class="fw-bold text-dark mb-1 fs-6"><?= htmlspecialchars($alt['title']) ?></h6>
                            <p class="text-muted small mb-2"><?= htmlspecialchars($alt['description']) ?></p>
                            <a href="<?= htmlspecialchars($alt['action_url']) ?>" class="btn btn-sm btn-link p-0 text-decoration-none fw-semibold">
                                <?= htmlspecialchars($alt['action_label']) ?> <i class="bi bi-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- 1. Executive Overview: Primary KPI Cards -->
    <div class="row g-3 mb-4">
        <!-- Card 1: Net Sales -->
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 p-3">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="text-muted small fw-semibold text-uppercase">Mauzo Halisi (Net Sales)</div>
                        <h3 class="fw-bold text-dark my-1"><?= $kpis['net_sales']['formatted_current'] ?></h3>
                        <div class="small text-muted">
                            Kipindi Kilichopita: <?= $kpis['net_sales']['previous'] > 0 ? Helper::formatMoney($kpis['net_sales']['previous']) : 'TSh 0' ?>
                        </div>
                    </div>
                    <div class="rounded-circle p-2 bg-primary-subtle text-primary">
                        <i class="bi bi-cash-stack fs-4"></i>
                    </div>
                </div>
                <div class="mt-3 pt-2 border-top d-flex justify-content-between align-items-center">
                    <span class="badge <?= ($kpis['net_sales']['percentage_change'] >= 0) ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' ?> rounded-pill px-2 py-1">
                        <i class="bi <?= ($kpis['net_sales']['percentage_change'] >= 0) ? 'bi-arrow-up-right' : 'bi-arrow-down-right' ?>"></i>
                        <?= ($kpis['net_sales']['percentage_change'] >= 0 ? '+' : '') . $kpis['net_sales']['percentage_change'] ?>%
                    </span>
                    <span class="small text-muted font-monospace"><?= $kpis['sales_count']['current'] ?> Stakabadhi</span>
                </div>
            </div>
        </div>

        <!-- Card 2: Gross Profit & Margin % (Protected by RBAC) -->
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 p-3">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="text-muted small fw-semibold text-uppercase">Faida Ghafi (Gross Profit)</div>
                        <?php if ($canSeeProfit): ?>
                            <h3 class="fw-bold text-success my-1"><?= $kpis['gross_profit']['formatted_current'] ?></h3>
                            <div class="small text-muted">
                                Margin: <strong><?= number_format($kpis['gross_margin']['current'], 1) ?>%</strong>
                                (Target: <?= number_format($targetMargin['target_margin'], 1) ?>%)
                            </div>
                        <?php else: ?>
                            <h3 class="fw-bold text-muted my-1 font-monospace">••••••••</h3>
                            <div class="small text-muted"><i class="bi bi-lock-fill me-1"></i>Haki Maalumu ya Kifedha</div>
                        <?php endif; ?>
                    </div>
                    <div class="rounded-circle p-2 bg-success-subtle text-success">
                        <i class="bi bi-graph-up-arrow fs-4"></i>
                    </div>
                </div>
                <div class="mt-3 pt-2 border-top d-flex justify-content-between align-items-center">
                    <?php if ($canSeeProfit): ?>
                        <span class="badge <?= ($kpis['gross_profit']['percentage_change'] >= 0) ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' ?> rounded-pill px-2 py-1">
                            <i class="bi <?= ($kpis['gross_profit']['percentage_change'] >= 0) ? 'bi-arrow-up-right' : 'bi-arrow-down-right' ?>"></i>
                            <?= ($kpis['gross_profit']['percentage_change'] >= 0 ? '+' : '') . $kpis['gross_profit']['percentage_change'] ?>%
                        </span>
                        <span class="small text-muted font-monospace">COGS: <?= Helper::formatMoney($kpis['cogs']['current']) ?></span>
                    <?php else: ?>
                        <span class="badge bg-light text-muted border">Imefichwa</span>
                        <span class="small text-muted">Inalindwa</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Card 3: Net Profit (Gross Profit - Expenses) -->
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 p-3">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="text-muted small fw-semibold text-uppercase">Faida Halisi (Net Profit)</div>
                        <?php if ($canSeeProfit): ?>
                            <h3 class="fw-bold <?= ($kpis['net_profit']['current'] >= 0) ? 'text-teal text-primary' : 'text-danger' ?> my-1">
                                <?= $kpis['net_profit']['formatted_current'] ?>
                            </h3>
                            <div class="small text-muted">
                                Matumizi (OPEX): <?= Helper::formatMoney($kpis['operating_expenses']['current']) ?>
                            </div>
                        <?php else: ?>
                            <h3 class="fw-bold text-muted my-1 font-monospace">••••••••</h3>
                            <div class="small text-muted"><i class="bi bi-lock-fill me-1"></i>Haki Maalumu ya Kifedha</div>
                        <?php endif; ?>
                    </div>
                    <div class="rounded-circle p-2 bg-info-subtle text-info">
                        <i class="bi bi-wallet2 fs-4"></i>
                    </div>
                </div>
                <div class="mt-3 pt-2 border-top d-flex justify-content-between align-items-center">
                    <?php if ($canSeeProfit): ?>
                        <span class="badge <?= ($kpis['net_profit']['percentage_change'] >= 0) ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' ?> rounded-pill px-2 py-1">
                            Net Margin: <?= number_format($kpis['net_margin']['current'], 1) ?>%
                        </span>
                        <span class="small text-muted font-monospace"><?= ($kpis['net_profit']['difference'] >= 0 ? '+' : '') . Helper::formatMoney($kpis['net_profit']['difference']) ?></span>
                    <?php else: ?>
                        <span class="badge bg-light text-muted border">Imefichwa</span>
                        <span class="small text-muted">Inalindwa</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Card 4: Cash Collected vs Credit Outstanding -->
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 p-3">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="text-muted small fw-semibold text-uppercase">Pesa Zilizokusanywa (Cash Flow)</div>
                        <h3 class="fw-bold text-dark my-1"><?= $kpis['cash_collected']['formatted_current'] ?></h3>
                        <div class="small text-muted">
                            Kiwango cha Makusanyo: <strong><?= $revVsCash['collection_ratio'] ?>%</strong>
                        </div>
                    </div>
                    <div class="rounded-circle p-2 bg-warning-subtle text-warning">
                        <i class="bi bi-safe fs-4"></i>
                    </div>
                </div>
                <div class="mt-3 pt-2 border-top d-flex justify-content-between align-items-center">
                    <span class="badge bg-warning-subtle text-dark rounded-pill px-2 py-1">
                        Deni Lililobaki: <?= Helper::formatMoney($kpis['credit_outstanding']['current']) ?>
                    </span>
                    <a href="/index.php?route=reports/payments" class="small text-decoration-none">Maelezo <i class="bi bi-chevron-right"></i></a>
                </div>
            </div>
        </div>
    </div>

    <!-- Secondary KPI Row: Inventory, Debts, Purchases & Returns -->
    <div class="row g-3 mb-4">
        <!-- Card 5: Inventory Valuation -->
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small">Thamani ya Mali Stoo</div>
                        <div class="fw-bold text-dark fs-5">
                            <?= $canSeeCost ? Helper::formatMoney($inventoryIntel['total_cost_value']) : '••••••••' ?>
                        </div>
                        <div class="small text-muted"><?= number_format($inventoryIntel['total_stock_units']) ?> pcs (<?= number_format($inventoryIntel['total_products']) ?> bidhaa)</div>
                    </div>
                    <i class="bi bi-boxes fs-3 text-secondary"></i>
                </div>
            </div>
        </div>

        <!-- Card 6: Low Stock & Out of Stock -->
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small">Tahadhari za Stoo</div>
                        <div class="fw-bold fs-5">
                            <span class="text-danger"><?= number_format($inventoryIntel['out_of_stock_count']) ?> Zimekwisha</span>
                            <span class="text-muted"> / </span>
                            <span class="text-warning"><?= number_format($inventoryIntel['low_stock_count']) ?> Chini</span>
                        </div>
                        <a href="/index.php?route=inventory" class="small text-decoration-none">Angalia Orodha ya Stoo</a>
                    </div>
                    <i class="bi bi-exclamation-diamond fs-3 text-warning"></i>
                </div>
            </div>
        </div>

        <!-- Card 7: Total Credit Outstanding across System -->
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small">Jumla ya Madeni ya Wateja</div>
                        <div class="fw-bold text-danger fs-5">
                            <?= Helper::formatMoney($debtAging['total_outstanding'] ?? 0) ?>
                        </div>
                        <div class="small text-muted"><?= count($debtAging['debts'] ?? []) ?> Miamala ya Mkopo</div>
                    </div>
                    <i class="bi bi-credit-card fs-3 text-danger"></i>
                </div>
            </div>
        </div>

        <!-- Card 8: Purchases in Period -->
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small">Manunuzi ya Mzigo (Purchases)</div>
                        <div class="fw-bold text-primary fs-5">
                            <?= Helper::formatMoney($kpis['purchase_value']['current']) ?>
                        </div>
                        <div class="small text-muted"><?= number_format($kpis['purchase_count']['current']) ?> Oda za Wasambazaji</div>
                    </div>
                    <i class="bi bi-truck fs-3 text-primary"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. Charts Row: Interactive Sales Trend & Profit Trend -->
    <div class="row g-3 mb-4">
        <!-- Chart 1: Sales Trend -->
        <div class="col-lg-<?= $canSeeProfit ? '7' : '12' ?>">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white p-3 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold text-dark mb-0">
                        <i class="bi bi-graph-up text-primary me-2"></i>Mwenendo wa Mauzo (Sales Trend)
                    </h6>
                    <span class="badge bg-light text-muted border font-monospace"><?= htmlspecialchars($range['label']) ?></span>
                </div>
                <div class="card-body p-3">
                    <?php if (empty($salesTrend)): ?>
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-bar-chart fs-1 opacity-25"></i>
                            <p class="mt-2 mb-0">Hakuna kumbukumbu za mauzo katika kipindi hiki.</p>
                        </div>
                    <?php else: ?>
                        <div style="height: 280px; position: relative;">
                            <canvas id="salesTrendChart"></canvas>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Chart 2: Profit Breakdown / Trend (Authorized Users) -->
        <?php if ($canSeeProfit): ?>
            <div class="col-lg-5">
                <div class="card border-0 shadow-sm rounded-4 h-100">
                    <div class="card-header bg-white p-3 border-bottom d-flex justify-content-between align-items-center">
                        <h6 class="fw-bold text-dark mb-0">
                            <i class="bi bi-pie-chart text-success me-2"></i>Mchanganuo wa Faida (P&L Trend)
                        </h6>
                        <span class="badge bg-light text-muted border">COGS vs Faida</span>
                    </div>
                    <div class="card-body p-3">
                        <?php if (empty($profitTrend)): ?>
                            <div class="text-center py-5 text-muted">
                                <i class="bi bi-pie-chart fs-1 opacity-25"></i>
                                <p class="mt-2 mb-0">Hakuna data ya kutosha kuonyesha grafu ya faida.</p>
                            </div>
                        <?php else: ?>
                            <div style="height: 280px; position: relative;">
                                <canvas id="profitTrendChart"></canvas>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- 3. Financial Overview & Revenue vs Cash Collections -->
    <div class="row g-3 mb-4">
        <!-- Revenue vs Cash Flow Breakdown -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white p-3 border-bottom">
                    <h6 class="fw-bold text-dark mb-0">
                        <i class="bi bi-arrow-left-right text-primary me-2"></i>Mapato ya Ankara dhidi ya Pesa Taslimu (Revenue vs Cash)
                    </h6>
                </div>
                <div class="card-body p-3">
                    <div class="row g-3 mb-3 text-center">
                        <div class="col-4 border-end">
                            <div class="small text-muted">Mauzo ya Ankara</div>
                            <div class="fw-bold text-dark fs-6"><?= Helper::formatMoney($revVsCash['invoiced_revenue']) ?></div>
                        </div>
                        <div class="col-4 border-end">
                            <div class="small text-muted">Iliyokusanywa (Cash)</div>
                            <div class="fw-bold text-success fs-6"><?= Helper::formatMoney($revVsCash['cash_collected']) ?></div>
                        </div>
                        <div class="col-4">
                            <div class="small text-muted">Mkopo / Deni</div>
                            <div class="fw-bold text-danger fs-6"><?= Helper::formatMoney($revVsCash['period_unpaid_balance']) ?></div>
                        </div>
                    </div>
                    <div class="progress" style="height: 12px;">
                        <div class="progress-bar bg-success" role="progressbar" style="width: <?= min(100, $revVsCash['collection_ratio']) ?>%;" title="Iliyolipwa: <?= $revVsCash['collection_ratio'] ?>%"></div>
                        <div class="progress-bar bg-danger" role="progressbar" style="width: <?= max(0, 100 - $revVsCash['collection_ratio']) ?>%;" title="Deni Lililobaki"></div>
                    </div>
                    <div class="d-flex justify-content-between small text-muted mt-2">
                        <span><i class="bi bi-circle-fill text-success me-1" style="font-size: 8px;"></i>Pesa Iliyokusanywa (<?= $revVsCash['collection_ratio'] ?>%)</span>
                        <span><i class="bi bi-circle-fill text-danger me-1" style="font-size: 8px;"></i>Deni la Kipindi Hiki (<?= round(100 - $revVsCash['collection_ratio'], 1) ?>%)</span>
                    </div>

                    <!-- Payment Methods Distribution Table -->
                    <h6 class="fw-bold text-dark mt-4 mb-2 small text-uppercase">Njia Zilizotumika Kulipa</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Njia ya Malipo</th>
                                    <th class="text-center">Miamala</th>
                                    <th class="text-end">Kiasi (TSh)</th>
                                    <th class="text-end pe-2">Mgao (%)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($paymentMethods)): ?>
                                    <tr><td colspan="4" class="text-center py-2 text-muted small">Hakuna malipo katika kipindi hiki.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($paymentMethods as $pm): ?>
                                        <?php if (!is_array($pm)) continue; ?>
                                        <tr>
                                            <td class="fw-semibold text-dark"><?= htmlspecialchars($pm['payment_method'] ?? 'N/A') ?></td>
                                            <td class="text-center"><?= number_format((int)($pm['transactions_count'] ?? $pm['transaction_count'] ?? 0)) ?></td>
                                            <td class="text-end fw-bold"><?= Helper::formatMoney((float)($pm['total_collected'] ?? $pm['total_amount'] ?? 0)) ?></td>
                                            <td class="text-end pe-2"><?= number_format((float)($pm['percentage'] ?? 0), 1) ?>%</td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Profit Waterfall / Breakdown (Authorized Users) -->
        <?php if ($canSeeProfit && !empty($waterfall)): ?>
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm rounded-4 h-100">
                    <div class="card-header bg-white p-3 border-bottom d-flex justify-content-between align-items-center">
                        <h6 class="fw-bold text-dark mb-0">
                            <i class="bi bi-layers text-teal me-2"></i>Maporomoko ya Faida (Profit Waterfall)
                        </h6>
                        <span class="badge bg-success-subtle text-success">Net Margin: <?= number_format($waterfall['net_margin'], 1) ?>%</span>
                    </div>
                    <div class="card-body p-3">
                        <div class="d-flex flex-column gap-2">
                            <!-- Step 1: Gross Sales -->
                            <div class="p-2 rounded bg-light d-flex justify-content-between align-items-center">
                                <span><i class="bi bi-tag text-primary me-2"></i>Mauzo Kabla ya Punguzo (Gross Sales)</span>
                                <span class="fw-bold font-monospace"><?= Helper::formatMoney($waterfall['gross_sales']) ?></span>
                            </div>
                            <!-- Step 2: Discounts -->
                            <div class="p-2 rounded bg-light d-flex justify-content-between align-items-center text-danger">
                                <span><i class="bi bi-dash-circle text-danger me-2"></i>Punguzo Lililotolewa (Discounts)</span>
                                <span class="fw-bold font-monospace">-<?= Helper::formatMoney($waterfall['discounts']) ?></span>
                            </div>
                            <!-- Step 3: Net Sales -->
                            <div class="p-2 rounded bg-primary-subtle text-primary d-flex justify-content-between align-items-center">
                                <span class="fw-bold"><i class="bi bi-check2-circle me-2"></i>Mapato Halisi ya Mauzo (Net Sales)</span>
                                <span class="fw-extrabold font-monospace"><?= Helper::formatMoney($waterfall['net_sales']) ?></span>
                            </div>
                            <!-- Step 4: COGS -->
                            <div class="p-2 rounded bg-light d-flex justify-content-between align-items-center text-danger">
                                <span><i class="bi bi-cart-x text-danger me-2"></i>Gharama ya Kununulia Mzigo (COGS)</span>
                                <span class="fw-bold font-monospace">-<?= Helper::formatMoney($waterfall['cogs']) ?></span>
                            </div>
                            <!-- Step 5: Gross Profit -->
                            <div class="p-2 rounded bg-success-subtle text-success d-flex justify-content-between align-items-center">
                                <span class="fw-bold"><i class="bi bi-trophy me-2"></i>Faida Ghafi (Gross Profit - <?= number_format($waterfall['gross_margin'], 1) ?>%)</span>
                                <span class="fw-extrabold font-monospace"><?= Helper::formatMoney($waterfall['gross_profit']) ?></span>
                            </div>
                            <!-- Step 6: Expenses -->
                            <div class="p-2 rounded bg-light d-flex justify-content-between align-items-center text-danger">
                                <span><i class="bi bi-receipt text-danger me-2"></i>Gharama za Uendeshaji wa Duka (OPEX)</span>
                                <span class="fw-bold font-monospace">-<?= Helper::formatMoney($waterfall['operating_expenses']) ?></span>
                            </div>
                            <!-- Step 7: Net Profit -->
                            <div class="p-2 rounded bg-dark text-white d-flex justify-content-between align-items-center">
                                <span class="fw-bold"><i class="bi bi-cash-stack text-warning me-2"></i>FAIDA HALISI YA BIASHARA (Net Profit)</span>
                                <span class="fw-bold font-monospace fs-5 text-warning"><?= Helper::formatMoney($waterfall['net_profit']) ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- 4. Motorcycle vs Bajaj Analytics & Vehicle Models -->
    <div class="row g-3 mb-4">
        <!-- Motorcycle vs Bajaj Breakdown -->
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white p-3 border-bottom">
                    <h6 class="fw-bold text-dark mb-0">
                        <i class="bi bi-bicycle text-primary me-2"></i>Pikipiki dhidi ya Bajaji (Motorcycle vs Bajaj BI)
                    </h6>
                    <small class="text-muted">Kulingana na jedwali halisi la utangamano (product compatibility)</small>
                </div>
                <div class="card-body p-3">
                    <?php if (empty($vehicleAnalytics['data'])): ?>
                        <div class="text-center py-4 text-muted">Hakuna mauzo yenye data ya utangamano wa chombo katika kipindi hiki.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Aina ya Chombo</th>
                                        <th class="text-center">Vipande</th>
                                        <th class="text-end">Mapato (TSh)</th>
                                        <th class="text-end">Mgao (%)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($vehicleAnalytics['data'] as $va): ?>
                                        <tr>
                                            <td class="fw-bold">
                                                <?php if ($va['vehicle_type'] === 'Bajaj'): ?>
                                                    <span class="badge bg-warning-subtle text-dark"><i class="bi bi-truck-front me-1"></i>Bajaji</span>
                                                <?php elseif ($va['vehicle_type'] === 'Motorcycle'): ?>
                                                    <span class="badge bg-primary-subtle text-primary"><i class="bi bi-bicycle me-1"></i>Pikipiki</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary-subtle text-secondary">Universal / Zote</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center"><?= number_format($va['quantity_sold']) ?></td>
                                            <td class="text-end fw-semibold"><?= Helper::formatMoney($va['revenue']) ?></td>
                                            <td class="text-end fw-bold"><?= $va['revenue_share'] ?>%</td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Top Vehicle Models Breakdown -->
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white p-3 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold text-dark mb-0">
                        <i class="bi bi-speedometer2 text-info me-2"></i>Mauzo kwa Miundo ya Vyombo (Vehicle Models)
                    </h6>
                    <a href="/index.php?route=reports/vehicle_compatibility" class="small text-decoration-none">Ripoti Kamili</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">Muundo wa Chombo (Model)</th>
                                    <th>Aina</th>
                                    <th class="text-center">Vipuri Tofauti</th>
                                    <th class="text-center">Jumla ya Vipande</th>
                                    <th class="text-end pe-3">Thamani ya Mauzo (TSh)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($topVehicleModels)): ?>
                                    <tr><td colspan="5" class="text-center py-4 text-muted">Hakuna taarifa za miundo ya vyombo katika kipindi hiki.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($topVehicleModels as $vm): ?>
                                        <tr>
                                            <td class="ps-3 fw-bold text-dark">
                                                <?= htmlspecialchars($vm['brand_name']) ?> <?= htmlspecialchars($vm['model_name']) ?>
                                            </td>
                                            <td>
                                                <span class="badge <?= ($vm['vehicle_type'] === 'Bajaj') ? 'bg-warning-subtle text-dark' : 'bg-primary-subtle text-primary' ?> rounded-pill">
                                                    <?= htmlspecialchars($vm['vehicle_type']) ?>
                                                </span>
                                            </td>
                                            <td class="text-center"><?= number_format($vm['compatible_products_sold']) ?></td>
                                            <td class="text-center fw-semibold"><?= number_format($vm['quantity_sold']) ?> pcs</td>
                                            <td class="text-end pe-3 fw-bold text-primary"><?= Helper::formatMoney($vm['revenue']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 5. Top Selling Products & 30% Target Margin Monitor -->
    <div class="row g-3 mb-4">
        <!-- Top Selling Products Table -->
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white p-3 border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <h6 class="fw-bold text-dark mb-0">
                        <i class="bi bi-star-fill text-warning me-2"></i>Bidhaa Zinazoongoza kwa Mauzo
                    </h6>
                    <!-- Sorting Filter -->
                    <div class="btn-group btn-group-sm" role="group">
                        <a href="/index.php?route=dashboard&period=<?= urlencode($period) ?>&start_date=<?= urlencode($range['start_date']) ?>&end_date=<?= urlencode($range['end_date']) ?>&sort_by=quantity" class="btn <?= ($sortBy === 'quantity') ? 'btn-dark' : 'btn-outline-secondary' ?>">Kiasi (Qty)</a>
                        <a href="/index.php?route=dashboard&period=<?= urlencode($period) ?>&start_date=<?= urlencode($range['start_date']) ?>&end_date=<?= urlencode($range['end_date']) ?>&sort_by=revenue" class="btn <?= ($sortBy === 'revenue') ? 'btn-dark' : 'btn-outline-secondary' ?>">Mapato</a>
                        <?php if ($canSeeProfit): ?>
                            <a href="/index.php?route=dashboard&period=<?= urlencode($period) ?>&start_date=<?= urlencode($range['start_date']) ?>&end_date=<?= urlencode($range['end_date']) ?>&sort_by=profit" class="btn <?= ($sortBy === 'profit') ? 'btn-dark' : 'btn-outline-secondary' ?>">Faida</a>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">Bidhaa</th>
                                    <th>Kategoria</th>
                                    <th class="text-center">Iliyouzwa</th>
                                    <th class="text-end">Mapato (TSh)</th>
                                    <?php if ($canSeeProfit): ?>
                                        <th class="text-end pe-3">Margin %</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($topProducts)): ?>
                                    <tr><td colspan="5" class="text-center py-4 text-muted">Hakuna mauzo katika kipindi hiki.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($topProducts as $tp): ?>
                                        <tr>
                                            <td class="ps-3">
                                                <div class="fw-bold text-dark"><?= htmlspecialchars($tp['product_name']) ?></div>
                                                <small class="text-muted font-monospace"><?= htmlspecialchars($tp['sku']) ?></small>
                                            </td>
                                            <td class="small text-muted"><?= htmlspecialchars($tp['category_name'] ?? '—') ?></td>
                                            <td class="text-center fw-bold"><?= number_format($tp['total_qty']) ?></td>
                                            <td class="text-end fw-semibold text-primary"><?= Helper::formatMoney($tp['total_revenue']) ?></td>
                                            <?php if ($canSeeProfit): ?>
                                                <td class="text-end pe-3 fw-bold <?= ($tp['gross_margin'] >= 30) ? 'text-success' : 'text-danger' ?>">
                                                    <?= number_format($tp['gross_margin'], 1) ?>%
                                                </td>
                                            <?php endif; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- 30% Target Margin Monitor -->
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white p-3 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold text-dark mb-0">
                        <i class="bi bi-bullseye text-danger me-2"></i>Ufuatiliaji wa Lengo la Faida (30% Target Margin)
                    </h6>
                    <span class="badge bg-light text-dark border">Lengo: <?= number_format($targetMargin['target_margin'], 1) ?>%</span>
                </div>
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center mb-3 p-3 rounded-3 bg-light">
                        <div>
                            <div class="small text-muted">Margin ya Sasa ya Stoo</div>
                            <h4 class="fw-bold mb-0 <?= ($targetMargin['current_stock_margin'] >= $targetMargin['target_margin']) ? 'text-success' : 'text-danger' ?>">
                                <?= number_format($targetMargin['current_stock_margin'], 1) ?>%
                            </h4>
                        </div>
                        <div class="text-end">
                            <div class="small text-muted">Tofauti na Lengo</div>
                            <span class="badge <?= ($targetMargin['variance'] >= 0) ? 'bg-success' : 'bg-danger' ?> rounded-pill px-3 py-2 fs-6">
                                <?= ($targetMargin['variance'] >= 0 ? '+' : '') . number_format($targetMargin['variance'], 1) ?>%
                            </span>
                        </div>
                    </div>

                    <!-- Products Margin Variance Breakdown -->
                    <h6 class="fw-bold text-dark mb-2 small text-uppercase">Tofauti ya Bei za Bidhaa na Lengo</h6>
                    <div class="table-responsive" style="max-height: 240px; overflow-y: auto;">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Bidhaa</th>
                                    <th class="text-end">Bei ya Kuuza</th>
                                    <th class="text-end">Margin</th>
                                    <th class="text-end pe-2">Tofauti</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($targetMargin['products'] as $p): ?>
                                    <tr>
                                        <td>
                                            <div class="text-truncate" style="max-width: 140px;" title="<?= htmlspecialchars($p['product_name']) ?>">
                                                <?= htmlspecialchars($p['product_name']) ?>
                                            </div>
                                        </td>
                                        <td class="text-end font-monospace small"><?= Helper::formatMoney($p['selling_price']) ?></td>
                                        <td class="text-end fw-bold <?= ($p['current_margin'] >= 30) ? 'text-success' : 'text-danger' ?>">
                                            <?= number_format($p['current_margin'], 1) ?>%
                                        </td>
                                        <td class="text-end pe-2 small fw-semibold <?= ($p['margin_variance'] >= 0) ? 'text-success' : 'text-danger' ?>">
                                            <?= ($p['margin_variance'] >= 0 ? '+' : '') . number_format($p['margin_variance'], 1) ?>%
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 6. Inventory Intelligence: Movements, Dead Stock & Slow Moving -->
    <div class="row g-3 mb-4">
        <!-- Stock Movement Summary -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white p-3 border-bottom">
                    <h6 class="fw-bold text-dark mb-0">
                        <i class="bi bi-arrow-down-up text-primary me-2"></i>Mzunguko wa Stoo (Stock Movement)
                    </h6>
                    <small class="text-muted">Miamala ya stoo katika kipindi hiki</small>
                </div>
                <div class="card-body p-3">
                    <ul class="list-group list-group-flush small">
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span><i class="bi bi-box-arrow-in-down text-success me-2"></i>Mzigo Ulioingia (Purchases / In)</span>
                            <span class="badge bg-success-subtle text-success rounded-pill font-monospace">+<?= number_format($stockMovement['stock_in']) ?> pcs</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span><i class="bi bi-box-arrow-up text-danger me-2"></i>Mzigo Uliouzwa (Sales / Out)</span>
                            <span class="badge bg-danger-subtle text-danger rounded-pill font-monospace">-<?= number_format($stockMovement['stock_out']) ?> pcs</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span><i class="bi bi-arrow-return-left text-warning me-2"></i>Marejesho Stoo (Returns)</span>
                            <span class="badge bg-warning-subtle text-dark rounded-pill font-monospace">+<?= number_format($stockMovement['returns_in']) ?> pcs</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span><i class="bi bi-sliders text-secondary me-2"></i>Marekebisho Halisi (Adjustments)</span>
                            <span class="badge bg-light text-dark border rounded-pill font-monospace"><?= ($stockMovement['adjustments_net'] >= 0 ? '+' : '') . number_format($stockMovement['adjustments_net']) ?> pcs</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0 pt-3 border-top fw-bold">
                            <span><i class="bi bi-boxes text-primary me-2"></i>Jumla ya Vipuri Stoo Sasa</span>
                            <span class="text-primary font-monospace fs-6"><?= number_format($stockMovement['current_stock']) ?> pcs</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Dead Stock Alert Table -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white p-3 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold text-dark mb-0">
                        <i class="bi bi-hourglass-bottom text-danger me-2"></i>Mzigo Uliokwama (Dead Stock: 90+ Days)
                    </h6>
                    <span class="badge bg-danger-subtle text-danger">Hazijauzwa</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">Bidhaa</th>
                                    <th class="text-center">Akiba</th>
                                    <th class="text-end pe-3">Siku Zilizopita</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($deadStock)): ?>
                                    <tr><td colspan="3" class="text-center py-4 text-muted small">Hakuna bidhaa zilizokwama kwa zaidi ya siku 90.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($deadStock as $ds): ?>
                                        <tr>
                                            <td class="ps-3">
                                                <div class="fw-semibold text-dark text-truncate" style="max-width: 150px;"><?= htmlspecialchars($ds['product_name']) ?></div>
                                                <small class="text-muted font-monospace"><?= htmlspecialchars($ds['sku']) ?></small>
                                            </td>
                                            <td class="text-center fw-bold"><?= number_format($ds['current_stock']) ?></td>
                                            <td class="text-end pe-3 text-danger fw-semibold"><?= $ds['days_since_sale'] ?> siku</td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Slow Moving Products Table -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white p-3 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold text-dark mb-0">
                        <i class="bi bi-turtle text-warning me-2"></i>Vipuri Vinavyoenda Taratibu (Slow Moving)
                    </h6>
                    <small class="text-muted">Uuzaji mdogo</small>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">Bidhaa</th>
                                    <th class="text-center">Iliyouzwa (30d)</th>
                                    <th class="text-end pe-3">Stoo Iliyopo</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($slowStock)): ?>
                                    <tr><td colspan="3" class="text-center py-4 text-muted small">Hakuna bidhaa za slow moving kwa sasa.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($slowStock as $ss): ?>
                                        <tr>
                                            <td class="ps-3">
                                                <div class="fw-semibold text-dark text-truncate" style="max-width: 150px;"><?= htmlspecialchars($ss['product_name']) ?></div>
                                                <small class="text-muted font-monospace"><?= htmlspecialchars($ss['sku']) ?></small>
                                            </td>
                                            <td class="text-center fw-bold text-warning"><?= number_format($ss['units_sold_period']) ?> pcs</td>
                                            <td class="text-end pe-3 fw-bold text-dark"><?= number_format($ss['current_stock']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 7. Debts, Overdue Invoices & Recent Activity -->
    <div class="row g-3 mb-4">
        <!-- Overdue Invoices Alert Table -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white p-3 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold text-dark mb-0">
                        <i class="bi bi-clock-history text-danger me-2"></i>Ankara Zilizopitiliza Tarehe ya Ahadi (Overdue Debts)
                    </h6>
                    <a href="/index.php?route=reports/debts" class="small text-decoration-none">Madeni Yote</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">Mteja</th>
                                    <th>Ankara</th>
                                    <th>Ahadi Iliyopita</th>
                                    <th class="text-center">Kundi</th>
                                    <th class="text-end pe-3">Baki ya Deni (TSh)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($overdueDebts)): ?>
                                    <tr><td colspan="5" class="text-center py-4 text-muted small">Hakuna madeni yaliyopitiliza tarehe ya ahadi! Safi sana.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($overdueDebts as $od): ?>
                                        <tr>
                                            <td class="ps-3 fw-bold text-dark">
                                                <?= htmlspecialchars($od['customer_name']) ?>
                                                <div class="small text-muted fw-normal"><?= htmlspecialchars($od['customer_phone'] ?? '') ?></div>
                                            </td>
                                            <td><span class="badge bg-light text-dark border font-monospace"><?= htmlspecialchars($od['invoice_no']) ?></span></td>
                                            <td class="text-danger small fw-semibold"><?= date('d/m/Y', strtotime($od['due_date'])) ?> (<?= $od['days_overdue'] ?>d)</td>
                                            <td class="text-center"><span class="badge bg-danger-subtle text-danger rounded-pill"><?= $od['aging_bucket'] ?></span></td>
                                            <td class="text-end pe-3 fw-bold text-danger"><?= Helper::formatMoney($od['balance_due']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activity Feed -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white p-3 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold text-dark mb-0">
                        <i class="bi bi-activity text-primary me-2"></i>Miamala ya Hivi Karibuni (Live Transaction Feed)
                    </h6>
                    <span class="badge bg-light text-muted border">Live Updates</span>
                </div>
                <div class="card-body p-3">
                    <?php if (empty($recentActivity)): ?>
                        <div class="text-center py-4 text-muted small">Hakuna miamala ya hivi karibuni.</div>
                    <?php else: ?>
                        <div class="timeline">
                            <?php foreach ($recentActivity as $act): ?>
                                <div class="d-flex align-items-start gap-3 mb-3 pb-2 border-bottom">
                                    <div class="p-2 rounded-circle bg-light">
                                        <i class="bi <?= $act['icon'] ?> fs-5"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="fw-bold text-dark"><?= htmlspecialchars($act['description']) ?></span>
                                            <span class="small text-muted"><?= date('H:i, d/m', strtotime($act['created_at'])) ?></span>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center mt-1 small">
                                            <span class="text-muted font-monospace"><i class="bi bi-person me-1"></i><?= htmlspecialchars($act['actor_name'] ?? 'Mfumo') ?> &bull; <?= htmlspecialchars($act['ref_no'] ?? '') ?></span>
                                            <span class="fw-bold text-dark"><?= Helper::formatMoney($act['amount']) ?></span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- Modal: Alert Threshold Configuration (Manager / Admin) -->
<?php if ($canManageAlerts): ?>
    <div class="modal fade" id="alertSettingsModal" tabindex="-1" aria-labelledby="alertSettingsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow">
                <form method="POST" action="/index.php?route=dashboard/update_thresholds">
                    <input type="hidden" name="csrf_token" value="<?= Helper::csrfToken() ?>">
                    <div class="modal-header border-bottom p-3">
                        <h5 class="modal-title fw-bold" id="alertSettingsModalLabel">
                            <i class="bi bi-sliders text-primary me-2"></i>Vigezo vya Tahadhari za Biashara
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Siku za Kutotembea kwa Mzigo ili kuitwa Dead Stock</label>
                            <div class="input-group input-group-sm">
                                <input type="number" name="dead_stock_days" class="form-control" value="<?= htmlspecialchars($thresholds['dead_stock_days']) ?>" min="15" max="365" required>
                                <span class="input-group-text">Siku</span>
                            </div>
                            <small class="text-muted">Kiwango cha kawaida: siku 90 bila mauzo.</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Kipindi cha Tathmini ya Mzigo Unaotembea Taratibu (Slow Stock)</label>
                            <div class="input-group input-group-sm">
                                <input type="number" name="slow_stock_days" class="form-control" value="<?= htmlspecialchars($thresholds['slow_stock_days']) ?>" min="7" max="180" required>
                                <span class="input-group-text">Siku</span>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Kiwango cha Juu cha Mauzo kwa Mzigo wa Slow Moving</label>
                            <div class="input-group input-group-sm">
                                <input type="number" name="slow_stock_threshold" class="form-control" value="<?= htmlspecialchars($thresholds['slow_stock_threshold']) ?>" min="1" max="10" required>
                                <span class="input-group-text">Vipande</span>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Siku za Deni Kupita Tarehe ya Ahadi ili kuwa Tahadhari (Overdue)</label>
                            <div class="input-group input-group-sm">
                                <input type="number" name="overdue_debt_days" class="form-control" value="<?= htmlspecialchars($thresholds['overdue_debt_days']) ?>" min="0" max="30" required>
                                <span class="input-group-text">Siku</span>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Kiwango cha Juu cha Asilimia ya Marejesho (Return Rate %)</label>
                            <div class="input-group input-group-sm">
                                <input type="number" name="high_return_threshold" class="form-control" step="0.5" value="<?= htmlspecialchars($thresholds['high_return_threshold']) ?>" min="1" max="50" required>
                                <span class="input-group-text">%</span>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-top p-3">
                        <button type="button" class="btn btn-light border btn-sm px-3 rounded-pill" data-bs-dismiss="modal">Funga</button>
                        <button type="submit" class="btn btn-primary btn-sm px-4 rounded-pill fw-bold">Hifadhi Mabadiliko</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Chart.js Visualization Scripts -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    <?php if (!empty($salesTrend)): ?>
        // 1. Sales Trend Chart
        const salesCtx = document.getElementById('salesTrendChart');
        if (salesCtx) {
            const salesLabels = <?= json_encode(array_column($salesTrend, 'period_label')) ?>;
            const salesData = <?= json_encode(array_map(fn($r) => (float)$r['net_sales'], $salesTrend)) ?>;
            const ordersData = <?= json_encode(array_map(fn($r) => (int)$r['sales_count'], $salesTrend)) ?>;

            new Chart(salesCtx, {
                type: 'line',
                data: {
                    labels: salesLabels,
                    datasets: [
                        {
                            label: 'Mauzo Halisi (TSh)',
                            data: salesData,
                            borderColor: '#0d6efd',
                            backgroundColor: 'rgba(13, 110, 253, 0.08)',
                            borderWidth: 2,
                            fill: true,
                            tension: 0.3,
                            yAxisID: 'y'
                        },
                        {
                            label: 'Idadi ya Stakabadhi',
                            data: ordersData,
                            borderColor: '#6c757d',
                            backgroundColor: 'transparent',
                            borderWidth: 1.5,
                            borderDash: [4, 4],
                            pointRadius: 3,
                            yAxisID: 'y1'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false
                    },
                    scales: {
                        y: {
                            type: 'linear',
                            display: true,
                            position: 'left',
                            ticks: {
                                callback: function(val) {
                                    return (val >= 1000000) ? (val / 1000000).toFixed(1) + 'M' : ((val >= 1000) ? (val / 1000).toFixed(0) + 'k' : val);
                                }
                            }
                        },
                        y1: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            grid: {
                                drawOnChartArea: false
                            },
                            ticks: {
                                precision: 0
                            }
                        }
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let label = context.dataset.label || '';
                                    if (label) label += ': ';
                                    if (context.datasetIndex === 0) {
                                        label += new Intl.NumberFormat('en-US').format(context.parsed.y) + ' TSh';
                                    } else {
                                        label += context.parsed.y + ' mauzo';
                                    }
                                    return label;
                                }
                            }
                        }
                    }
                }
            });
        }
    <?php endif; ?>

    <?php if ($canSeeProfit && !empty($profitTrend)): ?>
        // 2. Profit Trend Chart
        const profitCtx = document.getElementById('profitTrendChart');
        if (profitCtx) {
            const profitLabels = <?= json_encode(array_column($profitTrend, 'period_label')) ?>;
            const grossProfitData = <?= json_encode(array_map(fn($r) => (float)$r['gross_profit'], $profitTrend)) ?>;
            const expensesData = <?= json_encode(array_map(fn($r) => (float)$r['expenses'], $profitTrend)) ?>;
            const netProfitData = <?= json_encode(array_map(fn($r) => (float)$r['net_profit'], $profitTrend)) ?>;

            new Chart(profitCtx, {
                type: 'bar',
                data: {
                    labels: profitLabels,
                    datasets: [
                        {
                            label: 'Faida Ghafi',
                            data: grossProfitData,
                            backgroundColor: '#198754'
                        },
                        {
                            label: 'Matumizi (OPEX)',
                            data: expensesData,
                            backgroundColor: '#dc3545'
                        },
                        {
                            label: 'Faida Halisi',
                            data: netProfitData,
                            backgroundColor: '#0dcaf0'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            ticks: {
                                callback: function(val) {
                                    return (val >= 1000000) ? (val / 1000000).toFixed(1) + 'M' : ((val >= 1000) ? (val / 1000).toFixed(0) + 'k' : val);
                                }
                            }
                        }
                    }
                }
            });
        }
    <?php endif; ?>
});
</script>
