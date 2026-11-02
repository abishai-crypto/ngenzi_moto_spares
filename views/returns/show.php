<?php
use App\Core\Helper;
use App\Core\Auth;
?>

<!-- Header -->
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <h4 class="fw-bold mb-0">
                <i class="bi bi-arrow-return-left text-primary me-2"></i>Rejesho #<?= htmlspecialchars($return['return_number']) ?>
            </h4>

            <?php
            $badges = [
                'draft'     => 'bg-secondary-subtle text-secondary border',
                'requested' => 'bg-warning-subtle text-dark border border-warning',
                'approved'  => 'bg-info-subtle text-info border border-info',
                'received'  => 'bg-primary-subtle text-primary border border-primary',
                'refunded'  => 'bg-success-subtle text-success border border-success',
                'completed' => 'bg-success text-white',
                'rejected'  => 'bg-danger-subtle text-danger border border-danger',
                'cancelled' => 'bg-danger text-white'
            ];
            $badgeClass = $badges[$return['status']] ?? 'bg-light text-dark';
            ?>
            <span class="badge <?= $badgeClass ?> text-uppercase fs-6 px-3 py-1">
                <?= $return['status'] ?>
            </span>
        </div>
        <p class="text-muted small mb-0">
            Tarehe ya Rejesho: <strong><?= date('d/m/Y', strtotime($return['return_date'])) ?></strong> &bull;
            Ankara ya Mauzo: <a href="/index.php?route=sale_show&id=<?= $return['sale_id'] ?>" class="text-decoration-none fw-bold font-monospace">#<?= htmlspecialchars($return['invoice_no']) ?></a> &bull;
            Iliundwa na: <strong><?= htmlspecialchars($return['created_by_name'] ?? 'System') ?></strong>
        </p>
    </div>

    <div class="d-flex flex-wrap gap-2">
        <a href="/index.php?route=returns" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Orodha
        </a>
        <a href="/index.php?route=return_receipt&id=<?= $return['id'] ?>" target="_blank" class="btn btn-primary-custom btn-sm">
            <i class="bi bi-printer me-1"></i> Risiti (Thermal)
        </a>
        <a href="/index.php?route=return_credit_note&id=<?= $return['id'] ?>" target="_blank" class="btn btn-outline-danger btn-sm">
            <i class="bi bi-file-earmark-pdf me-1"></i> Credit Note (A4)
        </a>

        <!-- Workflow Action Buttons based on Status & RBAC -->
        <?php if ($canApprove && in_array($return['status'], ['draft', 'requested'])): ?>
            <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#approveModal">
                <i class="bi bi-check-circle me-1"></i> Idhinisha (Approve)
            </button>
            <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#rejectModal">
                <i class="bi bi-x-circle me-1"></i> Kataa (Reject)
            </button>
        <?php endif; ?>

        <?php if ($canReceive && (int)$return['is_restocked'] === 0 && in_array($return['status'], ['approved', 'requested', 'refunded'])): ?>
            <button type="button" class="btn btn-info text-white btn-sm" data-bs-toggle="modal" data-bs-target="#receiveModal">
                <i class="bi bi-box-arrow-in-down me-1"></i> Pokea & Ingiza Stoo
            </button>
        <?php endif; ?>

        <?php if ($canRefund && $return['refund_status'] === 'unrefunded' && in_array($return['status'], ['approved', 'received'])): ?>
            <button type="button" class="btn btn-warning text-dark fw-bold btn-sm" data-bs-toggle="modal" data-bs-target="#refundModal">
                <i class="bi bi-cash-coin me-1"></i> Fanya Marejesho ya Pesa
            </button>
        <?php endif; ?>

        <?php if ($canCancel && !in_array($return['status'], ['cancelled', 'rejected'])): ?>
            <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#cancelModal">
                <i class="bi bi-x-octagon me-1"></i> Ghairi Rejesho
            </button>
        <?php endif; ?>
    </div>
</div>

