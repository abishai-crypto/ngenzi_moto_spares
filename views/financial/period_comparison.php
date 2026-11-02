<?php
use App\Core\Helper;
use App\Core\Auth;
?>

<!-- Header -->
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-1 text-dark">
            <i class="bi bi-arrow-left-right text-primary me-2"></i>Ulinganifu wa Vipindi vya Kifedha (Period Comparison)
        </h4>
        <p class="text-muted small mb-0">
            Kipindi cha Sasa: <strong class="text-primary"><?= htmlspecialchars($range['label']) ?></strong> dhidi ya Kipindi Kilichopita: <strong class="text-secondary"><?= htmlspecialchars($range['prev_label']) ?></strong>
        </p>
    </div>

    <div class="d-flex flex-wrap gap-2">
        <a href="/index.php?route=financial/dashboard" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-speedometer2 me-1"></i> Dashibodi
        </a>
        <a href="/index.php?route=financial/profit_report" class="btn btn-outline-success btn-sm">
            <i class="bi bi-file-earmark-spreadsheet me-1"></i> Taarifa ya P&L
        </a>
    </div>
</div>

<!-- Date Filter -->
<div class="card border-0 shadow-sm rounded-3 mb-4">
    <div class="card-body p-3">
        <form method="GET" action="/index.php" class="row g-2 align-items-center">
            <input type="hidden" name="route" value="financial/period_comparison">

            <div class="col-md-4 col-sm-6">
                <select name="period" class="form-select form-select-sm" onchange="if(this.value!='custom') this.form.submit();">
                    <option value="today" <?= ($range['period'] === 'today') ? 'selected' : '' ?>>Leo vs Jana</option>
                    <option value="this_week" <?= ($range['period'] === 'this_week') ? 'selected' : '' ?>>Wiki Hii vs Wiki Iliyopita</option>
                    <option value="this_month" <?= ($range['period'] === 'this_month') ? 'selected' : '' ?>>Mwezi Huu vs Mwezi Uliopita</option>
                    <option value="this_quarter" <?= ($range['period'] === 'this_quarter') ? 'selected' : '' ?>>Robo Mwaka Hii vs Robo Iliyopita</option>
                    <option value="this_year" <?= ($range['period'] === 'this_year') ? 'selected' : '' ?>>Mwaka Huu vs Mwaka Jana</option>
                    <option value="custom" <?= ($range['period'] === 'custom') ? 'selected' : '' ?>>Custom</option>
                </select>
            </div>

            <div class="col-md-3 col-sm-6">
                <input type="date" name="start_date" class="form-control form-control-sm" value="<?= htmlspecialchars($range['start_date']) ?>">
            </div>

            <div class="col-md-3 col-sm-6">
                <input type="date" name="end_date" class="form-control form-control-sm" value="<?= htmlspecialchars($range['end_date']) ?>">
            </div>

            <div class="col-md-2 col-sm-6 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm w-100"><i class="bi bi-funnel me-1"></i> Linganisha</button>
            </div>
        </form>
    </div>
</div>

