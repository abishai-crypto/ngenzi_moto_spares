<?php
use App\Core\Helper;
?>

<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-pencil-square text-primary me-2"></i>Hariri Kipuri: <?= htmlspecialchars($product['name']) ?></h4>
        <p class="text-muted small mb-0">SKU: <code><?= htmlspecialchars($product['sku']) ?></code> &bull; Kategoria: <strong><?= htmlspecialchars($product['category_name']) ?></strong></p>
    </div>
    <div class="d-flex gap-2">
        <a href="/index.php?route=product_show&id=<?= $product['id'] ?>" class="btn btn-outline-info">
            <i class="bi bi-eye me-1"></i> Tazama Undani
        </a>
        <a href="/index.php?route=products" class="btn btn-light border">
            <i class="bi bi-arrow-left me-1"></i> Orodha
        </a>
    </div>
</div>

<form action="/index.php?route=product_update" method="POST" id="editProductForm">
    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
    <input type="hidden" name="id" value="<?= $product['id'] ?>">

    <div class="row g-4">
        <!-- Main Form Column (Left 8 cols) -->
        <div class="col-lg-8">

            <!-- SECTION 1: Basic Information -->
            <div class="premium-card mb-4">
                <div class="premium-card-header">
                    <h5><i class="bi bi-info-circle-fill text-primary"></i> 1. Taarifa za Msingi</h5>
                </div>
                <div class="premium-card-body p-4">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label fw-semibold small">Jina la Kipuri (Product Name) <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control form-control-lg" required value="<?= htmlspecialchars($product['name']) ?>">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">SKU (Utambulisho usiobadilika)</label>
                            <input type="text" class="form-control bg-light" readonly value="<?= htmlspecialchars($product['sku']) ?>">
                            <small class="text-muted" style="font-size:11px;">SKU haiwezi kubadilishwa ili kulinda kumbukumbu za mauzo na stoo.</small>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Barcode</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-upc"></i></span>
                                <input type="text" name="barcode" class="form-control" value="<?= htmlspecialchars($product['barcode'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Eneo la Shelfu / Stoo</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-geo-alt"></i></span>
                                <input type="text" name="shelf_location" class="form-control" value="<?= htmlspecialchars($product['shelf_location'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Muda wa Waranti (Siku)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-shield-check"></i></span>
                                <input type="number" name="warranty_days" class="form-control" min="0" value="<?= (int)($product['warranty_days'] ?? 0) ?>">
                                <span class="input-group-text bg-light small">Siku</span>
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold small">Maelezo ya Ziada (Description)</label>
                            <textarea name="description" class="form-control" rows="2"><?= htmlspecialchars($product['description'] ?? '') ?></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SECTION 2: Classification -->
            <div class="premium-card mb-4">
                <div class="premium-card-header">
                    <h5><i class="bi bi-tags-fill text-teal"></i> 2. Uainishaji (Classification)</h5>
                </div>
                <div class="premium-card-body p-4">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">Kategoria <span class="text-danger">*</span></label>
                            <select name="category_id" class="form-select" required>
                                <?php foreach ($categories as $c): ?>
                                    <option value="<?= $c['id'] ?>" <?= (int)$product['category_id'] === (int)$c['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($c['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">Chapa ya Kipuri (Brand)</label>
                            <select name="brand_id" class="form-select">
                                <option value="">-- Generic / Hakuna --</option>
                                <?php foreach ($brands as $b): ?>
                                    <option value="<?= $b['id'] ?>" <?= (int)$product['brand_id'] === (int)$b['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($b['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">Kipimo cha Hesabu (Unit) <span class="text-danger">*</span></label>
                            <select name="unit_id" class="form-select" required>
                                <?php foreach ($units as $u): ?>
                                    <option value="<?= $u['id'] ?>" <?= (int)$product['unit_id'] === (int)$u['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($u['name']) ?> (<?= htmlspecialchars($u['short_name']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SECTION 3: Vehicle Compatibility Multi-Select -->
            <div class="premium-card mb-4">
                <div class="premium-card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0"><i class="bi bi-bicycle text-primary"></i> 3. Vyombo Vinavyoendana (Vehicle Compatibility)</h5>
                        <small class="text-muted">Chagua miundo yote ya pikipiki na bajaji zinazoweza kutumia kipuri hiki.</small>
                    </div>
                    <span class="badge bg-primary-subtle text-primary rounded-pill px-3 py-1" id="selectedVehiclesCount">
                        <?= count($assignedVehicleIds) ?> Zimechaguliwa
                    </span>
                </div>
                <div class="premium-card-body p-3">
                    <div class="mb-3">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                            <input type="text" id="vehicleFilterInput" class="form-control" placeholder="Tafuta chombo kwa haraka...">
                        </div>
                    </div>

                    <div class="vehicle-picker-box border rounded p-2 bg-light" style="max-height: 280px; overflow-y: auto;">
                        <div class="row g-2" id="vehicleListContainer">
                            <?php foreach ($vehicleModels as $vm): ?>
                                <?php $isAssigned = in_array((int)$vm['id'], $assignedVehicleIds); ?>
                                <div class="col-md-6 vehicle-item-col" data-search="<?= strtolower(htmlspecialchars($vm['brand_name'] . ' ' . $vm['model_name'] . ' ' . $vm['engine_capacity'] . ' ' . $vm['vehicle_type'])) ?>">
                                    <div class="form-check p-2 bg-white rounded border <?= $isAssigned ? 'border-primary' : 'border-light' ?> h-100">
                                        <input class="form-check-input vehicle-checkbox" type="checkbox" name="vehicle_models[]" value="<?= $vm['id'] ?>" id="vm_<?= $vm['id'] ?>" <?= $isAssigned ? 'checked' : '' ?>>
                                        <label class="form-check-label small w-100 cursor-pointer" for="vm_<?= $vm['id'] ?>">
                                            <div class="fw-bold text-dark"><?= htmlspecialchars($vm['brand_name']) ?> <?= htmlspecialchars($vm['model_name']) ?></div>
                                            <div class="text-muted" style="font-size:11px;">
                                                <span class="badge <?= $vm['vehicle_type'] === 'Bajaj' ? 'bg-warning-subtle text-dark' : 'bg-info-subtle text-info' ?> py-0 px-1 me-1">
                                                    <?= htmlspecialchars($vm['vehicle_type']) ?>
                                                </span>
                                                <?= htmlspecialchars($vm['engine_capacity'] ?? '') ?>
                                                <?php if (!empty($vm['year_from'])): ?> &bull; Mwaka <?= $vm['year_from'] ?>-<?= $vm['year_to'] ?: 'Sasa' ?><?php endif; ?>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SECTION 4: Stock Settings -->
            <div class="premium-card mb-4">
                <div class="premium-card-header">
                    <h5><i class="bi bi-box-fill text-teal"></i> 4. Mipangilio ya Stoo</h5>
                </div>
                <div class="premium-card-body p-4">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">Kiwango cha Chini (Min Level)</label>
                            <input type="number" name="min_stock_level" class="form-control" value="<?= (int)$product['min_stock_level'] ?>" min="0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">Reorder Level (Agiza Mzigo)</label>
                            <input type="number" name="reorder_level" class="form-control" value="<?= (int)$product['reorder_level'] ?>" min="0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">Kiwango cha Juu (Max Level)</label>
                            <input type="number" name="max_stock_level" class="form-control" value="<?= (int)$product['max_stock_level'] ?>" min="1">
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- Right Side Column: Pricing Engine & Audit Information (Right 4 cols) -->
        <div class="col-lg-4">

            <!-- SECTION 5: Pricing & Profit Calculation -->
            <div class="premium-card mb-4 border-primary">
                <div class="premium-card-header bg-primary text-white">
                    <h5 class="mb-0 text-white"><i class="bi bi-cash-stack me-1"></i> Bei & Hesabu ya Faida</h5>
                </div>
                <div class="premium-card-body p-4">

                    <!-- Buying Price Input -->
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Bei ya Kununua (Cost Price - TZS) <span class="text-danger">*</span></label>
                        <div class="input-group input-group-lg">
                            <span class="input-group-text bg-light fw-bold text-muted">TZS</span>
                            <input type="number" step="100" name="buying_price" id="buyingPriceInput" class="form-control fw-bold fs-5 text-dark" required value="<?= (float)$product['buying_price'] ?>" min="0" <?= !$canSeeCosts ? 'readonly' : '' ?>>
                        </div>
                        <?php if (!$canSeeCosts): ?>
                            <small class="text-danger" style="font-size:11px;"><i class="bi bi-lock-fill me-1"></i>Huna mamlaka ya kubadili bei ya kununua.</small>
                        <?php endif; ?>
                    </div>

                    <!-- Hidden target margin to preserve backend compatibility without showing percentages -->
                    <input type="hidden" name="target_margin" id="targetMarginInput" value="<?= (float)$product['target_margin'] ?>">

                    <!-- Smart Recommended Price Box -->
                    <div class="p-3 bg-light rounded-3 border mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="small fw-semibold text-muted">Bei Elekezi:</span>
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill">Bei ya Rejareja</span>
                        </div>
                        <div class="fs-4 fw-bolder text-primary" id="recommendedPriceDisplay"><?= Helper::formatMoney((float)$product['recommended_selling_price']) ?></div>
                        <input type="hidden" name="recommended_selling_price" id="recommendedPriceHidden" value="<?= (float)$product['recommended_selling_price'] ?>">
                        <div class="small text-muted d-flex justify-content-between mt-1">
                            <span>Faida Tarajiwa:</span>
                            <strong class="text-success" id="expectedProfitDisplay">+<?= Helper::formatMoney((float)$product['recommended_selling_price'] - (float)$product['buying_price']) ?></strong>
                        </div>
                    </div>

                    <!-- Actual Selling Price Input -->
                    <div class="mb-3">
                        <label class="form-label fw-bold small d-flex justify-content-between">
                            <span>Bei Halisi ya Kuuza <span class="text-danger">*</span></span>
                            <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none" id="btnUseRecommended">Tumia Bei Elekezi</button>
                        </label>
                        <div class="input-group input-group-lg">
                            <span class="input-group-text bg-light fw-bold text-muted">TZS</span>
                            <input type="number" step="100" name="selling_price" id="sellingPriceInput" class="form-control fw-bold fs-5 text-dark" required value="<?= (float)$product['selling_price'] ?>" min="0">
                        </div>
                    </div>

                    <!-- Live Realized Profit Status Badge -->
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center p-2 rounded border" id="liveMarginStatusBox">
                            <span class="small fw-semibold">Kiasi cha Faida Halisi:</span>
                            <span class="badge bg-secondary" id="liveMarginBadge">+TZS 0</span>
                        </div>
                    </div>

                    <!-- Minimum Selling Price Input -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Bei ya Chini ya Mwisho (Minimum)</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light">TZS</span>
                            <input type="number" step="100" name="minimum_selling_price" id="minimumPriceInput" class="form-control" value="<?= (float)$product['minimum_selling_price'] ?>">
                        </div>
                    </div>

                    <!-- Price Change Reason (Audit Requirement) -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Sababu ya Mabadiliko ya Bei</label>
                        <input type="text" name="price_change_reason" class="form-control" placeholder="mf. Gharama za msambazaji zimepanda">
                        <small class="text-muted" style="font-size:11px;">Itahifadhiwa kwenye kumbukumbu ya bei (Price History).</small>
                    </div>

                </div>
            </div>

            <!-- SECTION 6: Status & Save Action -->
            <div class="premium-card">
                <div class="premium-card-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Hali ya Kipuri (Status)</label>
                        <select name="status" class="form-select">
                            <option value="active" <?= $product['status'] === 'active' ? 'selected' : '' ?>>Hai (Active - Tayari Kuuzwa)</option>
                            <option value="inactive" <?= $product['status'] === 'inactive' ? 'selected' : '' ?>>Imezimwa (Inactive)</option>
                            <option value="discontinued" <?= $product['status'] === 'discontinued' ? 'selected' : '' ?>>Haitolewi Tena (Discontinued)</option>
                        </select>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary-custom btn-lg shadow-sm">
                            <i class="bi bi-check2-circle me-1"></i> Hifadhi Mabadiliko
                        </button>
                        <a href="/index.php?route=products" class="btn btn-light border">Ghairi</a>
                    </div>
                </div>
            </div>

        </div>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const buyingInput = document.getElementById('buyingPriceInput');
    const marginInput = document.getElementById('targetMarginInput');
    const recDisplay = document.getElementById('recommendedPriceDisplay');
    const recHidden = document.getElementById('recommendedPriceHidden');
    const profitDisplay = document.getElementById('expectedProfitDisplay');
    const sellingInput = document.getElementById('sellingPriceInput');
    const minInput = document.getElementById('minimumPriceInput');
    const btnUseRec = document.getElementById('btnUseRecommended');
    const liveMarginBadge = document.getElementById('liveMarginBadge');
    const liveStatusBox = document.getElementById('liveMarginStatusBox');

    function calculateSmartPrice() {
        const cost = parseFloat(buyingInput.value) || 0;
        let targetMargin = (marginInput ? parseFloat(marginInput.value) : 30.0) || 30.0;
        if (targetMargin >= 100) targetMargin = 99;

        if (cost > 0) {
            const recommended = Math.round(cost / (1 - (targetMargin / 100)));
            const profit = recommended - cost;

            recDisplay.textContent = 'TZS ' + recommended.toLocaleString();
            recHidden.value = recommended;
            profitDisplay.textContent = (profit >= 0 ? '+TZS ' : '-TZS ') + Math.abs(profit).toLocaleString();
        }
        updateLiveMargin();
    }

    function updateLiveMargin() {
        const cost = parseFloat(buyingInput.value) || 0;
        const selling = parseFloat(sellingInput.value) || 0;

        if (selling > 0 && cost > 0) {
            const profit = selling - cost;
            liveMarginBadge.textContent = (profit >= 0 ? '+TZS ' : '-TZS ') + Math.abs(profit).toLocaleString();

            if (profit > 0) {
                liveMarginBadge.className = 'badge bg-success';
                liveStatusBox.style.borderColor = '#198754';
                liveStatusBox.style.background = '#e8f5e9';
            } else if (profit === 0) {
                liveMarginBadge.className = 'badge bg-warning text-dark';
                liveStatusBox.style.borderColor = '#ffc107';
                liveStatusBox.style.background = '#fffde7';
            } else {
                liveMarginBadge.className = 'badge bg-danger';
                liveStatusBox.style.borderColor = '#dc3545';
                liveStatusBox.style.background = '#ffebee';
            }
        } else {
            liveMarginBadge.className = 'badge bg-secondary';
            liveMarginBadge.textContent = '+TZS 0';
            liveStatusBox.style.borderColor = '#dee2e6';
            liveStatusBox.style.background = '#f8f9fa';
        }
    }

    buyingInput.addEventListener('input', calculateSmartPrice);
    marginInput.addEventListener('input', calculateSmartPrice);
    sellingInput.addEventListener('input', updateLiveMargin);

    btnUseRec.addEventListener('click', function() {
        sellingInput.value = recHidden.value;
        updateLiveMargin();
    });

    calculateSmartPrice();

    // Vehicle Search Filter
    const vehicleFilterInput = document.getElementById('vehicleFilterInput');
    const vehicleCols = document.querySelectorAll('.vehicle-item-col');
    const countBadge = document.getElementById('selectedVehiclesCount');
    const vehicleCheckboxes = document.querySelectorAll('.vehicle-checkbox');

    vehicleFilterInput.addEventListener('input', function() {
        const query = this.value.toLowerCase().trim();
        vehicleCols.forEach(col => {
            const text = col.getAttribute('data-search') || '';
            col.style.display = text.includes(query) ? '' : 'none';
        });
    });

    function updateVehicleCount() {
        const checked = document.querySelectorAll('.vehicle-checkbox:checked').length;
        countBadge.textContent = checked + ' Zimechaguliwa';
    }

    vehicleCheckboxes.forEach(cb => {
        cb.addEventListener('change', function() {
            const wrapper = this.closest('.form-check');
            if (this.checked) {
                wrapper.classList.add('border-primary');
                wrapper.classList.remove('border-light');
            } else {
                wrapper.classList.remove('border-primary');
                wrapper.classList.add('border-light');
            }
            updateVehicleCount();
        });
    });
});
</script>
