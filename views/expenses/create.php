<?php
use App\Core\Helper;
use App\Core\Auth;
?>

<div class="row justify-content-center">
    <div class="col-lg-8 col-md-10">
        <!-- Header -->
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h4 class="fw-bold mb-1 text-dark">
                    <i class="bi bi-plus-circle text-danger me-2"></i>Rekodi Matumizi Mapya (Record Expense)
                </h4>
                <p class="text-muted small mb-0">Namba ya Vocha inayotengenezwa: <strong class="text-primary font-monospace"><?= htmlspecialchars($nextExpenseNo) ?></strong></p>
            </div>
            <a href="/index.php?route=expenses" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Rudi kwenye Orodha
            </a>
        </div>

        <!-- Form Card -->
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-body p-4">
                <form action="/index.php?route=expenses/store" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= Helper::generateCSRF() ?>">

                    <div class="row g-3">
                        <!-- Category -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Kategoria ya Matumizi <span class="text-danger">*</span></label>
                            <select name="category_id" class="form-select" required>
                                <option value="">-- Chagua Kategoria --</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>">
                                        <?= htmlspecialchars($cat['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">Chagua aina ya gharama za uendeshaji (OPEX).</div>
                        </div>

                        <!-- Amount -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Kiasi cha Fedha (TSh) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light fw-bold">TSh</span>
                                <input type="number" step="0.01" min="1" name="amount" class="form-control fw-bold text-danger fs-5" placeholder="0.00" required>
                            </div>
                        </div>

                        <!-- Date -->
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Tarehe ya Matumizi <span class="text-danger">*</span></label>
                            <input type="date" name="expense_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>

                        <!-- Payment Method -->
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Njia ya Malipo <span class="text-danger">*</span></label>
                            <select name="payment_method" class="form-select" required>
                                <option value="Cash" selected>Taslimu (Cash)</option>
                                <option value="Mobile Money">Mitandao (M-Pesa / Tigo Pesa / Airtel Money)</option>
                                <option value="Bank">Benki (Bank Transfer / CRDB / NMB)</option>
                                <option value="Card">Kadi ya Benki (POS / Card)</option>
                            </select>
                        </div>

                        <!-- Provider / Channel -->
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Mtoa Huduma / Mtandao</label>
                            <input type="text" name="provider" class="form-control" placeholder="Mf. M-Pesa, NMB, KCB, n.k">
                        </div>

                        <!-- Reference No -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Namba ya Kumbukumbu / Muamala</label>
                            <input type="text" name="reference_no" class="form-control" placeholder="Mf. Ref No, Receipt No, Cheque No">
                        </div>

                        <!-- Payee -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Mlipwaji (Payee / Vendor / Supplier)</label>
                            <input type="text" name="payee" class="form-control" placeholder="Jina la mtu, kampuni au muuzaji aliyelipwa">
                        </div>

                        <!-- Description -->
                        <div class="col-12">
                            <label class="form-label fw-semibold">Maelezo ya Matumizi <span class="text-danger">*</span></label>
                            <input type="text" name="description" class="form-control" placeholder="Mf. Malipo ya usafirishaji vipuri kutoka stoo ya Kariakoo" required>
                        </div>

                        <!-- Notes -->
                        <div class="col-12">
                            <label class="form-label fw-semibold">Maelezo ya Ziada (Notes / Remarks)</label>
                            <textarea name="notes" class="form-control" rows="3" placeholder="Maelezo mengineyo yoyote ya ofisi kuhusu matumizi haya..."></textarea>
                        </div>
                    </div>

                    <hr class="my-4">

                    <div class="d-flex justify-content-end gap-2">
                        <a href="/index.php?route=expenses" class="btn btn-light px-4">Ghairi</a>
                        <button type="submit" class="btn btn-danger px-4 fw-semibold">
                            <i class="bi bi-check-circle me-1"></i> Rekodi Matumizi
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