<!-- KPI Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-3 col-sm-6">
        <div class="card border-0 shadow-sm rounded-3 p-3">
            <span class="text-muted small text-uppercase fw-semibold">Jumla ya Thamani ya Vipuri</span>
            <h3 class="fw-bold mb-0 text-dark font-monospace mt-1">
                <?= Helper::formatCurrency((float)$return['subtotal']) ?>
            </h3>
            <small class="text-muted"><?= count($items) ?> aina ya vipuri</small>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card border-0 shadow-sm rounded-3 p-3">
            <span class="text-muted small text-uppercase fw-semibold">Tozo ya Rejesho (Fee)</span>
            <h3 class="fw-bold mb-0 text-danger font-monospace mt-1">
                <?= (float)$return['return_fee'] > 0 ? "- " . Helper::formatCurrency((float)$return['return_fee']) : 'TSh 0.00' ?>
            </h3>
            <small class="text-muted"><?= (float)$return['return_fee'] > 0 ? 'Tozo iliyokatwa' : 'Hakuna Tozo' ?></small>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card border-0 shadow-sm rounded-3 p-3">
            <span class="text-muted small text-uppercase fw-semibold">Kiasi cha Kurudisha (Net Refund)</span>
            <h3 class="fw-bold mb-0 text-primary font-monospace mt-1">
                <?= Helper::formatCurrency((float)$return['refund_amount']) ?>
            </h3>
            <small class="text-muted">
                Hadhi: <strong><?= htmlspecialchars($return['refund_status']) ?></strong>
            </small>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card border-0 shadow-sm rounded-3 p-3">
            <span class="text-muted small text-uppercase fw-semibold">Hali ya Stoo (Restock)</span>
            <h3 class="fw-bold mb-0 <?= (int)$return['is_restocked'] === 1 ? 'text-success' : 'text-warning' ?> font-monospace mt-1">
                <?= (int)$return['is_restocked'] === 1 ? 'Imepokelewa' : 'Haijapokelewa' ?>
            </h3>
            <small class="text-muted">
                <?= (int)$return['is_restocked'] === 1 ? 'Vipuri vimeingizwa stoo' : 'Inasubiri Storekeeper' ?>
            </small>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Left Column: Return Metadata & Timeline -->
    <div class="col-lg-4">
        <!-- Customer & Sale Overview Card -->
        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="fw-bold mb-0 text-dark">
                    <i class="bi bi-person text-primary me-2"></i>Taarifa za Mteja & Mauzo
                </h6>
            </div>
            <div class="card-body p-3">
                <table class="table table-sm table-borderless mb-0">
                    <tbody>
                        <tr>
                            <td class="text-muted small py-2" style="width: 40%;">Mteja:</td>
                            <td class="fw-semibold py-2">
                                <?php if (!empty($return['customer_id'])): ?>
                                    <a href="/index.php?route=customer_show&id=<?= $return['customer_id'] ?>" class="text-decoration-none">
                                        <?= htmlspecialchars($return['customer_name'] ?? 'Mteja') ?>
                                    </a>
                                <?php else: ?>
                                    <?= htmlspecialchars($return['customer_name'] ?? 'Walk-in Customer') ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php if (!empty($return['customer_code'])): ?>
                            <tr>
                                <td class="text-muted small py-2">Kodi ya Mteja:</td>
                                <td class="font-monospace small py-2"><?= htmlspecialchars($return['customer_code']) ?></td>
                            </tr>
                        <?php endif; ?>
                        <?php if (!empty($return['customer_phone'])): ?>
                            <tr>
                                <td class="text-muted small py-2">Simu:</td>
                                <td class="py-2"><?= htmlspecialchars($return['customer_phone']) ?></td>
                            </tr>
                        <?php endif; ?>
                        <tr class="border-top">
                            <td class="text-muted small py-2">Ankara ya Mauzo:</td>
                            <td class="font-monospace fw-semibold py-2">
                                <a href="/index.php?route=sale_show&id=<?= $return['sale_id'] ?>" class="text-decoration-none">
                                    #<?= htmlspecialchars($return['invoice_no']) ?>
                                </a>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted small py-2">Thamani ya Ankara:</td>
                            <td class="font-monospace small py-2"><?= Helper::formatCurrency((float)$return['sale_total_amount']) ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted small py-2">Salio Linalodaiwa:</td>
                            <td class="font-monospace small py-2 text-danger fw-bold"><?= Helper::formatCurrency((float)$return['sale_balance_due']) ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Return Details & Settlement Info Card -->
        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="fw-bold mb-0 text-dark">
                    <i class="bi bi-info-circle text-primary me-2"></i>Maelezo ya Rejesho
                </h6>
            </div>
            <div class="card-body p-3">
                <table class="table table-sm table-borderless mb-0">
                    <tbody>
                        <tr>
                            <td class="text-muted small py-2" style="width: 45%;">Sababu Kuu:</td>
                            <td class="py-2">
                                <span class="badge bg-light text-dark border">
                                    <?= htmlspecialchars(str_replace('_', ' ', $return['return_type'])) ?>
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted small py-2">Njia ya Marejesho:</td>
                            <td class="py-2 fw-semibold">
                                <?= htmlspecialchars($return['refund_method'] ?: 'Haijafanyika bado') ?>
                                <?php if (!empty($return['refund_provider'])): ?>
                                    <small class="text-muted">(<?= htmlspecialchars($return['refund_provider']) ?>)</small>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php if (!empty($return['refund_reference_no'])): ?>
                            <tr>
                                <td class="text-muted small py-2">Kumbukumbu ya Malipo:</td>
                                <td class="font-monospace py-2 fw-bold text-success"><?= htmlspecialchars($return['refund_reference_no']) ?></td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>

                <?php if (!empty($return['notes'])): ?>
                    <hr class="my-2">
                    <div class="p-2 bg-light rounded small">
                        <span class="fw-semibold text-muted d-block mb-1">Maelezo (Notes):</span>
                        <?= nl2br(htmlspecialchars($return['notes'])) ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($return['cancel_reason'])): ?>
                    <hr class="my-2">
                    <div class="p-2 bg-danger-subtle rounded small text-danger">
                        <span class="fw-semibold d-block mb-1">Sababu ya Kughairi/Kukataa:</span>
                        <?= nl2br(htmlspecialchars($return['cancel_reason'])) ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Audit & Workflow Log -->
        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="fw-bold mb-0 text-dark">
                    <i class="bi bi-clock-history text-primary me-2"></i>Kumbukumbu ya Hatua (Audit Trail)
                </h6>
            </div>
            <div class="card-body p-3">
                <ul class="list-unstyled small mb-0">
                    <li class="mb-2">
                        <i class="bi bi-plus-circle text-primary me-2"></i>
                        <strong>Iliundwa:</strong> <?= date('d/m/Y H:i', strtotime($return['created_at'])) ?>
                        <span class="text-muted d-block ps-4">Na: <?= htmlspecialchars($return['created_by_name'] ?? 'Mtumiaji') ?></span>
                    </li>
                    <?php if (!empty($return['approved_at'])): ?>
                        <li class="mb-2">
                            <i class="bi bi-check-circle text-success me-2"></i>
                            <strong>Ileidhinishwa:</strong> <?= date('d/m/Y H:i', strtotime($return['approved_at'])) ?>
                            <span class="text-muted d-block ps-4">Na: <?= htmlspecialchars($return['approved_by_name'] ?? 'Meneja') ?></span>
                        </li>
                    <?php endif; ?>
                    <?php if (!empty($return['received_at'])): ?>
                        <li class="mb-2">
                            <i class="bi bi-box-arrow-in-down text-info me-2"></i>
                            <strong>Imepokelewa Stoo:</strong> <?= date('d/m/Y H:i', strtotime($return['received_at'])) ?>
                            <span class="text-muted d-block ps-4">Na: <?= htmlspecialchars($return['received_by_name'] ?? 'Storekeeper') ?></span>
                        </li>
                    <?php endif; ?>
                    <?php if (!empty($return['refunded_at'])): ?>
                        <li class="mb-2">
                            <i class="bi bi-cash-stack text-warning me-2"></i>
                            <strong>Marejesho ya Fedha:</strong> <?= date('d/m/Y H:i', strtotime($return['refunded_at'])) ?>
                            <span class="text-muted d-block ps-4">Na: <?= htmlspecialchars($return['refunded_by_name'] ?? 'Cashier') ?></span>
                        </li>
                    <?php endif; ?>
                    <?php if (!empty($return['cancelled_at'])): ?>
                        <li class="mb-2 text-danger">
                            <i class="bi bi-x-circle text-danger me-2"></i>
                            <strong>Imeghairiwa:</strong> <?= date('d/m/Y H:i', strtotime($return['cancelled_at'])) ?>
                            <span class="d-block ps-4">Na: <?= htmlspecialchars($return['cancelled_by_name'] ?? 'Admin') ?></span>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>

    <!-- Right Column: Return Items Table -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
                <h6 class="fw-bold mb-0 text-dark">
                    <i class="bi bi-box-seam text-primary me-2"></i>Vipuri Vilivyorejeshwa (<?= count($items) ?>)
                </h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light small text-muted">
                            <tr>
                                <th class="ps-3">Kipuri</th>
                                <th class="text-center">Idadi ya Rejesho</th>
                                <th class="text-end">Bei ya Kurejesha</th>
                                <th class="text-center">Hali ya Bidhaa</th>
                                <th class="text-center">Hali ya Stoo</th>
                                <th class="text-end pe-3">Jumla Ndogo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $it): ?>
                                <tr>
                                    <td class="ps-3">
                                        <div class="fw-bold text-dark" style="font-size: 13px;">
                                            <?= htmlspecialchars($it['product_name'] ?? $it['live_product_name'] ?? 'Kipuri') ?>
                                        </div>
                                        <small class="text-muted font-monospace" style="font-size: 11px;">
                                            <?= htmlspecialchars($it['product_sku'] ?? $it['live_product_sku'] ?? '') ?>
                                            <?php if (!empty($it['brand_name'])): ?> &bull; <?= htmlspecialchars($it['brand_name']) ?><?php endif; ?>
                                        </small>
                                    </td>
                                    <td class="text-center font-monospace fw-bold fs-6">
                                        <?= $it['quantity'] ?> <?= htmlspecialchars($it['unit_name'] ?? 'Pcs') ?>
                                    </td>
                                    <td class="text-end font-monospace small">
                                        <?= Helper::formatCurrency((float)$it['unit_refund_price']) ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($it['condition_status'] === 'resellable'): ?>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle">
                                                <i class="bi bi-check-circle me-1"></i>Inayouzika (Resellable)
                                            </span>
                                        <?php elseif ($it['condition_status'] === 'damaged'): ?>
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle">
                                                <i class="bi bi-exclamation-triangle me-1"></i>Imeharibika (Damaged)
                                            </span>
                                        <?php elseif ($it['condition_status'] === 'defective'): ?>
                                            <span class="badge bg-warning-subtle text-dark border border-warning-subtle">
                                                <i class="bi bi-x-octagon me-1"></i>Hitilafu ya Kiwanda (Defective)
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Haifai Kuuzwa</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ((int)$it['is_restocked'] === 1): ?>
                                            <span class="badge bg-success text-white">
                                                <i class="bi bi-box-seam me-1"></i>Imeingizwa Stoo
                                            </span>
                                        <?php elseif ($it['condition_status'] !== 'resellable'): ?>
                                            <span class="badge bg-light text-muted border">
                                                Haikuingizwa (Vibovu)
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-warning-subtle text-dark border border-warning-subtle">
                                                Inasubiri Kupokelewa
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end pe-3 font-monospace fw-bold text-dark">
                                        <?= Helper::formatCurrency((float)$it['subtotal']) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Approve Return -->
