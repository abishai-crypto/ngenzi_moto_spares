<?php
use App\Core\Helper;
use App\Core\Auth;
?>

<!-- Header -->
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-1 text-dark">
            <i class="bi bi-arrow-return-left text-primary me-2"></i>Kuanzisha Rejesho Jipya (Create Return)
        </h4>
        <p class="text-muted small mb-0">
            Chagua ankara ya mauzo, weka idadi ya vipuri vinavyorudishwa, sababu ya marejesho na hali ya bidhaa.
        </p>
    </div>

    <div class="d-flex flex-wrap gap-2">
        <a href="/index.php?route=returns" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Orodha ya Marejesho
        </a>
    </div>
</div>

<!-- Step 1: Sale Lookup Card -->
<div class="card border-0 shadow-sm rounded-3 mb-4">
    <div class="card-header bg-white border-bottom py-3">
        <h6 class="fw-bold mb-0 text-dark">
            <i class="bi bi-search text-primary me-2"></i>Hatua ya 1: Tafuta Ankara ya Mauzo (Invoice Lookup)
        </h6>
    </div>
    <div class="card-body p-3">
        <form method="GET" action="/index.php" class="row g-2 align-items-center">
            <input type="hidden" name="route" value="return_create">
            <div class="col-md-5 col-sm-8">
                <label class="form-label small text-muted fw-semibold">Nambari ya Ankara (Mf. INV-000001 au namba kamili)</label>
                <div class="input-group">
                    <span class="input-group-text bg-light"><i class="bi bi-receipt"></i></span>
                    <input type="text" name="invoice_no" id="invoice_search_input" class="form-control" 
                           placeholder="Ingiza nambari ya ankara..." 
                           value="<?= htmlspecialchars($sale['invoice_no'] ?? ($invoiceQuery ?? '')) ?>" required>
                </div>
            </div>
            <div class="col-md-3 col-sm-4 pt-sm-4">
                <button type="submit" class="btn btn-primary-custom w-100">
                    <i class="bi bi-search me-1"></i> Pakia Ankara
                </button>
            </div>
        </form>
    </div>
</div>

