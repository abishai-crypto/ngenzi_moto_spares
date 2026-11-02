<?php
$avail = $product['availability'] ?? \App\Models\Product::getAvailabilityInfo($product);
$images = !empty($product['images']) ? $product['images'] : [[
    'id'         => 0,
    'image_url'  => $product['image_url'] ?: '/assets/img/spare_part_placeholder.svg',
    'alt_text'   => $product['name'],
    'is_primary' => 1
]];
?>
<div class="container my-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="/index.php?route=store" class="text-decoration-none">Nyumbani</a></li>
            <li class="breadcrumb-item"><a href="/index.php?route=shop" class="text-decoration-none">Katalogi</a></li>
            <li class="breadcrumb-item"><a href="/index.php?route=shop&category=<?= $product['category_id'] ?>" class="text-decoration-none"><?= htmlspecialchars($product['category_name']) ?></a></li>
            <li class="breadcrumb-item active text-truncate" aria-current="page" style="max-width: 250px;"><?= htmlspecialchars($product['name']) ?></li>
        </ol>
    </nav>

    <!-- Product Overview Card -->
    <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
        <div class="row g-4">
            <!-- Left: Product Image & Thumbnail Gallery (Phase 17) -->
            <div class="col-lg-5">
                <div class="p-3 bg-light rounded-4 border position-relative d-flex align-items-center justify-content-center overflow-hidden" style="min-height: 360px;">
                    <img id="mainProductImg" src="<?= htmlspecialchars($images[0]['image_url']) ?>" 
                         alt="<?= htmlspecialchars($product['name']) ?>" 
                         class="img-fluid rounded-3 cursor-pointer" 
                         style="max-height: 320px; object-fit: contain; cursor: zoom-in;"
                         onclick="openLightbox(this.src)"
                         onerror="this.src='/assets/img/spare_part_placeholder.svg';">

                    <!-- Customer-Friendly Availability Badge -->
                    <div class="position-absolute top-0 start-0 m-3">
                        <span class="badge <?= $avail['badge_class'] ?> px-3 py-2 rounded-pill shadow-sm">
                            <i class="bi bi-info-circle me-1"></i> <?= htmlspecialchars($avail['label']) ?>
                        </span>
                    </div>
                </div>

                <!-- Thumbnail Gallery (Phase 17 Multiple Product Images) -->
                <?php if (count($images) > 1): ?>
                <div class="d-flex gap-2 mt-3 overflow-auto pb-1" id="thumbnailGallery">
                    <?php foreach ($images as $idx => $img): ?>
                    <div class="border rounded-3 p-1 bg-white cursor-pointer thumb-box <?= $idx === 0 ? 'border-primary' : 'border-light' ?>" 
                         style="width: 70px; height: 70px; flex-shrink: 0; cursor: pointer;"
                         onclick="switchMainImage('<?= htmlspecialchars($img['image_url']) ?>', this)">
                        <img src="<?= htmlspecialchars($img['image_url']) ?>" 
                             class="w-100 h-100 object-fit-contain rounded" 
                             alt="<?= htmlspecialchars($img['alt_text'] ?? $product['name']) ?>"
                             onerror="this.src='/assets/img/spare_part_placeholder.svg';">
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Right: Product Details & Purchase Form -->
            <div class="col-lg-7">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <span class="badge bg-light text-primary border mb-2 px-3 py-1">
                            <?= htmlspecialchars($product['category_name']) ?>
                            <?php if (!empty($product['product_type'])): ?>
                                &bull; <?= htmlspecialchars($product['product_type']) ?>
                            <?php endif; ?>
                        </span>
                        <h2 class="fw-bold text-dark mb-2"><?= htmlspecialchars($product['name']) ?></h2>
                        <div class="text-muted small mb-3">
                            <span>SKU: <strong class="text-dark font-monospace"><?= htmlspecialchars($product['sku']) ?></strong></span>
                            <?php if (!empty($product['brand_name'])): ?>
                                <span class="ms-3">Chapa: <strong class="text-dark"><?= htmlspecialchars($product['brand_name']) ?></strong></span>
                            <?php endif; ?>
                            <?php if (!empty($product['unit_short'])): ?>
                                <span class="ms-3">Kipimo: <strong class="text-dark"><?= htmlspecialchars($product['unit_short']) ?></strong></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <!-- Wishlist Toggle -->
                    <form action="/index.php?route=wishlist_add" method="POST">
                        <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                        <button type="submit" class="btn btn-outline-danger btn-sm rounded-circle p-2 shadow-sm" title="Hifadhi kwenye Wishlist">
                            <i class="bi bi-heart fs-5"></i>
                        </button>
                    </form>
                </div>

                <!-- Price & Rating Box -->
                <div class="p-3 bg-light rounded-4 my-3 d-flex flex-wrap justify-content-between align-items-center">
                    <div>
                        <small class="text-muted d-block"><?= __('selling_price') ?> (TSh)</small>
                        <div class="d-flex align-items-center gap-2">
                            <span class="display-6 fw-bold text-primary">TSh <?= number_format($product['selling_price'], 2) ?></span>
                            <?php if (!empty($product['is_catalog_only']) || empty($avail['can_order'])): ?>
                                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill px-2.5 py-1 small fw-semibold">
                                    <i class="bi bi-clock-history me-1"></i> <?= __('on_order') ?>
                                </span>
                            <?php else: ?>
                                <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2.5 py-1 small fw-semibold">
                                    <i class="bi bi-check-circle-fill me-1"></i> <?= __('in_stock') ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="text-end">
                        <div class="text-warning fs-6">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="bi <?= $i <= round($ratingSummary['avg_rating'] ?? 5) ? 'bi-star-fill' : 'bi-star' ?>"></i>
                            <?php endfor; ?>
                            <span class="text-dark fw-bold ms-1"><?= $ratingSummary['avg_rating'] ?? '5.0' ?></span>
                        </div>
                        <small class="text-muted"><?= $ratingSummary['review_count'] ?? 0 ?> tathmini</small>
                    </div>
                </div>

                <!-- Store Pickup Banner -->
                <div class="card border rounded-3 p-3 mb-3 bg-white shadow-sm">
                    <div class="d-flex align-items-center gap-2 text-dark">
                        <i class="bi bi-shop text-success fs-5"></i>
                        <div>
                            <span class="fw-bold small d-block"><?= __('store_pickup') ?></span>
                            <span class="text-muted small"><?= __('pickup_location') ?></span>
                        </div>
                    </div>
                </div>

                <!-- Purchase Actions -->
                <?php if (!empty($avail['can_order'])): ?>
                    <form action="/index.php?route=cart_add" method="POST" class="mb-4">
                        <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                        <div class="row g-3 align-items-center">
                            <div class="col-sm-4">
                                <label class="form-label small fw-semibold">Idadi Unayotaka</label>
                                <div class="input-group">
                                    <input type="number" name="quantity" class="form-control text-center fw-bold" 
                                           value="1" min="1" max="<?= max(1, (int)$product['current_stock']) ?>">
                                </div>
                            </div>
                            <div class="col-sm-8 d-flex gap-2 pt-sm-4">
                                <button type="submit" class="btn btn-store-primary btn-lg flex-grow-1 rounded-pill shadow-sm">
                                    <i class="bi bi-cart-plus me-1"></i> Weka Kwenye Kapu
                                </button>
                            </div>
                        </div>
                    </form>
                <?php elseif (!empty($product['is_catalog_only'])): ?>
                    <div class="alert alert-secondary rounded-3 mb-4 small">
                        <i class="bi bi-info-circle me-1"></i> <strong>Bidhaa ya Katalogi:</strong> Kipuri hiki kipo kwenye orodha yetu ya marejeleo. Wasiliana nasi kwa namba <strong>+255 754 123 456</strong> au bonyeza kitufe hapa chini kuomba kiagizwe kwa ajili yako.
                    </div>
                    <form action="/index.php?route=request_out_of_stock" method="POST" class="mb-4">
                        <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                        <button type="submit" class="btn btn-outline-primary rounded-pill px-4">
                            <i class="bi bi-bell-fill me-1"></i> Omba Kuagiziwa Kipuri Hiki
                        </button>
                    </form>
                <?php else: ?>
                    <div class="alert alert-warning rounded-3 mb-4 small">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i> Samahani, kipuri hiki hakipo stoo kwa sasa. Wasiliana nasi kwa namba <strong>+255 754 123 456</strong> kuagizia.
                    </div>
                    <form action="/index.php?route=request_out_of_stock" method="POST" class="mb-4">
                        <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                        <button type="submit" class="btn btn-warning rounded-pill px-4 text-dark fw-bold">
                            <i class="bi bi-bell-fill me-1"></i> Niambie Kikiingia Stoo
                        </button>
                    </form>
                <?php endif; ?>

                <!-- Descriptions -->
                <div class="border-top pt-3">
                    <h6 class="fw-bold mb-2"><i class="bi bi-card-text text-primary"></i> Maelezo ya Kipuri</h6>
                    <p class="text-muted small mb-0">
                        <?= nl2br(htmlspecialchars($product['online_description'] ?: ($product['description'] ?: 'Hakuna maelezo ya ziada yaliyowekwa kwa kipuri hiki.'))) ?>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Vehicle Compatibility Table -->
    <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
        <h5 class="fw-bold text-dark mb-3">
            <i class="bi bi-bicycle text-warning"></i> Pikipiki na Bajaji Zinazofaa (Vehicle Compatibility)
        </h5>
        <?php if (empty($product['compatible_vehicles'])): ?>
            <p class="text-muted small mb-0">Taarifa mahususi za chombo hazijaambatanishwa kwa kipuri hiki.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 small">
                    <thead class="table-light">
                        <tr>
                            <th>Aina ya Chombo</th>
                            <th>Chapa (Brand)</th>
                            <th>Mtindo (Model Name)</th>
                            <th>Uwezo wa Injini (CC)</th>
                            <th>Miaka ya Uzalishaji</th>
                            <th>Maelezo Maalum</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($product['compatible_vehicles'] as $v): ?>
                        <tr>
                            <td>
                                <span class="badge <?= $v['vehicle_type'] === 'Bajaj' ? 'bg-warning text-dark' : 'bg-primary' ?>">
                                    <?= htmlspecialchars($v['vehicle_type']) ?>
                                </span>
                            </td>
                            <td class="fw-bold"><?= htmlspecialchars($v['brand_name']) ?></td>
                            <td><?= htmlspecialchars($v['model_name']) ?></td>
                            <td><?= htmlspecialchars($v['engine_capacity'] ?: 'N/A') ?></td>
                            <td>
                                <?= !empty($v['year_from']) ? htmlspecialchars($v['year_from']) . ' - ' . ($v['year_to'] ?: 'Sasa') : 'Miundo Yote' ?>
                            </td>
                            <td class="text-muted"><?= htmlspecialchars($v['compatibility_notes'] ?: 'Inafaa moja kwa moja') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Related Products -->
    <?php if (!empty($relatedProducts)): ?>
    <div class="mb-4">
        <h5 class="fw-bold text-dark mb-3"><i class="bi bi-grid-fill text-primary"></i> Vipuri Vinavyofanana (Related Spares)</h5>
        <div class="row row-cols-1 row-cols-sm-2 row-cols-md-4 g-3">
            <?php foreach ($relatedProducts as $rel): 
                $relAvail = $rel['availability'] ?? \App\Models\Product::getAvailabilityInfo($rel);
            ?>
            <div class="col">
                <div class="card h-100 border rounded-4 bg-white shadow-sm overflow-hidden">
                    <div class="p-3 bg-light text-center" style="height: 140px; display: flex; align-items: center; justify-content: center;">
                        <a href="/index.php?route=product_detail&id=<?= $rel['id'] ?>">
                            <img src="<?= htmlspecialchars($rel['image_url']) ?>" 
                                 class="img-fluid" 
                                 style="max-height: 120px; object-fit: contain;" 
                                 alt="<?= htmlspecialchars($rel['name']) ?>"
                                 onerror="this.src='/assets/img/spare_part_placeholder.svg';">
                        </a>
                    </div>
                    <div class="p-3 d-flex flex-column flex-grow-1">
                        <small class="text-muted"><?= htmlspecialchars($rel['category_name']) ?></small>
                        <h6 class="fw-bold mb-2">
                            <a href="/index.php?route=product_detail&id=<?= $rel['id'] ?>" class="text-dark text-decoration-none">
                                <?= htmlspecialchars($rel['name']) ?>
                            </a>
                        </h6>
                        <div class="mt-auto d-flex justify-content-between align-items-center">
                            <span class="fw-bold text-primary">TSh <?= number_format($rel['selling_price'], 2) ?></span>
                            <a href="/index.php?route=product_detail&id=<?= $rel['id'] ?>" class="btn btn-outline-primary btn-sm rounded-pill px-2">
                                Tazama
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Lightbox Modal for Image Zoom -->
<div class="modal fade" id="imageLightboxModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content bg-transparent border-0">
            <div class="modal-body text-center p-0">
                <img id="lightboxImg" src="" class="img-fluid rounded-4 shadow-lg bg-white p-2" style="max-height: 80vh;">
            </div>
        </div>
    </div>
</div>

<script>
function switchMainImage(url, thumbElem) {
    var main = document.getElementById('mainProductImg');
    if (main) {
        main.src = url;
    }
    document.querySelectorAll('.thumb-box').forEach(function(b) {
        b.classList.remove('border-primary');
        b.classList.add('border-light');
    });
    if (thumbElem) {
        thumbElem.classList.remove('border-light');
        thumbElem.classList.add('border-primary');
    }
}

function openLightbox(url) {
    var modalImg = document.getElementById('lightboxImg');
    if (modalImg) {
        modalImg.src = url;
        var modal = new bootstrap.Modal(document.getElementById('imageLightboxModal'));
        modal.show();
    }
}
</script>
