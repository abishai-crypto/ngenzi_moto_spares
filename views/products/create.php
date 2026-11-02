<?php
use App\Core\Helper;
?>

<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-plus-circle-fill text-primary me-2"></i>Sajili Kipuri Kipya</h4>
        <p class="text-muted small mb-0">Weka taarifa za kipuri, uendano na pikipiki/bajaji, na hesabu ya faida ya 30% (Smart Pricing Engine).</p>
    </div>
    <a href="/index.php?route=products" class="btn btn-light border">
        <i class="bi bi-arrow-left me-1"></i> Rudi Kwenye Orodha
    </a>
</div>

<form action="/index.php?route=product_store" method="POST" id="productForm">
    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

    <div class="row g-4">
        <!-- Main Form Column (Left 8 cols) -->
        <div class="col-lg-8">

            <!-- SECTION 1: Basic Information -->
            <div class="premium-card mb-4">
                <div class="premium-card-header">
                    <h5><i class="bi bi-info-circle-fill text-primary"></i> 1. Taarifa za Msingi za Kipuri</h5>
                </div>
                <div class="premium-card-body p-4">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label fw-semibold small">Jina la Kipuri (Product Name) <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control form-control-lg" required placeholder="mf. Brake Shoe Rear (Kiatu cha Breki cha Nyuma)" autofocus>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Nambari ya Utambulisho (SKU)</label>
                            <div class="input-group">
                                <input type="text" name="sku" id="skuInput" class="form-control text-uppercase" placeholder="mf. BRK-000001 (Acha wazi ili kujiunda yenyewe)">
                                <button type="button" class="btn btn-light border" id="btnAutoSku" title="Zalisha SKU Kiotomatiki">
                                    <i class="bi bi-magic me-1"></i> Auto
                                </button>
                            </div>
                            <small class="text-muted" style="font-size:11px;">Acha wazi mfumo utazalisha SKU ya kipekee kulingana na kategoria.</small>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Barcode (Si lazima)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-upc"></i></span>
                                <input type="text" name="barcode" class="form-control" placeholder="mf. 8901425000123">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Eneo la Shelfu / Stoo (Shelf Location)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-geo-alt"></i></span>
                                <input type="text" name="shelf_location" class="form-control" placeholder="mf. Shelfu A-3, Droo 2">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Muda wa Waranti (Siku)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-shield-check"></i></span>
                                <input type="number" name="warranty_days" class="form-control" min="0" value="0" placeholder="0">
                                <span class="input-group-text bg-light small">Siku</span>
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold small">Maelezo ya Ziada (Description)</label>
                            <textarea name="description" class="form-control" rows="2" placeholder="Maelezo maalum kuhusu ubora, nyenzo, au maagizo ya matumizi..."></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SECTION 2: Classification (Category, Brand, Unit) -->
            <div class="premium-card mb-4">
                <div class="premium-card-header">
                    <h5><i class="bi bi-tags-fill text-teal"></i> 2. Uainishaji (Classification)</h5>
                </div>
                <div class="premium-card-body p-4">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">Kategoria <span class="text-danger">*</span></label>
                            <select name="category_id" id="categorySelect" class="form-select" required>
                                <option value="">-- Chagua Kategoria --</option>
                                <?php foreach ($categories as $c): ?>
                                    <option value="<?= $c['id'] ?>" data-code="<?= htmlspecialchars($c['code'] ?? '') ?>">
                                        <?= htmlspecialchars($c['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">Chapa ya Kipuri (Part Brand)</label>
                            <select name="brand_id" class="form-select">
                                <option value="">-- Chagua Brand (Si lazima) --</option>
                                <?php foreach ($brands as $b): ?>
                                    <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">Kipimo cha Hesabu (Unit) <span class="text-danger">*</span></label>
                            <select name="unit_id" class="form-select" required>
                                <option value="">-- Chagua Kipimo --</option>
                                <?php foreach ($units as $u): ?>
                                    <option value="<?= $u['id'] ?>" <?= $u['short_name'] === 'Pcs' ? 'selected' : '' ?>>
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
                        <small class="text-muted">Chagua miundo ya pikipiki au bajaji ambazo kipuri hiki kinafaa (Relational Many-to-Many).</small>
                    </div>
                    <span class="badge bg-primary-subtle text-primary rounded-pill px-3 py-1" id="selectedVehiclesCount">
                        0 Zimechaguliwa
                    </span>
                </div>
                <div class="premium-card-body p-3">
                    <div class="mb-3">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                            <input type="text" id="vehicleFilterInput" class="form-control" placeholder="Tafuta chombo kwa haraka (mf. Boxer 150, TVS King, Bajaj RE)...">
                        </div>
                    </div>

                    <div class="vehicle-picker-box border rounded p-2 bg-light" style="max-height: 280px; overflow-y: auto;">
                        <div class="row g-2" id="vehicleListContainer">
                            <?php foreach ($vehicleModels as $vm): ?>
                                <div class="col-md-6 vehicle-item-col" data-search="<?= strtolower(htmlspecialchars($vm['brand_name'] . ' ' . $vm['model_name'] . ' ' . $vm['engine_capacity'] . ' ' . $vm['vehicle_type'])) ?>">
                                    <div class="form-check p-2 bg-white rounded border border-light h-100">
                                        <input class="form-check-input vehicle-checkbox" type="checkbox" name="vehicle_models[]" value="<?= $vm['id'] ?>" id="vm_<?= $vm['id'] ?>">
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
                    <small class="text-muted mt-2 d-block">
                        <i class="bi bi-lightbulb me-1"></i>Ikiwa kipuri hiki kinafaa vyombo vingi (mf. Spark plug au Bearing), chagua vyote husika.
                    </small>
                </div>
            </div>

            <!-- SECTION 4: Stock Settings -->
            <div class="premium-card mb-4">
                <div class="premium-card-header">
                    <h5><i class="bi bi-box-fill text-teal"></i> 4. Mipangilio ya Stoo (Stock Settings)</h5>
                </div>
                <div class="premium-card-body p-4">
                    <div class="row g-3">
                        <div class="col-md-3 col-sm-6">
                            <label class="form-label fw-semibold small">Stoo ya Awali (Initial)</label>
                            <input type="number" name="current_stock" class="form-control" value="0" min="0">
                            <small class="text-muted" style="font-size:11px;">Mzigo uliopo stoo sasa.</small>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <label class="form-label fw-semibold small">Kiwango cha Chini (Min)</label>
                            <input type="number" name="min_stock_level" class="form-control" value="5" min="0">
                            <small class="text-muted" style="font-size:11px;">Tahadhari ya dharura.</small>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <label class="form-label fw-semibold small">Reorder Level (Agiza)</label>
                            <input type="number" name="reorder_level" class="form-control" value="10" min="0">
                            <small class="text-muted" style="font-size:11px;">Onyo la kuagiza mzigo.</small>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <label class="form-label fw-semibold small">Kiwango cha Juu (Max)</label>
                            <input type="number" name="max_stock_level" class="form-control" value="100" min="1">
                            <small class="text-muted" style="font-size:11px;">Mwisho wa kujaa stoo.</small>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- Right Side Column: Smart 30% Pricing & Actions (Right 4 cols) -->
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
                            <input type="number" step="100" name="buying_price" id="buyingPriceInput" class="form-control fw-bold fs-5 text-dark" required placeholder="10,000" min="0">
                        </div>
                        <small class="text-muted" style="font-size:11px;">Gharama ya kununua kipuri kutoka kwa supplier.</small>
                    </div>

                    <!-- Hidden target margin to preserve backend compatibility without showing percentages -->
                    <input type="hidden" name="target_margin" id="targetMarginInput" value="30.0">

                    <!-- Smart Recommended Price Output Box -->
                    <div class="p-3 bg-light rounded-3 border mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="small fw-semibold text-muted">Bei Elekezi:</span>
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill">Bei ya Rejareja</span>
                        </div>
                        <div class="fs-4 fw-bolder text-primary" id="recommendedPriceDisplay">TZS 0</div>
                        <input type="hidden" name="recommended_selling_price" id="recommendedPriceHidden" value="0">
                        <div class="small text-muted d-flex justify-content-between mt-1">
                            <span>Faida Tarajiwa:</span>
                            <strong class="text-success" id="expectedProfitDisplay">+TZS 0</strong>
                        </div>
                    </div>

                    <!-- Actual Selling Price Input -->
                    <div class="mb-3">
                        <label class="form-label fw-bold small d-flex justify-content-between">
                            <span>Bei Halisi ya Kuuza (Selling Price) <span class="text-danger">*</span></span>
                            <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none" id="btnUseRecommended">Tumia Bei Elekezi</button>
                        </label>
                        <div class="input-group input-group-lg">
                            <span class="input-group-text bg-light fw-bold text-muted">TZS</span>
                            <input type="number" step="100" name="selling_price" id="sellingPriceInput" class="form-control fw-bold fs-5 text-dark" required placeholder="14,300" min="0">
                        </div>
                    </div>

                    <!-- Live Realized Profit Status Badge -->
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center p-2 rounded border" id="liveMarginStatusBox" style="background:#f8f9fa;">
                            <span class="small fw-semibold">Kiasi cha Faida Halisi:</span>
                            <span class="badge bg-secondary" id="liveMarginBadge">+TZS 0</span>
                        </div>
                    </div>

                    <!-- Minimum Selling Price Input -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Bei ya Chini ya Mwisho (Minimum Price)</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light">TZS</span>
                            <input type="number" step="100" name="minimum_selling_price" id="minimumPriceInput" class="form-control" placeholder="Bei ya mwisho bila kupata hasara">
                        </div>
                        <small class="text-muted" style="font-size:11px;">Mwisho kabisa wa kutoa punguzo (Discount limit).</small>
                    </div>

                </div>
            </div>

            <!-- SECTION 6: Status & Save Action -->
            <div class="premium-card">
                <div class="premium-card-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Hali ya Kipuri (Status)</label>
                        <select name="status" class="form-select">
                            <option value="active" selected>Hai (Active - Tayari Kuuzwa)</option>
                            <option value="inactive">Imezimwa (Inactive)</option>
                        </select>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary-custom btn-lg shadow-sm">
                            <i class="bi bi-check2-circle me-1"></i> Hifadhi Kipuri
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

            if (!sellingInput.value || sellingInput.dataset.manual !== 'true') {
                sellingInput.value = recommended;
            }
            if (!minInput.value) {
                minInput.value = cost;
            }
        } else {
            recDisplay.textContent = 'TZS 0';
            recHidden.value = 0;
            profitDisplay.textContent = '+TZS 0';
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
    sellingInput.addEventListener('input', function() {
        sellingInput.dataset.manual = 'true';
        updateLiveMargin();
    });

    btnUseRec.addEventListener('click', function() {
        sellingInput.value = recHidden.value;
        sellingInput.dataset.manual = 'false';
        updateLiveMargin();
    });

    // Auto SKU Button
    document.getElementById('btnAutoSku').addEventListener('click', function() {
        const catSelect = document.getElementById('categorySelect');
        const selectedOpt = catSelect.options[catSelect.selectedIndex];
        let code = selectedOpt ? selectedOpt.getAttribute('data-code') : '';
        if (!code) code = 'PRD';
        const randomSeq = Math.floor(100000 + Math.random() * 900000);
        document.getElementById('skuInput').value = code.toUpperCase() + '-' + randomSeq;
    });

    // Vehicle Search Filter inside picker
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
        cb.addEventListener('change', updateVehicleCount);
    });
});
</script>
