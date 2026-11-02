<?php
use App\Core\Helper;
use App\Core\Auth;
?>

<!-- Header -->
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-1 text-dark">
            <i class="bi bi-cash-coin text-success me-2"></i>Ripoti ya Malipo na Mikusanyo ya Fedha (Cash Collections)
        </h4>
        <p class="text-muted small mb-0">
            Kipindi: <strong class="text-dark"><?= htmlspecialchars($range['label']) ?></strong> &bull; Jumla Iliyokusanywa: <strong class="text-success">TSh <?= number_format($paymentMethods['grand_total'], 2) ?></strong>
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
            <input type="hidden" name="route" value="reports/payments">

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

<div class="row g-4 mb-4">
    <!-- Payment Methods Summary -->
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm rounded-3 h-100">
            <div class="card-header bg-white p-3 border-bottom">
                <h6 class="fw-bold text-dark mb-0"><i class="bi bi-pie-chart text-success me-2"></i>Mkusanyiko kwa Njia za Malipo</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Njia ya Malipo</th>
                                <th class="text-center">Miamala</th>
                                <th class="text-end">Kiasi (TSh)</th>
                                <th class="text-end pe-3">%</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($paymentMethods['methods'])): ?>
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">Hakuna malipo yaliyopokelewa.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($paymentMethods['methods'] as $pm): ?>
                                    <tr>
                                        <td class="ps-3 fw-bold text-dark">
                                            <i class="bi bi-credit-card me-2 text-primary"></i><?= htmlspecialchars($pm['payment_method']) ?>
                                        </td>
                                        <td class="text-center fw-semibold"><?= number_format($pm['transaction_count']) ?></td>
                                        <td class="text-end fw-bold text-success"><?= number_format($pm['total_amount'], 2) ?></td>
                                        <td class="text-end pe-3">
                                            <span class="badge bg-success-subtle text-success fw-bold"><?= number_format($pm['percentage'], 1) ?>%</span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <tr class="table-light fw-bold">
                                    <td class="ps-3" colspan="2">JUMLA KUU ILIYOKUSANYWA</td>
                                    <td class="text-end text-success fs-6">TSh <?= number_format($paymentMethods['grand_total'], 2) ?></td>
                                    <td class="text-end pe-3 text-success">100.00%</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Visual Progress of Payment Methods -->
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm rounded-3 h-100">
            <div class="card-header bg-white p-3 border-bottom">
                <h6 class="fw-bold text-dark mb-0"><i class="bi bi-bar-chart text-primary me-2"></i>Uwiano wa Njia za Malipo (Visual Share)</h6>
            </div>
            <div class="card-body p-3">
                <?php if (!empty($paymentMethods['methods'])): ?>
                    <?php foreach ($paymentMethods['methods'] as $pm): ?>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between small mb-1">
                                <span class="fw-semibold text-dark"><?= htmlspecialchars($pm['payment_method']) ?></span>
                                <span class="text-success fw-bold">TSh <?= number_format($pm['total_amount'], 2) ?> (<?= $pm['percentage'] ?>%)</span>
                            </div>
                            <div class="progress" style="height: 10px;">
                                <div class="progress-bar bg-success" role="progressbar" style="width: <?= $pm['percentage'] ?>%"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Detailed Cash Collection Ledger -->
<div class="card border-0 shadow-sm rounded-3">
    <div class="card-header bg-white p-3 border-bottom d-flex justify-content-between align-items-center">
        <h6 class="fw-bold text-dark mb-0"><i class="bi bi-list-ul me-2"></i>Miamala ya Mikusanyo (Detailed Payment Transactions)</h6>
        <span class="badge bg-light text-dark border">Jumla: TSh <?= number_format($cashCollections['total_collected'], 2) ?></span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Tarehe & Saa</th>
                        <th>Namba ya Malipo</th>
                        <th>Ankara (Invoice)</th>
                        <th>Mteja</th>
                        <th>Njia ya Malipo</th>
                        <th>Kumbukumbu (Ref)</th>
                        <th class="text-end">Kiasi (TSh)</th>
                        <th class="pe-3">Mpokeaji</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($cashCollections['data'])): ?>
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">Hakuna kumbukumbu za malipo.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($cashCollections['data'] as $cc): ?>
                            <tr>
                                <td class="ps-3 text-nowrap"><?= date('d/m/Y H:i', strtotime($cc['payment_date'])) ?></td>
                                <td><span class="badge bg-light text-dark border font-monospace"><?= htmlspecialchars($cc['payment_no'] ?? 'PAY-'.$cc['id']) ?></span></td>
                                <td><span class="badge bg-light text-primary border font-monospace"><?= htmlspecialchars($cc['invoice_no']) ?></span></td>
                                <td><div class="fw-semibold text-dark"><?= htmlspecialchars($cc['customer_name'] ?? 'Walk-in') ?></div></td>
                                <td>
                                    <span class="badge bg-secondary-subtle text-secondary"><?= htmlspecialchars($cc['payment_method']) ?></span>
                                    <?php if (!empty($cc['provider'])): ?>
                                        <small class="text-muted">(<?= htmlspecialchars($cc['provider']) ?>)</small>
                                    <?php endif; ?>
                                </td>
                                <td><span class="small font-monospace text-muted"><?= htmlspecialchars($cc['reference_no'] ?? '—') ?></span></td>
                                <td class="text-end fw-bold text-success"><?= number_format($cc['amount'], 2) ?></td>
                                <td class="pe-3"><small class="text-muted"><?= htmlspecialchars($cc['received_by_name']) ?></small></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
