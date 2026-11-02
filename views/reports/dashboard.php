<?php
use App\Core\Helper;
use App\Core\Auth;
?>

<!-- Header -->
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-1 text-dark">
            <i class="bi bi-file-earmark-bar-graph-fill text-primary me-2"></i>Kituo cha Ripoti & Takwimu za Biashara (Reports & Analytics)
        </h4>
        <p class="text-muted small mb-0">
            Kipindi: <strong class="text-primary"><?= htmlspecialchars($range['label']) ?></strong>
        </p>
    </div>

    <!-- Date Filter Dropdown -->
    <div class="d-flex flex-wrap align-items-center gap-2">
        <div class="btn-group btn-group-sm" role="group">
            <a href="/index.php?route=reports&period=today" class="btn <?= ($range['period'] === 'today') ? 'btn-primary' : 'btn-outline-secondary' ?>">Leo</a>
            <a href="/index.php?route=reports&period=yesterday" class="btn <?= ($range['period'] === 'yesterday') ? 'btn-primary' : 'btn-outline-secondary' ?>">Jana</a>
            <a href="/index.php?route=reports&period=this_week" class="btn <?= ($range['period'] === 'this_week') ? 'btn-primary' : 'btn-outline-secondary' ?>">Wiki Hii</a>
            <a href="/index.php?route=reports&period=this_month" class="btn <?= ($range['period'] === 'this_month') ? 'btn-primary' : 'btn-outline-secondary' ?>">Mwezi Huu</a>
            <a href="/index.php?route=reports&period=last_month" class="btn <?= ($range['period'] === 'last_month') ? 'btn-primary' : 'btn-outline-secondary' ?>">Mwezi Uliopita</a>
            <a href="/index.php?route=reports&period=this_quarter" class="btn <?= ($range['period'] === 'this_quarter') ? 'btn-primary' : 'btn-outline-secondary' ?>">Robo Mwaka</a>
            <a href="/index.php?route=reports&period=this_year" class="btn <?= ($range['period'] === 'this_year') ? 'btn-primary' : 'btn-outline-secondary' ?>">Mwaka Huu</a>
        </div>

        <button class="btn btn-outline-dark btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#customFilter">
            <i class="bi bi-calendar-range me-1"></i> Chagua Tarehe
        </button>
    </div>
</div>

