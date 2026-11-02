<?php
use App\Core\Helper;
use App\Core\Auth;
$isReversed = ($payment['status'] === 'reversed');
?>

<!-- Header -->
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <a href="/index.php?route=payments" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> Rudi kwenye Malipo
            </a>
            <h4 class="fw-bold mb-0">
                Risiti ya Malipo #<span class="font-monospace text-primary"><?= htmlspecialchars($payment['payment_no']) ?></span>
            </h4>
            <?php if ($isReversed): ?>
                <span class="badge bg-danger-subtle text-danger border border-danger-subtle">
                    Imeghairiwa (Reversed)
                </span>
            <?php else: ?>
                <span class="badge bg-success-subtle text-success border border-success-subtle">
                    Imekamilika (Completed)
                </span>
            <?php endif; ?>
        </div>
        <p class="text-muted small mb-0">Muhtasari kamili wa muamala wa malipo na athari zake kwenye ankara na akaunti ya mteja.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="/index.php?route=payment_receipt&id=<?= $payment['id'] ?>" target="_blank" class="btn btn-primary-custom btn-sm d-flex align-items-center gap-1">
            <i class="bi bi-printer"></i> Chapisha Risiti
        </a>
    </div>
</div>

<div class="row g-4">
    <!-- Left Column: Payment Details Card -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="fw-bold mb-0 text-dark">
                    <i class="bi bi-receipt-cutoff text-primary me-2"></i>Taarifa za Muamala
                </h6>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-sm-6">
                        <label class="small text-muted mb-0">Nambari ya Risiti (Payment Ref)</label>
                        <h5 class="fw-bold font-monospace text-primary mb-0"><?= htmlspecialchars($payment['payment_no']) ?></h5>
                    </div>
                    <div class="col-sm-6">
                        <label class="small text-muted mb-0">Tarehe na Saa ya Malipo</label>
                        <h6 class="fw-bold font-monospace text-dark mb-0"><?= date('d M Y, H:i', strtotime($payment['payment_date'])) ?></h6>
                    </div>
                    <div class="col-sm-6">
                        <label class="small text-muted mb-0">Kiasi Kilicholipwa</label>
                        <h3 class="fw-bold font-monospace <?= $isReversed ? 'text-decoration-line-through text-muted' : 'text-success' ?> mb-0">
                            <?= Helper::formatCurrency((float)$payment['amount']) ?>
                        </h3>
                    </div>
                    <div class="col-sm-6">
                        <label class="small text-muted mb-0">Njia ya Malipo</label>
                        <h5 class="fw-bold text-dark mb-0">
                            <i class="bi bi-credit-card-2-front text-primary me-1"></i>
                            <?= htmlspecialchars($payment['payment_method']) ?>
                            <?= !empty($payment['provider']) ? '<span class="text-muted small">(' . htmlspecialchars($payment['provider']) . ')</span>' : '' ?>
                        </h5>
                    </div>
                    <?php if (!empty($payment['reference_no'])): ?>
                        <div class="col-sm-6">
                            <label class="small text-muted mb-0">Nambari ya Kumbukumbu (Tx Reference)</label>
                            <div class="font-monospace fw-semibold text-dark"><?= htmlspecialchars($payment['reference_no']) ?></div>
                        </div>
                    <?php endif; ?>
                    <div class="col-sm-6">
                        <label class="small text-muted mb-0">Mpokeaji / Kashia (Received By)</label>
                        <div class="fw-semibold text-dark">
                            <i class="bi bi-person-circle text-secondary me-1"></i>
                            <?= htmlspecialchars($payment['received_by_name'] ?? 'Staff') ?>
                        </div>
                    </div>
                    <?php if (!empty($payment['notes'])): ?>
                        <div class="col-12">
                            <label class="small text-muted mb-0">Maelezo ya Ziada (Notes)</label>
                            <div class="p-2 bg-light rounded small"><?= nl2br(htmlspecialchars($payment['notes'])) ?></div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Reversal Box if Reversed -->
                <?php if ($isReversed): ?>
                    <div class="alert alert-danger mt-4 mb-0 border-0 shadow-sm">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <i class="bi bi-exclamation-octagon-fill fs-5 text-danger"></i>
                            <h6 class="fw-bold mb-0 text-danger">Malipo Haya Yameghairiwa (Payment Reversed)</h6>
                        </div>
                        <div class="small">
                            <p class="mb-1"><strong>Imeghairiwa na:</strong> <?= htmlspecialchars($payment['reversed_by_name'] ?? 'Msimamizi') ?> mnamo <?= date('d/m/Y H:i', strtotime($payment['reversed_at'])) ?></p>
                            <p class="mb-0"><strong>Sababu:</strong> <?= htmlspecialchars($payment['reversal_reason']) ?></p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Right Column: Associated Invoice & Customer -->
    <div class="col-lg-4">
        <!-- Associated Sale Invoice Card -->
        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="fw-bold mb-0 text-dark">
                    <i class="bi bi-file-earmark-text text-primary me-2"></i>Ankara Inayolipwa
                </h6>
            </div>
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small">Ankara #:</span>
                    <a href="/index.php?route=sale_show&id=<?= $payment['sale_id'] ?>" class="font-monospace fw-bold text-decoration-none">
                        <?= htmlspecialchars($payment['invoice_no']) ?>
                    </a>
                </div>
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small">Jumla ya Ankara:</span>
                    <span class="font-monospace fw-bold text-dark"><?= Helper::formatCurrency((float)$payment['sale_total_amount']) ?></span>
                </div>
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small">Jumla Iliyolipwa:</span>
                    <span class="font-monospace fw-bold text-success"><?= Helper::formatCurrency((float)$payment['sale_paid_amount']) ?></span>
                </div>
                <div class="d-flex align-items-center justify-content-between mb-3 border-top pt-2">
                    <span class="text-muted small">Salio Linalodaiwa:</span>
                    <span class="font-monospace fw-bold text-danger"><?= Helper::formatCurrency((float)$payment['sale_balance_due']) ?></span>
                </div>
                <a href="/index.php?route=sale_show&id=<?= $payment['sale_id'] ?>" class="btn btn-outline-primary btn-sm w-100">
                    <i class="bi bi-box-arrow-up-right me-1"></i> Fungua Ankara Kamili
                </a>
            </div>
        </div>

        <!-- Associated Customer Card -->
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="fw-bold mb-0 text-dark">
                    <i class="bi bi-person text-primary me-2"></i>Mteja Husika
                </h6>
            </div>
            <div class="card-body p-3">
                <h6 class="fw-bold text-dark mb-1"><?= htmlspecialchars($payment['customer_name']) ?></h6>
                <?php if (!empty($payment['customer_code'])): ?>
                    <p class="text-muted small font-monospace mb-1">Kodi: <?= htmlspecialchars($payment['customer_code']) ?></p>
                <?php endif; ?>
                <?php if (!empty($payment['customer_phone'])): ?>
                    <p class="text-muted small mb-1">Simu: <?= htmlspecialchars($payment['customer_phone']) ?></p>
                <?php endif; ?>
                <?php if (!empty($payment['customer_id'])): ?>
                    <div class="mt-3 d-flex gap-2">
                        <a href="/index.php?route=debt_customer_profile&id=<?= $payment['customer_id'] ?>" class="btn btn-outline-secondary btn-sm flex-fill">
                            Wasifu wa Deni
                        </a>
                        <a href="/index.php?route=debt_statement&customer_id=<?= $payment['customer_id'] ?>" class="btn btn-outline-primary btn-sm flex-fill">
                            Statement
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