<!-- Comparison Metric Cards -->
<div class="card border-0 shadow-sm rounded-3 mb-4">
    <div class="card-header bg-white p-3 border-bottom">
        <h6 class="fw-bold text-dark mb-0"><i class="bi bi-table me-2"></i>Ulinganifu wa Vipimo Vikuu vya Fedha</h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Kipimo cha Fedha (Financial Metric)</th>
                        <th class="text-end">Kipindi cha Sasa (Current)</th>
                        <th class="text-end">Kipindi Kilichopita (Previous)</th>
                        <th class="text-end">Tofauti ya Nambari (Difference)</th>
                        <th class="text-end pe-3">Mabadiliko Asilimia (%)</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Net Sales -->
                    <tr>
                        <td class="ps-3 fw-bold text-dark">
                            <i class="bi bi-cart-check text-primary me-2"></i>Mapato Halisi (Net Sales)
                        </td>
                        <td class="text-end fw-bold text-primary">TSh <?= number_format($comparison['net_sales']['current'], 2) ?></td>
                        <td class="text-end text-muted">TSh <?= number_format($comparison['net_sales']['previous'], 2) ?></td>
                        <td class="text-end fw-semibold <?= ($comparison['net_sales']['diff'] >= 0) ? 'text-success' : 'text-danger' ?>">
                            <?= ($comparison['net_sales']['diff'] >= 0) ? '+' : '' ?><?= number_format($comparison['net_sales']['diff'], 2) ?>
                        </td>
                        <td class="text-end pe-3">
                            <span class="badge <?= ($comparison['net_sales']['diff'] >= 0) ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' ?> fw-bold">
                                <i class="bi bi-arrow-<?= ($comparison['net_sales']['diff'] >= 0) ? 'up' : 'down' ?> me-1"></i><?= number_format(abs($comparison['net_sales']['percentage']), 2) ?>%
                            </span>
                        </td>
                    </tr>

                    <!-- COGS -->
                    <tr>
                        <td class="ps-3 fw-bold text-dark">
                            <i class="bi bi-box-seam text-warning me-2"></i>Gharama ya Bidhaa (COGS)
                        </td>
                        <td class="text-end text-danger fw-semibold">TSh <?= number_format($comparison['cogs']['current'], 2) ?></td>
                        <td class="text-end text-muted">TSh <?= number_format($comparison['cogs']['previous'], 2) ?></td>
                        <td class="text-end fw-semibold <?= ($comparison['cogs']['diff'] <= 0) ? 'text-success' : 'text-danger' ?>">
                            <?= ($comparison['cogs']['diff'] >= 0) ? '+' : '' ?><?= number_format($comparison['cogs']['diff'], 2) ?>
                        </td>
                        <td class="text-end pe-3">
                            <span class="badge bg-secondary-subtle text-secondary fw-bold">
                                <?= number_format($comparison['cogs']['percentage'], 2) ?>%
                            </span>
                        </td>
                    </tr>

                    <!-- Gross Profit -->
                    <tr class="table-info">
                        <td class="ps-3 fw-bold text-dark">
                            <i class="bi bi-trophy text-info me-2"></i>Faida Ghafi (Gross Profit)
                        </td>
                        <td class="text-end fw-bold text-info">TSh <?= number_format($comparison['gross_profit']['current'], 2) ?></td>
                        <td class="text-end text-muted">TSh <?= number_format($comparison['gross_profit']['previous'], 2) ?></td>
                        <td class="text-end fw-bold <?= ($comparison['gross_profit']['diff'] >= 0) ? 'text-success' : 'text-danger' ?>">
                            <?= ($comparison['gross_profit']['diff'] >= 0) ? '+' : '' ?><?= number_format($comparison['gross_profit']['diff'], 2) ?>
                        </td>
                        <td class="text-end pe-3">
                            <span class="badge <?= ($comparison['gross_profit']['diff'] >= 0) ? 'bg-success text-white' : 'bg-danger text-white' ?> fw-bold">
                                <i class="bi bi-arrow-<?= ($comparison['gross_profit']['diff'] >= 0) ? 'up' : 'down' ?> me-1"></i><?= number_format(abs($comparison['gross_profit']['percentage']), 2) ?>%
                            </span>
                        </td>
                    </tr>

                    <!-- Gross Margin % -->
                    <tr>
                        <td class="ps-3 text-muted">
                            <i class="bi bi-percent me-2"></i>Uwiano wa Faida Ghafi (Gross Margin %)
                        </td>
                        <td class="text-end fw-bold text-info"><?= number_format($comparison['gross_margin']['current'], 2) ?>%</td>
                        <td class="text-end text-muted"><?= number_format($comparison['gross_margin']['previous'], 2) ?>%</td>
                        <td class="text-end fw-semibold <?= ($comparison['gross_margin']['diff'] >= 0) ? 'text-success' : 'text-danger' ?>">
                            <?= ($comparison['gross_margin']['diff'] >= 0) ? '+' : '' ?><?= number_format($comparison['gross_margin']['diff'], 2) ?>%
                        </td>
                        <td class="text-end pe-3 text-muted">—</td>
                    </tr>

                    <!-- Operating Expenses -->
                    <tr>
                        <td class="ps-3 fw-bold text-dark">
                            <i class="bi bi-wallet2 text-danger me-2"></i>Gharama za Uendeshaji (OPEX)
                        </td>
                        <td class="text-end text-danger fw-bold">TSh <?= number_format($comparison['operating_expenses']['current'], 2) ?></td>
                        <td class="text-end text-muted">TSh <?= number_format($comparison['operating_expenses']['previous'], 2) ?></td>
                        <td class="text-end fw-semibold <?= ($comparison['operating_expenses']['diff'] <= 0) ? 'text-success' : 'text-danger' ?>">
                            <?= ($comparison['operating_expenses']['diff'] >= 0) ? '+' : '' ?><?= number_format($comparison['operating_expenses']['diff'], 2) ?>
                        </td>
                        <td class="text-end pe-3">
                            <span class="badge bg-secondary-subtle text-secondary fw-bold">
                                <?= number_format($comparison['operating_expenses']['percentage'], 2) ?>%
                            </span>
                        </td>
                    </tr>

                    <!-- Net Profit -->
                    <tr class="<?= ($comparison['net_profit']['current'] >= 0) ? 'table-success' : 'table-danger' ?>">
                        <td class="ps-3 fw-bold text-dark">
                            <i class="bi bi-award text-success me-2"></i>FAIDA HALISI (NET PROFIT)
                        </td>
                        <td class="text-end fw-bold fs-5 <?= ($comparison['net_profit']['current'] >= 0) ? 'text-success' : 'text-danger' ?>">
                            TSh <?= number_format($comparison['net_profit']['current'], 2) ?>
                        </td>
                        <td class="text-end text-muted">TSh <?= number_format($comparison['net_profit']['previous'], 2) ?></td>
                        <td class="text-end fw-bold <?= ($comparison['net_profit']['diff'] >= 0) ? 'text-success' : 'text-danger' ?>">
                            <?= ($comparison['net_profit']['diff'] >= 0) ? '+' : '' ?><?= number_format($comparison['net_profit']['diff'], 2) ?>
                        </td>
                        <td class="text-end pe-3">
                            <span class="badge <?= ($comparison['net_profit']['diff'] >= 0) ? 'bg-success text-white' : 'bg-danger text-white' ?> fw-bold px-2 py-1">
                                <i class="bi bi-arrow-<?= ($comparison['net_profit']['diff'] >= 0) ? 'up' : 'down' ?> me-1"></i><?= number_format(abs($comparison['net_profit']['percentage']), 2) ?>%
                            </span>
                        </td>
                    </tr>

                    <!-- Net Margin % -->
                    <tr>
                        <td class="ps-3 text-muted">
                            <i class="bi bi-percent me-2"></i>Uwiano wa Faida Halisi (Net Margin %)
                        </td>
                        <td class="text-end fw-bold <?= ($comparison['net_margin']['current'] >= 0) ? 'text-success' : 'text-danger' ?>">
                            <?= number_format($comparison['net_margin']['current'], 2) ?>%
                        </td>
                        <td class="text-end text-muted"><?= number_format($comparison['net_margin']['previous'], 2) ?>%</td>
                        <td class="text-end fw-semibold <?= ($comparison['net_margin']['diff'] >= 0) ? 'text-success' : 'text-danger' ?>">
                            <?= ($comparison['net_margin']['diff'] >= 0) ? '+' : '' ?><?= number_format($comparison['net_margin']['diff'], 2) ?>%
                        </td>
                        <td class="text-end pe-3 text-muted">—</td>
                    </tr>

                    <!-- Sales Count -->
                    <tr>
                        <td class="ps-3 fw-semibold text-dark">
                            <i class="bi bi-receipt me-2"></i>Idadi ya Miamala ya Mauzo
                        </td>
                        <td class="text-end fw-bold"><?= number_format($comparison['sales_count']['current']) ?></td>
                        <td class="text-end text-muted"><?= number_format($comparison['sales_count']['previous']) ?></td>
                        <td class="text-end fw-semibold">
                            <?= ($comparison['sales_count']['diff'] >= 0) ? '+' : '' ?><?= number_format($comparison['sales_count']['diff']) ?>
                        </td>
                        <td class="text-end pe-3">
                            <span class="badge bg-light text-dark border">
                                <?= number_format($comparison['sales_count']['percentage'], 2) ?>%
                            </span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
