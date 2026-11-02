<?php
use App\Core\Helper;
use App\Core\Auth;
?>

<!-- Header -->
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <h4 class="fw-bold mb-0">
                <i class="bi bi-receipt text-primary me-2"></i>Ankara #<?= htmlspecialchars($sale['invoice_no']) ?>
            </h4>

            <?php if ($sale['sale_status'] === 'cancelled'): ?>
                <span class="badge bg-danger-subtle text-danger border border-danger-subtle fs-6"><i class="bi bi-x-circle me-1"></i>Imeghairiwa (Cancelled)</span>
            <?php elseif ($sale['payment_status'] === 'paid'): ?>
                <span class="badge bg-success-subtle text-success border border-success-subtle fs-6"><i class="bi bi-check-circle me-1"></i>Imelipwa Kamili (Paid)</span>
            <?php elseif ($sale['payment_status'] === 'partially_paid'): ?>
                <span class="badge bg-warning-subtle text-dark border border-warning-subtle fs-6"><i class="bi bi-pie-chart me-1"></i>Nusu Malipo (Partial)</span>
            <?php else: ?>
                <span class="badge bg-danger-subtle text-danger border border-danger-subtle fs-6"><i class="bi bi-wallet2 me-1"></i>Mkopo (Credit / Unpaid)</span>
            <?php endif; ?>
        </div>
        <p class="text-muted small mb-0">
            Tarehe ya Mauzo: <strong><?= date('d/m/Y H:i:s', strtotime($sale['sale_date'])) ?></strong> &bull;
            Cashier: <strong><?= htmlspecialchars($sale['cashier_name'] ?? 'System') ?></strong>
        </p>
    </div>

    <div class="d-flex flex-wrap gap-2">
        <a href="/index.php?route=sales" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Orodha ya Mauzo
        </a>
        <a href="/index.php?route=sale_receipt&id=<?= $sale['id'] ?>" target="_blank" class="btn btn-primary-custom btn-sm">
            <i class="bi bi-printer me-1"></i> Risiti (Thermal)
        </a>
        <a href="/index.php?route=sale_invoice&id=<?= $sale['id'] ?>" target="_blank" class="btn btn-outline-danger btn-sm">
            <i class="bi bi-file-earmark-pdf me-1"></i> Ankara Rasmi (A4)
        </a>

        <?php if ($sale['sale_status'] !== 'cancelled' && (Auth::hasRole(['super_admin', 'manager', 'cashier']) || Auth::hasPermission('returns.create'))): ?>
            <a href="/index.php?route=return_create&sale_id=<?= $sale['id'] ?>" class="btn btn-warning btn-sm text-dark fw-bold">
                <i class="bi bi-arrow-return-left me-1"></i> Rejesha Bidhaa (Return)
            </a>
        <?php endif; ?>

        <?php if ($canCancel && $sale['sale_status'] !== 'cancelled'): ?>
            <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#cancelSaleModal">
                <i class="bi bi-x-octagon me-1"></i> Ghairi Mauzo (Cancel)
            </button>
        <?php endif; ?>
    </div>
</div>

