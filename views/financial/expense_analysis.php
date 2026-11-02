<?php
use App\Core\Helper;
use App\Core\Auth;
?>

<!-- Header -->
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-1 text-dark">
            <i class="bi bi-pie-chart text-danger me-2"></i>Uchambuzi wa Gharama za Uendeshaji (Expense Analysis)
        </h4>
        <p class="text-muted small mb-0">
            Kipindi: <strong class="text-dark"><?= htmlspecialchars($range['label']) ?></strong> &bull; Jumla ya Matumizi: <strong class="text-danger">TSh <?= number_format($categoryExpenses['grand_total'], 2) ?></strong>
        </p>
    </div>

    <div class="d-flex flex-wrap gap-2">
        <a href="/index.php?route=expenses" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-wallet2 me-1"></i> Orodha ya Matumizi
        </a>
        <a href="/index.php?route=expenses/create" class="btn btn-danger btn-sm">
            <i class="bi bi-plus-circle me-1"></i> Rekodi Matumizi Mapya
        </a>
    </div>
</div>

<!-- Date Filter -->
<div class="card border-0 shadow-sm rounded-3 mb-4">
    <div class="card-body p-3">
        <form method="GET" action="/index.php" class="row g-2 align-items-center">
            <input type="hidden" name="route" value="financial/expense_analysis">

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

<div class="row g-4">
    <!-- Category Breakdown Table -->
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header bg-white p-3 border-bottom">
                <h6 class="fw-bold text-dark mb-0"><i class="bi bi-list-check me-2"></i>Mgawanyo wa Matumizi kwa Kategoria</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Kategoria ya Matumizi</th>
                                <th class="text-center">Miamala</th>
                                <th class="text-end">Kiasi (TSh)</th>
                                <th class="text-end pe-3">Asilimia (%)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($categoryExpenses['categories'])): ?>
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">Hakuna matumizi yaliyorekodiwa katika kipindi hiki.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($categoryExpenses['categories'] as $cat): ?>
                                    <tr>
                                        <td class="ps-3">
                                            <div class="fw-bold text-dark"><?= htmlspecialchars($cat['category_name']) ?></div>
                                            <small class="text-muted font-monospace"><?= htmlspecialchars($cat['category_code'] ?? 'GENERAL') ?></small>
                                        </td>
                                        <td class="text-center"><?= number_format($cat['total_count']) ?></td>
                                        <td class="text-end fw-bold text-danger"><?= number_format($cat['total_amount'], 2) ?></td>
                                        <td class="text-end pe-3">
                                            <span class="badge bg-danger-subtle text-danger fw-bold"><?= number_format($cat['percentage'], 2) ?>%</span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <tr class="table-light fw-bold">
                                    <td class="ps-3" colspan="2">JUMLA KUU YA MATUMIZI</td>
                                    <td class="text-end text-danger fs-6">TSh <?= number_format($categoryExpenses['grand_total'], 2) ?></td>
                                    <td class="text-end pe-3 text-danger">100.00%</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Visual Bars & Daily Trend -->
    <div class="col-lg-5">
        <!-- Visual Bars -->
        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-header bg-white p-3 border-bottom">
                <h6 class="fw-bold text-dark mb-0"><i class="bi bi-bar-chart text-danger me-2"></i>Uwiano wa Matumizi (Visual Share)</h6>
            </div>
            <div class="card-body p-3">
                <?php if (empty($categoryExpenses['categories'])): ?>
                    <div class="text-center py-3 text-muted">Hakuna taarifa.</div>
                <?php else: ?>
                    <?php foreach ($categoryExpenses['categories'] as $cat): ?>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between small mb-1">
                                <span class="fw-semibold text-dark"><?= htmlspecialchars($cat['category_name']) ?></span>
                                <span class="text-danger fw-bold">TSh <?= number_format($cat['total_amount'], 2) ?> (<?= $cat['percentage'] ?>%)</span>
                            </div>
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar bg-danger" role="progressbar" style="width: <?= $cat['percentage'] ?>%"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Daily Trend Table -->
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header bg-white p-3 border-bottom">
                <h6 class="fw-bold text-dark mb-0"><i class="bi bi-calendar-event me-2"></i>Mwenendo wa Matumizi kwa Siku</h6>
            </div>
            <div class="card-body p-0" style="max-height: 250px; overflow-y: auto;">
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Tarehe</th>
                                <th class="text-center">Miamala</th>
                                <th class="text-end pe-3">Kiasi (TSh)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($trend)): ?>
                                <tr>
                                    <td colspan="3" class="text-center py-3 text-muted">Hakuna matumizi yaliyopatikana.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($trend as $tr): ?>
                                    <tr>
                                        <td class="ps-3 fw-semibold"><?= date('d/m/Y', strtotime($tr['period_label'])) ?></td>
                                        <td class="text-center"><?= number_format($tr['transaction_count']) ?></td>
                                        <td class="text-end pe-3 text-danger fw-bold"><?= number_format($tr['total_amount'], 2) ?></td>
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
