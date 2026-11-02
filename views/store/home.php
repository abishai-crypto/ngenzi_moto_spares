<div class="container my-4">
    <!-- Hero Banner -->
    <div class="hero-banner shadow-lg mb-5">
        <div class="row align-items-center">
            <div class="col-lg-7">
                <span class="badge bg-warning text-dark px-3 py-2 rounded-pill fw-bold mb-3">
                    <i class="bi bi-patch-check-fill"></i> <?= __('hero_badge') ?>
                </span>
                <h1 class="display-5 fw-extrabold text-white mb-3">
                    <?= __('hero_title_prefix') ?> <span class="text-warning"><?= __('hero_title_highlight') ?></span> <?= __('hero_title_suffix') ?>
                </h1>
                <p class="lead text-white-50 mb-4">
                    <?= __('hero_desc') ?>
                </p>
                <div class="d-flex flex-wrap gap-3">
                    <a href="#all-products-section" class="btn btn-store-accent btn-lg px-4 py-2 rounded-pill shadow">
                        <i class="bi bi-grid-fill me-2"></i> <?= __('hero_btn_shop') ?>
                    </a>
                    <a href="/index.php?route=store_vehicle" class="btn btn-outline-light btn-lg px-4 py-2 rounded-pill">
                        <i class="bi bi-bicycle me-2"></i> <?= __('hero_btn_vehicle') ?>
                    </a>
                </div>
            </div>
            <div class="col-lg-5 text-center mt-4 mt-lg-0 d-none d-lg-block">
                <div class="p-4 bg-white bg-opacity-10 rounded-4 border border-white border-opacity-20 text-start">
                    <h5 class="text-white fw-bold mb-3"><i class="bi bi-funnel-fill text-warning"></i> <?= __('hero_filter_title') ?></h5>
                    <form action="/index.php" method="GET">
                        <input type="hidden" name="route" value="shop">
                        <div class="mb-3">
                            <label class="form-label text-white-50 small"><?= __('hero_filter_vehicle_type') ?></label>
                            <select name="vehicle_type" class="form-select form-select-sm rounded-3">
                                <option value=""><?= __('hero_filter_all_types') ?></option>
                                <option value="Motorcycle"><?= __('hero_filter_motorcycle') ?></option>
                                <option value="Bajaj"><?= __('hero_filter_bajaj') ?></option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-white-50 small"><?= __('search_placeholder') ?></label>
                            <input type="text" name="search" class="form-control form-control-sm rounded-3" placeholder="Mf. Piston, Brake Shoe, Clutch Cable...">
                        </div>
                        <button type="submit" class="btn btn-store-accent w-100 rounded-3 fw-bold">
                            <i class="bi bi-search me-1"></i> <?= __('search_btn') ?>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Value Propositions -->
    <div class="row g-3 mb-5">
        <div class="col-md-4">
            <div class="p-3 bg-white rounded-4 border d-flex align-items-center gap-3 shadow-sm h-100">
                <div class="p-3 rounded-circle bg-teal-subtle text-success fs-3 d-flex align-items-center justify-content-center" style="width: 58px; height: 58px;">
                    <i class="bi bi-shield-check"></i>
                </div>
                <div>
                    <h6 class="fw-bold mb-1"><?= __('val_prop_1_title') ?></h6>
                    <small class="text-muted"><?= __('val_prop_1_desc') ?></small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="p-3 bg-white rounded-4 border d-flex align-items-center gap-3 shadow-sm h-100">
                <div class="p-3 rounded-circle bg-warning-subtle text-warning fs-3 d-flex align-items-center justify-content-center" style="width: 58px; height: 58px;">
                    <i class="bi bi-tags-fill"></i>
                </div>
                <div>
                    <h6 class="fw-bold mb-1"><?= __('val_prop_2_title') ?></h6>
                    <small class="text-muted"><?= __('val_prop_2_desc') ?></small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="p-3 bg-white rounded-4 border d-flex align-items-center gap-3 shadow-sm h-100">
                <div class="p-3 rounded-circle bg-primary-subtle text-primary fs-3 d-flex align-items-center justify-content-center" style="width: 58px; height: 58px;">
                    <i class="bi bi-shop"></i>
                </div>
                <div>
                    <h6 class="fw-bold mb-1"><?= __('val_prop_3_title') ?></h6>
                    <small class="text-muted"><?= __('val_prop_3_desc') ?></small>
                </div>
            </div>
        </div>
    </div>

    <!-- Categories Section -->
    <?php if (!empty($categories)): ?>
    <div class="mb-5">
        <div class="d-flex justify-content-between align-items-end mb-4">
            <div>
                <h3 class="fw-bold text-dark mb-1"><?= __('categories_title') ?></h3>
                <p class="text-muted mb-0"><?= __('categories_subtitle') ?></p>
            </div>
            <a href="/index.php?route=shop" class="btn btn-outline-primary btn-sm rounded-pill px-3">
                <?= __('all_parts') ?> <i class="bi bi-arrow-right"></i>
            </a>
        </div>
        <div class="row row-cols-2 row-cols-md-4 row-cols-lg-6 g-3">
            <?php foreach ($categories as $cat): ?>
            <div class="col">
                <a href="/index.php?route=shop&category=<?= $cat['id'] ?>" class="text-decoration-none">
                    <div class="card h-100 text-center border-0 shadow-sm rounded-4 p-3 hover-shadow transition" style="background: #ffffff;">
                        <div class="mx-auto mb-2 p-3 rounded-circle bg-light text-primary fs-4" style="width: 60px; height: 60px; display: flex; align-items: center; justify-content: center;">
                            <i class="bi bi-tools"></i>
                        </div>
                        <h6 class="fw-bold text-dark mb-1 fs-6"><?= htmlspecialchars($cat['name']) ?></h6>
                        <small class="text-muted"><?= $cat['product_count'] ?> vipuri</small>
                    </div>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Vehicle Compatibility Cards (Motorcycles & Bajaj) -->
    <?php if (!empty($vehicleModels)): ?>
    <div class="mb-5">
        <div class="d-flex justify-content-between align-items-end mb-4">
            <div>
                <h3 class="fw-bold text-dark mb-1"><?= __('vehicles_title') ?></h3>
                <p class="text-muted mb-0"><?= __('vehicles_subtitle') ?></p>
            </div>
            <a href="/index.php?route=store_vehicle" class="btn btn-outline-primary btn-sm rounded-pill px-3">
                Vyombo Vyote <i class="bi bi-arrow-right"></i>
            </a>
        </div>
        <div class="row row-cols-2 row-cols-md-3 row-cols-lg-6 g-3">
            <?php foreach ($vehicleModels as $vm): ?>
            <div class="col">
                <a href="/index.php?route=store_vehicle&model_id=<?= $vm['id'] ?>" class="text-decoration-none">
                    <div class="card h-100 border-0 shadow-sm rounded-4 p-3 bg-white hover-shadow transition">
                        <span class="badge <?= $vm['vehicle_type'] === 'Bajaj' ? 'bg-warning text-dark' : 'bg-primary' ?> mb-2 align-self-start">
                            <?= htmlspecialchars($vm['vehicle_type']) ?>
                        </span>
                        <h6 class="fw-bold text-dark mb-1"><?= htmlspecialchars($vm['brand_name']) ?> <?= htmlspecialchars($vm['model_name']) ?></h6>
                        <small class="text-muted"><?= $vm['parts_count'] ?> <?= __('compatible_models') ?></small>
                    </div>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Featured Products Section -->
    <?php if (!empty($featuredProducts)): ?>
    <div class="mb-5">
        <div class="d-flex justify-content-between align-items-end mb-4">
            <div>
                <h3 class="fw-bold text-dark mb-1"><?= __('featured_title') ?></h3>
                <p class="text-muted mb-0"><?= __('featured_subtitle') ?></p>
            </div>
            <a href="#all-products-section" class="btn btn-outline-primary btn-sm rounded-pill px-3">
                <?= __('catalog_title') ?> <i class="bi bi-arrow-down"></i>
            </a>
        </div>

        <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4">
            <?php foreach ($featuredProducts as $product): ?>
            <div class="col">
                <div class="product-card-online shadow-sm">
                    <!-- Product Image -->
                    <div class="position-relative">
                        <a href="/index.php?route=product_detail&id=<?= $product['id'] ?>">
                            <img src="<?= htmlspecialchars($product['image_url']) ?>" class="card-img-top" alt="<?= htmlspecialchars($product['name']) ?>"
                                 onerror="this.src='/assets/img/spare_part_placeholder.png';">
                        </a>
                        <!-- Stock Status Badge -->
                        <div class="position-absolute top-0 start-0 m-2">
                            <?php if ($product['in_stock']): ?>
                                <span class="badge bg-success rounded-pill px-2.5 py-1 small shadow-sm">
                                    <i class="bi bi-check-circle-fill me-1"></i> <?= __('in_stock') ?>
                                </span>
                            <?php else: ?>
                                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill px-2.5 py-1 small shadow-sm fw-semibold">
                                    <i class="bi bi-clock-history me-1"></i> <?= __('on_order') ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        <!-- Wishlist Button -->
                        <form action="/index.php?route=wishlist_add" method="POST" class="position-absolute top-0 end-0 m-2">
                            <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                            <button type="submit" class="btn btn-light btn-sm rounded-circle shadow-sm" title="<?= __('nav_wishlist') ?>">
                                <i class="bi bi-heart text-danger"></i>
                            </button>
                        </form>
                    </div>

                    <!-- Product Content -->
                    <div class="p-3 d-flex flex-column flex-grow-1">
                        <div class="small text-muted mb-1"><?= htmlspecialchars($product['category_name']) ?> • SKU: <?= htmlspecialchars($product['sku']) ?></div>
                        <h6 class="fw-bold mb-2">
                            <a href="/index.php?route=product_detail&id=<?= $product['id'] ?>" class="text-dark text-decoration-none">
                                <?= htmlspecialchars($product['name']) ?>
                            </a>
                        </h6>

                        <?php if (!empty($product['compatible_models'])): ?>
                            <div class="mb-3">
                                <span class="badge-vehicle text-truncate d-inline-block" style="max-width: 100%;" title="<?= htmlspecialchars($product['compatible_models']) ?>">
                                    <i class="bi bi-check2-circle"></i> <?= htmlspecialchars($product['compatible_models']) ?>
                                </span>
                            </div>
                        <?php endif; ?>

                        <div class="mt-auto pt-2 border-top d-flex justify-content-between align-items-center">
                            <div>
                                <small class="text-muted d-block" style="font-size: 11px;"><?= __('selling_price') ?></small>
                                <span class="fs-5 fw-bold text-primary">TSh <?= number_format($product['selling_price'], 2) ?></span>
                            </div>
                            <?php if ($product['in_stock']): ?>
                                <form action="/index.php?route=cart_add" method="POST">
                                    <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                    <input type="hidden" name="quantity" value="1">
                                    <button type="submit" class="btn btn-store-primary btn-sm rounded-pill px-3 shadow-sm">
                                        <i class="bi bi-cart-plus"></i> <?= __('add_to_cart') ?>
                                    </button>
                                </form>
                            <?php else: ?>
                                <form action="/index.php?route=request_out_of_stock" method="POST">
                                    <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                    <button type="submit" class="btn btn-outline-warning btn-sm rounded-pill px-2.5 fw-semibold text-dark shadow-sm" title="<?= __('order_on_request') ?>: TSh <?= number_format($product['selling_price'], 2) ?>">
                                        <i class="bi bi-telephone-outbound me-1"></i> <?= __('order_on_request') ?>
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- COMPLETE ALL PRODUCTS CATALOG SECTION WITH TRANSPARENT PRICES (Phase 18 Core Requirement) -->
    <div id="all-products-section" class="mb-5 pt-3">
        <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 border-bottom pb-4 mb-4">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <span class="badge bg-primary text-white rounded-pill px-3 py-1.5 fw-bold">
                            <i class="bi bi-box-seam me-1"></i> <?= __('all_parts') ?>: <?= count($allProducts ?? []) ?>
                        </span>
                        <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2.5 py-1 small fw-semibold">
                            <i class="bi bi-check-all"></i> Bei Kamili Hadharani
                        </span>
                    </div>
                    <h2 class="h3 fw-bold text-dark mb-1"><?= __('catalog_title') ?></h2>
                    <p class="text-muted mb-0"><?= __('catalog_subtitle') ?></p>
                </div>
                <!-- Realtime Search Input -->
                <div style="min-width: 280px; max-width: 400px;" class="w-100">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0 rounded-start-pill text-muted ps-3">
                            <i class="bi bi-search"></i>
                        </span>
                        <input type="text" id="catalogQuickSearch" class="form-control bg-light border-start-0 rounded-end-pill py-2" 
                               placeholder="Chuja kipuri, SKU, au chombo...">
                    </div>
                </div>
            </div>

            <!-- Filter Pills -->
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
                <div class="d-flex flex-wrap gap-2" id="catalogFilterPills">
                    <button type="button" class="btn btn-sm btn-store-primary active rounded-pill px-3 py-1.5 fw-semibold catalog-filter-btn text-white" data-filter="all">
                        <i class="bi bi-grid-3x3-gap-fill me-1"></i> <?= __('all_parts') ?> (<?= count($allProducts ?? []) ?>)
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3 py-1.5 fw-semibold catalog-filter-btn" data-filter="in_stock">
                        <i class="bi bi-check-circle-fill text-success me-1"></i> <?= __('in_stock') ?>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3 py-1.5 fw-semibold catalog-filter-btn" data-filter="on_order">
                        <i class="bi bi-clock-history text-warning me-1"></i> <?= __('on_order') ?>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3 py-1.5 fw-semibold catalog-filter-btn" data-filter="motorcycle">
                        <i class="bi bi-bicycle text-primary me-1"></i> Pikipiki (Boxer/SanLG/Honda)
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3 py-1.5 fw-semibold catalog-filter-btn" data-filter="bajaj">
                        <i class="bi bi-truck-front text-info me-1"></i> Bajaji (TVS/Bajaj RE)
                    </button>
                </div>
                <div class="small text-muted">
                    Zinazoonekana: <strong id="catalogVisibleCount" class="text-dark"><?= count($allProducts ?? []) ?></strong> vipuri
                </div>
            </div>

            <!-- Product Grid -->
            <?php if (empty($allProducts)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-inbox fs-1 text-muted"></i>
                    <p class="text-muted mt-2"><?= __('no_parts_found') ?></p>
                </div>
            <?php else: ?>
                <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4" id="catalogProductGrid">
                    <?php foreach ($allProducts as $p): 
                        $isInStock = !empty($p['in_stock']);
                        $stockState = $isInStock ? 'in_stock' : 'on_order';
                    ?>
                    <div class="col catalog-product-item"
                         data-name="<?= htmlspecialchars(strtolower($p['name'])) ?>"
                         data-sku="<?= htmlspecialchars(strtolower($p['sku'])) ?>"
                         data-category="<?= htmlspecialchars(strtolower($p['category_name'])) ?>"
                         data-stock="<?= $stockState ?>"
                         data-vehicle="<?= htmlspecialchars(strtolower($p['compatible_models'] ?? '')) ?>">
                        <div class="product-card-online shadow-sm">
                            <!-- Image & Badges -->
                            <div class="position-relative">
                                <a href="/index.php?route=product_detail&id=<?= $p['id'] ?>">
                                    <img src="<?= htmlspecialchars($p['image_url']) ?>" class="card-img-top" alt="<?= htmlspecialchars($p['name']) ?>"
                                         onerror="this.src='/assets/img/spare_part_placeholder.png';">
                                </a>
                                <!-- Stock status -->
                                <div class="position-absolute top-0 start-0 m-2">
                                    <?php if ($isInStock): ?>
                                        <span class="badge bg-success rounded-pill px-2.5 py-1 small shadow-sm">
                                            <i class="bi bi-check-circle-fill me-1"></i> <?= __('in_stock') ?> (<?= (int)$p['current_stock'] ?>)
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill px-2.5 py-1 small shadow-sm fw-semibold">
                                            <i class="bi bi-clock-history me-1"></i> <?= __('on_order') ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <!-- Wishlist -->
                                <form action="/index.php?route=wishlist_add" method="POST" class="position-absolute top-0 end-0 m-2">
                                    <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                                    <button type="submit" class="btn btn-light btn-sm rounded-circle shadow-sm" title="<?= __('nav_wishlist') ?>">
                                        <i class="bi bi-heart text-danger"></i>
                                    </button>
                                </form>
                            </div>

                            <!-- Content -->
                            <div class="p-3 d-flex flex-column flex-grow-1">
                                <div class="d-flex justify-content-between align-items-center small text-muted mb-1">
                                    <span class="text-truncate" style="max-width: 140px;"><?= htmlspecialchars($p['category_name']) ?></span>
                                    <span class="badge bg-light text-dark border">SKU: <?= htmlspecialchars($p['sku']) ?></span>
                                </div>

                                <h6 class="fw-bold mb-2">
                                    <a href="/index.php?route=product_detail&id=<?= $p['id'] ?>" class="text-dark text-decoration-none">
                                        <?= htmlspecialchars($p['name']) ?>
                                    </a>
                                </h6>

                                <?php if (!empty($p['compatible_models'])): ?>
                                    <div class="mb-3">
                                        <span class="badge-vehicle text-truncate d-inline-block" style="max-width: 100%;" title="<?= htmlspecialchars($p['compatible_models']) ?>">
                                            <i class="bi bi-bicycle"></i> <?= htmlspecialchars($p['compatible_models']) ?>
                                        </span>
                                    </div>
                                <?php endif; ?>

                                <!-- Explicit Price Display (Never Hidden) -->
                                <div class="mt-auto pt-2 border-top">
                                    <div class="d-flex justify-content-between align-items-baseline mb-2">
                                        <small class="text-muted fw-semibold text-uppercase" style="font-size: 10px;">
                                            <?= __('selling_price') ?>:
                                        </small>
                                        <span class="fs-5 fw-extrabold text-primary">
                                            TSh <?= number_format($p['selling_price'], 2) ?>
                                        </span>
                                    </div>

                                    <?php if ($isInStock): ?>
                                        <form action="/index.php?route=cart_add" method="POST">
                                            <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                                            <input type="hidden" name="quantity" value="1">
                                            <button type="submit" class="btn btn-store-primary btn-sm rounded-pill w-100 shadow-sm d-flex align-items-center justify-content-center gap-1">
                                                <i class="bi bi-cart-plus"></i> <?= __('add_to_cart') ?>
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <form action="/index.php?route=request_out_of_stock" method="POST">
                                            <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                                            <button type="submit" class="btn btn-outline-warning btn-sm rounded-pill w-100 fw-semibold text-dark shadow-sm d-flex align-items-center justify-content-center gap-1" title="<?= __('order_on_request') ?>: TSh <?= number_format($p['selling_price'], 2) ?>">
                                                <i class="bi bi-telephone-outbound"></i> <?= __('order_on_request') ?>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Instant Client-Side Catalog Search & Filter Script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('catalogQuickSearch');
    const filterButtons = document.querySelectorAll('.catalog-filter-btn');
    const productCards = document.querySelectorAll('.catalog-product-item');
    const counterDisplay = document.getElementById('catalogVisibleCount');
    let currentFilter = 'all';

    function updateVisibility() {
        const query = (searchInput ? searchInput.value : '').toLowerCase().trim();
        let visibleCount = 0;

        productCards.forEach(card => {
            const name = (card.getAttribute('data-name') || '').toLowerCase();
            const sku = (card.getAttribute('data-sku') || '').toLowerCase();
            const cat = (card.getAttribute('data-category') || '').toLowerCase();
            const stock = card.getAttribute('data-stock'); // 'in_stock' or 'on_order'
            const vehicle = (card.getAttribute('data-vehicle') || '').toLowerCase();

            const matchesQuery = !query || name.includes(query) || sku.includes(query) || cat.includes(query) || vehicle.includes(query);
            
            let matchesFilter = true;
            if (currentFilter === 'in_stock') {
                matchesFilter = (stock === 'in_stock');
            } else if (currentFilter === 'on_order') {
                matchesFilter = (stock === 'on_order');
            } else if (currentFilter === 'motorcycle') {
                matchesFilter = vehicle.includes('motorcycle') || vehicle.includes('boxer') || vehicle.includes('sanlg') || vehicle.includes('honda');
            } else if (currentFilter === 'bajaj') {
                matchesFilter = vehicle.includes('bajaj') || vehicle.includes('tvs') || vehicle.includes('king');
            }

            if (matchesQuery && matchesFilter) {
                card.style.display = '';
                visibleCount++;
            } else {
                card.style.display = 'none';
            }
        });

        if (counterDisplay) {
            counterDisplay.textContent = visibleCount;
        }
    }

    if (searchInput) {
        searchInput.addEventListener('input', updateVisibility);
    }

    filterButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            filterButtons.forEach(b => {
                b.classList.remove('active', 'btn-store-primary', 'text-white');
                b.classList.add('btn-outline-secondary');
            });
            this.classList.remove('btn-outline-secondary');
            this.classList.add('active', 'btn-store-primary', 'text-white');
            currentFilter = this.getAttribute('data-filter') || 'all';
            updateVisibility();
        });
    });
});
</script>