<!-- Financial KPI Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-3 col-sm-6">
        <div class="card border-0 shadow-sm rounded-3 p-3">
            <span class="text-muted small text-uppercase fw-semibold">Jumla Kuu ya Mauzo</span>
            <h3 class="fw-bold mb-0 text-primary font-monospace mt-1">
                <?= Helper::formatCurrency((float)$sale['total_amount']) ?>
            </h3>
            <small class="text-muted"><?= count($items) ?> bidhaa zilizonunuliwa</small>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card border-0 shadow-sm rounded-3 p-3">
            <span class="text-muted small text-uppercase fw-semibold">Kiasi Kilicholipwa</span>
            <h3 class="fw-bold mb-0 text-success font-monospace mt-1">
                <?= Helper::formatCurrency((float)$sale['paid_amount']) ?>
            </h3>
            <small class="text-muted">Njia: <?= htmlspecialchars($sale['payment_method']) ?></small>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card border-0 shadow-sm rounded-3 p-3">
            <span class="text-muted small text-uppercase fw-semibold">Salio Linalodaiwa (Balance)</span>
            <h3 class="fw-bold mb-0 <?= (float)$sale['balance_due'] > 0 ? 'text-danger' : 'text-muted' ?> font-monospace mt-1">
                <?= Helper::formatCurrency((float)$sale['balance_due']) ?>
            </h3>
            <small class="text-muted"><?= (float)$sale['balance_due'] > 0 ? 'Deni ambalo halijalipwa' : 'Hakuna Deni' ?></small>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card border-0 shadow-sm rounded-3 p-3">
            <span class="text-muted small text-uppercase fw-semibold">Faida Halisi (Gross Profit)</span>
            <h3 class="fw-bold mb-0 <?= $canViewProfit ? 'text-info' : 'text-muted' ?> font-monospace mt-1">
                <?= $canViewProfit ? Helper::formatCurrency((float)$sale['gross_profit']) : '***' ?>
            </h3>
            <small class="text-muted"><?= $canViewProfit ? "Margin: {$sale['gross_margin_percent']}%" : 'Imezuiwa kwa Cashier' ?></small>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Left Column: Customer & Payment Details -->
    <div class="col-lg-4">
        <!-- Customer Info Card -->
        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="fw-bold mb-0 text-dark">
                    <i class="bi bi-person text-primary me-2"></i>Taarifa za Mteja
                </h6>
            </div>
            <div class="card-body p-3">
                <table class="table table-sm table-borderless mb-0">
                    <tbody>
                        <tr>
                            <td class="text-muted small py-2" style="width: 40%;">Jina la Mteja:</td>
                            <td class="fw-semibold py-2">
                                <?php if (!empty($sale['customer_id'])): ?>
                                    <a href="/index.php?route=customer_show&id=<?= $sale['customer_id'] ?>" class="text-decoration-none">
                                        <?= htmlspecialchars($sale['customer_name'] ?? 'Mteja') ?>
                                    </a>
                                <?php else: ?>
                                    <?= htmlspecialchars($sale['customer_name'] ?? 'Walk-in Customer') ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php if (!empty($sale['customer_code'])): ?>
                            <tr>
                                <td class="text-muted small py-2">Kodi ya Mteja:</td>
                                <td class="font-monospace small py-2"><?= htmlspecialchars($sale['customer_code']) ?></td>
                            </tr>
                        <?php endif; ?>
                        <?php if (!empty($sale['customer_phone'])): ?>
                            <tr>
                                <td class="text-muted small py-2">Simu:</td>
                                <td class="py-2"><?= htmlspecialchars($sale['customer_phone']) ?></td>
                            </tr>
                        <?php endif; ?>
                        <?php if (!empty($sale['mechanic_name'])): ?>
                            <tr>
                                <td class="text-muted small py-2">Fundi Aliyerejea:</td>
                                <td class="py-2 fw-semibold text-primary"><?= htmlspecialchars($sale['mechanic_name']) ?></td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Sale Metadata & Discounts -->
        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="fw-bold mb-0 text-dark">
                    <i class="bi bi-info-circle text-primary me-2"></i>Maelezo ya Ankara
                </h6>
            </div>
            <div class="card-body p-3">
                <table class="table table-sm table-borderless mb-0">
                    <tbody>
                        <tr>
                            <td class="text-muted small py-2" style="width: 45%;">Jumla ya Bei (Subtotal):</td>
                            <td class="font-monospace fw-semibold py-2 text-end"><?= Helper::formatCurrency((float)$sale['subtotal']) ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted small py-2">Punguzo (Discount):</td>
                            <td class="font-monospace text-danger py-2 text-end">
                                <?= (float)$sale['discount_amount'] > 0 ? "- " . Helper::formatCurrency((float)$sale['discount_amount']) : '0.00' ?>
                            </td>
                        </tr>
                        <?php if (!empty($sale['discount_reason'])): ?>
                            <tr>
                                <td class="text-muted small py-1" colspan="2">
                                    <small class="text-muted fst-italic">Sababu ya Punguzo: <?= htmlspecialchars($sale['discount_reason']) ?></small>
                                </td>
                            </tr>
                        <?php endif; ?>
                        <tr class="border-top">
                            <td class="fw-bold text-dark py-2">JUMLA KUU:</td>
                            <td class="font-monospace fw-bold text-primary py-2 text-end fs-6">
                                <?= Helper::formatCurrency((float)$sale['total_amount']) ?>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <?php if (!empty($sale['notes'])): ?>
                    <hr class="my-2">
                    <div class="p-2 bg-light rounded small">
                        <span class="fw-semibold text-muted d-block mb-1">Maelezo (Notes):</span>
                        <?= nl2br(htmlspecialchars($sale['notes'])) ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Right Column: Sale Items & Payments -->
    <div class="col-lg-8">
        <!-- Sale Items Table -->
        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="fw-bold mb-0 text-dark">
                    <i class="bi bi-box-seam text-primary me-2"></i>Vipuri Vilivyouzwa (<?= count($items) ?>)
                </h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light small text-muted">
                            <tr>
                                <th class="ps-3">Kipuri</th>
                                <th class="text-center">Idadi</th>
                                <th class="text-end">Bei ya Kuuza</th>
                                <th class="text-end">Punguzo</th>
                                <th class="text-end">Jumla Ndogo</th>
                                <?php if ($canViewProfit): ?>
                                    <th class="text-end pe-3">Faida (COGS)</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $it): ?>
                                <tr>
                                    <td class="ps-3">
                                        <div class="fw-bold text-dark" style="font-size: 13px;">
                                            <?= htmlspecialchars($it['product_name'] ?? $it['live_product_name'] ?? 'Kipuri') ?>
                                        </div>
                                        <div class="small text-muted font-monospace" style="font-size: 11px;">
                                            <?= htmlspecialchars($it['product_sku'] ?? $it['live_product_sku'] ?? '') ?>
                                            <?php if (!empty($it['brand_name'])): ?> &bull; <?= htmlspecialchars($it['brand_name']) ?><?php endif; ?>
                                            <?php if (!empty($it['shelf_location'])): ?> &bull; Rafu: <?= htmlspecialchars($it['shelf_location']) ?><?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="text-center font-monospace fw-bold">
                                        <?= $it['quantity'] ?> <?= htmlspecialchars($it['unit_name'] ?? 'Pcs') ?>
                                    </td>
                                    <td class="text-end font-monospace small">
                                        <?= Helper::formatCurrency((float)$it['unit_price']) ?>
                                    </td>
                                    <td class="text-end font-monospace text-danger small">
                                        <?= (float)$it['discount_per_unit'] > 0 ? Helper::formatCurrency((float)$it['discount_per_unit']) : '—' ?>
                                    </td>
                                    <td class="text-end font-monospace fw-bold text-dark">
                                        <?= Helper::formatCurrency((float)$it['subtotal']) ?>
                                    </td>
                                    <?php if ($canViewProfit): ?>
                                        <td class="text-end pe-3 font-monospace small text-info">
                                            +<?= Helper::formatCurrency((float)$it['gross_profit']) ?>
                                            <div class="text-muted" style="font-size: 10px;">Cost: <?= Helper::formatCurrency((float)$it['unit_cost']) ?></div>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Payments Received Table -->
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="fw-bold mb-0 text-dark">
                    <i class="bi bi-cash-stack text-primary me-2"></i>Malipo Yaliyopokelewa
                </h6>
            </div>
            <div class="card-body p-0">
                <?php if (empty($payments)): ?>
                    <div class="text-center py-4 text-muted small">
                        Hakuna rekodi ya malipo taslimu iliyorekodiwa (Mauzo ya mkopo pekee).
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0">
                            <thead class="table-light small text-muted">
                                <tr>
                                    <th class="ps-3">Tarehe & Saa</th>
                                    <th>Njia ya Malipo</th>
                                    <th>Kumbukumbu (Ref)</th>
                                    <th>Mpokeaji</th>
                                    <th class="text-end pe-3">Kiasi Kilicholipwa</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($payments as $pay): ?>
                                    <tr>
                                        <td class="ps-3 small text-muted">
                                            <?= date('d/m/Y H:i', strtotime($pay['payment_date'])) ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark border">
                                                <?= htmlspecialchars($pay['payment_method']) ?>
                                            </span>
                                        </td>
                                        <td class="small font-monospace">
                                            <?= htmlspecialchars($pay['reference_no'] ?: '—') ?>
                                        </td>
                                        <td class="small text-secondary">
                                            <?= htmlspecialchars($pay['received_by_name'] ?? 'Cashier') ?>
                                        </td>
                                        <td class="text-end pe-3 font-monospace fw-bold text-success">
                                            <?= Helper::formatCurrency((float)$pay['amount']) ?>
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

