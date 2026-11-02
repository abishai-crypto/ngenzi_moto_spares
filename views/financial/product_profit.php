<?php
use App\Core\Helper;
use App\Core\Auth;
?>

<!-- Header -->
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-1 text-dark">
            <i class="bi bi-box-seam text-primary me-2"></i>Uchambuzi wa Faida kwa Bidhaa (Product Profitability)
        </h4>
        <p class="text-muted small mb-0">
            Kipindi: <strong class="text-dark"><?= htmlspecialchars($range['label']) ?></strong> &bull; Jumla ya Vipuri Vilivyochambuliwa: <strong><?= count($products) ?></strong>
        </p>
    </div>

    <div class="d-flex flex-wrap gap-2">
        <a href="/index.php?route=financial/dashboard" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-speedometer2 me-1"></i> Dashibodi
        </a>
        <a href="/index.php?route=financial/category_profit" class="btn btn-outline-info btn-sm">
            <i class="bi bi-grid me-1"></i> Faida kwa Kategoria
        </a>
    </div>
</div>

<!-- Filters Bar -->
<div class="card border-0 shadow-sm rounded-3 mb-4">
    <div class="card-body p-3">
        <form method="GET" action="/index.php" class="row g-2 align-items-center">
            <input type="hidden" name="route" value="financial/product_profit">

            <div class="col-md-3 col-sm-6">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Tafuta Jina, SKU, Part No..." value="<?= htmlspecialchars($filters['search'] ?? '') ?>">
            </div>

            <div class="col-md-2 col-sm-6">
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

            <div class="col-md-3 col-sm-12 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm w-100"><i class="bi bi-funnel me-1"></i> Chuja</button>
                <a href="/index.php?route=financial/product_profit" class="btn btn-outline-secondary btn-sm"><i class="bi bi-x-circle"></i></a>
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
                        <th class="text-center">Idadi Iliyouzwa</th>
                        <th class="text-end">Mapato (Revenue)</th>
                        <th class="text-end">COGS (Gharama)</th>
                        <th class="text-end">Faida Ghafi (Profit)</th>
                        <th class="text-end pe-3">Margin %</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($products)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                Hakuna rekodi za mauzo ya vipuri katika kipindi na vigezo vilivyochaguliwa.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php 
                        $grandQty = 0;
                        $grandRev = 0;
                        $grandCogs = 0;
                        $grandProfit = 0;
                        foreach ($products as $idx => $p): 
                            $grandQty += (int)$p['total_qty_sold'];
                            $grandRev += (float)$p['total_revenue'];
                            $grandCogs += (float)$p['total_cogs'];
                            $grandProfit += (float)$p['gross_profit'];
                        ?>
                            <tr>
                                <td class="ps-3 text-muted"><?= $idx + 1 ?></td>
                                <td>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($p['product_name']) ?></div>
                                    <small class="text-muted font-monospace"><?= htmlspecialchars($p['sku']) ?> <?= !empty($p['part_number']) ? '| Part #' . htmlspecialchars($p['part_number']) : '' ?></small>
                                </td>
                                <td>
                                    <div class="small fw-semibold"><?= htmlspecialchars($p['category_name'] ?? '—') ?></div>
                                    <small class="text-muted"><?= htmlspecialchars($p['brand_name'] ?? '—') ?></small>
                                </td>
                                <td class="text-center fw-bold">
                                    <?= number_format($p['total_qty_sold']) ?>
                                </td>
                                <td class="text-end fw-semibold">
                                    <?= number_format($p['total_revenue'], 2) ?>
                                </td>
                                <td class="text-end text-muted">
                                    <?= number_format($p['total_cogs'], 2) ?>
                                </td>
                                <td class="text-end fw-bold <?= ($p['gross_profit'] >= 0) ? 'text-success' : 'text-danger' ?>">
                                    <?= number_format($p['gross_profit'], 2) ?>
                                </td>
                                <td class="text-end pe-3">
                                    <span class="badge <?= ($p['margin_percent'] >= 25) ? 'bg-success-subtle text-success' : (($p['margin_percent'] >= 10) ? 'bg-info-subtle text-info' : 'bg-warning-subtle text-warning') ?> fw-bold px-2 py-1">
                                        <?= number_format($p['margin_percent'], 2) ?>%
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <!-- Grand Total Footer -->
                        <?php $overallMargin = ($grandRev > 0) ? round(($grandProfit / $grandRev) * 100, 2) : 0.00; ?>
                        <tr class="table-light fw-bold">
                            <td class="ps-3" colspan="3">JUMLA KUU (TOTALS)</td>
                            <td class="text-center"><?= number_format($grandQty) ?></td>
                            <td class="text-end text-primary">TSh <?= number_format($grandRev, 2) ?></td>
                            <td class="text-end text-muted">TSh <?= number_format($grandCogs, 2) ?></td>
                            <td class="text-end text-success">TSh <?= number_format($grandProfit, 2) ?></td>
                            <td class="text-end pe-3 text-success"><?= number_format($overallMargin, 2) ?>%</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
