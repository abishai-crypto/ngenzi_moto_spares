<?php
use App\Core\Helper;
use App\Core\Auth;
?>

<!-- Header -->
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-1 text-dark">
            <i class="bi bi-tags text-primary me-2"></i>Mauzo kwa Kategoria & Chapa (Category & Brand Sales)
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
            <input type="hidden" name="route" value="reports/sales_by_category">

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

<!-- Tabs: Category vs Brand -->
<ul class="nav nav-tabs nav-fill mb-4" id="catBrandTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active fw-semibold" id="cat-tab" data-bs-toggle="tab" data-bs-target="#catPane" type="button" role="tab">
            <i class="bi bi-folder2 me-1"></i> Mauzo kwa Kategoria (Categories)
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link fw-semibold" id="brand-tab" data-bs-toggle="tab" data-bs-target="#brandPane" type="button" role="tab">
            <i class="bi bi-award me-1"></i> Mauzo kwa Chapa (Brands)
        </button>
    </li>
</ul>

<div class="tab-content" id="catBrandTabsContent">
    <!-- Category Pane -->
    <div class="tab-pane fade show active" id="catPane" role="tabpanel">
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Kategoria</th>
                                <th class="text-center">Aina za Vipuri</th>
                                <th class="text-center">Idadi Iliyouzwa</th>
                                <th class="text-end">Mapato Halisi (TSh)</th>
                                <th class="text-end">COGS</th>
                                <th class="text-end">Faida Ghafi</th>
                                <th class="text-end pe-3">Margin %</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($categories)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">Hakuna mauzo katika kipindi hiki.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($categories as $c): ?>
                                    <tr>
                                        <td class="ps-3 fw-bold text-dark"><?= htmlspecialchars($c['category_name']) ?></td>
                                        <td class="text-center"><?= number_format($c['products_count']) ?></td>
                                        <td class="text-center fw-bold"><?= number_format($c['total_qty_sold']) ?></td>
                                        <td class="text-end fw-semibold text-primary"><?= number_format($c['total_revenue'], 2) ?></td>
                                        <td class="text-end text-muted"><?= number_format($c['total_cogs'], 2) ?></td>
                                        <td class="text-end fw-bold text-success"><?= number_format($c['gross_profit'], 2) ?></td>
                                        <td class="text-end pe-3">
                                            <span class="badge bg-success-subtle text-success fw-bold"><?= number_format($c['gross_margin'], 1) ?>%</span>
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

    <!-- Brand Pane -->
    <div class="tab-pane fade" id="brandPane" role="tabpanel">
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Chapa ya Vipuri</th>
                                <th class="text-center">Aina za Vipuri</th>
                                <th class="text-center">Idadi Iliyouzwa</th>
                                <th class="text-end">Mapato Halisi (TSh)</th>
                                <th class="text-end">COGS</th>
                                <th class="text-end">Faida Ghafi</th>
                                <th class="text-end pe-3">Margin %</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($brands)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">Hakuna mauzo katika kipindi hiki.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($brands as $b): ?>
                                    <tr>
                                        <td class="ps-3 fw-bold text-dark"><?= htmlspecialchars($b['brand_name']) ?></td>
                                        <td class="text-center"><?= number_format($b['products_count']) ?></td>
                                        <td class="text-center fw-bold"><?= number_format($b['total_qty_sold']) ?></td>
                                        <td class="text-end fw-semibold text-primary"><?= number_format($b['total_revenue'], 2) ?></td>
                                        <td class="text-end text-muted"><?= number_format($b['total_cogs'], 2) ?></td>
                                        <td class="text-end fw-bold text-success"><?= number_format($b['gross_profit'], 2) ?></td>
                                        <td class="text-end pe-3">
                                            <span class="badge bg-success-subtle text-success fw-bold"><?= number_format($b['gross_margin'], 1) ?>%</span>
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
