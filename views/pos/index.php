<?php
use App\Core\Helper;
use App\Core\Auth;
?>

<div class="pos-wrapper">
    <!-- Top Alert / Flash Container -->
    <div id="posAlertContainer"></div>

    <div class="row g-3">
        <!-- ======================================================== -->
        <!-- LEFT COLUMN: Product Catalog, Filters & Search           -->
        <!-- ======================================================== -->
        <div class="col-xl-7 col-lg-6">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-header bg-white border-bottom p-3">
                    <div class="row g-2 align-items-center">
                        <!-- Barcode Scanner Input -->
                        <div class="col-md-5">
                            <div class="input-group">
                                <span class="input-group-text bg-light text-primary border-end-0">
                                    <i class="bi bi-upc-scan fs-5"></i>
                                </span>
                                <input type="text" id="posBarcodeInput" class="form-control border-start-0" 
                                       placeholder="Changanua / Weka Barcode (Bonyeza Enter)..." autofocus autocomplete="off">
                            </div>
                        </div>

                        <!-- Text Search Input -->
                        <div class="col-md-7">
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0">
                                    <i class="bi bi-search text-muted"></i>
                                </span>
                                <input type="text" id="posSearchInput" class="form-control border-start-0" 
                                       placeholder="Tafuta jina la kipuri, SKU, au rafu...">
                                <button class="btn btn-outline-secondary" type="button" id="posClearSearch" title="Futa utafutaji">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Category, Brand & Vehicle Filters -->
                    <div class="row g-2 mt-2">
                        <div class="col-md-4">
                            <select id="posFilterCategory" class="form-select form-select-sm">
                                <option value="">-- Kategoria Zote --</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <select id="posFilterBrand" class="form-select form-select-sm">
                                <option value="">-- Chapa (Brand) Zote --</option>
                                <?php foreach ($brands as $b): ?>
                                    <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <select id="posFilterVehicle" class="form-select form-select-sm">
                                <option value="">-- Chombo / Muundo --</option>
                                <?php foreach ($vehicleModels as $vm): ?>
                                    <option value="<?= $vm['id'] ?>">
                                        <?= htmlspecialchars($vm['brand_name'] . ' ' . $vm['model_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Products Grid / Results List -->
                <div class="card-body p-3 overflow-auto" style="max-height: calc(100vh - 280px); min-height: 480px;">
                    <div id="posLoadingSpinner" class="text-center py-5 d-none">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Inapakia...</span>
                        </div>
                        <p class="text-muted small mt-2">Inatafuta vipuri...</p>
                    </div>

                    <div id="posProductGrid" class="row g-3">
                        <!-- Loaded via JavaScript and initial seed -->
                        <?php foreach ($initialProducts as $prod): ?>
                            <?php 
                                $isOut = ((int)$prod['current_stock'] <= 0);
                                $isLow = (!$isOut && (int)$prod['current_stock'] <= (int)$prod['reorder_level']);
                            ?>
                            <div class="col-xxl-3 col-xl-4 col-md-6 col-sm-6">
                                <div class="card h-100 product-pos-card border <?= $isOut ? 'border-danger-subtle bg-light bg-opacity-50' : 'border-light shadow-sm' ?> rounded-3 p-2 d-flex flex-column justify-content-between"
                                     data-product='<?= htmlspecialchars(json_encode([
                                         'id' => (int)$prod['id'],
                                         'name' => $prod['name'],
                                         'sku' => $prod['sku'],
                                         'selling_price' => (float)$prod['selling_price'],
                                         'minimum_selling_price' => (float)($prod['minimum_selling_price'] ?? 0),
                                         'current_stock' => (int)$prod['current_stock'],
                                         'unit_short' => $prod['unit_short'] ?? 'Pcs',
                                         'brand_name' => $prod['brand_name'] ?? 'General'
                                     ]), ENT_QUOTES, 'UTF-8') ?>'>
                                    <div>
                                        <div class="d-flex justify-content-between align-items-start gap-1 mb-1">
                                            <span class="badge bg-light text-secondary border font-monospace" style="font-size: 10px;">
                                                <?= htmlspecialchars($prod['sku']) ?>
                                            </span>
                                            <?php if ($isOut): ?>
                                                <span class="badge bg-danger-subtle text-danger" style="font-size: 10px;">Imeisha Stoo</span>
                                            <?php elseif ($isLow): ?>
                                                <span class="badge bg-warning-subtle text-dark" style="font-size: 10px;">Stoo: <?= $prod['current_stock'] ?></span>
                                            <?php else: ?>
                                                <span class="badge bg-success-subtle text-success" style="font-size: 10px;">Stoo: <?= $prod['current_stock'] ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="d-flex gap-2 align-items-center mb-2">
                                            <div class="product-pos-thumb rounded border bg-light d-flex align-items-center justify-content-center overflow-hidden flex-shrink-0" style="width:48px; height:48px;">
                                                <img src="<?= htmlspecialchars($prod['image_url'] ?: '/assets/images/placeholder_product.svg') ?>" 
                                                     alt="<?= htmlspecialchars($prod['name']) ?>" 
                                                     class="img-fluid" 
                                                     style="max-width:100%; max-height:100%; object-fit:contain;"
                                                     onerror="this.src='/assets/images/placeholder_product.svg';">
                                            </div>
                                            <div class="overflow-hidden flex-grow-1">
                                                <h6 class="fw-bold text-dark mb-0 product-title text-truncate" style="font-size: 13px;" title="<?= htmlspecialchars($prod['name']) ?>">
                                                    <?= htmlspecialchars($prod['name']) ?>
                                                </h6>
                                                <div class="text-muted small text-truncate" style="font-size: 11px;">
                                                    <?= htmlspecialchars($prod['brand_name'] ?? 'General') ?>
                                                    <?php if (!empty($prod['shelf_location'])): ?>
                                                        &bull; <i class="bi bi-geo-alt"></i> <?= htmlspecialchars($prod['shelf_location']) ?>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center justify-content-between pt-2 border-top">
                                        <span class="fw-bold text-primary font-monospace" style="font-size: 14px;">
                                            <?= Helper::formatCurrency((float)$prod['selling_price']) ?>
                                        </span>
                                        <button type="button" class="btn btn-sm <?= $isOut ? 'btn-outline-secondary disabled' : 'btn-primary-custom' ?> px-2 py-1 btn-add-to-cart"
                                                <?= $isOut ? 'disabled' : '' ?>>
                                            <i class="bi bi-cart-plus me-1"></i> Weka
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- ======================================================== -->
        <!-- RIGHT COLUMN: Cart, Customer Selection & Checkout        -->
        <!-- ======================================================== -->
        <div class="col-xl-5 col-lg-6">
            <div class="card border-0 shadow-sm rounded-3 h-100 d-flex flex-column justify-content-between">
                <!-- Cart Header & Customer Selector -->
                <div class="card-header bg-white border-bottom p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <h6 class="fw-bold mb-0 text-dark">
                            <i class="bi bi-cart3 text-primary me-2"></i>Kapu la Mauzo (<span id="cartCountBadge">0</span>)
                        </h6>
                        <button type="button" class="btn btn-outline-danger btn-sm py-0 px-2" id="btnClearCart" title="Futa Kapu Lote">
                            <i class="bi bi-trash"></i> Safisha
                        </button>
                    </div>

                    <!-- Customer Selection Box -->
                    <div class="p-2 bg-light rounded border">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <label class="form-label small fw-semibold text-muted mb-0">Mteja (Customer):</label>
                            <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none small text-primary fw-semibold" data-bs-toggle="modal" data-bs-target="#quickCustomerModal">
                                <i class="bi bi-person-plus"></i> + Mteja Mpya
                            </button>
                        </div>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white border-end-0"><i class="bi bi-person text-muted"></i></span>
                            <input type="text" id="customerSearchInput" class="form-control border-start-0" 
                                   placeholder="Andika jina, simu, au kodi ya mteja..." autocomplete="off">
                            <button class="btn btn-outline-secondary" type="button" id="btnResetCustomer" title="Weka Walk-in">
                                Walk-in
                            </button>
                        </div>
                        <!-- Customer Dropdown Search Results -->
                        <div id="customerSearchResults" class="list-group shadow-sm position-absolute mt-1 d-none" 
                             style="z-index: 1050; width: 380px; max-height: 220px; overflow-y: auto;"></div>

                        <!-- Selected Customer Info Pill -->
                        <div id="selectedCustomerBadge" class="mt-2 d-flex align-items-center justify-content-between bg-white px-2 py-1 rounded border small">
                            <div>
                                <span class="fw-semibold text-dark" id="dispCustomerName">Mteja wa Kawaida (Walk-in)</span>
                                <span class="text-muted ms-1" id="dispCustomerPhone"></span>
                                <span class="badge bg-secondary-subtle text-secondary font-monospace ms-1" id="dispCustomerCode">WALK-IN</span>
                            </div>
                            <span class="text-success small fw-semibold" id="dispCreditLimit"></span>
                        </div>
                    </div>
                </div>

                <!-- Cart Items Table -->
                <div class="card-body p-0 overflow-auto" style="max-height: 280px; min-height: 180px;">
                    <table class="table table-hover align-middle mb-0" id="cartTable">
                        <thead class="table-light sticky-top small text-muted">
                            <tr>
                                <th class="ps-3" style="width: 42%;">Kipuri</th>
                                <th class="text-center" style="width: 25%;">Idadi</th>
                                <th class="text-end" style="width: 23%;">Jumla</th>
                                <th class="text-center" style="width: 10%;"></th>
                            </tr>
                        </thead>
                        <tbody id="cartTableBody">
                            <!-- Populated dynamically via JS -->
                            <tr id="emptyCartRow">
                                <td colspan="4" class="text-center py-5 text-muted">
                                    <i class="bi bi-cart-x display-6 d-block text-secondary mb-2"></i>
                                    Kapu ni tupu. Chagua vipuri upande wa kushoto au scan barcode.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Cart Footer & Checkout Panel -->
                <div class="card-footer bg-white border-top p-3">
                    <!-- Totals Breakdown -->
                    <div class="d-flex justify-content-between align-items-center mb-1 small">
                        <span class="text-muted">Jumla Ndogo (Subtotal):</span>
                        <span class="fw-semibold font-monospace" id="cartSubtotalText">TSh 0.00</span>
                    </div>

                    <?php if ($canDiscount): ?>
                        <div class="d-flex justify-content-between align-items-center mb-1 small">
                            <span class="text-muted">Punguzo la Ankara (Discount):</span>
                            <div class="input-group input-group-sm" style="width: 130px;">
                                <span class="input-group-text bg-light font-monospace py-0" style="font-size: 11px;">TSh</span>
                                <input type="number" step="500" min="0" id="overallDiscountInput" class="form-control text-end py-0" value="0">
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="d-flex justify-content-between align-items-center py-2 border-top border-bottom my-2">
                        <span class="fw-bold text-dark fs-5">JUMLA KUU:</span>
                        <span class="fw-bold text-primary fs-4 font-monospace" id="cartGrandTotalText">TSh 0.00</span>
                    </div>

                    <!-- Payment Method Selection -->
                    <div class="mb-2">
                        <label class="form-label small fw-semibold text-muted mb-1">Njia ya Malipo (Payment Method):</label>
                        <div class="btn-group w-100 btn-group-sm" role="group" id="paymentMethodGroup">
                            <input type="radio" class="btn-check" name="payment_method" id="payCash" value="Cash" checked>
                            <label class="btn btn-outline-primary" for="payCash"><i class="bi bi-cash me-1"></i>Cash</label>

                            <input type="radio" class="btn-check" name="payment_method" id="payMpesa" value="M-Pesa">
                            <label class="btn btn-outline-primary" for="payMpesa">M-Pesa</label>

                            <input type="radio" class="btn-check" name="payment_method" id="payTigo" value="Tigo Pesa">
                            <label class="btn btn-outline-primary" for="payTigo">Tigo</label>

                            <input type="radio" class="btn-check" name="payment_method" id="payAirtel" value="Airtel Money">
                            <label class="btn btn-outline-primary" for="payAirtel">Airtel</label>

                            <input type="radio" class="btn-check" name="payment_method" id="payCredit" value="Credit">
                            <label class="btn btn-outline-warning" for="payCredit"><i class="bi bi-wallet2 me-1"></i>Mkopo</label>
                        </div>
                    </div>

                    <!-- Amount Paid & Change Row -->
                    <div class="row g-2 align-items-center mb-2">
                        <div class="col-6">
                            <label class="form-label small fw-semibold text-muted mb-1">Pesa Iliyopokelewa:</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light font-monospace py-0" style="font-size: 11px;">TSh</span>
                                <input type="number" step="500" min="0" id="amountPaidInput" class="form-control text-end fw-bold py-0" value="0">
                            </div>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold text-muted mb-1" id="lblChangeOrBalance">Chenji (Change):</label>
                            <div class="p-1 px-2 rounded bg-light border text-end fw-bold font-monospace text-success fs-6" id="dispChangeOrBalance">
                                TSh 0.00
                            </div>
                        </div>
                    </div>

                    <!-- Quick Cash Preset Buttons -->
                    <div class="d-flex gap-1 mb-3">
                        <button type="button" class="btn btn-light btn-sm border py-0 px-2 font-monospace btn-quick-cash" data-mode="exact">Exact</button>
                        <button type="button" class="btn btn-light btn-sm border py-0 px-2 font-monospace btn-quick-cash" data-cash="10000">10k</button>
                        <button type="button" class="btn btn-light btn-sm border py-0 px-2 font-monospace btn-quick-cash" data-cash="20000">20k</button>
                        <button type="button" class="btn btn-light btn-sm border py-0 px-2 font-monospace btn-quick-cash" data-cash="50000">50k</button>
                        <button type="button" class="btn btn-light btn-sm border py-0 px-2 font-monospace btn-quick-cash" data-cash="100000">100k</button>
                    </div>

                    <!-- Reference Number (for Mobile Money / Bank) -->
                    <div class="mb-3 d-none" id="referenceNoContainer">
                        <label class="form-label small fw-semibold text-muted mb-1">Nambari ya Muamala (Reference No / SMS Code):</label>
                        <input type="text" id="referenceNoInput" class="form-control form-control-sm" placeholder="Mf. 9K728X..." autocomplete="off">
                    </div>

                    <!-- Complete Sale Submit Button -->
                    <button type="button" class="btn btn-primary-custom w-100 py-2 fs-6 fw-bold shadow-sm d-flex align-items-center justify-content-center gap-2" id="btnCompleteSale">
                        <i class="bi bi-check2-circle fs-5"></i>
                        <span>KAMILISHA MAUZO (F9)</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ======================================================== -->
