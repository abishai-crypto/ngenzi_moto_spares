<div class="container my-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="/index.php?route=store" class="text-decoration-none">Nyumbani</a></li>
            <li class="breadcrumb-item active" aria-current="page">Kapu la Ununuzi</li>
        </ol>
    </nav>

    <h3 class="fw-bold text-dark mb-4"><i class="bi bi-cart3 text-primary"></i> Kapu Lako la Ununuzi</h3>

    <?php if (empty($cart['items'])): ?>
        <!-- Empty Cart State -->
        <div class="card border-0 shadow-sm rounded-4 p-5 text-center bg-white my-4">
            <div class="p-4 rounded-circle bg-light d-inline-block text-muted mb-3" style="width: 90px; height: 90px;">
                <i class="bi bi-cart-x fs-1"></i>
            </div>
            <h4 class="fw-bold text-dark mb-2">Kapu Lako Liko Wazi</h4>
            <p class="text-muted small max-w-sm mx-auto mb-4" style="max-width: 400px;">
                Bado haujaweka kipuri chochote kwenye kapu lako la ununuzi. Angalia katalogi yetu ya vipuri halisi uongeze unachohitaji.
            </p>
            <div>
                <a href="/index.php?route=shop" class="btn btn-store-primary rounded-pill px-4 py-2">
                    <i class="bi bi-grid me-1"></i> Tazama Katalogi ya Vipuri
                </a>
            </div>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <!-- Left: Cart Items Table -->
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold mb-0">Vipuri Vilivyochaguliwa (<?= $cart['item_count'] ?>)</h6>
                        <form action="/index.php?route=cart_clear" method="POST" onsubmit="return confirm('Una uhakika unataka kufuta bidhaa zote kwenye kapu?');">
                            <button type="submit" class="btn btn-link text-danger text-decoration-none p-0 small">
                                <i class="bi bi-trash"></i> Futa Kapu Lote
                            </button>
                        </form>
                    </div>

                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead class="table-light small">
                                <tr>
                                    <th>Kipuri</th>
                                    <th>Bei</th>
                                    <th style="width: 140px;">Idadi</th>
                                    <th>Jumla Ndogo</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cart['items'] as $it): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-3">
                                            <img src="<?= htmlspecialchars($it['image_url']) ?>" alt="<?= htmlspecialchars($it['product_name']) ?>" 
                                                 class="rounded-3 border p-1" style="width: 54px; height: 54px; object-fit: contain;"
                                                 onerror="this.src='/assets/img/spare_part_placeholder.png';">
                                            <div>
                                                <a href="/index.php?route=product_detail&id=<?= $it['product_id'] ?>" class="fw-bold text-dark text-decoration-none small d-block">
                                                    <?= htmlspecialchars($it['product_name']) ?>
                                                </a>
                                                <small class="text-muted">SKU: <?= htmlspecialchars($it['product_sku']) ?></small>
                                                <?php if (!$it['is_available']): ?>
                                                    <span class="badge bg-danger-subtle text-danger d-block mt-1 small">
                                                        <?= $it['stock_warning'] ?: 'Haipatikani kwa sasa' ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="fw-semibold small">
                                        TSh <?= number_format($it['unit_price'], 2) ?>
                                    </td>
                                    <td>
                                        <form action="/index.php?route=cart_update" method="POST" class="d-flex align-items-center gap-1">
                                            <input type="hidden" name="product_id" value="<?= $it['product_id'] ?>">
                                            <input type="number" name="quantity" value="<?= $it['quantity'] ?>" min="1" max="<?= $it['current_stock'] ?>" 
                                                   class="form-control form-control-sm text-center fw-bold" style="width: 70px;"
                                                   onchange="this.form.submit()">
                                        </form>
                                    </td>
                                    <td class="fw-bold text-primary small">
                                        TSh <?= number_format($it['subtotal'], 2) ?>
                                    </td>
                                    <td>
                                        <form action="/index.php?route=cart_remove" method="POST">
                                            <input type="hidden" name="product_id" value="<?= $it['product_id'] ?>">
                                            <button type="submit" class="btn btn-outline-danger btn-sm rounded-circle p-1" title="Ondoa">
                                                <i class="bi bi-x fs-6"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <a href="/index.php?route=shop" class="btn btn-outline-secondary rounded-pill px-4">
                    <i class="bi bi-arrow-left me-1"></i> Endelea na Manunuzi
                </a>
            </div>

            <!-- Right: Order Summary Sidebar -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm rounded-4 p-4 bg-white sticky-top" style="top: 80px;">
                    <h5 class="fw-bold text-dark mb-3">Muhtasari wa Malipo</h5>

                    <div class="d-flex justify-content-between mb-2 small">
                        <span class="text-muted">Jumla ya Vipuri:</span>
                        <span class="fw-semibold">TSh <?= number_format($cart['subtotal'], 2) ?></span>
                    </div>

                    <div class="d-flex justify-content-between mb-3 small">
                        <span class="text-muted"><i class="bi bi-shop text-primary me-1"></i> Utaratibu wa Kupokea:</span>
                        <span class="fw-bold text-success">
                            Kuchukua Dukani (BURE)
                        </span>
                    </div>

                    <hr class="my-3">

                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <span class="fw-bold fs-6">Jumla Kuu:</span>
                        <span class="fw-bold fs-4 text-primary">TSh <?= number_format($cart['total'], 2) ?></span>
                    </div>

                    <?php if ($cart['all_available']): ?>
                        <a href="/index.php?route=checkout" class="btn btn-store-accent btn-lg w-100 rounded-pill fw-bold shadow">
                            Kamilisha Agizo (Checkout) <i class="bi bi-arrow-right ms-1"></i>
                        </a>
                    <?php else: ?>
                        <button class="btn btn-secondary btn-lg w-100 rounded-pill" disabled>
                            Rekebisha Bidhaa Zilizopungua
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>
