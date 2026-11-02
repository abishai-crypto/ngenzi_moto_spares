<?php
use App\Core\Helper;
use App\Core\Auth;
?>

<!-- Header -->
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-1 text-dark">
            <i class="bi bi-graph-up text-primary me-2"></i>Dashibodi ya Fedha na Faida (Financial Dashboard)
        </h4>
        <p class="text-muted small mb-0">
            Kipindi: <strong class="text-primary"><?= htmlspecialchars($range['label']) ?></strong>
        </p>
    </div>

    <!-- Date Range Dropdown & Custom Range Filter -->
    <div class="d-flex flex-wrap align-items-center gap-2">
        <div class="btn-group btn-group-sm" role="group">
            <a href="/index.php?route=financial/dashboard&period=today" class="btn <?= ($range['period'] === 'today') ? 'btn-primary' : 'btn-outline-secondary' ?>">Leo</a>
            <a href="/index.php?route=financial/dashboard&period=yesterday" class="btn <?= ($range['period'] === 'yesterday') ? 'btn-primary' : 'btn-outline-secondary' ?>">Jana</a>
            <a href="/index.php?route=financial/dashboard&period=this_week" class="btn <?= ($range['period'] === 'this_week') ? 'btn-primary' : 'btn-outline-secondary' ?>">Wiki Hii</a>
            <a href="/index.php?route=financial/dashboard&period=this_month" class="btn <?= ($range['period'] === 'this_month') ? 'btn-primary' : 'btn-outline-secondary' ?>">Mwezi Huu</a>
            <a href="/index.php?route=financial/dashboard&period=last_month" class="btn <?= ($range['period'] === 'last_month') ? 'btn-primary' : 'btn-outline-secondary' ?>">Mwezi Uliopita</a>
            <a href="/index.php?route=financial/dashboard&period=this_quarter" class="btn <?= ($range['period'] === 'this_quarter') ? 'btn-primary' : 'btn-outline-secondary' ?>">Robo Mwaka</a>
            <a href="/index.php?route=financial/dashboard&period=this_year" class="btn <?= ($range['period'] === 'this_year') ? 'btn-primary' : 'btn-outline-secondary' ?>">Mwaka Huu</a>
        </div>

        <button class="btn btn-outline-dark btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#customFilter">
            <i class="bi bi-calendar-range me-1"></i> Chagua Tarehe
        </button>
    </div>
</div>

<!-- Custom Date Filter (Collapsible) -->
<div class="collapse <?= ($range['period'] === 'custom') ? 'show' : '' ?> mb-4" id="customFilter">
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-body p-3">
            <form method="GET" action="/index.php" class="row g-2 align-items-center">
                <input type="hidden" name="route" value="financial/dashboard">
                <input type="hidden" name="period" value="custom">

                <div class="col-md-4">
                    <label class="form-label small mb-1 fw-semibold">Kuanzia Tarehe:</label>
                    <input type="date" name="start_date" class="form-control form-control-sm" value="<?= htmlspecialchars($range['start_date']) ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label small mb-1 fw-semibold">Hadi Tarehe:</label>
                    <input type="date" name="end_date" class="form-control form-control-sm" value="<?= htmlspecialchars($range['end_date']) ?>" required>
                </div>
                <div class="col-md-4 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary btn-sm mt-4"><i class="bi bi-funnel me-1"></i> Tekeleza</button>
                    <a href="/index.php?route=financial/dashboard" class="btn btn-outline-secondary btn-sm mt-4">Weka Upya</a>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Primary Financial KPIs (6 Core Cards) -->
