<?php
use App\Core\Helper;
use App\Core\Auth;
?>

<!-- Header -->
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-1">
            <i class="bi bi-wallet2 text-primary me-2"></i>Orodha ya Malipo (Payment History)
        </h4>
        <p class="text-muted small mb-0">Tazama miamala yote ya malipo yaliyopokelewa kwa ankara za mauzo na madeni ya wateja.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="/index.php?route=payments_daily_collection" class="btn btn-outline-primary btn-sm d-flex align-items-center gap-1">
            <i class="bi bi-calendar-check"></i> Makusanyo ya Siku
        </a>
        <a href="/index.php?route=debt_collect" class="btn btn-primary-custom btn-sm d-flex align-items-center gap-1">
            <i class="bi bi-cash-coin"></i> Pokea Malipo ya Deni
        </a>
    </div>
</div>

<!-- Filters Bar -->
<div class="card border-0 shadow-sm rounded-3 mb-4">
    <div class="card-body p-3">
        <form method="GET" action="/index.php" class="row g-2 align-items-end">
            <input type="hidden" name="route" value="payments">

            <!-- Search -->
            <div class="col-md-3">
                <label class="form-label small fw-semibold text-muted mb-1">Tafuta Malipo</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" name="search" class="form-control border-start-0" 
                           placeholder="Risiti #, Ankara #, Mteja..." value="<?= htmlspecialchars($filters['search']) ?>">
                </div>
            </div>

            <!-- Date From -->
            <div class="col-md-2">
                <label class="form-label small fw-semibold text-muted mb-1">Kuanzia Tarehe</label>
                <input type="date" name="date_from" class="form-control form-control-sm" value="<?= htmlspecialchars($filters['date_from']) ?>">
            </div>

            <!-- Date To -->
            <div class="col-md-2">
                <label class="form-label small fw-semibold text-muted mb-1">Hadi Tarehe</label>
                <input type="date" name="date_to" class="form-control form-control-sm" value="<?= htmlspecialchars($filters['date_to']) ?>">
            </div>

            <!-- Payment Method -->
            <div class="col-md-2">
                <label class="form-label small fw-semibold text-muted mb-1">Njia ya Malipo</label>
                <select name="payment_method" class="form-select form-select-sm">
                    <option value="">-- Zote --</option>
                    <?php foreach ($paymentMethods as $m): ?>
                        <option value="<?= trim($m) ?>" <?= ($filters['payment_method'] === trim($m)) ? 'selected' : '' ?>>
                            <?= htmlspecialchars(trim($m)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Status -->
            <div class="col-md-1">
                <label class="form-label small fw-semibold text-muted mb-1">Hadhi</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">-- Zote --</option>
                    <option value="completed" <?= ($filters['status'] === 'completed') ? 'selected' : '' ?>>Halali</option>
                    <option value="reversed" <?= ($filters['status'] === 'reversed') ? 'selected' : '' ?>>Zilizoghairiwa</option>
                </select>
            </div>

            <!-- Buttons -->
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary-custom btn-sm flex-fill">
                    <i class="bi bi-filter"></i> Chuja
                </button>
                <a href="/index.php?route=payments" class="btn btn-outline-secondary btn-sm" title="Futa Vichujio">
                    <i class="bi bi-arrow-counterclockwise"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Payments Table Card -->
<div class="card border-0 shadow-sm rounded-3">
    <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
        <h6 class="fw-bold mb-0 text-dark">
            <i class="bi bi-list-check text-primary me-2"></i>Miamala ya Malipo
        </h6>
        <span class="badge bg-light text-dark border font-monospace">Jumla: <?= number_format($totalPayments) ?></span>
    </div>
    <div class="card-body p-0">
        <?php if (empty($payments)): ?>
            <div class="text-center py-5">
                <i class="bi bi-wallet text-muted display-4"></i>
                <p class="text-muted mt-2 mb-0">Hakuna kumbukumbu za malipo zilizopatikana.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light small text-muted">
                        <tr>
                            <th class="ps-3">Namba ya Risiti</th>
                            <th>Tarehe & Saa</th>
                            <th>Ankara #</th>
                            <th>Mteja</th>
                            <th>Njia ya Malipo</th>
                            <th class="text-end">Kiasi (TSh)</th>
                            <th class="text-center">Hadhi</th>
                            <th>Mpokeaji</th>
                            <th class="text-end pe-3">Vitendo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payments as $p): ?>
                            <?php $isReversed = ($p['status'] === 'reversed'); ?>
                            <tr class="<?= $isReversed ? 'table-danger bg-opacity-25 opacity-75' : '' ?>">
                                <td class="ps-3 font-monospace fw-bold">
                                    <a href="/index.php?route=payment_show&id=<?= $p['id'] ?>" class="text-decoration-none">
                                        <?= htmlspecialchars($p['payment_no']) ?>
                                    </a>
                                </td>
                                <td class="small text-muted font-monospace">
                                    <?= date('d/m/Y H:i', strtotime($p['payment_date'])) ?>
                                </td>
                                <td class="font-monospace">
                                    <a href="/index.php?route=sale_show&id=<?= $p['sale_id'] ?>" class="text-decoration-none small text-dark fw-semibold">
                                        <?= htmlspecialchars($p['invoice_no']) ?>
                                    </a>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark"><?= htmlspecialchars($p['customer_name']) ?></div>
                                    <?php if (!empty($p['customer_phone'])): ?>
                                        <small class="text-muted font-monospace"><?= htmlspecialchars($p['customer_phone']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">
                                        <i class="bi bi-credit-card-2-front text-primary me-1"></i>
                                        <?= htmlspecialchars($p['payment_method']) ?>
                                        <?= !empty($p['provider']) ? '(' . htmlspecialchars($p['provider']) . ')' : '' ?>
                                    </span>
                                    <?php if (!empty($p['reference_no'])): ?>
                                        <div class="tiny text-muted font-monospace">Ref: <?= htmlspecialchars($p['reference_no']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end font-monospace fw-bold <?= $isReversed ? 'text-decoration-line-through text-muted' : 'text-success' ?>">
                                    <?= Helper::formatCurrency((float)$p['amount']) ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($isReversed): ?>
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle">
                                            Imeghairiwa
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle">
                                            Imekamilika
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="small text-muted">
                                    <?= htmlspecialchars($p['received_by_name'] ?? 'Staff') ?>
                                </td>
                                <td class="text-end pe-3">
                                    <div class="btn-group btn-group-sm">
                                        <a href="/index.php?route=payment_receipt&id=<?= $p['id'] ?>" target="_blank" class="btn btn-outline-primary" title="Chapisha Risiti">
                                            <i class="bi bi-printer"></i>
                                        </a>
                                        <a href="/index.php?route=payment_show&id=<?= $p['id'] ?>" class="btn btn-outline-secondary" title="Maelezo Zaidi">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <?php if ($canReverse && !$isReversed): ?>
                                            <button type="button" class="btn btn-outline-danger btn-reverse-payment" 
                                                    data-bs-toggle="modal" data-bs-target="#reversePaymentModal"
                                                    data-id="<?= $p['id'] ?>"
                                                    data-payment-no="<?= htmlspecialchars($p['payment_no']) ?>"
                                                    data-amount="<?= Helper::formatCurrency((float)$p['amount']) ?>"
                                                    title="Ghairi Malipo Haya">
                                                <i class="bi bi-arrow-counterclockwise"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="card-footer bg-white border-top py-3">
                    <nav aria-label="Page navigation">
                        <ul class="pagination pagination-sm justify-content-center mb-0">
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?= ($currentPage == $i) ? 'active' : '' ?>">
                                    <a class="page-link" href="/index.php?route=payments&page=<?= $i ?>&search=<?= urlencode($filters['search']) ?>&date_from=<?= urlencode($filters['date_from']) ?>&date_to=<?= urlencode($filters['date_to']) ?>&payment_method=<?= urlencode($filters['payment_method']) ?>&status=<?= urlencode($filters['status']) ?>">
                                        <?= $i ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Modal: Controlled Payment Reversal -->
<?php if ($canReverse): ?>
<div class="modal fade" id="reversePaymentModal" tabindex="-1" aria-labelledby="reversePaymentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-danger text-white">
                <h6 class="modal-title fw-bold" id="reversePaymentModalLabel">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>Kughairi Malipo (Payment Reversal)
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="/index.php?route=payment_reverse" method="POST">
                <input type="hidden" name="csrf_token" value="<?= Helper::generateCsrf() ?>">
                <input type="hidden" name="payment_id" id="modalReversePaymentId" value="">

                <div class="modal-body p-4">
                    <div class="alert alert-warning small mb-3">
                        <i class="bi bi-info-circle me-1"></i>
                        <strong>Tahadhari:</strong> Kughairi malipo kutarejesha deni kwenye ankara ya mauzo na kwenye akaunti ya mteja. Kumbukumbu zote zitahifadhiwa kwenye ukaguzi wa mfumo (Audit Trail).
                    </div>

                    <div class="mb-3">
                        <label class="form-label small text-muted mb-0">Risiti ya Malipo:</label>
                        <h5 class="fw-bold font-monospace text-dark" id="modalReversePaymentNo">-</h5>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small text-muted mb-0">Kiasi Kinachoghairiwa:</label>
                        <h4 class="fw-bold font-monospace text-danger" id="modalReverseAmount">-</h4>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-dark">Sababu ya Kughairi (Inahitajika) <span class="text-danger">*</span></label>
                        <textarea name="reversal_reason" class="form-control" rows="3" 
                                  placeholder="Eleza kwa undani sababu ya kughairi muamala huu..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Sitisha</button>
                    <button type="submit" class="btn btn-danger btn-sm">
                        <i class="bi bi-check2-circle me-1"></i> Thibitisha Kughairi Malipo
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const reverseModal = document.getElementById('reversePaymentModal');
    if (reverseModal) {
        reverseModal.addEventListener('show.bs.modal', (event) => {
            const button = event.relatedTarget;
            document.getElementById('modalReversePaymentId').value = button.getAttribute('data-id');
            document.getElementById('modalReversePaymentNo').textContent = button.getAttribute('data-payment-no');
            document.getElementById('modalReverseAmount').textContent = button.getAttribute('data-amount');
        });
    }
});
</script>
<?php endif; ?>
