<?php
use App\Core\Helper;
use App\Core\Auth;
?>

<!-- Header -->
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-1 text-dark">
            <i class="bi bi-grid text-warning me-2"></i>Faida kwa Kategoria, Chapa & Aina ya Chombo
        </h4>
        <p class="text-muted small mb-0">
            Kipindi: <strong class="text-dark"><?= htmlspecialchars($range['label']) ?></strong>
        </p>
    </div>

    <div class="d-flex flex-wrap gap-2">
        <a href="/index.php?route=financial/dashboard" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-speedometer2 me-1"></i> Dashibodi
        </a>
        <a href="/index.php?route=financial/product_profit" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-box-seam me-1"></i> Faida kwa Bidhaa
        </a>
    </div>
</div>

<!-- Date Filter -->
<div class="card border-0 shadow-sm rounded-3 mb-4">
    <div class="card-body p-3">
        <form method="GET" action="/index.php" class="row g-2 align-items-center">
            <input type="hidden" name="route" value="financial/category_profit">

            <div class="col-md-4 col-sm-6">
                <select name="period" class="form-select form-select-sm" onchange="if(this.value!='custom') this.form.submit();">
                    <option value="today" <?= ($range['period'] === 'today') ? 'selected' : '' ?>>Leo</option>
                    <option value="this_week" <?= ($range['period'] === 'this_week') ? 'selected' : '' ?>>Wiki Hii</option>
                    <option value="this_month" <?= ($range['period'] === 'this_month') ? 'selected' : '' ?>>Mwezi Huu</option>
                    <option value="last_month" <?= ($range['period'] === 'last_month') ? 'selected' : '' ?>>Mwezi Uliopita</option>
                    <option value="this_quarter" <?= ($range['period'] === 'this_quarter') ? 'selected' : '' ?>>Robo Mwaka</option>
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

<!-- Nav Tabs for Category vs Brand vs Vehicle Compatibility -->
<ul class="nav nav-tabs nav-fill mb-4" id="profitTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active fw-semibold" id="cat-tab" data-bs-toggle="tab" data-bs-target="#catTabPane" type="button" role="tab">
            <i class="bi bi-folder2 me-1"></i> Faida kwa Kategoria (Categories)
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link fw-semibold" id="brand-tab" data-bs-toggle="tab" data-bs-target="#brandTabPane" type="button" role="tab">
            <i class="bi bi-award me-1"></i> Faida kwa Chapa (Brands)
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link fw-semibold" id="vehicle-tab" data-bs-toggle="tab" data-bs-target="#vehicleTabPane" type="button" role="tab">
            <i class="bi bi-bicycle me-1"></i> Faida kwa Aina ya Chombo (Boxer, TVS, Bajaj, n.k)
        </button>
    </li>
</ul>

<div class="tab-content" id="profitTabsContent">
    <!-- 1. CATEGORY TAB -->
    <div class="tab-pane fade show active" id="catTabPane" role="tabpanel">
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Kategoria ya Vipuri</th>
                                <th class="text-center">Aina za Vipuri</th>
                                <th class="text-center">Idadi Iliyouzwa</th>
                                <th class="text-end">Mapato (Revenue)</th>
                                <th class="text-end">COGS (Gharama)</th>
                                <th class="text-end">Faida Ghafi</th>
                                <th class="text-end pe-3">Margin %</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($categoryProfits)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">Hakuna mauzo ya kategoria katika kipindi hiki.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($categoryProfits as $cp): ?>
                                    <tr>
                                        <td class="ps-3 fw-bold text-dark"><?= htmlspecialchars($cp['category_name']) ?></td>
                                        <td class="text-center"><?= number_format($cp['products_count']) ?></td>
                                        <td class="text-center"><?= number_format($cp['total_qty_sold']) ?></td>
                                        <td class="text-end fw-semibold"><?= number_format($cp['total_revenue'], 2) ?></td>
                                        <td class="text-end text-muted"><?= number_format($cp['total_cogs'], 2) ?></td>
                                        <td class="text-end fw-bold text-success"><?= number_format($cp['gross_profit'], 2) ?></td>
                                        <td class="text-end pe-3">
                                            <span class="badge bg-success-subtle text-success fw-bold"><?= number_format($cp['margin_percent'], 2) ?>%</span>
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

    <!-- 2. BRAND TAB -->
    <div class="tab-pane fade" id="brandTabPane" role="tabpanel">
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Chapa ya Vipuri (Brand)</th>
                                <th class="text-center">Aina za Vipuri</th>
                                <th class="text-center">Idadi Iliyouzwa</th>
                                <th class="text-end">Mapato (Revenue)</th>
                                <th class="text-end">COGS (Gharama)</th>
                                <th class="text-end">Faida Ghafi</th>
                                <th class="text-end pe-3">Margin %</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($brandProfits)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">Hakuna mauzo ya chapa katika kipindi hiki.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($brandProfits as $bp): ?>
                                    <tr>
                                        <td class="ps-3 fw-bold text-dark"><?= htmlspecialchars($bp['brand_name']) ?></td>
                                        <td class="text-center"><?= number_format($bp['products_count']) ?></td>
                                        <td class="text-center"><?= number_format($bp['total_qty_sold']) ?></td>
                                        <td class="text-end fw-semibold"><?= number_format($bp['total_revenue'], 2) ?></td>
                                        <td class="text-end text-muted"><?= number_format($bp['total_cogs'], 2) ?></td>
                                        <td class="text-end fw-bold text-success"><?= number_format($bp['gross_profit'], 2) ?></td>
                                        <td class="text-end pe-3">
                                            <span class="badge bg-success-subtle text-success fw-bold"><?= number_format($bp['margin_percent'], 2) ?>%</span>
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

    <!-- 3. VEHICLE COMPATIBILITY TAB -->
    <div class="tab-pane fade" id="vehicleTabPane" role="tabpanel">
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Mfano wa Chombo (Vehicle Model)</th>
                                <th>Aina ya Chombo</th>
                                <th class="text-center">Vipuri Vinavyoingiana</th>
                                <th class="text-center">Idadi Iliyouzwa</th>
                                <th class="text-end">Mapato (Revenue)</th>
                                <th class="text-end">COGS (Gharama)</th>
                                <th class="text-end">Faida Ghafi</th>
                                <th class="text-end pe-3">Margin %</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($vehicleProfits)): ?>
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-muted">Hakuna mauzo ya uoanifu wa vyombo yaliyopatikana katika kipindi hiki.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($vehicleProfits as $vp): ?>
                                    <tr>
                                        <td class="ps-3 fw-bold text-dark"><?= htmlspecialchars($vp['vehicle_model']) ?></td>
                                        <td>
                                            <span class="badge bg-light text-dark border"><?= htmlspecialchars(ucfirst($vp['vehicle_type'])) ?></span>
                                        </td>
                                        <td class="text-center"><?= number_format($vp['compatible_products_sold']) ?></td>
                                        <td class="text-center"><?= number_format($vp['total_qty_sold']) ?></td>
                                        <td class="text-end fw-semibold"><?= number_format($vp['total_revenue'], 2) ?></td>
                                        <td class="text-end text-muted"><?= number_format($vp['total_cogs'], 2) ?></td>
                                        <td class="text-end fw-bold text-success"><?= number_format($vp['gross_profit'], 2) ?></td>
                                        <td class="text-end pe-3">
                                            <span class="badge bg-success-subtle text-success fw-bold"><?= number_format($vp['margin_percent'], 2) ?>%</span>
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