<div class="row g-3 mb-4">
    <!-- Revenue Card -->
    <div class="col-xl-4 col-md-6">
        <div class="card border-0 shadow-sm rounded-3 h-100 border-start border-primary border-4">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted small fw-semibold">MAPATO HALISI (NET SALES)</span>
                    <span class="badge bg-primary-subtle text-primary rounded-pill"><i class="bi bi-cart-check"></i> <?= number_format($summary['sales_count']) ?> Mauzo</span>
                </div>
                <h3 class="fw-bold text-dark mb-1">TSh <?= number_format($summary['net_sales'], 2) ?></h3>
                <div class="small text-muted">
                    Mauzo ghafi: TSh <?= number_format($summary['gross_sales'], 2) ?> &bull; Punguzo: TSh <?= number_format($summary['total_discounts'], 2) ?>
                </div>
            </div>
        </div>
    </div>

    <!-- COGS Card -->
    <div class="col-xl-4 col-md-6">
        <div class="card border-0 shadow-sm rounded-3 h-100 border-start border-warning border-4">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted small fw-semibold">GHARAMA YA BIDHAA (COGS)</span>
                    <span class="badge bg-warning-subtle text-warning rounded-pill"><i class="bi bi-box-seam"></i> <?= number_format($summary['total_units_sold']) ?> Vipuri</span>
                </div>
                <h3 class="fw-bold text-dark mb-1">TSh <?= number_format($summary['cogs'], 2) ?></h3>
                <div class="small text-muted">
                    Gharama halisi ya manunuzi ya vipuri vilivyouzwa
                </div>
            </div>
        </div>
    </div>

    <!-- Gross Profit Card -->
    <div class="col-xl-4 col-md-6">
        <div class="card border-0 shadow-sm rounded-3 h-100 border-start border-info border-4">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted small fw-semibold">FAIDA GHAFI (GROSS PROFIT)</span>
                    <span class="badge bg-info-subtle text-info rounded-pill fw-bold"><?= number_format($summary['gross_margin_percent'], 2) ?>% Margin</span>
                </div>
                <h3 class="fw-bold text-info mb-1">TSh <?= number_format($summary['gross_profit'], 2) ?></h3>
                <div class="small text-muted">
                    Mapato Halisi - COGS
                </div>
            </div>
        </div>
    </div>

    <!-- Operating Expenses Card -->
    <div class="col-xl-4 col-md-6">
        <div class="card border-0 shadow-sm rounded-3 h-100 border-start border-danger border-4">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted small fw-semibold">MATUMIZI YA UENDESHAJI (OPEX)</span>
                    <span class="badge bg-danger-subtle text-danger rounded-pill"><i class="bi bi-wallet2"></i> <?= number_format($summary['expense_count']) ?> Matumizi</span>
                </div>
                <h3 class="fw-bold text-danger mb-1">TSh <?= number_format($summary['operating_expenses'], 2) ?></h3>
                <div class="small text-muted">
                    Kodi, umeme, mishahara, usafiri na matumizi mengineyo
                </div>
            </div>
        </div>
    </div>

    <!-- Net Profit Card -->
    <div class="col-xl-4 col-md-6">
        <div class="card border-0 shadow-sm rounded-3 h-100 border-start <?= ($summary['net_profit'] >= 0) ? 'border-success' : 'border-danger' ?> border-4">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted small fw-semibold">FAIDA HALISI (NET PROFIT)</span>
                    <span class="badge <?= ($summary['net_margin_percent'] >= 0) ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' ?> rounded-pill fw-bold">
                        <?= number_format($summary['net_margin_percent'], 2) ?>% Margin
                    </span>
                </div>
                <h3 class="fw-bold <?= ($summary['net_profit'] >= 0) ? 'text-success' : 'text-danger' ?> mb-1">
                    TSh <?= number_format($summary['net_profit'], 2) ?>
                </h3>
                <div class="small text-muted">
                    Faida Ghafi - Gharama za Uendeshaji
                </div>
            </div>
        </div>
    </div>

    <!-- Cash vs Debt Card -->
    <div class="col-xl-4 col-md-6">
        <div class="card border-0 shadow-sm rounded-3 h-100 border-start border-secondary border-4">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted small fw-semibold">FEDHA ZILIZOPASWA KUKUSANYWA</span>
                    <span class="badge bg-secondary-subtle text-secondary rounded-pill"><i class="bi bi-cash-stack"></i> Mzunguko</span>
                </div>
                <h4 class="fw-bold text-dark mb-1">TSh <?= number_format($summary['total_payments_received'], 2) ?></h4>
                <div class="small text-muted">
                    Madeni ya Wateja Yaliyopo: <strong class="text-danger">TSh <?= number_format($summary['accounts_receivable'], 2) ?></strong>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Secondary Insights (Inventory Valuation & Navigation Shortcuts) -->
