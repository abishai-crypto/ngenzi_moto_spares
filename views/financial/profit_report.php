<?php
use App\Core\Helper;
use App\Core\Auth;
?>

<!-- Header -->
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-1 text-dark">
            <i class="bi bi-file-earmark-spreadsheet text-success me-2"></i>Ripoti ya Mapato na Faida (Profit & Loss Statement)
        </h4>
        <p class="text-muted small mb-0">
            Kipindi: <strong class="text-dark"><?= htmlspecialchars($range['label']) ?></strong> (Tarehe: <?= date('d/m/Y', strtotime($range['start_date'])) ?> hadi <?= date('d/m/Y', strtotime($range['end_date'])) ?>)
        </p>
    </div>

    <div class="d-flex flex-wrap gap-2">
        <a href="/index.php?route=financial/dashboard" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-speedometer2 me-1"></i> Dashibodi
        </a>
        <button type="button" onclick="window.print()" class="btn btn-outline-dark btn-sm">
            <i class="bi bi-printer me-1"></i> Chapisha Ripoti
        </button>
    </div>
</div>

<!-- Date Filter -->
<div class="card border-0 shadow-sm rounded-3 mb-4">
    <div class="card-body p-3">
        <form method="GET" action="/index.php" class="row g-2 align-items-center">
            <input type="hidden" name="route" value="financial/profit_report">

            <div class="col-md-3 col-sm-6">
                <select name="period" class="form-select form-select-sm" onchange="if(this.value!='custom') this.form.submit();">
                    <option value="today" <?= ($range['period'] === 'today') ? 'selected' : '' ?>>Leo</option>
                    <option value="yesterday" <?= ($range['period'] === 'yesterday') ? 'selected' : '' ?>>Jana</option>
                    <option value="this_week" <?= ($range['period'] === 'this_week') ? 'selected' : '' ?>>Wiki Hii</option>
                    <option value="this_month" <?= ($range['period'] === 'this_month') ? 'selected' : '' ?>>Mwezi Huu</option>
                    <option value="last_month" <?= ($range['period'] === 'last_month') ? 'selected' : '' ?>>Mwezi Uliopita</option>
                    <option value="this_quarter" <?= ($range['period'] === 'this_quarter') ? 'selected' : '' ?>>Robo Mwaka</option>
                    <option value="this_year" <?= ($range['period'] === 'this_year') ? 'selected' : '' ?>>Mwaka Huu</option>
                    <option value="custom" <?= ($range['period'] === 'custom') ? 'selected' : '' ?>>Muda Maalum (Custom)</option>
                </select>
            </div>

            <div class="col-md-3 col-sm-6">
                <input type="date" name="start_date" class="form-control form-control-sm" value="<?= htmlspecialchars($range['start_date']) ?>">
            </div>

            <div class="col-md-3 col-sm-6">
                <input type="date" name="end_date" class="form-control form-control-sm" value="<?= htmlspecialchars($range['end_date']) ?>">
            </div>

            <div class="col-md-3 col-sm-6 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm w-100"><i class="bi bi-funnel me-1"></i> Sasisha</button>
            </div>
        </form>
    </div>
</div>