<!-- MODAL: Quick Customer Registration                      -->
<!-- ======================================================== -->
<div class="modal fade" id="quickCustomerModal" tabindex="-1" aria-labelledby="quickCustomerModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="quickCustomerModalLabel">
                    <i class="bi bi-person-plus text-primary me-2"></i>Sajili Mteja Mpya kwa Haraka
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="quickCustomerForm">
                <input type="hidden" name="csrf_token" value="<?= Helper::generateCsrf() ?>">
                <div class="modal-body">
                    <div id="quickCustomerError" class="alert alert-danger py-2 small d-none"></div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-muted">Jina Kamili la Mteja <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required placeholder="Mf. Hamis Ally, Gereji ya Golani">
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-muted">Nambari ya Simu <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="bi bi-telephone text-muted"></i></span>
                            <input type="text" name="phone" class="form-control" required placeholder="07XXXXXXXX">
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold text-muted">Aina ya Mteja</label>
                            <select name="customer_type" class="form-select">
                                <option value="individual">Mteja wa Kawaida</option>
                                <option value="mechanic">Fundi (Mechanic)</option>
                                <option value="garage">Gereji (Garage)</option>
                                <option value="business">Kampuni / Biashara</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold text-muted">Eneo / Mtaa</label>
                            <input type="text" name="area" class="form-control" placeholder="Mf. Kariakoo">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border btn-sm" data-bs-dismiss="modal">Ghairi</button>
                    <button type="submit" class="btn btn-primary-custom btn-sm" id="btnSubmitQuickCustomer">
                        <i class="bi bi-check-lg me-1"></i> Hifadhi Mteja
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ======================================================== -->
<!-- MODAL: Post-Sale Thermal Receipt Preview                 -->
<!-- ======================================================== -->
<div class="modal fade" id="saleSuccessModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="saleSuccessModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header bg-success text-white py-2">
                <h6 class="modal-title fw-bold" id="saleSuccessModalLabel">
                    <i class="bi bi-check-circle-fill me-1"></i> Mauzo Yamekamilika!
                </h6>
            </div>
            <div class="modal-body text-center p-4">
                <div class="text-success mb-2">
                    <i class="bi bi-receipt display-4"></i>
                </div>
                <h5 class="fw-bold text-dark mb-1" id="postSaleInvoiceNo">INV-000000</h5>
                <p class="text-muted small mb-3">Jumla: <strong class="text-primary font-monospace" id="postSaleTotal">TSh 0.00</strong></p>

                <div class="d-grid gap-2">
                    <a href="#" target="_blank" id="btnReceiptPrint" class="btn btn-primary-custom">
                        <i class="bi bi-printer me-1"></i> Chapisha Risiti (Thermal)
                    </a>
                    <a href="#" target="_blank" id="btnInvoicePrint" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-file-earmark-pdf me-1"></i> Ankara Rasmi (A4 Invoice)
                    </a>
                    <button type="button" class="btn btn-light border btn-sm mt-2" data-bs-dismiss="modal" id="btnNewSale">
                        <i class="bi bi-plus-lg me-1"></i> Mauzo Mapya (New Sale)
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
/**
 * POS Client-Side Engine
 * Fast cart management, server-side validation synchronization,
 * barcode scanning, and audio feedback.
 */