<div class="row g-3 mb-4">
    <div class="col-lg-8">
        <!-- Monthly Trend Overview -->
        <div class="card border-0 shadow-sm rounded-3 h-100">
            <div class="card-header bg-white p-3 border-bottom d-flex justify-content-between align-items-center">
                <h6 class="fw-bold text-dark mb-0"><i class="bi bi-bar-chart-line text-primary me-2"></i>Mwenendo wa Miezi 6 (Revenue vs COGS vs Net Profit)</h6>
                <a href="/index.php?route=financial/period_comparison" class="btn btn-outline-primary btn-sm">Linganisha Vipindi &raquo;</a>
            </div>
            <div class="card-body p-3">
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Mwezi</th>
                                <th class="text-end">Mapato (Sales)</th>
                                <th class="text-end">COGS</th>
                                <th class="text-end">Faida Ghafi</th>
                                <th class="text-end">Matumizi</th>
                                <th class="text-end">Faida Halisi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($trend as $t): ?>
                                <tr>
                                    <td class="fw-bold"><?= htmlspecialchars($t['month']) ?></td>
                                    <td class="text-end"><?= number_format($t['revenue'], 2) ?></td>
                                    <td class="text-end text-muted"><?= number_format($t['cogs'], 2) ?></td>
                                    <td class="text-end text-info fw-semibold"><?= number_format($t['gross_profit'], 2) ?></td>
                                    <td class="text-end text-danger"><?= number_format($t['expenses'], 2) ?></td>
                                    <td class="text-end fw-bold <?= ($t['net_profit'] >= 0) ? 'text-success' : 'text-danger' ?>">
                                        <?= number_format($t['net_profit'], 2) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Navigation Links & Inventory Value -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-3 mb-3 bg-dark text-white p-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="small text-light">THAMANI YA STOO (INVENTORY VALUATION)</span>
                <i class="bi bi-box-seam fs-4 text-warning"></i>
            </div>
            <h3 class="fw-bold text-warning mb-1">TSh <?= number_format($summary['inventory_valuation'], 2) ?></h3>
            <div class="small text-light">
                Jumla ya Vipuri Stoo: <strong><?= number_format($summary['total_stock_units']) ?> pcs</strong> (Rasilimali / Asset)
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header bg-white p-3 border-bottom">
                <h6 class="fw-bold text-dark mb-0"><i class="bi bi-link-45deg me-1"></i>Ripoti za Kifedha</h6>
            </div>
            <div class="list-group list-group-flush rounded-bottom-3">
                <a href="/index.php?route=financial/profit_report" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-file-earmark-spreadsheet text-success me-2"></i>Ripoti Kamili ya Mapato (P&L)</span>
                    <i class="bi bi-chevron-right small text-muted"></i>
                </a>
                <a href="/index.php?route=financial/product_profit" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-tags text-primary me-2"></i>Faida kwa Kila Kipuri (Product Profit)</span>
                    <i class="bi bi-chevron-right small text-muted"></i>
                </a>
                <a href="/index.php?route=financial/category_profit" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-grid text-warning me-2"></i>Faida kwa Kategoria & Chapa</span>
                    <i class="bi bi-chevron-right small text-muted"></i>
                </a>
                <a href="/index.php?route=financial/sale_profit" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-receipt text-info me-2"></i>Faida kwa Mauzo, Cashier & Wateja</span>
                    <i class="bi bi-chevron-right small text-muted"></i>
                </a>
                <a href="/index.php?route=financial/expense_analysis" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-pie-chart text-danger me-2"></i>Uchambuzi wa Matumizi (OPEX)</span>
                    <i class="bi bi-chevron-right small text-muted"></i>
                </a>
                <a href="/index.php?route=financial/period_comparison" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-arrow-left-right text-secondary me-2"></i>Ulinganifu wa Vipindi (Comparison)</span>
                    <i class="bi bi-chevron-right small text-muted"></i>
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Row: Top Profitable Products & Expense Distribution -->
<div class="row g-3">
    <!-- Top 5 Most Profitable Products -->
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm rounded-3 h-100">
            <div class="card-header bg-white p-3 border-bottom d-flex justify-content-between align-items-center">
                <h6 class="fw-bold text-dark mb-0"><i class="bi bi-trophy text-warning me-2"></i>Vipuri Vinavyozalisha Faida Zaidi (Top Profitable Spares)</h6>
                <a href="/index.php?route=financial/product_profit" class="small text-primary text-decoration-none">Tazama Vyote &raquo;</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Kipuri</th>
                                <th class="text-center">Vilivyouzwa</th>
                                <th class="text-end">Mapato</th>
                                <th class="text-end">COGS</th>
                                <th class="text-end pe-3">Faida Ghafi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($topProducts)): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">Hakuna mauzo yaliyofanyika katika kipindi hiki.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($topProducts as $tp): ?>
                                    <tr>
                                        <td class="ps-3">
                                            <div class="fw-bold text-dark"><?= htmlspecialchars($tp['product_name']) ?></div>
                                            <small class="text-muted font-monospace"><?= htmlspecialchars($tp['sku']) ?></small>
                                        </td>
                                        <td class="text-center fw-semibold"><?= number_format($tp['total_qty_sold']) ?></td>
                                        <td class="text-end"><?= number_format($tp['total_revenue'], 2) ?></td>
                                        <td class="text-end text-muted"><?= number_format($tp['total_cogs'], 2) ?></td>
                                        <td class="text-end pe-3">
                                            <div class="fw-bold text-success"><?= number_format($tp['gross_profit'], 2) ?></div>
                                            <span class="badge bg-success-subtle text-success small"><?= number_format($tp['margin_percent'], 1) ?>%</span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Expenses by Category in Period -->
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm rounded-3 h-100">
            <div class="card-header bg-white p-3 border-bottom d-flex justify-content-between align-items-center">
                <h6 class="fw-bold text-dark mb-0"><i class="bi bi-pie-chart text-danger me-2"></i>Mgawanyo wa Matumizi (Expenses by Category)</h6>
                <a href="/index.php?route=financial/expense_analysis" class="small text-danger text-decoration-none">Ripoti Kamili &raquo;</a>
            </div>
            <div class="card-body p-3">
                <?php if (empty($categoryExpenses['categories'])): ?>
                    <div class="text-center py-4 text-muted">Hakuna matumizi yaliyorekodiwa katika kipindi hiki.</div>
                <?php else: ?>
                    <div class="mb-3">
                        <?php foreach (array_slice($categoryExpenses['categories'], 0, 6) as $ec): ?>
                            <div class="mb-2">
                                <div class="d-flex justify-content-between small mb-1">
                                    <span class="fw-semibold text-dark"><?= htmlspecialchars($ec['category_name']) ?></span>
                                    <span><strong>TSh <?= number_format($ec['total_amount'], 2) ?></strong> (<?= $ec['percentage'] ?>%)</span>
                                </div>
                                <div class="progress" style="height: 6px;">
                                    <div class="progress-bar bg-danger" role="progressbar" style="width: <?= $ec['percentage'] ?>%"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