<!-- Formal Profit & Loss Statement Card -->
<div class="card border-0 shadow-sm rounded-3 mb-4">
    <div class="card-header bg-white p-4 border-bottom text-center">
        <h4 class="fw-bold text-dark mb-1">NGENZI MOTO SPARE PARTS</h4>
        <h6 class="text-secondary fw-normal mb-1">TAARIFA YA MAPATO, GHARAMA NA FAIDA (PROFIT & LOSS STATEMENT)</h6>
        <div class="small text-muted">Kipindi: <?= date('d/m/Y', strtotime($range['start_date'])) ?> hadi <?= date('d/m/Y', strtotime($range['end_date'])) ?> &bull; Sarafu: TSh (Tanzanian Shilling)</div>
    </div>

    <div class="card-body p-4">
        <div class="table-responsive">
            <table class="table table-bordered align-middle">
                <tbody>
                    <!-- 1. SALES REVENUE SECTION -->
                    <tr class="table-primary">
                        <th colspan="2" class="fw-bold fs-6">1. MAPATO YA MAUZO YA VIPURI (SALES REVENUE)</th>
                        <th class="text-end fw-bold fs-6">Kiasi (TSh)</th>
                    </tr>
                    <tr>
                        <td class="ps-4" colspan="2">Mauzo Ghafi (Gross Sales Subtotal) [<?= number_format($summary['sales_count']) ?> Mauzo]</td>
                        <td class="text-end fw-semibold"><?= number_format($summary['gross_sales'], 2) ?></td>
                    </tr>
                    <tr>
                        <td class="ps-4 text-danger" colspan="2">Toa: Punguzo la Bei Lililotolewa (Sales Discounts)</td>
                        <td class="text-end text-danger fw-semibold">(<?= number_format($summary['total_discounts'], 2) ?>)</td>
                    </tr>
                    <?php if ($summary['refunded_amount'] > 0): ?>
                        <tr>
                            <td class="ps-4 text-warning" colspan="2">Toa: Marejesho na Malipo ya Fedha (Returns & Refunds)</td>
                            <td class="text-end text-warning fw-semibold">(<?= number_format($summary['refunded_amount'], 2) ?>)</td>
                        </tr>
                    <?php endif; ?>
                    <tr class="bg-light fw-bold">
                        <td class="ps-3" colspan="2">MAPATO HALISI YA MAUZO (NET SALES REVENUE)</td>
                        <td class="text-end text-primary fs-5"><?= number_format($summary['net_sales'], 2) ?></td>
                    </tr>

                    <!-- 2. COGS SECTION -->
                    <tr class="table-warning">
                        <th colspan="2" class="fw-bold fs-6">2. GHARAMA ZA UNUNUZI WA VIPURI VILIVYOUZWA (COST OF GOODS SOLD - COGS)</th>
                        <th class="text-end fw-bold fs-6">Kiasi (TSh)</th>
                    </tr>
                    <tr>
                        <td class="ps-4" colspan="2">Gharama ya Vipuri Vilivyouzwa (Historical Unit Cost at Sale) [<?= number_format($summary['total_units_sold']) ?> Vipuri]</td>
                        <td class="text-end fw-semibold text-danger">(<?= number_format($summary['cogs'], 2) ?>)</td>
                    </tr>
                    <tr class="bg-light fw-bold">
                        <td class="ps-3" colspan="2">JUMLA YA GHARAMA YA BIDHAA ZILIZOUZWA (TOTAL COGS)</td>
                        <td class="text-end text-danger fs-5">(<?= number_format($summary['cogs'], 2) ?>)</td>
                    </tr>

                    <!-- 3. GROSS PROFIT -->
                    <tr class="table-info">
                        <th colspan="2" class="fw-bold fs-5">3. FAIDA GHAFI YA BIASHARA (GROSS PROFIT)</th>
                        <th class="text-end fw-bold fs-5 text-dark">TSh <?= number_format($summary['gross_profit'], 2) ?></th>
                    </tr>
                    <tr>
                        <td class="ps-4 text-muted" colspan="2">Uwiano wa Faida Ghafi (Gross Profit Margin %):</td>
                        <td class="text-end fw-bold text-info"><?= number_format($summary['gross_margin_percent'], 2) ?>%</td>
                    </tr>

                    <!-- 4. OPERATING EXPENSES (OPEX) -->
                    <tr class="table-danger">
                        <th colspan="2" class="fw-bold fs-6">4. GHARAMA ZA UENDESHAJI (OPERATING EXPENSES - OPEX)</th>
                        <th class="text-end fw-bold fs-6">Kiasi (TSh)</th>
                    </tr>
                    <?php if (empty($categoryExpenses['categories'])): ?>
                        <tr>
                            <td class="ps-4 text-muted" colspan="2">Hakuna matumizi ya uendeshaji katika kipindi hiki.</td>
                            <td class="text-end">0.00</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($categoryExpenses['categories'] as $ec): ?>
                            <tr>
                                <td class="ps-4" colspan="2"><?= htmlspecialchars($ec['category_name']) ?></td>
                                <td class="text-end"><?= number_format($ec['total_amount'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <tr class="bg-light fw-bold">
                        <td class="ps-3" colspan="2">JUMLA YA GHARAMA ZA UENDESHAJI (TOTAL OPEX)</td>
                        <td class="text-end text-danger fs-5">(<?= number_format($summary['operating_expenses'], 2) ?>)</td>
                    </tr>

                    <!-- 5. NET PROFIT -->
                    <tr class="<?= ($summary['net_profit'] >= 0) ? 'table-success' : 'table-danger' ?>">
                        <th colspan="2" class="fw-bold fs-4">5. FAIDA HALISI (NET PROFIT / LOSS)</th>
                        <th class="text-end fw-bold fs-4 <?= ($summary['net_profit'] >= 0) ? 'text-success' : 'text-danger' ?>">
                            TSh <?= number_format($summary['net_profit'], 2) ?>
                        </th>
                    </tr>
                    <tr>
                        <td class="ps-4 text-muted" colspan="2">Uwiano wa Faida Halisi (Net Profit Margin %):</td>
                        <td class="text-end fw-bold <?= ($summary['net_profit'] >= 0) ? 'text-success' : 'text-danger' ?>">
                            <?= number_format($summary['net_margin_percent'], 2) ?>%
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Financial Position Balance Snapshot -->
        <div class="row g-3 mt-4 pt-3 border-top">
            <div class="col-md-4">
                <div class="p-3 bg-light rounded-3 text-center">
                    <div class="text-muted small">Fedha Zilizopokelewa (Cash Collected)</div>
                    <div class="fw-bold text-dark fs-5">TSh <?= number_format($summary['total_payments_received'], 2) ?></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="p-3 bg-light rounded-3 text-center">
                    <div class="text-muted small">Madeni Yanayodaiwa (Accounts Receivable)</div>
                    <div class="fw-bold text-danger fs-5">TSh <?= number_format($summary['accounts_receivable'], 2) ?></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="p-3 bg-light rounded-3 text-center">
                    <div class="text-muted small">Thamani ya Vipuri Stoo (Inventory Asset Value)</div>
                    <div class="fw-bold text-primary fs-5">TSh <?= number_format($summary['inventory_valuation'], 2) ?></div>
                </div>
            </div>
        </div>
    </div>
</div>
