<?php
use App\Core\Helper;
use App\Core\Auth;
$p = $analytics['product'];
?>

<!-- Header -->
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-1 text-dark">
            <i class="bi bi-graph-up-arrow text-primary me-2"></i>Uchambuzi wa 360° wa Kipuri: <?= htmlspecialchars($p['name']) ?>
        </h4>
        <p class="text-muted small mb-0">SKU: <strong class="font-monospace text-primary"><?= htmlspecialchars($p['sku']) ?></strong> &bull; Part Number: <strong class="font-monospace"><?= htmlspecialchars($p['part_number'] ?? 'N/A') ?></strong></p>
    </div>

    <div class="d-flex flex-wrap gap-2">
        <a href="/index.php?route=reports/sales_by_product" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Rudi kwenye Mauzo ya Bidhaa
        </a>
        <button type="button" onclick="window.print()" class="btn btn-outline-dark btn-sm">
            <i class="bi bi-printer me-1"></i> Chapisha
        </button>
    </div>
</div>

<!-- Primary Metric Cards -->
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm rounded-3 p-3 bg-light border-start border-primary border-4">
            <div class="small text-muted fw-semibold">MAPATO YALIYOINGIZWA (REVENUE)</div>
            <h4 class="fw-bold text-primary mb-1">TSh <?= number_format($analytics['total_revenue'], 2) ?></h4>
            <div class="small text-muted">Jumla ya Vipuri Vilivyouzwa: <strong><?= number_format($analytics['total_units_sold']) ?> pcs</strong></div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm rounded-3 p-3 bg-light border-start border-success border-4">
            <div class="small text-muted fw-semibold">FAIDA GHAFI (GROSS PROFIT)</div>
            <h4 class="fw-bold text-success mb-1">TSh <?= number_format($analytics['gross_profit'], 2) ?></h4>
            <div class="small text-muted">Margin: <strong><?= number_format($analytics['gross_margin'], 2) ?>%</strong> | COGS: TSh <?= number_format($analytics['total_cogs'], 2) ?></div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm rounded-3 p-3 bg-light border-start border-warning border-4">
            <div class="small text-muted fw-semibold">THAMANI YA STOO ILIYOPO</div>
            <h4 class="fw-bold text-dark mb-1">TSh <?= number_format($analytics['current_stock_value'], 2) ?></h4>
            <div class="small text-muted">Stoo ya Sasa: <strong><?= number_format($p['current_stock']) ?> pcs</strong> (Bei: TSh <?= number_format($p['buying_price'], 2) ?>)</div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm rounded-3 p-3 bg-light border-start border-danger border-4">
            <div class="small text-muted fw-semibold">MAREJESHO (RETURNS)</div>
            <h4 class="fw-bold text-danger mb-1"><?= number_format($analytics['total_units_returned']) ?> pcs</h4>
            <div class="small text-muted">Jumla ya Manunuzi: <strong><?= number_format($analytics['total_units_purchased']) ?> pcs</strong></div>
        </div>
    </div>
</div>

<!-- Specs and 6-Month Trend -->
<div class="row g-4">
    <!-- Specs and Details -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-3 h-100">
            <div class="card-header bg-white p-3 border-bottom">
                <h6 class="fw-bold text-dark mb-0"><i class="bi bi-info-circle me-2"></i>Taarifa za Msingi za Kipuri</h6>
            </div>
            <div class="card-body p-0">
                <table class="table table-bordered mb-0 align-middle">
                    <tbody>
                        <tr><th class="bg-light w-40">Jina la Kipuri</th><td class="fw-bold"><?= htmlspecialchars($p['name']) ?></td></tr>
                        <tr><th class="bg-light">SKU</th><td class="font-monospace"><?= htmlspecialchars($p['sku']) ?></td></tr>
                        <tr><th class="bg-light">Kategoria</th><td><?= htmlspecialchars($p['category_name'] ?? '—') ?></td></tr>
                        <tr><th class="bg-light">Chapa (Brand)</th><td><?= htmlspecialchars($p['brand_name'] ?? '—') ?></td></tr>
                        <tr><th class="bg-light">Bei ya Kununua (Buying Price)</th><td class="text-muted">TSh <?= number_format($p['buying_price'], 2) ?></td></tr>
                        <tr><th class="bg-light">Bei ya Kuuza (Selling Price)</th><td class="fw-bold text-primary">TSh <?= number_format($p['selling_price'], 2) ?></td></tr>
                        <tr><th class="bg-light">Mauzo ya Mwisho (Last Sale)</th><td><?= !empty($analytics['last_sale_date']) ? date('d/m/Y H:i', strtotime($analytics['last_sale_date'])) : 'Haijawahi kuuzwa' ?></td></tr>
                        <tr><th class="bg-light">Manunuzi ya Mwisho (Last Purchase)</th><td><?= !empty($analytics['last_purchase_date']) ? date('d/m/Y', strtotime($analytics['last_purchase_date'])) : 'Haijawahi kuagizwa' ?></td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- 6-Month Performance Trend -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-3 h-100">
            <div class="card-header bg-white p-3 border-bottom">
                <h6 class="fw-bold text-dark mb-0"><i class="bi bi-bar-chart-line text-primary me-2"></i>Mwenendo wa Mauzo ya Miezi 6 Iliyopita</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Mwezi</th>
                                <th class="text-center">Idadi Iliyouzwa</th>
                                <th class="text-end pe-3">Mapato (TSh)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($analytics['sales_trend'])): ?>
                                <tr><td colspan="3" class="text-center py-4 text-muted">Hakuna historia ya mauzo ya miezi 6.</td></tr>
                            <?php else: ?>
                                <?php foreach ($analytics['sales_trend'] as $st): ?>
                                    <tr>
                                        <td class="ps-3 fw-bold"><?= htmlspecialchars($st['month_label']) ?></td>
                                        <td class="text-center fw-semibold text-primary"><?= number_format($st['qty_sold']) ?> pcs</td>
                                        <td class="text-end pe-3 fw-bold text-dark"><?= number_format($st['revenue'], 2) ?></td>
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