<?php if ($sale): ?>
    <!-- Sale Overview Banner -->
    <div class="card border-0 shadow-sm rounded-3 mb-4 border-start border-4 border-primary">
        <div class="card-body p-3">
            <div class="row align-items-center">
                <div class="col-md-3">
                    <span class="text-muted small text-uppercase">Ankara</span>
                    <h5 class="fw-bold text-primary font-monospace mb-0">#<?= htmlspecialchars($sale['invoice_no']) ?></h5>
                    <small class="text-muted"><?= date('d/m/Y H:i', strtotime($sale['sale_date'])) ?></small>
                </div>
                <div class="col-md-3">
                    <span class="text-muted small text-uppercase">Mteja</span>
                    <h6 class="fw-bold text-dark mb-0"><?= htmlspecialchars($sale['customer_name'] ?? 'Mteja wa Kawaida') ?></h6>
                    <small class="text-muted"><?= htmlspecialchars($sale['customer_phone'] ?? 'Bila Namba') ?></small>
                </div>
                <div class="col-md-3">
                    <span class="text-muted small text-uppercase">Jumla ya Mauzo</span>
                    <h6 class="fw-bold text-success font-monospace mb-0"><?= Helper::formatCurrency((float)$sale['total_amount']) ?></h6>
                    <small class="text-muted">Paid: <?= Helper::formatCurrency((float)$sale['paid_amount']) ?> &bull; Due: <?= Helper::formatCurrency((float)$sale['balance_due']) ?></small>
                </div>
                <div class="col-md-3 text-md-end">
                    <span class="badge <?= $sale['sale_status'] === 'completed' ? 'bg-success' : 'bg-warning text-dark' ?> text-uppercase px-2 py-1">
                        <?= htmlspecialchars($sale['sale_status']) ?>
                    </span>
                    <div class="small text-muted mt-1">Cashier: <?= htmlspecialchars($sale['cashier_name'] ?? 'System') ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Step 2: Return Items & Form -->
    <form method="POST" action="/index.php?route=return_store" id="returnForm">
        <input type="hidden" name="csrf_token" value="<?= Helper::generateCsrf() ?>">
        <input type="hidden" name="sale_id" value="<?= $sale['id'] ?>">

        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
                <h6 class="fw-bold mb-0 text-dark">
                    <i class="bi bi-box-seam text-primary me-2"></i>Hatua ya 2: Chagua Vipuri vya Kurejesha
                </h6>
                <span class="badge bg-light text-dark border">Jumla ya Vipuri: <?= count($items) ?></span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="returnItemsTable">
                        <thead class="table-light small text-muted">
                            <tr>
                                <th class="ps-3" style="width: 30%;">Kipuri</th>
                                <th class="text-center" style="width: 10%;">Iliyouzwa</th>
                                <th class="text-center" style="width: 10%;">Iliyokwisharudishwa</th>
                                <th class="text-center" style="width: 10%;">Inayoruhusiwa</th>
                                <th class="text-end" style="width: 12%;">Bei ya Kurejesha (Net)</th>
                                <th class="text-center" style="width: 10%;">Idadi ya Kurejesha</th>
                                <th class="text-center" style="width: 18%;">Hali ya Kipuri (Condition)</th>
                                <th class="text-end pe-3" style="width: 15%;">Jumla Ndogo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $hasReturnable = false;
                            foreach ($items as $it): 
                                $rem = (int)$it['remaining_returnable'];
                                if ($rem > 0) $hasReturnable = true;
                            ?>
                                <tr class="<?= $rem <= 0 ? 'table-secondary opacity-75' : '' ?>">
                                    <td class="ps-3">
                                        <div class="fw-bold text-dark" style="font-size: 13px;">
                                            <?= htmlspecialchars($it['product_name'] ?? $it['live_product_name'] ?? 'Kipuri') ?>
                                        </div>
                                        <small class="text-muted font-monospace" style="font-size: 11px;">
                                            <?= htmlspecialchars($it['product_sku'] ?? $it['live_product_sku'] ?? '') ?>
                                            <?php if (!empty($it['brand_name'])): ?> &bull; <?= htmlspecialchars($it['brand_name']) ?><?php endif; ?>
                                        </small>
                                    </td>
                                    <td class="text-center font-monospace">
                                        <?= $it['original_quantity'] ?> <?= htmlspecialchars($it['unit_name'] ?? 'Pcs') ?>
                                    </td>
                                    <td class="text-center font-monospace text-muted">
                                        <?= $it['already_returned'] ?>
                                    </td>
                                    <td class="text-center font-monospace fw-bold <?= $rem > 0 ? 'text-primary' : 'text-danger' ?>">
                                        <?= $rem ?>
                                    </td>
                                    <td class="text-end font-monospace small">
                                        <?= Helper::formatCurrency((float)$it['net_unit_price']) ?>
                                        <input type="hidden" class="item-unit-price" value="<?= (float)$it['net_unit_price'] ?>">
                                    </td>
                                    <td class="text-center">
                                        <?php if ($rem > 0): ?>
                                            <input type="number" name="items[<?= $it['id'] ?>][quantity]" 
                                                   class="form-control form-control-sm text-center font-monospace fw-bold return-qty-input" 
                                                   min="0" max="<?= $rem ?>" value="0" style="max-width: 80px; margin: 0 auto;"
                                                   data-sale-item-id="<?= $it['id'] ?>"
                                                   data-unit-price="<?= (float)$it['net_unit_price'] ?>"
                                                   data-max="<?= $rem ?>">
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Imekwisha Rejeshwa Yote</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($rem > 0): ?>
                                            <select name="items[<?= $it['id'] ?>][condition_status]" class="form-select form-select-sm">
                                                <option value="resellable" selected>Inayouzika Tena (Resellable)</option>
                                                <option value="damaged">Imeharibika (Damaged)</option>
                                                <option value="defective">Mbovu / Kiwanda (Defective)</option>
                                                <option value="not_resellable">Haifai Kuuzwa (Not Resellable)</option>
                                            </select>
                                        <?php else: ?>
                                            <span class="text-muted small">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end pe-3 font-monospace fw-bold text-dark item-subtotal-display">
                                        TSh 0.00
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Step 3: Return Details & Settlement Summary -->
        <div class="row g-4 mb-4">
            <div class="col-lg-7">
                <div class="card border-0 shadow-sm rounded-3 h-100">
                    <div class="card-header bg-white border-bottom py-3">
                        <h6 class="fw-bold mb-0 text-dark">
                            <i class="bi bi-card-text text-primary me-2"></i>Maelezo ya Sababu ya Marejesho
                        </h6>
                    </div>
                    <div class="card-body p-3">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold text-muted">Aina / Sababu Kuu <span class="text-danger">*</span></label>
                                <select name="return_type" id="return_type_select" class="form-select" required>
                                    <?php foreach ($returnReasons as $rsn): ?>
                                        <option value="<?= htmlspecialchars(trim($rsn)) ?>">
                                            <?= htmlspecialchars(str_replace('_', ' ', trim($rsn))) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold text-muted">Tarehe ya Marejesho</label>
                                <input type="date" name="return_date" class="form-control" value="<?= date('Y-m-d') ?>">
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-semibold text-muted">Sababu Kamili / Maelezo (Notes)</label>
                                <textarea name="notes" class="form-control" rows="3" 
                                          placeholder="Mf. Mteja amenunua saizi isiyo sahihi ya Brake shoe, alileta ndani ya siku 2..."></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card border-0 shadow-sm rounded-3 h-100">
                    <div class="card-header bg-white border-bottom py-3">
                        <h6 class="fw-bold mb-0 text-dark">
                            <i class="bi bi-calculator text-primary me-2"></i>Muhtasari wa Thamani ya Rejesho
                        </h6>
                    </div>
                    <div class="card-body p-3">
                        <table class="table table-sm table-borderless mb-0">
                            <tbody>
                                <tr>
                                    <td class="text-muted small py-2">Jumla ya Thamani ya Vipuri:</td>
                                    <td class="font-monospace fw-semibold py-2 text-end" id="summaryGrossSubtotal">TSh 0.00</td>
                                </tr>
                                <tr>
                                    <td class="text-muted small py-2">
                                        Tozo ya Kurudisha (Restocking Fee):
                                    </td>
                                    <td class="py-2 text-end" style="max-width: 140px;">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text">TSh</span>
                                            <input type="number" name="return_fee" id="return_fee_input" 
                                                   class="form-control form-control-sm text-end font-monospace" 
                                                   min="0" step="100" value="0.00">
                                        </div>
                                    </td>
                                </tr>
                                <tr class="border-top">
                                    <td class="fw-bold text-dark py-2 fs-6">Thamani Halisi ya Kurejesha:</td>
                                    <td class="font-monospace fw-bold text-primary py-2 text-end fs-5" id="summaryNetRefund">
                                        TSh 0.00
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                        <?php if ((float)$sale['balance_due'] > 0): ?>
                            <div class="alert alert-warning py-2 px-3 small mt-3 mb-0">
                                <i class="bi bi-exclamation-circle me-1"></i>
                                <strong>Ankara ya Mkopo:</strong> Ankara hii inadaiwa TSh <?= number_format((float)$sale['balance_due'], 2) ?>. Thamani ya rejesho itapunguza deni hili kwanza kabla ya kutoa fedha taslimu!
                            </div>
                        <?php endif; ?>

                        <div class="mt-4 d-grid gap-2">
                            <?php if ($hasReturnable): ?>
                                <?php if (Auth::hasRole(['super_admin', 'manager']) || Auth::hasPermission('returns.approve')): ?>
                                    <button type="submit" name="auto_approve" value="1" class="btn btn-success btn-sm">
                                        <i class="bi bi-check-circle me-1"></i> Idhinisha Moja kwa Moja (Approve Return)
                                    </button>
                                <?php endif; ?>

                                <button type="submit" name="submit_request" value="1" class="btn btn-primary-custom btn-sm">
                                    <i class="bi bi-send me-1"></i> Wasilisha Ombi la Rejesho (Submit Request)
                                </button>
                            <?php else: ?>
                                <button type="button" class="btn btn-secondary btn-sm" disabled>
                                    Vipuri Vyote Vimesharejeshwa
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
<?php endif; ?>