document.addEventListener('DOMContentLoaded', function () {
    const csrfToken = '<?= Helper::generateCsrf() ?>';
    const canDiscount = <?= $canDiscount ? 'true' : 'false' ?>;
    const canSellBelowMin = <?= $canSellBelowMin ? 'true' : 'false' ?>;

    let cart = [];
    let selectedCustomer = null; // null => Walk-in

    const barcodeInput = document.getElementById('posBarcodeInput');
    const searchInput = document.getElementById('posSearchInput');
    const clearSearchBtn = document.getElementById('posClearSearch');
    const filterCat = document.getElementById('posFilterCategory');
    const filterBrand = document.getElementById('posFilterBrand');
    const filterVehicle = document.getElementById('posFilterVehicle');
    const productGrid = document.getElementById('posProductGrid');
    const loadingSpinner = document.getElementById('posLoadingSpinner');

    const cartTableBody = document.getElementById('cartTableBody');
    const cartCountBadge = document.getElementById('cartCountBadge');
    const cartSubtotalText = document.getElementById('cartSubtotalText');
    const cartGrandTotalText = document.getElementById('cartGrandTotalText');
    const overallDiscountInput = document.getElementById('overallDiscountInput');
    const amountPaidInput = document.getElementById('amountPaidInput');
    const dispChangeOrBalance = document.getElementById('dispChangeOrBalance');
    const lblChangeOrBalance = document.getElementById('lblChangeOrBalance');
    const btnCompleteSale = document.getElementById('btnCompleteSale');
    const btnClearCart = document.getElementById('btnClearCart');
    const referenceNoContainer = document.getElementById('referenceNoContainer');
    const referenceNoInput = document.getElementById('referenceNoInput');

    const customerSearchInput = document.getElementById('customerSearchInput');
    const customerSearchResults = document.getElementById('customerSearchResults');
    const btnResetCustomer = document.getElementById('btnResetCustomer');
    const dispCustomerName = document.getElementById('dispCustomerName');
    const dispCustomerPhone = document.getElementById('dispCustomerPhone');
    const dispCustomerCode = document.getElementById('dispCustomerCode');
    const dispCreditLimit = document.getElementById('dispCreditLimit');

    // Format money helper
    function fmtMoney(num) {
        return 'TSh ' + Number(num).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    // 1. ADD PRODUCT TO CART
    function addToCart(product) {
        const existing = cart.find(item => item.product_id === product.id);
        if (existing) {
            if (existing.quantity + 1 > product.current_stock) {
                showAlert(`Stoo haitoshi kwa '${product.name}'. Kilichopo: ${product.current_stock}.`, 'warning');
                return;
            }
            existing.quantity += 1;
        } else {
            if (product.current_stock < 1) {
                showAlert(`Kipuri '${product.name}' hakipo stoo kwa sasa.`, 'danger');
                return;
            }
            cart.push({
                product_id: product.id,
                name: product.name,
                sku: product.sku,
                unit_price: Number(product.selling_price),
                minimum_selling_price: Number(product.minimum_selling_price || 0),
                discount: 0,
                quantity: 1,
                current_stock: product.current_stock,
                unit_short: product.unit_short || 'Pcs'
            });
        }
        renderCart();
    }

    // 2. RENDER CART TABLE
    function renderCart() {
        if (cart.length === 0) {
            cartTableBody.innerHTML = `
                <tr id="emptyCartRow">
                    <td colspan="4" class="text-center py-5 text-muted">
                        <i class="bi bi-cart-x display-6 d-block text-secondary mb-2"></i>
                        Kapu ni tupu. Chagua vipuri upande wa kushoto au scan barcode.
                    </td>
                </tr>
            `;
            cartCountBadge.textContent = '0';
            updateTotals();
            return;
        }

        let html = '';
        let totalItems = 0;

        cart.forEach((item, index) => {
            totalItems += item.quantity;
            const itemSubtotal = (item.unit_price - item.discount) * item.quantity;

            html += `
                <tr data-index="${index}">
                    <td class="ps-3">
                        <div class="fw-bold text-dark" style="font-size: 13px; line-height: 1.2;">
                            ${escapeHtml(item.name)}
                        </div>
                        <div class="small text-muted font-monospace" style="font-size: 11px;">
                            ${item.sku} &bull; ${fmtMoney(item.unit_price)}
                        </div>
                    </td>
                    <td class="text-center">
                        <div class="input-group input-group-sm justify-content-center" style="max-width: 110px; margin: auto;">
                            <button class="btn btn-outline-secondary py-0 px-2 btn-qty-minus" type="button">-</button>
                            <input type="number" min="1" max="${item.current_stock}" class="form-control text-center py-0 px-1 font-monospace input-qty" value="${item.quantity}">
                            <button class="btn btn-outline-secondary py-0 px-2 btn-qty-plus" type="button">+</button>
                        </div>
                    </td>
                    <td class="text-end fw-bold font-monospace pe-2" style="font-size: 13px;">
                        ${fmtMoney(itemSubtotal)}
                    </td>
                    <td class="text-center">
                        <button type="button" class="btn btn-link text-danger p-0 btn-remove-item" title="Ondoa">
                            <i class="bi bi-x-circle"></i>
                        </button>
                    </td>
                </tr>
            `;
        });

        cartTableBody.innerHTML = html;
        cartCountBadge.textContent = totalItems.toString();
        updateTotals();
    }

    // 3. UPDATE TOTALS AND CHANGE/BALANCE DUE
    function updateTotals() {
        let subtotal = 0;
        cart.forEach(item => {
            subtotal += (item.unit_price - item.discount) * item.quantity;
        });

        const overallDiscount = overallDiscountInput ? Number(overallDiscountInput.value || 0) : 0;
        const grandTotal = Math.max(0, subtotal - overallDiscount);

        cartSubtotalText.textContent = fmtMoney(subtotal);
        cartGrandTotalText.textContent = fmtMoney(grandTotal);

        const activePaymentMethod = document.querySelector('input[name="payment_method"]:checked')?.value || 'Cash';

        // If amount paid is 0 and total is > 0 and method is Cash, auto-set exact
        let paid = Number(amountPaidInput.value || 0);

        if (activePaymentMethod === 'Credit') {
            lblChangeOrBalance.textContent = 'Deni la Mkopo (Balance Due):';
            lblChangeOrBalance.className = 'form-label small fw-semibold text-warning mb-1';
            dispChangeOrBalance.textContent = fmtMoney(grandTotal);
            dispChangeOrBalance.className = 'p-1 px-2 rounded bg-warning-subtle border border-warning text-end fw-bold font-monospace text-dark fs-6';
            amountPaidInput.value = 0;
            return;
        }

        if (paid >= grandTotal) {
            const change = paid - grandTotal;
            lblChangeOrBalance.textContent = 'Chenji (Change):';
            lblChangeOrBalance.className = 'form-label small fw-semibold text-success mb-1';
            dispChangeOrBalance.textContent = fmtMoney(change);
            dispChangeOrBalance.className = 'p-1 px-2 rounded bg-success-subtle border border-success text-end fw-bold font-monospace text-success fs-6';
        } else {
            const balanceDue = grandTotal - paid;
            lblChangeOrBalance.textContent = 'Salio Linalodaiwa (Balance Due):';
            lblChangeOrBalance.className = 'form-label small fw-semibold text-danger mb-1';
            dispChangeOrBalance.textContent = fmtMoney(balanceDue);
            dispChangeOrBalance.className = 'p-1 px-2 rounded bg-danger-subtle border border-danger text-end fw-bold font-monospace text-danger fs-6';
        }
    }

    // 4. EVENT LISTENERS FOR CART TABLE (DELEGATION)
    cartTableBody.addEventListener('click', function (e) {
        const tr = e.target.closest('tr');
        if (!tr) return;
        const index = parseInt(tr.dataset.index);

        if (e.target.closest('.btn-qty-plus')) {
            if (cart[index].quantity + 1 > cart[index].current_stock) {
                showAlert(`Stoo haitoshi kwa '${cart[index].name}'. Kilichopo: ${cart[index].current_stock}.`, 'warning');
                return;
            }
            cart[index].quantity += 1;
            renderCart();
        } else if (e.target.closest('.btn-qty-minus')) {
            if (cart[index].quantity > 1) {
                cart[index].quantity -= 1;
                renderCart();
            } else {
                cart.splice(index, 1);
                renderCart();
            }
        } else if (e.target.closest('.btn-remove-item')) {
            cart.splice(index, 1);
            renderCart();
        }
    });

    cartTableBody.addEventListener('change', function (e) {
        if (e.target.classList.contains('input-qty')) {
            const tr = e.target.closest('tr');
            const index = parseInt(tr.dataset.index);
            let val = parseInt(e.target.value);
            if (isNaN(val) || val < 1) val = 1;
            if (val > cart[index].current_stock) {
                showAlert(`Stoo haitoshi. Kiwango kimerejeshwa kuwa: ${cart[index].current_stock}.`, 'warning');
                val = cart[index].current_stock;
            }
            cart[index].quantity = val;
            renderCart();
        }
    });

    // 5. PRODUCT SEARCH AND FILTERS (AJAX)
    let debounceTimer = null;
    function fetchProducts() {
        loadingSpinner.classList.remove('d-none');
        productGrid.innerHTML = '';

        const params = new URLSearchParams({
            route: 'pos_search_products',
            q: searchInput.value.trim(),
            category_id: filterCat.value,
            brand_id: filterBrand.value,
            vehicle_model_id: filterVehicle.value,
            limit: 24
        });

        fetch(`/index.php?${params.toString()}`)
            .then(res => res.json())
            .then(data => {
                loadingSpinner.classList.add('d-none');
                if (!data.success || data.items.length === 0) {
                    productGrid.innerHTML = `
                        <div class="col-12 text-center py-5 text-muted">
                            <i class="bi bi-search display-6 d-block text-secondary mb-2"></i>
                            Hakuna vipuri vilivyopatikana kwa vigezo hivi.
                        </div>
                    `;
                    return;
                }

                let cardsHtml = '';
                data.items.forEach(prod => {
                    const isOut = prod.is_out_of_stock;
                    cardsHtml += `
                        <div class="col-xxl-3 col-xl-4 col-md-6 col-sm-6">
                            <div class="card h-100 product-pos-card border ${isOut ? 'border-danger-subtle bg-light bg-opacity-50' : 'border-light shadow-sm'} rounded-3 p-2 d-flex flex-column justify-content-between"
                                 data-product='${escapeHtml(JSON.stringify(prod))}'>
                                <div>
                                    <div class="d-flex justify-content-between align-items-start gap-1 mb-1">
                                        <span class="badge bg-light text-secondary border font-monospace" style="font-size: 10px;">
                                            ${prod.sku}
                                        </span>
                                        <span class="badge ${isOut ? 'bg-danger-subtle text-danger' : 'bg-success-subtle text-success'}" style="font-size: 10px;">
                                            ${isOut ? 'Imeisha' : 'Stoo: ' + prod.current_stock}
                                        </span>
                                    </div>
                                    <div class="d-flex gap-2 align-items-center mb-2">
                                        <div class="product-pos-thumb rounded border bg-light d-flex align-items-center justify-content-center overflow-hidden flex-shrink-0" style="width:48px; height:48px;">
                                            <img src="${escapeHtml(prod.image_url || '/assets/images/placeholder_product.svg')}" 
                                                 alt="${escapeHtml(prod.name)}" 
                                                 class="img-fluid" 
                                                 style="max-width:100%; max-height:100%; object-fit:contain;"
                                                 onerror="this.src='/assets/images/placeholder_product.svg';">
                                        </div>
                                        <div class="overflow-hidden flex-grow-1">
                                            <h6 class="fw-bold text-dark mb-0 product-title text-truncate" style="font-size: 13px;" title="${escapeHtml(prod.name)}">
                                                ${escapeHtml(prod.name)}
                                            </h6>
                                            <div class="text-muted small text-truncate" style="font-size: 11px;">
                                                ${escapeHtml(prod.brand_name)} ${prod.shelf_location ? '&bull; ' + escapeHtml(prod.shelf_location) : ''}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center justify-content-between pt-2 border-top">
                                    <span class="fw-bold text-primary font-monospace" style="font-size: 14px;">
                                        ${prod.formatted_price}
                                    </span>
                                    <button type="button" class="btn btn-sm ${isOut ? 'btn-outline-secondary disabled' : 'btn-primary-custom'} px-2 py-1 btn-add-to-cart"
                                            ${isOut ? 'disabled' : ''}>
                                        <i class="bi bi-cart-plus me-1"></i> Weka
                                    </button>
                                </div>
                            </div>
                        </div>
                    `;
                });
                productGrid.innerHTML = cardsHtml;
            })
            .catch(err => {
                loadingSpinner.classList.add('d-none');
                showAlert("Hitilafu katika kutafuta vipuri: " + err.message, "danger");
            });
    }

    searchInput.addEventListener('input', function () {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(fetchProducts, 300);
    });

    clearSearchBtn.addEventListener('click', function () {
        searchInput.value = '';
        fetchProducts();
    });

    filterCat.addEventListener('change', fetchProducts);
    filterBrand.addEventListener('change', fetchProducts);
    filterVehicle.addEventListener('change', fetchProducts);

    // 6. ADD BUTTON CLICKS ON PRODUCT CARDS
    productGrid.addEventListener('click', function (e) {
        const btn = e.target.closest('.btn-add-to-cart');
        if (!btn || btn.disabled) return;
        const card = btn.closest('.product-pos-card');
        if (!card) return;
        const product = JSON.parse(card.dataset.product);
        addToCart(product);
    });

    // 7. BARCODE LOOKUP ON ENTER
    barcodeInput.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            const code = barcodeInput.value.trim();
            if (!code) return;

            fetch(`/index.php?route=pos_barcode_lookup&barcode=${encodeURIComponent(code)}`)
                .then(res => res.json())
                .then(data => {
                    if (data.success && data.item) {
                        addToCart(data.item);
                        barcodeInput.value = '';
                    } else {
                        showAlert(data.message || "Kipuri hakikupatikana kwa barcode hii.", "danger");
                    }
                })
                .catch(err => {
                    showAlert("Hitilafu ya mtandao katika barcode: " + err.message, "danger");
                });
        }
    });

    // 8. CUSTOMER SEARCH AUTOCOMPLETE
    let custDebounce = null;
    customerSearchInput.addEventListener('input', function () {
        clearTimeout(custDebounce);
        const q = customerSearchInput.value.trim();
        if (q.length < 2) {
            customerSearchResults.classList.add('d-none');
            return;
        }

        custDebounce = setTimeout(() => {
            fetch(`/index.php?route=customer_ajax_search&q=${encodeURIComponent(q)}`)
                .then(res => res.json())
                .then(data => {
                    if (!Array.isArray(data) || data.length === 0) {
                        customerSearchResults.innerHTML = '<div class="list-group-item small text-muted">Hakuna mteja aliyepatikana</div>';
                        customerSearchResults.classList.remove('d-none');
                        return;
                    }

                    let itemsHtml = '';
                    data.forEach(c => {
                        itemsHtml += `
                            <button type="button" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-2 btn-select-cust"
                                    data-customer='${escapeHtml(JSON.stringify(c))}'>
                                <div>
                                    <div class="fw-bold small text-dark">${escapeHtml(c.name)}</div>
                                    <small class="text-muted">${c.phone || ''} &bull; ${c.customer_code || ''}</small>
                                </div>
                                <span class="badge bg-light text-primary border">${c.customer_type || 'Mteja'}</span>
                            </button>
                        `;
                    });
                    customerSearchResults.innerHTML = itemsHtml;
                    customerSearchResults.classList.remove('d-none');
                });
        }, 250);
    });

    customerSearchResults.addEventListener('click', function (e) {
        const btn = e.target.closest('.btn-select-cust');
        if (!btn) return;
        selectedCustomer = JSON.parse(btn.dataset.customer);
        customerSearchResults.classList.add('d-none');
        customerSearchInput.value = '';
        updateCustomerBadge();
    });

    btnResetCustomer.addEventListener('click', function () {
        selectedCustomer = null;
        updateCustomerBadge();
    });

    function updateCustomerBadge() {
        if (selectedCustomer) {
            dispCustomerName.textContent = selectedCustomer.name;
            dispCustomerPhone.textContent = selectedCustomer.phone ? `(${selectedCustomer.phone})` : '';
            dispCustomerCode.textContent = selectedCustomer.customer_code || 'CUS';
            dispCreditLimit.textContent = selectedCustomer.credit_limit ? `Limit: ${fmtMoney(selectedCustomer.credit_limit)}` : '';
        } else {
            dispCustomerName.textContent = 'Mteja wa Kawaida (Walk-in)';
            dispCustomerPhone.textContent = '';
            dispCustomerCode.textContent = 'WALK-IN';
            dispCreditLimit.textContent = '';
        }
    }

    // 9. QUICK CASH PRESET BUTTONS
    document.querySelectorAll('.btn-quick-cash').forEach(btn => {
        btn.addEventListener('click', function () {
            if (this.dataset.mode === 'exact') {
                let subtotal = 0;
                cart.forEach(item => subtotal += (item.unit_price - item.discount) * item.quantity);
                const discount = overallDiscountInput ? Number(overallDiscountInput.value || 0) : 0;
                amountPaidInput.value = Math.max(0, subtotal - discount);
            } else {
                amountPaidInput.value = Number(this.dataset.cash);
            }
            updateTotals();
        });
    });

    amountPaidInput.addEventListener('input', updateTotals);
    if (overallDiscountInput) {
        overallDiscountInput.addEventListener('input', updateTotals);
    }

    // 10. PAYMENT METHOD CHANGE
    document.querySelectorAll('input[name="payment_method"]').forEach(radio => {
        radio.addEventListener('change', function () {
            if (this.value === 'Credit') {
                if (!selectedCustomer) {
                    showAlert("Mauzo ya mkopo yanahitaji kumchagua mteja aliyesajiliwa kwanza.", "warning");
                }
                amountPaidInput.value = 0;
                referenceNoContainer.classList.add('d-none');
            } else if (this.value === 'Cash') {
                referenceNoContainer.classList.add('d-none');
            } else {
                // Mobile money or Bank
                referenceNoContainer.classList.remove('d-none');
            }
            updateTotals();
        });
    });

    // 11. QUICK CUSTOMER SUBMISSION
    const quickCustomerForm = document.getElementById('quickCustomerForm');
    const quickCustomerModal = new bootstrap.Modal(document.getElementById('quickCustomerModal'));
    quickCustomerForm.addEventListener('submit', function (e) {
        e.preventDefault();
        const formData = new FormData(quickCustomerForm);

        fetch('/index.php?route=pos_quick_customer', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success && data.customer) {
                selectedCustomer = data.customer;
                updateCustomerBadge();
                quickCustomerModal.hide();
                quickCustomerForm.reset();
                showAlert(data.message, 'success');
            } else {
                const errDiv = document.getElementById('quickCustomerError');
                errDiv.textContent = data.message || 'Hitilafu ya usajili wa mteja.';
                errDiv.classList.remove('d-none');
            }
        })
        .catch(err => {
            showAlert("Hitilafu: " + err.message, "danger");
        });
    });

    // 12. COMPLETE SALE (CHECKOUT)
    btnCompleteSale.addEventListener('click', completeSale);

    // Keyboard shortcut: F9 or Ctrl+Enter for checkout
    document.addEventListener('keydown', function (e) {
        if (e.key === 'F9' || (e.ctrlKey && e.key === 'Enter')) {
            e.preventDefault();
            completeSale();
        }
    });

    function completeSale() {
        if (cart.length === 0) {
            showAlert("Kapu la mauzo halina bidhaa.", "warning");
            return;
        }

        const activePaymentMethod = document.querySelector('input[name="payment_method"]:checked')?.value || 'Cash';
        if (activePaymentMethod === 'Credit' && !selectedCustomer) {
            showAlert("Huwezi kutoa mkopo kwa mteja wa Walk-in. Tafadhali chagua au sajili mteja kwanza.", "danger");
            return;
        }

        btnCompleteSale.disabled = true;
        btnCompleteSale.innerHTML = `<span class="spinner-border spinner-border-sm me-2"></span>Inachakata mauzo...`;

        const payload = {
            csrf_token: csrfToken,
            customer_id: selectedCustomer ? selectedCustomer.id : null,
            customer_name: selectedCustomer ? selectedCustomer.name : 'Mteja wa Kawaida (Walk-in)',
            customer_phone: selectedCustomer ? selectedCustomer.phone : '',
            payment_method: activePaymentMethod,
            amount_paid: Number(amountPaidInput.value || 0),
            overall_discount: overallDiscountInput ? Number(overallDiscountInput.value || 0) : 0,
            reference_no: referenceNoInput.value.trim(),
            cart: cart.map(i => ({
                product_id: i.product_id,
                quantity: i.quantity,
                unit_price: i.unit_price,
                discount: i.discount
            }))
        };

        fetch('/index.php?route=pos_checkout', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(payload)
        })
        .then(res => res.json())
        .then(data => {
            btnCompleteSale.disabled = false;
            btnCompleteSale.innerHTML = `<i class="bi bi-check2-circle fs-5"></i><span>KAMILISHA MAUZO (F9)</span>`;

            if (data.success && data.sale) {
                // Clear cart
                cart = [];
                renderCart();

                // Show Success Modal
                document.getElementById('postSaleInvoiceNo').textContent = data.sale.invoice_no;
                document.getElementById('postSaleTotal').textContent = fmtMoney(data.sale.total_amount);
                document.getElementById('btnReceiptPrint').href = `/index.php?route=sale_receipt&id=${data.sale.sale_id}`;
                document.getElementById('btnInvoicePrint').href = `/index.php?route=sale_invoice&id=${data.sale.sale_id}`;

                const modal = new bootstrap.Modal(document.getElementById('saleSuccessModal'));
                modal.show();

            } else {
                showAlert(data.message || "Mauzo hayakufanikiwa.", "danger");
            }
        })
        .catch(err => {
            btnCompleteSale.disabled = false;
            btnCompleteSale.innerHTML = `<i class="bi bi-check2-circle fs-5"></i><span>KAMILISHA MAUZO (F9)</span>`;
            showAlert("Hitilafu katika mtandao: " + err.message, "danger");
        });
    }

    // Clear cart button
    btnClearCart.addEventListener('click', function () {
        if (cart.length > 0 && confirm("Je, una uhakika unataka kusafisha kapu lote la mauzo?")) {
            cart = [];
            renderCart();
        }
    });

    // Helper alerts
    function showAlert(msg, type = 'info') {
        const container = document.getElementById('posAlertContainer');
        container.innerHTML = `
            <div class="alert alert-${type} alert-dismissible fade show shadow-sm" role="alert">
                ${escapeHtml(msg)}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `;
        setTimeout(() => {
            const al = container.querySelector('.alert');
            if (al) al.remove();
        }, 5000);
    }

    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }
});
</script>