<div class="modal fade" id="approveModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white py-2">
                <h5 class="modal-title fs-6 fw-bold"><i class="bi bi-check-circle me-1"></i> Thibitisha Kuidhinisha Rejesho</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="/index.php?route=return_approve">
                <input type="hidden" name="csrf_token" value="<?= Helper::generateCsrf() ?>">
                <input type="hidden" name="return_id" value="<?= $return['id'] ?>">
                <div class="modal-body">
                    <p class="mb-0">
                        Je, una uhakika unataka kuidhinisha Rejesho <strong>#<?= htmlspecialchars($return['return_number']) ?></strong> lenye thamani ya <strong><?= Helper::formatCurrency((float)$return['refund_amount']) ?></strong>?
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border btn-sm" data-bs-dismiss="modal">Ghairi</button>
                    <button type="submit" class="btn btn-success btn-sm"><i class="bi bi-check-circle me-1"></i> Idhinisha</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Receive & Restock -->
<div class="modal fade" id="receiveModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-info text-white py-2">
                <h5 class="modal-title fs-6 fw-bold"><i class="bi bi-box-arrow-in-down me-1"></i> Pokea Mzigo & Ingiza Stoo</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="/index.php?route=return_receive">
                <input type="hidden" name="csrf_token" value="<?= Helper::generateCsrf() ?>">
                <input type="hidden" name="return_id" value="<?= $return['id'] ?>">
                <div class="modal-body">
                    <p class="small text-muted mb-2">
                        Mfumo utaingiza kiotomatiki vipuri vyote vilivyowekwa <strong>Resellable</strong> kwenye stoo inayouzika, na kurekodi muamala wa stoo wa <em>sale_return</em>. Vipuri vilivyoharibika havitaongezwa stoo.
                    </p>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-muted">Maelezo ya Ukaguzi wa Stoo</label>
                        <textarea name="receive_notes" class="form-control" rows="2" placeholder="Mf. Vipuri vimekaguliwa viko katika hali nzuri..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border btn-sm" data-bs-dismiss="modal">Ghairi</button>
                    <button type="submit" class="btn btn-info text-white btn-sm"><i class="bi bi-box-arrow-in-down me-1"></i> Thibitisha Mapokezi</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Process Refund -->