<!-- Custom Filter Collapse -->
<div class="collapse <?= ($range['period'] === 'custom') ? 'show' : '' ?> mb-4" id="customFilter">
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-body p-3">
            <form method="GET" action="/index.php" class="row g-2 align-items-center">
                <input type="hidden" name="route" value="reports">
                <input type="hidden" name="period" value="custom">

                <div class="col-md-4">
                    <label class="form-label small mb-1 fw-semibold">Kuanzia:</label>
                    <input type="date" name="start_date" class="form-control form-control-sm" value="<?= htmlspecialchars($range['start_date']) ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label small mb-1 fw-semibold">Hadi:</label>
                    <input type="date" name="end_date" class="form-control form-control-sm" value="<?= htmlspecialchars($range['end_date']) ?>" required>
                </div>
                <div class="col-md-4 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary btn-sm mt-4"><i class="bi bi-funnel me-1"></i> Tekeleza</button>
                    <a href="/index.php?route=reports" class="btn btn-outline-secondary btn-sm mt-4">Weka Upya</a>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Primary KPI Cards -->
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm rounded-3 h-100 border-start border-primary border-4">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="text-muted small fw-semibold">MAPATO YA MAUZO (NET SALES)</span>
                    <i class="bi bi-cart-check text-primary fs-5"></i>
                </div>
                <h4 class="fw-bold text-dark mb-1">TSh <?= number_format($kpis['net_sales'], 2) ?></h4>
                <div class="small text-muted">Miamala: <strong><?= number_format($kpis['sales_count']) ?></strong> | Vipuri: <strong><?= number_format($kpis['units_sold']) ?></strong></div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm rounded-3 h-100 border-start border-success border-4">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="text-muted small fw-semibold">FAIDA GHAFI (GROSS PROFIT)</span>
                    <span class="badge bg-success-subtle text-success"><?= number_format($kpis['gross_margin'], 1) ?>%</span>
                </div>
                <h4 class="fw-bold text-success mb-1">TSh <?= number_format($kpis['gross_profit'], 2) ?></h4>
                <div class="small text-muted">COGS: TSh <?= number_format($kpis['cogs'], 2) ?></div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm rounded-3 h-100 border-start border-danger border-4">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="text-muted small fw-semibold">MATUMIZI YA UENDESHAJI</span>
                    <i class="bi bi-wallet2 text-danger fs-5"></i>
                </div>
                <h4 class="fw-bold text-danger mb-1">TSh <?= number_format($kpis['operating_expenses'], 2) ?></h4>
                <div class="small text-muted">Faida Halisi: <strong class="<?= ($kpis['net_profit'] >= 0) ? 'text-success' : 'text-danger' ?>">TSh <?= number_format($kpis['net_profit'], 2) ?></strong></div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm rounded-3 h-100 border-start border-warning border-4">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="text-muted small fw-semibold">THAMANI YA STOO (ASSET)</span>
                    <i class="bi bi-box-seam text-warning fs-5"></i>
                </div>
                <h4 class="fw-bold text-dark mb-1">TSh <?= number_format($kpis['stock_value'], 2) ?></h4>
                <div class="small text-muted">Vipuri Vinavyoisha: <strong class="text-danger"><?= number_format($kpis['low_stock_count']) ?></strong></div>
            </div>
        </div>
    </div>
</div>

