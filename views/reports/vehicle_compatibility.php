<?php
use App\Core\Helper;
use App\Core\Auth;
?>

<!-- Header -->
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-1 text-dark">
            <i class="bi bi-bicycle text-primary me-2"></i>Mauzo kwa Aina ya Chombo (Motorcycle & Bajaj Compatibility)
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

<!-- Filters Bar -->
<div class="card border-0 shadow-sm rounded-3 mb-4">
    <div class="card-body p-3">
        <form method="GET" action="/index.php" class="row g-2 align-items-center">
            <input type="hidden" name="route" value="reports/vehicle_compatibility">

            <div class="col-md-3 col-sm-6">
                <select name="vehicle_type" class="form-select form-select-sm">
                    <option value="">-- Aina Zote za Vyombo --</option>
                    <option value="Motorcycle" <?= ($vehicleType === 'Motorcycle') ? 'selected' : '' ?>>Pikipiki Pekee (Motorcycle)</option>
                    <option value="Bajaj" <?= ($vehicleType === 'Bajaj') ? 'selected' : '' ?>>Bajaji Pekee (Three-Wheeler)</option>
                </select>
            </div>

            <div class="col-md-3 col-sm-6">
                <select name="period" class="form-select form-select-sm" onchange="if(this.value!='custom') this.form.submit();">
                    <option value="today" <?= ($range['period'] === 'today') ? 'selected' : '' ?>>Leo</option>
                    <option value="this_week" <?= ($range['period'] === 'this_week') ? 'selected' : '' ?>>Wiki Hii</option>
                    <option value="this_month" <?= ($range['period'] === 'this_month') ? 'selected' : '' ?>>Mwezi Huu</option>
                    <option value="last_month" <?= ($range['period'] === 'last_month') ? 'selected' : '' ?>>Mwezi Uliopita</option>
                    <option value="this_year" <?= ($range['period'] === 'this_year') ? 'selected' : '' ?>>Mwaka Huu</option>
                    <option value="custom" <?= ($range['period'] === 'custom') ? 'selected' : '' ?>>Custom</option>
                </select>
            </div>

            <div class="col-md-2 col-sm-6">
                <input type="date" name="start_date" class="form-control form-control-sm" value="<?= htmlspecialchars($range['start_date']) ?>">
            </div>

            <div class="col-md-2 col-sm-6">
                <input type="date" name="end_date" class="form-control form-control-sm" value="<?= htmlspecialchars($range['end_date']) ?>">
            </div>

            <div class="col-md-2 col-sm-12 d-flex gap-1">
                <button type="submit" class="btn btn-primary btn-sm w-100"><i class="bi bi-funnel"></i> Chuja</button>
                <a href="/index.php?route=reports/vehicle_compatibility" class="btn btn-outline-secondary btn-sm"><i class="bi bi-x-circle"></i></a>
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
                        <th>Mfano wa Chombo (Vehicle Model)</th>
                        <th>Aina ya Chombo</th>
                        <th class="text-center">Vipuri Vinavyoingiana</th>
                        <th class="text-center">Idadi Iliyouzwa</th>
                        <th class="text-end">Mapato Halisi (TSh)</th>
                        <th class="text-end">COGS</th>
                        <th class="text-end">Faida Ghafi</th>
                        <th class="text-end pe-3">Margin %</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($compatibilities)): ?>
                        <tr>
                            <td colspan="9" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                Hakuna mauzo ya uoanifu wa vyombo yaliyopatikana katika kipindi hiki.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php 
                        $totalQty = 0; $totalRev = 0; $totalCogs = 0; $totalProfit = 0;
                        foreach ($compatibilities as $idx => $comp): 
                            $totalQty += (int)$comp['total_qty_sold'];
                            $totalRev += (float)$comp['total_revenue'];
                            $totalCogs += (float)$comp['total_cogs'];
                            $totalProfit += (float)$comp['gross_profit'];
                        ?>
                            <tr>
                                <td class="ps-3 text-muted"><?= $idx + 1 ?></td>
                                <td class="fw-bold text-dark">
                                    <?= htmlspecialchars($comp['vehicle_model']) ?>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">
                                        <?= htmlspecialchars(ucfirst($comp['vehicle_type'])) ?>
                                    </span>
                                </td>
                                <td class="text-center"><?= number_format($comp['compatible_products_sold']) ?></td>
                                <td class="text-center fw-bold"><?= number_format($comp['total_qty_sold']) ?></td>
                                <td class="text-end fw-semibold text-primary"><?= number_format($comp['total_revenue'], 2) ?></td>
                                <td class="text-end text-muted"><?= number_format($comp['total_cogs'], 2) ?></td>
                                <td class="text-end fw-bold text-success"><?= number_format($comp['gross_profit'], 2) ?></td>
                                <td class="text-end pe-3">
                                    <span class="badge bg-success-subtle text-success fw-bold"><?= number_format($comp['gross_margin'], 1) ?>%</span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <!-- Grand Total -->
                        <?php $overallMargin = ($totalRev > 0) ? round(($totalProfit / $totalRev) * 100, 2) : 0.00; ?>
                        <tr class="table-light fw-bold">
                            <td class="ps-3" colspan="4">JUMLA KUU</td>
                            <td class="text-center"><?= number_format($totalQty) ?></td>
                            <td class="text-end text-primary">TSh <?= number_format($totalRev, 2) ?></td>
                            <td class="text-end text-muted">TSh <?= number_format($totalCogs, 2) ?></td>
                            <td class="text-end text-success">TSh <?= number_format($totalProfit, 2) ?></td>
                            <td class="text-end pe-3 text-success"><?= number_format($overallMargin, 2) ?>%</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
