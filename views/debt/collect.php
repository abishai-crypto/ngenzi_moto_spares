<?php
use App\Core\Helper;
use App\Core\Auth;
?>

<!-- Header -->
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-1">
            <i class="bi bi-cash-coin text-success me-2"></i>Kukusanya Malipo ya Deni (Debt Collection)
        </h4>
        <p class="text-muted small mb-0">Pokea malipo ya deni toka kwa mteja na gawa kiasi hicho kwenye ankara ambazo hazijalipwa.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="/index.php?route=debts" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1">
            <i class="bi bi-arrow-left"></i> Dashibodi ya Madeni
        </a>
    </div>
</div>

<div class="row g-4">
    <!-- Left Column: Customer Selector & Unpaid Invoices -->
    <div class="col-lg-7">
        <!-- Customer Selection Card -->
        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="fw-bold mb-0 text-dark">
                    <i class="bi bi-person-check text-primary me-2"></i>Chagua Mteja
                </h6>
            </div>
            <div class="card-body p-3">
                <form method="GET" action="/index.php" class="row g-2 align-items-end">
                    <input type="hidden" name="route" value="debt_collect">
                    <div class="col-md-9">
                        <label class="form-label small fw-semibold text-muted mb-1">Mteja Mwenye Deni:</label>
                        <select name="customer_id" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">-- Chagua Mteja --</option>
                            <?php foreach ($debtors as $d): ?>
                                <option value="<?= $d['id'] ?>" <?= ($customerId == $d['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($d['name']) ?> (<?= htmlspecialchars($d['customer_code']) ?> - Deni: TSh <?= number_format((float)$d['current_balance']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary-custom btn-sm w-100">
                            Pakia Ankara
                        </button>
                    </div>
                </form>

                <?php if ($customer): ?>
                    <div class="mt-3 p-3 bg-light rounded border">
                        <div class="row g-2">
                            <div class="col-sm-4">
                                <span class="small text-muted d-block">Jina la Mteja:</span>
                                <strong class="text-dark"><?= htmlspecialchars($customer['name']) ?></strong>
                            </div>
                            <div class="col-sm-4">
                                <span class="small text-muted d-block">Simu:</span>
                                <span class="font-monospace text-dark"><?= htmlspecialchars($customer['phone'] ?? '-') ?></span>
                            </div>
                            <div class="col-sm-4">
                                <span class="small text-muted d-block">Deni Lililopo Sasa:</span>
                                <h5 class="fw-bold font-monospace text-danger mb-0"><?= Helper::formatCurrency((float)$customer['current_balance']) ?></h5>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Outstanding Invoices List -->
        <?php if ($customer): ?>
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
                    <h6 class="fw-bold mb-0 text-dark">
                        <i class="bi bi-receipt text-primary me-2"></i>Ankara Zisizolipwa (Outstanding Invoices)
                    </h6>
                    <span class="badge bg-danger text-white font-monospace"><?= count($outstandingInvoices) ?> ankara</span>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($outstandingInvoices)): ?>
                        <div class="text-center py-4 text-muted">
                            Mteja huyu hana ankara zenye madeni kwa sasa.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light small text-muted">
                                    <tr>
                                        <th class="ps-3">Ankara #</th>
                                        <th>Tarehe</th>
                                        <th>Mwisho (Due)</th>
                                        <th class="text-end">Jumla</th>
                                        <th class="text-end">Salio Linalobaki</th>
                                        <th class="text-center">Hali</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($outstandingInvoices as $inv): ?>
                                        <tr>
                                            <td class="ps-3 font-monospace fw-bold">
                                                <a href="/index.php?route=sale_show&id=<?= $inv['id'] ?>" target="_blank" class="text-decoration-none">
                                                    <?= htmlspecialchars($inv['invoice_no']) ?>
                                                </a>
                                            </td>
                                            <td class="small font-monospace text-muted">
                                                <?= date('d/m/Y', strtotime($inv['sale_date'])) ?>
                                            </td>
                                            <td class="small font-monospace">
                                                <?= $inv['due_date'] ? date('d/m/Y', strtotime($inv['due_date'])) : '-' ?>
                                                <?php if (!empty($inv['is_overdue'])): ?>
                                                    <span class="badge bg-danger ms-1"><?= $inv['days_overdue'] ?>d</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-end font-monospace text-muted">
                                                <?= Helper::formatCurrency((float)$inv['total_amount']) ?>
                                            </td>
                                            <td class="text-end font-monospace fw-bold text-danger">
                                                <?= Helper::formatCurrency((float)$inv['balance_due']) ?>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle">
                                                    <?= htmlspecialchars($inv['payment_status']) ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Right Column: Collection Payment Form -->
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="fw-bold mb-0 text-dark">
                    <i class="bi bi-wallet2 text-success me-2"></i>Fomu ya Kupokea Malipo
                </h6>
            </div>
            <div class="card-body p-4">
                <?php if (!$customer): ?>
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-arrow-left-circle display-4 text-muted"></i>
                        <p class="mt-2 mb-0">Tafadhali chagua mteja kwanza upande wa kushoto.</p>
                    </div>
                <?php else: ?>
                    <form action="/index.php?route=debt_collect" method="POST">
                        <input type="hidden" name="csrf_token" value="<?= Helper::generateCsrf() ?>">
                        <input type="hidden" name="customer_id" value="<?= $customer['id'] ?>">

                        <!-- Total Debt Indicator -->
                        <div class="alert alert-danger p-3 mb-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="small fw-semibold">Jumla ya Deni Linalodaiwa:</span>
                                <h4 class="fw-bold font-monospace mb-0"><?= Helper::formatCurrency((float)$customer['current_balance']) ?></h4>
                            </div>
                        </div>

                        <!-- Amount to Pay -->
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-dark">Kiasi Kinacholipwa (TSh) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="1" max="<?= (float)$customer['current_balance'] ?>" 
                                   name="amount" class="form-control form-control-lg font-monospace fw-bold text-success" 
                                   placeholder="0.00" value="<?= (float)$customer['current_balance'] ?>" required>
                            <small class="text-muted">Mfumo utagawa kiasi hiki kuanzia ankara ya zamani zaidi kiotomatiki (FIFO).</small>
                        </div>

                        <!-- Payment Method -->
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-dark">Njia ya Malipo <span class="text-danger">*</span></label>
                            <select name="payment_method" id="debtPayMethod" class="form-select" required>
                                <?php foreach ($paymentMethods as $pm): ?>
                                    <option value="<?= trim($pm) ?>"><?= htmlspecialchars(trim($pm)) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Provider (for Mobile Money / Bank) -->
                        <div class="mb-3" id="providerGroup">
                            <label class="form-label small fw-semibold text-dark">Mtoa Huduma (Provider / Mtandao)</label>
                            <select name="provider" class="form-select">
                                <option value="">-- Chagua (Hiari) --</option>
                                <?php foreach ($providers as $pr): ?>
                                    <option value="<?= trim($pr) ?>"><?= htmlspecialchars(trim($pr)) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Reference No -->
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-dark">Nambari ya Kumbukumbu (Tx Reference / Namba ya Muamala)</label>
                            <input type="text" name="reference_no" class="form-control" placeholder="Mf. QX78945612 au NMB-REF-001">
                        </div>

                        <!-- Notes -->
                        <div class="mb-4">
                            <label class="form-label small fw-semibold text-dark">Maelezo ya Ziada (Notes)</label>
                            <textarea name="notes" class="form-control" rows="2" placeholder="Maelezo yoyote kuhusu malipo haya..."></textarea>
                        </div>

                        <!-- Submit Button -->
                        <button type="submit" class="btn btn-success btn-lg w-100 fw-bold">
                            <i class="bi bi-check-circle me-1"></i> Kamilisha & Toa Risiti ya Malipo
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
