<?php
use App\Core\Helper;
use App\Core\Auth;
?>

<!-- Header -->
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-1 text-dark">
            <i class="bi bi-person-badge text-primary me-2"></i>Utendaji wa Wauzaji (Sales by Cashier)
        </h4>
        <p class="text-muted small mb-0">
            Kipindi: <strong class="text-dark"><?= htmlspecialchars($range['label']) ?></strong>
        </p>
    </div>

    <div class="d-flex flex-wrap gap-2">
        <a href="/index.php?route=reports" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-speedometer2 me-1"></i> Kituo cha Ripoti
        </a>
        <button type="button" onclick="window.print()" class="btn btn-outline-dark btn-sm">
            <i class="bi bi-printer me-1"></i> Chapisha
        </button>
    </div>
</div>

<!-- Date Filter -->
<div class="card border-0 shadow-sm rounded-3 mb-4">
    <div class="card-body p-3">
        <form method="GET" action="/index.php" class="row g-2 align-items-center">
            <input type="hidden" name="route" value="reports/sales_by_cashier">

            <div class="col-md-4 col-sm-6">
                <select name="period" class="form-select form-select-sm" onchange="if(this.value!='custom') this.form.submit();">
                    <option value="today" <?= ($range['period'] === 'today') ? 'selected' : '' ?>>Leo</option>
                    <option value="this_week" <?= ($range['period'] === 'this_week') ? 'selected' : '' ?>>Wiki Hii</option>
                    <option value="this_month" <?= ($range['period'] === 'this_month') ? 'selected' : '' ?>>Mwezi Huu</option>
                    <option value="last_month" <?= ($range['period'] === 'last_month') ? 'selected' : '' ?>>Mwezi Uliopita</option>
                    <option value="this_year" <?= ($range['period'] === 'this_year') ? 'selected' : '' ?>>Mwaka Huu</option>
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
                <button type="submit" class="btn btn-primary btn-sm w-100"><i class="bi bi-funnel me-1"></i> Sasisha</button>
            </div>
        </form>
    </div>
</div>

<!-- Cashier Table -->
<div class="card border-0 shadow-sm rounded-3">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Jina la Cashier</th>
                        <th class="text-center">Miamala ya Mauzo</th>
                        <th class="text-center">Vipuri Vilivyouzwa</th>
                        <th class="text-end">Mauzo Ghafi (TSh)</th>
                        <th class="text-end">Punguzo (TSh)</th>
                        <th class="text-end">Mauzo Halisi (TSh)</th>
                        <th class="text-end pe-3">Wastani kwa Ankara (Avg)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($cashiers)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                Hakuna mauzo ya wauzaji yaliyopatikana katika kipindi hiki.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php 
                        $grandCount = 0; $grandItems = 0; $grandGross = 0; $grandDisc = 0; $grandNet = 0;
                        foreach ($cashiers as $c): 
                            $grandCount += (int)$c['sales_count'];
                            $grandItems += (int)$c['items_sold'];
                            $grandGross += (float)$c['gross_sales'];
                            $grandDisc += (float)$c['total_discounts'];
                            $grandNet += (float)$c['total_revenue'];
                        ?>
                            <tr>
                                <td class="ps-3">
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($c['cashier_name']) ?></div>
                                    <small class="text-muted font-monospace">@<?= htmlspecialchars($c['username']) ?></small>
                                </td>
                                <td class="text-center fw-bold"><?= number_format($c['sales_count']) ?></td>
                                <td class="text-center"><?= number_format($c['items_sold']) ?></td>
                                <td class="text-end"><?= number_format($c['gross_sales'], 2) ?></td>
                                <td class="text-end text-danger"><?= ($c['total_discounts'] > 0) ? '(' . number_format($c['total_discounts'], 2) . ')' : '—' ?></td>
                                <td class="text-end fw-bold text-primary"><?= number_format($c['total_revenue'], 2) ?></td>
                                <td class="text-end pe-3 fw-semibold text-dark">
                                    TSh <?= number_format($c['avg_sale_value'], 2) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <!-- Grand Total -->
                        <?php $overallAvg = ($grandCount > 0) ? round($grandNet / $grandCount, 2) : 0.00; ?>
                        <tr class="table-light fw-bold">
                            <td class="ps-3">JUMLA KUU</td>
                            <td class="text-center"><?= number_format($grandCount) ?></td>
                            <td class="text-center"><?= number_format($grandItems) ?></td>
                            <td class="text-end">TSh <?= number_format($grandGross, 2) ?></td>
                            <td class="text-end text-danger">TSh <?= number_format($grandDisc, 2) ?></td>
                            <td class="text-end text-primary">TSh <?= number_format($grandNet, 2) ?></td>
                            <td class="text-end pe-3 text-dark">TSh <?= number_format($overallAvg, 2) ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
