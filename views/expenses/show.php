<?php
use App\Core\Helper;
use App\Core\Auth;
?>

<div class="row justify-content-center">
    <div class="col-lg-9 col-md-11">
        <!-- Header -->
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
            <div>
                <h4 class="fw-bold mb-1 text-dark">
                    <i class="bi bi-receipt text-primary me-2"></i>Vocha ya Matumizi #<?= htmlspecialchars($expense['expense_no']) ?>
                </h4>
                <p class="text-muted small mb-0">Iliandikwa tarehe: <strong><?= date('d/m/Y H:i', strtotime($expense['created_at'])) ?></strong></p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="/index.php?route=expenses" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left me-1"></i> Orodha ya Matumizi
                </a>
                <button type="button" onclick="window.print()" class="btn btn-outline-dark btn-sm">
                    <i class="bi bi-printer me-1"></i> Chapisha Vocha
                </button>
            </div>
        </div>

        <!-- Voucher Card -->
        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-header bg-white p-4 border-bottom">
                <div class="row align-items-center">
                    <div class="col-sm-6">
                        <h5 class="fw-bold text-dark mb-1">NGENZI MOTO SPARE PARTS</h5>
                        <div class="small text-muted">Kariakoo, Dar es Salaam, Tanzania</div>
                        <div class="small text-muted">Simu: +255 700 000 000 | Email: info@ngenzimotospare.co.tz</div>
                    </div>
                    <div class="col-sm-6 text-sm-end mt-3 mt-sm-0">
                        <span class="badge bg-light text-dark border fs-6 font-monospace p-2">
                            <?= htmlspecialchars($expense['expense_no']) ?>
                        </span>
                        <div class="mt-2">
                            <?php
                            $statusBadgeMap = [
                                'approved'  => 'bg-success text-white',
                                'recorded'  => 'bg-info text-dark',
                                'rejected'  => 'bg-danger text-white',
                                'cancelled' => 'bg-dark text-white',
                            ];
                            $statusLabelMap = [
                                'approved'  => 'IMEIDHINISHWA (APPROVED)',
                                'recorded'  => 'IMEREKODIWA (RECORDED)',
                                'rejected'  => 'IMEKATALIWA (REJECTED)',
                                'cancelled' => 'IMEGHAIRIWA (CANCELLED)',
                            ];
                            $badgeClass = $statusBadgeMap[$expense['status']] ?? 'bg-secondary text-white';
                            $statusLabel = $statusLabelMap[$expense['status']] ?? strtoupper($expense['status']);
                            ?>
                            <span class="badge <?= $badgeClass ?> px-3 py-2 rounded-pill fw-bold">
                                <?= $statusLabel ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-body p-4">
                <div class="row g-4 mb-4">
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded-3">
                            <div class="text-muted small">Kategoria ya Gharama:</div>
                            <div class="fw-bold text-dark fs-5"><?= htmlspecialchars($expense['category_name']) ?></div>
                            <div class="small text-muted font-monospace"><?= htmlspecialchars($expense['category_code'] ?? 'GENERAL') ?></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded-3 text-md-end">
                            <div class="text-muted small">Kiasi Kilicholipwa:</div>
                            <div class="fw-bold text-danger fs-3">TSh <?= number_format($expense['amount'], 2) ?></div>
                            <div class="small text-muted">Tarehe: <?= date('d/m/Y', strtotime($expense['expense_date'])) ?></div>
                        </div>
                    </div>
                </div>

                <!-- Transaction Details Table -->
                <div class="table-responsive mb-4">
                    <table class="table table-bordered align-middle">
                        <tbody>
                            <tr>
                                <th class="bg-light w-25">Maelezo ya Matumizi</th>
                                <td class="fw-semibold"><?= htmlspecialchars($expense['description']) ?></td>
                            </tr>
                            <tr>
                                <th class="bg-light">Mlipwaji (Payee)</th>
                                <td><?= !empty($expense['payee']) ? htmlspecialchars($expense['payee']) : '<span class="text-muted">Haijawekwa</span>' ?></td>
                            </tr>
                            <tr>
                                <th class="bg-light">Njia ya Malipo</th>
                                <td>
                                    <span class="badge bg-light text-dark border"><?= htmlspecialchars($expense['payment_method']) ?></span>
                                    <?php if (!empty($expense['provider'])): ?>
                                        <span class="badge bg-light text-dark border"><?= htmlspecialchars($expense['provider']) ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <th class="bg-light">Kumbukumbu ya Malipo (Ref No)</th>
                                <td class="font-monospace"><?= !empty($expense['reference_no']) ? htmlspecialchars($expense['reference_no']) : '<span class="text-muted">N/A</span>' ?></td>
                            </tr>
                            <?php if (!empty($expense['notes'])): ?>
                                <tr>
                                    <th class="bg-light">Maelezo ya Ziada (Notes)</th>
                                    <td><?= nl2br(htmlspecialchars($expense['notes'])) ?></td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Audit Information -->
                <div class="row g-3 pt-3 border-top">
                    <div class="col-md-4">
                        <div class="small text-muted">Mrekodi (Recorded By):</div>
                        <div class="fw-semibold text-dark"><?= htmlspecialchars($expense['recorder_name'] ?? 'System') ?></div>
                        <div class="small text-muted"><?= date('d/m/Y H:i', strtotime($expense['created_at'])) ?></div>
                    </div>

                    <div class="col-md-4">
                        <div class="small text-muted">Aliyeidhinisha (Approved By):</div>
                        <?php if (!empty($expense['approver_name'])): ?>
                            <div class="fw-semibold text-success"><i class="bi bi-check-circle me-1"></i><?= htmlspecialchars($expense['approver_name']) ?></div>
                            <div class="small text-muted"><?= date('d/m/Y H:i', strtotime($expense['approved_at'])) ?></div>
                        <?php else: ?>
                            <div class="text-muted fst-italic">Bado haijaidhinishwa</div>
                        <?php endif; ?>
                    </div>

                    <div class="col-md-4">
                        <?php if ($expense['status'] === 'cancelled'): ?>
                            <div class="small text-muted">Aliyeghairi (Cancelled By):</div>
                            <div class="fw-semibold text-danger"><i class="bi bi-x-circle me-1"></i><?= htmlspecialchars($expense['canceller_name']) ?></div>
                            <div class="small text-muted"><?= date('d/m/Y H:i', strtotime($expense['cancelled_at'])) ?></div>
                            <div class="small text-danger fw-bold mt-1">Sababu: <?= htmlspecialchars($expense['cancel_reason']) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Action Controls -->
            <div class="card-footer bg-light p-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <span class="text-muted small">Hadhi ya Sasa: <strong class="text-dark"><?= ucfirst($expense['status']) ?></strong></span>
                </div>

                <div class="d-flex flex-wrap gap-2">
                    <!-- Approval Controls -->
                    <?php if ($expense['status'] === 'recorded' && (Auth::hasPermission('expenses.approve') || Auth::hasRole(['super_admin', 'manager', 'accountant']))): ?>
                        <form action="/index.php?route=expenses/approve" method="POST" class="d-inline" onsubmit="return confirm('Je, una uhakika unataka kuidhinisha matumizi haya?');">
                            <input type="hidden" name="csrf_token" value="<?= Helper::generateCSRF() ?>">
                            <input type="hidden" name="id" value="<?= $expense['id'] ?>">
                            <button type="submit" class="btn btn-success btn-sm">
                                <i class="bi bi-check2-circle me-1"></i> Idhinisha (Approve)
                            </button>
                        </form>

                        <button type="button" class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#rejectModal">
                            <i class="bi bi-x-circle me-1"></i> Kataa (Reject)
                        </button>
                    <?php endif; ?>

                    <!-- Cancellation Controls (Controlled Cancellation, never hard-delete) -->
                    <?php if ($expense['status'] !== 'cancelled' && (Auth::hasPermission('expenses.cancel') || Auth::hasRole(['super_admin', 'manager']))): ?>
                        <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#cancelModal">
                            <i class="bi bi-slash-circle me-1"></i> Ghairi Matumizi (Cancel Expense)
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="/index.php?route=expenses/reject" method="POST">
                <input type="hidden" name="csrf_token" value="<?= Helper::generateCSRF() ?>">
                <input type="hidden" name="id" value="<?= $expense['id'] ?>">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold text-danger"><i class="bi bi-x-circle me-1"></i> Kataa Matumizi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Sababu ya Kukataa <span class="text-danger">*</span></label>
                        <textarea name="reason" class="form-control" rows="3" required placeholder="Andika sababu kwanini matumizi haya yamekataliwa..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Funga</button>
                    <button type="submit" class="btn btn-warning fw-semibold">Thibitisha Kukataa</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Cancel Modal -->
<div class="modal fade" id="cancelModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="/index.php?route=expenses/cancel" method="POST">
                <input type="hidden" name="csrf_token" value="<?= Helper::generateCSRF() ?>">
                <input type="hidden" name="id" value="<?= $expense['id'] ?>">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold text-danger"><i class="bi bi-slash-circle me-1"></i> Ghairi Matumizi Haya</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning small">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i>
                        Kughairi matumizi kutaondoa kiasi hiki (TSh <?= number_format($expense['amount'], 2) ?>) kwenye hesabu za faida halisi. Kumbukumbu haitafutwa ili kuhakikisha ukaguzi wa kifedha (Audit Trail).
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Sababu ya Kughairi <span class="text-danger">*</span></label>
                        <textarea name="reason" class="form-control" rows="3" required placeholder="Andika sababu ya kughairi matumizi haya..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Funga</button>
                    <button type="submit" class="btn btn-danger fw-semibold">Thibitisha Kughairi</button>
                </div>
            </form>
        </div>
    </div>
</div>
