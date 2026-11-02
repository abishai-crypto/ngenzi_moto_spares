<?php
use App\Core\Helper;
use App\Core\Auth;
?>

<!-- Header -->
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-1 text-dark">
            <i class="bi bi-box-seam text-primary me-2"></i>Mauzo kwa Kila Bidhaa (Sales by Product)
        </h4>
        <p class="text-muted small mb-0">
            Kipindi: <strong class="text-dark"><?= htmlspecialchars($range['label']) ?></strong> &bull; Jumla ya Vipuri: <strong><?= count($products) ?></strong>
        </p>
    </div>

    <div class="d-flex flex-wrap gap-2">
        <a href="/index.php?route=reports" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-speedometer2 me-1"></i> Kituo cha Ripoti
        </a>
        <a href="/index.php?route=reports/export&type=products&period=<?= urlencode($range['period']) ?>&start_date=<?= urlencode($range['start_date']) ?>&end_date=<?= urlencode($range['end_date']) ?>" class="btn btn-outline-success btn-sm">
            <i class="bi bi-file-earmark-spreadsheet me-1"></i> Pakua CSV
        </a>
        <button type="button" onclick="window.print()" class="btn btn-outline-dark btn-sm">
            <i class="bi bi-printer me-1"></i> Chapisha
        </button>
    </div>
</div>

<!-- Filters Bar -->
<div class="card border-0 shadow-sm rounded-3 mb-4">
    <div class="card-body p-3">
        <form method="GET" action="/index.php" class="row g-2 align-items-center">
            <input type="hidden" name="route" value="reports/sales_by_product">

            <div class="col-md-3 col-sm-6">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Tafuta Jina au SKU..." value="<?= htmlspecialchars($filters['search']) ?>">
            </div>

            <div class="col-md-3 col-sm-6">
                <select name="category_id" class="form-select form-select-sm">
                    <option value="">-- Kategoria Zote --</option>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= ($filters['category_id'] == $c['id']) ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-2 col-sm-6">
                <select name="brand_id" class="form-select form-select-sm">
                    <option value="">-- Chapa Zote --</option>
                    <?php foreach ($brands as $b): ?>
                        <option value="<?= $b['id'] ?>" <?= ($filters['brand_id'] == $b['id']) ? 'selected' : '' ?>><?= htmlspecialchars($b['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-2 col-sm-6">
                <select name="period" class="form-select form-select-sm" onchange="if(this.value!='custom') this.form.submit();">
                    <option value="today" <?= ($range['period'] === 'today') ? 'selected' : '' ?>>Leo</option>
                    <option value="this_week" <?= ($range['period'] === 'this_week') ? 'selected' : '' ?>>Wiki Hii</option>
                    <option value="this_month" <?= ($range['period'] === 'this_month') ? 'selected' : '' ?>>Mwezi Huu</option>
                    <option value="last_month" <?= ($range['period'] === 'last_month') ? 'selected' : '' ?>>Mwezi Uliopita</option>
                    <option value="this_year" <?= ($range['period'] === 'this_year') ? 'selected' : '' ?>>Mwaka Huu</option>
                    <option value="custom" <?= ($range['period'] === 'custom') ? 'selected' : '' ?>>Custom</option>
                </select>
            </div>

            <div class="col-md-2 col-sm-12 d-flex gap-1">
                <button type="submit" class="btn btn-primary btn-sm w-100"><i class="bi bi-funnel"></i> Chuja</button>
                <a href="/index.php?route=reports/sales_by_product" class="btn btn-outline-secondary btn-sm"><i class="bi bi-x-circle"></i></a>
            </div>
        </form>
    </div>
</div>

<!-- Table Card -->
<div class="card border-0 shadow-sm rounded-3">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">#</th>
                        <th>Kipuri (Spare Part)</th>
                        <th>Kategoria / Chapa</th>
                        <th class="text-center">Vilivyouzwa</th>
                        <th class="text-end">Mapato Ghafi</th>
                        <th class="text-end">Mapato Halisi</th>
                        <th class="text-end">COGS</th>
                        <th class="text-end">Faida Ghafi</th>
                        <th class="text-end">Margin %</th>
                        <th class="text-end pe-3">Uchambuzi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($products)): ?>
                        <tr>
                            <td colspan="10" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                Hakuna mauzo ya bidhaa yaliyopatikana katika kipindi hiki.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php 
                        $totalQty = 0; $totalRev = 0; $totalCogs = 0; $totalProfit = 0;
                        foreach ($products as $idx => $p): 
                            $totalQty += (int)$p['total_qty_sold'];
                            $totalRev += (float)$p['net_revenue'];
                            $totalCogs += (float)$p['total_cogs'];
                            $totalProfit += (float)$p['gross_profit'];
                        ?>
                            <tr>
                                <td class="ps-3 text-muted"><?= $idx + 1 ?></td>
                                <td>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($p['product_name']) ?></div>
                                    <small class="text-muted font-monospace"><?= htmlspecialchars($p['sku']) ?></small>
                                </td>
                                <td>
                                    <div class="small fw-semibold"><?= htmlspecialchars($p['category_name'] ?? '—') ?></div>
                                    <small class="text-muted"><?= htmlspecialchars($p['brand_name'] ?? '—') ?></small>
                                </td>
                                <td class="text-center fw-bold text-primary"><?= number_format($p['total_qty_sold']) ?></td>
                                <td class="text-end"><?= number_format($p['gross_revenue'], 2) ?></td>
                                <td class="text-end fw-bold text-dark"><?= number_format($p['net_revenue'], 2) ?></td>
                                <td class="text-end text-muted"><?= number_format($p['total_cogs'], 2) ?></td>
                                <td class="text-end fw-bold text-success"><?= number_format($p['gross_profit'], 2) ?></td>
                                <td class="text-end">
                                    <span class="badge bg-success-subtle text-success fw-bold"><?= number_format($p['gross_margin'], 1) ?>%</span>
                                </td>
                                <td class="text-end pe-3">
                                    <a href="/index.php?route=reports/product_performance&id=<?= $p['product_id'] ?>" class="btn btn-sm btn-outline-info" title="Tazama 360 Analytics">
                                        <i class="bi bi-graph-up"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <!-- Grand Totals -->
                        <?php $overallMargin = ($totalRev > 0) ? round(($totalProfit / $totalRev) * 100, 2) : 0.00; ?>
                        <tr class="table-light fw-bold">
                            <td class="ps-3" colspan="3">JUMLA KUU</td>
                            <td class="text-center"><?= number_format($totalQty) ?></td>
                            <td></td>
                            <td class="text-end text-primary">TSh <?= number_format($totalRev, 2) ?></td>
                            <td class="text-end text-muted">TSh <?= number_format($totalCogs, 2) ?></td>
                            <td class="text-end text-success">TSh <?= number_format($totalProfit, 2) ?></td>
                            <td class="text-end text-success"><?= number_format($overallMargin, 2) ?>%</td>
                            <td></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
