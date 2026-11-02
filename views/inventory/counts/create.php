<?php
use App\Core\Helper;
use App\Core\Auth;
?>

<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-1">
            <i class="bi bi-clipboard-plus text-primary me-2"></i>Anzisha Ukaguzi wa Kimwili wa Stoo (New Stock Count)
        </h4>
        <p class="text-muted small mb-0">Ingiza idadi halisi ya vipuri vilivyopo stoo ili kulinganisha na taarifa za mfumo.</p>
    </div>
    <a href="/index.php?route=inventory_counts" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Orodha ya Ukaguzi
    </a>
</div>

<!-- Category Filter to Narrow Down Count -->
<div class="card border-0 shadow-sm rounded-3 mb-4">
    <div class="card-body p-3">
        <form method="GET" action="/index.php" class="row g-2 align-items-end">
            <input type="hidden" name="route" value="inventory_count_create">
            <div class="col-md-6">
                <label class="form-label small fw-semibold text-muted mb-1">Chuja Vipuri kwa Kategoria Maalum (Hiari):</label>
                <select name="category_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">-- Kategoria Zote (Vipuri Vyote vya Stoo) --</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= ($categoryId == $cat['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6 text-md-end">
                <span class="small text-muted">Vipuri vilivyochaguliwa kwa ukaguzi huu: <strong><?= count($products) ?></strong></span>
            </div>
        </form>
    </div>
</div>

<form action="/index.php?route=inventory_count_store" method="POST" id="countForm">
    <input type="hidden" name="csrf_token" value="<?= Helper::csrfToken() ?>">

    <!-- Metadata Card -->
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-body p-4">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Namba ya Ukaguzi (Count No)</label>
                    <input type="text" name="count_no" class="form-control" value="<?= htmlspecialchars($suggestedCode) ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Tarehe ya Kuhesabu</label>
                    <input type="date" name="count_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Mchunguzi / Mwangalizi</label>
                    <input type="text" class="form-control bg-light" value="<?= htmlspecialchars(Auth::user()['full_name'] ?? 'Storekeeper') ?>" readonly>
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold">Maelezo ya Ukaguzi (Session Notes)</label>
                    <textarea name="notes" class="form-control" rows="2" placeholder="Mf. Ukaguzi wa mwisho wa mwezi stoo kuu ya Kariakoo..."></textarea>
                </div>
            </div>
        </div>
    </div>

    <!-- Counting Items Table -->
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
            <h6 class="fw-bold mb-0 text-dark">
                Orodha ya Vipuri vya Kuhesabu (Count Sheet)
            </h6>
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                <?= count($products) ?> Bidhaa
            </span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive" style="max-height: 550px; overflow-y: auto;">
                <table class="table align-middle table-hover mb-0">
                    <thead class="table-light small text-muted sticky-top bg-light">
                        <tr>
                            <th style="width: 35%;">Kipuri & SKU</th>
                            <th>Kategoria</th>
                            <th class="text-center" style="width: 12%;">Idadi ya Mfumo (System Qty)</th>
                            <th class="text-center" style="width: 15%;">Hesabu Halisi (Physical Qty)</th>
                            <th class="text-center" style="width: 12%;">Tofauti (Variance)</th>
                            <th style="width: 25%;">Sababu / Maelezo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $pr): ?>
                            <tr id="row-<?= $pr['id'] ?>">
                                <td>
                                    <div class="fw-semibold text-dark"><?= htmlspecialchars($pr['name']) ?></div>
                                    <div class="small text-muted font-monospace">SKU: <?= htmlspecialchars($pr['sku']) ?></div>
                                </td>
                                <td class="small text-muted"><?= htmlspecialchars($pr['category_name']) ?></td>
                                <td class="text-center fw-bold fs-6 text-secondary" id="sys-qty-<?= $pr['id'] ?>">
                                    <?= (int)$pr['quantity'] ?> <span class="small fw-normal text-muted"><?= $pr['unit_short'] ?? 'Pcs' ?></span>
                                </td>
                                <td class="text-center">
                                    <input type="number" 
                                           name="items[<?= $pr['id'] ?>][physical_quantity]" 
                                           class="form-control form-control-sm text-center fw-bold physical-qty-input" 
                                           data-id="<?= $pr['id'] ?>"
                                           data-sys="<?= (int)$pr['quantity'] ?>"
                                           min="0" step="1" 
                                           value="<?= (int)$pr['quantity'] ?>" 
                                           required>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-success-subtle text-success border border-success-subtle variance-badge" id="variance-<?= $pr['id'] ?>">
                                        0 (Sawa)
                                    </span>
                                </td>
                                <td>
                                    <input type="text" 
                                           name="items[<?= $pr['id'] ?>][reason]" 
                                           class="form-control form-control-sm" 
                                           placeholder="Eleza ikitokea tofauti...">
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white border-top py-3 d-flex align-items-center justify-content-between">
            <a href="/index.php?route=inventory_counts" class="btn btn-light border">
                <i class="bi bi-x-circle me-1"></i> Ghairi
            </a>
            <button type="submit" class="btn btn-primary-custom px-4 fw-semibold">
                <i class="bi bi-send-check me-1"></i> Wasilisha Ukaguzi kwa Uidhinishaji (Submit Count)
            </button>
        </div>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const inputs = document.querySelectorAll('.physical-qty-input');
    inputs.forEach(input => {
        input.addEventListener('input', function() {
            const id = this.getAttribute('data-id');
            const sysQty = parseInt(this.getAttribute('data-sys')) || 0;
            const physQty = parseInt(this.value) || 0;
            const variance = physQty - sysQty;
            const badge = document.getElementById('variance-' + id);

            if (variance === 0) {
                badge.className = 'badge bg-success-subtle text-success border border-success-subtle variance-badge';
                badge.textContent = '0 (Sawa)';
            } else if (variance > 0) {
                badge.className = 'badge bg-primary-subtle text-primary border border-primary-subtle variance-badge';
                badge.textContent = '+' + variance + ' (Ziada)';
            } else {
                badge.className = 'badge bg-danger-subtle text-danger border border-danger-subtle variance-badge';
                badge.textContent = variance + ' (Upungufu)';
            }
        });
    });
});
</script>
