<?php
use App\Core\Helper;
use App\Core\Auth;
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h4 class="fw-bold mb-1">
                    <i class="bi bi-sliders text-warning me-2"></i>Marekebisho ya Stoo (Stock Adjustment)
                </h4>
                <p class="text-muted small mb-0">Fanya marekebisho ya idadi ya stoo kwa sababu zilizoidhinishwa (Damaged, Lost, Correction).</p>
            </div>
            <a href="/index.php?route=inventory_history" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-clock-history me-1"></i> Historia ya Mienendo
            </a>
        </div>

        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-body p-4">
                <form action="/index.php?route=inventory_adjust_save" method="POST" id="adjustmentForm">
                    <input type="hidden" name="csrf_token" value="<?= Helper::csrfToken() ?>">

                    <!-- Product Selection -->
                    <div class="mb-3">
                        <label for="product_id" class="form-label fw-semibold">Chagua Kipuri (Product) <span class="text-danger">*</span></label>
                        <select name="product_id" id="product_id" class="form-select select2" required>
                            <option value="">-- Chagua Kipuri --</option>
                            <?php foreach ($products as $pr): ?>
                                <option value="<?= $pr['id'] ?>" 
                                        data-qty="<?= $pr['quantity'] ?>"
                                        data-unit="<?= htmlspecialchars($pr['unit_short'] ?? 'Pcs') ?>"
                                        data-sku="<?= htmlspecialchars($pr['sku']) ?>"
                                        <?= ($selectedProduct && $selectedProduct['id'] == $pr['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($pr['name']) ?> (<?= htmlspecialchars($pr['sku']) ?>) — Stoo: <?= $pr['quantity'] ?> <?= htmlspecialchars($pr['unit_short'] ?? 'Pcs') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Live Stock Indicator Card -->
                    <div class="p-3 bg-light rounded-3 mb-3 d-flex align-items-center justify-content-between border">
                        <div>
                            <span class="small text-muted d-block text-uppercase fw-semibold">Idadi Iliyopo Sasa Kwenye Mfumo (Current Stock)</span>
                            <div class="fs-4 fw-bold text-dark mt-1" id="currentStockDisplay">
                                <?= $selectedProduct ? $selectedProduct['quantity'] . ' ' . ($selectedProduct['unit_short'] ?? 'Pcs') : '--' ?>
                            </div>
                        </div>
                        <div class="text-end">
                            <span class="small text-muted d-block text-uppercase fw-semibold">Makadirio ya Baada ya Marekebisho</span>
                            <div class="fs-4 fw-bold text-primary mt-1" id="newStockPreview">--</div>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <!-- Adjustment Type -->
                        <div class="col-md-6">
                            <label for="adjustment_type" class="form-label fw-semibold">Aina ya Marekebisho <span class="text-danger">*</span></label>
                            <select name="adjustment_type" id="adjustment_type" class="form-select" required>
                                <option value="adjustment_in">Adjustment In (+) — Ongezeko la Hesabu</option>
                                <option value="adjustment_out">Adjustment Out (-) — Upungufu wa Hesabu</option>
                                <option value="damage">Damage (-) — Kipuri Kimeharibika</option>
                                <option value="loss">Loss (-) — Kipuri Kimepotea</option>
                                <option value="opening_stock">Opening Stock (+) — Stoo ya Mwanzo</option>
                            </select>
                            <div class="form-text small">Chagua kama unataka kuongeza au kupunguza idadi stoo.</div>
                        </div>

                        <!-- Quantity -->
                        <div class="col-md-6">
                            <label for="quantity" class="form-label fw-semibold">Idadi ya Kurekebisha (Units) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" name="quantity" id="quantity" class="form-control" min="1" step="1" placeholder="Mf. 5" required>
                                <span class="input-group-text bg-light text-muted" id="unitDisplay">Pcs</span>
                            </div>
                        </div>
                    </div>

                    <!-- Reason -->
                    <div class="mb-3">
                        <label for="reason" class="form-label fw-semibold">Sababu ya Marekebisho <span class="text-danger">*</span></label>
                        <select name="reason" id="reason" class="form-select" required>
                            <option value="">-- Chagua Sababu --</option>
                            <option value="Physical count correction">Marekebisho baada ya kuhesabu stoo (Physical Count)</option>
                            <option value="Damaged item">Kipuri kimevunjika au kimeharibika (Damaged)</option>
                            <option value="Lost item">Kipuri kimepotea (Lost / Missing)</option>
                            <option value="Data correction">Kusahihisha makosa ya uwekaji data (Data Entry Correction)</option>
                            <option value="Opening stock">Kuingiza stoo ya mwanzo (Opening Stock)</option>
                            <option value="Supplier return discrepancy">Hitilafu ya mzigo uliorudishwa</option>
                            <option value="Other">Sababu Nyingine (Eleza hapa chini)</option>
                        </select>
                    </div>

                    <!-- Notes -->
                    <div class="mb-4">
                        <label for="notes" class="form-label fw-semibold">Maelezo ya Ziada (Notes / Audit Memo)</label>
                        <textarea name="notes" id="notes" class="form-control" rows="3" placeholder="Toa maelezo ya kina kuhusu tukio hili kwa ajili ya ukaguzi..."></textarea>
                    </div>

                    <div class="d-flex align-items-center justify-content-between pt-3 border-top">
                        <a href="/index.php?route=inventory" class="btn btn-light border">
                            <i class="bi bi-x-circle me-1"></i> Ghairi
                        </a>
                        <button type="submit" class="btn btn-warning text-dark fw-semibold px-4">
                            <i class="bi bi-check2-circle me-1"></i> Hifadhi Marekebisho ya Stoo
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const productSelect = document.getElementById('product_id');
    const typeSelect = document.getElementById('adjustment_type');
    const qtyInput = document.getElementById('quantity');
    const currentStockDisplay = document.getElementById('currentStockDisplay');
    const newStockPreview = document.getElementById('newStockPreview');
    const unitDisplay = document.getElementById('unitDisplay');

    function calculatePreview() {
        const selected = productSelect.options[productSelect.selectedIndex];
        if (!selected || !selected.value) {
            currentStockDisplay.textContent = '--';
            newStockPreview.textContent = '--';
            return;
        }

        const currentQty = parseInt(selected.getAttribute('data-qty')) || 0;
        const unit = selected.getAttribute('data-unit') || 'Pcs';
        const changeQty = parseInt(qtyInput.value) || 0;
        const type = typeSelect.value;

        currentStockDisplay.textContent = currentQty + ' ' + unit;
        unitDisplay.textContent = unit;

        let newQty = currentQty;
        if (type === 'adjustment_in' || type === 'opening_stock') {
            newQty = currentQty + changeQty;
        } else {
            newQty = currentQty - changeQty;
        }

        if (changeQty > 0) {
            newStockPreview.textContent = newQty + ' ' + unit;
            if (newQty < 0) {
                newStockPreview.className = 'fs-4 fw-bold text-danger mt-1';
                newStockPreview.textContent = newQty + ' ' + unit + ' (HAIRUHUSIWI - STOO HAITOSHI!)';
            } else {
                newStockPreview.className = 'fs-4 fw-bold text-primary mt-1';
            }
        } else {
            newStockPreview.textContent = currentQty + ' ' + unit;
        }
    }

    productSelect.addEventListener('change', calculatePreview);
    typeSelect.addEventListener('change', calculatePreview);
    qtyInput.addEventListener('input', calculatePreview);

    calculatePreview();
});
</script>
