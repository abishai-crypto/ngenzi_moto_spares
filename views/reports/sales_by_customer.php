<?php
use App\Core\Helper;
use App\Core\Auth;
?>

<!-- Header -->
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-1 text-dark">
            <i class="bi bi-person-lines-fill text-info me-2"></i>Mauzo kwa Wateja (Sales by Customer)
        </h4>
        <p class="text-muted small mb-0">
            Kipindi: <strong class="text-dark"><?= htmlspecialchars($range['label']) ?></strong>
        </p>
    </div>

    <div class="d-flex flex-wrap gap-2">
        <a href="/index.php?route=reports" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-speedometer2 me-1"></i> Kituo cha Ripoti
        </a>
        <a href="/index.php?route=reports/customer_statement" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-file-earmark-person me-1"></i> Taarifa ya Mteja (Statement)
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
            <input type="hidden" name="route" value="reports/sales_by_customer">

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

<!-- Customer Sales Table -->
<div class="card border-0 shadow-sm rounded-3">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">#</th>
                        <th>Jina la Mteja</th>
                        <th>Aina ya Mteja</th>
                        <th class="text-center">Ankara (Invoices)</th>
                        <th class="text-center">Vipuri Vilivyonunuliwa</th>
                        <th class="text-end">Jumla ya Thamani (TSh)</th>
                        <th class="text-end">Kilicholipwa (TSh)</th>
                        <th class="text-end pe-3">Deni Lililobaki (TSh)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($customers)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                Hakuna mauzo ya wateja yaliyopatikana katika kipindi hiki.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($customers as $idx => $c): ?>
                            <tr>
                                <td class="ps-3 text-muted"><?= $idx + 1 ?></td>
                                <td>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($c['customer_name']) ?></div>
                                    <small class="text-muted"><i class="bi bi-telephone me-1"></i><?= htmlspecialchars($c['customer_phone'] ?? 'Bila Simu') ?></small>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border"><?= htmlspecialchars(ucfirst($c['customer_type'] ?? 'Regular')) ?></span>
                                </td>
                                <td class="text-center fw-semibold"><?= number_format($c['invoices_count']) ?></td>
                                <td class="text-center"><?= number_format($c['items_purchased']) ?></td>
                                <td class="text-end fw-bold text-primary"><?= number_format($c['total_purchases'], 2) ?></td>
                                <td class="text-end text-success fw-semibold"><?= number_format($c['amount_paid'], 2) ?></td>
                                <td class="text-end pe-3 fw-bold <?= ($c['outstanding_balance'] > 0) ? 'text-danger' : 'text-muted' ?>">
                                    <?= number_format($c['outstanding_balance'], 2) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
