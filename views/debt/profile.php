<?php
use App\Core\Helper;
use App\Core\Auth;

$creditLimit = (float)($customer['credit_limit'] ?? 0.00);
$currentBalance = (float)($customer['current_balance'] ?? 0.00);
$availableCredit = max(0.00, $creditLimit - $currentBalance);
$creditUsagePercent = ($creditLimit > 0) ? min(100, round(($currentBalance / $creditLimit) * 100)) : 0;
?>

<div class="container-fluid px-4 py-3">
    <!-- Breadcrumb & Top Actions -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-3 gap-2">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="/index.php?route=debts">Madeni ya Wateja</a></li>
                <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($customer['name']) ?></li>
            </ol>
        </nav>
        <div class="d-flex flex-wrap gap-2">
            <a href="/index.php?route=debts" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-left me-1"></i> Orodha ya Madeni
            </a>
            <a href="/index.php?route=customer_view&id=<?= $customer['id'] ?>" class="btn btn-outline-info btn-sm">
                <i class="fas fa-user me-1"></i> Wasifu wa Mteja
            </a>
            <a href="/index.php?route=debt_statement&customer_id=<?= $customer['id'] ?>" class="btn btn-outline-primary btn-sm">
                <i class="fas fa-file-invoice-dollar me-1"></i> Taarifa ya Akaunti (Statement)
            </a>
            <?php if (Auth::hasPermission('debt.collect') && $currentBalance > 0): ?>
                <a href="/index.php?route=debt_collect&customer_id=<?= $customer['id'] ?>" class="btn btn-success btn-sm">
                    <i class="fas fa-hand-holding-usd me-1"></i> Kusanya Malipo ya Deni
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Customer Overview Banner -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body p-3 p-md-4">
            <div class="row align-items-center g-3">
                <div class="col-auto">
                    <div class="avatar-lg bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 64px; height: 64px; font-size: 26px;">
                        <i class="fas fa-user-tie"></i>
                    </div>
                </div>
                <div class="col">
                    <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                        <h4 class="fw-bold mb-0 text-dark"><?= htmlspecialchars($customer['name']) ?></h4>
                        <span class="badge bg-secondary"><?= htmlspecialchars($customer['customer_code']) ?></span>
                        <span class="badge bg-light text-dark border"><?= htmlspecialchars(ucfirst($customer['customer_type'])) ?></span>
                        <?php if (($customer['credit_status'] ?? 'active') === 'blocked'): ?>
                            <span class="badge bg-danger"><i class="fas fa-ban me-1"></i> Mkopo Umezuiwa</span>
                        <?php elseif (empty($customer['credit_allowed'])): ?>
                            <span class="badge bg-secondary"><i class="fas fa-times me-1"></i> Hajaruhusiwa Mkopo</span>
                        <?php else: ?>
                            <span class="badge bg-success"><i class="fas fa-check me-1"></i> Mkopo Unaruhusiwa</span>
                        <?php endif; ?>
                    </div>
                    <div class="text-muted small d-flex flex-wrap gap-3">
                        <?php if (!empty($customer['phone'])): ?>
                            <span><i class="fas fa-phone-alt me-1 text-success"></i> <?= htmlspecialchars($customer['phone']) ?></span>
                        <?php endif; ?>
                        <?php if (!empty($customer['area'])): ?>
                            <span><i class="fas fa-map-marker-alt me-1 text-danger"></i> <?= htmlspecialchars($customer['area']) ?></span>
                        <?php endif; ?>
                        <span><i class="fas fa-clock me-1 text-info"></i> Masharti: <strong><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $customer['payment_terms'] ?? '30_days'))) ?></strong></span>
                    </div>
                </div>
                <div class="col-md-auto text-md-end">
                    <div class="small text-muted mb-1">Jumla ya Deni Lililopo</div>
                    <h3 class="fw-bold text-danger font-monospace mb-0">TSh <?= number_format($currentBalance, 2) ?></h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Financial KPIs Row -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card shadow-sm border-0 border-start border-danger border-4 h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted small fw-bold text-uppercase">Deni Linalodaiwa</div>
                            <div class="fs-4 fw-bold text-danger font-monospace">TSh <?= number_format($currentBalance, 2) ?></div>
                        </div>
                        <div class="bg-danger-subtle text-danger p-3 rounded-circle">
                            <i class="fas fa-hand-holding-usd fa-lg"></i>
                        </div>
                    </div>
                    <div class="mt-2 text-muted small">
                        Inajumuisha ankara <strong><?= count($outstandingInvoices) ?></strong> ambazo hazijalipwa
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card shadow-sm border-0 border-start border-primary border-4 h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted small fw-bold text-uppercase">Ukomo wa Mkopo</div>
                            <div class="fs-4 fw-bold text-primary font-monospace">TSh <?= number_format($creditLimit, 2) ?></div>
                        </div>
                        <div class="bg-primary-subtle text-primary p-3 rounded-circle">
                            <i class="fas fa-shield-alt fa-lg"></i>
                        </div>
                    </div>
                    <div class="mt-2 text-muted small">
                        Kiwango cha juu anachoruhusiwa kukopa
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card shadow-sm border-0 border-start border-success border-4 h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted small fw-bold text-uppercase">Nafasi ya Kukopa</div>
                            <div class="fs-4 fw-bold text-success font-monospace">TSh <?= number_format($availableCredit, 2) ?></div>
                        </div>
                        <div class="bg-success-subtle text-success p-3 rounded-circle">
                            <i class="fas fa-coins fa-lg"></i>
                        </div>
                    </div>
                    <div class="mt-2 text-muted small">
                        Salio la mkopo lililobaki kutumika
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card shadow-sm border-0 border-start border-warning border-4 h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted small fw-bold text-uppercase">Matumizi ya Mkopo</div>
                            <div class="fs-4 fw-bold text-warning font-monospace"><?= $creditUsagePercent ?>%</div>
                        </div>
                        <div class="bg-warning-subtle text-warning p-3 rounded-circle">
                            <i class="fas fa-chart-pie fa-lg"></i>
                        </div>
                    </div>
                    <div class="progress mt-2" style="height: 6px;">
                        <div class="progress-bar <?= $creditUsagePercent > 80 ? 'bg-danger' : 'bg-warning' ?>" role="progressbar" style="width: <?= $creditUsagePercent ?>%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Debt Aging Breakdown Matrix -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white py-3 border-bottom">
            <div class="d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold text-dark"><i class="fas fa-hourglass-half me-2 text-warning"></i> Muhtasari wa Uzeekaji wa Deni (Debt Aging Breakdown)</h6>
                <a href="/index.php?route=debt_aging&customer_id=<?= $customer['id'] ?>" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-external-link-alt me-1"></i> Ripoti Kamili
                </a>
            </div>
        </div>
        <div class="card-body p-3">
            <div class="row g-2 text-center">
                <div class="col-6 col-md-2">
                    <div class="p-2 border rounded bg-light">
                        <div class="text-muted small" style="font-size: 11px;">Bado Haijaiva (Current)</div>
                        <div class="fw-bold font-monospace text-success mt-1">TSh <?= number_format((float)($aging['current_not_due'] ?? 0), 2) ?></div>
                    </div>
                </div>
                <div class="col-6 col-md-2">
                    <div class="p-2 border rounded bg-light">
                        <div class="text-muted small" style="font-size: 11px;">Siku 1 – 7</div>
                        <div class="fw-bold font-monospace text-info mt-1">TSh <?= number_format((float)($aging['overdue_1_7'] ?? 0), 2) ?></div>
                    </div>
                </div>
                <div class="col-6 col-md-2">
                    <div class="p-2 border rounded bg-light">
                        <div class="text-muted small" style="font-size: 11px;">Siku 8 – 30</div>
                        <div class="fw-bold font-monospace text-warning mt-1">TSh <?= number_format((float)($aging['overdue_8_30'] ?? 0), 2) ?></div>
                    </div>
                </div>
                <div class="col-6 col-md-2">
                    <div class="p-2 border rounded bg-light">
                        <div class="text-muted small" style="font-size: 11px;">Siku 31 – 60</div>
                        <div class="fw-bold font-monospace text-warning-emphasis mt-1">TSh <?= number_format((float)($aging['overdue_31_60'] ?? 0), 2) ?></div>
                    </div>
                </div>
                <div class="col-6 col-md-2">
                    <div class="p-2 border rounded bg-light">
                        <div class="text-muted small" style="font-size: 11px;">Siku 61 – 90</div>
                        <div class="fw-bold font-monospace text-danger mt-1">TSh <?= number_format((float)($aging['overdue_61_90'] ?? 0), 2) ?></div>
                    </div>
                </div>
                <div class="col-6 col-md-2">
                    <div class="p-2 border rounded bg-danger-subtle border-danger">
                        <div class="text-danger small fw-bold" style="font-size: 11px;">Siku 90+ (Hatari)</div>
                        <div class="fw-bold font-monospace text-danger mt-1">TSh <?= number_format((float)($aging['overdue_90_plus'] ?? 0), 2) ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white border-bottom p-0">
            <ul class="nav nav-tabs card-header-tabs mx-3" id="debtTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active fw-semibold text-danger py-3" id="invoices-tab" data-bs-toggle="tab" data-bs-target="#invoices" type="button" role="tab" aria-controls="invoices" aria-selected="true">
                        <i class="fas fa-file-invoice me-2"></i> Ankara Zisizolipwa (<?= count($outstandingInvoices) ?>)
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link fw-semibold text-secondary py-3" id="payments-tab" data-bs-toggle="tab" data-bs-target="#payments" type="button" role="tab" aria-controls="payments" aria-selected="false">
                        <i class="fas fa-receipt me-2"></i> Historia ya Malipo (<?= count($paymentHistory) ?>)
                    </button>
                </li>
            </ul>
        </div>
        <div class="card-body p-0">
            <div class="tab-content" id="debtTabsContent">
                
                <!-- Tab 1: Outstanding Invoices -->
                <div class="tab-pane fade show active p-3" id="invoices" role="tabpanel" aria-labelledby="invoices-tab">
                    <?php if (empty($outstandingInvoices)): ?>
                        <div class="text-center py-5 text-muted">
                            <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                            <h5>Mteja Hana Deni Lolote!</h5>
                            <p class="small text-muted">Ankara zote za mauzo za mteja huyu zimeshalipwa kikamilifu.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light small">
                                    <tr>
                                        <th>Ankara #</th>
                                        <th>Tarehe ya Mauzo</th>
                                        <th>Tarehe ya Kulipa (Due)</th>
                                        <th>Hali ya Muda</th>
                                        <th class="text-end">Jumla ya Ankara</th>
                                        <th class="text-end">Iliyolipwa</th>
                                        <th class="text-end">Salio Linalodaiwa</th>
                                        <th class="text-center">Kitendo</th>
                                    </tr>
                                </thead>
                                <tbody class="small">
                                    <?php foreach ($outstandingInvoices as $inv): ?>
                                        <tr>
                                            <td class="fw-bold font-monospace">
                                                <a href="/index.php?route=sale_show&id=<?= $inv['id'] ?>" class="text-decoration-none text-primary">
                                                    <?= htmlspecialchars($inv['invoice_no']) ?>
                                                </a>
                                            </td>
                                            <td><?= date('d/m/Y', strtotime($inv['sale_date'])) ?></td>
                                            <td>
                                                <?= $inv['due_date'] ? date('d/m/Y', strtotime($inv['due_date'])) : '<span class="text-muted">—</span>' ?>
                                            </td>
                                            <td>
                                                <?php if (!empty($inv['is_overdue'])): ?>
                                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle">
                                                        <i class="fas fa-exclamation-triangle me-1"></i> Imechelewa siku <?= (int)$inv['days_overdue'] ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-success-subtle text-success border border-success-subtle">
                                                        <i class="fas fa-check me-1"></i> Bado ipo ndani ya muda
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-end font-monospace">TSh <?= number_format($inv['total_amount'], 2) ?></td>
                                            <td class="text-end font-monospace text-success">TSh <?= number_format($inv['paid_amount'], 2) ?></td>
                                            <td class="text-end font-monospace text-danger fw-bold">TSh <?= number_format($inv['balance_due'], 2) ?></td>
                                            <td class="text-center">
                                                <?php if (Auth::hasPermission('debt.collect')): ?>
                                                    <a href="/index.php?route=debt_collect&customer_id=<?= $customer['id'] ?>&sale_id=<?= $inv['id'] ?>" class="btn btn-sm btn-outline-success">
                                                        <i class="fas fa-hand-holding-usd me-1"></i> Lipa Hii
                                                    </a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot class="table-light fw-bold small">
                                    <tr>
                                        <td colspan="6" class="text-end text-uppercase">Jumla ya Salio la Madeni:</td>
                                        <td class="text-end text-danger font-monospace fs-6">TSh <?= number_format($currentBalance, 2) ?></td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Tab 2: Payment History -->
                <div class="tab-pane fade p-3" id="payments" role="tabpanel" aria-labelledby="payments-tab">
                    <?php if (empty($paymentHistory)): ?>
                        <div class="text-center py-5 text-muted">
                            <i class="fas fa-receipt fa-3x mb-3 text-secondary"></i>
                            <h5>Hakuna Rekodi za Malipo</h5>
                            <p class="small text-muted">Mteja huyu bado hajalipa malipo yoyote kwenye mfumo.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light small">
                                    <tr>
                                        <th>Risiti #</th>
                                        <th>Tarehe</th>
                                        <th>Njia ya Malipo</th>
                                        <th>Mtoa Huduma / Kumbukumbu</th>
                                        <th>Kuhusiana na Ankara</th>
                                        <th class="text-end">Kiasi Kilicholipwa</th>
                                        <th>Imepokelewa na</th>
                                        <th>Hali</th>
                                        <th class="text-center">Kitendo</th>
                                    </tr>
                                </thead>
                                <tbody class="small">
                                    <?php foreach ($paymentHistory as $pay): ?>
                                        <tr>
                                            <td class="fw-bold font-monospace">
                                                <a href="/index.php?route=payment_show&id=<?= $pay['id'] ?>" class="text-decoration-none text-dark">
                                                    <?= htmlspecialchars($pay['payment_no']) ?>
                                                </a>
                                            </td>
                                            <td><?= date('d/m/Y H:i', strtotime($pay['payment_date'])) ?></td>
                                            <td>
                                                <span class="badge bg-light text-dark border">
                                                    <?= htmlspecialchars($pay['payment_method']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?= htmlspecialchars($pay['provider'] ?: '—') ?>
                                                <?php if (!empty($pay['reference_no'])): ?>
                                                    <span class="text-muted d-block small font-monospace"><?= htmlspecialchars($pay['reference_no']) ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (!empty($pay['invoice_no'])): ?>
                                                    <a href="/index.php?route=sale_show&id=<?= $pay['sale_id'] ?>" class="text-decoration-none">
                                                        <?= htmlspecialchars($pay['invoice_no']) ?>
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-muted">Malipo ya Jumla</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-end fw-bold font-monospace text-success">
                                                TSh <?= number_format($pay['amount'], 2) ?>
                                            </td>
                                            <td><?= htmlspecialchars($pay['received_by_name'] ?? '—') ?></td>
                                            <td>
                                                <?php if ($pay['status'] === 'completed'): ?>
                                                    <span class="badge bg-success">Imekamilika</span>
                                                <?php elseif ($pay['status'] === 'reversed'): ?>
                                                    <span class="badge bg-danger">Imebatilishwa</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning text-dark"><?= htmlspecialchars(ucfirst($pay['status'])) ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <a href="/index.php?route=payment_receipt&id=<?= $pay['id'] ?>" target="_blank" class="btn btn-outline-secondary btn-sm" title="Chapisha Risiti">
                                                    <i class="fas fa-print"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </div>
</div>
