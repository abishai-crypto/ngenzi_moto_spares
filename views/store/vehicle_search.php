<?php
$selectedVehicle = $selectedVehicle ?? ($vehicle ?? null);
?>
<div class="container my-4">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="/index.php?route=store" class="text-decoration-none">Nyumbani</a></li>
            <li class="breadcrumb-item active" aria-current="page">Tafuta kwa Chombo</li>
        </ol>
    </nav>

    <!-- Top Vehicle Selector -->
    <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
        <h4 class="fw-bold text-dark mb-2"><i class="bi bi-bicycle text-warning"></i> Chagua Pikipiki au Bajaji Yako</h4>
        <p class="text-muted small mb-4">Chagua mtindo halisi wa chombo chako ili mfumo uonyeshe vipuri vyote vinavyoendana nacho kwa 100%.</p>

        <div class="row row-cols-2 row-cols-md-3 row-cols-lg-4 g-2">
            <?php foreach ($allModels as $m): ?>
                <div class="col">
                    <a href="/index.php?route=store_vehicle&model_id=<?= $m['id'] ?>" 
                       class="btn <?= ($selectedVehicle && (int)$selectedVehicle['id'] === (int)$m['id']) ? 'btn-primary' : 'btn-outline-secondary' ?> w-100 text-start p-3 rounded-4 d-flex flex-column gap-1">
                        <span class="badge <?= $m['vehicle_type'] === 'Bajaj' ? 'bg-warning text-dark' : 'bg-info text-dark' ?> align-self-start small">
                            <?= htmlspecialchars($m['vehicle_type']) ?>
                        </span>
                        <span class="fw-bold fs-6"><?= htmlspecialchars($m['brand_name']) ?> <?= htmlspecialchars($m['model_name']) ?></span>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Compatible Parts Results -->
    <?php if ($selectedVehicle): ?>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold mb-0">
                Vipuri Vinavyofaa: <span class="text-primary"><?= htmlspecialchars($selectedVehicle['brand_name']) ?> <?= htmlspecialchars($selectedVehicle['model_name']) ?></span>
                (<?= count($compatibleParts) ?>)
            </h5>
            <a href="/index.php?route=shop&model=<?= $selectedVehicle['id'] ?>" class="btn btn-outline-primary btn-sm rounded-pill">
                Fungua kwenye Katalogi <i class="bi bi-arrow-right"></i>
            </a>
        </div>

        <?php if (empty($compatibleParts)): ?>
            <div class="card border-0 rounded-4 shadow-sm p-5 text-center bg-white my-4">
                <i class="bi bi-tools fs-1 text-muted"></i>
                <h6 class="fw-bold mt-2">Bado hakuna vipuri vilivyoorodheshwa kwa chombo hiki.</h6>
                <p class="text-muted small">Tafadhali wasiliana nasi dukani Kariakoo tukuagizie moja kwa moja.</p>
            </div>
        <?php else: ?>
            <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-3">
                <?php foreach ($compatibleParts as $p): ?>
                <div class="col">
                    <div class="product-card-online">
                        <div class="position-relative">
                            <a href="/index.php?route=product_detail&id=<?= $p['id'] ?>">
                                <img src="<?= htmlspecialchars($p['image_url']) ?>" class="card-img-top" alt="<?= htmlspecialchars($p['name']) ?>"
                                     style="height: 180px; object-fit: contain; padding: 12px; background: #f8fafc;"
                                     onerror="this.src='/assets/images/placeholder_product.svg';">
                            </a>
                            <div class="position-absolute top-0 start-0 m-2">
                                <?php if ($p['in_stock']): ?>
                                    <span class="badge bg-success rounded-pill px-2 py-1 small">Stoo Ipo</span>
                                <?php else: ?>
                                    <span class="badge bg-danger rounded-pill px-2 py-1 small">Imeisha</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="p-3 d-flex flex-column flex-grow-1">
                            <div class="small text-muted mb-1 d-flex justify-content-between">
                                <span><?= htmlspecialchars($p['category_name']) ?></span>
                                <code><?= htmlspecialchars($p['sku']) ?></code>
                            </div>
                            <h6 class="fw-bold mb-2">
                                <a href="/index.php?route=product_detail&id=<?= $p['id'] ?>" class="text-dark text-decoration-none">
                                    <?= htmlspecialchars($p['name']) ?>
                                </a>
                            </h6>
                            <div class="mt-auto pt-2 border-top d-flex justify-content-between align-items-center">
                                <span class="fs-6 fw-bold text-primary">TSh <?= number_format($p['selling_price'], 2) ?></span>
                                <?php if ($p['in_stock']): ?>
                                    <form action="/index.php?route=cart_add" method="POST">
                                        <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                                        <input type="hidden" name="quantity" value="1">
                                        <button type="submit" class="btn btn-store-primary btn-sm rounded-pill px-3">
                                            <i class="bi bi-cart-plus"></i> Weka
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <button class="btn btn-secondary btn-sm rounded-pill" disabled>Imeisha</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