<div class="modal fade" id="refundModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark py-2">
                <h5 class="modal-title fs-6 fw-bold"><i class="bi bi-cash-coin me-1"></i> Fanya Marejesho ya Fedha (Issue Refund)</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="/index.php?route=return_refund">
                <input type="hidden" name="csrf_token" value="<?= Helper::generateCsrf() ?>">
                <input type="hidden" name="return_id" value="<?= $return['id'] ?>">
                <div class="modal-body">
                    <div class="p-3 bg-light rounded mb-3 text-center">
                        <span class="text-muted small text-uppercase">Kiasi Kinachorejeshwa</span>
                        <h3 class="fw-bold text-primary font-monospace mb-0"><?= Helper::formatCurrency((float)$return['refund_amount']) ?></h3>
                    </div>

                    <?php if ((float)$return['sale_balance_due'] > 0): ?>
                        <div class="alert alert-warning py-2 px-3 small mb-3">
                            <i class="bi bi-exclamation-triangle me-1"></i>
                            Ankara hii inadaiwa TSh <?= number_format((float)$return['sale_balance_due'], 2) ?>. Kiasi hiki kitatumika kwanza kupunguza deni la ankara na salio la mteja!
                        </div>
                    <?php endif; ?>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-muted">Njia ya Malipo (Payment Method) <span class="text-danger">*</span></label>
                        <select name="refund_method" class="form-select" required>
                            <option value="Cash">Cash (Pesa Taslimu)</option>
                            <option value="Mobile Money">Mobile Money (M-Pesa, Tigo Pesa, n.k.)</option>
                            <option value="Bank">Benki (Bank Transfer)</option>
                            <option value="Customer Credit">Customer Credit / Account Balance</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-muted">Mtoa Huduma (Provider - Hiari)</label>
                        <select name="provider" class="form-select">
                            <option value="">-- Chagua Mtoa Huduma --</option>
                            <?php foreach ($providers as $prv): ?>
                                <option value="<?= htmlspecialchars(trim($prv)) ?>"><?= htmlspecialchars(trim($prv)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border btn-sm" data-bs-dismiss="modal">Ghairi</button>
                    <button type="submit" class="btn btn-warning text-dark fw-bold btn-sm"><i class="bi bi-cash-coin me-1"></i> Thibitisha Malipo</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Reject Return -->
<div class="modal fade" id="rejectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white py-2">
                <h5 class="modal-title fs-6 fw-bold"><i class="bi bi-x-circle me-1"></i> Kataa Rejesho (Reject)</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="/index.php?route=return_reject">
                <input type="hidden" name="csrf_token" value="<?= Helper::generateCsrf() ?>">
                <input type="hidden" name="return_id" value="<?= $return['id'] ?>">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-muted">Sababu ya Kukataa Rejesho <span class="text-danger">*</span></label>
                        <textarea name="reject_reason" class="form-control" rows="3" required placeholder="Weka sababu ya kukataa..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border btn-sm" data-bs-dismiss="modal">Ghairi</button>
                    <button type="submit" class="btn btn-danger btn-sm"><i class="bi bi-x-circle me-1"></i> Kataa Rejesho</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Cancel Return -->
<div class="modal fade" id="cancelModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white py-2">
                <h5 class="modal-title fs-6 fw-bold"><i class="bi bi-x-octagon me-1"></i> Ghairi Rejesho (Cancel)</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="/index.php?route=return_cancel">
                <input type="hidden" name="csrf_token" value="<?= Helper::generateCsrf() ?>">
                <input type="hidden" name="return_id" value="<?= $return['id'] ?>">
                <div class="modal-body">
                    <p class="text-danger small fw-semibold mb-2">
                        Onyo: Kughairi rejesho hili kutarudisha mabadiliko yoyote ya stoo na malipo ya fedha/deni yaliyokuwa yamefanyika!
                    </p>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-muted">Sababu ya Kughairi <span class="text-danger">*</span></label>
                        <textarea name="cancel_reason" class="form-control" rows="3" required placeholder="Weka sababu ya kughairi..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border btn-sm" data-bs-dismiss="modal">Ghairi</button>
                    <button type="submit" class="btn btn-danger btn-sm"><i class="bi bi-x-octagon me-1"></i> Thibitisha Kughairi</button>
                </div>
            </form>
        </div>
    </div>
</div>