<!-- 6 Master Report Modules Grid -->
<h5 class="fw-bold text-dark mb-3"><i class="bi bi-grid-fill text-secondary me-2"></i>Moduli za Ripoti za Kina (Detailed Report Modules)</h5>
<div class="row g-3 mb-4">
    <!-- 1. Sales Module -->
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-3 h-100">
            <div class="card-header bg-white p-3 border-bottom d-flex align-items-center gap-2">
                <i class="bi bi-cart-check fs-4 text-primary"></i>
                <div>
                    <h6 class="fw-bold text-dark mb-0">Ripoti za Mauzo (Sales)</h6>
                    <small class="text-muted">Mauzo, bidhaa, kategoria & wauzaji</small>
                </div>
            </div>
            <div class="list-group list-group-flush rounded-bottom-3 small">
                <a href="/index.php?route=reports/sales" class="list-group-item list-group-item-action d-flex justify-content-between">
                    <span><i class="bi bi-receipt me-2 text-muted"></i>Orodha Kamili ya Mauzo</span>
                    <i class="bi bi-chevron-right text-muted"></i>
                </a>
                <a href="/index.php?route=reports/sales_by_product" class="list-group-item list-group-item-action d-flex justify-content-between">
                    <span><i class="bi bi-box-seam me-2 text-muted"></i>Mauzo kwa Kila Bidhaa</span>
                    <i class="bi bi-chevron-right text-muted"></i>
                </a>
                <a href="/index.php?route=reports/sales_by_category" class="list-group-item list-group-item-action d-flex justify-content-between">
                    <span><i class="bi bi-tags me-2 text-muted"></i>Mauzo kwa Kategoria & Chapa</span>
                    <i class="bi bi-chevron-right text-muted"></i>
                </a>
                <a href="/index.php?route=reports/sales_by_cashier" class="list-group-item list-group-item-action d-flex justify-content-between">
                    <span><i class="bi bi-person-badge me-2 text-muted"></i>Mauzo kwa Kila Cashier</span>
                    <i class="bi bi-chevron-right text-muted"></i>
                </a>
                <a href="/index.php?route=reports/vehicle_compatibility" class="list-group-item list-group-item-action d-flex justify-content-between">
                    <span><i class="bi bi-bicycle me-2 text-muted"></i>Mauzo kwa Pikipiki / Bajaji</span>
                    <i class="bi bi-chevron-right text-muted"></i>
                </a>
            </div>
        </div>
    </div>

    <!-- 2. Financial & Profit Module -->
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-3 h-100">
            <div class="card-header bg-white p-3 border-bottom d-flex align-items-center gap-2">
                <i class="bi bi-graph-up-arrow fs-4 text-success"></i>
                <div>
                    <h6 class="fw-bold text-dark mb-0">Ripoti za Fedha & Faida</h6>
                    <small class="text-muted">P&L, mapato, COGS & mikusanyo</small>
                </div>
            </div>
            <div class="list-group list-group-flush rounded-bottom-3 small">
                <a href="/index.php?route=financial/profit_report" class="list-group-item list-group-item-action d-flex justify-content-between">
                    <span><i class="bi bi-file-earmark-spreadsheet me-2 text-muted"></i>Taarifa ya Faida (P&L Statement)</span>
                    <i class="bi bi-chevron-right text-muted"></i>
                </a>
                <a href="/index.php?route=reports/payments" class="list-group-item list-group-item-action d-flex justify-content-between">
                    <span><i class="bi bi-cash-stack me-2 text-muted"></i>Mikusanyo ya Fedha Taslimu</span>
                    <i class="bi bi-chevron-right text-muted"></i>
                </a>
                <a href="/index.php?route=financial/product_profit" class="list-group-item list-group-item-action d-flex justify-content-between">
                    <span><i class="bi bi-percent me-2 text-muted"></i>Faida kwa Kila Kipuri (Margin %)</span>
                    <i class="bi bi-chevron-right text-muted"></i>
                </a>
                <a href="/index.php?route=financial/expense_analysis" class="list-group-item list-group-item-action d-flex justify-content-between">
                    <span><i class="bi bi-pie-chart me-2 text-muted"></i>Uchambuzi wa Matumizi (OPEX)</span>
                    <i class="bi bi-chevron-right text-muted"></i>
                </a>
                <a href="/index.php?route=financial/period_comparison" class="list-group-item list-group-item-action d-flex justify-content-between">
                    <span><i class="bi bi-arrow-left-right me-2 text-muted"></i>Ulinganifu wa Vipindi (Comparison)</span>
                    <i class="bi bi-chevron-right text-muted"></i>
                </a>
            </div>
        </div>
    </div>

    <!-- 3. Inventory & Stock Module -->
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-3 h-100">
            <div class="card-header bg-white p-3 border-bottom d-flex align-items-center gap-2">
                <i class="bi bi-boxes fs-4 text-warning"></i>
                <div>
                    <h6 class="fw-bold text-dark mb-0">Ripoti za Stoo & Mali</h6>
                    <small class="text-muted">Thamani, mienendo & stock velocity</small>
                </div>
            </div>
            <div class="list-group list-group-flush rounded-bottom-3 small">
                <a href="/index.php?route=reports/inventory" class="list-group-item list-group-item-action d-flex justify-content-between">
                    <span><i class="bi bi-box-seam me-2 text-muted"></i>Thamani ya Stoo (Stock Valuation)</span>
                    <i class="bi bi-chevron-right text-muted"></i>
                </a>
                <a href="/index.php?route=reports/inventory&tab=movement" class="list-group-item list-group-item-action d-flex justify-content-between">
                    <span><i class="bi bi-clock-history me-2 text-muted"></i>Mienendo ya Stoo (Stock Movement)</span>
                    <i class="bi bi-chevron-right text-muted"></i>
                </a>
                <a href="/index.php?route=reports/inventory&tab=fast" class="list-group-item list-group-item-action d-flex justify-content-between">
                    <span><i class="bi bi-lightning-charge me-2 text-muted"></i>Vipuri Vinavyotoka Haraka (Fast)</span>
                    <i class="bi bi-chevron-right text-muted"></i>
                </a>
                <a href="/index.php?route=reports/inventory&tab=dead" class="list-group-item list-group-item-action d-flex justify-content-between">
                    <span><i class="bi bi-slash-circle me-2 text-muted"></i>Mzigo Uliolala (Dead Stock)</span>
                    <i class="bi bi-chevron-right text-muted"></i>
                </a>
                <a href="/index.php?route=reports/inventory&tab=reorder" class="list-group-item list-group-item-action d-flex justify-content-between">
                    <span><i class="bi bi-cart-plus me-2 text-muted"></i>Mapendekezo ya Kuagiza (Reorder)</span>
                    <i class="bi bi-chevron-right text-muted"></i>
                </a>
                <a href="/index.php?route=report_product_images" class="list-group-item list-group-item-action d-flex justify-content-between bg-light fw-semibold text-primary">
                    <span><i class="bi bi-images me-2 text-primary"></i>Picha za Vipuri (Image Inventory)</span>
                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill">Offline Ready</span>
                </a>
            </div>
        </div>
    </div>

    <!-- 4. Customers & Debts Module -->
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-3 h-100">
            <div class="card-header bg-white p-3 border-bottom d-flex align-items-center gap-2">
                <i class="bi bi-people fs-4 text-info"></i>
                <div>
                    <h6 class="fw-bold text-dark mb-0">Wateja & Madeni (Customers)</h6>
                    <small class="text-muted">Manunuzi, madeni & customer statement</small>
                </div>
            </div>
            <div class="list-group list-group-flush rounded-bottom-3 small">
                <a href="/index.php?route=reports/sales_by_customer" class="list-group-item list-group-item-action d-flex justify-content-between">
                    <span><i class="bi bi-person-lines-fill me-2 text-muted"></i>Manunuzi ya Wateja</span>
                    <i class="bi bi-chevron-right text-muted"></i>
                </a>
                <a href="/index.php?route=reports/debts" class="list-group-item list-group-item-action d-flex justify-content-between">
                    <span><i class="bi bi-credit-card me-2 text-muted"></i>Uchambuzi wa Madeni (Debt Aging)</span>
                    <i class="bi bi-chevron-right text-muted"></i>
                </a>
                <a href="/index.php?route=reports/customer_statement" class="list-group-item list-group-item-action d-flex justify-content-between">
                    <span><i class="bi bi-file-earmark-person me-2 text-muted"></i>Taarifa ya Mteja (Customer Statement)</span>
                    <i class="bi bi-chevron-right text-muted"></i>
                </a>
            </div>
        </div>
    </div>

    <!-- 5. Purchases & Suppliers Module -->
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-3 h-100">
            <div class="card-header bg-white p-3 border-bottom d-flex align-items-center gap-2">
                <i class="bi bi-truck fs-4 text-secondary"></i>
                <div>
                    <h6 class="fw-bold text-dark mb-0">Manunuzi (Purchases & Suppliers)</h6>
                    <small class="text-muted">Mzigo mpya, wauzaji wa jumla & madeni</small>
                </div>
            </div>
            <div class="list-group list-group-flush rounded-bottom-3 small">
                <a href="/index.php?route=reports/purchases" class="list-group-item list-group-item-action d-flex justify-content-between">
                    <span><i class="bi bi-bag-check me-2 text-muted"></i>Orodha ya Manunuzi ya Mzigo</span>
                    <i class="bi bi-chevron-right text-muted"></i>
                </a>
                <a href="/index.php?route=reports/purchases#suppliers" class="list-group-item list-group-item-action d-flex justify-content-between">
                    <span><i class="bi bi-building me-2 text-muted"></i>Utendaji wa Wasambazaji</span>
                    <i class="bi bi-chevron-right text-muted"></i>
                </a>
            </div>
        </div>
    </div>

    <!-- 6. Returns & Refunds Module -->
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-3 h-100">
            <div class="card-header bg-white p-3 border-bottom d-flex align-items-center gap-2">
                <i class="bi bi-arrow-counterclockwise fs-4 text-danger"></i>
                <div>
                    <h6 class="fw-bold text-dark mb-0">Marejesho (Returns & Refunds)</h6>
                    <small class="text-muted">Vipuri vilivyorejeshwa & return rate %</small>
                </div>
            </div>
            <div class="list-group list-group-flush rounded-bottom-3 small">
                <a href="/index.php?route=reports/returns" class="list-group-item list-group-item-action d-flex justify-content-between">
                    <span><i class="bi bi-arrow-repeat me-2 text-muted"></i>Orodha ya Marejesho</span>
                    <i class="bi bi-chevron-right text-muted"></i>
                </a>
                <a href="/index.php?route=reports/returns#rate" class="list-group-item list-group-item-action d-flex justify-content-between">
                    <span><i class="bi bi-graph-down me-2 text-muted"></i>Kiwango cha Marejesho (Return Rate %)</span>
                    <i class="bi bi-chevron-right text-muted"></i>
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Daily Sales Trend & Fast-Moving Items Snapshot -->
<div class="row g-3">
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm rounded-3 h-100">
            <div class="card-header bg-white p-3 border-bottom d-flex justify-content-between align-items-center">
                <h6 class="fw-bold text-dark mb-0"><i class="bi bi-graph-up text-primary me-2"></i>Mwenendo wa Mauzo ya Kila Siku (Daily Sales Trend)</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Tarehe</th>
                                <th class="text-center">Idadi ya Mauzo</th>
                                <th class="text-end">Mauzo Halisi (TSh)</th>
                                <th class="text-end pe-3">Fedha Taslimu</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($dailyTrend)): ?>
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">Hakuna mauzo katika kipindi hiki.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach (array_slice($dailyTrend, -10) as $dt): ?>
                                    <tr>
                                        <td class="ps-3 fw-semibold"><?= date('d/m/Y', strtotime($dt['sale_day'])) ?></td>
                                        <td class="text-center"><?= number_format($dt['sales_count']) ?></td>
                                        <td class="text-end fw-bold text-primary"><?= number_format($dt['net_sales'], 2) ?></td>
                                        <td class="text-end pe-3 text-success"><?= number_format($dt['cash_collected'], 2) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Fast Moving Spares -->
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm rounded-3 h-100">
            <div class="card-header bg-white p-3 border-bottom d-flex justify-content-between align-items-center">
                <h6 class="fw-bold text-dark mb-0"><i class="bi bi-lightning-charge text-warning me-2"></i>Vipuri Vinavyotoka Haraka Zaidi</h6>
                <a href="/index.php?route=reports/inventory&tab=fast" class="small text-primary text-decoration-none">Tazama Vyote &raquo;</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Kipuri</th>
                                <th class="text-center">Vilivyouzwa</th>
                                <th class="text-end pe-3">Stoo Iliyopo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($fastMoving)): ?>
                                <tr>
                                    <td colspan="3" class="text-center py-4 text-muted">Hakuna rekodi.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($fastMoving as $fm): ?>
                                    <tr>
                                        <td class="ps-3">
                                            <div class="fw-bold text-dark"><?= htmlspecialchars($fm['product_name']) ?></div>
                                            <small class="text-muted font-monospace"><?= htmlspecialchars($fm['sku']) ?></small>
                                        </td>
                                        <td class="text-center fw-bold text-primary"><?= number_format($fm['total_qty_sold']) ?></td>
                                        <td class="text-end pe-3">
                                            <span class="badge bg-light text-dark border"><?= number_format($fm['current_stock']) ?> pcs</span>
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
</div>