<!-- JavaScript for Live Return Calculation -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const qtyInputs = document.querySelectorAll('.return-qty-input');
    const feeInput = document.getElementById('return_fee_input');
    const grossDisplay = document.getElementById('summaryGrossSubtotal');
    const netDisplay = document.getElementById('summaryNetRefund');

    function formatMoney(amount) {
        return 'TSh ' + Number(amount).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function calculateTotals() {
        let grossSubtotal = 0;

        qtyInputs.forEach(input => {
            const qty = parseInt(input.value) || 0;
            const unitPrice = parseFloat(input.getAttribute('data-unit-price')) || 0;
            const itemSubtotal = qty * unitPrice;

            const row = input.closest('tr');
            const subtotalCell = row.querySelector('.item-subtotal-display');
            if (subtotalCell) {
                subtotalCell.textContent = formatMoney(itemSubtotal);
            }

            grossSubtotal += itemSubtotal;
        });

        const fee = parseFloat(feeInput ? feeInput.value : 0) || 0;
        const netRefund = Math.max(0, grossSubtotal - fee);

        if (grossDisplay) grossDisplay.textContent = formatMoney(grossSubtotal);
        if (netDisplay) netDisplay.textContent = formatMoney(netRefund);
    }

    qtyInputs.forEach(input => {
        input.addEventListener('input', calculateTotals);
        input.addEventListener('change', calculateTotals);
    });

    if (feeInput) {
        feeInput.addEventListener('input', calculateTotals);
    }

    calculateTotals();
});
</script>
