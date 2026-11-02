<div class="container my-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="/index.php?route=store" class="text-decoration-none">Nyumbani</a></li>
            <li class="breadcrumb-item"><a href="/index.php?route=cart" class="text-decoration-none">Kapu</a></li>
            <li class="breadcrumb-item active" aria-current="page">Kamilisha Agizo</li>
        </ol>
    </nav>

    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-1"><i class="bi bi-shield-check text-success me-2"></i> Kamilisha Agizo Lako (Checkout)</h3>
            <span class="text-muted small">Utaratibu wa kuchukua bidhaa dukani (Manual / Store Pickup) — Kariakoo, Dar es Salaam</span>
        </div>
    </div>

    <form action="/index.php?route=checkout_process" method="POST" id="checkoutForm">
        <input type="hidden" name="idempotency_key" value="IDEMP-<?= time() ?>-<?= bin2hex(random_bytes(4)) ?>">
        <input type="hidden" name="delivery_provider" value="CUSTOMER_PICKUP">
        <input type="hidden" name="delivery_method" value="CUSTOMER_PICKUP">
        <input type="hidden" name="address_id" value="0">

        <div class="row g-4">
            <!-- Left: Pickup Details & Payment -->
            <div class="col-lg-8">
                <!-- STEP 1: Store Pickup & Customer Details -->
                <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold text-dark mb-0">
                            <span class="badge rounded-circle bg-primary text-white me-2">1</span> Utaratibu wa Kuchukua Vipuri
                        </h5>
                        <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-1.5 rounded-pill fw-bold">
                            <i class="bi bi-shop me-1"></i> Kuchukua Dukani (Bure)
                        </span>
                    </div>

                    <!-- Pickup Location Banner -->
                    <div class="alert alert-light border rounded-3 p-3 mb-4">
                        <div class="d-flex gap-3">
                            <div class="fs-2 text-primary">
                                <i class="bi bi-geo-alt-fill"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold text-dark mb-1">Duka la Ngenzi Moto Spare Parts</h6>
                                <p class="text-muted small mb-1">Mtaa wa Nyamwezi na Msimbazi, Kariakoo, Dar es Salaam.</p>
                                <div class="d-flex flex-wrap gap-3 small text-secondary">
                                    <span><i class="bi bi-clock me-1 text-primary"></i> Jumatatu – Jumamosi: Saa 2:00 Asubuhi – 12:00 Jioni</span>
                                    <span><i class="bi bi-telephone me-1 text-primary"></i> Simu ya Duka: 0754 123 456 / 0712 987 654</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <h6 class="fw-bold text-dark mb-2">Taarifa za Mteja (Mtu Atakayekuja Kuchukua):</h6>
                    <p class="text-muted small mb-3">Tafadhali thibitisha jina na namba ya simu ili tukuhifadhie mzigo wako tayari kwa makabidhiano unapofika:</p>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Jina Kamili la Mteja <span class="text-danger">*</span></label>
                            <input type="text" name="recipient_name" class="form-control" placeholder="Mf. Juma Bakari" value="<?= htmlspecialchars($customer['name'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Namba ya Simu ya Mawasiliano <span class="text-danger">*</span></label>
                            <input type="tel" name="phone" class="form-control" placeholder="07xxxxxxxx" value="<?= htmlspecialchars($customer['phone'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small fw-semibold">Barua Pepe (Email - Hiari)</label>
                            <input type="email" name="email" class="form-control" placeholder="mteja@example.com" value="<?= htmlspecialchars($customer['email'] ?? '') ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Maelekezo au Ujumbe wa Ziada (Hiari)</label>
                            <input type="text" name="customer_notes" class="form-control" placeholder="Mf. Nitakuja mchana saa 8, au Namtuma dereva wangu mwenye kitambulisho...">
                        </div>
                    </div>
                </div>

                <!-- STEP 2: Payment Method Selection -->
                <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
                    <h5 class="fw-bold text-dark mb-3">
                        <span class="badge rounded-circle bg-primary text-white me-2">2</span> Chagua Njia ya Malipo
                    </h5>

                    <div class="d-flex flex-column gap-3">
                        <!-- Mobile Money -->
                        <div class="p-3 border rounded-3 bg-light">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="payment_method" id="pay_mm" value="Mobile Money" checked>
                                <label class="form-check-label fw-bold cursor-pointer" for="pay_mm">
                                    <i class="bi bi-phone text-success me-1"></i> Lipa kwa Simu (Lipa Namba ya Mtandao)
                                </label>
                            </div>
                            <div class="ps-4 mt-2">
                                <label class="form-label small text-muted mb-1">Chagua Mtandao Wako:</label>
                                <select name="payment_provider" class="form-select form-select-sm" style="max-width: 260px;">
                                    <option value="M-Pesa">M-Pesa (Vodacom)</option>
                                    <option value="Tigo Pesa">Tigo Pesa</option>
                                    <option value="Airtel Money">Airtel Money</option>
                                    <option value="Halopesa">HaloPesa</option>
                                </select>
                                <small class="text-muted d-block mt-2" style="font-size: 11px;">
                                    *Utapewa Lipa Namba ya duka mara tu baada ya kuthibitisha agizo lako.
                                </small>
                            </div>
                        </div>

                        <!-- Bank Transfer -->
                        <div class="p-3 border rounded-3">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="payment_method" id="pay_bank" value="Bank">
                                <label class="form-check-label fw-bold cursor-pointer" for="pay_bank">
                                    <i class="bi bi-bank text-primary me-1"></i> Uhamisho wa Benki (CRDB / NMB)
                                </label>
                            </div>
                            <small class="text-muted ps-4 d-block mt-1">Utaonyeshwa namba ya akaunti rasmi ya benki ya Ngenzi Moto Spare Parts kuweka fedha.</small>
                        </div>

                        <!-- Cash at Store -->
                        <div class="p-3 border rounded-3">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="payment_method" id="pay_cash" value="Cash">
                                <label class="form-check-label fw-bold cursor-pointer" for="pay_cash">
                                    <i class="bi bi-cash-stack text-warning me-1"></i> Lipa Dukani Utakapofika (Cash / POS Kaunta)
                                </label>
                            </div>
                            <small class="text-muted ps-4 d-block mt-1">Lipa pesa taslimu au kadi dukani Kariakoo wakati unakabidhiwa mzigo wako.</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right: Order Summary Sidebar -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm rounded-4 p-4 bg-white sticky-top" style="top: 80px;">
                    <h5 class="fw-bold text-dark mb-3">Muhtasari wa Agizo (<?= $cart['item_count'] ?>)</h5>

                    <div class="overflow-auto mb-3" style="max-height: 240px;">
                        <?php foreach ($cart['items'] as $it): ?>
                        <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                            <div>
                                <span class="fw-semibold small d-block"><?= htmlspecialchars($it['product_name']) ?></span>
                                <small class="text-muted"><?= $it['quantity'] ?> x TSh <?= number_format($it['unit_price'], 2) ?></small>
                            </div>
                            <span class="fw-bold small text-primary">TSh <?= number_format($it['subtotal'], 2) ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="d-flex justify-content-between mb-2 small">
                        <span class="text-muted">Jumla ya Vipuri:</span>
                        <span class="fw-semibold">TSh <?= number_format($cart['subtotal'], 2) ?></span>
                    </div>

                    <div class="d-flex justify-content-between mb-2 small">
                        <span class="text-muted">Utaratibu wa Kuchukua:</span>
                        <span class="fw-semibold text-dark"><i class="bi bi-shop me-1"></i> Dukani Kariakoo</span>
                    </div>

                    <div class="d-flex justify-content-between mb-3 small">
                        <span class="text-muted">Gharama ya Usafirishaji:</span>
                        <span class="fw-bold text-success">BURE (TSh 0.00)</span>
                    </div>

                    <hr class="my-3">

                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <span class="fw-bold fs-6">Jumla Kuu ya Kulipa:</span>
                        <span class="fw-bold fs-4 text-primary">
                            TSh <?= number_format($cart['subtotal'], 2) ?>
                        </span>
                    </div>

                    <button type="submit" class="btn btn-store-accent btn-lg w-100 rounded-pill fw-bold shadow">
                        <i class="bi bi-check2-circle me-1"></i> Thibitisha Agizo Lako
                    </button>

                    <div class="text-center mt-3">
                        <small class="text-muted d-block" style="font-size: 11px;">
                            <i class="bi bi-shield-check text-success"></i> Muamala salama wa Ngenzi Moto Spare Parts — Kariakoo
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
