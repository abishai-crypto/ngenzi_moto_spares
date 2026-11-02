<?php
use App\Core\Helper;
use App\Core\Auth;
?>

<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-1">
            <i class="bi bi-cash-stack text-success me-2"></i>Thamani ya Mali Iliyopo Stoo (Inventory Valuation)
        </h4>
        <p class="text-muted small mb-0">Uchambuzi sahihi wa mtaji uliofungwa kwenye bidhaa kwa hesabu za desimali (Decimal Cost Arithmetic).</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="/index.php?<?= http_build_query(array_merge($_GET, ['export' => 'csv'])) ?>" class="btn btn-outline-success btn-sm d-flex align-items-center gap-1">
            <i class="bi bi-file-earmark-spreadsheet"></i> Pakua CSV
        </a>
        <a href="/index.php?route=inventory_dashboard" class="btn btn-light border btn-sm">
            <i class="bi bi-speedometer2 me-1"></i> Dashibodi
        </a>
    </div>
</div>

<!-- Summary KPI Cards -->
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm rounded-3 p-3 bg-white border-start border-4 border-primary">
            <span class="text-muted small fw-medium text-uppercase">Thamani ya Kununua (Cost Value)</span>
            <h3 class="fw-bold text-primary mb-0 mt-1"><?= Helper::formatMoney($report['total_cost_value']) ?></h3>
            <span class="text-muted small">Jumla ya mtaji kwenye bidhaa</span>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm rounded-3 p-3 bg-white border-start border-4 border-success">
            <span class="text-muted small fw-medium text-uppercase">Thamani ya Kuuza (Retail Value)</span>
            <h3 class="fw-bold text-success mb-0 mt-1"><?= Helper::formatMoney($report['total_retail_value']) ?></h3>
            <span class="text-muted small">Makadirio ya mapato ya mauzo</span>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm rounded-3 p-3 bg-white border-start border-4 border-info">
            <span class="text-muted small fw-medium text-uppercase">Faida Inayotarajiwa (Gross Profit)</span>
            <h3 class="fw-bold text-info mb-0 mt-1"><?= Helper::formatMoney($report['potential_profit']) ?></h3>
            <span class="text-muted small">Margin ya jumla: <strong><?= $report['overall_margin'] ?>%</strong></span>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm rounded-3 p-3 bg-white border-start border-4 border-secondary">
            <span class="text-muted small fw-medium text-uppercase">Jumla ya Vipande (Total Units)</span>
            <h3 class="fw-bold text-dark mb-0 mt-1"><?= number_format($report['total_units']) ?></h3>
            <span class="text-muted small">Kati ya vipuri <strong><?= $report['total_products'] ?></strong></span>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card border-0 shadow-sm rounded-3 mb-4">
    <div class="card-body p-3">
        <form method="GET" action="/index.php" class="row g-2 align-items-end">
            <input type="hidden" name="route" value="inventory_valuation">

            <div class="col-md-5">
                <label class="form-label small fw-semibold text-muted mb-1">Chuja kwa Kategoria</label>
                <select name="category_id" class="form-select form-select-sm">
                    <option value="">-- Kategoria Zote --</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= ($categoryId == $cat['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-5">
                <label class="form-label small fw-semibold text-muted mb-1">Chuja kwa Brand</label>
                <select name="brand_id" class="form-select form-select-sm">
                    <option value="">-- Brand Zote --</option>
                    <?php foreach ($brands as $br): ?>
                        <option value="<?= $br['id'] ?>" <?= ($brandId == $br['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($br['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-2 d-flex gap-1">
                <button type="submit" class="btn btn-primary-custom btn-sm w-100">
                    <i class="bi bi-funnel-fill me-1"></i> Chuja
                </button>
                <a href="/index.php?route=inventory_valuation" class="btn btn-light border btn-sm" title="Safisha">
                    <i class="bi bi-x-circle"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Valuation Table -->
<div class="card border-0 shadow-sm rounded-3">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table align-middle table-hover mb-0">
                <thead class="table-light small text-muted">
                    <tr>
                        <th>Kipuri</th>
                        <th>Kategoria & Brand</th>
                        <th class="text-center">Idadi Stoo</th>
                        <th class="text-end">Bei ya Kununua (Cost)</th>
                        <th class="text-end">Thamani ya Cost</th>
                        <th class="text-end">Bei ya Kuuza</th>
                        <th class="text-end">Thamani ya Kuuza</th>
                        <th class="text-end">Faida Inayotarajiwa</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($report['products'])): ?>
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">Hakuna bidhaa zilizopatikana kwenye kigezo hiki.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($report['products'] as $p): ?>
                            <tr>
                                <td>
                                    <a href="/index.php?route=product_show&id=<?= $p['id'] ?>" class="fw-semibold text-dark text-decoration-none">
                                        <?= htmlspecialchars($p['name']) ?>
                                    </a>
                                    <div class="small text-muted font-monospace">SKU: <?= htmlspecialchars($p['sku']) ?></div>
                                </td>
                                <td>
                                    <div class="small fw-semibold text-dark"><?= htmlspecialchars($p['category_name']) ?></div>
                                    <div class="small text-muted"><?= htmlspecialchars($p['brand_name'] ?? 'Generic') ?></div>
                                </td>
                                <td class="text-center fw-bold fs-6 text-dark">
                                    <?= (int)$p['quantity'] ?> <span class="small fw-normal text-muted"><?= $p['unit_short'] ?></span>
                                </td>
                                <td class="text-end text-muted small">
                                    <?= Helper::formatMoney((float)$p['buying_cost']) ?>
                                </td>
                                <td class="text-end fw-semibold text-primary">
                                    <?= Helper::formatMoney((float)$p['total_cost_value']) ?>
                                </td>
                                <td class="text-end text-muted small">
                                    <?= Helper::formatMoney((float)$p['selling_price']) ?>
                                </td>
                                <td class="text-end fw-semibold text-success">
                                    <?= Helper::formatMoney((float)$p['total_selling_value']) ?>
                                </td>
                                <td class="text-end fw-semibold text-info">
                                    <?= Helper::formatMoney((float)$p['potential_profit']) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
                <tfoot class="table-light fw-bold border-top">
                    <tr>
                        <td colspan="2">Jumla Kuu (Grand Total)</td>
                        <td class="text-center"><?= number_format($report['total_units']) ?></td>
                        <td></td>
                        <td class="text-end text-primary"><?= Helper::formatMoney($report['total_cost_value']) ?></td>
                        <td></td>
                        <td class="text-end text-success"><?= Helper::formatMoney($report['total_retail_value']) ?></td>
                        <td class="text-end text-info"><?= Helper::formatMoney($report['potential_profit']) ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