<!-- Modal: Cancel Sale -->
<?php if ($canCancel && $sale['sale_status'] !== 'cancelled'): ?>
    <div class="modal fade" id="cancelSaleModal" tabindex="-1" aria-labelledby="cancelSaleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white py-2">
                    <h5 class="modal-title fw-bold fs-6" id="cancelSaleModalLabel">
                        <i class="bi bi-exclamation-triangle me-1"></i> Thibitisha Kughairi Mauzo
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="/index.php?route=sale_cancel">
                    <input type="hidden" name="csrf_token" value="<?= Helper::generateCsrf() ?>">
                    <input type="hidden" name="sale_id" value="<?= $sale['id'] ?>">

                    <div class="modal-body">
                        <p class="text-danger fw-semibold small mb-2">
                            Onyo: Kughairi mauzo kutaweka ankara hii katika hali ya CANCELLED na kurejesha kiotomatiki vipuri vyote stoo kupitia muamala wa marejesho!
                        </p>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-muted">Sababu ya Kughairi Mauzo <span class="text-danger">*</span></label>
                            <textarea name="cancel_reason" class="form-control" rows="3" required 
                                      placeholder="Mf. Mteja alighairi manunuzi, hitilafu ya nambari ya simu, vipuri vilivyochaguliwa si sahihi..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light border btn-sm" data-bs-dismiss="modal">Ghairi</button>
                        <button type="submit" class="btn btn-danger btn-sm">
                            <i class="bi bi-x-circle me-1"></i> Ghairi Mauzo na Rudisha Stoo
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endif; ?>
