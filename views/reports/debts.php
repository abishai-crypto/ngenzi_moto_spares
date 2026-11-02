<?php
use App\Core\Helper;
use App\Core\Auth;
?>

<!-- Header -->
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-1 text-dark">
            <i class="bi bi-credit-card-2-front-fill text-danger me-2"></i>Ripoti ya Madeni & Ukuaji wa Madeni (Debt Aging Report)
        </h4>
        <p class="text-muted small mb-0">
            Jumla ya Madeni Yanayodaiwa: <strong class="text-danger">TSh <?= number_format($aging['total_outstanding'], 2) ?></strong>
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

<!-- 6 Aging Buckets Cards -->
<div class="row g-3 mb-4">
    <?php foreach ($aging['buckets'] as $key => $b): ?>
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="card border-0 shadow-sm rounded-3 h-100 p-2 text-center bg-light">
                <div class="small text-muted fw-semibold mb-1"><?= htmlspecialchars($b['label']) ?></div>
                <h6 class="fw-bold text-danger mb-1">TSh <?= number_format($b['amount'], 2) ?></h6>
                <span class="badge bg-secondary-subtle text-secondary small"><?= number_format($b['count']) ?> Ankara</span>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Detailed Credit Sales / Outstanding Invoices Table -->
<div class="card border-0 shadow-sm rounded-3">
    <div class="card-header bg-white p-3 border-bottom d-flex justify-content-between align-items-center">
        <h6 class="fw-bold text-dark mb-0"><i class="bi bi-list-check me-2"></i>Orodha ya Ankara Zenye Madeni (Outstanding Invoices)</h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Ankara</th>
                        <th>Mteja</th>
                        <th>Tarehe ya Mauzo</th>
                        <th>Tarehe ya Kulipa (Due)</th>
                        <th class="text-end">Kiasi cha Mauzo</th>
                        <th class="text-end">Kilicholipwa</th>
                        <th class="text-end">Deni Lililobaki (TSh)</th>
                        <th class="text-center">Siku Zilizopita</th>
                        <th class="text-end pe-3">Kitendo</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($aging['debts'])): ?>
                        <tr>
                            <td colspan="9" class="text-center py-5 text-muted">
                                <i class="bi bi-check-circle fs-1 text-success d-block mb-2"></i>
                                Hakuna madeni yanayodaiwa kwa sasa.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($aging['debts'] as $d): ?>
                            <tr>
                                <td class="ps-3">
                                    <span class="badge bg-light text-dark border font-monospace">
                                        <?= htmlspecialchars($d['invoice_no']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($d['customer_name']) ?></div>
                                    <small class="text-muted"><?= htmlspecialchars($d['customer_phone'] ?? '—') ?></small>
                                </td>
                                <td><?= date('d/m/Y', strtotime($d['sale_date'])) ?></td>
                                <td>
                                    <?php if (!empty($d['due_date'])): ?>
                                        <span class="fw-semibold <?= ($d['overdue_days'] > 0) ? 'text-danger' : 'text-dark' ?>">
                                            <?= date('d/m/Y', strtotime($d['due_date'])) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">Haijawekwa</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end"><?= number_format($d['total_amount'], 2) ?></td>
                                <td class="text-end text-success"><?= number_format($d['paid_amount'], 2) ?></td>
                                <td class="text-end fw-bold text-danger"><?= number_format($d['balance_due'], 2) ?></td>
                                <td class="text-center">
                                    <?php if ($d['overdue_days'] > 0): ?>
                                        <span class="badge bg-danger-subtle text-danger fw-bold">Siku <?= $d['overdue_days'] ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-success-subtle text-success">Haijapita</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end pe-3">
                                    <a href="/index.php?route=reports/customer_statement&customer_id=<?= $d['customer_id'] ?>" class="btn btn-sm btn-outline-primary" title="Tazama Statement">
                                        <i class="bi bi-file-earmark-person"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
