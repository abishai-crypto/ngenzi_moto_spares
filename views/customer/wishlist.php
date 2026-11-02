<div class="container my-4">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="/index.php?route=store" class="text-decoration-none">Nyumbani</a></li>
            <li class="breadcrumb-item"><a href="/index.php?route=customer_dashboard" class="text-decoration-none">Dashibodi</a></li>
            <li class="breadcrumb-item active" aria-current="page">Wishlist</li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-1"><i class="bi bi-heart-fill text-danger"></i> Vipuri Nilivyohifadhi (Wishlist)</h4>
            <p class="text-muted small mb-0">Hifadhi vipuri unavyopanga kuvinunua siku zijazo au unavyovipenda.</p>
        </div>
        <a href="/index.php?route=shop" class="btn btn-store-primary rounded-pill px-3">
            <i class="bi bi-search me-1"></i> Tafuta Vipuri Zaidi
        </a>
    </div>

    <?php if (empty($items)): ?>
        <div class="card border-0 shadow-sm rounded-4 p-5 text-center bg-white">
            <i class="bi bi-heart fs-1 text-muted"></i>
            <h5 class="fw-bold mt-2">Bado haujahifadhi kipuri chochote.</h5>
            <p class="text-muted small">Bonyeza alama ya moyo kwenye kipuri chochote ili kukihifadhi hapa.</p>
            <div>
                <a href="/index.php?route=shop" class="btn btn-store-primary rounded-pill px-4">Tazama Katalogi</a>
            </div>
        </div>
    <?php else: ?>
        <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-3">
            <?php foreach ($items as $it): ?>
            <div class="col">
                <div class="product-card-online">
                    <div class="position-relative">
                        <a href="/index.php?route=product_detail&id=<?= $it['product_id'] ?>">
                            <img src="<?= htmlspecialchars($it['image_url']) ?>" class="card-img-top" alt="<?= htmlspecialchars($it['product_name']) ?>"
                                 onerror="this.src='/assets/img/spare_part_placeholder.png';">
                        </a>
                        <!-- Remove button -->
                        <form action="/index.php?route=wishlist_remove" method="POST" class="position-absolute top-0 end-0 m-2">
                            <input type="hidden" name="product_id" value="<?= $it['product_id'] ?>">
                            <button type="submit" class="btn btn-light btn-sm rounded-circle shadow-sm" title="Ondoa kwenye Wishlist">
                                <i class="bi bi-x text-danger fs-6"></i>
                            </button>
                        </form>
                    </div>
                    <div class="p-3 d-flex flex-column flex-grow-1">
                        <small class="text-muted mb-1"><?= htmlspecialchars($it['category_name']) ?> • SKU: <?= htmlspecialchars($it['sku']) ?></small>
                        <h6 class="fw-bold mb-2">
                            <a href="/index.php?route=product_detail&id=<?= $it['product_id'] ?>" class="text-dark text-decoration-none">
                                <?= htmlspecialchars($it['product_name']) ?>
                            </a>
                        </h6>
                        <div class="mt-auto pt-2 border-top d-flex justify-content-between align-items-center">
                            <span class="fs-6 fw-bold text-primary">TSh <?= number_format($it['selling_price'], 2) ?></span>
                            <?php if ($it['current_stock'] > 0): ?>
                                <form action="/index.php?route=wishlist_move_to_cart" method="POST">
                                    <input type="hidden" name="product_id" value="<?= $it['product_id'] ?>">
                                    <button type="submit" class="btn btn-store-primary btn-sm rounded-pill px-3">
                                        <i class="bi bi-cart-plus me-1"></i> Kapuni
                                    </button>
                                </form>
                            <?php else: ?>
                                <span class="badge bg-danger-subtle text-danger small">Imeisha</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
